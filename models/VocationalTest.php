<?php
class VocationalTest extends BaseModel
{
    protected $table = 'resultados_test';

    public function createTest($usuarioId, $respuestas)
    {
        // Calculate scores
        $puntajes = $this->calculateScores($respuestas);

        // Create test result
        $testId = $this->create([
            'usuario_id' => $usuarioId,
            'puntajes_json' => json_encode($puntajes)
        ]);

        // Store detailed answers
        $this->storeDetailedAnswers($testId, $respuestas);

        return $testId;
    }

    private function calculateScores($respuestas)
    {
        $puntajes = [];
        $conteos = [];

        // Initialize arrays
        foreach (TEST_CATEGORIES as $category) {
            $puntajes[$category] = 0;
            $conteos[$category] = 0;
        }

        // Get question details
        $questionModel = new Question();

        foreach ($respuestas as $preguntaId => $respuesta) {
            // normalize/validate answer to 1-5
            $respuestaNorm = $this->normalizeAnswer($respuesta);

            $pregunta = $questionModel->find($preguntaId);

            if (!$pregunta) {
                continue;
            }

            $categoria = $pregunta['categoria'];
            $peso = $pregunta['peso'] ?? 1;

            // For 1-5 scale: convert to 0-4 range, then multiply by peso
            $puntajes[$categoria] += (($respuestaNorm - 1) * $peso);
            $conteos[$categoria]++;
        }

        // Calculate percentages and states
        $resultados = [];
        foreach (TEST_CATEGORIES as $category) {
            if ($conteos[$category] > 0) {
                $promedio = $puntajes[$category] / $conteos[$category];
                // For 1-5 scale: max value per question is 4 (5-1), so divide by 4
                $porcentaje = ($promedio / 4) * 100;

                // Clamp percentage to the [0,100] range to avoid values >100
                if (!is_numeric($porcentaje)) {
                    $porcentaje = 0;
                }
                $porcentaje = max(0, min(100, $porcentaje));

                if ($porcentaje >= APTO_THRESHOLD) {
                    $estado = 'APTO';
                } elseif ($porcentaje >= POTENCIAL_THRESHOLD) {
                    $estado = 'POTENCIAL';
                } else {
                    $estado = 'POR REFORZAR';
                }

                $resultados[$category] = [
                    'puntaje' => $puntajes[$category],
                    'conteo' => $conteos[$category],
                    'promedio' => $promedio,
                    'porcentaje' => round($porcentaje, 2),
                    'estado' => $estado
                ];
            } else {
                $resultados[$category] = [
                    'puntaje' => 0,
                    'conteo' => 0,
                    'promedio' => 0,
                    'porcentaje' => 0,
                    'estado' => 'POR REFORZAR'
                ];
            }
        }

        return $resultados;
    }

    private function storeDetailedAnswers($testId, $respuestas)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO respuestas_detalle (test_id, pregunta_id, respuesta) VALUES (?, ?, ?)"
        );

        foreach ($respuestas as $preguntaId => $respuesta) {
            // Normalize to 1-5 range
            $normalized = $this->normalizeAnswer($respuesta);

            // Execute with explicit integer casting
            $stmt->execute([(int) $testId, (int) $preguntaId, (int) $normalized]);
        }
    }

    /**
     * Normalize and validate input values to integer 1-5 for Likert scale.
     * Accepts numeric 1-5 or strings '1'-'5'.
     * Throws Exception on unrecognized or out-of-range values.
     */
    private function normalizeAnswer($val)
    {
        if (is_numeric($val)) {
            $n = (int) $val;
            if ($n >= 1 && $n <= 5)
                return $n;
        }

        if (is_string($val)) {
            $v = trim($val);
            if (in_array($v, ['1', '2', '3', '4', '5'], true)) {
                return (int) $v;
            }
        }

        throw new Exception('Respuesta inválida: debe ser un valor entre 1 y 5. Recibido: ' . json_encode($val));
    }


    public function getResultsByUser($usuarioId)
    {
        $stmt = $this->db->prepare("
            SELECT rt.*, u.nombre, u.apellido, u.email
            FROM {$this->table} rt
            JOIN usuarios u ON rt.usuario_id = u.id
            WHERE rt.usuario_id = ?
            ORDER BY rt.fecha_test DESC
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    }

    public function getResultsByCourse($curso = null, $institucionId = null)
    {
        $sql = "
            SELECT rt.*, u.nombre, u.apellido, u.email, u.curso, u.institucion_id
            FROM {$this->table} rt
            JOIN usuarios u ON rt.usuario_id = u.id
            WHERE u.rol = 'estudiante'
        ";
        $params = [];

        if ($curso) {
            $sql .= " AND u.curso = ?";
            $params[] = $curso;
        }

        if (!empty($institucionId)) {
            $sql .= " AND u.institucion_id = ?";
            $params[] = $institucionId;
        }

        $sql .= " ORDER BY u.curso, u.apellido, u.nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getStatistics()
    {
        $stats = [];

        // Total tests
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM {$this->table}");
        $stats['total_tests'] = $stmt->fetch()['total'];

        // Tests by month
        $stmt = $this->db->query("
            SELECT DATE_FORMAT(fecha_test, '%Y-%m') as mes, COUNT(*) as cantidad
            FROM {$this->table}
            WHERE fecha_test >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY mes
            ORDER BY mes
        ");
        $stats['tests_by_month'] = $stmt->fetchAll();

        // Average scores by area — clamp individual stored percentages to 100 before averaging
        // Use JSON_EXTRACT(...)+0 to coerce to numeric, COALESCE/IFNULL to handle nulls, and LEAST(...,100) to cap.
        $sql = <<<SQL
SELECT
    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.ciencias.porcentaje')+0, 0), 100)) AS ciencias,
    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.tecnologia.porcentaje')+0, 0), 100)) AS tecnologia,
    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.humanidades.porcentaje')+0, 0), 100)) AS humanidades,
    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.artes.porcentaje')+0, 0), 100)) AS artes,
    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.salud.porcentaje')+0, 0), 100)) AS salud,
    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.negocios.porcentaje')+0, 0), 100)) AS negocios
FROM {$this->table}
SQL;

        $stmt = $this->db->query($sql);
        $stats['average_scores'] = $stmt->fetch();

        return $stats;
    }

    /**
     * Get statistics filtered by institution for DECE dashboard
     */
    public function getStatisticsByInstitution($institucionId, $curso = null, $paralelo = null)
    {
        $stats = [];
        
        // Build WHERE clause
        $where = "u.institucion_id = ?";
        $params = [$institucionId];
        
        if ($curso) {
            $where .= " AND u.curso = ?";
            $params[] = $curso;
        }
        
        if ($paralelo) {
            $where .= " AND u.paralelo = ?";
            $params[] = $paralelo;
        }
        
        // Total tests for this institution
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} rt
                JOIN usuarios u ON rt.usuario_id = u.id
                WHERE {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $stats['total_tests'] = $stmt->fetch()['total'];
        
        // Total students
        $sql = "SELECT COUNT(DISTINCT u.id) as total 
                FROM usuarios u
                WHERE {$where} AND u.rol = 'estudiante'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $stats['total_students'] = $stmt->fetch()['total'];
        
        // Average scores by area for this institution
        $sql = "SELECT
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.ciencias.porcentaje')+0, 0), 100)) AS ciencias,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.tecnologia.porcentaje')+0, 0), 100)) AS tecnologia,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.humanidades.porcentaje')+0, 0), 100)) AS humanidades,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.artes.porcentaje')+0, 0), 100)) AS artes,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.salud.porcentaje')+0, 0), 100)) AS salud,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.negocios.porcentaje')+0, 0), 100)) AS negocios
                FROM {$this->table} rt
                JOIN usuarios u ON rt.usuario_id = u.id
                WHERE {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $stats['average_scores'] = $stmt->fetch();
        
        // Tests by month for this institution
        $sql = "SELECT DATE_FORMAT(rt.fecha_test, '%Y-%m') as mes, COUNT(*) as cantidad
                FROM {$this->table} rt
                JOIN usuarios u ON rt.usuario_id = u.id
                WHERE {$where} AND rt.fecha_test >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY mes
                ORDER BY mes";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $stats['tests_by_month'] = $stmt->fetchAll();
        
        return $stats;
    }

    /**
     * Get trends by institution for DECE dashboard
     */
    public function getTrendsByInstitution($institucionId)
    {
        $sql = "SELECT 
                    DATE_FORMAT(rt.fecha_test, '%Y-%m') as mes,
                    COUNT(*) as total_tests,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.ciencias.porcentaje')+0, 0), 100)) AS ciencias,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.tecnologia.porcentaje')+0, 0), 100)) AS tecnologia,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.humanidades.porcentaje')+0, 0), 100)) AS humanidades,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.artes.porcentaje')+0, 0), 100)) AS artes,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.salud.porcentaje')+0, 0), 100)) AS salud,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.negocios.porcentaje')+0, 0), 100)) AS negocios
                FROM {$this->table} rt
                JOIN usuarios u ON rt.usuario_id = u.id
                WHERE u.institucion_id = ? AND rt.fecha_test >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY mes
                ORDER BY mes";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$institucionId]);
        return $stmt->fetchAll();
    }

    /**
     * Get distribution by area for DECE dashboard
     */
    public function getDistributionByArea($institucionId, $curso = null, $paralelo = null)
    {
        $where = "u.institucion_id = ?";
        $params = [$institucionId];
        
        if ($curso) {
            $where .= " AND u.curso = ?";
            $params[] = $curso;
        }
        
        if ($paralelo) {
            $where .= " AND u.paralelo = ?";
            $params[] = $paralelo;
        }
        
        $sql = "SELECT
                    SUM(CASE WHEN JSON_EXTRACT(puntajes_json, '$.ciencias.porcentaje') >= 70 THEN 1 ELSE 0 END) as ciencias,
                    SUM(CASE WHEN JSON_EXTRACT(puntajes_json, '$.tecnologia.porcentaje') >= 70 THEN 1 ELSE 0 END) as tecnologia,
                    SUM(CASE WHEN JSON_EXTRACT(puntajes_json, '$.humanidades.porcentaje') >= 70 THEN 1 ELSE 0 END) as humanidades,
                    SUM(CASE WHEN JSON_EXTRACT(puntajes_json, '$.artes.porcentaje') >= 70 THEN 1 ELSE 0 END) as artes,
                    SUM(CASE WHEN JSON_EXTRACT(puntajes_json, '$.salud.porcentaje') >= 70 THEN 1 ELSE 0 END) as salud,
                    SUM(CASE WHEN JSON_EXTRACT(puntajes_json, '$.negocios.porcentaje') >= 70 THEN 1 ELSE 0 END) as negocios
                FROM {$this->table} rt
                JOIN usuarios u ON rt.usuario_id = u.id
                WHERE {$where}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Get performance by course for DECE dashboard
     */
    public function getPerformanceByCourse($institucionId)
    {
        $sql = "SELECT 
                    u.curso,
                    COUNT(*) as total_tests,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.ciencias.porcentaje')+0, 0), 100)) AS ciencias,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.tecnologia.porcentaje')+0, 0), 100)) AS tecnologia,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.humanidades.porcentaje')+0, 0), 100)) AS humanidades,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.artes.porcentaje')+0, 0), 100)) AS artes,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.salud.porcentaje')+0, 0), 100)) AS salud,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.negocios.porcentaje')+0, 0), 100)) AS negocios
                FROM {$this->table} rt
                JOIN usuarios u ON rt.usuario_id = u.id
                WHERE u.institucion_id = ? AND u.curso IS NOT NULL
                GROUP BY u.curso
                ORDER BY u.curso";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$institucionId]);
        return $stmt->fetchAll();
    }

    /**
     * Get performance by paralelo for DECE dashboard
     */
    public function getPerformanceByParalelo($institucionId, $curso)
    {
        $sql = "SELECT 
                    u.paralelo,
                    COUNT(*) as total_tests,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.ciencias.porcentaje')+0, 0), 100)) AS ciencias,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.tecnologia.porcentaje')+0, 0), 100)) AS tecnologia,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.humanidades.porcentaje')+0, 0), 100)) AS humanidades,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.artes.porcentaje')+0, 0), 100)) AS artes,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.salud.porcentaje')+0, 0), 100)) AS salud,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.negocios.porcentaje')+0, 0), 100)) AS negocios
                FROM {$this->table} rt
                JOIN usuarios u ON rt.usuario_id = u.id
                WHERE u.institucion_id = ? AND u.curso = ? AND u.paralelo IS NOT NULL
                GROUP BY u.paralelo
                ORDER BY u.paralelo";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$institucionId, $curso]);
        return $stmt->fetchAll();
    }

    /**
     * Get student results for DECE dashboard with filters
     */
    public function getStudentResultsByInstitution($institucionId, $curso = null, $paralelo = null)
    {
        $where = "u.institucion_id = ? AND u.rol = 'estudiante'";
        $params = [$institucionId];
        
        if ($curso) {
            $where .= " AND u.curso = ?";
            $params[] = $curso;
        }
        
        if ($paralelo) {
            $where .= " AND u.paralelo = ?";
            $params[] = $paralelo;
        }
        
        $sql = "SELECT 
                    u.id, u.nombre, u.apellido, u.email, u.curso, u.paralelo, u.bachillerato,
                    rt.id as test_id, rt.fecha_test, rt.puntajes_json
                FROM usuarios u
                LEFT JOIN {$this->table} rt ON u.id = rt.usuario_id
                WHERE {$where}
                ORDER BY u.curso, u.paralelo, u.apellido, u.nombre";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
?>
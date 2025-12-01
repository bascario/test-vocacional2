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
            // normalize/validate answer to 0 or 1
            $respuestaNorm = $this->normalizeAnswer($respuesta);

            $pregunta = $questionModel->find($preguntaId);

            if (!$pregunta) {
                continue;
            }

            $categoria = $pregunta['categoria'];
            $peso = $pregunta['peso'] ?? 1;

            $puntajes[$categoria] += ($respuestaNorm * $peso);
            $conteos[$categoria]++;
        }

        // Calculate percentages and states
        $resultados = [];
        foreach (TEST_CATEGORIES as $category) {
            if ($conteos[$category] > 0) {
                $promedio = $puntajes[$category] / $conteos[$category];
                $porcentaje = ($promedio / 1) * 100; // Binary scale (max value is 1)

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
            // Force to 0 or 1: treat "1", 1, true as 1; everything else as 0
            $val = (string) trim((string) $respuesta);
            $normalized = ($val === '1' || $val === 1 || $val === true) ? 1 : 0;

            // Execute with explicit integer casting
            $stmt->execute([(int) $testId, (int) $preguntaId, (int) $normalized]);
        }
    }

    /**
     * Normalize different possible input values to integer 0 or 1.
     * Accepts numeric 0/1, strings '0'/'1', 'si','sí','no','true','false','on','off', booleans.
     * Throws Exception on unrecognized values to avoid inserting invalid values.
     */
    private function normalizeAnswer($val)
    {
        if (is_bool($val)) {
            return $val ? 1 : 0;
        }

        if (is_numeric($val)) {
            $n = (int) $val;
            if ($n === 0 || $n === 1)
                return $n;
        }

        if (is_string($val)) {
            $v = trim(mb_strtolower($val));
            // common true values
            $truthy = ['1', 'si', 'sí', 'yes', 'y', 'true', 't', 'on'];
            $falsy = ['0', 'no', 'n', 'false', 'f', 'off'];

            if (in_array($v, $truthy, true))
                return 1;
            if (in_array($v, $falsy, true))
                return 0;
        }

        throw new Exception('Valor de respuesta inválido: ' . json_encode($val));
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
}
?>
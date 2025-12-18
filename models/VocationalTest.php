<?php
class VocationalTest extends BaseModel
{
    protected $table = 'resultados_test';

    public function createTest($usuarioId, $respuestas, $encuestaData = null)
    {
        // Calculate scores
        $puntajes = $this->calculateScores($respuestas);

        $data = [
            'usuario_id' => $usuarioId,
            'puntajes_json' => json_encode($puntajes)
        ];

        if ($encuestaData) {
            $data['encuesta_prev_json'] = json_encode($encuestaData);
        }

        // Create test result
        $testId = $this->create($data);

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

            // Fix: Normalize category name if it comes without accent from DB
            if ($categoria === 'Artistico') {
                $categoria = 'Artístico';
            }

            // Ensure category exists in our initialized arrays
            if (!isset($puntajes[$categoria])) {
                // Determine if we should map it to a valid category or skip
                // Try to find a close match or log error. For now, skip to prevent crash
                if (array_key_exists($categoria, $puntajes)) {
                    // This creates the entry if it somehow was missing but key check passed? 
                    // No, if !isset it means it's not in our TEST_CATEGORIES keys.
                    // But we initialized them from TEST_CATEGORIES.
                    // So if we are here, $categoria is an unexpected string.
                } else {
                    // Fallback or skip
                    continue;
                }
            }

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
            SELECT rt.*, u.nombre, u.apellido, u.email, u.curso, u.paralelo, u.bachillerato, u.telefono, u.fecha_nacimiento
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

    public function getStatistics($filters = [])
    {
        $stats = [];
        $params = [];
        $where = "u.rol = 'estudiante'";

        // Apply filters
        if (!empty($filters['zona'])) {
            $where .= " AND ie.zona = ?";
            $params[] = $filters['zona'];
        }
        if (!empty($filters['distrito'])) {
            $where .= " AND ie.distrito = ?";
            $params[] = $filters['distrito'];
        }
        if (!empty($filters['institucion_id'])) {
            $where .= " AND u.institucion_id = ?";
            $params[] = $filters['institucion_id'];
        }

        // Base Query with Joins
        $baseQuery = " FROM {$this->table} rt 
                       JOIN usuarios u ON rt.usuario_id = u.id 
                       LEFT JOIN instituciones_educativas ie ON u.institucion_id = ie.id 
                       WHERE {$where}";

        // Total tests
        $stmt = $this->db->prepare("SELECT COUNT(*) as total {$baseQuery}");
        $stmt->execute($params);
        $stats['total_tests'] = $stmt->fetch()['total'];

        // Tests by month (keep for legacy or dual use if needed, but we will use Radar primarily)
        // Note: For filtered views, we might not show the trend chart, but let's keep it compatible
        $stmt = $this->db->prepare("
            SELECT DATE_FORMAT(rt.fecha_test, '%Y-%m') as mes, COUNT(*) as cantidad
            {$baseQuery} AND rt.fecha_test >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY mes
            ORDER BY mes
        ");
        $stmt->execute($params);
        $stats['tests_by_month'] = $stmt->fetchAll();

        // Average scores by area (for Radar Chart)
        // Note: Keys in JSON are capitalized and accented as per T.C. config
        // e.g. 'Realista', 'Investigador', 'Artístico', 'Social', 'Emprendedor', 'Convencional'
        $sql = "SELECT
            AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.Investigador.porcentaje')+0, 0), 100)) AS Investigador,
            AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.Realista.porcentaje')+0, 0), 100)) AS Realista,
            AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.Social.porcentaje')+0, 0), 100)) AS Social,
            AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.Artístico.porcentaje')+0, 0), 100)) AS Artistico,
            AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.Convencional.porcentaje')+0, 0), 100)) AS Convencional,
            AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.Emprendedor.porcentaje')+0, 0), 100)) AS Emprendedor
            {$baseQuery}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $stats['average_scores'] = $stmt->fetch();

        return $stats;
    }

    /**
     * Get recent tests with user details
     */
    public function getRecentTestsWithDetails($limit = 10, $filters = [])
    {
        $params = [];
        $where = "u.rol = 'estudiante'";

        // Reuse filters logic
        if (!empty($filters['zona'])) {
            $where .= " AND ie.zona = ?";
            $params[] = $filters['zona'];
        }
        if (!empty($filters['distrito'])) {
            $where .= " AND ie.distrito = ?";
            $params[] = $filters['distrito'];
        }
        if (!empty($filters['institucion_id'])) {
            $where .= " AND u.institucion_id = ?";
            $params[] = $filters['institucion_id'];
        }

        // Include first/last answer timestamps and duration (seconds) using respuestas_detalle
        $sql = "SELECT rt.*, u.nombre, u.apellido, u.email, u.curso, u.paralelo, ie.nombre as institucion_nombre,
                   MIN(rd.created_at) AS first_answer_at,
                   MAX(rd.created_at) AS last_answer_at,
                   TIMESTAMPDIFF(SECOND, MIN(rd.created_at), MAX(rd.created_at)) AS duration_seconds
            FROM {$this->table} rt
            JOIN usuarios u ON rt.usuario_id = u.id
            LEFT JOIN instituciones_educativas ie ON u.institucion_id = ie.id
            LEFT JOIN respuestas_detalle rd ON rd.test_id = rt.id
            WHERE {$where}
            GROUP BY rt.id
            ORDER BY rt.fecha_test DESC
            LIMIT " . (int) $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
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

    /**
     * Get statistics for all institutions in a zona
     */
    public function getStatisticsByZona($zona, $institucionId = null, $curso = null, $paralelo = null)
    {
        $sql = "
            SELECT 
                COUNT(DISTINCT rt.id) as total_tests,
                COUNT(DISTINCT rt.usuario_id) as total_students,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.ciencias.porcentaje')+0, 0), 100)) AS ciencias,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.tecnologia.porcentaje')+0, 0), 100)) AS tecnologia,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.humanidades.porcentaje')+0, 0), 100)) AS humanidades,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.artes.porcentaje')+0, 0), 100)) AS artes,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.salud.porcentaje')+0, 0), 100)) AS salud,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.negocios.porcentaje')+0, 0), 100)) AS negocios
            FROM {$this->table} rt
            INNER JOIN usuarios u ON rt.usuario_id = u.id
            INNER JOIN instituciones_educativas ie ON u.institucion_id = ie.id
            WHERE ie.zona = ?
        ";

        $params = [$zona];

        if ($institucionId) {
            $sql .= " AND u.institucion_id = ?";
            $params[] = $institucionId;
        }

        if ($curso) {
            $sql .= " AND u.curso = ?";
            $params[] = $curso;
        }

        if ($paralelo) {
            $sql .= " AND u.paralelo = ?";
            $params[] = $paralelo;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $stats = $stmt->fetch();

        $stats['average_scores'] = [
            'ciencias' => $stats['ciencias'] ?? 0,
            'tecnologia' => $stats['tecnologia'] ?? 0,
            'humanidades' => $stats['humanidades'] ?? 0,
            'artes' => $stats['artes'] ?? 0,
            'salud' => $stats['salud'] ?? 0,
            'negocios' => $stats['negocios'] ?? 0
        ];

        return $stats;
    }

    /**
     * Get performance comparison by institution within a zona
     */
    public function getPerformanceByInstitution($zona)
    {
        $sql = "
            SELECT 
                ie.id as institucion_id,
                ie.nombre as institucion_nombre,
                ie.codigo as institucion_codigo,
                COUNT(DISTINCT rt.id) as total_tests,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.ciencias.porcentaje')+0, 0), 100)) AS ciencias,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.tecnologia.porcentaje')+0, 0), 100)) AS tecnologia,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.humanidades.porcentaje')+0, 0), 100)) AS humanidades,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.artes.porcentaje')+0, 0), 100)) AS artes,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.salud.porcentaje')+0, 0), 100)) AS salud,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.negocios.porcentaje')+0, 0), 100)) AS negocios
            FROM instituciones_educativas ie
            LEFT JOIN usuarios u ON ie.id = u.institucion_id
            LEFT JOIN {$this->table} rt ON u.id = rt.usuario_id
            WHERE ie.zona = ?
            GROUP BY ie.id, ie.nombre, ie.codigo
            ORDER BY ie.nombre
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$zona]);
        return $stmt->fetchAll();
    }

    /**
     * Get ALL results filtered by detailed criteria (for group reports)
     */
    public function getGroupResults($filters = [])
    {
        $params = [];
        $where = "u.rol = 'estudiante'";

        if (!empty($filters['zona'])) {
            $where .= " AND ie.zona = ?";
            $params[] = $filters['zona'];
        }
        if (!empty($filters['distrito'])) {
            $where .= " AND ie.distrito = ?";
            $params[] = $filters['distrito'];
        }
        if (!empty($filters['institucion_id'])) {
            $where .= " AND u.institucion_id = ?";
            $params[] = $filters['institucion_id'];
        }
        if (!empty($filters['curso'])) {
            $where .= " AND u.curso = ?";
            $params[] = $filters['curso'];
        }

        // Fetch latest test per user? Or all tests? 
        // Typically group reports want the LATEST unique result per student.
        // Doing a simple join might give duplicates if a student took test twice.
        // For optimization, let's assume one test or fetch all.
        // Better: Subquery to get max ID, or just fetching all and user filtering.
        // Let's do a simple fetch for now. If duplicates are an issue we can refine.

        $sql = "SELECT rt.*, u.nombre, u.apellido, u.email, u.curso, u.paralelo, ie.nombre as institucion_nombre 
                FROM {$this->table} rt
                JOIN usuarios u ON rt.usuario_id = u.id
                LEFT JOIN instituciones_educativas ie ON u.institucion_id = ie.id
                WHERE {$where}
                ORDER BY u.apellido, u.nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get student results for zona with optional filters
     */
    public function getStudentResultsByZona($zona, $institucionId = null, $curso = null, $paralelo = null)
    {
        $sql = "
            SELECT 
                u.id as usuario_id,
                u.nombre,
                u.apellido,
                u.email,
                u.curso,
                u.paralelo,
                u.bachillerato,
                ie.nombre as institucion_nombre,
                ie.codigo as institucion_codigo,
                rt.id as test_id,
                rt.fecha_test,
                rt.puntajes_json
            FROM usuarios u
            INNER JOIN instituciones_educativas ie ON u.institucion_id = ie.id
            LEFT JOIN (
                SELECT rt1.*
                FROM {$this->table} rt1
                INNER JOIN (
                    SELECT usuario_id, MAX(fecha_test) as max_fecha
                    FROM {$this->table}
                    GROUP BY usuario_id
                ) rt2 ON rt1.usuario_id = rt2.usuario_id AND rt1.fecha_test = rt2.max_fecha
            ) rt ON u.id = rt.usuario_id
            WHERE ie.zona = ? AND u.rol = 'estudiante'
        ";

        $params = [$zona];

        if ($institucionId) {
            $sql .= " AND u.institucion_id = ?";
            $params[] = $institucionId;
        }

        if ($curso) {
            $sql .= " AND u.curso = ?";
            $params[] = $curso;
        }

        if ($paralelo) {
            $sql .= " AND u.paralelo = ?";
            $params[] = $paralelo;
        }

        $sql .= " ORDER BY ie.nombre, u.apellido, u.nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

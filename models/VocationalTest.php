<?php
class VocationalTest extends BaseModel
{
    protected $table = 'resultados_test';

    /**
     * Crea un resultado de test y guarda respuestas detalladas.
     *
     * @param int $usuarioId ID del usuario.
     * @param array $respuestas Respuestas del test.
     * @param array|null $encuestaData Datos de la encuesta previa.
     * @return string ID del test creado.
     * @throws Exception Si el usuario no puede retomar el test.
     */
    public function createTest($usuarioId, $respuestas, $encuestaData = null)
    {
        // Verificar si el usuario puede retomar el test
        if (!$this->canRetakeTest($usuarioId)) {
            $daysUntil = $this->getDaysUntilRetake($usuarioId);
            $lastDate = $this->getLastTestDate($usuarioId);

            throw new Exception(
                "Debes esperar " . TEST_RETAKE_MONTHS . " meses desde tu último test antes de volver a realizarlo. " .
                "Último test: " . date('d/m/Y', strtotime($lastDate)) . ". " .
                "Podrás retomar el test en " . $daysUntil . " días."
            );
        }

        // Calcular puntajes
        $puntajes = $this->calculateScores($respuestas);

        $data = [
            'usuario_id' => $usuarioId,
            'puntajes_json' => json_encode($puntajes)
        ];

        if ($encuestaData) {
            $data['encuesta_prev_json'] = json_encode($encuestaData);
        }

        // Crear resultado del test
        $testId = $this->create($data);

        // Guardar respuestas detalladas
        $this->storeDetailedAnswers($testId, $respuestas);

        return $testId;
    }

    /**
     * Verificar si un usuario puede retomar el test
     * @param int $usuarioId ID del usuario
     * @return bool true si puede retomar, false si debe esperar
     */
    public function canRetakeTest($usuarioId)
    {
        $lastDate = $this->getLastTestDate($usuarioId);

        // Si no tiene tests previos, puede tomar el test
        if (!$lastDate) {
            return true;
        }

        // Calcular la fecha mínima para retomar (último test + X meses)
        $minRetakeDate = date('Y-m-d H:i:s', strtotime($lastDate . ' +' . TEST_RETAKE_MONTHS . ' months'));
        $now = date('Y-m-d H:i:s');

        // Puede retomar si la fecha actual es mayor o igual a la fecha mínima
        return $now >= $minRetakeDate;
    }

    /**
     * Obtener la fecha del último test realizado por un usuario
     * @param int $usuarioId ID del usuario
     * @return string|null Fecha del último test o null si no tiene tests
     */
    public function getLastTestDate($usuarioId)
    {
        $stmt = $this->db->prepare("
            SELECT fecha_test 
            FROM {$this->table} 
            WHERE usuario_id = ? 
            ORDER BY fecha_test DESC 
            LIMIT 1
        ");
        $stmt->execute([$usuarioId]);
        $result = $stmt->fetch();

        return $result ? $result['fecha_test'] : null;
    }

    /**
     * Calcular cuántos días faltan para poder retomar el test
     * @param int $usuarioId ID del usuario
     * @return int Número de días restantes (0 si ya puede retomar)
     */
    public function getDaysUntilRetake($usuarioId)
    {
        $lastDate = $this->getLastTestDate($usuarioId);

        // Si no tiene tests previos, puede retomar inmediatamente
        if (!$lastDate) {
            return 0;
        }

        // Calcular la fecha mínima para retomar
        $minRetakeDate = strtotime($lastDate . ' +' . TEST_RETAKE_MONTHS . ' months');
        $now = time();

        // Si ya puede retomar, retornar 0
        if ($now >= $minRetakeDate) {
            return 0;
        }

        // Calcular días restantes
        $secondsRemaining = $minRetakeDate - $now;
        $daysRemaining = ceil($secondsRemaining / (60 * 60 * 24));

        return (int) $daysRemaining;
    }

    /**
     * Obtener la fecha en que el usuario podrá retomar el test
     * @param int $usuarioId ID del usuario
     * @return string|null Fecha formateada o null si ya puede retomar
     */
    public function getNextRetakeDate($usuarioId)
    {
        $lastDate = $this->getLastTestDate($usuarioId);

        if (!$lastDate) {
            return null;
        }

        if ($this->canRetakeTest($usuarioId)) {
            return null;
        }

        $nextDate = date('Y-m-d', strtotime($lastDate . ' +' . TEST_RETAKE_MONTHS . ' months'));
        return $nextDate;
    }

    /**
     * Calcular puntajes agregados por categoría a partir de las respuestas.
     *
     * @param array $respuestas Respuestas del usuario.
     * @return array Resultados calculados por categoría.
     */
    private function calculateScores($respuestas)
    {
        $puntajes = [];
        $conteos = [];

        // Inicializar arrays
        foreach (TEST_CATEGORIES as $category) {
            $puntajes[$category] = 0;
            $conteos[$category] = 0;
        }

        // Obtener detalles de la pregunta
        $questionModel = new Question();

        foreach ($respuestas as $preguntaId => $respuesta) {
            // normalizar/validar respuesta a 1-5
            $respuestaNorm = $this->normalizeAnswer($respuesta);

            $pregunta = $questionModel->find($preguntaId);

            if (!$pregunta) {
                continue;
            }

            $categoria = $pregunta['categoria'];

            // Normalizar nombre de categoría si viene sin tilde desde la BD
            if ($categoria === 'Artistico') {
                $categoria = 'Artístico';
            }

            // Asegurar que la categoría existe en nuestros arrays inicializados
            if (!isset($puntajes[$categoria])) {
                // Determinar si debemos mapear a una categoría válida o saltar
                // Intentar encontrar una coincidencia cercana o registrar error. Por ahora, saltar
                if (array_key_exists($categoria, $puntajes)) {
                    // Esto crea la entrada si faltaba pero la comprobación de clave pasó?
                    // No, si !isset significa que no está en nuestras claves TEST_CATEGORIES.
                } else {
                    // Fallback o saltar
                    continue;
                }
            }

            $peso = $pregunta['peso'] ?? 1;

            // Para escala 1-5: convertir a rango 0-4 y multiplicar por peso
            $puntajes[$categoria] += (($respuestaNorm - 1) * $peso);
            $conteos[$categoria]++;
        }

        // Calcular porcentajes y estados (APTO / POTENCIAL / POR REFORZAR)
        $resultados = [];
        foreach (TEST_CATEGORIES as $category) {
            if ($conteos[$category] > 0) {
                $promedio = $puntajes[$category] / $conteos[$category];
                // Para escala 1-5: valor máximo por pregunta es 4 (5-1), así que dividir por 4
                $porcentaje = ($promedio / 4) * 100;

                // Limitar porcentaje al rango [0,100] para evitar valores >100
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

    /**
     * Guardar respuestas detalladas en la tabla `respuestas_detalle`.
     *
     * @param int $testId ID del test.
     * @param array $respuestas Array de respuestas.
     */
    private function storeDetailedAnswers($testId, $respuestas)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO respuestas_detalle (test_id, pregunta_id, respuesta) VALUES (?, ?, ?)"
        );

        foreach ($respuestas as $preguntaId => $respuesta) {
            // Normalizar a rango 1-5
            $normalized = $this->normalizeAnswer($respuesta);

            // Ejecutar con casteo a entero
            $stmt->execute([(int) $testId, (int) $preguntaId, (int) $normalized]);
        }
    }

    /**
     * Normaliza y valida una respuesta a entero entre 1 y 5.
     * Acepta numéricos 1-5 o cadenas '1'-'5'.
     *
     * @param mixed $val Valor a normalizar.
     * @return int Valor normalizado (1-5).
     * @throws Exception Si el valor no es válido o está fuera de rango.
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


    /**
     * Obtener resultados de tests por usuario.
     *
     * @param int $usuarioId ID del usuario.
     * @return array Lista de resultados.
     */
    public function getResultsByUser($usuarioId)
    {
        $stmt = $this->db->prepare("
            SELECT rt.*, u.nombre, u.apellido, u.email, u.curso, u.paralelo, u.telefono, u.fecha_nacimiento
            FROM {$this->table} rt
            JOIN usuarios u ON rt.usuario_id = u.id
            WHERE rt.usuario_id = ?
            ORDER BY rt.fecha_test DESC
        ");
        $stmt->execute([$usuarioId]);
        return $stmt->fetchAll();
    }

    /**
     * Obtener resultados filtrados por curso e institución.
     */
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

    /**
     * Obtener estadísticas generales filtradas.
     *
     * @param array $filters Filtros (zona, distrito, institucion_id, amie).
     * @return array Estadísticas (total_tests, tests_by_month, average_scores).
     */
    public function getStatistics($filters = [])
    {
        $stats = [];
        $mappings = [
            'zona' => 'ie.zona',
            'distrito' => 'ie.distrito',
            'institucion_id' => 'u.institucion_id',
            'amie' => 'ie.codigo'
        ];

        $queryRef = QueryHelper::buildWhereClause($filters, $mappings);
        $dynamicWhere = $queryRef['where'];
        $params = $queryRef['params'];

        // Restricción base
        $whereClauses = array_merge(["u.rol = 'estudiante'"], $dynamicWhere);
        $whereSql = implode(" AND ", $whereClauses);

        // Consulta base con Joins
        $baseQuery = " FROM {$this->table} rt 
                       JOIN usuarios u ON rt.usuario_id = u.id 
                       LEFT JOIN instituciones_educativas ie ON u.institucion_id = ie.id 
                       WHERE {$whereSql}";

        // Total tests
        $stmt = $this->db->prepare("SELECT COUNT(*) as total {$baseQuery}");
        $stmt->execute($params);
        $stats['total_tests'] = $stmt->fetch()['total'];

        // Tests por mes
        $stmt = $this->db->prepare("
            SELECT DATE_FORMAT(rt.fecha_test, '%Y-%m') as mes, COUNT(*) as cantidad
            {$baseQuery} AND rt.fecha_test >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY mes
            ORDER BY mes
        ");
        $stmt->execute($params);
        $stats['tests_by_month'] = $stmt->fetchAll();

        // Promedios por área
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
     * Obtener tests recientes con detalles de usuario y tiempos.
     */
    public function getRecentTestsWithDetails($limit = 10, $filters = [])
    {
        $mappings = [
            'zona' => 'ie.zona',
            'distrito' => 'ie.distrito',
            'institucion_id' => 'u.institucion_id',
            'amie' => 'ie.codigo'
        ];

        $queryRef = QueryHelper::buildWhereClause($filters, $mappings);
        $dynamicWhere = $queryRef['where'];
        $params = $queryRef['params'];

        // Restricción base
        $whereClauses = array_merge(["u.rol = 'estudiante'"], $dynamicWhere);
        $whereSql = implode(" AND ", $whereClauses);

        // Incluir timestamps de primera/última respuesta y duración (segundos) usando respuestas_detalle
        $sql = "SELECT rt.*, u.nombre, u.apellido, u.email, u.curso, u.paralelo, ie.nombre as institucion_nombre,
                   MIN(rd.created_at) AS first_answer_at,
                   MAX(rd.created_at) AS last_answer_at,
                   TIMESTAMPDIFF(SECOND, MIN(rd.created_at), MAX(rd.created_at)) AS duration_seconds
            FROM {$this->table} rt
            JOIN usuarios u ON rt.usuario_id = u.id
            LEFT JOIN instituciones_educativas ie ON u.institucion_id = ie.id
            LEFT JOIN respuestas_detalle rd ON rd.test_id = rt.id
            WHERE {$whereSql}
            GROUP BY rt.id
            ORDER BY rt.fecha_test DESC
            LIMIT " . (int) $limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtener estadísticas por institución para el dashboard DECE.
     */
    public function getStatisticsByInstitution($institucionId, $curso = null, $paralelo = null)
    {
        $stats = [];

        // Construir cláusula WHERE
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

        // Total de tests para esta institución
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} rt
                JOIN usuarios u ON rt.usuario_id = u.id
                WHERE {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $stats['total_tests'] = $stmt->fetch()['total'];

        // Total de estudiantes
        $sql = "SELECT COUNT(DISTINCT u.id) as total 
                FROM usuarios u
                WHERE {$where} AND u.rol = 'estudiante'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $stats['total_students'] = $stmt->fetch()['total'];

        // Promedios por área para esta institución
        $sql = "SELECT
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Investigador.porcentaje')+0, 0), 100)) AS Investigador,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Realista.porcentaje')+0, 0), 100)) AS Realista,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Social.porcentaje')+0, 0), 100)) AS Social,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Artístico.porcentaje')+0, 0), 100)) AS Artístico,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Emprendedor.porcentaje')+0, 0), 100)) AS Emprendedor,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Convencional.porcentaje')+0, 0), 100)) AS Convencional
                FROM {$this->table} rt
                JOIN usuarios u ON rt.usuario_id = u.id
                WHERE {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $stats['average_scores'] = $stmt->fetch();

        // Tests por mes para esta institución
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
     * Obtener tendencias por institución para dashboard DECE.
     */
    public function getTrendsByInstitution($institucionId)
    {
        $sql = "SELECT 
                    DATE_FORMAT(rt.fecha_test, '%Y-%m') as mes,
                    COUNT(*) as total_tests,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Investigador.porcentaje')+0, 0), 100)) AS Investigador,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Realista.porcentaje')+0, 0), 100)) AS Realista,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Social.porcentaje')+0, 0), 100)) AS Social,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Artístico.porcentaje')+0, 0), 100)) AS Artístico,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Emprendedor.porcentaje')+0, 0), 100)) AS Emprendedor,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Convencional.porcentaje')+0, 0), 100)) AS Convencional
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
     * Obtener distribución por área para dashboard DECE.
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
                    SUM(CASE WHEN JSON_EXTRACT(puntajes_json, '$.Investigador.porcentaje') >= 70 THEN 1 ELSE 0 END) as Investigador,
                    SUM(CASE WHEN JSON_EXTRACT(puntajes_json, '$.Realista.porcentaje') >= 70 THEN 1 ELSE 0 END) as Realista,
                    SUM(CASE WHEN JSON_EXTRACT(puntajes_json, '$.Social.porcentaje') >= 70 THEN 1 ELSE 0 END) as Social,
                    SUM(CASE WHEN JSON_EXTRACT(puntajes_json, '$.Artístico.porcentaje') >= 70 THEN 1 ELSE 0 END) as Artístico,
                    SUM(CASE WHEN JSON_EXTRACT(puntajes_json, '$.Emprendedor.porcentaje') >= 70 THEN 1 ELSE 0 END) as Emprendedor,
                    SUM(CASE WHEN JSON_EXTRACT(puntajes_json, '$.Convencional.porcentaje') >= 70 THEN 1 ELSE 0 END) as Convencional
                FROM {$this->table} rt
                JOIN usuarios u ON rt.usuario_id = u.id
                WHERE {$where}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Obtener rendimiento por curso para dashboard DECE.
     */
    public function getPerformanceByCourse($institucionId)
    {
        $sql = "SELECT 
                    u.curso,
                    COUNT(*) as total_tests,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Investigador.porcentaje')+0, 0), 100)) AS Investigador,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Realista.porcentaje')+0, 0), 100)) AS Realista,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Social.porcentaje')+0, 0), 100)) AS Social,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Artístico.porcentaje')+0, 0), 100)) AS Artístico,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Emprendedor.porcentaje')+0, 0), 100)) AS Emprendedor,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Convencional.porcentaje')+0, 0), 100)) AS Convencional
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
     * Obtener rendimiento por paralelo para dashboard DECE.
     */
    public function getPerformanceByParalelo($institucionId, $curso)
    {
        $sql = "SELECT 
                    u.paralelo,
                    COUNT(*) as total_tests,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Investigador.porcentaje')+0, 0), 100)) AS Investigador,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Realista.porcentaje')+0, 0), 100)) AS Realista,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Social.porcentaje')+0, 0), 100)) AS Social,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Artístico.porcentaje')+0, 0), 100)) AS Artístico,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Emprendedor.porcentaje')+0, 0), 100)) AS Emprendedor,
                    AVG(LEAST(IFNULL(JSON_EXTRACT(puntajes_json, '$.Convencional.porcentaje')+0, 0), 100)) AS Convencional
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
     * Obtener resultados de estudiantes para dashboard DECE con filtros.
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
                    u.id, u.nombre, u.apellido, u.email, u.curso, u.paralelo,
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
     * Obtener estadísticas para todas las instituciones en una zona.
     */
    public function getStatisticsByZona($zona, $institucionId = null, $curso = null, $paralelo = null, $amie = null)
    {
        $sql = "
            SELECT 
                COUNT(DISTINCT rt.id) as total_tests,
                COUNT(DISTINCT rt.usuario_id) as total_students,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.Investigador.porcentaje')+0, 0), 100)) AS Investigador,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.Realista.porcentaje')+0, 0), 100)) AS Realista,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.Social.porcentaje')+0, 0), 100)) AS Social,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.Artístico.porcentaje')+0, 0), 100)) AS Artístico,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.Emprendedor.porcentaje')+0, 0), 100)) AS Emprendedor,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.Convencional.porcentaje')+0, 0), 100)) AS Convencional
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

        if ($amie) {
            $sql .= " AND ie.codigo = ?";
            $params[] = $amie;
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
            'Investigador' => $stats['Investigador'] ?? 0,
            'Realista' => $stats['Realista'] ?? 0,
            'Social' => $stats['Social'] ?? 0,
            'Artístico' => $stats['Artístico'] ?? 0,
            'Emprendedor' => $stats['Emprendedor'] ?? 0,
            'Convencional' => $stats['Convencional'] ?? 0
        ];

        return $stats;
    }

    /**
     * Obtener comparación de rendimiento por institución dentro de una zona.
     */
    public function getPerformanceByInstitution($zona)
    {
        $sql = "
            SELECT 
                ie.id as institucion_id,
                ie.nombre as institucion_nombre,
                ie.codigo as institucion_codigo,
                COUNT(DISTINCT rt.id) as total_tests,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.Investigador.porcentaje')+0, 0), 100)) AS Investigador,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.Realista.porcentaje')+0, 0), 100)) AS Realista,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.Social.porcentaje')+0, 0), 100)) AS Social,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.Artístico.porcentaje')+0, 0), 100)) AS Artístico,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.Emprendedor.porcentaje')+0, 0), 100)) AS Emprendedor,
                AVG(LEAST(IFNULL(JSON_EXTRACT(rt.puntajes_json, '$.Convencional.porcentaje')+0, 0), 100)) AS Convencional
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
     * Obtener TODOS los resultados filtrados por criterio detallado (para reportes grupales).
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
        if (!empty($filters['amie'])) {
            $where .= " AND ie.codigo = ?";
            $params[] = $filters['amie'];
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
     * Obtener resultados de estudiantes para una zona con filtros opcionales.
     */
    public function getStudentResultsByZona($zona, $institucionId = null, $curso = null, $paralelo = null, $amie = null)
    {
        $sql = "
            SELECT 
                u.id,
                u.nombre,
                u.apellido,
                u.email,
                u.curso,
                u.paralelo,

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

        if ($amie) {
            $sql .= " AND ie.codigo = ?";
            $params[] = $amie;
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

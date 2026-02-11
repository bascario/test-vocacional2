<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/VocationalTest.php';
require_once __DIR__ . '/../models/Institucion.php';
require_once __DIR__ . '/../utils/ReportHelper.php';

class ReportService
{
    private $userModel;
    private $testModel;
    private $institucionModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->testModel = new VocationalTest();
        $this->institucionModel = new Institucion();
    }

    /**
     * Valida si el usuario actual tiene permiso para ver el reporte del estudiante objetivo.
     *
     * @param array $currentUser Datos del usuario actual.
     * @param int $targetStudentId ID del estudiante objetivo.
     * @throws Exception Si el acceso es denegado.
     */
    public function validateIndividualReportAccess($currentUser, $targetStudentId)
    {
        $currentUserId = $currentUser['id'];
        $currentUserRole = $currentUser['rol'] ?? '';

        $isOwn = ($currentUserId == $targetStudentId);
        $isAdmin = ($currentUserRole === 'administrador');
        $isDece = ($currentUserRole === 'dece');
        $isZonal = ($currentUserRole === 'zonal');

        if (!$isOwn && !$isAdmin && !$isDece && !$isZonal) {
            throw new Exception('Acceso denegado: no tienes permiso para descargar este reporte');
        }

        // Verificaciones específicas para DECE
        if ($isDece && !$isOwn) {
            // Asegurar que el usuario actual (DECE) tenga institución asignada
            if (empty($currentUser['institucion_id'])) {
                // Si no está en el array $currentUser, obtenerlo
                $currentUser = $this->userModel->find($currentUserId);
            }

            if (empty($currentUser['institucion_id'])) {
                throw new Exception('Acceso denegado: tu cuenta no está vinculada a una institución');
            }

            // Verificar que el estudiante solicitado pertenezca a la misma institución
            $student = $this->userModel->find($targetStudentId);
            if (empty($student) || ($student['institucion_id'] ?? null) != $currentUser['institucion_id']) {
                throw new Exception('Acceso denegado: el estudiante no pertenece a su institución');
            }
        }

        // Verificaciones específicas para Zonal
        if ($isZonal && !$isOwn) {
            if (empty($currentUser['zona_id'])) {
                $currentUser = $this->userModel->find($currentUserId);
            }

            if (empty($currentUser['zona_id'])) {
                throw new Exception('Acceso denegado: tu cuenta no está vinculada a una zona');
            }

            $student = $this->userModel->find($targetStudentId);
            if (empty($student) || empty($student['institucion_id'])) {
                throw new Exception('Acceso denegado: el estudiante no está vinculado a una institución');
            }

            $studentInst = $this->institucionModel->find($student['institucion_id']);
            if (empty($studentInst) || ($studentInst['zona'] ?? null) != $currentUser['zona_id']) {
                throw new Exception('Acceso denegado: el estudiante no pertenece a su zona');
            }
        }
    }

    /**
     * Obtiene los datos necesarios para generar el reporte individual.
     *
     * @param int $studentId ID del estudiante.
     * @return array Datos completos para el reporte.
     * @throws Exception Si no se encuentran resultados.
     */
    public function getIndividualReportData($studentId)
    {
        $results = $this->testModel->getResultsByUser($studentId);

        if (empty($results)) {
            throw new Exception("No se encontraron resultados para este estudiante");
        }

        $latestResult = $results[0];
        $scores = json_decode($latestResult['puntajes_json'], true);

        // Obtener información adicional
        $studentInfo = $this->userModel->find($studentId);
        $deceUser = null;
        $institution = null;
        if (!empty($studentInfo['institucion_id'])) {
            $deceUser = $this->userModel->getDeceByInstitution($studentInfo['institucion_id']);
            $institution = $this->institucionModel->find($studentInfo['institucion_id']);
        }

        // Calcular métricas
        $normalizedScores = ReportHelper::normalizeScores($scores);
        $differentiation = ReportHelper::calculateDifferentiation($normalizedScores);
        $competence = ReportHelper::calculateCompetence($normalizedScores);
        $topAreas = ReportHelper::getTopAreas($normalizedScores, 3);

        // Validación QR
        $validationUrl = ReportHelper::getValidationUrl($latestResult['id']);
        $qrCodeBase64 = ReportHelper::generateQRCodeBase64($validationUrl);

        return [
            'result' => $latestResult,
            'student' => $studentInfo,
            'institution' => $institution,
            'deceUser' => $deceUser,
            'scores' => $displayScores = $normalizedScores, // las claves de normalizedScores coinciden con la visualización
            'metrics' => [
                'differentiation' => $differentiation,
                'competence' => $competence,
                'topAreas' => $topAreas
            ],
            'qrCode' => $qrCodeBase64
        ];
    }

    /**
     * Obtiene los datos necesarios para generar el reporte grupal.
     *
     * @param array $filters Filtros para seleccionar el grupo de estudiantes.
     * @param array $currentUser Datos del usuario que solicita el reporte.
     * @return array Datos procesados para el reporte grupal.
     * @throws Exception Si no se encuentran resultados o hay error de permisos.
     */
    public function getGroupReportData($filters, $currentUser)
    {
        // Verificación de permiso para DECE
        if (!empty($currentUser['rol']) && $currentUser['rol'] === 'dece') {
            if (empty($currentUser['institucion_id'])) {
                $currentUser = $this->userModel->find($currentUser['id']);
            }
            if (empty($currentUser) || empty($currentUser['institucion_id'])) {
                throw new Exception('Acceso denegado: tu cuenta no está vinculada a una institución');
            }
            // Forzar filtro
            $filters['institucion_id'] = $currentUser['institucion_id'];
        }

        $results = $this->testModel->getGroupResults($filters);

        if (empty($results)) {
            throw new Exception("No se encontraron resultados con los filtros seleccionados");
        }

        // Calcular estadísticas grupales
        $totals = ['Realista' => 0, 'Investigador' => 0, 'Artístico' => 0, 'Social' => 0, 'Emprendedor' => 0, 'Convencional' => 0];
        $counts = ['Realista' => 0, 'Investigador' => 0, 'Artístico' => 0, 'Social' => 0, 'Emprendedor' => 0, 'Convencional' => 0];

        foreach ($results as $row) {
            $scores = json_decode($row['puntajes_json'], true);
            if (is_array($scores)) {
                foreach ($totals as $cat => $val) {
                    $pct = 0;
                    if (isset($scores[$cat])) {
                        if (is_array($scores[$cat]))
                            $pct = $scores[$cat]['porcentaje'] ?? 0;
                        else
                            $pct = $scores[$cat];
                    }
                    $totals[$cat] += $pct;
                    $counts[$cat]++;
                }
            }
        }

        $groupAverages = [];
        $numStudents = count($results);
        if ($numStudents > 0) {
            foreach ($totals as $cat => $sum) {
                $groupAverages[$cat] = round($sum / $numStudents, 2);
            }
        }

        // Área destacada
        $topAreaName = null;
        $topAreaScore = -1;
        foreach ($groupAverages as $cat => $avg) {
            if ($avg > $topAreaScore) {
                $topAreaScore = $avg;
                $topAreaName = $cat;
            }
        }

        // QR
        $validationData = $filters['institucion_id'] ?? 'group_report';
        $validationUrl = ReportHelper::getValidationUrl($validationData);
        $qrCodeBase64 = ReportHelper::generateQRCodeBase64($validationUrl);

        // Texto de información de filtros
        // Podríamos necesitar obtener el nombre de la institución si el ID está presente
        $institutionName = 'Todas las Instituciones';
        if (!empty($filters['institucion_id'])) {
            $inst = $this->institucionModel->find($filters['institucion_id']);
            if ($inst)
                $institutionName = $inst['nombre'];
        }

        $filterInfo = [
            'zona' => $filters['zona'],
            'distrito' => $filters['distrito'],
            'institution' => $institutionName,
            'course' => $filters['curso'],
            'amie' => $filters['amie']
        ];

        return [
            'results' => $results,
            'averages' => $groupAverages,
            'topArea' => ['name' => $topAreaName, 'score' => $topAreaScore],
            'qrCode' => $qrCodeBase64,
            'filterInfo' => $filterInfo,
            'currentUser' => $currentUser // pasado de vuelta para la firma
        ];
    }
}

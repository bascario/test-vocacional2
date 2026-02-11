<?php
require_once 'models/User.php';
require_once 'models/VocationalTest.php';
require_once 'models/Institucion.php';

/**
 * Controlador para la gestión de la zona (Coordinación Zonal).
 * Permite visualizar estadísticas, reportes y exportar datos de las instituciones de la zona.
 */
class ZonaController
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
     * Página principal del dashboard Zonal.
     * Muestra estadísticas agregadas por zona y filtros por institución.
     */
    public function index()
    {
        // Verificar rol Zonal
        if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'zonal') {
            $_SESSION['error'] = 'Acceso no autorizado';
            header('Location: /test-vocacional/login');
            exit;
        }

        // Obtener usuario zonal actual
        $currentUser = $this->userModel->find($_SESSION['user_id']);

        if (empty($currentUser['zona_id'])) {
            $_SESSION['error'] = 'Tu cuenta no está vinculada a una zona. Contacta al administrador.';
            header('Location: /test-vocacional/login');
            exit;
        }

        $zonaId = $currentUser['zona_id'];

        // Obtener nombre de la zona (asumiendo que zona_id es el nombre)
        // En un escenario real, podría haber una tabla separada de zonas
        $institutions = $this->institucionModel->getByZona($zonaId);

        if (empty($institutions)) {
            $_SESSION['error'] = 'No hay instituciones asignadas a tu zona';
            header('Location: /test-vocacional/login');
            exit;
        }

        $zona = $zonaId; // Usando zona_id como nombre de zona

        $institucionId = $_GET['institucion'] ?? null;
        $amie = $_GET['amie'] ?? null;
        $curso = $_GET['curso'] ?? null;
        $paralelo = $_GET['paralelo'] ?? null;

        // Obtener estadísticas
        $stats = $this->testModel->getStatisticsByZona($zona, $institucionId, $curso, $paralelo, $amie);

        // Obtener rendimiento por institución
        $performanceByInstitution = $this->testModel->getPerformanceByInstitution($zona);

        // Obtener cursos y paralelos disponibles (si se seleccionó institución)
        $courses = [];
        $paralelos = [];
        if ($institucionId) {
            $courses = $this->userModel->getCoursesByInstitution($institucionId);
            if ($curso) {
                $paralelos = $this->userModel->getParalelosByCourse($institucionId, $curso);
            }
        }

        // Obtener resultados de estudiantes
        $studentResults = $this->testModel->getStudentResultsByZona($zona, $institucionId, $curso, $paralelo, $amie);

        // Contar instituciones y total de estudiantes
        $totalInstitutions = count($institutions);
        $totalStudents = count($studentResults);

        require_once 'views/zona_dashboard.php';
    }

    /**
     * Endpoint AJAX para obtener cursos de una institución seleccionada.
     */
    public function getCourses()
    {
        header('Content-Type: application/json');

        if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'zonal') {
            echo json_encode(['error' => 'Acceso no autorizado']);
            exit;
        }

        $institucionId = $_GET['institucion'] ?? null;

        if (!$institucionId) {
            echo json_encode(['courses' => []]);
            exit;
        }

        $courses = $this->userModel->getCoursesByInstitution($institucionId);
        echo json_encode(['courses' => $courses]);
        exit;
    }

    /**
     * Endpoint AJAX para obtener paralelos de una institución y curso seleccionados.
     */
    public function getParalelos()
    {
        header('Content-Type: application/json');

        if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'zonal') {
            echo json_encode(['error' => 'Acceso no autorizado']);
            exit;
        }

        $institucionId = $_GET['institucion'] ?? null;
        $curso = $_GET['curso'] ?? null;

        if (!$institucionId || !$curso) {
            echo json_encode(['paralelos' => []]);
            exit;
        }

        $paralelos = $this->userModel->getParalelosByCourse($institucionId, $curso);
        echo json_encode(['paralelos' => $paralelos]);
        exit;
    }

    /**
     * Generar reporte zonal (Vista HTML para imprimir).
     */
    public function generateZonaReport()
    {
        if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'zonal') {
            $_SESSION['error'] = 'Acceso no autorizado';
            header('Location: /test-vocacional/login');
            exit;
        }

        try {
            $currentUser = $this->userModel->find($_SESSION['user_id']);
            $zonaId = $currentUser['zona_id'] ?? null;

            if (!$zonaId) {
                throw new Exception('Tu cuenta no está vinculada a una zona');
            }

            $zona = $zonaId;
            $institucionId = $_GET['institucion'] ?? null;
            $curso = $_GET['curso'] ?? null;
            $paralelo = $_GET['paralelo'] ?? null;

            // Usar método unificado para resultados
            $filters = [
                'zona' => $zona,
                'institucion_id' => $institucionId,
                'curso' => $curso,
                'paralelo' => $paralelo,
                'amie' => $_GET['amie'] ?? null
            ];
            $results = $this->testModel->getGroupResults($filters);

            if (empty($results)) {
                throw new Exception("No se encontraron resultados para los filtros seleccionados");
            }

            // Calcular estadísticas grupales
            $totals = ['Realista' => 0, 'Investigador' => 0, 'Artístico' => 0, 'Social' => 0, 'Emprendedor' => 0, 'Convencional' => 0];
            $numStudents = count($results);

            foreach ($results as $row) {
                $scores = json_decode($row['puntajes_json'], true);
                if (is_array($scores)) {
                    foreach ($totals as $cat => $val) {
                        $pct = isset($scores[$cat]) ? (is_array($scores[$cat]) ? ($scores[$cat]['porcentaje'] ?? 0) : $scores[$cat]) : 0;
                        $totals[$cat] += $pct;
                    }
                }
            }

            $groupAverages = [];
            if ($numStudents > 0) {
                foreach ($totals as $cat => $sum) {
                    $groupAverages[$cat] = round($sum / $numStudents, 2);
                }
            }

            // Identificar área más destacada
            $topAreaName = null;
            $topAreaScore = -1;
            foreach ($groupAverages as $cat => $avg) {
                if ($avg > $topAreaScore) {
                    $topAreaScore = $avg;
                    $topAreaName = $cat;
                }
            }

            // Preparar datos para la vista
            // Si se selecciona institución específica, obtener nombre, si no Genérico
            $instName = 'Todas las Instituciones de la Zona';
            if ($institucionId) {
                $inst = $this->institucionModel->find($institucionId);
                if ($inst)
                    $instName = $inst['nombre'];
            }

            $filterInfo = [
                'institution' => $instName,
                'zona' => $zona,
                'distrito' => 'Todos', // Podría detectar si se filtra por institución
                'course' => ($curso ? $curso . ($paralelo ? ' - ' . $paralelo : '') : 'Todos los cursos')
            ];

            $deceUser = $currentUser; // Firma de Admin Zonal
            // Objeto institución simulado para lugar de firma
            $institution = ['nombre' => 'Coordinación Zonal ' . $zona];

            $reportTitle = "Reporte Zonal - Zona " . $zona;

            // Renderizar vista
            require 'views/report_group_print.php';
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al generar reporte: ' . $e->getMessage();
            header('Location: /test-vocacional/admin/zona');
            exit;
        }
    }

    /**
     * Exportar datos a Excel.
     */
    public function exportData()
    {
        if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'zonal') {
            $_SESSION['error'] = 'Acceso no autorizado';
            header('Location: /test-vocacional/login');
            exit;
        }

        try {
            $currentUser = $this->userModel->find($_SESSION['user_id']);
            $zonaId = $currentUser['zona_id'] ?? null;

            if (!$zonaId) {
                throw new Exception('Tu cuenta no está vinculada a una zona');
            }

            $zona = $zonaId;
            $institucionId = $_GET['institucion'] ?? null;
            $curso = $_GET['curso'] ?? null;
            $paralelo = $_GET['paralelo'] ?? null;

            // Obtener resultados de estudiantes
            $results = $this->testModel->getStudentResultsByZona($zona, $institucionId, $curso, $paralelo, $_GET['amie'] ?? null);

            // Generar Excel
            require_once 'utils/ExcelGenerator.php';
            $excelGenerator = new ExcelGenerator();
            $excelContent = $excelGenerator->generateZonaReport($results, [
                'zona' => $zona,
                'institucion' => $institucionId,
                'curso' => $curso,
                'paralelo' => $paralelo
            ]);

            $filename = 'datos_zona_' . $zona . '_' . date('Y-m-d') . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $excelContent;
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al exportar datos: ' . $e->getMessage();
            header('Location: /test-vocacional/admin/zona');
            exit;
        }
    }
}
?>
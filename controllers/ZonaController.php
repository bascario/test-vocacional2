<?php
require_once 'models/User.php';
require_once 'models/VocationalTest.php';
require_once 'models/Institucion.php';

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
     * Main Zona dashboard
     */
    public function index()
    {
        // Verify zonal role
        if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'zonal') {
            $_SESSION['error'] = 'Acceso no autorizado';
            header('Location: /test-vocacional/login');
            exit;
        }

        // Get current zonal user
        $currentUser = $this->userModel->find($_SESSION['user_id']);

        if (empty($currentUser['zona_id'])) {
            $_SESSION['error'] = 'Tu cuenta no está vinculada a una zona. Contacta al administrador.';
            header('Location: /test-vocacional/login');
            exit;
        }

        $zonaId = $currentUser['zona_id'];

        // Get zona name from first institution (assuming zona_id matches zona name)
        // In a real scenario, you might have a separate zonas table
        $institutions = $this->institucionModel->getByZona($zonaId);

        if (empty($institutions)) {
            $_SESSION['error'] = 'No hay instituciones asignadas a tu zona';
            header('Location: /test-vocacional/login');
            exit;
        }

        $zona = $zonaId; // Using zona_id as zona name

        // Get filter parameters
        $institucionId = $_GET['institucion'] ?? null;
        $curso = $_GET['curso'] ?? null;
        $paralelo = $_GET['paralelo'] ?? null;

        // Get statistics
        $stats = $this->testModel->getStatisticsByZona($zona, $institucionId, $curso, $paralelo);

        // Get performance by institution
        $performanceByInstitution = $this->testModel->getPerformanceByInstitution($zona);

        // Get available courses and paralelos (if institution selected)
        $courses = [];
        $paralelos = [];
        if ($institucionId) {
            $courses = $this->userModel->getCoursesByInstitution($institucionId);
            if ($curso) {
                $paralelos = $this->userModel->getParalelosByCourse($institucionId, $curso);
            }
        }

        // Get student results
        $studentResults = $this->testModel->getStudentResultsByZona($zona, $institucionId, $curso, $paralelo);

        // Count institutions and total students
        $totalInstitutions = count($institutions);
        $totalStudents = count($studentResults);

        require_once 'views/zona_dashboard.php';
    }

    /**
     * AJAX endpoint to get courses for selected institution
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
     * AJAX endpoint to get paralelos for selected institution and course
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
     * Generate zona report (HTML Print View)
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

            // Use unified method for results
            $filters = [
                'zona' => $zona,
                'institucion_id' => $institucionId,
                'curso' => $curso,
                'paralelo' => $paralelo
            ];
            $results = $this->testModel->getGroupResults($filters);

            if (empty($results)) {
                throw new Exception("No se encontraron resultados para los filtros seleccionados");
            }

            // Calculate Group Stats
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

            // Identify Top Area
            $topAreaName = null;
            $topAreaScore = -1;
            foreach ($groupAverages as $cat => $avg) {
                if ($avg > $topAreaScore) {
                    $topAreaScore = $avg;
                    $topAreaName = $cat;
                }
            }

            // Prepare View Data
            // If specific institution selected, get its name, otherwise Generic
            $instName = 'Todas las Instituciones de la Zona';
            if ($institucionId) {
                $inst = $this->institucionModel->find($institucionId);
                if ($inst)
                    $instName = $inst['nombre'];
            }

            $filterInfo = [
                'institution' => $instName,
                'zona' => $zona,
                'distrito' => 'Todos', // Could detect if filtering by institution
                'course' => ($curso ? $curso . ($paralelo ? ' - ' . $paralelo : '') : 'Todos los cursos')
            ];

            $deceUser = $currentUser; // Zonal Admin signature
            // Mock institution object for signature place
            $institution = ['nombre' => 'Coordinación Zonal ' . $zona];

            $reportTitle = "Reporte Zonal - Zona " . $zona;

            // Render View
            require 'views/report_group_print.php';
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al generar reporte: ' . $e->getMessage();
            header('Location: /test-vocacional/admin/zona');
            exit;
        }
    }

    /**
     * Export data to Excel
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

            // Get student results
            $results = $this->testModel->getStudentResultsByZona($zona, $institucionId, $curso, $paralelo);

            // Generate Excel
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
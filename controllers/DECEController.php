<?php
require_once 'models/User.php';
require_once 'models/VocationalTest.php';
require_once 'models/Institucion.php';

class DECEController
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
     * Main DECE dashboard
     */
    public function index()
    {
        // Verify DECE role
        if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'dece') {
            $_SESSION['error'] = 'Acceso no autorizado';
            header('Location: /test-vocacional/login');
            exit;
        }

        // Get current DECE user
        $currentUser = $this->userModel->find($_SESSION['user_id']);

        if (empty($currentUser['institucion_id'])) {
            $_SESSION['error'] = 'Tu cuenta no está vinculada a una institución. Contacta al administrador.';
            header('Location: /test-vocacional/login');
            exit;
        }

        $institucionId = $currentUser['institucion_id'];

        // Get institution details
        $institucion = $this->institucionModel->find($institucionId);

        // Get filter parameters
        $curso = $_GET['curso'] ?? null;
        $paralelo = $_GET['paralelo'] ?? null;
        $amie = $_GET['amie'] ?? null;

        // Get statistics
        $stats = $this->testModel->getStatisticsByInstitution($institucionId, $curso, $paralelo);

        // Get available courses and paralelos
        $courses = $this->userModel->getCoursesByInstitution($institucionId);
        $paralelos = [];
        if ($curso) {
            $paralelos = $this->userModel->getParalelosByCourse($institucionId, $curso);
        }

        // Get performance by course
        $performanceByCourse = $this->testModel->getPerformanceByCourse($institucionId);

        // Get performance by paralelo if course is selected
        $performanceByParalelo = [];
        if ($curso) {
            $performanceByParalelo = $this->testModel->getPerformanceByParalelo($institucionId, $curso);
        }

        // Get student results
        $studentResults = $this->testModel->getStudentResultsByInstitution($institucionId, $curso, $paralelo);

        require_once 'views/dece_dashboard.php';
    }

    /**
     * AJAX endpoint to get paralelos for a course
     */
    public function getParalelos()
    {
        header('Content-Type: application/json');

        if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'dece') {
            echo json_encode(['error' => 'Acceso no autorizado']);
            exit;
        }

        $currentUser = $this->userModel->find($_SESSION['user_id']);
        $institucionId = $currentUser['institucion_id'] ?? null;
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
     * Generate institution report (HTML Print View)
     */
    public function generateInstitutionReport()
    {
        if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'dece') {
            $_SESSION['error'] = 'Acceso no autorizado';
            header('Location: /test-vocacional/login');
            exit;
        }

        try {
            $currentUser = $this->userModel->find($_SESSION['user_id']);
            $institucionId = $currentUser['institucion_id'] ?? null;

            if (!$institucionId) {
                throw new Exception('Tu cuenta no está vinculada a una institución');
            }

            $curso = $_GET['curso'] ?? null;
            $paralelo = $_GET['paralelo'] ?? null;

            // Get institution details
            $institucion = $this->institucionModel->find($institucionId);

            // Use unified method for results to be consistent with Admin
            $filters = [
                'institucion_id' => $institucionId,
                'curso' => $curso,
                'paralelo' => $paralelo,
                'amie' => $_GET['amie'] ?? null
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
            $filterInfo = [
                'institution' => $institucion['nombre'],
                'zona' => $institucion['zona'] ?? '',
                'distrito' => $institucion['distrito'] ?? '',
                'course' => ($curso ? $curso . ($paralelo ? ' - ' . $paralelo : '') : 'Todos los cursos')
            ];

            $deceUser = $currentUser; // For signature
            $reportTitle = "Reporte Institucional - " . $institucion['nombre'];

            // Render View
            require 'views/report_group_print.php';
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al generar reporte: ' . $e->getMessage();
            header('Location: /test-vocacional/admin/dece');
            exit;
        }
    }

    /**
     * Export data to Excel
     */
    public function exportData()
    {
        if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'dece') {
            $_SESSION['error'] = 'Acceso no autorizado';
            header('Location: /test-vocacional/login');
            exit;
        }

        try {
            $currentUser = $this->userModel->find($_SESSION['user_id']);
            $institucionId = $currentUser['institucion_id'] ?? null;

            if (!$institucionId) {
                throw new Exception('Tu cuenta no está vinculada a una institución');
            }

            $curso = $_GET['curso'] ?? null;
            $paralelo = $_GET['paralelo'] ?? null;

            // Get student results
            $results = $this->testModel->getStudentResultsByInstitution($institucionId, $curso, $paralelo);

            // Generate Excel
            require_once 'utils/ExcelGenerator.php';
            $excelGenerator = new ExcelGenerator();
            $excelContent = $excelGenerator->generateDECEReport($results, [
                'curso' => $curso,
                'paralelo' => $paralelo,
                'amie' => $_GET['amie'] ?? null
            ]);

            $filename = 'datos_dece_' . date('Y-m-d') . '.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $excelContent;
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al exportar datos: ' . $e->getMessage();
            header('Location: /test-vocacional/admin/dece');
            exit;
        }
    }
}
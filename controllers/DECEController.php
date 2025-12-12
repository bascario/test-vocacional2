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
     * Generate institution report PDF
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

            // Get statistics
            $stats = $this->testModel->getStatisticsByInstitution($institucionId, $curso, $paralelo);

            // Get performance data
            $performanceByCourse = $this->testModel->getPerformanceByCourse($institucionId);
            $performanceByParalelo = [];
            if ($curso) {
                $performanceByParalelo = $this->testModel->getPerformanceByParalelo($institucionId, $curso);
            }

            // Get student results
            $studentResults = $this->testModel->getStudentResultsByInstitution($institucionId, $curso, $paralelo);

            // Generate PDF
            require_once 'utils/PDFGenerator.php';
            $pdfGenerator = new PDFGenerator();
            $pdfContent = $pdfGenerator->generateDECEReport(
                $institucion,
                $stats,
                $performanceByCourse,
                $performanceByParalelo,
                $studentResults,
                $curso,
                $paralelo
            );

            // Output PDF
            $filename = 'reporte_dece_' . $institucion['codigo'] . '_' . date('Y-m-d') . '.pdf';
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $pdfContent;
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
                'paralelo' => $paralelo
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
?>
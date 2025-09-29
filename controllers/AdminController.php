<?php
class AdminController {
    private $userModel;
    private $testModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->testModel = new VocationalTest();
    }
    
    public function index() {
        // Get statistics
        $stats = $this->testModel->getStatistics();
        
        // Get recent tests
        $recentTests = $this->testModel->findAll([], 'fecha_test DESC LIMIT 10');
        
        // Get students by course
        $students = $this->userModel->getStudentsByCourse();
        
        require_once 'views/admin_dashboard.php';
    }
    
    public function generateIndividualReport() {
        $studentId = $_GET['student_id'] ?? null;
        
        if (!$studentId) {
            $_SESSION['error'] = "ID de estudiante no proporcionado";
            header('Location: /test-vocacional/admin');
            exit;
        }
        
        try {
            $results = $this->testModel->getResultsByUser($studentId);
            
            if (empty($results)) {
                throw new Exception("No se encontraron resultados para este estudiante");
            }
            
            $latestResult = $results[0];
            $scores = json_decode($latestResult['puntajes_json'], true);
            
            // Generate PDF
            require_once 'utils/PDFGenerator.php';
            $pdfGenerator = new PDFGenerator();
            $pdfContent = $pdfGenerator->generateIndividualReport($latestResult, $scores);
            
            // Output PDF
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="reporte_individual_' . $latestResult['username'] . '.pdf"');
            echo $pdfContent;
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error al generar reporte: " . $e->getMessage();
            header('Location: /test-vocacional/admin');
            exit;
        }
    }
    
    public function generateGroupReport() {
        $course = $_GET['course'] ?? null;
        $format = $_GET['format'] ?? 'pdf';
        
        try {
            $results = $this->testModel->getResultsByCourse($course);
            
            if (empty($results)) {
                throw new Exception("No se encontraron resultados");
            }
            
            if ($format === 'excel') {
                // Generate Excel
                require_once 'utils/ExcelGenerator.php';
                $excelGenerator = new ExcelGenerator();
                $excelContent = $excelGenerator->generateGroupReport($results);
                
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="reporte_grupal_' . date('Y-m-d') . '.xlsx"');
                echo $excelContent;
                exit;
            } else {
                // Generate PDF
                require_once 'utils/PDFGenerator.php';
                $pdfGenerator = new PDFGenerator();
                $pdfContent = $pdfGenerator->generateGroupReport($results, $course);
                
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="reporte_grupal_' . date('Y-m-d') . '.pdf"');
                echo $pdfContent;
                exit;
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Error al generar reporte grupal: " . $e->getMessage();
            header('Location: /test-vocacional/admin');
            exit;
        }
    }
}
?>
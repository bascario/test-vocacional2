<?php
require_once 'models/Institucion.php';

class AdminController {
    private $userModel;
    private $testModel;
    private $institucionModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->testModel = new VocationalTest();
        $this->institucionModel = new Institucion();
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
            // If current user is DECE, only allow generating report for students in the same institution
            if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'dece') {
                $current = $this->userModel->find($_SESSION['user_id']);
                if (empty($current) || empty($current['institucion_id'])) {
                    throw new Exception('Acceso denegado: tu cuenta no está vinculada a una institución');
                }

                // Check that the requested student belongs to the same institucion
                $student = $this->userModel->find($studentId);
                if (empty($student) || ($student['institucion_id'] ?? null) != $current['institucion_id']) {
                    throw new Exception('Acceso denegado: el estudiante no pertenece a su institución');
                }
            }

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
            
            // Output PDF (use student id in filename)
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="reporte_individual_' . $studentId . '.pdf"');
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
            // If current user is DECE, restrict results to their institution
            $institucionFilter = null;
            if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'dece') {
                $current = $this->userModel->find($_SESSION['user_id']);
                if (empty($current) || empty($current['institucion_id'])) {
                    throw new Exception('Acceso denegado: tu cuenta no está vinculada a una institución');
                }
                $institucionFilter = $current['institucion_id'];
            }

            $results = $this->testModel->getResultsByCourse($course, $institucionFilter);
            
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

    // Institutions management
    public function institutions() {
        // Only admins and dece
        if (empty($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['administrador', 'dece'])) {
            $_SESSION['error'] = 'Acceso no autorizado';
            header('Location: /test-vocacional/admin');
            exit;
        }

        // Handle create
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre = $_POST['nombre'] ?? '';
            $codigo = $_POST['codigo'] ?? '';
            $tipo = $_POST['tipo'] ?? '';

            try {
                $this->institucionModel->createInstitution([
                    'nombre' => $nombre,
                    'codigo' => $codigo,
                    'tipo' => $tipo
                ]);

                $_SESSION['success'] = 'Institución agregada exitosamente';
                header('Location: /test-vocacional/admin/institutions');
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error al agregar institución: ' . $e->getMessage();
            }
        }

        // If DECE, show only the institution linked to the user (but allow creation)
        if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'dece') {
            $current = $this->userModel->find($_SESSION['user_id']);
            $institutions = [];
            if (!empty($current['institucion_id'])) {
                $inst = $this->institucionModel->find($current['institucion_id']);
                if ($inst) $institutions[] = $inst;
            }
        } else {
            $institutions = $this->institucionModel->getAll();
        }
        require_once 'views/admin_institutions.php';
    }

    // Public search endpoint used by registration autocomplete
    public function searchInstitutions() {
        $q = $_GET['q'] ?? '';
        $results = [];

        try {
            if (trim($q) !== '') {
                $rows = $this->institucionModel->search($q, 50);
                foreach ($rows as $r) {
                    $results[] = [
                        'id' => $r['id'],
                        'text' => $r['nombre'] . ' (' . $r['codigo'] . ')'
                    ];
                }
            }
        } catch (Exception $e) {
            // ignore and return empty
        }

        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
        exit;
    }

    // Users management: list users and allow admin to change roles
    public function users() {
        // Only administrador
        if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrador') {
            $_SESSION['error'] = 'Acceso no autorizado';
            header('Location: /test-vocacional/admin');
            exit;
        }

        // Handle role change
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_POST['user_id'] ?? null;
            $role = $_POST['role'] ?? null;

            if (empty($userId) || empty($role)) {
                $_SESSION['error'] = 'Faltan datos para actualizar el rol';
                header('Location: /test-vocacional/admin/users');
                exit;
            }

            try {
                $this->userModel->updateRole((int)$userId, $role);
                $_SESSION['success'] = 'Rol actualizado correctamente';
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error al actualizar rol: ' . $e->getMessage();
            }

            header('Location: /test-vocacional/admin/users');
            exit;
        }

        $users = $this->userModel->findAll([], 'apellido, nombre');
        require_once 'views/admin_users.php';
    }
}
?>
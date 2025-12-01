<?php
class TestController {
    private $questionModel;
    private $testModel;
    private $userModel;
    
    public function __construct() {
        $this->questionModel = new Question();
        $this->testModel = new VocationalTest();
        $this->userModel = new User();
    }
    
    public function index() {
        // Get all questions grouped by category and type and flatten into a single list
        $grouped = $this->questionModel->getAllGrouped();
        $questions = [];
        foreach ($grouped as $category => $types) {
            foreach ($types as $type => $qs) {
                foreach ($qs as $q) {
                    $questions[] = $q; // flattened list
                }
            }
        }
        
        // Check if user has already taken the test
        $existingResults = $this->testModel->getResultsByUser($_SESSION['user_id']);
        
        require_once 'views/test_form.php';
    }
    
    public function submit() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /test-vocacional/test');
            exit;
        }
        
        $respuestas = $_POST['respuestas'] ?? [];
        
        if (empty($respuestas)) {
            $_SESSION['error'] = "Por favor responde todas las preguntas";
            header('Location: /test-vocacional/test');
            exit;
        }
        
        // Ensure user is logged in and valid
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = "Debes iniciar sesión para completar el test.";
            header('Location: /test-vocacional/login');
            exit;
        }

        $user = $this->userModel->find($_SESSION['user_id']);
        if (!$user) {
            // Clear potentially invalid session and ask to login again
            session_unset();
            session_destroy();
            session_start();
            $_SESSION['error'] = "Sesión inválida. Por favor inicia sesión de nuevo.";
            header('Location: /test-vocacional/login');
            exit;
        }

        try {
            // Create test result
            $testId = $this->testModel->createTest($_SESSION['user_id'], $respuestas);

            $_SESSION['success'] = "Test completado exitosamente";
            header('Location: /test-vocacional/results');
            exit;
        } catch (Exception $e) {
            // Log exception and request data for debugging
            try {
                $dbg = "=== Exception at " . date('c') . " ===\n";
                $dbg .= "Message: " . $e->getMessage() . "\n";
                $dbg .= "Code: " . $e->getCode() . "\n";
                $dbg .= "User ID: " . ($_SESSION['user_id'] ?? 'none') . "\n";
                $dbg .= "POST respuestas count: " . count($respuestas) . "\n";
                $dbg .= "POST respuestas content:\n" . var_export($respuestas, true) . "\n";
                $dbg .= "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
                $dbgFile = __DIR__ . '/../storage/test_submission_error.log';
                @file_put_contents($dbgFile, $dbg, FILE_APPEND);
            } catch (Exception $ex) {
                // ignore logging errors
            }

            // Show detailed error (for debugging - can remove later)
            // $_SESSION['error'] = "Debug: " . $e->getMessage();

            // Friendly messages for FK or other DB errors
            if (strpos($e->getMessage(), '1452') !== false || strpos($e->getMessage(), 'foreign key') !== false) {
                $_SESSION['error'] = "No se pudo guardar el test: usuario no válido. Por favor inicia sesión de nuevo.";
            } else if (strpos($e->getMessage(), 'Check constraint') !== false || strpos($e->getMessage(), '3819') !== false) {
                $_SESSION['error'] = "Error al guardar el test: se recibió una respuesta inválida. Por favor revisa las respuestas e inténtalo de nuevo.";
            } else if (strpos($e->getMessage(), 'Respuesta inválida') !== false) {
                $_SESSION['error'] = "Error: " . $e->getMessage();
            } else {
                $_SESSION['error'] = "Error al guardar el test: " . $e->getMessage();
            }
            header('Location: /test-vocacional/test');
            exit;
        }
    }
    
    public function results() {
        $results = $this->testModel->getResultsByUser($_SESSION['user_id']);
        
        if (empty($results)) {
            $_SESSION['error'] = "No has completado ningún test aún";
            header('Location: /test-vocacional/test');
            exit;
        }
        
        // Get the latest result
        $latestResult = $results[0];
        $scores = json_decode($latestResult['puntajes_json'], true);
        
        // Load recommendation helper so view can call getRecommendationText()
        require_once 'utils/Recommendations.php';
        require_once 'views/test_results.php';
    }
}
?>
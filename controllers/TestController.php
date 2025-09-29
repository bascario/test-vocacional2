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
        // Get all questions grouped by category and type
        $questions = $this->questionModel->getAllGrouped();
        
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
        
        try {
            // Create test result
            $testId = $this->testModel->createTest($_SESSION['user_id'], $respuestas);
            
            $_SESSION['success'] = "Test completado exitosamente";
            header('Location: /test-vocacional/results');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = "Error al guardar el test: " . $e->getMessage();
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
        
        require_once 'views/test_results.php';
    }
}
?>
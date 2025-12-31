<?php
class TestController
{
    private $questionModel;
    private $testModel;
    private $userModel;

    public function __construct()
    {
        $this->questionModel = new Question();
        $this->testModel = new VocationalTest();
        $this->userModel = new User();
    }

    // Mostrar el flujo principal del test: encuesta -> cuestionario o resultados
    public function index()
    {
        // Verificar si el usuario ya realizó el test (salvo que solicite uno nuevo con ?new=1)
        $forceNew = false;
        if (isset($_GET['new']) && ($_GET['new'] === '1' || $_GET['new'] === 'true')) {
            $forceNew = true;
        }

        $existingResults = $this->testModel->getResultsByUser($_SESSION['user_id']);
        if (!empty($existingResults) && !$forceNew) {
            // Si el estudiante ya tiene resultados y NO pidió iniciar un nuevo test, mostrar resultados
            $this->results();
            return;
        }

        // Nota: no eliminar `pre_test_completed` aquí — si venimos de la encuesta
        // esta variable debe permanecer para que el flujo muestre el cuestionario.

        // Check for survey completion in session
        if (!isset($_SESSION['pre_test_completed']) || $_SESSION['pre_test_completed'] !== true) {
            require_once 'views/pre_test_survey.php';
            return;
        }

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

        require_once 'views/test_form.php';
    }

    // Procesar envío de la encuesta previa al test
    public function submitSurvey()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /test-vocacional/test');
            exit;
        }

        // Validate required fields (basic check)
        $required = ['preferencia_mayor', 'preferencia_menor', 'madre_estudios', 'padre_estudios', 'tiempo_libre', 'exito_profesional', 'importancia_exito'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $_SESSION['error'] = "Por favor completa todos los campos de la encuesta.";
                header('Location: /test-vocacional/test'); // Redirects back to index which shows survey
                exit;
            }
        }

        // Store survey data in session
        $_SESSION['encuesta_data'] = [
            'preferencia_mayor' => $_POST['preferencia_mayor'],
            'preferencia_menor' => $_POST['preferencia_menor'],
            'madre' => [
                'estudios' => $_POST['madre_estudios'],
                'profesion' => $_POST['madre_profesion'] ?? ''
            ],
            'padre' => [
                'estudios' => $_POST['padre_estudios'],
                'profesion' => $_POST['padre_profesion'] ?? ''
            ],
            'tiempo_libre' => $_POST['tiempo_libre'],
            'exito_profesional' => $_POST['exito_profesional'],
            'importancia_exito' => $_POST['importancia_exito']
        ];

        // Mark as completed
        $_SESSION['pre_test_completed'] = true;

        // Redirect to main test forcing a new test (so users who already have results can start a fresh one)
        header('Location: /test-vocacional/test?new=1');
        exit;
    }

    // Procesar envío del cuestionario y crear resultado
    public function submit()
    {
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
            // Retrieve survey data from session
            $encuestaData = $_SESSION['encuesta_data'] ?? null;

            // Create test result using updated model method
            $testId = $this->testModel->createTest($_SESSION['user_id'], $respuestas, $encuestaData);

            // Cleanup session
            unset($_SESSION['pre_test_completed']);
            unset($_SESSION['encuesta_data']);

            $_SESSION['success'] = "Test completado exitosamente";
            header('Location: /test-vocacional/results');
            exit;
        } catch (Exception $e) {
            // Log exception (simplified for brevity)
            // ... (keep existing logging if preferred or simplify)
            $dbgFile = __DIR__ . '/../storage/test_submission_error.log';
            $dbg = $e->getMessage();
            @file_put_contents($dbgFile, $dbg, FILE_APPEND);

            $_SESSION['error'] = "Error al guardar el test: " . $e->getMessage();
            header('Location: /test-vocacional/test');
            exit;
        }
    }

    // Mostrar resultados del usuario (último test)
    public function results()
    {
        $results = $this->testModel->getResultsByUser($_SESSION['user_id']);

        if (empty($results)) {
            $_SESSION['error'] = "No has completado ningún test aún";
            header('Location: /test-vocacional/test');
            exit;
        }

        // Get the latest result
        $latestResult = $results[0];
        $scores = json_decode($latestResult['puntajes_json'], true);

        // Load current user data so the view can show/edit profile
        $user = $this->userModel->find($_SESSION['user_id']);

        // Load recommendation helper so view can call getRecommendationText()
        require_once 'utils/Recommendations.php';
        require_once 'views/test_results.php';
    }
}
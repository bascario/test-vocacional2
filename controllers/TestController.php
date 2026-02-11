<?php
/**
 * Controlador para la gestión de tests vocacionales.
 * Maneja la lógica de presentación del test, encuesta previa y resultados.
 */
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

    /**
     * Mostrar el flujo principal del test: encuesta -> cuestionario o resultados.
     */
    public function index()
    {
        // Verificar si el usuario ya realizó el test (salvo que solicite uno nuevo con ?new=1)
        $forceNew = false;
        if (isset($_GET['new']) && ($_GET['new'] === '1' || $_GET['new'] === 'true')) {
            $forceNew = true;
        }

        $existingResults = $this->testModel->getResultsByUser($_SESSION['user_id']);

        // Si tiene resultados previos y NO pidió nuevo test, mostrar resultados
        if (!empty($existingResults) && !$forceNew) {
            $this->results();
            return;
        }

        // Si pidió nuevo test, verificar si puede retomarlo
        if (!empty($existingResults) && $forceNew) {
            if (!$this->testModel->canRetakeTest($_SESSION['user_id'])) {
                // No puede retomar aún, mostrar resultados con mensaje
                $daysUntil = $this->testModel->getDaysUntilRetake($_SESSION['user_id']);
                $nextDate = $this->testModel->getNextRetakeDate($_SESSION['user_id']);

                $_SESSION['warning'] =
                    "Debes esperar " . TEST_RETAKE_MONTHS . " meses desde tu último test antes de volver a realizarlo. " .
                    "Podrás retomar el test el " . date('d/m/Y', strtotime($nextDate)) . " (" . $daysUntil . " días restantes).";

                $this->results();
                return;
            }
        }

        // Nota: no eliminar `pre_test_completed` aquí — si venimos de la encuesta
        // esta variable debe permanecer para que el flujo muestre el cuestionario.

        // Verificar si la encuesta fue completada en la sesión
        if (!isset($_SESSION['pre_test_completed']) || $_SESSION['pre_test_completed'] !== true) {
            require_once 'views/pre_test_survey.php';
            return;
        }

        // Obtener todas las preguntas agrupadas por categoría y tipo y aplanar en una sola lista
        $grouped = $this->questionModel->getAllGrouped();
        $questions = [];
        foreach ($grouped as $category => $types) {
            foreach ($types as $type => $qs) {
                foreach ($qs as $q) {
                    $questions[] = $q; // lista aplanada
                }
            }
        }

        require_once 'views/test_form.php';
    }

    /**
     * Procesar envío de la encuesta previa al test.
     */
    public function submitSurvey()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /test-vocacional/test');
            exit;
        }

        // Validar campos requeridos (chequeo básico)
        $required = ['preferencia_mayor', 'preferencia_menor', 'madre_estudios', 'padre_estudios', 'tiempo_libre', 'exito_profesional', 'importancia_exito'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $_SESSION['error'] = "Por favor completa todos los campos de la encuesta.";
                header('Location: /test-vocacional/test'); // Redirige al index que muestra la encuesta
                exit;
            }
        }

        // Almacenar datos de encuesta en sesión
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

        // Marcar como completada
        $_SESSION['pre_test_completed'] = true;

        // Redirigir al test principal forzando nuevo test (para que usuarios con resultados previos puedan empezar uno nuevo)
        header('Location: /test-vocacional/test?new=1');
        exit;
    }

    /**
     * Procesar envío del cuestionario y crear resultado.
     */
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

        // Asegurar que el usuario esté logueado y sea válido
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['error'] = "Debes iniciar sesión para completar el test.";
            header('Location: /test-vocacional/login');
            exit;
        }

        $user = $this->userModel->find($_SESSION['user_id']);
        if (!$user) {
            // Limpiar sesión potencialmente inválida y pedir login de nuevo
            session_unset();
            session_destroy();
            session_start();
            $_SESSION['error'] = "Sesión inválida. Por favor inicia sesión de nuevo.";
            header('Location: /test-vocacional/login');
            exit;
        }

        try {
            // Recuperar datos de encuesta de la sesión
            $encuestaData = $_SESSION['encuesta_data'] ?? null;

            // Crear resultado del test usando el método actualizado del modelo
            $testId = $this->testModel->createTest($_SESSION['user_id'], $respuestas, $encuestaData);

            // Limpiar sesión
            unset($_SESSION['pre_test_completed']);
            unset($_SESSION['encuesta_data']);

            $_SESSION['success'] = "Test completado exitosamente";
            header('Location: /test-vocacional/results');
            exit;
        } catch (Exception $e) {
            // Log de excepción
            $dbgFile = __DIR__ . '/../storage/test_submission_error.log';
            $dbg = $e->getMessage();
            @file_put_contents($dbgFile, $dbg, FILE_APPEND);

            $_SESSION['error'] = "Error al guardar el test: " . $e->getMessage();
            header('Location: /test-vocacional/test');
            exit;
        }
    }

    /**
     * Mostrar resultados del usuario (último test).
     */
    public function results()
    {
        $results = $this->testModel->getResultsByUser($_SESSION['user_id']);

        if (empty($results)) {
            $_SESSION['error'] = "No has completado ningún test aún";
            header('Location: /test-vocacional/test');
            exit;
        }

        // Obtener el último resultado
        $latestResult = $results[0];
        $scores = json_decode($latestResult['puntajes_json'], true);

        // Cargar datos actuales del usuario para que la vista pueda mostrar/editar perfil
        $user = $this->userModel->find($_SESSION['user_id']);

        // Cargar helper de recomendaciones para que la vista pueda llamar a getRecommendationText()
        require_once 'utils/Recommendations.php';
        require_once 'views/test_results.php';
    }
}
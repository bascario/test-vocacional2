<?php
// Cargar autoload de Composer primero
require_once 'vendor/autoload.php';

// Luego cargar la configuración
require_once 'config/config.php';

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Router principal (resto del código del index.php)
$request = $_SERVER['REQUEST_URI'];
$request = str_replace('/test-vocacional', '', $request);
$request = strtok($request, '?');

switch ($request) {
    case '/':
    case '':
        require_once 'controllers/AuthController.php';
        $controller = new AuthController();
        $controller->index();
        break;
        
    case '/login':
        require_once 'controllers/AuthController.php';
        $controller = new AuthController();
        $controller->login();
        break;
        
    case '/register':
        require_once 'controllers/AuthController.php';
        $controller = new AuthController();
        $controller->register();
        break;
        
    case '/logout':
        require_once 'controllers/AuthController.php';
        $controller = new AuthController();
        $controller->logout();
        break;
        
    case '/test':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        require_once 'controllers/TestController.php';
        $controller = new TestController();
        $controller->index();
        break;
        
    case '/test/submit':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        require_once 'controllers/TestController.php';
        $controller = new TestController();
        $controller->submit();
        break;
        
    case '/results':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        require_once 'controllers/TestController.php';
        $controller = new TestController();
        $controller->results();
        break;
        
    case '/admin':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        AuthMiddleware::checkRole(['administrador', 'dece']);
        require_once 'controllers/AdminController.php';
        $controller = new AdminController();
        $controller->index();
        break;
        
    case '/admin/questions':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        AuthMiddleware::checkRole(['administrador', 'dece']);
        require_once 'controllers/QuestionController.php';
        $controller = new QuestionController();
        $controller->index();
        break;
        
    case '/admin/questions/import':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        AuthMiddleware::checkRole(['administrador']);
        require_once 'controllers/QuestionController.php';
        $controller = new QuestionController();
        $controller->import();
        break;
        
    case '/admin/questions/delete':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        AuthMiddleware::checkRole(['administrador']);
        require_once 'controllers/QuestionController.php';
        $controller = new QuestionController();
        $controller->delete();
        break;

    case '/admin/institutions':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        AuthMiddleware::checkRole(['administrador', 'dece']);
        require_once 'controllers/AdminController.php';
        $controller = new AdminController();
        $controller->institutions();
        break;

    case '/admin/users':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        AuthMiddleware::checkRole(['administrador']);
        require_once 'controllers/AdminController.php';
        $controller = new AdminController();
        $controller->users();
        break;

    case '/institutions/search':
        // Public endpoint for autocomplete/search of institutions
        require_once 'controllers/AdminController.php';
        $controller = new AdminController();
        $controller->searchInstitutions();
        break;
        
    case '/admin/reports/individual':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        AuthMiddleware::checkRole(['administrador', 'dece']);
        require_once 'controllers/AdminController.php';
        $controller = new AdminController();
        $controller->generateIndividualReport();
        break;
        
    case '/admin/reports/group':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        AuthMiddleware::checkRole(['administrador', 'dece']);
        require_once 'controllers/AdminController.php';
        $controller = new AdminController();
        $controller->generateGroupReport();
        break;
        
    default:
        http_response_code(404);
        echo "Página no encontrada";
        break;
}
?>
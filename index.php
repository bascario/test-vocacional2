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

    case '/auth/check-username':
        require_once 'controllers/AuthController.php';
        $controller = new AuthController();
        $controller->checkUsername();
        break;

    case '/logout':
        require_once 'controllers/AuthController.php';
        $controller = new AuthController();
        $controller->logout();
        break;

    case '/cuenta-suspendida':
        require_once 'controllers/AuthController.php';
        $controller = new AuthController();
        $controller->suspended();
        break;

    case '/auth/changePassword':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        require_once 'controllers/AuthController.php';
        $controller = new AuthController();
        $controller->changePassword();
        break;

    case '/recover-password':
        require_once 'controllers/AuthController.php';
        $controller = new AuthController();
        $controller->recoverPassword();
        break;

    case '/auth/recoverPassword':
        require_once 'controllers/AuthController.php';
        $controller = new AuthController();
        $controller->recoverPassword();
        break;

    case '/reset-password':
        require_once 'controllers/AuthController.php';
        $controller = new AuthController();
        $controller->resetPassword();
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

    case '/test/survey':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        require_once 'controllers/TestController.php';
        $controller = new TestController();
        $controller->submitSurvey();
        break;

    case '/results':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        require_once 'controllers/TestController.php';
        $controller = new TestController();
        $controller->results();
        break;

    case '/profile':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        require_once 'controllers/AuthController.php';
        $controller = new AuthController();
        $controller->profile();
        break;

    case '/profile/update':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        require_once 'controllers/AuthController.php';
        $controller = new AuthController();
        $controller->updateProfile();
        break;

    case '/politica-datos':
        require_once 'views/politica_datos.php';
        break;

    case '/admin':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();

        if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'dece') {
            header('Location: /test-vocacional/admin/dece');
            exit;
        }

        if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'zonal') {
            header('Location: /test-vocacional/admin/zona');
            exit;
        }

        AuthMiddleware::checkRole(['administrador']);

        require_once 'controllers/AdminController.php';
        $controller = new AdminController();
        $controller->index();
        break;

    case '/admin/zona':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        AuthMiddleware::checkRole(['zonal']);
        require_once 'controllers/ZonaController.php';
        $controller = new ZonaController();
        $controller->index();
        break;

    case '/admin/zona/courses':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        AuthMiddleware::checkRole(['zonal']);
        require_once 'controllers/ZonaController.php';
        $controller = new ZonaController();
        $controller->getCourses();
        break;

    case '/admin/zona/paralelos':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        AuthMiddleware::checkRole(['zonal']);
        require_once 'controllers/ZonaController.php';
        $controller = new ZonaController();
        $controller->getParalelos();
        break;

    case '/admin/zona/reports/zona':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        AuthMiddleware::checkRole(['zonal']);
        require_once 'controllers/ZonaController.php';
        $controller = new ZonaController();
        $controller->generateZonaReport();
        break;

    case '/admin/zona/export':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        AuthMiddleware::checkRole(['zonal']);
        require_once 'controllers/ZonaController.php';
        $controller = new ZonaController();
        $controller->exportData();
        break;

    case '/admin/dece':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        AuthMiddleware::checkRole(['dece']);
        require_once 'controllers/DECEController.php';
        $controller = new DECEController();
        $controller->index();
        break;

    case '/admin/dece/paralelos':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        AuthMiddleware::checkRole(['dece']);
        require_once 'controllers/DECEController.php';
        $controller = new DECEController();
        $controller->getParalelos();
        break;

    case '/admin/dece/reports/institution':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        AuthMiddleware::checkRole(['dece']);
        require_once 'controllers/DECEController.php';
        $controller = new DECEController();
        $controller->generateInstitutionReport();
        break;

    case '/admin/dece/export':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        AuthMiddleware::checkRole(['dece']);
        require_once 'controllers/DECEController.php';
        $controller = new DECEController();
        $controller->exportData();
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
        AuthMiddleware::checkRole(['administrador', 'dece']);
        require_once 'controllers/AdminController.php';
        $controller = new AdminController();
        $controller->users();
        break;

    case '/admin/users/change-password':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        AuthMiddleware::checkRole(['administrador', 'dece', 'zonal']);
        require_once 'controllers/AdminController.php';
        $controller = new AdminController();
        $controller->changeUserPassword();
        break;

    case '/admin/payment-status':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        AuthMiddleware::checkRole(['cuenta_oculta']);
        require_once 'controllers/AdminController.php';
        $controller = new AdminController();
        $controller->paymentStatus();
        break;

    case '/admin/payment-status/update':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
        AuthMiddleware::checkRole(['cuenta_oculta']);
        require_once 'controllers/AdminController.php';
        $controller = new AdminController();
        $controller->updatePaymentStatus();
        break;

    case '/institutions/search':
        require_once 'controllers/AdminController.php';
        $controller = new AdminController();
        $controller->searchInstitutions();
        break;

    case '/verify-report':
        require_once 'controllers/AdminController.php';
        if (file_exists('views/verify_report.php')) {
            require_once 'views/verify_report.php';
        } else {
            echo "Página de verificación en construcción";
        }
        break;

    case '/admin/reports/individual':
        require_once 'middleware/AuthMiddleware.php';
        AuthMiddleware::checkAuth();
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
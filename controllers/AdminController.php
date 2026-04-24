<?php
require_once 'models/Institucion.php';
require_once __DIR__ . '/../services/InstitutionService.php';
require_once __DIR__ . '/../services/UserService.php';
require_once __DIR__ . '/../services/ReportService.php';

/**
 * Controlador de Administración.
 * Maneja el panel principal, la gestión de usuarios e instituciones, y la generación de reportes.
 */
class AdminController
{
    private $testModel;
    private $institutionService;
    private $userService;
    private $reportService;

    public function __construct()
    {
        $this->testModel = new VocationalTest();
        $this->institutionService = new InstitutionService();
        $this->userService = new UserService();
        $this->reportService = new ReportService();
    }

    /**
     * Muestra el panel principal de administración con estadísticas y filtros.
     */
    public function index()
    {
        // Filtros
        $filters = [
            'zona' => $_GET['zona'] ?? null,
            'distrito' => $_GET['distrito'] ?? null,
            'institucion_id' => $_GET['institucion_id'] ?? null,
            'amie' => $_GET['amie'] ?? null
        ];

        // Obtener opciones de filtro
        $zonas = $this->institutionService->getZonas();
        $distritos = $filters['zona'] ? $this->institutionService->getDistritos($filters['zona']) : [];

        $instituciones = [];
        if ($filters['distrito']) {
            $instituciones = $this->institutionService->getByDistrito($filters['distrito']);
        } elseif ($filters['zona']) {
            $instituciones = $this->institutionService->getByZona($filters['zona']);
        }

        // Obtener estadísticas
        $stats = $this->testModel->getStatistics($filters);

        $recentTests = $this->testModel->getRecentTestsWithDetails(10, $filters);

        require_once 'views/admin_dashboard.php';
    }



    // gestior de instituciones 
    /**
     * Gestiona las instituciones educativas (listar, crear, editar, eliminar).
     * Requiere rol 'administrador' o 'dece'.
     */
    public function institutions()
    {
        // Solo administradores y dece
        if (empty($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['administrador', 'dece'])) {
            $_SESSION['error'] = 'Acceso no autorizado';
            header('Location: /test-vocacional/admin');
            exit;
        }

        // Manejar eliminación
        if (isset($_GET['delete'])) {
            try {
                // Solo admin puede eliminar
                if ($_SESSION['user_role'] !== 'administrador') {
                    throw new Exception('Sólo el administrador puede eliminar instituciones');
                }
                $this->institutionService->delete($_GET['delete']);
                $_SESSION['success'] = 'Institución eliminada exitosamente';
                header('Location: /test-vocacional/admin/institutions');
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error al eliminar: ' . $e->getMessage();
            }
        }

        // Manejar creación/actualización
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->institutionService->save($_POST);
                $_SESSION['success'] = isset($_POST['id']) && $_POST['id'] ? 'Institución actualizada exitosamente' : 'Institución agregada exitosamente';
                header('Location: /test-vocacional/admin/institutions');
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error: ' . $e->getMessage();
            }
        }

        // Configuración de paginación y filtros
        $perPage = 20;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1)
            $page = 1;
        $offset = ($page - 1) * $perPage;

        $filters = [
            'search' => $_GET['search'] ?? null,
            'provincia' => $_GET['provincia'] ?? null,
            'canton' => $_GET['canton'] ?? null,
            'zona' => $_GET['zona'] ?? null,
            'distrito' => $_GET['distrito'] ?? null,
            'tipo' => $_GET['tipo'] ?? null,
        ];

        // Si es DECE, mostrar solo la institución vinculada al usuario
        if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'dece') {
            $current = $this->userService->find($_SESSION['user_id']);
            $institutions = [];
            if (!empty($current['institucion_id'])) {
                $inst = $this->institutionService->find($current['institucion_id']);
                if ($inst)
                    $institutions[] = $inst;
            }
            $totalRecords = count($institutions);
            $totalPages = 1;
        } else {
            $totalRecords = $this->institutionService->countAll($filters);
            $totalPages = ceil($totalRecords / $perPage);
            $institutions = $this->institutionService->getAll($perPage, $offset, $filters);
        }

        $currentPage = $page;

        // Obtener valores únicos para filtros
        $provinciasList = $this->institutionService->getUniqueValues('provincia');
        $cantonesList = $this->institutionService->getUniqueValues('canton');
        $zonasInstList = $this->institutionService->getUniqueValues('zona');
        $distritosInstList = $this->institutionService->getUniqueValues('distrito');

        $tiposList = ['Fiscal', 'Particular', 'Fiscomisional', 'Municipal'];

        require_once 'views/admin_institutions.php';
    }

    /**
     * Endpoint público para búsqueda de instituciones (autocompletado en registro).
     * Devuelve JSON.
     */
    public function searchInstitutions()
    {
        $q = $_GET['q'] ?? '';
        $results = [];

        try {
            if (trim($q) !== '') {
                $rows = $this->institutionService->search($q, 50);
                foreach ($rows as $r) {
                    $results[] = [
                        'id' => $r['id'],
                        'text' => $r['nombre'] . ' (' . $r['codigo'] . ')'
                    ];
                }
            }
        } catch (Exception $e) {
            // ignorar y devolver vacío
        }

        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
        exit;
    }

    /**
     * Genera y muestra/descarga el reporte individual en HTML.
     */
    public function generateIndividualReport()
    {
        $studentId = $_GET['student_id'] ?? null;

        if (!$studentId) {
            $_SESSION['error'] = "ID de estudiante no proporcionado";
            header('Location: /test-vocacional/admin');
            exit;
        }

        try {
            $currentUser = [
                'id' => $_SESSION['user_id'] ?? null,
                'rol' => $_SESSION['user_role'] ?? null,
                'institucion_id' => null, // Se obtendrá si es necesario
                'zona_id' => null // Se obtendrá si es necesario
            ];

            // Validar Acceso
            $this->reportService->validateIndividualReportAccess($currentUser, $studentId);

            // Obtener Datos
            $data = $this->reportService->getIndividualReportData($studentId);

            // Desempaquetar datos para la vista
            $result = $data['result'];
            $student = $data['student'];
            $institution = $data['institution'];
            $deceUser = $data['deceUser'];
            $normalizedScores = $data['scores'];

            // Métricas
            $differentiation = $data['metrics']['differentiation'];
            $competence = $data['metrics']['competence'];
            $topAreas = $data['metrics']['topAreas'];

            $qrCodeBase64 = $data['qrCode'];

            // Renderizar la vista imprimible
            require 'views/report_individual_print.php';
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = "Error al generar reporte: " . $e->getMessage();
            header('Location: /test-vocacional/admin');
            exit;
        }
    }

    /**
     * Genera el reporte grupal en PDF (Vista) o Excel según el parámetro.
     */
    public function generateGroupReport()
    {
        $filters = [
            'zona' => $_GET['zona'] ?? null,
            'distrito' => $_GET['distrito'] ?? null,
            'institucion_id' => $_GET['institucion_id'] ?? null,
            'curso' => $_GET['course'] ?? null,
            'amie' => $_GET['amie'] ?? null
        ];
        $format = $_GET['format'] ?? 'pdf';

        try {
            $currentUser = [
                'id' => $_SESSION['user_id'] ?? null,
                'rol' => $_SESSION['user_role'] ?? null,
                'institucion_id' => null
            ];

            // Obtener datos (Validación interna)
            $data = $this->reportService->getGroupReportData($filters, $currentUser);

            $results = $data['results'];
            $filterInfo = $data['filterInfo'];

            if ($format === 'excel') {
                require_once 'utils/ExcelGenerator.php';
                $excelGenerator = new ExcelGenerator();
                $excelContent = $excelGenerator->generateGroupReport($results);

                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="reporte_grupal_' . date('Y-m-d') . '.xlsx"');
                echo $excelContent;
                exit;
            } else {
                // Variables de vista
                $groupAverages = $data['averages'];
                $topAreaName = $data['topArea']['name'];
                $topAreaScore = $data['topArea']['score'];
                $qrCodeBase64 = $data['qrCode'];
                $deceUser = $data['currentUser']; // Usuario firma
                $reportTitle = "Reporte Grupal - Administrador";

                require 'views/report_group_print.php';
                exit;
            }

        } catch (Exception $e) {
            $_SESSION['error'] = "Error al generar reporte grupal: " . $e->getMessage();
            header('Location: /test-vocacional/admin');
            exit;
        }
    }



    /**
     * Gestión de usuarios: listar usuarios y permitir al admin cambiar roles.
     */
    public function users()
    {
        // Solo administrador y dece
        if (empty($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['administrador', 'dece'])) {
            $_SESSION['error'] = 'Acceso no autorizado';
            header('Location: /test-vocacional/admin');
            exit;
        }

        // Manejar cambios de rol y asignación
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_POST['user_id'] ?? null;
            $role = $_POST['role'] ?? null;
            $zonaId = $_POST['zona_id'] ?? null;
            $institucionId = $_POST['institucion_id'] ?? null;

            if (empty($userId) || empty($role)) {
                $_SESSION['error'] = 'Faltan datos para actualizar el usuario';
                header('Location: /test-vocacional/admin/users');
                exit;
            }

            try {
                $currentUser = [
                    'id' => $_SESSION['user_id'] ?? null,
                    'rol' => $_SESSION['user_role'] ?? null,
                    'institucion_id' => null
                ];

                // Obtener usuario actual completo si es DECE para asegurar tener institucion_id
                if ($currentUser['rol'] === 'dece') {
                    $u = $this->userService->find($currentUser['id']);
                    $currentUser['institucion_id'] = $u['institucion_id'];
                }

                $contextData = [
                    'zona_id' => $zonaId,
                    'institucion_id' => $institucionId
                ];

                $this->userService->updateUserRole($currentUser, $userId, $role, $contextData);

                $_SESSION['success'] = 'Usuario actualizado correctamente';
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error al actualizar usuario: ' . $e->getMessage();
            }

            header('Location: /test-vocacional/admin/users');
            exit;
        }

        // Configuración de paginación
        $perPage = 20;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1)
            $page = 1;
        $offset = ($page - 1) * $perPage;

        // Filtros
        $filters = [
            'rol' => $_GET['rol'] ?? null,
            'institucion_id' => $_GET['institucion_id'] ?? null,
            'search' => $_GET['search'] ?? null,
            'curso' => $_GET['curso'] ?? null,
            'paralelo' => $_GET['paralelo'] ?? null,
            'zona' => $_GET['zona'] ?? null,
            'distrito' => $_GET['distrito'] ?? null,
        ];

        // Restringir DECE a su propia institución
        if ($_SESSION['user_role'] === 'dece') {
            $current = $this->userService->find($_SESSION['user_id']);
            $filters['institucion_id'] = $current['institucion_id'] ?? -1;
            $filters['zona'] = null;
            $filters['distrito'] = null;
        }

        // Obtener todos los usuarios con filtros e información de institución
        $totalRecords = $this->userService->countAll($filters);
        $totalPages = ceil($totalRecords / $perPage);
        $users = $this->userService->findAll($perPage, $offset, $filters);
        $currentPage = $page;

        // Obtener instituciones para filtros y asignación DECE
        // Corregido: pasar int 0 en lugar de null para evitar advertencia, aunque el limite 0 usualmente significa sin limite en muchos contextos,
        // aqui probaremos con un limite alto
        $institutions = $this->institutionService->getAll(10000, 0, []);

        // Obtener valores únicos para filtros
        $cursosList = $this->userService->getUniqueValues('curso');
        $paralelosList = $this->userService->getUniqueValues('paralelo');
        $zonasList = $this->institutionService->getUniqueValues('zona');
        $distritosList = $this->institutionService->getUniqueValues('distrito');

        require_once 'views/admin_users.php';
    }

    /**
     * Permite a los administradores cambiar la contraseña de cualquier usuario.
     * DECE puede cambiar contraseña de usuarios de su institución.
     * Zonal puede cambiar contraseña de usuarios de su zona.
     */
    public function updateUserPassword(int $actorId, string $actorRole, int $targetUserId, string $newPassword)
    {
        return $this->userService->updateUserPassword($actorId, $actorRole, $targetUserId, $newPassword);
    }

    /**
     * Procesa el formulario de cambio de contraseña.
     */
    public function changeUserPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Método inválido';
            header('Location: /test-vocacional/admin/users');
            exit;
        }

        $targetUserId = $_POST['target_user_id'] ?? null;
        $newPassword = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (empty($targetUserId) || empty($newPassword) || empty($confirm)) {
            $_SESSION['error'] = 'Faltan datos para cambiar la contraseña';
            header('Location: /test-vocacional/admin/users');
            exit;
        }

        if ($newPassword !== $confirm) {
            $_SESSION['error'] = 'Las contraseñas no coinciden';
            header('Location: /test-vocacional/admin/users');
            exit;
        }

        try {
            $currentUserId = $_SESSION['user_id'] ?? null;
            $currentRole = $_SESSION['user_role'] ?? null;

            $this->userService->updateUserPassword((int) $currentUserId, $currentRole, (int) $targetUserId, $newPassword);

            $_SESSION['success'] = 'Contraseña actualizada correctamente';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }

        header('Location: /test-vocacional/admin/users');
        exit;
    }

    /**
     * Muestra la página de administración de estado de pago.
     */
    public function paymentStatus()
    {
        if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'cuenta_oculta') {
            $_SESSION['error'] = 'Acceso no autorizado';
            header('Location: /test-vocacional/login');
            exit;
        }

        $perPage = 20;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1) {
            $page = 1;
        }
        $offset = ($page - 1) * $perPage;

        $filters = [
            'rol' => $_GET['rol'] ?? null,
            'institucion_id' => $_GET['institucion_id'] ?? null,
            'search' => $_GET['search'] ?? null,
        ];

        $totalRecords = $this->userService->countAll($filters);
        $totalPages = ceil($totalRecords / $perPage);
        $users = $this->userService->findAll($perPage, $offset, $filters);
        $currentPage = $page;

        $institutions = $this->institutionService->getAll(10000, 0, []);
        require_once 'views/admin_payment_status.php';
    }

    /**
     * Actualiza el estado de pago de un usuario.
     */
    public function updatePaymentStatus()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'Método inválido';
            header('Location: /test-vocacional/admin/payment-status');
            exit;
        }

        if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'cuenta_oculta') {
            $_SESSION['error'] = 'Acceso no autorizado';
            header('Location: /test-vocacional/login');
            exit;
        }

        $targetUserId = $_POST['user_id'] ?? null;
        $paymentStatus = $_POST['payment_status'] ?? null;

        if (empty($targetUserId) || empty($paymentStatus)) {
            $_SESSION['error'] = 'Faltan datos para actualizar el estado de pago';
            header('Location: /test-vocacional/admin/payment-status');
            exit;
        }

        try {
            $currentUser = [
                'id' => $_SESSION['user_id'] ?? null,
                'rol' => $_SESSION['user_role'] ?? null,
            ];

            $this->userService->updatePaymentStatus($currentUser, (int)$targetUserId, $paymentStatus);
            $_SESSION['success'] = 'Estado de pago actualizado correctamente';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error al actualizar estado de pago: ' . $e->getMessage();
        }

        header('Location: /test-vocacional/admin/payment-status');
        exit;
    }
}

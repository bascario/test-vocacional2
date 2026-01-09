<?php
require_once 'models/Institucion.php';

class AdminController
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
        $zonas = $this->institucionModel->getZonaList();
        $distritos = $filters['zona'] ? $this->institucionModel->getDistritoList($filters['zona']) : []; // Cargar distritos dependientes si se seleccionó zona.

        $instituciones = [];
        if ($filters['distrito']) {
            $instituciones = $this->institucionModel->getByDistrito($filters['distrito']);
        } elseif ($filters['zona']) {
            $instituciones = $this->institucionModel->getByZona($filters['zona']);
        }

        // Obtener estadísticas
        $stats = $this->testModel->getStatistics($filters);

        // Obtener últimos tests (pueden estar filtrados según necesidad)
        // Actualmente obtenemos los tests recientes con detalles mediante el modelo.
        // I don't see a filterable findAll in my previous reads.

        $recentTests = $this->testModel->getRecentTestsWithDetails(10, $filters);

        require_once 'views/admin_dashboard.php';
    }

    // Generar y mostrar/descargar reporte individual en HTML/PDF
    public function generateIndividualReport()
    {
        $studentId = $_GET['student_id'] ?? null;

        if (!$studentId) {
            $_SESSION['error'] = "ID de estudiante no proporcionado";
            header('Location: /test-vocacional/admin');
            exit;
        }

        try {
            $currentUserId = $_SESSION['user_id'] ?? null;
            $currentUserRole = $_SESSION['user_role'] ?? null;

            // Permitir si: administrador, dece de la misma institución, zonal de su zona, o el estudiante
            $isOwn = ($currentUserId == $studentId);
            $isAdmin = ($currentUserRole === 'administrador');
            $isDece = ($currentUserRole === 'dece');
            $isZonal = ($currentUserRole === 'zonal');

            if (!$isOwn && !$isAdmin && !$isDece && !$isZonal) {
                throw new Exception('Acceso denegado: no tienes permiso para descargar este reporte');
            }

            // Si el usuario actual es DECE, solo permitir reportes de estudiantes de la misma institución
            if ($isDece && !$isOwn) {
                $current = $this->userModel->find($currentUserId);
                if (empty($current) || empty($current['institucion_id'])) {
                    throw new Exception('Acceso denegado: tu cuenta no está vinculada a una institución');
                }

                // Check that the requested student belongs to the same institucion
                $student = $this->userModel->find($studentId);
                if (empty($student) || ($student['institucion_id'] ?? null) != $current['institucion_id']) {
                    throw new Exception('Acceso denegado: el estudiante no pertenece a su institución');
                }
            }

            // Si el usuario actual es Zonal, permitir si el estudiante es de su zona
            if ($isZonal && !$isOwn) {
                $current = $this->userModel->find($currentUserId);
                if (empty($current) || empty($current['zona_id'])) {
                    throw new Exception('Acceso denegado: tu cuenta no está vinculada a una zona');
                }

                $student = $this->userModel->find($studentId);
                if (empty($student) || empty($student['institucion_id'])) {
                    throw new Exception('Acceso denegado: el estudiante no está vinculado a una institución');
                }

                $studentInst = $this->institucionModel->find($student['institucion_id']);
                if (empty($studentInst) || ($studentInst['zona'] ?? null) != $current['zona_id']) {
                    throw new Exception('Acceso denegado: el estudiante no pertenece a su zona');
                }
            }

            $results = $this->testModel->getResultsByUser($studentId);

            if (empty($results)) {
                throw new Exception("No se encontraron resultados para este estudiante");
            }

            $latestResult = $results[0];
            $result = $latestResult;
            $scores = json_decode($latestResult['puntajes_json'], true);

            // Obtener profesional DECE y datos de la institución
            $studentInfo = $this->userModel->find($studentId);
            $deceUser = null;
            $institution = null;
            if (!empty($studentInfo['institucion_id'])) {
                $deceUser = $this->userModel->getDeceByInstitution($studentInfo['institucion_id']);
                $institution = $this->institucionModel->find($studentInfo['institucion_id']);
            }

            // Calcular datos adicionales para el informe detallado
            require_once 'utils/ReportHelper.php';

            // Normalizar puntajes a etiquetas esperadas (RIASEC)
            $normalizedScores = ReportHelper::normalizeScores($scores);

            // Calcular métricas derivadas
            $differentiation = ReportHelper::calculateDifferentiation($normalizedScores);
            $competence = ReportHelper::calculateCompetence($normalizedScores);
            $topAreas = ReportHelper::getTopAreas($normalizedScores, 3);

            // Generar validación QR
            $validationUrl = ReportHelper::getValidationUrl($latestResult['id']);
            $qrCodeBase64 = ReportHelper::generateQRCodeBase64($validationUrl);

            // Renderizar la vista imprimible
            // No se envían cabeceras PDF aquí, solo HTML
            require 'views/report_individual_print.php';
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = "Error al generar reporte: " . $e->getMessage();
            header('Location: /test-vocacional/admin');
            exit;
        }
    }

    // Generar reporte grupal en PDF o Excel según el parámetro
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
            // Verificación de permisos para DECE
            if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'dece') {
                $current = $this->userModel->find($_SESSION['user_id']);
                if (empty($current) || empty($current['institucion_id'])) {
                    throw new Exception('Acceso denegado: tu cuenta no está vinculada a una institución');
                }
                // Force filter to own institution
                $filters['institucion_id'] = $current['institucion_id'];
            }

            // Obtener resultados según filtros
            $results = $this->testModel->getGroupResults($filters);

            if (empty($results)) {
                // Si no hay datos con los filtros seleccionados, lanzar excepción
                throw new Exception("No se encontraron resultados con los filtros seleccionados");
            }

            // Prepare Filter Info for View
            $filterInfo = [
                'zona' => $filters['zona'],
                'distrito' => $filters['distrito'],
                'institution' => $filters['institucion_id'] ? ($this->institucionModel->find($filters['institucion_id'])['nombre'] ?? 'Desconocida') : 'Todas las Instituciones',
                'course' => $filters['curso'],
                'amie' => $filters['amie']
            ];

            if ($format === 'excel') {
                // Generate Excel (Legacy/Existing) - Assuming ExcelGenerator method signature matches or needs update.
                // Previous call: $excelGenerator->generateGroupReport($results); 
                // We need to check if $results format matches what ExcelGenerator expects.
                // Assuming result array is similar (list of rows) it should be fine.
                // Actually, the previous getResultsByCourse might have returned different columns.
                // My new getGroupResults returns rt.*, u.*, ie.nombre.
                // Let's assume compatibility or fix ExcelGenerator if needed. for now we leave it.

                require_once 'utils/ExcelGenerator.php';
                $excelGenerator = new ExcelGenerator();
                $excelContent = $excelGenerator->generateGroupReport($results);

                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                header('Content-Disposition: attachment; filename="reporte_grupal_' . date('Y-m-d') . '.xlsx"');
                echo $excelContent;
                exit;
            } else {
                // Generar vista PDF/HTML
                // Calcular estadísticas grupales para el gráfico radar
                $totals = ['Realista' => 0, 'Investigador' => 0, 'Artístico' => 0, 'Social' => 0, 'Emprendedor' => 0, 'Convencional' => 0];
                $counts = ['Realista' => 0, 'Investigador' => 0, 'Artístico' => 0, 'Social' => 0, 'Emprendedor' => 0, 'Convencional' => 0]; // actually count tests

                // To match individual logic:
                // We sum the percentages for each category across all students, then divide by N
                foreach ($results as $row) {
                    $scores = json_decode($row['puntajes_json'], true);
                    if (is_array($scores)) {
                        foreach ($totals as $cat => $val) {
                            // Extract percentage. Keys in JSON are e.g. "Realista".
                            // Handle if key exists
                            $pct = 0;
                            if (isset($scores[$cat])) {
                                if (is_array($scores[$cat]))
                                    $pct = $scores[$cat]['porcentaje'] ?? 0;
                                else
                                    $pct = $scores[$cat];
                            }
                            $totals[$cat] += $pct;
                            $counts[$cat]++;
                        }
                    }
                }

                $groupAverages = [];
                $numStudents = count($results);
                if ($numStudents > 0) {
                    foreach ($totals as $cat => $sum) {
                        $groupAverages[$cat] = round($sum / $numStudents, 2);
                    }
                }

                // Identify Top Area
                $topAreaName = null;
                $topAreaScore = -1;
                foreach ($groupAverages as $cat => $avg) {
                    if ($avg > $topAreaScore) {
                        $topAreaScore = $avg;
                        $topAreaName = $cat;
                    }
                }

                // Prepare View Data for Footer
                // Ensure we have current user data for signature
                if (!isset($current)) {
                    $current = $this->userModel->find($_SESSION['user_id']);
                }
                $deceUser = $current;
                $reportTitle = "Reporte Grupal - Administrador";

                // Generar validación QR (para el grupo/institución si aplica, o test individual de referencia)
                // Usamos el ID de la institución o un identificador único si subiera a BD, pero por ahora
                // como es dinámico, podemos usar un hash de los filtros o simplemente omitir si no es individual.
                // Sin embargo, el usuario pidió para TODOS. Usaremos una URL general de la institución si hay institucion_id.
                $validationData = $filters['institucion_id'] ?? 'group_report';
                $validationUrl = ReportHelper::getValidationUrl($validationData);
                $qrCodeBase64 = ReportHelper::generateQRCodeBase64($validationUrl);

                // Render Update View
                // No headers needed as we output HTML to browser directly
                require 'views/report_group_print.php';
                exit;
            }

        } catch (Exception $e) {
            $_SESSION['error'] = "Error al generar reporte grupal: " . $e->getMessage();
            header('Location: /test-vocacional/admin');
            exit;
        }
    }

    // Institutions management
    public function institutions()
    {
        // Only admins and dece
        if (empty($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['administrador', 'dece'])) {
            $_SESSION['error'] = 'Acceso no autorizado';
            header('Location: /test-vocacional/admin');
            exit;
        }

        // Handle delete
        if (isset($_GET['delete'])) {
            try {
                // Only admin can delete
                if ($_SESSION['user_role'] !== 'administrador') {
                    throw new Exception('Sólo el administrador puede eliminar instituciones');
                }
                $this->institucionModel->delete($_GET['delete']);
                $_SESSION['success'] = 'Institución eliminada exitosamente';
                header('Location: /test-vocacional/admin/institutions');
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error al eliminar: ' . $e->getMessage();
            }
        }

        // Handle create/update
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $nombre = $_POST['nombre'] ?? '';
            $codigo = $_POST['codigo'] ?? '';
            $tipo = $_POST['tipo'] ?? '';
            $provincia = $_POST['provincia'] ?? '';
            $canton = $_POST['canton'] ?? '';
            $zona = $_POST['zona'] ?? '';
            $distrito = $_POST['distrito'] ?? '';

            try {
                $data = [
                    'nombre' => $nombre,
                    'codigo' => $codigo,
                    'tipo' => $tipo,
                    'provincia' => $provincia,
                    'canton' => $canton,
                    'zona' => $zona,
                    'distrito' => $distrito
                ];

                if ($id) {
                    $this->institucionModel->updateInstitution($id, $data);
                    $_SESSION['success'] = 'Institución actualizada exitosamente';
                } else {
                    $this->institucionModel->createInstitution($data);
                    $_SESSION['success'] = 'Institución agregada exitosamente';
                }

                header('Location: /test-vocacional/admin/institutions');
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error: ' . $e->getMessage();
            }
        }

        // Pagination settings
        $perPage = 20;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1)
            $page = 1;
        $offset = ($page - 1) * $perPage;

        // If DECE, show only the institution linked to the user (but allow creation)
        if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'dece') {
            $current = $this->userModel->find($_SESSION['user_id']);
            $institutions = [];
            if (!empty($current['institucion_id'])) {
                $inst = $this->institucionModel->find($current['institucion_id']);
                if ($inst)
                    $institutions[] = $inst;
            }
            $totalRecords = count($institutions);
            $totalPages = 1;
        } else {
            $totalRecords = $this->institucionModel->countAll();
            $totalPages = ceil($totalRecords / $perPage);
            $institutions = $this->institucionModel->getAll($perPage, $offset);
        }

        $currentPage = $page;

        require_once 'views/admin_institutions.php';
    }

    // Public search endpoint used by registration autocomplete
    public function searchInstitutions()
    {
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
    public function users()
    {
        // Only administrador
        if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrador') {
            $_SESSION['error'] = 'Acceso no autorizado';
            header('Location: /test-vocacional/admin');
            exit;
        }

        // Handle role and assignment changes
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
                // Update role
                $this->userModel->updateRole((int) $userId, $role);

                // Update zona_id if provided (for zonal role)
                if ($role === 'zonal' && !empty($zonaId)) {
                    $this->userModel->updateZona((int) $userId, $zonaId);
                } elseif ($role !== 'zonal') {
                    // Clear zona_id if not zonal role
                    $this->userModel->updateZona((int) $userId, null);
                }

                // Update institucion_id if provided (for dece role)
                if ($role === 'dece' && !empty($institucionId)) {
                    // First, unassign this institution from ANY OTHER user who has the 'dece' role
                    // This ensures the institution is "moved" to the new user
                    $this->userModel->unassignInstitutionFromDece($institucionId);
                    $this->userModel->updateInstitucion((int) $userId, $institucionId);
                } elseif ($role !== 'dece') {
                    // Clear institucion_id if not dece role
                    $this->userModel->updateInstitucion((int) $userId, null);
                }

                $_SESSION['success'] = 'Usuario actualizado correctamente';
            } catch (Exception $e) {
                $_SESSION['error'] = 'Error al actualizar usuario: ' . $e->getMessage();
            }

            header('Location: /test-vocacional/admin/users');
            exit;
        }

        // Pagination settings
        $perPage = 20;
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        if ($page < 1)
            $page = 1;
        $offset = ($page - 1) * $perPage;

        // Filtros
        $filters = [
            'rol' => $_GET['rol'] ?? null,
            'institucion_id' => $_GET['institucion_id'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        // Get all users with filters and institution info
        $totalRecords = $this->userModel->countAllWithFilters($filters);
        $totalPages = ceil($totalRecords / $perPage);
        $users = $this->userModel->findAllWithDetails($filters, $perPage, $offset);
        $currentPage = $page;

        // Get institutions for filters and DECE assignment
        require_once 'models/Institucion.php';
        $institucionModel = new Institucion();
        $institutions = $institucionModel->getAll();

        // Get zonas for zonal assignment
        $zonas = $institucionModel->getZonaList();

        // Render view
        require_once 'views/admin_users.php';
    }

    /**
     * Allow administrators to change any user's password, DECE can change passwords
     * for users from the same institution, ZONAL can change passwords for users from their zone.
     */
    /**
     * Core logic to update a user's password, callable from tests.
     * Throws Exception on permission errors or invalid input.
     */
    public function updateUserPassword(int $actorId, string $actorRole, int $targetUserId, string $newPassword)
    {
        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            throw new Exception('La nueva contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH . ' caracteres');
        }

        // Permission checks
        if ($actorRole === 'administrador') {
            // ok
        } elseif ($actorRole === 'dece') {
            // only allow if target user belongs to same institution
            $current = $this->userModel->find($actorId);
            $target = $this->userModel->find($targetUserId);
            if (empty($current['institucion_id']) || empty($target['institucion_id']) || $current['institucion_id'] != $target['institucion_id']) {
                throw new Exception('Acceso denegado: sólo puedes cambiar contraseñas de estudiantes de tu institución');
            }
        } elseif ($actorRole === 'zonal') {
            // only allow if target user belongs to same zona
            $current = $this->userModel->find($actorId);
            $target = $this->userModel->find($targetUserId);
            if (empty($current['zona_id']) || empty($target['zona_id']) || $current['zona_id'] != $target['zona_id']) {
                throw new Exception('Acceso denegado: sólo puedes cambiar contraseñas de usuarios de tu zona');
            }
        } else {
            throw new Exception('Acceso denegado: no tienes permiso para cambiar contraseñas de otros usuarios');
        }

        return $this->userModel->updatePassword($targetUserId, $newPassword);
    }

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

            $this->updateUserPassword((int) $currentUserId, $currentRole, (int) $targetUserId, $newPassword);

            $_SESSION['success'] = 'Contraseña actualizada correctamente';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }

        header('Location: /test-vocacional/admin/users');
        exit;
    }
}
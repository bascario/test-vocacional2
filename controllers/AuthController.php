<?php
/**
 * Controlador de Autenticación.
 * Maneja inicio de sesión, registro, recuperación de contraseña y perfil de usuario.
 */
class AuthController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Mostrar página de perfil del usuario.
     */
    public function profile()
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /test-vocacional/login');
            exit;
        }

        $user = $this->userModel->find($_SESSION['user_id']);
        require_once 'views/profile.php';
    }

    /**
     * Manejar actualizaciones de perfil desde la página de resultados o perfil.
     */
    public function updateProfile()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /test-vocacional/test');
            exit;
        }

        if (empty($_SESSION['user_id'])) {
            $_SESSION['error'] = 'Debes iniciar sesión para actualizar tu perfil.';
            header('Location: /test-vocacional/login');
            exit;
        }

        $allowed = ['nombre', 'apellido', 'email', 'curso', 'paralelo', 'telefono', 'institucion_id'];
        // permitir actualización de fecha_nacimiento desde perfil
        $allowed[] = 'fecha_nacimiento';
        $data = [];
        foreach ($allowed as $field) {
            if (isset($_POST[$field])) {
                $data[$field] = trim($_POST[$field]);
            }
        }

        // Validación básica: formato de email si se proporciona
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'El email no tiene un formato válido.';
            header('Location: /test-vocacional/results');
            exit;
        }

        // Validar fecha_nacimiento si se proporciona
        if (!empty($data['fecha_nacimiento'])) {
            $d = DateTime::createFromFormat('Y-m-d', $data['fecha_nacimiento']);
            if (!$d || $d->format('Y-m-d') !== $data['fecha_nacimiento']) {
                $_SESSION['error'] = 'La fecha de nacimiento no tiene un formato válido (AAAA-MM-DD).';
                header('Location: /test-vocacional/results');
                exit;
            }
        }

        try {
            $this->userModel->update($_SESSION['user_id'], $data);
            // Actualizar nombre en sesión si nombre/apellido cambiaron
            $user = $this->userModel->find($_SESSION['user_id']);
            if ($user['rol'] === 'estudiante') {
                $_SESSION['user_name'] = $user['nombre'];
            } else {
                $_SESSION['user_name'] = trim(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? ''));
            }
            $_SESSION['success'] = 'Perfil actualizado correctamente.';
            header('Location: /test-vocacional/results');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error actualizando perfil: ' . $e->getMessage();
            header('Location: /test-vocacional/results');
            exit;
        }
    }

    /**
     * Página principal del controlador de autenticación.
     * Redirige al dashboard correspondiente según el rol, o al login.
     */
    public function index()
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirectByRole($_SESSION['user_role']);
        } else {
            header('Location: /test-vocacional/login');
            exit;
        }
    }

    /**
     * Manejar inicio de sesión.
     * GET: Muestra formulario.
     * POST: Procesa credenciales.
     */
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            try {
                $user = $this->userModel->authenticate($username, $password);

                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_username'] = $user['username'];
                    $_SESSION['user_role'] = $user['rol'];

                    // Solo el nombre del estudiante para el header
                    if ($user['rol'] === 'estudiante') {
                        $_SESSION['user_name'] = $user['nombre'];
                    } else {
                        $_SESSION['user_name'] = $user['nombre'] . ' ' . $user['apellido'];
                    }

                    // Notificar si la contraseña fue actualizada automáticamente de MD5 legacy a bcrypt
                    if ($this->userModel->isPasswordRehashedFor($user['id'])) {
                        $_SESSION['info'] = 'Por seguridad tu contraseña fue actualizada automáticamente. Se recomienda cambiarla desde tu perfil.';
                    }

                    $this->redirectByRole($user['rol']);
                } else {
                    $_SESSION['error'] = "Usuario o contraseña incorrectos";
                    header('Location: /test-vocacional/login');
                    exit;
                }
            } catch (Exception $e) {
                $_SESSION['error'] = "Error al iniciar sesión: " . $e->getMessage();
                header('Location: /test-vocacional/login');
                exit;
            }
        } else {
            require_once 'views/login.php';
        }
    }

    /**
     * Manejar registro de usuarios.
     * GET: Muestra formulario.
     * POST: Procesa datos de registro.
     */
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $student_nombres = trim($_POST['student_nombres'] ?? '');
            $student_apellidos = trim($_POST['student_apellidos'] ?? '');
            $representative_nombres = trim($_POST['representative_nombres'] ?? '');
            $representative_apellidos = trim($_POST['representative_apellidos'] ?? '');

            $data = [
                'username' => $_POST['username'] ?? '',
                'password' => $_POST['password'] ?? '',
                'email' => $_POST['email'] ?? '',
                'nombre' => trim($student_nombres . ' ' . $student_apellidos),
                'apellido' => trim($representative_nombres . ' ' . $representative_apellidos),
                'institucion_id' => !empty($_POST['institucion_id']) ? (int) $_POST['institucion_id'] : null,
                'fecha_nacimiento' => !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null,
                'curso' => $_POST['curso'] ?? '',
                'paralelo' => $_POST['paralelo'] ?? '',
                'telefono' => $_POST['telefono'] ?? '',
                'rol' => 'estudiante',
                // Mantener campos separados original por si se necesitan para validación
                'student_nombres' => $student_nombres,
                'student_apellidos' => $student_apellidos,
                'representative_nombres' => $representative_nombres,
                'representative_apellidos' => $representative_apellidos
            ];

            try {
                $this->validateRegistrationData($data);
                $userId = $this->userModel->createUser($data);

                $_SESSION['success'] = "Registro exitoso. Por favor inicia sesión.";
                header('Location: /test-vocacional/login');
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                require_once 'views/register.php';
            }
        } else {
            // Cargar instituciones para selección
            require_once 'models/Institucion.php';
            $institucionModel = new Institucion();
            $institutions = $institucionModel->getAll();
            require_once 'views/register.php';
        }
    }

    /**
     * Cerrar sesión del usuario.
     */
    public function logout()
    {
        session_destroy();
        header('Location: /test-vocacional/login');
        exit;
    }

    /**
     * Mostrar formulario de cambio de contraseña.
     */
    public function changePassword()
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /test-vocacional/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            try {
                // Validar contraseña actual
                if (!$this->userModel->verifyPassword($_SESSION['user_id'], $currentPassword)) {
                    throw new Exception('La contraseña actual es incorrecta');
                }

                // Validar que la nueva contraseña cumpla requisitos
                if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                    throw new Exception('La nueva contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH . ' caracteres');
                }

                // Validar que las contraseñas coincidan
                if ($newPassword !== $confirmPassword) {
                    throw new Exception('Las contraseñas no coinciden');
                }

                // Validar que no sea igual a la anterior
                if ($currentPassword === $newPassword) {
                    throw new Exception('La nueva contraseña debe ser diferente a la anterior');
                }

                // Actualizar contraseña
                $this->userModel->updatePassword($_SESSION['user_id'], $newPassword);
                $_SESSION['success'] = 'Contraseña cambiada exitosamente';
                header('Location: /test-vocacional/test');
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                require_once 'views/change_password.php';
            }
        } else {
            require_once 'views/change_password.php';
        }
    }

    /**
     * Mostrar formulario de recuperación de contraseña.
     */
    public function recoverPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');

            try {
                // Validar email
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Por favor ingresa un email válido');
                }

                // Buscar usuario por email
                $user = $this->userModel->findByEmail($email);

                // Por seguridad, siempre mostrar mensaje genérico
                if ($user) {
                    // Generar token
                    $token = $this->userModel->createPasswordResetToken($user['id'], 1);

                    if ($token) {
                        // Enviar email
                        require_once 'utils/EmailSender.php';
                        $emailSender = new EmailSender();

                        $resetUrl = APP_URL . '/reset-password?token=' . urlencode($token);
                        $success = $emailSender->sendPasswordResetEmail(
                            $user['email'],
                            $user['nombre'] . ' ' . $user['apellido'],
                            $resetUrl
                        );

                        if ($success) {
                            $_SESSION['success'] = 'Se envió un enlace de recuperación a tu email. Revisa tu bandeja de entrada.';
                        } else {
                            // Email no se envió pero mostrar mensaje genérico de seguridad
                            error_log("Error enviando email a " . $user['email']);
                            $_SESSION['info'] = 'Si el email está registrado, recibirás instrucciones de recuperación.';
                        }
                    } else {
                        error_log("Error creando token para usuario " . $user['id']);
                        $_SESSION['info'] = 'Si el email está registrado, recibirás instrucciones de recuperación.';
                    }
                } else {
                    // Usuario no existe, pero no lo revelamos
                    $_SESSION['info'] = 'Si el email está registrado, recibirás instrucciones de recuperación.';
                }

                header('Location: /test-vocacional/recover-password');
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                require_once 'views/recover_password.php';
            }
        } else {
            require_once 'views/recover_password.php';
        }
    }

    /**
     * Mostrar formulario de restablecimiento de contraseña (con token).
     */
    public function resetPassword()
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            $_SESSION['error'] = 'Token de recuperación no válido o expirado.';
            header('Location: /test-vocacional/recover-password');
            exit;
        }

        // Validar token
        $tokenData = $this->userModel->validatePasswordResetToken($token);

        if (!$tokenData) {
            $_SESSION['error'] = 'El enlace de recuperación ha expirado. Solicita uno nuevo.';
            header('Location: /test-vocacional/recover-password');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newPassword = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            try {
                // Validaciones
                if (empty($newPassword)) {
                    throw new Exception('La contraseña no puede estar vacía');
                }

                if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                    throw new Exception('La contraseña debe tener al menos ' . PASSWORD_MIN_LENGTH . ' caracteres');
                }

                if ($newPassword !== $confirmPassword) {
                    throw new Exception('Las contraseñas no coinciden');
                }

                // Actualizar contraseña
                $this->userModel->updatePassword($tokenData['user_id'], $newPassword);

                // Marcar token como usado
                $this->userModel->usePasswordResetToken(hash('sha256', $token));

                $_SESSION['success'] = 'Contraseña actualizada exitosamente. Por favor inicia sesión.';
                header('Location: /test-vocacional/login');
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                // Re-renderizar con token
                require_once 'views/reset_password.php';
            }
        } else {
            // Mostrar formulario
            require_once 'views/reset_password.php';
        }
    }

    /**
     * Redirigir al usuario según su rol.
     *
     * @param string $role Rol del usuario.
     */
    private function redirectByRole($role)
    {
        switch ($role) {
            case 'administrador':
                header('Location: /test-vocacional/admin');
                break;
            case 'zonal':
                header('Location: /test-vocacional/admin/zona');
                break;
            case 'dece':
                header('Location: /test-vocacional/admin/dece');
                break;
            case 'estudiante':
                header('Location: /test-vocacional/test');
                break;
            default:
                header('Location: /test-vocacional/login');
        }
        exit;
    }

    /**
     * Validar datos de registro del usuario.
     *
     * @param array $data Datos del formulario de registro.
     * @throws Exception Si algún dato es inválido o falta.
     */
    private function validateRegistrationData($data)
    {
        if (empty($data['username']) || empty($data['password']) || empty($data['email'])) {
            throw new Exception("Todos los campos básicos son obligatorios");
        }

        if (empty($data['student_nombres']) || empty($data['student_apellidos'])) {
            throw new Exception("Los nombres y apellidos del estudiante son obligatorios");
        }

        if (empty($data['representative_nombres']) || empty($data['representative_apellidos'])) {
            throw new Exception("Los nombres y apellidos del representante son obligatorios");
        }

        if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
            throw new Exception("La contraseña debe tener al menos " . PASSWORD_MIN_LENGTH . " caracteres");
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El email no es válido");
        }

        // Validar fecha_nacimiento si se proporciona (espera AAAA-MM-DD)
        if (!empty($data['fecha_nacimiento'])) {
            $d = DateTime::createFromFormat('Y-m-d', $data['fecha_nacimiento']);
            if (!$d || $d->format('Y-m-d') !== $data['fecha_nacimiento']) {
                throw new Exception('La fecha de nacimiento no tiene un formato válido (AAAA-MM-DD).');
            }
        }
    }

    /**
     * Endpoint público para verificar disponibilidad de un nombre de usuario y sugerir alternativas.
     * Lógica extraída para generar sugerencias que pueden ser usadas por el endpoint y tests.
     *
     * @param string $username Nombre de usuario deseado.
     * @param string $nombre Nombres para sugerencias.
     * @param string $apellido Apellidos para sugerencias.
     * @return array Disponibilidad y sugerencias.
     */
    public function generateUsernameSuggestions(string $username, string $nombre = '', string $apellido = ''): array
    {
        $username = trim($username);
        $nombre = trim($nombre);
        $apellido = trim($apellido);

        if (empty($username)) {
            return ['available' => false, 'suggestions' => [], 'error' => 'Nombre de usuario vacío'];
        }

        // Normalizar: quitar espacios en los extremos y caracteres no alfanuméricos permitiendo _ y .
        $normalize = function ($s) {
            $s = trim($s);
            $s = preg_replace('/[^\p{L}0-9_.-]/u', '', $s);
            $s = preg_replace('/\s+/', '', $s);
            return mb_strtolower($s);
        };

        $usernameClean = $normalize($username);
        $nombreClean = $normalize($nombre);
        $apellidoClean = $normalize($apellido);

        // Si el username está libre, devolvemos disponible
        $existing = $this->userModel->findByUsername($usernameClean);
        if (!$existing) {
            return ['available' => true, 'suggestions' => []];
        }

        // Generar sugerencias significativas basadas en nombre y apellido
        $candidates = [];

        if ($nombreClean && $apellidoClean) {
            $first = preg_replace('/[^a-z0-9]/', '', $nombreClean);
            $last = preg_replace('/[^a-z0-9]/', '', $apellidoClean);

            $candidates[] = $first . '.' . $last;      // john.doe
            $candidates[] = $first . $last;           // johndoe
            $candidates[] = substr($first, 0, 1) . $last; // jdoe
            $candidates[] = $first . '.' . substr($last, 0, 1); // john.d
            $candidates[] = $last . $first;           // doejohn
        }

        // Agregar variaciones basadas en el username original
        $candidates[] = $usernameClean . mt_rand(10, 99);
        $candidates[] = $usernameClean . '_' . mt_rand(10, 99);
        $candidates[] = $usernameClean . '.' . mt_rand(10, 99);

        // Asegurar unicidad y disponibilidad
        $suggestions = [];
        foreach ($candidates as $cand) {
            if (count($suggestions) >= 5)
                break;
            $cand = mb_strtolower($cand);
            $cand = preg_replace('/[^a-z0-9_.-]/', '', $cand);
            $cand = substr($cand, 0, 30);
            if (empty($cand))
                continue;

            if ($cand === $usernameClean || in_array($cand, $suggestions, true))
                continue;

            if (!$this->userModel->findByUsername($cand)) {
                $suggestions[] = $cand;
            }
        }

        // Si no se encuentran sugerencias disponibles, usar numéricas aleatorias
        $tries = 0;
        while (count($suggestions) < 3 && $tries < 30) {
            $tries++;
            $cand = $usernameClean . mt_rand(100, 999);
            $cand = substr(preg_replace('/[^a-z0-9_.-]/', '', $cand), 0, 30);
            if (!$this->userModel->findByUsername($cand) && !in_array($cand, $suggestions, true)) {
                $suggestions[] = $cand;
            }
        }

        return ['available' => false, 'suggestions' => array_values($suggestions)];
    }

    /**
     * Endpoint API para verificar disponibilidad de usuario.
     */
    public function checkUsername()
    {
        header('Content-Type: application/json');

        $username = trim($_GET['username'] ?? $_POST['username'] ?? '');

        // Intentar obtener nombre de los campos separados si viene del registro
        $nombre = trim($_GET['student_nombres'] ?? $_POST['student_nombres'] ?? $_GET['nombre'] ?? $_POST['nombre'] ?? '');
        $apellido = trim($_GET['student_apellidos'] ?? $_POST['student_apellidos'] ?? $_GET['apellido'] ?? $_POST['apellido'] ?? '');

        $res = $this->generateUsernameSuggestions($username, $nombre, $apellido);
        echo json_encode($res);
        exit;
    }
}
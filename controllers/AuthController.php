<?php
class AuthController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    // Mostrar página de perfil (opcional)
    public function profile()
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /test-vocacional/login');
            exit;
        }

        $user = $this->userModel->find($_SESSION['user_id']);
        require_once 'views/profile.php';
    }

    // Manejar actualizaciones de perfil desde la página de resultados o perfil
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

        $allowed = ['nombre', 'apellido', 'email', 'curso', 'paralelo', 'telefono'];
        // allow updating fecha_nacimiento from profile
        $allowed[] = 'fecha_nacimiento';
        $data = [];
        foreach ($allowed as $field) {
            if (isset($_POST[$field])) {
                $data[$field] = trim($_POST[$field]);
            }
        }

        // Basic validation: email format if provided
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'El email no tiene un formato válido.';
            header('Location: /test-vocacional/results');
            exit;
        }

        // Validate fecha_nacimiento if provided
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
            // Refresh session display name if nombre/apellido changed
            $user = $this->userModel->find($_SESSION['user_id']);
            $_SESSION['user_name'] = trim(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? ''));
            $_SESSION['success'] = 'Perfil actualizado correctamente.';
            header('Location: /test-vocacional/results');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error actualizando perfil: ' . $e->getMessage();
            header('Location: /test-vocacional/results');
            exit;
        }
    }

    // Página de inicio del controlador de autenticación
    public function index()
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirectByRole($_SESSION['user_role']);
        } else {
            header('Location: /test-vocacional/login');
            exit;
        }
    }

    // Manejar inicio de sesión (GET muestra formulario, POST procesa)
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
                    $_SESSION['user_name'] = $user['nombre'] . ' ' . $user['apellido'];

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

    // Manejar registro de usuarios (GET muestra formulario, POST procesa)
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'username' => $_POST['username'] ?? '',
                'password' => $_POST['password'] ?? '',
                'email' => $_POST['email'] ?? '',
                'nombre' => $_POST['nombre'] ?? '',
                'apellido' => $_POST['apellido'] ?? '',
                'institucion_id' => !empty($_POST['institucion_id']) ? (int) $_POST['institucion_id'] : null,
                'fecha_nacimiento' => !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null,
                'curso' => $_POST['curso'] ?? '',
                'paralelo' => $_POST['paralelo'] ?? '',
                'bachillerato' => $_POST['bachillerato'] ?? '',
                'telefono' => $_POST['telefono'] ?? '',
                'rol' => 'estudiante'
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
            // Load institutions for selection
            require_once 'models/Institucion.php';
            $institucionModel = new Institucion();
            $institutions = $institucionModel->getAll();
            require_once 'views/register.php';
        }
    }

    public function logout()
    {
        session_destroy();
        header('Location: /test-vocacional/login');
        exit;
    }

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

    private function validateRegistrationData($data)
    {
        if (empty($data['username']) || empty($data['password']) || empty($data['email'])) {
            throw new Exception("Todos los campos son obligatorios");
        }

        if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
            throw new Exception("La contraseña debe tener al menos " . PASSWORD_MIN_LENGTH . " caracteres");
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("El email no es válido");
        }

        // Validate fecha_nacimiento if provided (expect YYYY-MM-DD)
        if (!empty($data['fecha_nacimiento'])) {
            $d = DateTime::createFromFormat('Y-m-d', $data['fecha_nacimiento']);
            if (!$d || $d->format('Y-m-d') !== $data['fecha_nacimiento']) {
                throw new Exception('La fecha de nacimiento no tiene un formato válido (AAAA-MM-DD).');
            }
        }
    }
}
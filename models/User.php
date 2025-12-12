<?php
class User extends BaseModel
{
    protected $table = 'usuarios';

    public function findByUsername($username)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    public function findByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function createUser($data)
    {
        // Hash de contraseña
        $data['password'] = md5($data['password']);

        // Validación básica
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            throw new Exception("Todos los campos obligatorios deben ser completados");
        }

        // Verificar duplicados
        if ($this->findByUsername($data['username'])) {
            throw new Exception("El nombre de usuario ya está registrado");
        }

        if ($this->findByEmail($data['email'])) {
            throw new Exception("El email ya está registrado");
        }

        // If role 'dece' is requested, only allow if current session user is administrador
        if (!empty($data['rol']) && $data['rol'] === 'dece') {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'administrador') {
                throw new Exception('Solo el administrador puede asignar el rol DECE');
            }
        }

        // Insertar solo los campos que existen en tu BD
        $insertData = [
            'username' => $data['username'],
            'password' => $data['password'],
            'email' => $data['email'],
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'],
            'rol' => $data['rol'] ?? 'estudiante'
        ];

        // Optional institution association
        if (!empty($data['institucion_id'])) {
            $insertData['institucion_id'] = $data['institucion_id'];
        }

        // Optional bachillerato type
        if (!empty($data['bachillerato'])) {
            $insertData['bachillerato'] = $data['bachillerato'];
        }

        // Optional phone number
        if (!empty($data['telefono'])) {
            $insertData['telefono'] = $data['telefono'];
        }

        return $this->create($insertData);
    }

    public function authenticate($username, $password)
    {
        $user = $this->findByUsername($username);

        if (!$user) {
            return false;
        }

        if ($user['password'] !== md5($password)) {
            return false;
        }

        return $user;
    }

    public function getStudentsByCourse($course = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE rol = 'estudiante'";
        $params = [];

        if ($course) {
            $sql .= " AND curso = ?";
            $params[] = $course;
        }

        $sql .= " ORDER BY apellido, nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getStudentsByEspecialidad($especialidad = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE rol = 'estudiante'";
        $params = [];

        if ($especialidad) {
            $sql .= " AND bachillerato = ?";
            $params[] = $especialidad;
        }

        $sql .= " ORDER BY apellido, nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function updateRole($userId, $role)
    {
        $allowed = ['administrador', 'dece', 'estudiante'];
        if (!in_array($role, $allowed, true)) {
            throw new Exception('Rol inválido');
        }

        $stmt = $this->db->prepare("UPDATE {$this->table} SET rol = ? WHERE id = ?");
        return $stmt->execute([$role, $userId]);
    }
}
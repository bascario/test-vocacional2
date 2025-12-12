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

        // Optional curso
        if (!empty($data['curso'])) {
            $insertData['curso'] = $data['curso'];
        }

        // Optional paralelo
        if (!empty($data['paralelo'])) {
            $insertData['paralelo'] = $data['paralelo'];
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
        $allowed = ['administrador', 'zonal', 'dece', 'estudiante'];
        if (!in_array($role, $allowed, true)) {
            throw new Exception('Rol inválido');
        }

        $stmt = $this->db->prepare("UPDATE {$this->table} SET rol = ? WHERE id = ?");
        return $stmt->execute([$role, $userId]);
    }

    /**
     * Get all students from a specific institution
     */
    public function getStudentsByInstitution($institucionId, $curso = null, $paralelo = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE rol = 'estudiante' AND institucion_id = ?";
        $params = [$institucionId];

        if ($curso) {
            $sql .= " AND curso = ?";
            $params[] = $curso;
        }

        if ($paralelo) {
            $sql .= " AND paralelo = ?";
            $params[] = $paralelo;
        }

        $sql .= " ORDER BY curso, paralelo, apellido, nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get unique courses in an institution
     */
    public function getCoursesByInstitution($institucionId)
    {
        $sql = "SELECT DISTINCT curso FROM {$this->table} 
                WHERE institucion_id = ? AND curso IS NOT NULL AND curso != ''
                ORDER BY curso";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$institucionId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Get paralelos for a specific course in an institution
     */
    public function getParalelosByCourse($institucionId, $curso)
    {
        $sql = "SELECT DISTINCT paralelo FROM {$this->table} 
                WHERE institucion_id = ? AND curso = ? AND paralelo IS NOT NULL AND paralelo != ''
                ORDER BY paralelo";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$institucionId, $curso]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    /**
     * Update zona assignment for a user
     */
    public function updateZona($userId, $zonaId)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET zona_id = ? WHERE id = ?");
        return $stmt->execute([$zonaId, $userId]);
    }

    /**
     * Update institution assignment for a user
     */
    public function updateInstitucion($userId, $institucionId)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET institucion_id = ? WHERE id = ?");
        return $stmt->execute([$institucionId, $userId]);
    }
}
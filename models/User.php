<?php
class User extends BaseModel
{
    protected $table = 'usuarios';

    // ID del último usuario cuya contraseña fue rehasheada de MD5 a bcrypt
    private $lastRehashedUserId = null;

    public function findByUsername($username)
    {
        // Búsqueda case-insensitive para evitar problemas con mayúsculas/minúsculas
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE LOWER(username) = LOWER(?)");
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
        // Trim y normalizar username (solo quitar espacios en los extremos)
        $data['username'] = isset($data['username']) ? trim($data['username']) : '';

        // Hash de contraseña usando bcrypt (password_hash es más seguro que MD5)
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

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

        // Optional fecha de nacimiento
        if (!empty($data['fecha_nacimiento'])) {
            // Expecting YYYY-MM-DD, but allow whatever and let DB validate if needed
            $insertData['fecha_nacimiento'] = $data['fecha_nacimiento'];
        }

        // Optional zona_id for zonal users
        if (!empty($data['zona_id'])) {
            $insertData['zona_id'] = $data['zona_id'];
        }

        return $this->create($insertData);
    }

    public function authenticate($username, $password)
    {
        $user = $this->findByUsername($username);

        if (!$user) {
            return false;
        }

        // Si la contraseña en BD parece ser bcrypt, usar password_verify
        if ((strpos($user['password'], '$2y$') === 0) || (strpos($user['password'], '$2a$') === 0) || strlen($user['password']) >= 60) {
            if (password_verify($password, $user['password'])) {
                return $user;
            }

            return false;
        }

        // Fallback para contraseñas legacy en MD5: si coincide, rehasheamos a bcrypt y actualizamos
        if (strlen($user['password']) === 32 && md5($password) === $user['password']) {
            // Re-hash y actualizar la contraseña para futuras autenticaciones
            $this->updatePassword($user['id'], $password);

            // Registrar que se re-hashéo para que el controlador pueda notificar al usuario
            $this->lastRehashedUserId = $user['id'];

            // Volver a recuperar el usuario (opcional) para que tenga el hash actualizado
            return $this->find($user['id']);
        }

        return false;
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
        try {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET zona_id = ? WHERE id = ?");
            return $stmt->execute([$zonaId, $userId]);
        } catch (PDOException $e) {
            // If the error is "Column not found" and we are trying to set NULL (clear the field), 
            // we can safely ignore it because the field essentially doesn't exist, so it's "cleared" by definition.
            if (strpos($e->getMessage(), 'Column not found') !== false && $zonaId === null) {
                return true;
            }
            // Otherwise, rethrow the exception
            throw $e;
        }
    }

    /**
     * Update institution assignment for a user
     */
    public function updateInstitucion($userId, $institucionId)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET institucion_id = ? WHERE id = ?");
        return $stmt->execute([$institucionId, $userId]);
    }

    /**
     * Find the DECE professional for a specific institution
     */
    public function getDeceByInstitution($institucionId)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE rol = 'dece' AND institucion_id = ? LIMIT 1");
        $stmt->execute([$institucionId]);
        return $stmt->fetch();
    }

    /**
     * Unassign an institution from any user with the DECE role
     */
    public function unassignInstitutionFromDece($institucionId)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET institucion_id = NULL WHERE rol = 'dece' AND institucion_id = ?");
        return $stmt->execute([$institucionId]);
    }

    /**
     * Update password for a user
     */
    public function updatePassword($userId, $newPassword)
    {
        // Hash usando bcrypt con cost 12
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->db->prepare("UPDATE {$this->table} SET password = ? WHERE id = ?");
        return $stmt->execute([$hashedPassword, $userId]);
    }

    /**
     * Verify current password for a user
     */
    public function verifyPassword($userId, $currentPassword)
    {
        $user = $this->find($userId);
        if (!$user) {
            return false;
        }
        // Usar password_verify para comparar bcrypt
        return password_verify($currentPassword, $user['password']);
    }

    /**
     * Indica si durante la autenticación reciente la contraseña de este usuario fue rehashada
     */
    public function isPasswordRehashedFor($userId)
    {
        return $this->lastRehashedUserId !== null && $this->lastRehashedUserId == $userId;
    }

    /**
     * Create a password reset token
     */
    public function createPasswordResetToken($userId, $expiryHours = 1)
    {
        try {
            $user = $this->find($userId);
            if (!$user) {
                throw new Exception('Usuario no encontrado');
            }

            // Generar token seguro
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);

            // Calcular expiración
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryHours} hours"));
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

            // Insertar token en BD
            $stmt = $this->db->prepare("
                INSERT INTO password_reset_tokens 
                (user_id, token, token_hash, email, expires_at, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $success = $stmt->execute([
                $userId,
                $token,
                $tokenHash,
                $user['email'],
                $expiresAt,
                $ipAddress
            ]);

            if ($success) {
                return $token;
            }

            throw new Exception('No se pudo crear el token');
        } catch (Exception $e) {
            error_log("Error creando token de reset: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate password reset token
     */
    public function validatePasswordResetToken($token)
    {
        try {
            if (empty($token) || strlen($token) !== 64) {
                return null;
            }

            $tokenHash = hash('sha256', $token);

            $stmt = $this->db->prepare("
                SELECT prt.*, u.username, u.email 
                FROM password_reset_tokens prt
                JOIN usuarios u ON prt.user_id = u.id
                WHERE prt.token_hash = ? 
                AND prt.used_at IS NULL 
                AND prt.expires_at > NOW()
                LIMIT 1
            ");

            $stmt->execute([$tokenHash]);
            $result = $stmt->fetch();

            return $result ? $result : null;
        } catch (Exception $e) {
            error_log("Error validando token: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Use password reset token (mark as used)
     */
    public function usePasswordResetToken($tokenHash)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE password_reset_tokens 
                SET used_at = NOW() 
                WHERE token_hash = ? AND used_at IS NULL
            ");

            return $stmt->execute([$tokenHash]);
        } catch (Exception $e) {
            error_log("Error marcando token como usado: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clean up expired tokens (run periodically)
     */
    public function cleanupExpiredTokens()
    {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM password_reset_tokens 
                WHERE expires_at < NOW() AND used_at IS NULL
            ");

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error limpiando tokens: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all users with details (institution, etc) and filtering support
     */
    public function findAllWithDetails($filters = [])
    {
        $sql = "SELECT u.*, ie.nombre as institucion_nombre, ie.zona, ie.distrito, ie.codigo as amie 
                FROM {$this->table} u 
                LEFT JOIN instituciones_educativas ie ON u.institucion_id = ie.id";

        $where = [];
        $params = [];

        if (!empty($filters['rol'])) {
            $where[] = "u.rol = ?";
            $params[] = $filters['rol'];
        }

        if (!empty($filters['institucion_id'])) {
            $where[] = "u.institucion_id = ?";
            $params[] = $filters['institucion_id'];
        }

        if (!empty($filters['search'])) {
            $s = "%" . $filters['search'] . "%";
            $where[] = "(u.username LIKE ? OR u.nombre LIKE ? OR u.apellido LIKE ? OR u.email LIKE ?)";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY u.apellido, u.nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
<?php
class User extends BaseModel {
    protected $table = 'usuarios';
    
    public function findByUsername($username) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
    
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }
    
    public function createUser($data) {
        // Hash password
        $data['password'] = md5($data['password']);
        
        // Validate data
        if (empty($data['username']) || empty($data['password']) || empty($data['email'])) {
            throw new Exception("Campos requeridos faltantes");
        }
        
        // Check if username or email already exists
        if ($this->findByUsername($data['username'])) {
            throw new Exception("El nombre de usuario ya existe");
        }
        
        if ($this->findByEmail($data['email'])) {
            throw new Exception("El email ya está registrado");
        }
        
        return $this->create($data);
    }
    
    public function authenticate($username, $password) {
        $user = $this->findByUsername($username);
        
        if (!$user) {
            return false;
        }
        
        if ($user['password'] !== md5($password)) {
            return false;
        }
        
        return $user;
    }
    
    public function getStudentsByCourse($course = null) {
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
}
?>
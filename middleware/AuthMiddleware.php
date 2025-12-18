<?php
class AuthMiddleware {
    // Verifica que exista sesión activa; si no, redirige al login
    public static function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /test-vocacional/login');
            exit;
        }
    }
    
    // Verifica que el rol del usuario esté dentro de los permitidos
    public static function checkRole($allowedRoles) {
        if (!isset($_SESSION['user_role'])) {
            header('Location: /test-vocacional/login');
            exit;
        }
        
        if (!in_array($_SESSION['user_role'], $allowedRoles)) {
            http_response_code(403);
            echo "Acceso denegado";
            exit;
        }
    }
    
    // Retorna true si el usuario está autenticado
    public static function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }
    
    // Devuelve información básica del usuario actual desde la sesión
    public static function getCurrentUser() {
        if (!self::isAuthenticated()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['user_username'],
            'role' => $_SESSION['user_role'],
            'name' => $_SESSION['user_name']
        ];
    }
}
?>
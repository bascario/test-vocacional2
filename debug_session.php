<?php
session_start();

echo "<h2>Debug de Sesión</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n\n";
echo "Datos de sesión:\n";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Información del usuario:</h3>";
if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    require_once 'models/BaseModel.php';
    require_once 'models/User.php';

    $userModel = new User();
    $user = $userModel->find($_SESSION['user_id']);

    echo "<pre>";
    print_r($user);
    echo "</pre>";
} else {
    echo "<p>No hay usuario en sesión</p>";
}

echo "<hr>";
echo "<a href='/test-vocacional/login'>Ir a Login</a> | ";
echo "<a href='/test-vocacional/logout'>Cerrar Sesión</a>";
?>
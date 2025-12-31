<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/User.php';

$userModel = new User();

$username = 'testuser_' . rand(1000,9999);
$password = 'Secreto123';

try {
    $userId = $userModel->createUser([
        'username' => $username,
        'password' => $password,
        'email' => $username . '@example.com',
        'nombre' => 'Auto',
        'apellido' => 'Test'
    ]);
    echo "Usuario creado: {$username} (ID: {$userId})\n";

    $row = $userModel->findByUsername($username);
    echo "Stored hash length: " . strlen($row['password']) . "\n";

    $auth = $userModel->authenticate($username, $password);
    echo "Authenticate result: " . ($auth ? 'OK' : 'FALLA') . "\n";

    // Clean up: delete test user
    $stmt = $userModel->db->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    echo "Cleanup: usuario borrado\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

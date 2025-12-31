<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/User.php';

$userModel = new User();

$username = 'md5test_' . rand(1000,9999);
$passwordPlain = 'OldPass1';
$passwordMd5 = md5($passwordPlain);

// Insert directly using reflection to access db
$reflection = new ReflectionClass($userModel);
$prop = $reflection->getProperty('db');
$prop->setAccessible(true);
$db = $prop->getValue($userModel);

$stmt = $db->prepare("INSERT INTO usuarios (username, password, email, nombre, apellido) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$username, $passwordMd5, $username . '@example.com', 'Legacy', 'User']);
$insertId = $db->lastInsertId();

echo "Inserted legacy MD5 user: {$username} (ID: {$insertId})\n";

// Try authenticate
$res = $userModel->authenticate($username, $passwordPlain);
if ($res) {
    echo "Authenticate OK, user id: " . $res['id'] . "\n";
    $u = $userModel->findByUsername($username);
    echo "New password length: " . strlen($u['password']) . "\n";
    echo "New hash sample: " . substr($u['password'],0,40) . "...\n";

    // Verificar bandera de rehash
    if ($userModel->isPasswordRehashedFor($insertId)) {
        echo "Flag de rehash detectado correctamente para el usuario ID: {$insertId}\n";
    } else {
        echo "Flag de rehash NO detectado (puede que no se estableciera)\n";
    }
} else {
    echo "Authenticate FAIL\n";
}

// Cleanup
$del = $db->prepare("DELETE FROM usuarios WHERE id = ?");
$del->execute([$insertId]);
echo "Cleanup done\n";

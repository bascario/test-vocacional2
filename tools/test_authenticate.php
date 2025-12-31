<?php
echo "--- TEST AUTHENTICATE START ---\n";
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/User.php';

echo "Modelos cargados\n";

$userModel = new User();

$username = 'Ruben';
$password = '123456';

echo "Intentando autenticar usuario: {$username}\n";
$userRow = $userModel->findByUsername($username);
if ($userRow) {
    echo "Usuario encontrado: " . $userRow['username'] . " | password length: " . strlen($userRow['password']) . "\n";
    echo "passwd (full): " . $userRow['password'] . "\n";
    echo "password_verify result: " . (password_verify($password, $userRow['password']) ? 'true' : 'false') . "\n";
    echo "md5 match: " . (md5($password) === $userRow['password'] ? 'true' : 'false') . "\n";
} else {
    echo "Usuario NO encontrado\n";
}

$res = $userModel->authenticate($username, $password);
if ($res) {
    echo "Autenticación exitosa para usuario: {$username}\n";
    $u = $userModel->findByUsername($username);
    echo "Password en DB (trunc): " . substr($u['password'],0,40) . "...\n";
    echo "Password length: " . strlen($u['password']) . "\n";
} else {
    echo "Autenticación FALLIDA para usuario: {$username}\n";
}

echo "--- TEST AUTHENTICATE END ---\n";

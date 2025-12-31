<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../controllers/AuthController.php';

// Test values
$usernames = ['admin', 'Ruben', 'nuevo_usuario_123', ''];

foreach ($usernames as $u) {
    // emulate GET
    $_GET['username'] = $u;
    ob_start();
    $c = new AuthController();
    $c->checkUsername();
    $out = ob_get_clean();
    echo "Input: [{$u}] -> Output: {$out}\n";
}

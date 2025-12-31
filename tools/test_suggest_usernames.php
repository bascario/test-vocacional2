<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../controllers/AuthController.php';

// Quick helper to call endpoint logic directly
$_GET['username'] = 'admin';
$_GET['nombre'] = 'Juan Carlos';
$_GET['apellido'] = 'Pérez-López';

ob_start();
$c = new AuthController();
$c->checkUsername();
$out = ob_get_clean();
echo "Response for admin + Juan Carlos Pérez-López:\n";
echo $out . "\n\n";

$_GET['username'] = 'usuario.prueba';
$_GET['nombre'] = 'Ana';
$_GET['apellido'] = 'Gómez';

ob_start();
$c->checkUsername();
$out = ob_get_clean();

echo "Response for usuario.prueba + Ana Gómez:\n";
echo $out . "\n";

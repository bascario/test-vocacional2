<?php
/**
 * Script de recuperación de emergencia para el usuario administrador.
 * USO: Acceder a este archivo mediante el navegador: http://localhost/test-vocacional/force_reset_admin.php
 * IMPORTANTE: ELIMINAR ESTE ARCHIVO DESPUÉS DE USARLO.
 */

// Cargar configuración y clases necesarias
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/BaseModel.php';
require_once 'models/User.php';

echo "<h2>Iniciando recuperación de administrador...</h2>";

try {
    $userModel = new User();
    $adminUsername = 'admin';
    $newPassword = 'admin123';

    // Buscar si el usuario existe
    $admin = $userModel->findByUsername($adminUsername);

    if ($admin) {
        echo "Usuario 'admin' encontrado. Reseteando contraseña...<br>";
        $userModel->updatePassword($admin['id'], $newPassword);

        // Asegurar que tenga el rol de administrador
        $userModel->updateRole($admin['id'], 'administrador');

        echo "<strong style='color: green;'>ÉXITO: La contraseña para 'admin' ha sido restablecida a: $newPassword</strong><br>";
    } else {
        echo "Usuario 'admin' no encontrado. Intentando crearlo...<br>";

        $data = [
            'username' => $adminUsername,
            'password' => $newPassword,
            'email' => 'admin@test-vocacional.com',
            'nombre' => 'Administrador',
            'apellido' => 'Sistema',
            'rol' => 'administrador'
        ];

        $userModel->createUser($data);
        echo "<strong style='color: green;'>ÉXITO: El usuario 'admin' ha sido creado con la contraseña: $newPassword</strong><br>";
    }

    echo "<br><a href='login'>Ir al login</a>";
    echo "<p style='color: red;'><strong>RECUERDA ELIMINAR ESTE ARCHIVO (force_reset_admin.php) POR SEGURIDAD.</strong></p>";

} catch (Exception $e) {
    echo "<strong style='color: red;'>ERROR: " . $e->getMessage() . "</strong>";
}

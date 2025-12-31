<?php
/**
 * Script de migración de MD5 a bcrypt
 * 
 * Uso: php migrate_passwords.php
 * 
 * IMPORTANTE: Ejecutar este script UNA SOLA VEZ para migrar todas las contraseñas
 * existentes (que están en MD5) a bcrypt.
 */

// Cargar autoload de Composer
require_once 'vendor/autoload.php';

// Cargar configuración
require_once 'config/config.php';

// Cargar modelos
require_once 'models/BaseModel.php';
require_once 'models/User.php';

echo "=========================================\n";
echo "Migrando contraseñas de MD5 a bcrypt\n";
echo "=========================================\n\n";

try {
    $user = new User();
    
    // Obtener la conexión PDO del modelo
    $reflection = new ReflectionClass($user);
    $property = $reflection->getProperty('db');
    $property->setAccessible(true);
    $db = $property->getValue($user);
    
    // Obtener todos los usuarios con contraseñas MD5 (32 caracteres)
    $stmt = $db->prepare("SELECT id, username FROM usuarios WHERE LENGTH(password) = 32 ORDER BY id");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "✓ No hay contraseñas MD5 para migrar. El sistema ya usa bcrypt.\n";
        exit(0);
    }
    
    echo "Encontrados " . count($users) . " usuario(s) con contraseñas MD5.\n";
    echo "Se asignarán contraseñas temporales.\n\n";
    
    // Preparar statement para actualizar
    $updateStmt = $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
    
    $migrated = [];
    $failed = [];
    
    foreach ($users as $userData) {
        // Generar contraseña temporal segura
        $tempPassword = bin2hex(random_bytes(8));
        $bcryptHash = password_hash($tempPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        
        try {
            $success = $updateStmt->execute([$bcryptHash, $userData['id']]);
            
            if ($success) {
                $migrated[] = [
                    'id' => $userData['id'],
                    'username' => $userData['username'],
                    'tempPassword' => $tempPassword
                ];
                echo "✓ Migrado: {$userData['username']} (ID: {$userData['id']})\n";
            } else {
                $failed[] = $userData['username'];
                echo "✗ Error: No se pudo migrar {$userData['username']}\n";
            }
        } catch (Exception $e) {
            $failed[] = $userData['username'];
            echo "✗ Excepción: {$userData['username']} - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=========================================\n";
    echo "Resultados de la migración\n";
    echo "=========================================\n";
    echo "Exitosos: " . count($migrated) . "\n";
    echo "Fallidos: " . count($failed) . "\n\n";
    
    if (!empty($migrated)) {
        echo "USUARIOS MIGRADOS (requieren reseteo de contraseña):\n";
        echo "-----------------------------------------\n";
        foreach ($migrated as $m) {
            echo "Usuario: " . str_pad($m['username'], 20) . " | Contraseña temporal: " . $m['tempPassword'] . "\n";
        }
        echo "\nNota: Notifique a estos usuarios que deben cambiar su contraseña al siguiente inicio de sesión.\n";
    }
    
    if (!empty($failed)) {
        echo "\nUSUARIOS CON ERROR:\n";
        echo "-----------------------------------------\n";
        foreach ($failed as $f) {
            echo "- " . $f . "\n";
        }
    }
    
    // Verificación final
    echo "\n=========================================\n";
    echo "Verificación final\n";
    echo "=========================================\n";
    
    $checkStmt = $db->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN LENGTH(password) = 32 THEN 1 ELSE 0 END) as md5_remaining,
            SUM(CASE WHEN LENGTH(password) = 60 THEN 1 ELSE 0 END) as bcrypt_count
        FROM usuarios
    ");
    $checkStmt->execute();
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Total usuarios: " . $result['total'] . "\n";
    echo "Contraseñas MD5 restantes: " . ($result['md5_remaining'] ?? 0) . "\n";
    echo "Contraseñas bcrypt: " . ($result['bcrypt_count'] ?? 0) . "\n";
    
    if (($result['md5_remaining'] ?? 0) == 0) {
        echo "\n✓ MIGRACIÓN EXITOSA: Todas las contraseñas están en bcrypt\n";
    } else {
        echo "\n⚠ ADVERTENCIA: Aún hay contraseñas en MD5\n";
    }
    
} catch (Exception $e) {
    echo "Error crítico: " . $e->getMessage() . "\n";
    exit(1);
}
?>

-- =====================================================
-- Migración: Convertir MD5 a bcrypt en tabla usuarios
-- Fecha: 2025-12-18
-- Descripción: Actualiza contraseñas de MD5 a bcrypt
--               Este script debe ejecutarse una sola vez
-- =====================================================

-- Nota: Las contraseñas en MD5 tienen 32 caracteres
-- Las contraseñas en bcrypt tienen 60 caracteres
-- Después de ejecutar esta migración, usaremos password_hash() en el código

-- Procedimiento temporal para actualizar contraseñas MD5 a bcrypt
-- (Este es un ejemplo, en producción se recomienda hacer esto en PHP)

-- Alternativa: Usar un script PHP para hacer la migración de forma más segura
-- Este archivo SQL solo documenta el cambio

-- =====================================================
-- INSTRUCCIONES DE MIGRACIÓN:
-- =====================================================

-- 1. Ejecutar el siguiente script PHP en la raíz del proyecto:
--    
--    <?php
--    require_once 'config/config.php';
--    require_once 'models/User.php';
--    
--    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
--    $stmt = $db->prepare("SELECT id, password FROM usuarios WHERE LENGTH(password) = 32");
--    $stmt->execute();
--    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
--    
--    $updateStmt = $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
--    
--    $count = 0;
--    foreach ($users as $user) {
--        // Las contraseñas en MD5 no pueden ser convertidas a bcrypt
--        // Los usuarios deberán resetear su contraseña
--        // Por ahora, crear un hash bcrypt temporal
--        $tempPassword = bin2hex(random_bytes(16));
--        $bcryptHash = password_hash($tempPassword, PASSWORD_BCRYPT, ['cost' => 12]);
--        
--        $updateStmt->execute([$bcryptHash, $user['id']]);
--        $count++;
--    }
--    
--    echo "Migradas $count contraseñas a bcrypt\n";
--    ?>

-- 2. O ejecutar directamente este comando SQL (aunque no es recomendado):

-- Para verificar cuáles contraseñas están en MD5 (32 caracteres):
SELECT id, username, LENGTH(password) as password_length FROM usuarios WHERE LENGTH(password) = 32;

-- Nota: Las contraseñas en MD5 no pueden ser "convertidas" a bcrypt porque:
-- - MD5 es una función de hash unidireccional
-- - No podemos recuperar la contraseña original desde el hash MD5
-- - Por lo tanto, los usuarios existentes deberán resetear sus contraseñas

-- =====================================================
-- ALTERNATIVA: Script PHP para la migración segura
-- =====================================================

-- Crear un archivo migrate_passwords.php en la raíz y ejecutar:
-- php migrate_passwords.php

-- Contenido de migrate_passwords.php:
-- <?php
-- require_once 'config/config.php';
-- require_once 'models/User.php';
-- 
-- $user = new User();
-- 
-- // Obtener todos los usuarios con contraseñas MD5
-- $db = $user->getConnection();
-- $stmt = $db->prepare("SELECT id, username FROM usuarios WHERE LENGTH(password) = 32");
-- $stmt->execute();
-- $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
-- 
-- if (empty($users)) {
--     echo "No hay contraseñas MD5 para migrar\n";
--     exit(0);
-- }
-- 
-- // Actualizar cada usuario con una contraseña temporal
-- $updateStmt = $db->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
-- 
-- $results = [];
-- foreach ($users as $userData) {
--     $tempPassword = bin2hex(random_bytes(8));
--     $bcryptHash = password_hash($tempPassword, PASSWORD_BCRYPT, ['cost' => 12]);
--     
--     $success = $updateStmt->execute([$bcryptHash, $userData['id']]);
--     
--     if ($success) {
--         $results[] = [
--             'username' => $userData['username'],
--             'tempPassword' => $tempPassword,
--             'status' => 'migrado'
--         ];
--     }
-- }
-- 
-- echo "Migración completada:\n";
-- foreach ($results as $result) {
--     echo "Usuario: " . $result['username'] . " | Contraseña temporal: " . $result['tempPassword'] . "\n";
-- }
-- 
-- echo "\nTotal usuarios migrados: " . count($results) . "\n";
-- echo "Nota: Los usuarios deben resetear sus contraseñas en la siguiente sesión\n";
-- ?>

-- =====================================================
-- Verificación después de la migración
-- =====================================================

-- Verificar que todas las contraseñas tienen 60 caracteres (bcrypt):
SELECT 
    COUNT(*) as total_usuarios,
    SUM(CASE WHEN LENGTH(password) = 32 THEN 1 ELSE 0 END) as md5_count,
    SUM(CASE WHEN LENGTH(password) = 60 THEN 1 ELSE 0 END) as bcrypt_count
FROM usuarios;

-- Si md5_count es 0, la migración fue exitosa.

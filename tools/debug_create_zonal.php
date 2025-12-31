<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

echo "DEBUG create zonal start\n";
$user = new User();
try {
    $id = $user->createUser([
        'username' => 'dbg_zonal_' . rand(1000,9999),
        'password' => 'Zpass123',
        'email' => 'zonal_' . rand(1000,9999) . '@example.com',
        'nombre' => 'Dbg',
        'apellido' => 'Zonal',
        'rol' => 'zonal',
        'zona_id' => 'Zona-1'
    ]);
    echo "Created zonal user id: $id\n";
    // Show inserted row
    $r = new ReflectionClass($user);
    $p = $r->getProperty('db'); $p->setAccessible(true);
    $db = $p->getValue($user);
    $stmt = $db->prepare('SELECT id, username, rol, zona_id FROM usuarios WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    print_r($row);

    // cleanup
    $db->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
} catch (PDOException $p) {
    echo "PDOException: " . $p->getMessage() . "\n";
}
 echo "DEBUG create zonal end\n";

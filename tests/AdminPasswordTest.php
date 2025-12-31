<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AdminController.php';
require_once __DIR__ . '/../models/User.php';

class AdminPasswordTest extends TestCase
{
    private $adminController;
    private $userModel;
    private $db;

    protected function setUp(): void
    {
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }
        $this->adminController = new AdminController();
        $this->userModel = new User();

        $r = new ReflectionClass($this->userModel);
        $p = $r->getProperty('db');
        $p->setAccessible(true);
        $this->db = $p->getValue($this->userModel);

        // Ensure test DB has 'zonal' role in the enum (migration may not have been applied in test env)
        try {
            $this->db->exec("ALTER TABLE usuarios MODIFY COLUMN rol ENUM('administrador', 'zonal', 'dece', 'estudiante') NOT NULL DEFAULT 'estudiante';");
        } catch (PDOException $e) {
            // If alter fails, continue; tests will reveal any issues
        }

        // Ensure zona_id column exists (some DBs may not have had zonal migration applied)
        try {
            $this->db->exec("ALTER TABLE usuarios ADD COLUMN zona_id varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Zona asignada para usuarios con rol zonal'");
        } catch (PDOException $e) {
            // Ignore if column already exists or if DB doesn't allow adding
        }
    }

    public function testAdminCanChangeAnyUserPassword()
    {
        // Create admin user
        $adminId = $this->userModel->createUser([
            'username' => 'phpunit_admin_' . rand(10000,99999),
            'password' => 'AdminPass1',
            'email' => 'admin_' . rand(10000,99999) . '@example.com',
            'nombre' => 'Admin',
            'apellido' => 'Test',
            'rol' => 'administrador'
        ]);

        $userId = $this->userModel->createUser([
            'username' => 'phpunit_target_' . rand(10000,99999),
            'password' => 'OldPass1',
            'email' => 'target_' . rand(10000,99999) . '@example.com',
            'nombre' => 'Target',
            'apellido' => 'User',
            'rol' => 'estudiante'
        ]);

        $this->assertTrue($this->adminController->updateUserPassword($adminId, 'administrador', $userId, 'NewP4ss!'));

        $updated = $this->userModel->find($userId);
        $this->assertTrue(password_verify('NewP4ss!', $updated['password']));

        // Cleanup
        $this->db->prepare('DELETE FROM usuarios WHERE id IN (?, ?)')->execute([$adminId, $userId]);
    }

    public function testDeceCanOnlyChangeSameInstitution()
    {
        // Create institution and users
        require_once __DIR__ . '/../models/Institucion.php';
        $instModel = new Institucion();
        $instId = $instModel->createInstitution(['nombre' => 'Inst PHPUnit ' . rand(1000,9999), 'codigo' => 'PU' . rand(10,99), 'tipo' => 'Fiscal']);

        // Create DECE user as admin (createUser enforces only admin can assign 'dece')
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_role'] = 'administrador';

        $deceId = $this->userModel->createUser([
            'username' => 'phpunit_dece_' . rand(10000,99999),
            'password' => 'DecePass1',
            'email' => 'dece_' . rand(10000,99999) . '@example.com',
            'nombre' => 'Dece',
            'apellido' => 'User',
            'rol' => 'dece',
            'institucion_id' => $instId
        ]);

        // Clear session role to simulate regular user later
        unset($_SESSION['user_role']);

        $studentId = $this->userModel->createUser([
            'username' => 'phpunit_student_' . rand(10000,99999),
            'password' => 'StudPass1',
            'email' => 'stud_' . rand(10000,99999) . '@example.com',
            'nombre' => 'Student',
            'apellido' => 'SameInst',
            'rol' => 'estudiante',
            'institucion_id' => $instId
        ]);

        // Should succeed
        $this->assertTrue($this->adminController->updateUserPassword($deceId, 'dece', $studentId, 'NewStud1'));

        // Now create a student in another institution
        $student2Id = $this->userModel->createUser([
            'username' => 'phpunit_student2_' . rand(10000,99999),
            'password' => 'StudPass1',
            'email' => 'stud2_' . rand(10000,99999) . '@example.com',
            'nombre' => 'Student',
            'apellido' => 'OtherInst',
            'rol' => 'estudiante',
            'institucion_id' => null
        ]);

        $this->expectException(Exception::class);
        $this->adminController->updateUserPassword($deceId, 'dece', $student2Id, 'Nope123');

        // Cleanup
        $this->db->prepare('DELETE FROM usuarios WHERE id IN (?, ?, ?)')->execute([$deceId, $studentId, $student2Id]);
        $instModel->deleteInstitution($instId);
    }

    public function testZonalCanOnlyChangeSameZone()
    {
        $zone = 'Zona-' . rand(1,99);

        $zonalId = $this->userModel->createUser([
            'username' => 'phpunit_zonal_' . rand(10000,99999),
            'password' => 'ZonalPass1',
            'email' => 'zonal_' . rand(10000,99999) . '@example.com',
            'nombre' => 'Zonal',
            'apellido' => 'User',
            'rol' => 'zonal',
            'zona_id' => $zone
        ]);

        $userSameZone = $this->userModel->createUser([
            'username' => 'phpunit_zoneuser_' . rand(10000,99999),
            'password' => 'Pass1',
            'email' => 'zoneuser_' . rand(10000,99999) . '@example.com',
            'nombre' => 'Zone',
            'apellido' => 'User',
            'rol' => 'estudiante',
            'zona_id' => $zone
        ]);

        $this->assertTrue($this->adminController->updateUserPassword($zonalId, 'zonal', $userSameZone, 'NewZone1'));

        $userOther = $this->userModel->createUser([
            'username' => 'phpunit_zoneuser2_' . rand(10000,99999),
            'password' => 'Pass1',
            'email' => 'zoneuser2_' . rand(10000,99999) . '@example.com',
            'nombre' => 'Zone',
            'apellido' => 'Other',
            'rol' => 'estudiante',
            'zona_id' => 'AnotherZone'
        ]);

        $this->expectException(Exception::class);
        $this->adminController->updateUserPassword($zonalId, 'zonal', $userOther, 'Nope123');

        // Cleanup
        $this->db->prepare('DELETE FROM usuarios WHERE id IN (?, ?, ?, ?)')->execute([$zonalId, $userSameZone, $userOther,]);
    }
}

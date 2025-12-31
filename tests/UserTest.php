<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/User.php';

class UserTest extends TestCase
{
    private $userModel;
    private $db;

    protected function setUp(): void
    {
        $this->userModel = new User();
        $r = new ReflectionClass($this->userModel);
        $p = $r->getProperty('db');
        $p->setAccessible(true);
        $this->db = $p->getValue($this->userModel);
    }

    public function testCreateUserStoresBcryptAndAuthenticate()
    {
        $username = 'phpunit_user_' . rand(10000, 99999);
        $password = 'SecretP@ss1';
        $email = $username . '@example.com';

        $userId = $this->userModel->createUser([
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'nombre' => 'PHPUnit',
            'apellido' => 'Test'
        ]);

        $this->assertIsNumeric($userId, 'createUser should return the new user id');

        $stored = $this->userModel->findByUsername($username);
        $this->assertNotEmpty($stored, 'Stored user should exist');
        $this->assertGreaterThanOrEqual(60, strlen($stored['password']), 'Stored password must be bcrypt-like');
        $this->assertTrue(password_verify($password, $stored['password']), 'password_verify should succeed');

        // Authenticate via model
        $auth = $this->userModel->authenticate($username, $password);
        $this->assertNotFalse($auth, 'authenticate should return user on success');

        // Cleanup
        $stmt = $this->db->prepare('DELETE FROM usuarios WHERE id = ?');
        $stmt->execute([$userId]);
    }

    public function testMd5FallbackRehash()
    {
        $username = 'phpunit_md5_' . rand(10000, 99999);
        $password = 'OldPass123';
        $md5 = md5($password);

        // Insert legacy MD5 user
        $stmt = $this->db->prepare('INSERT INTO usuarios (username, password, email, nombre, apellido) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$username, $md5, $username . '@example.com', 'Legacy', 'User']);
        $insertId = $this->db->lastInsertId();

        // Authenticate - should return user and rehash password
        $res = $this->userModel->authenticate($username, $password);
        $this->assertNotFalse($res, 'Authentication should succeed for legacy MD5 user');
        $this->assertEquals($insertId, $res['id']);

        // Confirm password updated to bcrypt
        $updated = $this->userModel->find($insertId);
        $this->assertGreaterThanOrEqual(60, strlen($updated['password']), 'Password should be updated to bcrypt');
        $this->assertTrue(password_verify($password, $updated['password']), 'password_verify should succeed after rehash');

        // Flag check
        $this->assertTrue($this->userModel->isPasswordRehashedFor($insertId), 'Model should indicate password was rehashed for this user');

        // Cleanup
        $del = $this->db->prepare('DELETE FROM usuarios WHERE id = ?');
        $del->execute([$insertId]);
    }
}

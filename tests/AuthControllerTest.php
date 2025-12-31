<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../models/User.php';

class AuthControllerTest extends TestCase
{
    private $controller;
    private $userModel;

    protected function setUp(): void
    {
        $this->controller = new AuthController();
        $this->userModel = new User();
    }

    public function testGenerateUsernameSuggestionsAvailable()
    {
        $unique = 'unit_test_unique_' . rand(10000, 99999);
        $res = $this->controller->generateUsernameSuggestions($unique, '', '');
        $this->assertArrayHasKey('available', $res);
        $this->assertTrue($res['available'], 'New unique username should be marked available');
        $this->assertEmpty($res['suggestions'], 'Available should return no suggestions');
    }

    public function testGenerateUsernameSuggestionsWhenTaken()
    {
        $username = 'phpunit_taken_' . rand(10000,99999);
        // Create a user to occupy the username (use model)
        $userId = $this->userModel->createUser([
            'username' => $username,
            'password' => 'xYz12345',
            'email' => $username . '@example.com',
            'nombre' => 'Taken',
            'apellido' => 'User'
        ]);

        $res = $this->controller->generateUsernameSuggestions($username, 'Taken', 'User');
        $this->assertFalse($res['available'], 'Taken username should be marked not available');
        $this->assertIsArray($res['suggestions']);
        $this->assertNotEmpty($res['suggestions'], 'Suggestions should be returned when username taken');

        // Cleanup
        $r = new ReflectionClass($this->userModel);
        $p = $r->getProperty('db');
        $p->setAccessible(true);
        $db = $p->getValue($this->userModel);
        $db->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$userId]);
    }
}

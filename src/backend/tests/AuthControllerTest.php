<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Controllers\AuthController;
use App\Services\JWTService;
use App\Interfaces\DatabaseInterface;
use App\Entities\User;

/**
 * Unit tests for AuthController.
 */
class AuthControllerTest extends TestCase
{
    private JWTService $jwtService;
    private string $testSecret = 'test-secret-key-for-unit-tests';

    protected function setUp(): void
    {
        $this->jwtService = new JWTService($this->testSecret);
    }

    /**
     * Create a mock DatabaseInterface.
     */
    private function createMockDatabase(bool $registerReturns = true, ?int $verifyReturns = 1): DatabaseInterface
    {
        $mock = $this->createMock(DatabaseInterface::class);
        
        $mock->method('registerUser')
            ->willReturn($registerReturns);
        
        $mock->method('verifyCredentials')
            ->willReturn($verifyReturns);
        
        return $mock;
    }

    // ========================================================================
    // Registration Tests
    // ========================================================================

    public function testRegisterSuccessfully(): void
    {
        $database = $this->createMockDatabase(registerReturns: true);
        $controller = new AuthController($database, $this->jwtService);
        
        $result = $controller->register([
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'username' => 'mario_rossi'
        ]);
        
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('User registered successfully', $result['message']);
    }

    public function testRegisterFailsWithMissingEmail(): void
    {
        $database = $this->createMockDatabase();
        $controller = new AuthController($database, $this->jwtService);
        
        $result = $controller->register([
            'password' => 'password123',
            'username' => 'mario_rossi'
        ]);
        
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
    }

    public function testRegisterFailsWithMissingPassword(): void
    {
        $database = $this->createMockDatabase();
        $controller = new AuthController($database, $this->jwtService);
        
        $result = $controller->register([
            'email' => 'test@example.com',
            'username' => 'mario_rossi'
        ]);
        
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
    }

    public function testRegisterFailsWithMissingUsername(): void
    {
        $database = $this->createMockDatabase();
        $controller = new AuthController($database, $this->jwtService);
        
        $result = $controller->register([
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
    }

    public function testRegisterFailsWithInvalidEmail(): void
    {
        $database = $this->createMockDatabase();
        $controller = new AuthController($database, $this->jwtService);
        
        $result = $controller->register([
            'email' => 'not-an-email',
            'password' => 'password123',
            'username' => 'mario_rossi'
        ]);
        
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
    }

    public function testRegisterFailsWithWeakPassword(): void
    {
        $database = $this->createMockDatabase();
        $controller = new AuthController($database, $this->jwtService);
        
        // Password too short (less than 8 characters)
        $result = $controller->register([
            'email' => 'test@example.com',
            'password' => 'short',
            'username' => 'mario_rossi'
        ]);
        
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
    }

    // Uncomment for stricter password rules:
    //
    // public function testRegisterFailsWithPasswordMissingUppercase(): void
    // {
    //     $database = $this->createMockDatabase();
    //     $controller = new AuthController($database, $this->jwtService);
    //     
    //     $result = $controller->register([
    //         'email' => 'test@example.com',
    //         'password' => 'lowercase123!',
    //         'username' => 'mario_rossi'
    //     ]);
    //     
    //     $this->assertEquals('error', $result['status']);
    //     $this->assertEquals('Invalid input', $result['message']);
    // }
    //
    // public function testRegisterFailsWithPasswordMissingNumber(): void
    // {
    //     $database = $this->createMockDatabase();
    //     $controller = new AuthController($database, $this->jwtService);
    //     
    //     $result = $controller->register([
    //         'email' => 'test@example.com',
    //         'password' => 'NoNumbersHere!',
    //         'username' => 'mario_rossi'
    //     ]);
    //     
    //     $this->assertEquals('error', $result['status']);
    //     $this->assertEquals('Invalid input', $result['message']);
    // }
    //
    // public function testRegisterFailsWithPasswordMissingSpecialChar(): void
    // {
    //     $database = $this->createMockDatabase();
    //     $controller = new AuthController($database, $this->jwtService);
    //     
    //     $result = $controller->register([
    //         'email' => 'test@example.com',
    //         'password' => 'NoSpecialChar123',
    //         'username' => 'mario_rossi'
    //     ]);
    //     
    //     $this->assertEquals('error', $result['status']);
    //     $this->assertEquals('Invalid input', $result['message']);
    // }

    public function testRegisterFailsWithExistingEmail(): void
    {
        $database = $this->createMockDatabase(registerReturns: false);
        $controller = new AuthController($database, $this->jwtService);
        
        $result = $controller->register([
            'email' => 'existing@example.com',
            'password' => 'password123',
            'username' => 'mario_rossi'
        ]);
        
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Email already registered', $result['message']);
    }

    // ========================================================================
    // Login Tests
    // ========================================================================

    public function testLoginSuccessfully(): void
    {
        $database = $this->createMockDatabase(verifyReturns: 42);
        $controller = new AuthController($database, $this->jwtService);
        
        $result = $controller->login([
            'email' => 'user@example.com',
            'password' => 'password123'
        ]);
        
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('Login successful', $result['message']);
        $this->assertArrayHasKey('token', $result);
        $this->assertNotEmpty($result['token']);
    }

    public function testLoginReturnsValidJWT(): void
    {
        $userId = 42;
        $email = 'user@example.com';
        
        $database = $this->createMockDatabase(verifyReturns: $userId);
        $controller = new AuthController($database, $this->jwtService);
        
        $result = $controller->login([
            'email' => $email,
            'password' => 'password123'
        ]);
        
        // Validate the token
        $payload = $this->jwtService->validateToken($result['token']);
        $this->assertNotNull($payload);
        $this->assertEquals($userId, $payload['sub']);
        $this->assertEquals($email, $payload['email']);
    }

    public function testLoginFailsWithMissingEmail(): void
    {
        $database = $this->createMockDatabase();
        $controller = new AuthController($database, $this->jwtService);
        
        $result = $controller->login([
            'password' => 'password123'
        ]);
        
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
        $this->assertArrayNotHasKey('token', $result);
    }

    public function testLoginFailsWithMissingPassword(): void
    {
        $database = $this->createMockDatabase();
        $controller = new AuthController($database, $this->jwtService);
        
        $result = $controller->login([
            'email' => 'user@example.com'
        ]);
        
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
        $this->assertArrayNotHasKey('token', $result);
    }

    public function testLoginFailsWithInvalidCredentials(): void
    {
        $database = $this->createMockDatabase(verifyReturns: null);
        $controller = new AuthController($database, $this->jwtService);
        
        $result = $controller->login([
            'email' => 'user@example.com',
            'password' => 'wrongpassword'
        ]);
        
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid credentials', $result['message']);
        $this->assertArrayNotHasKey('token', $result);
    }

    public function testLoginFailsWithInvalidEmailFormat(): void
    {
        $database = $this->createMockDatabase();
        $controller = new AuthController($database, $this->jwtService);
        
        $result = $controller->login([
            'email' => 'not-an-email',
            'password' => 'password123'
        ]);
        
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid credentials', $result['message']);
        $this->assertArrayNotHasKey('token', $result);
    }

    public function testLoginUsesGenericErrorForNonExistentUser(): void
    {
        // This test ensures we don't leak information about whether a user exists
        $database = $this->createMockDatabase(verifyReturns: null);
        $controller = new AuthController($database, $this->jwtService);
        
        $result = $controller->login([
            'email' => 'nonexistent@example.com',
            'password' => 'anypassword'
        ]);
        
        $this->assertEquals('error', $result['status']);
        // Message should be generic, not revealing if user exists
        $this->assertEquals('Invalid credentials', $result['message']);
    }
}

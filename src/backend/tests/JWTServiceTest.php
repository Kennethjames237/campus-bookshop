<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Services\JWTService;

/**
 * Unit tests for JWTService.
 */
class JWTServiceTest extends TestCase
{
    private JWTService $jwtService;
    private string $testSecret = 'test-secret-key-for-unit-tests';

    protected function setUp(): void
    {
        $this->jwtService = new JWTService($this->testSecret);
    }

    public function testGenerateTokenReturnsString(): void
    {
        $token = $this->jwtService->generateToken(1, 'test@example.com');
        
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testGenerateTokenHasThreeParts(): void
    {
        $token = $this->jwtService->generateToken(1, 'test@example.com');
        $parts = explode('.', $token);
        
        $this->assertCount(3, $parts);
    }

    public function testValidateTokenReturnsPayload(): void
    {
        $userId = 42;
        $email = 'user@example.com';
        
        $token = $this->jwtService->generateToken($userId, $email);
        $payload = $this->jwtService->validateToken($token);
        
        $this->assertNotNull($payload);
        $this->assertEquals($userId, $payload['sub']);
        $this->assertEquals($email, $payload['email']);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('exp', $payload);
    }

    public function testValidateTokenReturnsNullForInvalidToken(): void
    {
        $payload = $this->jwtService->validateToken('invalid.token.here');
        
        $this->assertNull($payload);
    }

    public function testValidateTokenReturnsNullForTamperedToken(): void
    {
        $token = $this->jwtService->generateToken(1, 'test@example.com');
        
        // Tamper with the token
        $tamperedToken = $token . 'tampered';
        $payload = $this->jwtService->validateToken($tamperedToken);
        
        $this->assertNull($payload);
    }

    public function testValidateTokenReturnsNullForWrongSecret(): void
    {
        // Generate token with one secret
        $token = $this->jwtService->generateToken(1, 'test@example.com');
        
        // Try to validate with different secret
        $differentService = new JWTService('different-secret');
        $payload = $differentService->validateToken($token);
        
        $this->assertNull($payload);
    }

    public function testDecodeTokenWithoutValidation(): void
    {
        $userId = 123;
        $email = 'decode@test.com';
        
        $token = $this->jwtService->generateToken($userId, $email);
        $payload = $this->jwtService->decodeToken($token);
        
        $this->assertEquals($userId, $payload['sub']);
        $this->assertEquals($email, $payload['email']);
    }

    public function testDecodeTokenThrowsExceptionForInvalidFormat(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid token format');
        
        $this->jwtService->decodeToken('not-a-jwt-token');
    }

    public function testGetExpirationReturnsEightHours(): void
    {
        $expiration = $this->jwtService->getExpiration();
        
        $this->assertEquals(28800, $expiration); // 8 hours in seconds
    }

    public function testTokenContainsCorrectClaims(): void
    {
        $token = $this->jwtService->generateToken(1, 'test@example.com');
        $payload = $this->jwtService->decodeToken($token);
        
        // Check all expected claims are present
        $this->assertArrayHasKey('sub', $payload);
        $this->assertArrayHasKey('email', $payload);
        $this->assertArrayHasKey('iat', $payload);
        $this->assertArrayHasKey('exp', $payload);
        
        // Verify expiration is in the future
        $this->assertGreaterThan(time(), $payload['exp']);
        
        // Verify issued at is current time (within 1 second tolerance)
        $this->assertEqualsWithDelta(time(), $payload['iat'], 1);
    }
}

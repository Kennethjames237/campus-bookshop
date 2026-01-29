<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Exception;

/**
 * Service for JWT token generation and validation.
 */
class JWTService
{
    private string $secretKey;
    private string $algorithm = 'HS256';
    private int $expiration = 28800; // 8 hours in seconds

    public function __construct(?string $secretKey = null)
    {
        $this->secretKey = $secretKey ?? getenv('JWT_SECRET') ?: 'default-secret-key';
    }

    /**
     * Generate a JWT token for a user.
     *
     * @param int $userId The user's ID
     * @param string $email The user's email
     * @return string The generated JWT token
     */
    public function generateToken(int $userId, string $email): string
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + $this->expiration;

        $payload = [
            'sub' => $userId,
            'email' => $email,
            'iat' => $issuedAt,
            'exp' => $expirationTime
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Validate a JWT token and return the payload.
     *
     * @param string $token The JWT token to validate
     * @return array|null The decoded payload or null if invalid/expired
     */
    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Decode a JWT token without validation (for debugging purposes).
     *
     * @param string $token The JWT token to decode
     * @return array The decoded payload
     * @throws Exception If the token format is invalid
     */
    public function decodeToken(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }

        $payload = json_decode(base64_decode($parts[1]), true);
        if ($payload === null) {
            throw new Exception('Invalid token payload');
        }

        return $payload;
    }

    /**
     * Get the token expiration time in seconds.
     *
     * @return int Expiration time in seconds
     */
    public function getExpiration(): int
    {
        return $this->expiration;
    }
}

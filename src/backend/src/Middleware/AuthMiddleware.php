<?php

namespace App\Middleware;

use App\Services\JWTService;

/**
 * Middleware for authenticating requests using JWT tokens.
 */
class AuthMiddleware
{
    private JWTService $jwtService;

    public function __construct(JWTService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Authenticate the request by validating the JWT token.
     *
     * @return array|null User data from token if valid, null otherwise
     */
    public function authenticate(): ?array
    {
        $token = $this->extractToken();
        
        if ($token === null) {
            return null;
        }

        return $this->jwtService->validateToken($token);
    }

    /**
     * Handle authentication and return error response if not authenticated.
     *
     * @return array|null Null if authenticated, error response array otherwise
     */
    public function handle(): ?array
    {
        $userData = $this->authenticate();

        if ($userData === null) {
            return [
                'status' => 'error',
                'message' => 'Unauthorized'
            ];
        }

        return null;
    }

    /**
     * Get the authenticated user data.
     * Should be called after successful authentication.
     *
     * @return array|null User data or null if not authenticated
     */
    public function getAuthenticatedUser(): ?array
    {
        return $this->authenticate();
    }

    /**
     * Extract JWT token from Authorization header.
     *
     * @return string|null The token or null if not found
     */
    private function extractToken(): ?string
    {
        // Get Authorization header
        $authHeader = $this->getAuthorizationHeader();

        if ($authHeader === null) {
            return null;
        }

        // Check for Bearer token format
        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * Get the Authorization header from the request.
     *
     * @return string|null The header value or null if not found
     */
    private function getAuthorizationHeader(): ?string
    {
        // Try different methods to get the Authorization header
        
        // Method 1: getallheaders() function (Apache)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $name => $value) {
                if (strtolower($name) === 'authorization') {
                    return $value;
                }
            }
        }

        // Method 2: $_SERVER superglobal
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }

        // Method 3: Apache-specific
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        return null;
    }
}

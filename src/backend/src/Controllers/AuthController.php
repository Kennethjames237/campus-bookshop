<?php

namespace App\Controllers;

use App\Entities\User;
use App\Interfaces\DatabaseInterface;
use App\Services\JWTService;

/**
 * Controller for authentication operations (login and registration).
 */
class AuthController
{
    private DatabaseInterface $database;
    private JWTService $jwtService;

    public function __construct(DatabaseInterface $database, JWTService $jwtService)
    {
        $this->database = $database;
        $this->jwtService = $jwtService;
    }

    /**
     * Register a new user.
     *
     * @param array $data Request data containing email, username, password
     * @return array Response with status and message
     */
    public function register(array $data): array
    {
        // Validate required fields
        $validation = $this->validateRegistrationData($data);
        if ($validation !== null) {
            return $validation;
        }

        $email = trim($data['email']);
        $password = $data['password'];
        $username = trim($data['username']);

        // Validate email format
        if (!$this->isValidEmail($email)) {
            return [
                'status' => 'error',
                'message' => 'Invalid input'
            ];
        }

        // Validate password strength
        $passwordValidation = $this->validatePassword($password);
        if ($passwordValidation !== null) {
            return $passwordValidation;
        }

        // Hash password before storing
        $hashedPassword = $this->hashPassword($password);

        // Create user entity
        $user = new User(
            id: null,
            username: $username,
            email: $email,
            password: $hashedPassword
        );

        // Attempt to register user
        $success = $this->database->registerUser($user);

        if (!$success) {
            return [
                'status' => 'error',
                'message' => 'Email already registered'
            ];
        }

        return [
            'status' => 'success',
            'message' => 'User registered successfully'
        ];
    }

    /**
     * Authenticate a user and return a JWT token.
     *
     * @param array $data Request data containing email and password
     * @return array Response with status and token/message
     */
    public function login(array $data): array
    {
        // Validate required fields
        if (empty($data['email']) || empty($data['password'])) {
            return [
                'status' => 'error',
                'message' => 'Invalid input'
            ];
        }

        $email = trim($data['email']);
        $password = $data['password'];

        // Validate email format
        if (!$this->isValidEmail($email)) {
            return [
                'status' => 'error',
                'message' => 'Invalid credentials'
            ];
        }

        // Verify credentials
        $userId = $this->database->verifyCredentials($email, $password);

        if ($userId === null) {
            // Generic error message to prevent user enumeration
            return [
                'status' => 'error',
                'message' => 'Invalid credentials'
            ];
        }

        // Generate JWT token
        $token = $this->jwtService->generateToken($userId, $email);

        return [
            'status' => 'success',
            'message' => 'Login successful',
            'token' => $token
        ];
    }

    /**
     * Validate registration data for required fields.
     *
     * @param array $data The registration data
     * @return array|null Error response or null if valid
     */
    private function validateRegistrationData(array $data): ?array
    {
        $requiredFields = ['email', 'username', 'password'];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid input'
                ];
            }
        }

        return null;
    }

    /**
     * Validate email format.
     *
     * @param string $email The email to validate
     * @return bool True if valid
     */
    private function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate password strength.
     *
     * @param string $password The password to validate
     * @return array|null Error response or null if valid
     */
    private function validatePassword(string $password): ?array
    {
        // Minimum 8 characters
        if (strlen($password) < 8) {
            return [
                'status' => 'error',
                'message' => 'Invalid input'
            ];
        }

        // Uncomment for stricter password rules:
        //
        // // At least one uppercase letter
        // if (!preg_match('/[A-Z]/', $password)) {
        //     return [
        //         'status' => 'error',
        //         'message' => 'Invalid input'
        //     ];
        // }
        //
        // // At least one lowercase letter
        // if (!preg_match('/[a-z]/', $password)) {
        //     return [
        //         'status' => 'error',
        //         'message' => 'Invalid input'
        //     ];
        // }
        //
        // // At least one digit
        // if (!preg_match('/[0-9]/', $password)) {
        //     return [
        //         'status' => 'error',
        //         'message' => 'Invalid input'
        //     ];
        // }
        //
        // // At least one special character
        // if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        //     return [
        //         'status' => 'error',
        //         'message' => 'Invalid input'
        //     ];
        // }

        return null;
    }

    /**
     * Hash a password using bcrypt.
     *
     * @param string $password The plain text password
     * @return string The hashed password
     */
    private function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}

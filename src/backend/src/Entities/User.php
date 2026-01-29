<?php

namespace App\Entities;

/**
 * User entity class representing a user in the system.
 */
class User
{
    public ?int $id;
    public string $username;
    public string $email;
    public string $password;

    public function __construct(
        ?int $id = null,
        string $username = '',
        string $email = '',
        string $password = ''
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->password = $password;
    }
}

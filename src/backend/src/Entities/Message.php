<?php

namespace App\Entities;

use DateTime;

/**
 * Message entity class representing a message between users in the system.
 */
class Message
{
    public ?int $id;
    public int $senderId;
    public int $receiverId;
    public string $content;
    public DateTime $createdAt;

    public function __construct(
        ?int $id = null,
        int $senderId = 0,
        int $receiverId = 0,
        string $content = '',
        ?DateTime $createdAt = null
    ) {
        $this->id = $id;
        $this->senderId = $senderId;
        $this->receiverId = $receiverId;
        $this->content = $content;
        $this->createdAt = $createdAt ?? new DateTime();
    }
}

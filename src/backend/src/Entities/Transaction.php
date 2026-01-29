<?php

namespace App\Entities;

use DateTime;

/**
 * Transaction entity class representing a book purchase in the system.
 */
class Transaction
{
    public ?int $id;
    public int $bookId;
    public int $buyerId;
    public int $sellerId;
    public float $price;
    public DateTime $createdAt;

    public function __construct(
        ?int $id = null,
        int $bookId = 0,
        int $buyerId = 0,
        int $sellerId = 0,
        float $price = 0.0,
        ?DateTime $createdAt = null
    ) {
        $this->id = $id;
        $this->bookId = $bookId;
        $this->buyerId = $buyerId;
        $this->sellerId = $sellerId;
        $this->price = $price;
        $this->createdAt = $createdAt ?? new DateTime();
    }
}

<?php

namespace App\Entities;

/**
 * Book entity class representing a book listing in the system.
 */
class Book
{
    public ?int $id;
    public string $name;
    public string $author;
    public string $isbn;
    public string $imagePath;
    public string $teacher;
    public string $course;
    public float $price;
    public int $sellerId;
    public string $sellerUsername;
    public bool $available;

    public function __construct(
        ?int $id = null,
        string $name = '',
        string $author = '',
        string $isbn = '',
        string $imagePath = '',
        string $teacher = '',
        string $course = '',
        float $price = 0.0,
        int $sellerId = 0,
	string $sellerUsername = '',
        bool $available = true
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->author = $author;
        $this->isbn = $isbn;
        $this->imagePath = $imagePath;
        $this->teacher = $teacher;
        $this->course = $course;
        $this->price = $price;
        $this->sellerId = $sellerId;
	$this->sellerUsername = $sellerUsername;
        $this->available = $available;
    }
}

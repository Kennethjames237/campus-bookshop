<?php

namespace App\Interfaces;

use App\Entities\User;
use App\Entities\Book;
use App\Entities\Transaction;
use App\Entities\Message;

/**
 * Database interface for data management operations.
 * This interface defines the contract that the database layer must implement.
 */
interface DatabaseInterface
{
    // ========================================================================
    // User Management Methods
    // ========================================================================

    /**
     * Register a new user in the database.
     *
     * @param User $user The user entity to register (password should be pre-hashed)
     * @return bool True if registration successful, false if email already exists
     */
    public function registerUser(User $user): bool;

    /**
     * Verify user credentials.
     *
     * @param string $email User's email
     * @param string $password User's plain text password (will be verified with password_verify)
     * @return int|null User ID if credentials are valid, null otherwise
     */
    public function verifyCredentials(string $email, string $password): ?int;

    /**
     * Get a user by their ID.
     *
     * @param int $id User ID
     * @return User|null User entity or null if not found
     */
    public function getUserById(int $id): ?User;

    // ========================================================================
    // Book Management Methods
    // ========================================================================

    /**
     * Get all available books.
     *
     * @return array Array of Book entities
     */
    public function getAllBooks(): array;

    /**
     * Insert a new book into the database.
     *
     * @param Book $book The book entity to insert
     * @return int The ID of the newly inserted book
     */
    public function insertBook(Book $book): int;

    /**
     * Update an existing book.
     *
     * @param Book $book The book entity with updated values (id must be set)
     * @return bool True if update successful, false otherwise
     */
    public function updateBook(Book $book): bool;

    /**
     * Delete a book from the database.
     *
     * @param int $id The ID of the book to delete
     * @return bool True if deletion successful, false otherwise
     */
    public function deleteBook(int $id): bool;

    // ========================================================================
    // Transaction Management Methods
    // ========================================================================

    /**
     * Place an order for a book.
     * Creates a transaction record and marks the book as unavailable.
     *
     * @param int $bookId The ID of the book to purchase
     * @param int $buyerId The ID of the buyer
     * @return int The ID of the created transaction/order
     */
    public function placeOrder(int $bookId, int $buyerId): int;

    /**
     * Get all purchases made by a buyer.
     *
     * @param int $buyerId The buyer's user ID
     * @return array Array of purchase data with book and seller info
     */
    public function getPurchasesByBuyer(int $buyerId): array;

    /**
     * Get all sales made by a seller.
     *
     * @param int $sellerId The seller's user ID
     * @return array Array of sale data with book and buyer info
     */
    public function getSalesBySeller(int $sellerId): array;

    // ========================================================================
    // Message Management Methods
    // ========================================================================

    /**
     * Get all conversations for a user.
     * Returns a list of users the current user has exchanged messages with,
     * along with the last message preview and timestamp.
     *
     * @param int $userId The user's ID
     * @return array Array of conversation data with user info and last message
     */
    public function getConversations(int $userId): array;

    /**
     * Get all messages between two users.
     * Returns messages ordered chronologically.
     *
     * @param int $userId1 First user's ID
     * @param int $userId2 Second user's ID
     * @return array Array of Message entities
     */
    public function getMessages(int $userId1, int $userId2): array;

    /**
     * Send a message from one user to another.
     *
     * @param Message $message The message entity to insert
     * @return int The ID of the newly inserted message
     */
    public function sendMessage(Message $message): int;
}

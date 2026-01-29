<?php

namespace App\Controllers;

use App\Entities\Book;
use App\Interfaces\DatabaseInterface;

/**
 * Controller for purchase operations.
 */
class PurchaseController
{
    private DatabaseInterface $database;

    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;
    }

    /**
     * Execute a book purchase.
     * POST /purchase
     *
     * @param array $data Request data containing bookId
     * @param int $buyerId Authenticated user ID (from JWT)
     * @return array Response with status and message/data
     */
    public function purchase(array $data, int $buyerId): array
    {
        // 1. Get bookId from request
        $bookId = isset($data['bookId']) ? (int) $data['bookId'] : 0;

        // 2. Find book
        $book = $this->getBookById($bookId);
        if ($book === null) {
            return [
                'status' => 'error',
                'message' => 'Book not found'
            ];
        }

        // 3. Check if available
        if (!$book->available) {
            return [
                'status' => 'error',
                'message' => 'Book already sold'
            ];
        }

        // 4. Check not self-purchase
        if ($book->sellerId === $buyerId) {
            return [
                'status' => 'error',
                'message' => 'Cannot purchase your own book'
            ];
        }

        // 5. Place order
        try {
            $orderId = $this->database->placeOrder($bookId, $buyerId);

            // 6. Get seller email
            $seller = $this->database->getUserById($book->sellerId);
            $sellerEmail = $seller ? $seller->email : '';

            return [
                'status' => 'success',
                'message' => 'Purchase completed successfully',
                'orderId' => $orderId,
                'sellerEmail' => $sellerEmail
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * List user's purchases.
     * GET /purchases
     *
     * @param int $buyerId Authenticated user ID (from JWT)
     * @return array Response with status and data
     */
    public function listPurchases(int $buyerId): array
    {
        try {
            $data = $this->database->getPurchasesByBuyer($buyerId);
            return [
                'status' => 'success',
                'data' => $data
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Server error'
            ];
        }
    }

    /**
     * List user's sales.
     * GET /sales
     *
     * @param int $sellerId Authenticated user ID (from JWT)
     * @return array Response with status and data
     */
    public function listSales(int $sellerId): array
    {
        try {
            $data = $this->database->getSalesBySeller($sellerId);
            return [
                'status' => 'success',
                'data' => $data
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Server error'
            ];
        }
    }

    /**
     * Get a book by ID from the database.
     *
     * @param int $id Book ID
     * @return Book|null Book entity or null if not found
     */
    private function getBookById(int $id): ?Book
    {
        $books = $this->database->getAllBooks();
        foreach ($books as $book) {
            if ($book->id === $id) {
                return $book;
            }
        }
        return null;
    }
}

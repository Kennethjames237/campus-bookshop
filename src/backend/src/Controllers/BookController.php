<?php

namespace App\Controllers;

use App\Entities\Book;
use App\Interfaces\DatabaseInterface;
use App\Services\ImageUploadService;

/**
 * Controller for book catalog operations (CRUD).
 */
class BookController
{
    private DatabaseInterface $database;
    private ImageUploadService $imageUploadService;

    public function __construct(DatabaseInterface $database, ImageUploadService $imageUploadService)
    {
        $this->database = $database;
        $this->imageUploadService = $imageUploadService;
    }

    /**
     * List all available books.
     * If userId is provided, excludes books owned by that user.
     *
     * @param int|null $userId Authenticated user ID (optional)
     * @return array Response with status and data
     */
    public function list(?int $userId = null, bool $myBooks = false): array
    {
        try {
            $books = $this->database->getAllBooks();

            // Filter out user's own books if authenticated
            if ($userId !== null) {
                $books = array_values(array_filter($books, function (Book $book) use ($userId, $myBooks) {
                if ($myBooks) return $book->sellerId === $userId;
                else return $book->sellerId !== $userId;
                }));
            }

            // Convert Book entities to arrays for JSON response
            $booksData = array_map(function (Book $book) {
                return $this->bookToArray($book);
            }, $books);

            return [
                'status' => 'success',
                'data' => $booksData
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Server error'
            ];
        }
    }

    /**
     * Create a new book listing.
     *
     * @param array $data Request data
     * @param int $sellerId Authenticated user ID (from JWT)
     * @return array Response with status and message
     */
    public function create(array $data, int $sellerId): array
    {
	$user = $this->database->getUserById($sellerId);
	$sellerUsername = $user->username;
        // Validate required fields
        $validation = $this->validateCreateData($data);
        if ($validation !== null) {
            return $validation;
        }

        // Handle image upload if provided
        $imagePath = '';
        if (!empty($data['image'])) {
            $uploadResult = $this->imageUploadService->uploadBase64($data['image']);
            if (!$uploadResult['success']) {
                return [
                    'status' => 'error',
                    'message' => $uploadResult['error']
                ];
            }
            $imagePath = $uploadResult['path'];
        }

        // Create book entity
        $book = new Book(
            id: null,
            name: trim($data['name']),
            author: trim($data['author']),
            isbn: $this->normalizeIsbn($data['isbn']),
            imagePath: $imagePath,
            teacher: isset($data['teacher']) ? trim($data['teacher']) : '',
            course: isset($data['course']) ? trim($data['course']) : '',
            price: (float) $data['price'],
            sellerId: $sellerId,
	    sellerUsername: $sellerUsername,
            available: true
        );

        try {
            $bookId = $this->database->insertBook($book);

            return [
                'status' => 'success',
                'id' => $bookId,
                'message' => 'Libro messo in vendita con successo'
            ];
        } catch (\Exception $e) {
            // Clean up uploaded image on failure
            if (!empty($imagePath)) {
                $this->imageUploadService->delete($imagePath);
            }
            return [
                'status' => 'error',
                'message' => 'Server error'
            ];
        }
    }

    /**
     * Update an existing book.
     *
     * @param array $data Request data (must include 'id')
     * @param int $userId Authenticated user ID
     * @return array Response with status and message
     */
    public function update(array $data, int $userId): array
    {
        // Validate id is provided
        if (empty($data['id']) || !is_numeric($data['id'])) {
            return [
                'status' => 'error',
                'message' => 'Invalid input'
            ];
        }

        $bookId = (int) $data['id'];

        // Find the book
        $existingBook = $this->getBookById($bookId);
        if ($existingBook === null) {
            return [
                'status' => 'error',
                'message' => 'Book not found'
            ];
        }

        // Check ownership
        if ($existingBook->sellerId !== $userId) {
            return [
                'status' => 'error',
                'message' => 'Forbidden'
            ];
        }

        // Validate update data if provided
        $validation = $this->validateUpdateData($data);
        if ($validation !== null) {
            return $validation;
        }

        // Handle image upload if provided
        $newImagePath = null;
        if (!empty($data['image'])) {
            $uploadResult = $this->imageUploadService->uploadBase64($data['image']);
            if (!$uploadResult['success']) {
                return [
                    'status' => 'error',
                    'message' => $uploadResult['error']
                ];
            }
            $newImagePath = $uploadResult['path'];
        }

        // Apply partial updates
        $updatedBook = $this->applyUpdates($existingBook, $data, $newImagePath);

        try {
            $success = $this->database->updateBook($updatedBook);

            if ($success) {
                // Delete old image if new one was uploaded
                if ($newImagePath !== null && !empty($existingBook->imagePath)) {
                    $this->imageUploadService->delete($existingBook->imagePath);
                }

                return [
                    'status' => 'success',
                    'message' => 'Book updated'
                ];
            }

            // Clean up new image on failure
            if ($newImagePath !== null) {
                $this->imageUploadService->delete($newImagePath);
            }

            return [
                'status' => 'error',
                'message' => 'Server error'
            ];
        } catch (\Exception $e) {
            // Clean up new image on exception
            if ($newImagePath !== null) {
                $this->imageUploadService->delete($newImagePath);
            }
            return [
                'status' => 'error',
                'message' => 'Server error'
            ];
        }
    }

    /**
     * Delete a book.
     *
     * @param array $data Request data (must include 'id')
     * @param int $userId Authenticated user ID
     * @return array Response with status and message
     */
    public function delete(array $data, int $userId): array
    {
        // Validate id is provided
        if (empty($data['id']) || !is_numeric($data['id'])) {
            return [
                'status' => 'error',
                'message' => 'Invalid input'
            ];
        }

        $bookId = (int) $data['id'];

        // Find the book
        $existingBook = $this->getBookById($bookId);
        if ($existingBook === null) {
            return [
                'status' => 'error',
                'message' => 'Book not found'
            ];
        }

        // Check ownership
        if ($existingBook->sellerId !== $userId) {
            return [
                'status' => 'error',
                'message' => 'Forbidden'
            ];
        }

        try {
            $success = $this->database->deleteBook($bookId);

            if ($success) {
                // Delete associated image
                if (!empty($existingBook->imagePath)) {
                    $this->imageUploadService->delete($existingBook->imagePath);
                }

                return [
                    'status' => 'success',
                    'message' => 'Book deleted'
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Server error'
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

    /**
     * Validate required fields for book creation.
     *
     * @param array $data Request data
     * @return array|null Error response or null if valid
     */
    private function validateCreateData(array $data): ?array
    {
        // Required fields
        $requiredFields = ['name', 'author', 'isbn', 'price'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid input'
                ];
            }
        }

        // Validate name
        $name = trim($data['name']);
        if (strlen($name) === 0 || strlen($name) > 255) {
            return [
                'status' => 'error',
                'message' => 'Invalid input'
            ];
        }

        // Validate author
        $author = trim($data['author']);
        if (strlen($author) === 0 || strlen($author) > 255) {
            return [
                'status' => 'error',
                'message' => 'Invalid input'
            ];
        }

        // Validate ISBN (10 or 13 digits only)
        if (!$this->isValidIsbn($data['isbn'])) {
            return [
                'status' => 'error',
                'message' => 'Invalid input'
            ];
        }

        // Validate price (must be > 0)
        if (!is_numeric($data['price']) || (float) $data['price'] <= 0) {
            return [
                'status' => 'error',
                'message' => 'Invalid input'
            ];
        }

        return null;
    }

    /**
     * Validate update data (partial update).
     *
     * @param array $data Request data
     * @return array|null Error response or null if valid
     */
    private function validateUpdateData(array $data): ?array
    {
        // Validate name if provided
        if (isset($data['name'])) {
            $name = trim($data['name']);
            if (strlen($name) === 0 || strlen($name) > 255) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid input'
                ];
            }
        }

        // Validate author if provided
        if (isset($data['author'])) {
            $author = trim($data['author']);
            if (strlen($author) === 0 || strlen($author) > 255) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid input'
                ];
            }
        }

        // Validate ISBN if provided
        if (isset($data['isbn']) && !$this->isValidIsbn($data['isbn'])) {
            return [
                'status' => 'error',
                'message' => 'Invalid input'
            ];
        }

        // Validate price if provided (must be > 0)
        if (isset($data['price'])) {
            if (!is_numeric($data['price']) || (float) $data['price'] <= 0) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid input'
                ];
            }
        }

        // Validate available if provided (must be boolean)
        if (isset($data['available']) && !is_bool($data['available'])) {
            return [
                'status' => 'error',
                'message' => 'Invalid input'
            ];
        }

        return null;
    }

    /**
     * Validate ISBN format (10 or 13 digits).
     *
     * @param string $isbn ISBN to validate
     * @return bool True if valid
     */
    private function isValidIsbn(string $isbn): bool
    {
        // Remove hyphens and spaces
        $normalized = preg_replace('/[-\s]/', '', $isbn);
        // Must be 10 or 13 digits
        return preg_match('/^\d{10}$|^\d{13}$/', $normalized) === 1;
    }

    /**
     * Normalize ISBN by removing hyphens and spaces.
     *
     * @param string $isbn ISBN to normalize
     * @return string Normalized ISBN
     */
    private function normalizeIsbn(string $isbn): string
    {
        return preg_replace('/[-\s]/', '', $isbn);
    }

    /**
     * Apply partial updates to a book entity.
     *
     * @param Book $book Existing book
     * @param array $data Update data
     * @param string|null $newImagePath New image path (if uploaded)
     * @return Book Updated book entity
     */
    private function applyUpdates(Book $book, array $data, ?string $newImagePath): Book
    {
        $updatedBook = new Book(
            id: $book->id,
            name: isset($data['name']) ? trim($data['name']) : $book->name,
            author: isset($data['author']) ? trim($data['author']) : $book->author,
            isbn: isset($data['isbn']) ? $this->normalizeIsbn($data['isbn']) : $book->isbn,
            imagePath: $newImagePath ?? $book->imagePath,
            teacher: isset($data['teacher']) ? trim($data['teacher']) : $book->teacher,
            course: isset($data['course']) ? trim($data['course']) : $book->course,
            price: isset($data['price']) ? (float) $data['price'] : $book->price,
            sellerId: $book->sellerId,
	    sellerUsername: $book->sellerUsername,
            available: isset($data['available']) ? (bool) $data['available'] : $book->available
        );

        return $updatedBook;
    }

    /**
     * Convert a Book entity to an array for JSON response.
     *
     * @param Book $book Book entity
     * @return array Book data as array
     */
    private function bookToArray(Book $book): array
    {
        return [
            'id' => $book->id,
            'name' => $book->name,
            'author' => $book->author,
            'isbn' => $book->isbn,
            'imagePath' => $this->getImageAsBase64($book->imagePath),
            'teacher' => $book->teacher,
            'course' => $book->course,
            'price' => $book->price,
            'sellerId' => $book->sellerId,
	    'sellerUsername' => $book->sellerUsername,
            'available' => $book->available
        ];
    }

    /**
     * Read an image file and convert it to base64 data URI.
     *
     * @param string $path Path to the image file
     * @return string Base64 data URI or empty string if file not found
     */
    private function getImageAsBase64(string $path): string
    {
        if (empty($path) || !file_exists($path)) {
            return '';
        }

        $mimeType = mime_content_type($path);
        $data = file_get_contents($path);

        return 'data:' . $mimeType . ';base64,' . base64_encode($data);
    }
}

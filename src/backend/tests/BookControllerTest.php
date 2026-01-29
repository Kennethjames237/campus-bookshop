<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Controllers\BookController;
use App\Services\ImageUploadService;
use App\Interfaces\DatabaseInterface;
use App\Entities\Book;

/**
 * Unit tests for BookController.
 */
class BookControllerTest extends TestCase
{
    private ImageUploadService $imageUploadService;

    protected function setUp(): void
    {
        $this->imageUploadService = $this->createMock(ImageUploadService::class);
    }

    /**
     * Create a mock DatabaseInterface with book methods.
     */
    private function createMockDatabase(
        array $books = [],
        int $insertReturns = 1,
        bool $updateReturns = true,
        bool $deleteReturns = true
    ): DatabaseInterface {
        $mock = $this->createMock(DatabaseInterface::class);

        $mock->method('getAllBooks')
            ->willReturn($books);

        $mock->method('insertBook')
            ->willReturn($insertReturns);

        $mock->method('updateBook')
            ->willReturn($updateReturns);

        $mock->method('deleteBook')
            ->willReturn($deleteReturns);

        return $mock;
    }

    /**
     * Create sample books for testing.
     */
    private function createSampleBooks(): array
    {
        return [
            new Book(
                id: 1,
                name: 'Ingegneria del Software',
                author: 'Ian Sommerville',
                isbn: '9788871926284',
                imagePath: 'uploads/test1.jpg',
                teacher: 'Prof. Bagnara',
                course: 'Informatica',
                price: 35.00,
                sellerId: 10,
                available: true
            ),
            new Book(
                id: 2,
                name: 'Sistemi Operativi',
                author: 'Silberschatz',
                isbn: '9781122334455',
                imagePath: '',
                teacher: 'Prof. Rossi',
                course: 'Informatica',
                price: 28.00,
                sellerId: 20,
                available: true
            ),
        ];
    }

    // ========================================================================
    // List Tests
    // ========================================================================

    public function testListReturnsAllBooksWhenNotAuthenticated(): void
    {
        $books = $this->createSampleBooks();
        $database = $this->createMockDatabase(books: $books);
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->list(null);

        $this->assertEquals('success', $result['status']);
        $this->assertCount(2, $result['data']);
    }

    public function testListExcludesOwnBooksWhenAuthenticated(): void
    {
        $books = $this->createSampleBooks();
        $database = $this->createMockDatabase(books: $books);
        $controller = new BookController($database, $this->imageUploadService);

        // User with ID 10 should not see their own book
        $result = $controller->list(10);

        $this->assertEquals('success', $result['status']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals(2, $result['data'][0]['id']);
    }

    public function testListReturnsEmptyArrayWhenNoBooks(): void
    {
        $database = $this->createMockDatabase(books: []);
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->list(null);

        $this->assertEquals('success', $result['status']);
        $this->assertEmpty($result['data']);
    }

    public function testListReturnsCorrectBookStructure(): void
    {
        $books = $this->createSampleBooks();
        $database = $this->createMockDatabase(books: $books);
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->list(null);

        $this->assertArrayHasKey('id', $result['data'][0]);
        $this->assertArrayHasKey('name', $result['data'][0]);
        $this->assertArrayHasKey('author', $result['data'][0]);
        $this->assertArrayHasKey('isbn', $result['data'][0]);
        $this->assertArrayHasKey('imagePath', $result['data'][0]);
        $this->assertArrayHasKey('teacher', $result['data'][0]);
        $this->assertArrayHasKey('course', $result['data'][0]);
        $this->assertArrayHasKey('price', $result['data'][0]);
        $this->assertArrayHasKey('sellerId', $result['data'][0]);
        $this->assertArrayHasKey('available', $result['data'][0]);
    }

    // ========================================================================
    // Create Tests
    // ========================================================================

    public function testCreateBookSuccessfully(): void
    {
        $database = $this->createMockDatabase(insertReturns: 105);
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->create([
            'name' => 'Sistemi Operativi',
            'author' => 'Silberschatz',
            'isbn' => '978-1122334455',
            'price' => 28.00
        ], 1);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(105, $result['id']);
        $this->assertEquals('Libro messo in vendita con successo', $result['message']);
    }

    public function testCreateBookWithOptionalFields(): void
    {
        $database = $this->createMockDatabase(insertReturns: 106);
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->create([
            'name' => 'Sistemi Operativi',
            'author' => 'Silberschatz',
            'isbn' => '9781122334455',
            'price' => 28.00,
            'teacher' => 'Prof. Bagnara',
            'course' => 'Informatica'
        ], 1);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals(106, $result['id']);
    }

    public function testCreateBookFailsWithMissingName(): void
    {
        $database = $this->createMockDatabase();
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->create([
            'author' => 'Silberschatz',
            'isbn' => '9781122334455',
            'price' => 28.00
        ], 1);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
    }

    public function testCreateBookFailsWithMissingAuthor(): void
    {
        $database = $this->createMockDatabase();
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->create([
            'name' => 'Sistemi Operativi',
            'isbn' => '9781122334455',
            'price' => 28.00
        ], 1);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
    }

    public function testCreateBookFailsWithMissingIsbn(): void
    {
        $database = $this->createMockDatabase();
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->create([
            'name' => 'Sistemi Operativi',
            'author' => 'Silberschatz',
            'price' => 28.00
        ], 1);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
    }

    public function testCreateBookFailsWithMissingPrice(): void
    {
        $database = $this->createMockDatabase();
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->create([
            'name' => 'Sistemi Operativi',
            'author' => 'Silberschatz',
            'isbn' => '9781122334455'
        ], 1);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
    }

    public function testCreateBookFailsWithInvalidIsbn(): void
    {
        $database = $this->createMockDatabase();
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->create([
            'name' => 'Sistemi Operativi',
            'author' => 'Silberschatz',
            'isbn' => '12345', // Invalid - not 10 or 13 digits
            'price' => 28.00
        ], 1);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
    }

    public function testCreateBookFailsWithZeroPrice(): void
    {
        $database = $this->createMockDatabase();
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->create([
            'name' => 'Sistemi Operativi',
            'author' => 'Silberschatz',
            'isbn' => '9781122334455',
            'price' => 0
        ], 1);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
    }

    public function testCreateBookFailsWithNegativePrice(): void
    {
        $database = $this->createMockDatabase();
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->create([
            'name' => 'Sistemi Operativi',
            'author' => 'Silberschatz',
            'isbn' => '9781122334455',
            'price' => -10.00
        ], 1);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
    }

    public function testCreateBookAcceptsIsbnWithHyphens(): void
    {
        $database = $this->createMockDatabase(insertReturns: 107);
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->create([
            'name' => 'Sistemi Operativi',
            'author' => 'Silberschatz',
            'isbn' => '978-1-122-33445-5',
            'price' => 28.00
        ], 1);

        $this->assertEquals('success', $result['status']);
    }

    public function testCreateBookWith10DigitIsbn(): void
    {
        $database = $this->createMockDatabase(insertReturns: 108);
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->create([
            'name' => 'Sistemi Operativi',
            'author' => 'Silberschatz',
            'isbn' => '1234567890',
            'price' => 28.00
        ], 1);

        $this->assertEquals('success', $result['status']);
    }

    public function testCreateBookWithImageUpload(): void
    {
        $database = $this->createMockDatabase(insertReturns: 109);
        
        $imageUploadService = $this->createMock(ImageUploadService::class);
        $imageUploadService->method('uploadBase64')
            ->willReturn(['success' => true, 'path' => 'uploads/test.jpg']);
        
        $controller = new BookController($database, $imageUploadService);

        $result = $controller->create([
            'name' => 'Sistemi Operativi',
            'author' => 'Silberschatz',
            'isbn' => '9781122334455',
            'price' => 28.00,
            'image' => 'data:image/jpeg;base64,/9j/4AAQSkZJRg...'
        ], 1);

        $this->assertEquals('success', $result['status']);
    }

    public function testCreateBookFailsWithInvalidImageFormat(): void
    {
        $database = $this->createMockDatabase();
        
        $imageUploadService = $this->createMock(ImageUploadService::class);
        $imageUploadService->method('uploadBase64')
            ->willReturn(['success' => false, 'error' => 'Invalid file format']);
        
        $controller = new BookController($database, $imageUploadService);

        $result = $controller->create([
            'name' => 'Sistemi Operativi',
            'author' => 'Silberschatz',
            'isbn' => '9781122334455',
            'price' => 28.00,
            'image' => 'data:image/gif;base64,...'
        ], 1);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid file format', $result['message']);
    }

    public function testCreateBookFailsWithFileTooLarge(): void
    {
        $database = $this->createMockDatabase();
        
        $imageUploadService = $this->createMock(ImageUploadService::class);
        $imageUploadService->method('uploadBase64')
            ->willReturn(['success' => false, 'error' => 'File too large']);
        
        $controller = new BookController($database, $imageUploadService);

        $result = $controller->create([
            'name' => 'Sistemi Operativi',
            'author' => 'Silberschatz',
            'isbn' => '9781122334455',
            'price' => 28.00,
            'image' => 'data:image/jpeg;base64,...huge-image...'
        ], 1);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('File too large', $result['message']);
    }

    // ========================================================================
    // Update Tests
    // ========================================================================

    public function testUpdateBookSuccessfully(): void
    {
        $books = $this->createSampleBooks();
        $database = $this->createMockDatabase(books: $books, updateReturns: true);
        $controller = new BookController($database, $this->imageUploadService);

        // User 10 owns book 1
        $result = $controller->update([
            'id' => 1,
            'price' => 25.00
        ], 10);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('Book updated', $result['message']);
    }

    public function testUpdateBookFailsWithMissingId(): void
    {
        $database = $this->createMockDatabase();
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->update([
            'price' => 25.00
        ], 10);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
    }

    public function testUpdateBookFailsWithInvalidId(): void
    {
        $database = $this->createMockDatabase();
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->update([
            'id' => 'not-a-number',
            'price' => 25.00
        ], 10);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
    }

    public function testUpdateBookFailsWhenBookNotFound(): void
    {
        $database = $this->createMockDatabase(books: []);
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->update([
            'id' => 999,
            'price' => 25.00
        ], 10);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Book not found', $result['message']);
    }

    public function testUpdateBookFailsWhenNotOwner(): void
    {
        $books = $this->createSampleBooks();
        $database = $this->createMockDatabase(books: $books);
        $controller = new BookController($database, $this->imageUploadService);

        // User 20 trying to update book owned by user 10
        $result = $controller->update([
            'id' => 1,
            'price' => 25.00
        ], 20);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Forbidden', $result['message']);
    }

    public function testUpdateBookPartialUpdate(): void
    {
        $books = $this->createSampleBooks();
        $database = $this->createMockDatabase(books: $books, updateReturns: true);
        $controller = new BookController($database, $this->imageUploadService);

        // Only updating available field
        $result = $controller->update([
            'id' => 1,
            'available' => false
        ], 10);

        $this->assertEquals('success', $result['status']);
    }

    public function testUpdateBookFailsWithInvalidPrice(): void
    {
        $books = $this->createSampleBooks();
        $database = $this->createMockDatabase(books: $books);
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->update([
            'id' => 1,
            'price' => -5.00
        ], 10);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
    }

    public function testUpdateBookFailsWithZeroPrice(): void
    {
        $books = $this->createSampleBooks();
        $database = $this->createMockDatabase(books: $books);
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->update([
            'id' => 1,
            'price' => 0
        ], 10);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
    }

    public function testUpdateBookFailsWithInvalidIsbn(): void
    {
        $books = $this->createSampleBooks();
        $database = $this->createMockDatabase(books: $books);
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->update([
            'id' => 1,
            'isbn' => '12345' // Invalid
        ], 10);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
    }

    public function testUpdateBookWithNewImage(): void
    {
        $books = $this->createSampleBooks();
        $database = $this->createMockDatabase(books: $books, updateReturns: true);
        
        $imageUploadService = $this->createMock(ImageUploadService::class);
        $imageUploadService->method('uploadBase64')
            ->willReturn(['success' => true, 'path' => 'uploads/new.jpg']);
        $imageUploadService->method('delete')
            ->willReturn(true);
        
        $controller = new BookController($database, $imageUploadService);

        $result = $controller->update([
            'id' => 1,
            'image' => 'data:image/jpeg;base64,...'
        ], 10);

        $this->assertEquals('success', $result['status']);
    }

    // ========================================================================
    // Delete Tests
    // ========================================================================

    public function testDeleteBookSuccessfully(): void
    {
        $books = $this->createSampleBooks();
        $database = $this->createMockDatabase(books: $books, deleteReturns: true);
        
        $imageUploadService = $this->createMock(ImageUploadService::class);
        $imageUploadService->method('delete')->willReturn(true);
        
        $controller = new BookController($database, $imageUploadService);

        // User 10 owns book 1
        $result = $controller->delete(['id' => 1], 10);

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('Book deleted', $result['message']);
    }

    public function testDeleteBookFailsWithMissingId(): void
    {
        $database = $this->createMockDatabase();
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->delete([], 10);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
    }

    public function testDeleteBookFailsWithInvalidId(): void
    {
        $database = $this->createMockDatabase();
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->delete(['id' => 'invalid'], 10);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
    }

    public function testDeleteBookFailsWhenBookNotFound(): void
    {
        $database = $this->createMockDatabase(books: []);
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->delete(['id' => 999], 10);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Book not found', $result['message']);
    }

    public function testDeleteBookFailsWhenNotOwner(): void
    {
        $books = $this->createSampleBooks();
        $database = $this->createMockDatabase(books: $books);
        $controller = new BookController($database, $this->imageUploadService);

        // User 20 trying to delete book owned by user 10
        $result = $controller->delete(['id' => 1], 20);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Forbidden', $result['message']);
    }

    // ========================================================================
    // ISBN Validation Tests
    // ========================================================================

    public function testIsbnValidation10Digits(): void
    {
        $database = $this->createMockDatabase(insertReturns: 1);
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->create([
            'name' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => '1234567890',
            'price' => 10.00
        ], 1);

        $this->assertEquals('success', $result['status']);
    }

    public function testIsbnValidation13Digits(): void
    {
        $database = $this->createMockDatabase(insertReturns: 1);
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->create([
            'name' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => '1234567890123',
            'price' => 10.00
        ], 1);

        $this->assertEquals('success', $result['status']);
    }

    public function testIsbnValidationFailsWithWrongLength(): void
    {
        $database = $this->createMockDatabase();
        $controller = new BookController($database, $this->imageUploadService);

        // 11 digits - invalid
        $result = $controller->create([
            'name' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => '12345678901',
            'price' => 10.00
        ], 1);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
    }

    public function testIsbnValidationFailsWithLetters(): void
    {
        $database = $this->createMockDatabase();
        $controller = new BookController($database, $this->imageUploadService);

        $result = $controller->create([
            'name' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => '123456789X',
            'price' => 10.00
        ], 1);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Invalid input', $result['message']);
    }
}

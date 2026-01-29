<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Controllers\PurchaseController;
use App\Interfaces\DatabaseInterface;
use App\Entities\Book;
use App\Entities\User;

/**
 * Unit tests for PurchaseController.
 */
class PurchaseControllerTest extends TestCase
{
    /**
     * Create a mock DatabaseInterface with configurable behavior.
     */
    private function createMockDatabase(
        array $books = [],
        ?User $user = null,
        int $placeOrderReturns = 1,
        array $purchasesReturns = [],
        array $salesReturns = []
    ): DatabaseInterface {
        $mock = $this->createMock(DatabaseInterface::class);

        $mock->method('getAllBooks')
            ->willReturn($books);

        $mock->method('getUserById')
            ->willReturn($user);

        $mock->method('placeOrder')
            ->willReturn($placeOrderReturns);

        $mock->method('getPurchasesByBuyer')
            ->willReturn($purchasesReturns);

        $mock->method('getSalesBySeller')
            ->willReturn($salesReturns);

        return $mock;
    }

    /**
     * Create a Book entity for testing.
     */
    private function createBook(
        int $id = 1,
        int $sellerId = 2,
        bool $available = true
    ): Book {
        return new Book(
            id: $id,
            name: 'Test Book',
            author: 'Test Author',
            isbn: '1234567890',
            imagePath: '',
            teacher: 'Test Teacher',
            course: 'Test Course',
            price: 25.00,
            sellerId: $sellerId,
            available: $available
        );
    }

    /**
     * Create a User entity for testing.
     */
    private function createUser(
        int $id = 2,
        string $email = 'seller@test.com'
    ): User {
        return new User(
            id: $id,
            username: 'seller',
            email: $email,
            password: 'hashed'
        );
    }

    // ========================================================================
    // Purchase Tests
    // ========================================================================

    public function testPurchaseSuccessfully(): void
    {
        $book = $this->createBook(id: 1, sellerId: 2, available: true);
        $seller = $this->createUser(id: 2, email: 'seller@test.com');

        $database = $this->createMockDatabase(
            books: [$book],
            user: $seller,
            placeOrderReturns: 100
        );

        $controller = new PurchaseController($database);
        $result = $controller->purchase(['bookId' => 1], 5); // buyerId = 5

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('Purchase completed successfully', $result['message']);
        $this->assertEquals(100, $result['orderId']);
        $this->assertEquals('seller@test.com', $result['sellerEmail']);
    }

    public function testPurchaseFailsBookNotFoundMissingBookId(): void
    {
        $database = $this->createMockDatabase(books: []);

        $controller = new PurchaseController($database);
        $result = $controller->purchase([], 5); // empty data

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Book not found', $result['message']);
    }

    public function testPurchaseFailsBookNotFoundInvalidId(): void
    {
        $book = $this->createBook(id: 1, sellerId: 2, available: true);
        $database = $this->createMockDatabase(books: [$book]);

        $controller = new PurchaseController($database);
        $result = $controller->purchase(['bookId' => 999], 5); // non-existent id

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Book not found', $result['message']);
    }

    public function testPurchaseFailsBookNotFoundEmptyDatabase(): void
    {
        $database = $this->createMockDatabase(books: []);

        $controller = new PurchaseController($database);
        $result = $controller->purchase(['bookId' => 1], 5);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Book not found', $result['message']);
    }

    public function testPurchaseFailsBookAlreadySold(): void
    {
        $book = $this->createBook(id: 1, sellerId: 2, available: false);
        $database = $this->createMockDatabase(books: [$book]);

        $controller = new PurchaseController($database);
        $result = $controller->purchase(['bookId' => 1], 5);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Book already sold', $result['message']);
    }

    public function testPurchaseFailsSelfPurchase(): void
    {
        $book = $this->createBook(id: 1, sellerId: 5, available: true);
        $database = $this->createMockDatabase(books: [$book]);

        $controller = new PurchaseController($database);
        $result = $controller->purchase(['bookId' => 1], 5); // buyerId == sellerId

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Cannot purchase your own book', $result['message']);
    }

    public function testPurchaseHandlesPlaceOrderException(): void
    {
        $book = $this->createBook(id: 1, sellerId: 2, available: true);

        $mock = $this->createMock(DatabaseInterface::class);
        $mock->method('getAllBooks')->willReturn([$book]);
        $mock->method('placeOrder')->willThrowException(new \Exception('DB error'));

        $controller = new PurchaseController($mock);
        $result = $controller->purchase(['bookId' => 1], 5);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Server error', $result['message']);
    }

    // ========================================================================
    // List Purchases Tests
    // ========================================================================

    public function testListPurchasesSuccessfully(): void
    {
        $purchaseData = [
            [
                'orderId' => 501,
                'book' => [
                    'id' => 78,
                    'name' => 'Ingegneria del Software',
                    'author' => 'Ian Sommerville',
                    'price' => 35.00
                ],
                'sellerUsername' => 'mario_rossi',
                'purchaseDate' => '2026-01-25T14:30:00Z'
            ]
        ];

        $database = $this->createMockDatabase(purchasesReturns: $purchaseData);

        $controller = new PurchaseController($database);
        $result = $controller->listPurchases(5);

        $this->assertEquals('success', $result['status']);
        $this->assertIsArray($result['data']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals(501, $result['data'][0]['orderId']);
    }

    public function testListPurchasesEmpty(): void
    {
        $database = $this->createMockDatabase(purchasesReturns: []);

        $controller = new PurchaseController($database);
        $result = $controller->listPurchases(5);

        $this->assertEquals('success', $result['status']);
        $this->assertIsArray($result['data']);
        $this->assertCount(0, $result['data']);
    }

    public function testListPurchasesHandlesException(): void
    {
        $mock = $this->createMock(DatabaseInterface::class);
        $mock->method('getPurchasesByBuyer')->willThrowException(new \Exception('DB error'));

        $controller = new PurchaseController($mock);
        $result = $controller->listPurchases(5);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Server error', $result['message']);
    }

    // ========================================================================
    // List Sales Tests
    // ========================================================================

    public function testListSalesSuccessfully(): void
    {
        $salesData = [
            [
                'orderId' => 501,
                'book' => [
                    'id' => 78,
                    'name' => 'Ingegneria del Software',
                    'author' => 'Ian Sommerville',
                    'price' => 35.00
                ],
                'buyerUsername' => 'luigi_verdi',
                'saleDate' => '2026-01-25T14:30:00Z'
            ]
        ];

        $database = $this->createMockDatabase(salesReturns: $salesData);

        $controller = new PurchaseController($database);
        $result = $controller->listSales(2);

        $this->assertEquals('success', $result['status']);
        $this->assertIsArray($result['data']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals(501, $result['data'][0]['orderId']);
    }

    public function testListSalesEmpty(): void
    {
        $database = $this->createMockDatabase(salesReturns: []);

        $controller = new PurchaseController($database);
        $result = $controller->listSales(2);

        $this->assertEquals('success', $result['status']);
        $this->assertIsArray($result['data']);
        $this->assertCount(0, $result['data']);
    }

    public function testListSalesHandlesException(): void
    {
        $mock = $this->createMock(DatabaseInterface::class);
        $mock->method('getSalesBySeller')->willThrowException(new \Exception('DB error'));

        $controller = new PurchaseController($mock);
        $result = $controller->listSales(2);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Server error', $result['message']);
    }
}

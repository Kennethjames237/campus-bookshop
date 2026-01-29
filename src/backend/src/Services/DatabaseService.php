<?php

namespace App\Services;

use App\Entities\User;
use App\Entities\Book;
use App\Entities\Message;
use App\Entities\Transaction;
use App\Interfaces\DatabaseInterface;
use PDO;
use PDOException;

class DatabaseService implements DatabaseInterface
{
    private PDO $connection;

    public function __construct()
    {
        $host = getenv('DB_HOST');
        $db = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASS');
        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

        try {
            $this->connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => true,
            ]);
        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }
    // ========================================================================
    // User Management Methods
    // ========================================================================

    /**
     * Register a new user in the database.
     *
     * @param User $user The user entity to register (password should be pre-hashed)
     * @return bool True if registration successful, false if email already exists
     */
    public function registerUser(User $user): bool
    {
        $sql = "INSERT INTO users (username, email, password) VALUES (:u, :e, :p)";
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute([
                'u' => $user->username,
                'e' => $user->email,
                'p' => $user->password
            ]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Verify user credentials.
     *
     * @param string $email User's email
     * @param string $password User's plain text password (will be verified with password_verify)
     * @return int|null User ID if credentials are valid, null otherwise
     */
    public function verifyCredentials(string $email, string $password): ?int
    {
        $sql = "SELECT id, password FROM users WHERE email = :email";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }
        if (password_verify($password, $row['password'])) {
            return (int) $row['id'];
        }
        return null;
    }

    /**
     * Get a user by their ID.
     *
     * @param int $id User ID
     * @return User|null User entity or null if not found
     */
    public function getUserById(int $id): ?User
    {
        $stmt = $this->connection->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row)
            return null;

        $user = new \App\Entities\User();
        $user->id = (int) $row['id'];
        $user->username = $row['username'];
        $user->email = $row['email'];
        $user->password = $row['password'];

        return $user;
    }

    // ========================================================================
    // Book Management Methods
    // ========================================================================

    /**
     * Get all available books.
     *
     * @return array Array of Book entities
     */
    public function getAllBooks(): array
    {
        $sql = "SELECT b.*, u.username AS seller_username 
            FROM books b
            JOIN users u ON b.seller_id = u.id 
            ORDER BY b.id DESC";
        $stmt = $this->connection->query($sql);
        $books = [];
        while ($row = $stmt->fetch()) {
            $books[] = new Book(
                (int) $row['id'],
                $row['name'],
                $row['author'],
                $row['isbn'],
                $row['image_path'],
                $row['teacher'],
                $row['course'],
                (float) $row['price'],
                (int) $row['seller_id'],
                $row['seller_username'],
                (bool) $row['available']
            );
        }
        return $books;
    }
    /**
     * Insert a new book into the database.
     *
     * @param Book $book The book entity to insert
     * @return int The ID of the newly inserted book
     */
    public function insertBook(Book $book): int
    {
        $sql = "INSERT INTO books (name, author, isbn, price, image_path, teacher, course, seller_id)
                VALUES (:name, :author, :isbn, :price, :img, :teach, :course, :seller)";
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute([
            'name' => $book->name,
            'author' => $book->author,
            'isbn' => $book->isbn,
            'price' => $book->price,
            'img' => $book->imagePath,
            'teach' => $book->teacher,
            'course' => $book->course,
            'seller' => $book->sellerId
        ]);
    }

    /**
     * Update an existing book.
     *
     * @param Book $book The book entity with updated values (id must be set)
     * @return bool True if update successful, false otherwise
     */
    public function updateBook(Book $book): bool
    {
        if (!$book->id)
            return false; // Serve l'ID per fare update

        $sql = "UPDATE books SET 
                name = :name, author = :author, isbn = :isbn, price = :price, 
                image_path = :img, teacher = :teach, course = :course, available = :avail 
                WHERE id = :id";

        $stmt = $this->connection->prepare($sql);
        return $stmt->execute([
            'id' => $book->id,
            'name' => $book->name,
            'author' => $book->author,
            'isbn' => $book->isbn,
            'price' => $book->price,
            'img' => $book->imagePath,
            'teach' => $book->teacher,
            'course' => $book->course,
            'avail' => (int) $book->available
        ]);
    }

    /**
     * Delete a book from the database.
     *
     * @param int $id The ID of the book to delete
     * @return bool True if deletion successful, false otherwise
     */
    public function deleteBook(int $id): bool
    {
        $sql = "DELETE FROM books WHERE id = :id";
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

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
    public function placeOrder(int $bookId, int $buyerId): int
    {
        try {
            $this->connection->beginTransaction();
            $stmt = $this->connection->prepare("
                SELECT price, seller_id, available 
                FROM books 
                WHERE id = :id 
                FOR UPDATE
            ");
            $stmt->execute(['id' => $bookId]);
            $book = $stmt->fetch();
            if (!$book)
                throw new \Exception("Libro non trovato.");
            if ($book['available'] == 0)
                throw new \Exception("Libro già venduto.");
            if ($book['seller_id'] == $buyerId)
                throw new \Exception("Non puoi acquistare il tuo stesso libro.");
            $sqlInsert = "
                INSERT INTO transactions (book_id, buyer_id, seller_id, price, created_at) 
                VALUES (:bid, :buy, :sel, :price, NOW())
            ";
            $stmtInsert = $this->connection->prepare($sqlInsert);
            $stmtInsert->execute([
                'bid' => $bookId,
                'buy' => $buyerId,
                'sel' => $book['seller_id'],
                'price' => $book['price']
            ]);

            $transactionId = (int) $this->connection->lastInsertId();

            $sqlUpdate = "UPDATE books SET available = 0 WHERE id = :id";
            $stmtUpdate = $this->connection->prepare($sqlUpdate);
            $stmtUpdate->execute(['id' => $bookId]);
            $this->connection->commit();
            return $transactionId;

        } catch (\Exception $e) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Get all purchases made by a buyer.
     *
     * @param int $buyerId The buyer's user ID
     * @return array Array of purchase data with book and seller info
     */
    public function getPurchasesByBuyer(int $buyerId): array
    {
        $sql = "
            SELECT 
                t.id as orderId, 
                t.created_at as purchaseDate,
                b.id as bookId, 
                b.name as bookName, 
                b.author as bookAuthor, 
                b.price as bookPrice, 
                b.image_path as bookImage,
                b.seller_id as bookSellerId,
                u.username as sellerUsername
            FROM transactions t
            JOIN books b ON t.book_id = b.id
            JOIN users u ON t.seller_id = u.id
            WHERE t.buyer_id = :uid 
            ORDER BY t.created_at DESC
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['uid' => $buyerId]);

        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = [
                'orderId' => (int) $row['orderId'],
                'book' => [
                    'id' => (int) $row['bookId'],
                    'name' => $row['bookName'],
                    'author' => $row['bookAuthor'],
                    'price' => (float) $row['bookPrice'],
                    'imagePath' => $row['bookImage'],
                    'sellerId' => (int) $row['bookSellerId']
                ],
                'sellerUsername' => $row['sellerUsername'],
                'purchaseDate' => $row['purchaseDate']
            ];
        }
        return $results;
    }

    /**
     * Get all sales made by a seller.
     *
     * @param int $sellerId The seller's user ID
     * @return array Array of sale data with book and buyer info
     */
    public function getSalesBySeller(int $sellerId): array
    {
        $sql = "
            SELECT 
                t.id as orderId, 
                t.created_at as saleDate,
                b.id as bookId, 
                b.name as bookName, 
                b.author as bookAuthor, 
                b.price as bookPrice, 
                b.image_path as bookImage,
                u.username as buyerUsername
            FROM transactions t
            JOIN books b ON t.book_id = b.id
            JOIN users u ON t.buyer_id = u.id
            WHERE t.seller_id = :uid 
            ORDER BY t.created_at DESC
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['uid' => $sellerId]);

        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = [
                'orderId' => (int) $row['orderId'],
                'book' => [
                    'id' => (int) $row['bookId'],
                    'name' => $row['bookName'],
                    'author' => $row['bookAuthor'],
                    'price' => (float) $row['bookPrice'],
                    'imagePath' => $row['bookImage']
                ],
                'buyerUsername' => $row['buyerUsername'],
                'saleDate' => $row['saleDate']
            ];
        }
        return $results;
    }

    private function mapTransactions(array $rows): array
    {
        $results = [];
        foreach ($rows as $row) {
            $results[] = new Transaction(
                (int) $row['id'],
                (int) $row['book_id'],
                (int) $row['buyer_id'],
                (int) $row['seller_id'],
                (float) $row['price'],
                new \DateTime($row['created_at'])
            );
        }
        return $results;
    }

    // ========================================================================
    // Message Management Methods
    // ========================================================================

    /**
     * Send a message from one user to another.
     *
     * @param Message $message The message entity to insert
     * @return int The ID of the newly inserted message
     */
    public function sendMessage(Message $message): int
    {
        $sql = "INSERT INTO messages (sender_id, receiver_id, content, created_at) 
                VALUES (:s, :r, :c, NOW())";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            's' => $message->senderId,
            'r' => $message->receiverId,
            'c' => $message->content
        ]);

        return (int) $this->connection->lastInsertId();
    }

    /**
     * Get all messages between two users.
     * Returns messages ordered chronologically.
     *
     * @param int $userId1 First user's ID
     * @param int $userId2 Second user's ID
     * @return array Array of Message entities
     */
    public function getMessages(int $userId1, int $userId2): array
    {
        $sql = "SELECT * FROM messages 
                WHERE (sender_id = :u1 AND receiver_id = :u2) 
                   OR (sender_id = :u2 AND receiver_id = :u1)
                ORDER BY created_at ASC";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            'u1' => $userId1,
            'u2' => $userId2
        ]);

        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = new Message(
                (int) $row['id'],
                (int) $row['sender_id'],
                (int) $row['receiver_id'],
                $row['content'],
                new \DateTime($row['created_at'])
            );
        }
        return $results;
    }

    /**
     * Get all conversations for a user.
     * Returns a list of users the current user has exchanged messages with,
     * along with the last message preview and timestamp.
     *
     * @param int $userId The user's ID
     * @return array Array of conversation data with user info and last message
     */
    public function getConversations(int $userId): array
    {
        $sql = "
        SELECT 
            u.id AS userId,
            u.username,
            m.content AS lastMessage,
            m.created_at AS lastMessageDate,
            m.sender_id AS lastSenderId  /* <--- 1. ECCO IL PEZZO MANCANTE */
        FROM messages m
        JOIN users u ON (
            CASE 
                WHEN m.sender_id = :uid THEN m.receiver_id 
                ELSE m.sender_id 
            END = u.id
        )
        WHERE m.id IN (
            SELECT MAX(id)
            FROM messages
            WHERE sender_id = :uid OR receiver_id = :uid
            GROUP BY 
                CASE 
                    WHEN sender_id = :uid THEN receiver_id 
                    ELSE sender_id 
                END
        )
        ORDER BY m.created_at DESC
    ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['uid' => $userId]);

        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = (object) [
                'userId' => (int) $row['userId'],
                'username' => $row['username'],
                'lastMessage' => $row['lastMessage'],
                'lastSenderId' => (int) $row['lastSenderId'],
                'lastMessageDate' => new \DateTime($row['lastMessageDate'])
            ];
        }

        return $results;
    }
}

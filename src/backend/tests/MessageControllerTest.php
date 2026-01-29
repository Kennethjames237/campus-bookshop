<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Controllers\MessageController;
use App\Interfaces\DatabaseInterface;
use App\Entities\Message;
use App\Entities\User;
use DateTime;

/**
 * Unit tests for MessageController.
 */
class MessageControllerTest extends TestCase
{
    /**
     * Create a mock DatabaseInterface with configurable behavior.
     */
    private function createMockDatabase(
        ?User $user = null,
        array $conversationsReturns = [],
        array $messagesReturns = [],
        int $sendMessageReturns = 1
    ): DatabaseInterface {
        $mock = $this->createMock(DatabaseInterface::class);

        $mock->method('getUserById')
            ->willReturn($user);

        $mock->method('getConversations')
            ->willReturn($conversationsReturns);

        $mock->method('getMessages')
            ->willReturn($messagesReturns);

        $mock->method('sendMessage')
            ->willReturn($sendMessageReturns);

        return $mock;
    }

    /**
     * Create a User entity for testing.
     */
    private function createUser(
        int $id = 2,
        string $username = 'test_user',
        string $email = 'test@test.com'
    ): User {
        return new User(
            id: $id,
            username: $username,
            email: $email,
            password: 'hashed'
        );
    }

    /**
     * Create a Message entity for testing.
     */
    private function createMessage(
        int $id = 1,
        int $senderId = 5,
        int $receiverId = 10,
        string $content = 'Test message',
        ?DateTime $createdAt = null
    ): Message {
        return new Message(
            id: $id,
            senderId: $senderId,
            receiverId: $receiverId,
            content: $content,
            createdAt: $createdAt ?? new DateTime('2026-01-25T14:00:00Z')
        );
    }

    // ========================================================================
    // List Conversations Tests
    // ========================================================================

    public function testListConversationsSuccessfully(): void
    {
        $conversationData = [
            [
                'userId' => 5,
                'username' => 'luigi_verdi',
                'lastMessage' => 'Ok, ci vediamo domani',
                'lastMessageDate' => '2026-01-25T14:30:00Z'
            ]
        ];

        $database = $this->createMockDatabase(conversationsReturns: $conversationData);

        $controller = new MessageController($database);
        $result = $controller->listConversations(10);

        $this->assertEquals('success', $result['status']);
        $this->assertIsArray($result['data']);
        $this->assertCount(1, $result['data']);
        $this->assertEquals(5, $result['data'][0]['userId']);
        $this->assertEquals('luigi_verdi', $result['data'][0]['username']);
    }

    public function testListConversationsEmpty(): void
    {
        $database = $this->createMockDatabase(conversationsReturns: []);

        $controller = new MessageController($database);
        $result = $controller->listConversations(10);

        $this->assertEquals('success', $result['status']);
        $this->assertIsArray($result['data']);
        $this->assertCount(0, $result['data']);
    }

    public function testListConversationsHandlesException(): void
    {
        $mock = $this->createMock(DatabaseInterface::class);
        $mock->method('getConversations')->willThrowException(new \Exception('DB error'));

        $controller = new MessageController($mock);
        $result = $controller->listConversations(10);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Server error', $result['message']);
    }

    // ========================================================================
    // Get Messages Tests
    // ========================================================================

    public function testGetMessagesSuccessfully(): void
    {
        $user = $this->createUser(id: 5);
        $messages = [
            $this->createMessage(id: 1, senderId: 5, receiverId: 10, content: 'Ciao!'),
            $this->createMessage(id: 2, senderId: 10, receiverId: 5, content: 'Ciao, come stai?')
        ];

        $database = $this->createMockDatabase(user: $user, messagesReturns: $messages);

        $controller = new MessageController($database);
        $result = $controller->getMessages(10, 5);

        $this->assertEquals('success', $result['status']);
        $this->assertIsArray($result['data']);
        $this->assertCount(2, $result['data']);
        $this->assertEquals(1, $result['data'][0]['id']);
        $this->assertEquals(5, $result['data'][0]['senderId']);
        $this->assertEquals(10, $result['data'][0]['receiverId']);
        $this->assertEquals('Ciao!', $result['data'][0]['content']);
        $this->assertArrayHasKey('createdAt', $result['data'][0]);
    }

    public function testGetMessagesEmpty(): void
    {
        $user = $this->createUser(id: 5);
        $database = $this->createMockDatabase(user: $user, messagesReturns: []);

        $controller = new MessageController($database);
        $result = $controller->getMessages(10, 5);

        $this->assertEquals('success', $result['status']);
        $this->assertIsArray($result['data']);
        $this->assertCount(0, $result['data']);
    }

    public function testGetMessagesFailsUserNotFoundNullUserId(): void
    {
        $database = $this->createMockDatabase();

        $controller = new MessageController($database);
        $result = $controller->getMessages(10, null);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('User not found', $result['message']);
    }

    public function testGetMessagesFailsUserNotFoundInvalidUserId(): void
    {
        $database = $this->createMockDatabase();

        $controller = new MessageController($database);
        $result = $controller->getMessages(10, 0);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('User not found', $result['message']);
    }

    public function testGetMessagesFailsUserNotFoundNonExistent(): void
    {
        $database = $this->createMockDatabase(user: null);

        $controller = new MessageController($database);
        $result = $controller->getMessages(10, 999);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('User not found', $result['message']);
    }

    public function testGetMessagesHandlesException(): void
    {
        $user = $this->createUser(id: 5);

        $mock = $this->createMock(DatabaseInterface::class);
        $mock->method('getUserById')->willReturn($user);
        $mock->method('getMessages')->willThrowException(new \Exception('DB error'));

        $controller = new MessageController($mock);
        $result = $controller->getMessages(10, 5);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Server error', $result['message']);
    }

    // ========================================================================
    // Send Message Tests
    // ========================================================================

    public function testSendMessageSuccessfully(): void
    {
        $receiver = $this->createUser(id: 5);
        $database = $this->createMockDatabase(user: $receiver, sendMessageReturns: 42);

        $controller = new MessageController($database);
        $result = $controller->sendMessage(
            ['receiverId' => 5, 'content' => 'Ciao, sono interessato al libro'],
            10
        );

        $this->assertEquals('success', $result['status']);
        $this->assertEquals('Message sent', $result['message']);
        $this->assertEquals(42, $result['messageId']);
    }

    public function testSendMessageFailsReceiverNotFoundMissingId(): void
    {
        $database = $this->createMockDatabase();

        $controller = new MessageController($database);
        $result = $controller->sendMessage(['content' => 'Test'], 10);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('User not found', $result['message']);
    }

    public function testSendMessageFailsReceiverNotFoundInvalidId(): void
    {
        $database = $this->createMockDatabase();

        $controller = new MessageController($database);
        $result = $controller->sendMessage(['receiverId' => 0, 'content' => 'Test'], 10);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('User not found', $result['message']);
    }

    public function testSendMessageFailsReceiverNotFoundNonExistent(): void
    {
        $database = $this->createMockDatabase(user: null);

        $controller = new MessageController($database);
        $result = $controller->sendMessage(['receiverId' => 999, 'content' => 'Test'], 10);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('User not found', $result['message']);
    }

    public function testSendMessageFailsContentRequiredMissing(): void
    {
        $database = $this->createMockDatabase();

        $controller = new MessageController($database);
        $result = $controller->sendMessage(['receiverId' => 5], 10);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Message content required', $result['message']);
    }

    public function testSendMessageFailsContentRequiredEmpty(): void
    {
        $database = $this->createMockDatabase();

        $controller = new MessageController($database);
        $result = $controller->sendMessage(['receiverId' => 5, 'content' => ''], 10);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Message content required', $result['message']);
    }

    public function testSendMessageFailsContentRequiredWhitespace(): void
    {
        $database = $this->createMockDatabase();

        $controller = new MessageController($database);
        $result = $controller->sendMessage(['receiverId' => 5, 'content' => '   '], 10);

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Message content required', $result['message']);
    }

    public function testSendMessageFailsCannotMessageYourself(): void
    {
        $database = $this->createMockDatabase();

        $controller = new MessageController($database);
        $result = $controller->sendMessage(
            ['receiverId' => 10, 'content' => 'Test'],
            10 // senderId == receiverId
        );

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Cannot message yourself', $result['message']);
    }

    public function testSendMessageHandlesException(): void
    {
        $receiver = $this->createUser(id: 5);

        $mock = $this->createMock(DatabaseInterface::class);
        $mock->method('getUserById')->willReturn($receiver);
        $mock->method('sendMessage')->willThrowException(new \Exception('DB error'));

        $controller = new MessageController($mock);
        $result = $controller->sendMessage(
            ['receiverId' => 5, 'content' => 'Test'],
            10
        );

        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Server error', $result['message']);
    }
}

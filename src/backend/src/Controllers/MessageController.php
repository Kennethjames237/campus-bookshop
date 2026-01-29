<?php

namespace App\Controllers;

use App\Entities\Message;
use App\Interfaces\DatabaseInterface;

/**
 * Controller for messaging operations.
 */
class MessageController
{
    private DatabaseInterface $database;

    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;
    }

    /**
     * Get list of conversations for the authenticated user.
     * GET /conversations
     *
     * @param int $userId Authenticated user ID (from JWT)
     * @return array Response with status and data
     */
    public function listConversations(int $userId): array
    {
        try {
            $data = $this->database->getConversations($userId);
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
     * Get messages exchanged with a specific user.
     * GET /messages?userId={id}
     *
     * @param int $currentUserId Authenticated user ID (from JWT)
     * @param int|null $otherUserId The other user's ID (from query param)
     * @return array Response with status and data
     */
    public function getMessages(int $currentUserId, ?int $otherUserId): array
    {
        // Validate userId parameter
        if ($otherUserId === null || $otherUserId <= 0) {
            return [
                'status' => 'error',
                'message' => 'User not found'
            ];
        }

        // Check if other user exists
        $otherUser = $this->database->getUserById($otherUserId);
        if ($otherUser === null) {
            return [
                'status' => 'error',
                'message' => 'User not found'
            ];
        }

        try {
            $messages = $this->database->getMessages($currentUserId, $otherUserId);

            // Transform Message entities to response format
            $data = array_map(function (Message $msg) {
                return [
                    'id' => $msg->id,
                    'senderId' => $msg->senderId,
                    'receiverId' => $msg->receiverId,
                    'content' => $msg->content,
                    'createdAt' => $msg->createdAt->format('Y-m-d\TH:i:s\Z')
                ];
            }, $messages);

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
     * Send a message to another user.
     * POST /messages
     *
     * @param array $data Request data containing receiverId and content
     * @param int $senderId Authenticated user ID (from JWT)
     * @return array Response with status and message/messageId
     */
    public function sendMessage(array $data, int $senderId): array
    {
        // Validate receiverId
        $receiverId = isset($data['receiverId']) ? (int)$data['receiverId'] : 0;
        if ($receiverId <= 0) {
            return [
                'status' => 'error',
                'message' => 'User not found'
            ];
        }

        // Validate content
        $content = isset($data['content']) ? trim($data['content']) : '';
        if ($content === '') {
            return [
                'status' => 'error',
                'message' => 'Message content required'
            ];
        }

        // Check not messaging yourself
        if ($receiverId === $senderId) {
            return [
                'status' => 'error',
                'message' => 'Cannot message yourself'
            ];
        }

        // Check if receiver exists
        $receiver = $this->database->getUserById($receiverId);
        if ($receiver === null) {
            return [
                'status' => 'error',
                'message' => 'User not found'
            ];
        }

        try {
            $message = new Message(
                id: null,
                senderId: $senderId,
                receiverId: $receiverId,
                content: $content
            );

            $messageId = $this->database->sendMessage($message);

            return [
                'status' => 'success',
                'message' => 'Message sent',
                'messageId' => $messageId
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Server error'
            ];
        }
    }
}

<?php

/**
 * UniprBooks API Entry Point
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use App\Controllers\AuthController;
use App\Controllers\BookController;
use App\Controllers\PurchaseController;
use App\Controllers\MessageController;
use App\Services\JWTService;
use App\Services\DatabaseService;
use App\Services\ImageUploadService;
use App\Middleware\AuthMiddleware;

// ============================================================================
// CORS and Headers Configuration
// ============================================================================

$origin_url = getenv('ALLOWED_ORIGIN') ?: '*';
header("Access-Control-Allow-Origin: {$origin_url}");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ============================================================================
// Request & Response Helpers
// ============================================================================

function getRequestBody(): array {
    $json = file_get_contents('php://input');
    return json_decode($json, true) ?? [];
}

function sendResponse(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// ============================================================================
// Routing & Service Initialization
// ============================================================================

$method = $_SERVER['REQUEST_METHOD'];
$uri = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
if (empty($uri)) $uri = '/';

// 1. Inizializziamo il DatabaseService (la connessione avviene internamente)
// Ne creiamo una sola istanza qui per tutta la durata della richiesta
try {
    $database = new DatabaseService(); 
} catch (Exception $e) {
    sendResponse(['status' => 'error', 'message' => 'Database connection failed'], 500);
}

// 2. Inizializziamo i servizi comuni
$jwtService = new JWTService();
$authMiddleware = new AuthMiddleware($jwtService);
$imageUploadService = new ImageUploadService();

// ============================================================================
// API Routes
// ============================================================================

// Health check
if ($uri === '/' && $method === 'GET') {
    sendResponse(['status' => 'success', 'message' => 'UniprBooks API is running']);
}

// Auth: Register
if ($uri === '/register' && $method === 'POST') {
    $authController = new AuthController($database, $jwtService);
    sendResponse($authController->register(getRequestBody()));
}

// Auth: Login
if ($uri === '/login' && $method === 'POST') {
    $authController = new AuthController($database, $jwtService);
    sendResponse($authController->login(getRequestBody()));
}

// Books: List (Public/Optional Auth)
if ($uri === '/books' && $method === 'GET') {
    $bookController = new BookController($database, $imageUploadService);
    
    $userId = null;
    if ($authMiddleware->handle() === null) {
        $userData = $authMiddleware->getAuthenticatedUser();
        $userId = $userData['sub'];
    }
    
    sendResponse($bookController->list($userId));
}

// My Books: List (Protected)
if ($uri === '/my-books' && $method === 'GET') {
    $bookController = new BookController($database, $imageUploadService);
    
    $userId = null;
    if ($authMiddleware->handle() === null) {
        $userData = $authMiddleware->getAuthenticatedUser();
        $userId = $userData['sub'];
    }
    
    sendResponse($bookController->list($userId, true));
}

// Books: Create (Protected)
if ($uri === '/books' && $method === 'POST') {
    if ($error = $authMiddleware->handle()) sendResponse($error, 401);
    
    $bookController = new BookController($database, $imageUploadService);
    $sellerId = $authMiddleware->getAuthenticatedUser()['sub'];
    
    sendResponse($bookController->create(getRequestBody(), $sellerId));
}

// Books: Update (Protected)
if ($uri === '/books' && $method === 'PUT') {
    if ($error = $authMiddleware->handle()) sendResponse($error, 401);
    
    $bookController = new BookController($database, $imageUploadService);
    $userId = $authMiddleware->getAuthenticatedUser()['sub'];
    
    sendResponse($bookController->update(getRequestBody(), $userId));
}

// Books: Delete (Protected)
if ($uri === '/books' && $method === 'DELETE') {
    if ($error = $authMiddleware->handle()) sendResponse($error, 401);
    
    $bookController = new BookController($database, $imageUploadService);
    $userId = $authMiddleware->getAuthenticatedUser()['sub'];
    
    sendResponse($bookController->delete(getRequestBody(), $userId));
}

// User: Me (Protected)
if ($uri === '/me' && $method === 'GET') {
    if ($error = $authMiddleware->handle()) sendResponse($error, 401);
    
    $userData = $authMiddleware->getAuthenticatedUser();
    sendResponse([
        'status' => 'success',
        'data' => ['id' => $userData['sub'], 'email' => $userData['email']]
    ]);
}

// Purchase: Create (Protected)
if ($uri === '/purchase' && $method === 'POST') {
    if ($error = $authMiddleware->handle()) sendResponse($error, 401);
    
    $purchaseController = new PurchaseController($database);
    $buyerId = $authMiddleware->getAuthenticatedUser()['sub'];
    
    sendResponse($purchaseController->purchase(getRequestBody(), $buyerId));
}

// Purchases: List (Protected)
if ($uri === '/purchases' && $method === 'GET') {
    if ($error = $authMiddleware->handle()) sendResponse($error, 401);
    
    $purchaseController = new PurchaseController($database);
    $buyerId = $authMiddleware->getAuthenticatedUser()['sub'];
    
    sendResponse($purchaseController->listPurchases($buyerId));
}

// Sales: List (Protected)
if ($uri === '/sales' && $method === 'GET') {
    if ($error = $authMiddleware->handle()) sendResponse($error, 401);
    
    $purchaseController = new PurchaseController($database);
    $sellerId = $authMiddleware->getAuthenticatedUser()['sub'];
    
    sendResponse($purchaseController->listSales($sellerId));
}

// Conversations: List (Protected)
if ($uri === '/conversations' && $method === 'GET') {
    if ($error = $authMiddleware->handle()) sendResponse($error, 401);
    
    $messageController = new MessageController($database);
    $userId = $authMiddleware->getAuthenticatedUser()['sub'];
    
    sendResponse($messageController->listConversations($userId));
}

// Messages: Get (Protected)
if ($uri === '/messages' && $method === 'GET') {
    if ($error = $authMiddleware->handle()) sendResponse($error, 401);
    
    $messageController = new MessageController($database);
    $currentUserId = $authMiddleware->getAuthenticatedUser()['sub'];
    $otherUserId = isset($_GET['userId']) ? (int)$_GET['userId'] : null;
    
    sendResponse($messageController->getMessages($currentUserId, $otherUserId));
}

// Messages: Send (Protected)
if ($uri === '/messages' && $method === 'POST') {
    if ($error = $authMiddleware->handle()) sendResponse($error, 401);
    
    $messageController = new MessageController($database);
    $senderId = $authMiddleware->getAuthenticatedUser()['sub'];
    
    sendResponse($messageController->sendMessage(getRequestBody(), $senderId));
}

// 404 Not Found
sendResponse(['status' => 'error', 'message' => 'Route not found'], 404);

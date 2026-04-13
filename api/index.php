<?php
/**
 * API Entry Point
 * Routes all requests to appropriate controllers
 */

declare(strict_types=1);

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Set timezone
date_default_timezone_set('Asia/Manila');

// Load core files
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/utils/Response.php';

// Set CORS headers
setCorsHeaders();

// Get request info
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove /api prefix if present
$uri = preg_replace('#^/api#', '', $uri);
$uri = '/' . trim($uri, '/');

// Parse URI segments
$segments = explode('/', trim($uri, '/'));
$resource = $segments[0] ?? '';
$id = $segments[1] ?? null;
$action = $segments[2] ?? null;

// Route mapping
$routes = [
    'auth' => 'AuthController',
    'users' => 'UserController',
    'products' => 'ProductController',
    'categories' => 'CategoryController',
    'suppliers' => 'SupplierController',
    'inventory' => 'InventoryController',
    'batches' => 'BatchController',
    'transactions' => 'TransactionController',
    'orders' => 'OrderController',
    'settings' => 'SettingsController',
];

// Health check endpoint
if ($uri === '/' || $uri === '/health') {
    Response::success([
        'status' => 'healthy',
        'version' => '1.0.0',
        'timestamp' => date('c')
    ], 'API is running');
}

// Check if resource exists
if (!isset($routes[$resource])) {
    Response::notFound("Endpoint not found: {$resource}");
}

// Load controller
$controllerName = $routes[$resource];
$controllerFile = __DIR__ . "/controllers/{$controllerName}.php";

if (!file_exists($controllerFile)) {
    Response::serverError("Controller not implemented: {$controllerName}");
}

require_once $controllerFile;

// Instantiate controller and handle request
try {
    $controller = new $controllerName();
    $controller->handleRequest($method, $id, $action);
} catch (PDOException $e) {
    if (Database::getConfig('APP_DEBUG', 'false') === 'true') {
        Response::serverError('Database error: ' . $e->getMessage());
    }
    Response::serverError('A database error occurred');
} catch (Exception $e) {
    if (Database::getConfig('APP_DEBUG', 'false') === 'true') {
        Response::serverError('Error: ' . $e->getMessage());
    }
    Response::serverError('An unexpected error occurred');
}

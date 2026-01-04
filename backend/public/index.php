<?php
/**
 * DiabetaCare API - Entry Point
 * 
 * All requests are routed through this file.
 * Handles CORS, autoloading, and request dispatching.
 */

declare(strict_types=1);

// Error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

// Load environment configuration
require_once __DIR__ . '/../config/env.php';

// Autoloader
require_once __DIR__ . '/../src/Autoloader.php';
\DiabetaCare\Autoloader::register();

// CORS Headers - Allow requests from Next.js frontend
$allowedOrigins = [
    'http://localhost:3000',
    'http://localhost:3001',
    getenv('FRONTEND_URL') ?: 'http://localhost:3000'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: {$origin}");
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

use DiabetaCare\Core\Router;
use DiabetaCare\Core\Request;
use DiabetaCare\Core\Response;
use DiabetaCare\Core\Database;

try {
    // Initialize database connection
    Database::initialize();
    
    // Create request object
    $request = Request::createFromGlobals();
    
    // Load routes
    $router = new Router();
    require_once __DIR__ . '/../routes/api.php';
    
    // Dispatch request and get response
    $response = $router->dispatch($request);
    
    // Send response
    $response->send();
    
} catch (\Throwable $e) {
    // Log error
    error_log("DiabetaCare API Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    // Send error response (don't leak details in production)
    $isDev = getenv('APP_ENV') === 'development';
    
    Response::error(
        'INTERNAL_ERROR',
        $isDev ? $e->getMessage() : 'An unexpected error occurred.',
        $isDev ? ['trace' => $e->getTraceAsString()] : [],
        500
    )->send();
}

<?php
/**
 * DiabetaCare - PHP Frontend Entry Point
 * 
 * Simple PHP router that handles all frontend requests.
 * Routes to appropriate pages based on URL.
 */

declare(strict_types=1);

session_start();

// Configuration
define('BASE_PATH', __DIR__);
define('API_BASE_URL', 'http://localhost:8080/api');

// Autoload classes
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/ApiClient.php';

// Get the requested path
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/diabetic-patient-management-system/frontend';
$path = str_replace($basePath, '', parse_url($requestUri, PHP_URL_PATH));
$path = $path === '' ? '/' : $path;

// Remove trailing slash if present (except for root)
if ($path !== '/' && str_ends_with($path, '/')) {
    $path = rtrim($path, '/');
}

// Route mapping
$routes = [
    // Public routes
    '/' => 'pages/dashboard.php',
    '/login' => 'pages/auth/login.php',
    '/register' => 'pages/auth/register.php',
    '/forgot-password' => 'pages/auth/forgot-password.php',
    '/reset-password' => 'pages/auth/reset-password.php',
    '/logout' => 'pages/auth/logout.php',
    
    // Protected routes
    '/dashboard' => 'pages/dashboard.php',
    '/patients' => 'pages/patients/index.php',
    '/patients/create' => 'pages/patients/create.php',
    '/patients/view' => 'pages/patients/view.php',
    '/patients/edit' => 'pages/patients/edit.php',
    '/appointments' => 'pages/appointments/index.php',
    '/appointments/create' => 'pages/appointments/create.php',
    '/appointments/view' => 'pages/appointments/view.php',
    '/appointments/edit' => 'pages/appointments/edit.php',
    '/medications' => 'pages/medications/index.php',
    '/medications/create' => 'pages/medications/create.php',
    '/medications/view' => 'pages/medications/view.php',
    '/medications/edit' => 'pages/medications/edit.php',
    '/lab-results' => 'pages/lab-results/index.php',
    '/lab-results/create' => 'pages/lab-results/create.php',
    '/lab-results/view' => 'pages/lab-results/view.php',
    '/lab-results/edit' => 'pages/lab-results/edit.php',
    '/settings' => 'pages/settings.php',
    '/quick-start' => 'pages/quick-start.php',
];

// Dynamic route patterns (with ID parameter)
$dynamicRoutes = [
    '/patients/(\d+)' => 'pages/patients/view.php',
    '/patients/(\d+)/edit' => 'pages/patients/edit.php',
    '/appointments/(\d+)' => 'pages/appointments/view.php',
    '/appointments/(\d+)/edit' => 'pages/appointments/edit.php',
    '/medications/(\d+)' => 'pages/medications/view.php',
    '/medications/(\d+)/edit' => 'pages/medications/edit.php',
    '/lab-results/(\d+)' => 'pages/lab-results/view.php',
    '/lab-results/(\d+)/edit' => 'pages/lab-results/edit.php',
];

// Public routes that don't require authentication
$publicRoutes = ['/login', '/register', '/forgot-password', '/reset-password'];

// Initialize page file and route params
$pageFile = null;
$routeParams = [];

// Check if exact route exists
if (isset($routes[$path])) {
    $pageFile = BASE_PATH . '/' . $routes[$path];
} else {
    // Check dynamic routes
    foreach ($dynamicRoutes as $pattern => $file) {
        if (preg_match('#^' . $pattern . '$#', $path, $matches)) {
            $pageFile = BASE_PATH . '/' . $file;
            // Store the ID for use in the page
            if (isset($matches[1])) {
                $_GET['id'] = $matches[1];
                $routeParams['id'] = $matches[1];
            }
            break;
        }
    }
}

// Process the route
if ($pageFile !== null) {
    // Check authentication for protected routes
    if (!in_array($path, $publicRoutes) && $path !== '/logout') {
        if (!isAuthenticated()) {
            // Store intended destination
            $_SESSION['redirect_after_login'] = $path;
            redirect('/login');
        }
    }
    
    // Include the page
    if (file_exists($pageFile)) {
        require_once $pageFile;
    } else {
        http_response_code(404);
        require_once BASE_PATH . '/pages/errors/404.php';
    }
} else {
    http_response_code(404);
    require_once BASE_PATH . '/pages/errors/404.php';
}

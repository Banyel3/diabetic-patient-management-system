<?php
/**
 * DiabetaCare - API Routes Configuration
 * 
 * Defines all API endpoints and their controller mappings.
 */

declare(strict_types=1);

use DiabetaCare\Core\Router;
use DiabetaCare\Middleware\AuthMiddleware;
use DiabetaCare\Controllers\AuthController;
use DiabetaCare\Controllers\DashboardController;
use DiabetaCare\Controllers\PatientsController;
use DiabetaCare\Controllers\AppointmentsController;
use DiabetaCare\Controllers\MedicationsController;
use DiabetaCare\Controllers\LabResultsController;
use DiabetaCare\Controllers\UsersController;

// =============================================================================
// PUBLIC ROUTES (No Authentication Required)
// =============================================================================

// Health check endpoint
$router->get('/api/health', function() {
    return \DiabetaCare\Core\Response::json([
        'status' => 'healthy',
        'version' => '2.0.0',
        'timestamp' => date('c'),
    ]);
});

// Authentication routes
$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/forgot-password', [AuthController::class, 'forgotPassword']);
$router->post('/api/auth/reset-password', [AuthController::class, 'resetPassword']);

// =============================================================================
// PROTECTED ROUTES (Authentication Required)
// =============================================================================

$router->group('', [AuthMiddleware::class], function($router) {
    
    // ---------------------------------------------------------------------------
    // Authentication
    // ---------------------------------------------------------------------------
    $router->post('/api/auth/logout', [AuthController::class, 'logout']);
    $router->get('/api/auth/me', [AuthController::class, 'me']);
    
    // ---------------------------------------------------------------------------
    // User Profile
    // ---------------------------------------------------------------------------
    $router->put('/api/users/me', [UsersController::class, 'updateProfile']);
    $router->put('/api/users/me/password', [UsersController::class, 'updatePassword']);
    
    // ---------------------------------------------------------------------------
    // Dashboard
    // ---------------------------------------------------------------------------
    $router->get('/api/dashboard/summary', [DashboardController::class, 'summary']);
    $router->get('/api/dashboard/upcoming-appointments', [DashboardController::class, 'upcomingAppointments']);
    $router->get('/api/dashboard/recent-patients', [DashboardController::class, 'recentPatients']);
    $router->get('/api/dashboard/critical-alerts', [DashboardController::class, 'criticalAlerts']);
    $router->get('/api/dashboard/hba1c-trends', [DashboardController::class, 'hba1cTrends']);
    $router->get('/api/dashboard/chart-data', [DashboardController::class, 'chartData']);
    
    // ---------------------------------------------------------------------------
    // Patients CRUD
    // ---------------------------------------------------------------------------
    $router->get('/api/patients/list', [PatientsController::class, 'list']);  // Lightweight endpoint for dropdowns
    $router->get('/api/patients', [PatientsController::class, 'index']);
    $router->get('/api/patients/{id}/summary', [PatientsController::class, 'summary']);
    $router->get('/api/patients/{id}', [PatientsController::class, 'show']);
    $router->post('/api/patients', [PatientsController::class, 'store']);
    $router->put('/api/patients/{id}', [PatientsController::class, 'update']);
    $router->delete('/api/patients/{id}', [PatientsController::class, 'destroy']);
    
    // ---------------------------------------------------------------------------
    // Appointments CRUD
    // ---------------------------------------------------------------------------
    $router->get('/api/appointments', [AppointmentsController::class, 'index']);
    $router->get('/api/appointments/{id}', [AppointmentsController::class, 'show']);
    $router->post('/api/appointments', [AppointmentsController::class, 'store']);
    $router->put('/api/appointments/{id}', [AppointmentsController::class, 'update']);
    $router->delete('/api/appointments/{id}', [AppointmentsController::class, 'destroy']);
    
    // ---------------------------------------------------------------------------
    // Medications CRUD
    // ---------------------------------------------------------------------------
    $router->get('/api/medications', [MedicationsController::class, 'index']);
    $router->get('/api/medications/{id}', [MedicationsController::class, 'show']);
    $router->post('/api/medications', [MedicationsController::class, 'store']);
    $router->put('/api/medications/{id}', [MedicationsController::class, 'update']);
    $router->delete('/api/medications/{id}', [MedicationsController::class, 'destroy']);
    
    // ---------------------------------------------------------------------------
    // Lab Results CRUD
    // ---------------------------------------------------------------------------
    $router->get('/api/lab-results', [LabResultsController::class, 'index']);
    $router->get('/api/lab-results/test-types', [LabResultsController::class, 'testTypes']);
    $router->get('/api/lab-results/{id}', [LabResultsController::class, 'show']);
    $router->post('/api/lab-results', [LabResultsController::class, 'store']);
    $router->put('/api/lab-results/{id}', [LabResultsController::class, 'update']);
    $router->delete('/api/lab-results/{id}', [LabResultsController::class, 'destroy']);
});

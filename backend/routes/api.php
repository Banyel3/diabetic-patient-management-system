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
Router::get('/api/health', function() {
    return \DiabetaCare\Core\Response::json([
        'status' => 'healthy',
        'version' => '2.0.0',
        'timestamp' => date('c'),
    ]);
});

// Authentication routes
Router::post('/api/auth/register', [AuthController::class, 'register']);
Router::post('/api/auth/login', [AuthController::class, 'login']);
Router::post('/api/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Router::post('/api/auth/reset-password', [AuthController::class, 'resetPassword']);

// =============================================================================
// PROTECTED ROUTES (Authentication Required)
// =============================================================================

Router::group(['middleware' => [AuthMiddleware::class]], function() {
    
    // ---------------------------------------------------------------------------
    // Authentication
    // ---------------------------------------------------------------------------
    Router::post('/api/auth/logout', [AuthController::class, 'logout']);
    Router::get('/api/auth/me', [AuthController::class, 'me']);
    
    // ---------------------------------------------------------------------------
    // User Profile
    // ---------------------------------------------------------------------------
    Router::put('/api/users/me', [UsersController::class, 'updateProfile']);
    Router::put('/api/users/me/password', [UsersController::class, 'updatePassword']);
    
    // ---------------------------------------------------------------------------
    // Dashboard
    // ---------------------------------------------------------------------------
    Router::get('/api/dashboard/summary', [DashboardController::class, 'summary']);
    Router::get('/api/dashboard/upcoming-appointments', [DashboardController::class, 'upcomingAppointments']);
    Router::get('/api/dashboard/recent-patients', [DashboardController::class, 'recentPatients']);
    Router::get('/api/dashboard/critical-alerts', [DashboardController::class, 'criticalAlerts']);
    Router::get('/api/dashboard/hba1c-trends', [DashboardController::class, 'hba1cTrends']);
    
    // ---------------------------------------------------------------------------
    // Patients CRUD
    // ---------------------------------------------------------------------------
    Router::get('/api/patients', [PatientsController::class, 'index']);
    Router::get('/api/patients/{id}/summary', [PatientsController::class, 'summary']);
    Router::get('/api/patients/{id}', [PatientsController::class, 'show']);
    Router::post('/api/patients', [PatientsController::class, 'store']);
    Router::put('/api/patients/{id}', [PatientsController::class, 'update']);
    Router::delete('/api/patients/{id}', [PatientsController::class, 'destroy']);
    
    // ---------------------------------------------------------------------------
    // Appointments CRUD
    // ---------------------------------------------------------------------------
    Router::get('/api/appointments', [AppointmentsController::class, 'index']);
    Router::get('/api/appointments/{id}', [AppointmentsController::class, 'show']);
    Router::post('/api/appointments', [AppointmentsController::class, 'store']);
    Router::put('/api/appointments/{id}', [AppointmentsController::class, 'update']);
    Router::delete('/api/appointments/{id}', [AppointmentsController::class, 'destroy']);
    
    // ---------------------------------------------------------------------------
    // Medications CRUD
    // ---------------------------------------------------------------------------
    Router::get('/api/medications', [MedicationsController::class, 'index']);
    Router::get('/api/medications/{id}', [MedicationsController::class, 'show']);
    Router::post('/api/medications', [MedicationsController::class, 'store']);
    Router::put('/api/medications/{id}', [MedicationsController::class, 'update']);
    Router::delete('/api/medications/{id}', [MedicationsController::class, 'destroy']);
    
    // ---------------------------------------------------------------------------
    // Lab Results CRUD
    // ---------------------------------------------------------------------------
    Router::get('/api/lab-results', [LabResultsController::class, 'index']);
    Router::get('/api/lab-results/test-types', [LabResultsController::class, 'testTypes']);
    Router::get('/api/lab-results/{id}', [LabResultsController::class, 'show']);
    Router::post('/api/lab-results', [LabResultsController::class, 'store']);
    Router::put('/api/lab-results/{id}', [LabResultsController::class, 'update']);
    Router::delete('/api/lab-results/{id}', [LabResultsController::class, 'destroy']);
});

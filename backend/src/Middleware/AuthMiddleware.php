<?php
/**
 * DiabetaCare - Authentication Middleware
 * 
 * Validates JWT tokens and populates request with user context.
 * Enforces authentication for protected routes.
 */

declare(strict_types=1);

namespace DiabetaCare\Middleware;

use DiabetaCare\Core\Request;
use DiabetaCare\Core\Response;
use DiabetaCare\Services\JwtService;

class AuthMiddleware
{
    private JwtService $jwtService;

    public function __construct()
    {
        $this->jwtService = new JwtService();
    }

    /**
     * Handle the request
     */
    public function handle(Request $request, callable $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return Response::unauthorized('Authentication token is required.', 'TOKEN_MISSING');
        }

        try {
            $payload = $this->jwtService->validateToken($token);
        } catch (\Exception $e) {
            // Check if the exception message contains an error code prefix
            if (str_starts_with($e->getMessage(), 'TOKEN_EXPIRED:')) {
                return Response::unauthorized('Authentication token has expired. Please log in again.', 'TOKEN_EXPIRED');
            }
            
            // All other validation failures
            return Response::unauthorized('Invalid authentication token.', 'TOKEN_INVALID');
        }

        // Populate request with auth context
        $request->userId = (int) $payload['sub'];
        $request->clinicId = (int) $payload['clinic_id'];
        $request->userRole = $payload['role'] ?? 'staff';

        return $next($request);
    }
}

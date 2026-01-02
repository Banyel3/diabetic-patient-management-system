<?php
/**
 * DiabetaCare - Role-Based Authorization Middleware
 * 
 * Enforces role-based access control for protected routes.
 * Must be used after AuthMiddleware.
 */

declare(strict_types=1);

namespace DiabetaCare\Middleware;

use DiabetaCare\Core\Request;
use DiabetaCare\Core\Response;

class RoleMiddleware
{
    private array $allowedRoles;

    /**
     * @param array $allowedRoles Array of roles allowed to access the route
     */
    public function __construct(array $allowedRoles = ['admin'])
    {
        $this->allowedRoles = $allowedRoles;
    }

    /**
     * Handle the request
     */
    public function handle(Request $request, callable $next): Response
    {
        // Ensure user is authenticated first
        if (!$request->userId) {
            return Response::unauthorized('Authentication required.');
        }

        // Check if user's role is allowed
        if (!in_array($request->userRole, $this->allowedRoles, true)) {
            return Response::forbidden(
                'You do not have permission to access this resource.'
            );
        }

        return $next($request);
    }

    /**
     * Create middleware instance that requires specific roles
     */
    public static function require(string ...$roles): self
    {
        return new self($roles);
    }
}

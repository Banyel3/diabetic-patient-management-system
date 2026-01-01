<?php
/**
 * DiabetaCare - Authentication Controller
 * 
 * Handles user registration, login, and logout.
 * Supports the multi-step clinic registration flow.
 */

declare(strict_types=1);

namespace DiabetaCare\Controllers;

use DiabetaCare\Core\Request;
use DiabetaCare\Core\Response;
use DiabetaCare\Core\Database;
use DiabetaCare\Services\JwtService;
use DiabetaCare\Services\Validator;

class AuthController
{
    private JwtService $jwtService;

    public function __construct()
    {
        $this->jwtService = new JwtService();
    }

    /**
     * POST /api/auth/register
     * 
     * Register a new clinic with admin user.
     * Accepts combined payload from multi-step registration.
     */
    public function register(Request $request): Response
    {
        $data = $request->all();

        // Validate all registration fields
        $validator = new Validator($data);
        
        // Step 1: Clinic Information
        $validator
            ->required('clinic_name', 'Clinic name is required.')
            ->maxLength('clinic_name', 255)
            ->required('clinic_email', 'Clinic email is required.')
            ->email('clinic_email')
            ->maxLength('clinic_phone', 50);

        // Step 2: Address
        $validator
            ->maxLength('street_address', 500)
            ->maxLength('city', 100)
            ->maxLength('state_province', 100)
            ->maxLength('zip_postal_code', 20);

        // Step 3: Admin Account
        $validator
            ->required('first_name', 'First name is required.')
            ->maxLength('first_name', 100)
            ->required('last_name', 'Last name is required.')
            ->maxLength('last_name', 100)
            ->required('email', 'Admin email is required.')
            ->email('email')
            ->required('password', 'Password is required.')
            ->password('password')
            ->required('terms_accepted', 'You must accept the terms and conditions.');

        if ($validator->fails()) {
            return Response::validationError(
                $validator->firstErrorMessage(),
                $validator->errors()
            );
        }

        // Check if clinic email already exists
        $existingClinic = Database::queryOne(
            'SELECT id FROM clinics WHERE email = ?',
            [$data['clinic_email']]
        );

        if ($existingClinic) {
            return Response::conflict('A clinic with this email already exists.', [
                'clinic_email' => ['This email is already registered.']
            ]);
        }

        // Check if user email already exists
        $existingUser = Database::queryOne(
            'SELECT id FROM users WHERE email = ?',
            [$data['email']]
        );

        if ($existingUser) {
            return Response::conflict('A user with this email already exists.', [
                'email' => ['This email is already registered.']
            ]);
        }

        // Hash password
        $passwordHash = password_hash($data['password'], PASSWORD_ARGON2ID);

        // Use transaction for atomic creation
        try {
            $result = Database::transaction(function () use ($data, $passwordHash) {
                // Create clinic
                Database::execute(
                    'INSERT INTO clinics (name, business_registration_number, medical_license_number, phone, email) 
                     VALUES (?, ?, ?, ?, ?)',
                    [
                        $data['clinic_name'],
                        $data['business_registration_number'] ?? null,
                        $data['medical_license_number'] ?? null,
                        $data['clinic_phone'] ?? null,
                        $data['clinic_email'],
                    ]
                );
                
                $clinicId = (int) Database::lastInsertId();

                // Create clinic address
                if (!empty($data['street_address']) || !empty($data['city'])) {
                    Database::execute(
                        'INSERT INTO clinic_addresses (clinic_id, street_address, city, state_province, zip_postal_code, country) 
                         VALUES (?, ?, ?, ?, ?, ?)',
                        [
                            $clinicId,
                            $data['street_address'] ?? null,
                            $data['city'] ?? null,
                            $data['state_province'] ?? null,
                            $data['zip_postal_code'] ?? null,
                            $data['country'] ?? 'United States',
                        ]
                    );
                }

                // Create admin user
                Database::execute(
                    'INSERT INTO users (clinic_id, first_name, last_name, email, phone, password_hash, role, terms_accepted_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
                    [
                        $clinicId,
                        $data['first_name'],
                        $data['last_name'],
                        $data['email'],
                        $data['admin_phone'] ?? null,
                        $passwordHash,
                        'admin',
                    ]
                );

                $userId = (int) Database::lastInsertId();

                return [
                    'user_id' => $userId,
                    'clinic_id' => $clinicId,
                ];
            });

            // Generate token for auto-login
            $token = $this->jwtService->generateToken(
                $result['user_id'],
                $result['clinic_id'],
                'admin'
            );

            return Response::created([
                'message' => 'Registration successful.',
                'user' => [
                    'id' => $result['user_id'],
                    'email' => $data['email'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'role' => 'admin',
                ],
                'clinic' => [
                    'id' => $result['clinic_id'],
                    'name' => $data['clinic_name'],
                ],
                'token' => $token,
                'expires_at' => $this->jwtService->getExpiryTime(),
            ]);

        } catch (\Throwable $e) {
            error_log("Registration error: " . $e->getMessage());
            return Response::error(
                'REGISTRATION_FAILED',
                'Failed to complete registration. Please try again.',
                [],
                500
            );
        }
    }

    /**
     * POST /api/auth/login
     * 
     * Authenticate user and return JWT token.
     */
    public function login(Request $request): Response
    {
        $validator = new Validator($request->all());
        $validator
            ->required('email', 'Email is required.')
            ->email('email')
            ->required('password', 'Password is required.');

        if ($validator->fails()) {
            return Response::validationError(
                $validator->firstErrorMessage(),
                $validator->errors()
            );
        }

        $email = $request->input('email');
        $password = $request->input('password');

        // Find user by email with clinic information
        $user = Database::queryOne(
            'SELECT u.id, u.clinic_id, u.first_name, u.last_name, u.email, u.password_hash, 
                    u.role, u.is_active, c.name as clinic_name
             FROM users u
             JOIN clinics c ON c.id = u.clinic_id
             WHERE u.email = ?',
            [$email]
        );

        if (!$user) {
            return Response::unauthorized('Invalid email or password.');
        }

        if (!$user['is_active']) {
            return Response::forbidden('Your account has been deactivated. Please contact support.');
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            return Response::unauthorized('Invalid email or password.');
        }

        // Update last login timestamp
        Database::execute(
            'UPDATE users SET last_login_at = NOW() WHERE id = ?',
            [$user['id']]
        );

        // Generate token
        $token = $this->jwtService->generateToken(
            (int) $user['id'],
            (int) $user['clinic_id'],
            $user['role']
        );

        return Response::json([
            'message' => 'Login successful.',
            'user' => [
                'id' => (int) $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'role' => $user['role'],
            ],
            'clinic' => [
                'id' => (int) $user['clinic_id'],
                'name' => $user['clinic_name'],
            ],
            'token' => $token,
            'expires_at' => $this->jwtService->getExpiryTime(),
        ]);
    }

    /**
     * POST /api/auth/logout
     * 
     * Logout current user (client should discard token).
     */
    public function logout(Request $request): Response
    {
        // With stateless JWT, logout is handled client-side
        // by discarding the token. We could optionally store
        // revoked tokens in a blacklist for enhanced security.
        
        return Response::json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * GET /api/auth/me
     * 
     * Get current authenticated user information.
     */
    public function me(Request $request): Response
    {
        $user = Database::queryOne(
            'SELECT u.id, u.clinic_id, u.first_name, u.last_name, u.email, u.phone,
                    u.role, u.last_login_at, c.name as clinic_name
             FROM users u
             JOIN clinics c ON c.id = u.clinic_id
             WHERE u.id = ?',
            [$request->userId]
        );

        if (!$user) {
            return Response::notFound('User not found.');
        }

        return Response::json([
            'user' => [
                'id' => (int) $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'phone' => $user['phone'],
                'role' => $user['role'],
                'last_login_at' => $user['last_login_at'],
            ],
            'clinic' => [
                'id' => (int) $user['clinic_id'],
                'name' => $user['clinic_name'],
            ],
        ]);
    }

    /**
     * POST /api/auth/forgot-password
     * 
     * Request password reset (generates token, would email in production).
     */
    public function forgotPassword(Request $request): Response
    {
        $validator = new Validator($request->all());
        $validator
            ->required('email', 'Email is required.')
            ->email('email');

        if ($validator->fails()) {
            return Response::validationError(
                $validator->firstErrorMessage(),
                $validator->errors()
            );
        }

        $email = $request->input('email');

        // Find user by email
        $user = Database::queryOne(
            'SELECT id, email FROM users WHERE email = ? AND is_active = 1',
            [$email]
        );

        // Always return success to prevent email enumeration
        if (!$user) {
            return Response::json([
                'message' => 'If an account with that email exists, we have sent password reset instructions.',
            ]);
        }

        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store reset token (using auth_tokens table)
        Database::execute(
            'INSERT INTO auth_tokens (user_id, token, type, expires_at) VALUES (?, ?, ?, ?)',
            [$user['id'], hash('sha256', $token), 'password_reset', $expiresAt]
        );

        // In production, send email with reset link:
        // $resetLink = "https://yourapp.com/reset-password?token={$token}";
        // sendEmail($user['email'], 'Password Reset', "Click here to reset: {$resetLink}");
        
        // For development, log the token
        error_log("Password reset token for {$email}: {$token}");

        return Response::json([
            'message' => 'If an account with that email exists, we have sent password reset instructions.',
            // Only include token in development for testing
            'debug_token' => $token, // Remove in production!
        ]);
    }

    /**
     * POST /api/auth/reset-password
     * 
     * Reset password using token.
     */
    public function resetPassword(Request $request): Response
    {
        $validator = new Validator($request->all());
        $validator
            ->required('token', 'Reset token is required.')
            ->required('password', 'Password is required.')
            ->password('password');

        if ($validator->fails()) {
            return Response::validationError(
                $validator->firstErrorMessage(),
                $validator->errors()
            );
        }

        $token = $request->input('token');
        $password = $request->input('password');
        $hashedToken = hash('sha256', $token);

        // Find valid reset token
        $tokenRecord = Database::queryOne(
            'SELECT id, user_id FROM auth_tokens 
             WHERE token = ? AND type = ? AND expires_at > NOW() AND used_at IS NULL',
            [$hashedToken, 'password_reset']
        );

        if (!$tokenRecord) {
            return Response::badRequest('Invalid or expired reset token.');
        }

        // Update password
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
        Database::execute(
            'UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?',
            [$passwordHash, $tokenRecord['user_id']]
        );

        // Mark token as used
        Database::execute(
            'UPDATE auth_tokens SET used_at = NOW() WHERE id = ?',
            [$tokenRecord['id']]
        );

        return Response::json([
            'message' => 'Password has been reset successfully.',
        ]);
    }
}

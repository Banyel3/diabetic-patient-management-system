<?php
/**
 * DiabetaCare - Users Controller
 * 
 * Handles user profile management operations.
 */

declare(strict_types=1);

namespace DiabetaCare\Controllers;

use DiabetaCare\Core\Request;
use DiabetaCare\Core\Response;
use DiabetaCare\Core\Database;
use DiabetaCare\Core\SqlHelper;
use DiabetaCare\Services\Validator;

class UsersController
{
    /**
     * PUT /api/users/me
     * 
     * Update current user's profile information.
     */
    public function updateProfile(Request $request): Response
    {
        $data = $request->all();
        $userId = $request->userId;

        $validator = new Validator($data);
        $validator
            ->required('first_name', 'First name is required.')
            ->maxLength('first_name', 100)
            ->required('last_name', 'Last name is required.')
            ->maxLength('last_name', 100)
            ->required('email', 'Email is required.')
            ->email('email')
            ->maxLength('phone', 50);

        if ($validator->fails()) {
            return Response::validationError(
                $validator->firstErrorMessage(),
                $validator->errors()
            );
        }

        // Check if email is already taken by another user
        $existingUser = Database::queryOne(
            'SELECT id FROM users WHERE email = ? AND id != ?',
            [$data['email'], $userId]
        );

        if ($existingUser) {
            return Response::conflict('This email is already in use by another account.', [
                'email' => ['This email is already registered.']
            ]);
        }

        // Update user profile
        $nowFunc = SqlHelper::now();
        Database::execute(
            "UPDATE users SET 
                first_name = ?,
                last_name = ?,
                email = ?,
                phone = ?,
                updated_at = {$nowFunc}
             WHERE id = ?",
            [
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $data['phone'] ?? null,
                $userId
            ]
        );

        // Fetch updated user data
        $user = Database::queryOne(
            'SELECT u.id, u.clinic_id, u.first_name, u.last_name, u.email, u.phone,
                    u.role, c.name as clinic_name
             FROM users u
             JOIN clinics c ON c.id = u.clinic_id
             WHERE u.id = ?',
            [$userId]
        );

        return Response::json([
            'message' => 'Profile updated successfully.',
            'user' => [
                'id' => (int) $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'phone' => $user['phone'],
                'role' => $user['role'],
            ],
            'clinic' => [
                'id' => (int) $user['clinic_id'],
                'name' => $user['clinic_name'],
            ],
        ]);
    }

    /**
     * PUT /api/users/me/password
     * 
     * Change current user's password.
     */
    public function updatePassword(Request $request): Response
    {
        $data = $request->all();
        $userId = $request->userId;

        $validator = new Validator($data);
        $validator
            ->required('current_password', 'Current password is required.')
            ->required('new_password', 'New password is required.')
            ->password('new_password');

        if ($validator->fails()) {
            return Response::validationError(
                $validator->firstErrorMessage(),
                $validator->errors()
            );
        }

        // Get current user's password hash
        $user = Database::queryOne(
            'SELECT id, password_hash FROM users WHERE id = ?',
            [$userId]
        );

        if (!$user) {
            return Response::notFound('User not found.');
        }

        // Verify current password
        if (!password_verify($data['current_password'], $user['password_hash'])) {
            return Response::unauthorized('Current password is incorrect.');
        }

        // Check new password is different from current
        if (password_verify($data['new_password'], $user['password_hash'])) {
            return Response::badRequest('New password must be different from current password.');
        }

        // Hash new password
        $newPasswordHash = password_hash($data['new_password'], PASSWORD_ARGON2ID);

        // Update password
        $nowFunc = SqlHelper::now();
        Database::execute(
            "UPDATE users SET password_hash = ?, updated_at = {$nowFunc} WHERE id = ?",
            [$newPasswordHash, $userId]
        );

        return Response::json([
            'message' => 'Password changed successfully.',
        ]);
    }
}

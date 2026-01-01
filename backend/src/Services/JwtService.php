<?php
/**
 * DiabetaCare - JWT Service
 * 
 * Handles JWT token creation and validation.
 * Uses HMAC-SHA256 for signing (HS256 algorithm).
 */

declare(strict_types=1);

namespace DiabetaCare\Services;

class JwtService
{
    private string $secret;
    private int $expiry;

    public function __construct()
    {
        $this->secret = getenv('JWT_SECRET') ?: 'change-this-secret';
        $this->expiry = (int) (getenv('JWT_EXPIRY') ?: 86400);
    }

    /**
     * Generate JWT token for user
     */
    public function generateToken(int $userId, int $clinicId, string $role): string
    {
        $header = $this->base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]));

        $issuedAt = time();
        $expiresAt = $issuedAt + $this->expiry;

        $payload = $this->base64UrlEncode(json_encode([
            'iss' => 'diabetacare',
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'sub' => $userId,
            'clinic_id' => $clinicId,
            'role' => $role,
        ]));

        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", $this->secret, true)
        );

        return "{$header}.{$payload}.{$signature}";
    }

    /**
     * Validate and decode JWT token
     * Returns payload array or null if invalid
     */
    public function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        // Verify signature
        $expectedSignature = $this->base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", $this->secret, true)
        );

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        // Decode payload
        $payloadData = json_decode($this->base64UrlDecode($payload), true);
        
        if (!$payloadData) {
            return null;
        }

        // Check expiration
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            return null;
        }

        return $payloadData;
    }

    /**
     * Get token expiry timestamp
     */
    public function getExpiryTime(): int
    {
        return time() + $this->expiry;
    }

    /**
     * Base64 URL-safe encoding
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL-safe decoding
     */
    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

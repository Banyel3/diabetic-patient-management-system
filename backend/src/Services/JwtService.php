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
     * Returns payload array or throws exception
     * 
     * @throws \Exception with code 'TOKEN_EXPIRED' if token is expired
     * @throws \Exception with code 'TOKEN_INVALID' if token is invalid
     */
    public function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            throw new \Exception('TOKEN_INVALID: Invalid token format');
        }

        [$header, $payload, $signature] = $parts;

        // Verify signature
        $expectedSignature = $this->base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", $this->secret, true)
        );

        if (!hash_equals($expectedSignature, $signature)) {
            throw new \Exception('TOKEN_INVALID: Invalid token signature');
        }

        // Decode payload
        $payloadData = json_decode($this->base64UrlDecode($payload), true);
        
        if (!$payloadData) {
            throw new \Exception('TOKEN_INVALID: Invalid token payload');
        }

        // Check expiration - CRITICAL: This must happen before any other checks
        if (!isset($payloadData['exp'])) {
            throw new \Exception('TOKEN_INVALID: Token missing expiration claim');
        }
        
        if ($payloadData['exp'] < time()) {
            throw new \Exception('TOKEN_EXPIRED: Token has expired');
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

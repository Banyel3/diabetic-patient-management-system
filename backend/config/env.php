<?php
/**
 * DiabetaCare - Environment Configuration
 * 
 * Loads environment variables from .env file.
 * In production, set these variables in the server configuration.
 */

declare(strict_types=1);

$envFile = __DIR__ . '/../.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }
            
            // Set environment variable
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

// Default values (only applied if not set in .env)
$defaults = [
    'APP_ENV' => 'development',
    'APP_DEBUG' => 'true',
    'DB_DRIVER' => 'sqlsrv',
    'DB_HOST' => '.\SQLEXPRESS',
    'DB_PORT' => '1433',
    'DB_NAME' => 'DiabetaCare',
    // DB_USER and DB_PASSWORD intentionally omitted - empty means Windows Auth for SQL Server
    'JWT_SECRET' => 'change-this-secret-in-production',
    'JWT_EXPIRY' => '86400', // 24 hours in seconds
    'FRONTEND_URL' => 'http://localhost:3000',
    'PAGINATION_DEFAULT_SIZE' => '10',
    'PAGINATION_MAX_SIZE' => '100',
];

// Only set defaults for keys not already set (including empty string values from .env)
foreach ($defaults as $key => $value) {
    if (getenv($key) === false) {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
    }
}

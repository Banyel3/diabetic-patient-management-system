<?php
/**
 * DiabetaCare - Helper Functions
 */

declare(strict_types=1);

/**
 * Check if user is authenticated
 */
function isAuthenticated(): bool
{
    return isset($_SESSION['user']) && isset($_SESSION['token']) && !isTokenExpired();
}

/**
 * Check if token is expired
 */
function isTokenExpired(): bool
{
    if (!isset($_SESSION['expires_at'])) {
        return true;
    }
    return time() > $_SESSION['expires_at'];
}

/**
 * Get current user
 */
function getCurrentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Get auth token
 */
function getToken(): ?string
{
    return $_SESSION['token'] ?? null;
}

/**
 * Set auth data in session
 */
function setAuth(array $user, string $token, int $expiresAt): void
{
    $_SESSION['user'] = $user;
    $_SESSION['token'] = $token;
    $_SESSION['expires_at'] = $expiresAt;
}

/**
 * Clear auth session
 */
function clearAuth(): void
{
    unset($_SESSION['user']);
    unset($_SESSION['token']);
    unset($_SESSION['expires_at']);
}

/**
 * Redirect to a URL
 */
function redirect(string $path): void
{
    $basePath = '/diabetic-patient-management-system/frontend';
    header("Location: {$basePath}{$path}");
    exit;
}

/**
 * Get base URL for assets and links
 */
function baseUrl(string $path = ''): string
{
    return '/diabetic-patient-management-system/frontend' . $path;
}

/**
 * Escape HTML output
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Get flash message
 */
function getFlash(string $key): ?string
{
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}

/**
 * Set flash message
 */
function setFlash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

/**
 * Format date for display
 */
function formatDate(?string $date, string $format = 'M j, Y'): string
{
    if (!$date) return 'N/A';
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime(?string $datetime, string $format = 'M j, Y g:i A'): string
{
    if (!$datetime) return 'N/A';
    return date($format, strtotime($datetime));
}

/**
 * Format time for display
 */
function formatTime(?string $time): string
{
    if (!$time) return 'N/A';
    return date('g:i A', strtotime($time));
}

/**
 * Calculate age from date of birth
 */
function calculateAge(string $dob): int
{
    return (int) date_diff(date_create($dob), date_create('now'))->y;
}

/**
 * Get status badge class
 */
function getStatusBadgeClass(string $status): string
{
    $classes = [
        'Active' => 'badge-success',
        'Inactive' => 'badge-secondary',
        'Deceased' => 'badge-danger',
        'Scheduled' => 'badge-info',
        'Completed' => 'badge-success',
        'Cancelled' => 'badge-secondary',
        'No-show' => 'badge-danger',
        'active' => 'badge-success',
        'discontinued' => 'badge-secondary',
        'completed' => 'badge-success',
        'Pending' => 'badge-warning',
        'Normal' => 'badge-success',
        'Abnormal' => 'badge-warning',
        'Critical' => 'badge-danger',
    ];
    return $classes[$status] ?? 'badge-secondary';
}

/**
 * Get diabetes type badge class
 */
function getDiabetesTypeBadgeClass(string $type): string
{
    $classes = [
        'Type 1' => 'bg-blue-50 text-blue-600',
        'Type 2' => 'bg-purple-50 text-purple-600',
        'Gestational' => 'bg-pink-50 text-pink-600',
        'Pre-diabetic' => 'bg-amber-50 text-amber-600',
        'Pre-diabetes' => 'bg-amber-50 text-amber-600',
    ];
    return $classes[$type] ?? 'bg-gray-50 text-gray-600';
}

/**
 * Get HbA1c color class
 */
function getHbA1cColorClass(?float $value): string
{
    if ($value === null) return 'text-muted';
    if ($value < 7) return 'text-success';
    if ($value < 8) return 'text-warning';
    return 'text-danger';
}

/**
 * Build query string from array
 */
function buildQueryString(array $params): string
{
    $filtered = array_filter($params, function($v) {
        return $v !== null && $v !== '';
    });
    return http_build_query($filtered);
}

/**
 * Get pagination data
 */
function getPaginationData(int $currentPage, int $totalPages, int $range = 2): array
{
    $pages = [];
    
    // Always show first page
    $pages[] = 1;
    
    // Add ellipsis after first page if needed
    if ($currentPage - $range > 2) {
        $pages[] = '...';
    }
    
    // Add pages around current page
    for ($i = max(2, $currentPage - $range); $i <= min($totalPages - 1, $currentPage + $range); $i++) {
        $pages[] = $i;
    }
    
    // Add ellipsis before last page if needed
    if ($currentPage + $range < $totalPages - 1) {
        $pages[] = '...';
    }
    
    // Always show last page if more than 1 page
    if ($totalPages > 1) {
        $pages[] = $totalPages;
    }
    
    return array_unique($pages);
}

/**
 * Get initials from name
 */
function getInitials(string $firstName, string $lastName): string
{
    return strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
}

/**
 * Check if request is POST
 */
function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Get POST data
 */
function post(string $key, $default = null)
{
    return $_POST[$key] ?? $default;
}

/**
 * Get GET data
 */
function get(string $key, $default = null)
{
    return $_GET[$key] ?? $default;
}

/**
 * CSRF token generation and validation
 */
function generateCsrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(?string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(generateCsrfToken()) . '">';
}

/**
 * Get appointment status badge class
 */
function getAppointmentStatusBadgeClass(string $status): string
{
    $classes = [
        'Scheduled' => 'badge-info',
        'Completed' => 'badge-success',
        'Cancelled' => 'badge-secondary',
        'No-show' => 'badge-danger',
        'In Progress' => 'badge-warning',
        'Confirmed' => 'badge-primary',
    ];
    return $classes[$status] ?? 'badge-secondary';
}

/**
 * Get the API client instance
 */
function api(): ApiClient
{
    static $api = null;
    if ($api === null) {
        require_once BASE_PATH . '/includes/ApiClient.php';
        $api = new ApiClient();
    }
    return $api;
}

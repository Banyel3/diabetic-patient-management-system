<?php
/**
 * DiabetaCare - HTTP Request Handler
 * 
 * Encapsulates incoming HTTP request data with validation helpers.
 */

declare(strict_types=1);

namespace DiabetaCare\Core;

class Request
{
    private string $method;
    private string $uri;
    private string $path;
    private array $query;
    private array $body;
    private array $headers;
    private array $params = [];
    
    // Auth context (set by middleware)
    public ?int $userId = null;
    public ?int $clinicId = null;
    public ?string $userRole = null;

    public function __construct(
        string $method,
        string $uri,
        array $query,
        array $body,
        array $headers
    ) {
        $this->method = strtoupper($method);
        $this->uri = $uri;
        $this->query = $query;
        $this->body = $body;
        $this->headers = $headers;
        
        // Parse path from URI
        $parsed = parse_url($uri);
        $this->path = $parsed['path'] ?? '/';
        
        // Remove base directory path (for XAMPP subdirectory installations)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = dirname(dirname($scriptName)); // Remove /public/index.php
        if ($basePath !== '/' && $basePath !== '\\' && str_starts_with($this->path, $basePath)) {
            $this->path = substr($this->path, strlen($basePath));
        }
        
        // Remove /public/index.php if present in path
        $this->path = preg_replace('#^/public/index\.php#', '', $this->path);
        
        // Ensure path starts with /
        if (!str_starts_with($this->path, '/')) {
            $this->path = '/' . $this->path;
        }
    }

    /**
     * Create request from PHP globals
     */
    public static function createFromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $query = $_GET;
        
        // Parse JSON body for POST/PUT/PATCH
        $body = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            if (str_contains($contentType, 'application/json')) {
                $rawBody = file_get_contents('php://input');
                if ($rawBody) {
                    $body = json_decode($rawBody, true) ?? [];
                }
            } else {
                $body = $_POST;
            }
        }
        
        // Get headers
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $headers[$headerName] = $value;
            }
        }
        
        // Add content type header
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['CONTENT-TYPE'] = $_SERVER['CONTENT_TYPE'];
        }
        
        // Ensure Authorization header is captured (Apache sometimes drops it)
        // Check multiple sources for the Authorization header
        if (!isset($headers['AUTHORIZATION'])) {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $headers['AUTHORIZATION'] = $_SERVER['HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['AUTHORIZATION'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (function_exists('apache_request_headers')) {
                $apacheHeaders = apache_request_headers();
                foreach ($apacheHeaders as $key => $value) {
                    if (strtoupper($key) === 'AUTHORIZATION') {
                        $headers['AUTHORIZATION'] = $value;
                        break;
                    }
                }
            } elseif (function_exists('getallheaders')) {
                $allHeaders = getallheaders();
                foreach ($allHeaders as $key => $value) {
                    if (strtoupper($key) === 'AUTHORIZATION') {
                        $headers['AUTHORIZATION'] = $value;
                        break;
                    }
                }
            }
        }
        
        return new self($method, $uri, $query, $body, $headers);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get query parameter
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get all query parameters
     */
    public function allQuery(): array
    {
        return $this->query;
    }

    /**
     * Get body parameter
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /**
     * Get all body data
     */
    public function all(): array
    {
        return $this->body;
    }

    /**
     * Get specific fields from body
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->body, array_flip($keys));
    }

    /**
     * Check if body has key
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->body);
    }

    /**
     * Get header
     */
    public function header(string $key, mixed $default = null): mixed
    {
        $key = strtoupper($key);
        return $this->headers[$key] ?? $default;
    }

    /**
     * Get bearer token from Authorization header
     */
    public function bearerToken(): ?string
    {
        $auth = $this->header('AUTHORIZATION');
        if ($auth && preg_match('/Bearer\s+(.+)/i', $auth, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Set route parameters (from URL matching)
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * Get route parameter
     */
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * Get pagination parameters with validation
     */
    public function pagination(): array
    {
        $defaultSize = (int) getenv('PAGINATION_DEFAULT_SIZE') ?: 10;
        $maxSize = (int) getenv('PAGINATION_MAX_SIZE') ?: 100;
        
        $page = max(1, (int) $this->query('page', 1));
        $pageSize = min($maxSize, max(1, (int) $this->query('page_size', $defaultSize)));
        $offset = ($page - 1) * $pageSize;
        
        return [
            'page' => $page,
            'page_size' => $pageSize,
            'offset' => $offset,
        ];
    }
}

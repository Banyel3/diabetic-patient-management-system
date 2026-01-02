<?php
/**
 * DiabetaCare - HTTP Response Builder
 * 
 * Fluent interface for building JSON API responses.
 */

declare(strict_types=1);

namespace DiabetaCare\Core;

class Response
{
    private int $statusCode;
    private array $data;
    private array $headers = [];

    public function __construct(array $data = [], int $statusCode = 200)
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
    }

    /**
     * Create success response with data
     */
    public static function json(array $data, int $statusCode = 200): self
    {
        return new self($data, $statusCode);
    }

    /**
     * Create success response for single item
     */
    public static function item(array $item, int $statusCode = 200): self
    {
        return new self($item, $statusCode);
    }

    /**
     * Create paginated list response
     */
    public static function paginated(
        array $items,
        int $page,
        int $pageSize,
        int $totalItems
    ): self {
        $totalPages = $pageSize > 0 ? (int) ceil($totalItems / $pageSize) : 0;
        return new self([
            'items' => $items,
            'pagination' => [
                'current_page' => $page,
                'page_size' => $pageSize,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages,
            ],
        ]);
    }

    /**
     * Create created response (201)
     */
    public static function created(array $data = []): self
    {
        return new self($data, 201);
    }

    /**
     * Create no content response (204)
     */
    public static function noContent(): self
    {
        return new self([], 204);
    }

    /**
     * Create error response
     */
    public static function error(
        string $code,
        string $message,
        array $details = [],
        int $statusCode = 400
    ): self {
        $error = [
            'error' => [
                'code' => $code,
                'message' => $message,
            ]
        ];

        if (!empty($details)) {
            $error['error']['details'] = $details;
        }

        return new self($error, $statusCode);
    }

    /**
     * Validation error (422)
     */
    public static function validationError(string $message, array $errors = []): self
    {
        return self::error('VALIDATION_ERROR', $message, $errors, 422);
    }

    /**
     * Not found error (404)
     */
    public static function notFound(string $message = 'Resource not found'): self
    {
        return self::error('NOT_FOUND', $message, [], 404);
    }

    /**
     * Unauthorized error (401)
     */
    public static function unauthorized(string $message = 'Authentication required'): self
    {
        return self::error('UNAUTHORIZED', $message, [], 401);
    }

    /**
     * Forbidden error (403)
     */
    public static function forbidden(string $message = 'Access denied'): self
    {
        return self::error('FORBIDDEN', $message, [], 403);
    }

    /**
     * Conflict error (409)
     */
    public static function conflict(string $message, array $details = []): self
    {
        return self::error('CONFLICT', $message, $details, 409);
    }

    /**
     * Add header
     */
    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Send response to client
     */
    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        // Don't output body for 204 No Content
        if ($this->statusCode !== 204 && !empty($this->data)) {
            echo json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }

    /**
     * Get response data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}

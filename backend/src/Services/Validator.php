<?php
/**
 * DiabetaCare - Validation Service
 * 
 * Input validation with error collection.
 */

declare(strict_types=1);

namespace DiabetaCare\Services;

class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Validate required field
     */
    public function required(string $field, ?string $message = null): self
    {
        $value = $this->getValue($field);
        
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->addError($field, $message ?? "{$field} is required.");
        }
        
        return $this;
    }

    /**
     * Validate email format
     */
    public function email(string $field, ?string $message = null): self
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, $message ?? "{$field} must be a valid email address.");
        }
        
        return $this;
    }

    /**
     * Validate minimum length
     */
    public function minLength(string $field, int $min, ?string $message = null): self
    {
        $value = $this->getValue($field);
        
        if ($value !== null && strlen((string) $value) < $min) {
            $this->addError($field, $message ?? "{$field} must be at least {$min} characters.");
        }
        
        return $this;
    }

    /**
     * Validate maximum length
     */
    public function maxLength(string $field, int $max, ?string $message = null): self
    {
        $value = $this->getValue($field);
        
        if ($value !== null && strlen((string) $value) > $max) {
            $this->addError($field, $message ?? "{$field} must not exceed {$max} characters.");
        }
        
        return $this;
    }

    /**
     * Validate enum value
     */
    public function inArray(string $field, array $allowed, ?string $message = null): self
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && !in_array($value, $allowed, true)) {
            $allowedStr = implode(', ', $allowed);
            $this->addError($field, $message ?? "{$field} must be one of: {$allowedStr}.");
        }
        
        return $this;
    }

    /**
     * Validate date format
     */
    public function date(string $field, string $format = 'Y-m-d', ?string $message = null): self
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '') {
            $date = \DateTime::createFromFormat($format, $value);
            if (!$date || $date->format($format) !== $value) {
                $this->addError($field, $message ?? "{$field} must be a valid date ({$format}).");
            }
        }
        
        return $this;
    }

    /**
     * Validate datetime format
     */
    public function datetime(string $field, string $format = 'Y-m-d H:i:s', ?string $message = null): self
    {
        return $this->date($field, $format, $message);
    }

    /**
     * Validate numeric value
     */
    public function numeric(string $field, ?string $message = null): self
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->addError($field, $message ?? "{$field} must be a number.");
        }
        
        return $this;
    }

    /**
     * Validate integer
     */
    public function integer(string $field, ?string $message = null): self
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, $message ?? "{$field} must be an integer.");
        }
        
        return $this;
    }

    /**
     * Validate positive number
     */
    public function positive(string $field, ?string $message = null): self
    {
        $value = $this->getValue($field);
        
        if ($value !== null && is_numeric($value) && (float) $value <= 0) {
            $this->addError($field, $message ?? "{$field} must be a positive number.");
        }
        
        return $this;
    }

    /**
     * Validate password strength
     */
    public function password(string $field, ?string $message = null): self
    {
        $value = $this->getValue($field);
        
        if ($value !== null && $value !== '') {
            if (strlen($value) < 8) {
                $this->addError($field, $message ?? "Password must be at least 8 characters.");
            }
        }
        
        return $this;
    }

    /**
     * Validate fields match (e.g., password confirmation)
     */
    public function matches(string $field, string $otherField, ?string $message = null): self
    {
        $value = $this->getValue($field);
        $otherValue = $this->getValue($otherField);
        
        if ($value !== $otherValue) {
            $this->addError($field, $message ?? "{$field} must match {$otherField}.");
        }
        
        return $this;
    }

    /**
     * Get value from data (supports nested fields with dot notation)
     */
    private function getValue(string $field): mixed
    {
        $keys = explode('.', $field);
        $value = $this->data;
        
        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }
        
        return $value;
    }

    /**
     * Add error message
     */
    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    /**
     * Check if validation passed
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * Get all errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message for field
     */
    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Get first error message overall
     */
    public function firstErrorMessage(): string
    {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0];
        }
        return 'Validation failed.';
    }
}

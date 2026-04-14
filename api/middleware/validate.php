<?php
/**
 * Input Validation Middleware
 * Validates and sanitizes incoming request data
 */

declare(strict_types=1);

require_once __DIR__ . '/../utils/Response.php';

if (!class_exists('Validator')) {
    class Validator
    {
        private array $errors = [];
        private array $data = [];
        private array $validated = [];

        public function __construct(array $data)
        {
            $this->data = $data;
        }

    /**
     * Create validator from JSON request body
     */
    public static function fromRequest(): self
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true) ?? [];
        return new self($data);
    }

    /**
     * Create validator from query parameters
     */
    public static function fromQuery(): self
    {
        return new self($_GET);
    }

    /**
     * Validate required field
     */
    public function required(string $field, ?string $message = null): self
    {
        $value = $this->data[$field] ?? null;

        if ($value === null || $value === '') {
            $this->errors[$field] = $message ?? "{$field} is required";
        } else {
            $this->validated[$field] = $value;
        }

        return $this;
    }

    /**
     * Validate optional field (only validates if present)
     */
    public function optional(string $field): self
    {
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            $this->validated[$field] = $this->data[$field];
        }
        return $this;
    }

    /**
     * Validate string field
     */
    public function string(string $field, ?int $minLength = null, ?int $maxLength = null): self
    {
        if (!isset($this->validated[$field])) {
            return $this;
        }

        $value = $this->validated[$field];

        if (!is_string($value)) {
            $this->errors[$field] = "{$field} must be a string";
            return $this;
        }

        $length = mb_strlen($value);

        if ($minLength !== null && $length < $minLength) {
            $this->errors[$field] = "{$field} must be at least {$minLength} characters";
        }

        if ($maxLength !== null && $length > $maxLength) {
            $this->errors[$field] = "{$field} must not exceed {$maxLength} characters";
        }

        // Sanitize - escape HTML entities
        $this->validated[$field] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        return $this;
    }

    /**
     * Validate email field
     */
    public function email(string $field): self
    {
        if (!isset($this->validated[$field])) {
            return $this;
        }

        $value = $this->validated[$field];

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "{$field} must be a valid email address";
        } else {
            $this->validated[$field] = strtolower(trim($value));
        }

        return $this;
    }

    /**
     * Validate integer field
     */
    public function integer(string $field, ?int $min = null, ?int $max = null): self
    {
        if (!isset($this->validated[$field])) {
            return $this;
        }

        $value = $this->validated[$field];

        if (!is_numeric($value)) {
            $this->errors[$field] = "{$field} must be a number";
            return $this;
        }

        $intValue = (int) $value;

        if ($min !== null && $intValue < $min) {
            $this->errors[$field] = "{$field} must be at least {$min}";
        }

        if ($max !== null && $intValue > $max) {
            $this->errors[$field] = "{$field} must not exceed {$max}";
        }

        $this->validated[$field] = $intValue;

        return $this;
    }

    /**
     * Validate decimal/float field
     */
    public function decimal(string $field, ?float $min = null, ?float $max = null): self
    {
        if (!isset($this->validated[$field])) {
            return $this;
        }

        $value = $this->validated[$field];

        if (!is_numeric($value)) {
            $this->errors[$field] = "{$field} must be a number";
            return $this;
        }

        $floatValue = (float) $value;

        if ($min !== null && $floatValue < $min) {
            $this->errors[$field] = "{$field} must be at least {$min}";
        }

        if ($max !== null && $floatValue > $max) {
            $this->errors[$field] = "{$field} must not exceed {$max}";
        }

        $this->validated[$field] = round($floatValue, 2);

        return $this;
    }

    /**
     * Validate boolean field
     */
    public function boolean(string $field): self
    {
        if (!isset($this->validated[$field])) {
            return $this;
        }

        $value = $this->validated[$field];
        $this->validated[$field] = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        return $this;
    }

    /**
     * Validate enum field
     */
    public function enum(string $field, array $allowedValues): self
    {
        if (!isset($this->validated[$field])) {
            return $this;
        }

        $value = $this->validated[$field];

        if (!in_array($value, $allowedValues, true)) {
            $allowed = implode(', ', $allowedValues);
            $this->errors[$field] = "{$field} must be one of: {$allowed}";
        }

        return $this;
    }

    /**
     * Validate date field
     */
    public function date(string $field, string $format = 'Y-m-d'): self
    {
        if (!isset($this->validated[$field])) {
            return $this;
        }

        $value = $this->validated[$field];
        $date = \DateTime::createFromFormat($format, $value);

        if (!$date || $date->format($format) !== $value) {
            $this->errors[$field] = "{$field} must be a valid date in format {$format}";
        }

        return $this;
    }

    /**
     * Validate UUID field
     */
    public function uuid(string $field): self
    {
        if (!isset($this->validated[$field])) {
            return $this;
        }

        $value = $this->validated[$field];
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

        if (!preg_match($pattern, $value)) {
            $this->errors[$field] = "{$field} must be a valid UUID";
        }

        return $this;
    }

    /**
     * Validate array field
     */
    public function array(string $field): self
    {
        if (!isset($this->validated[$field])) {
            return $this;
        }

        if (!is_array($this->validated[$field])) {
            $this->errors[$field] = "{$field} must be an array";
        }

        return $this;
    }

    /**
     * Validate password strength
     */
    public function password(string $field, int $minLength = 8): self
    {
        if (!isset($this->validated[$field])) {
            return $this;
        }

        $value = $this->validated[$field];

        if (mb_strlen($value) < $minLength) {
            $this->errors[$field] = "Password must be at least {$minLength} characters";
        }

        return $this;
    }

    /**
     * Validate phone number (Philippine format)
     */
    public function phone(string $field): self
    {
        if (!isset($this->validated[$field])) {
            return $this;
        }

        $value = preg_replace('/[^0-9]/', '', $this->validated[$field]);

        // Philippine mobile: 09XXXXXXXXX or +639XXXXXXXXX
        if (!preg_match('/^(0|63)?9\d{9}$/', $value)) {
            $this->errors[$field] = "{$field} must be a valid Philippine phone number";
        } else {
            // Normalize to 09XXXXXXXXX format
            if (substr($value, 0, 2) === '63') {
                $value = '0' . substr($value, 2);
            }
            $this->validated[$field] = $value;
        }

        return $this;
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
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get validated data
     */
    public function getData(): array
    {
        return $this->validated;
    }

    /**
     * Get a single validated value
     */
    public function get(string $field, $default = null)
    {
        return $this->validated[$field] ?? $default;
    }

    /**
     * Validate and return data, or send error response
     */
    public function validate(): ?array
    {
        if ($this->fails()) {
            Response::validationError($this->getErrors());
            return null;
        }

        return $this->getData();
    }
}
}

/**
 * Helper function to validate request data with rule-based validation
 * Supports rules like 'required', 'required|array', 'required|string', etc.
 */
function validateRequest(array $data, array $rules): array
{
    $errors = [];

    foreach ($rules as $field => $rule) {
        $conditions = explode('|', $rule);
        
        foreach ($conditions as $condition) {
            $condition = trim($condition);
            
            if ($condition === 'required') {
                if (!isset($data[$field]) || ($data[$field] === '' && $data[$field] !== 0)) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                }
            } elseif ($condition === 'array') {
                if (isset($data[$field]) && !is_array($data[$field])) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be an array';
                }
            } elseif ($condition === 'string') {
                if (isset($data[$field]) && !is_string($data[$field])) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be a string';
                }
            } elseif ($condition === 'numeric' || $condition === 'number') {
                if (isset($data[$field]) && !is_numeric($data[$field])) {
                    $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be numeric';
                }
            }
        }
    }

    return $errors;
}

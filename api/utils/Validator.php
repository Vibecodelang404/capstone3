<?php
/**
 * Validator Utility
 * Additional validation helpers
 */

declare(strict_types=1);

class Validator
{
    /**
     * Validate UUID format
     */
    public static function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid) === 1;
    }

    /**
     * Validate date format
     */
    public static function isValidDate(string $date, string $format = 'Y-m-d'): bool
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Validate datetime format
     */
    public static function isValidDateTime(string $datetime): bool
    {
        return self::isValidDate($datetime, 'Y-m-d H:i:s');
    }

    /**
     * Sanitize string for SQL LIKE
     */
    public static function escapeLike(string $value): string
    {
        return str_replace(['%', '_'], ['\\%', '\\_'], $value);
    }

    /**
     * Generate slug from string
     */
    public static function slugify(string $text): string
    {
        // Convert to lowercase
        $text = strtolower($text);
        // Replace non-alphanumeric characters with hyphens
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        // Remove leading/trailing hyphens
        return trim($text, '-');
    }

    /**
     * Validate phone number (basic)
     */
    public static function isValidPhone(string $phone): bool
    {
        // Remove common formatting characters
        $cleaned = preg_replace('/[\s\-\(\)\.]+/', '', $phone);
        // Check if remaining is digits with optional + prefix
        return preg_match('/^\+?\d{7,15}$/', $cleaned) === 1;
    }

    /**
     * Validate price format
     */
    public static function isValidPrice($price): bool
    {
        if (!is_numeric($price)) {
            return false;
        }
        return (float)$price >= 0;
    }

    /**
     * Validate quantity (positive integer)
     */
    public static function isValidQuantity($quantity): bool
    {
        if (!is_numeric($quantity)) {
            return false;
        }
        return (int)$quantity >= 0 && (int)$quantity == $quantity;
    }

    /**
     * Sanitize HTML entities
     */
    public static function sanitizeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Validate array has required keys
     */
    public static function hasRequiredKeys(array $data, array $keys): array
    {
        $missing = [];
        foreach ($keys as $key) {
            if (!isset($data[$key]) || (is_string($data[$key]) && trim($data[$key]) === '')) {
                $missing[] = $key;
            }
        }
        return $missing;
    }

    /**
     * Parse and validate pagination params
     */
    public static function getPaginationParams(array $query, int $defaultPerPage = 20, int $maxPerPage = 100): array
    {
        $page = max(1, (int)($query['page'] ?? 1));
        $perPage = min($maxPerPage, max(1, (int)($query['per_page'] ?? $defaultPerPage)));
        $offset = ($page - 1) * $perPage;

        return [
            'page' => $page,
            'per_page' => $perPage,
            'offset' => $offset,
        ];
    }

    /**
     * Parse sort parameters
     */
    public static function getSortParams(array $query, array $allowedFields, string $defaultField = 'created_at', string $defaultOrder = 'DESC'): array
    {
        $sortBy = $query['sort_by'] ?? $defaultField;
        $sortOrder = strtoupper($query['sort_order'] ?? $defaultOrder);

        // Validate sort field
        if (!in_array($sortBy, $allowedFields)) {
            $sortBy = $defaultField;
        }

        // Validate sort order
        if (!in_array($sortOrder, ['ASC', 'DESC'])) {
            $sortOrder = $defaultOrder;
        }

        return [
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
        ];
    }
}

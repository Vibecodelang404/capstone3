<?php
/**
 * Data Transformation Utilities
 * Convert between database snake_case and API camelCase
 */

declare(strict_types=1);

class Transformer
{
    /**
     * Convert snake_case to camelCase
     */
    public static function snakeToCamel(string $str): string
    {
        return lcfirst(str_replace('_', '', ucwords($str, '_')));
    }

    /**
     * Transform array keys from snake_case to camelCase
     */
    public static function toApiFormat(array $data): array
    {
        $transformed = [];
        foreach ($data as $key => $value) {
            $newKey = self::snakeToCamel((string)$key);
            if (is_array($value) && !empty($value) && is_array(reset($value))) {
                // Array of arrays
                $transformed[$newKey] = array_map(fn($item) => self::toApiFormat($item), $value);
            } elseif (is_array($value)) {
                // Single nested array
                $transformed[$newKey] = self::toApiFormat($value);
            } else {
                $transformed[$newKey] = $value;
            }
        }
        return $transformed;
    }

    /**
     * Transform array of records
     */
    public static function toApiFormatArray(array $records): array
    {
        return array_map(fn($record) => self::toApiFormat($record), $records);
    }
}

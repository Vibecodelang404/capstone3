<?php
/**
 * Standardized JSON Response Utility
 * Consistent API responses with proper HTTP status codes
 */

declare(strict_types=1);

class Response
{
    /**
     * Send JSON response and exit
     */
    public static function json($data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    /**
     * Success response with data
     */
    public static function success($data = null, ?string $message = null, int $statusCode = 200): void
    {
        $response = [
            'success' => true,
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        self::json($response, $statusCode);
    }

    /**
     * Created response (201)
     */
    public static function created($data = null, ?string $message = 'Resource created successfully'): void
    {
        self::success($data, $message, 201);
    }

    /**
     * No content response (204)
     */
    public static function noContent(): void
    {
        http_response_code(204);
        exit();
    }

    /**
     * Error response
     */
    public static function error(string $message, int $statusCode = 400, ?array $errors = null): void
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        self::json($response, $statusCode);
    }

    /**
     * Bad request (400)
     */
    public static function badRequest(string $message = 'Bad request', ?array $errors = null): void
    {
        self::error($message, 400, $errors);
    }

    /**
     * Unauthorized (401)
     */
    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, 401);
    }

    /**
     * Forbidden (403)
     */
    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, 403);
    }

    /**
     * Not found (404)
     */
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error($message, 404);
    }

    /**
     * Method not allowed (405)
     */
    public static function methodNotAllowed(string $message = 'Method not allowed'): void
    {
        self::error($message, 405);
    }

    /**
     * Conflict (409)
     */
    public static function conflict(string $message = 'Resource already exists'): void
    {
        self::error($message, 409);
    }

    /**
     * Unprocessable entity (422) - Validation errors
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): void
    {
        self::error($message, 422, $errors);
    }

    /**
     * Too many requests (429)
     */
    public static function tooManyRequests(string $message = 'Too many requests'): void
    {
        self::error($message, 429);
    }

    /**
     * Internal server error (500)
     */
    public static function serverError(string $message = 'Internal server error'): void
    {
        self::error($message, 500);
    }

    /**
     * Paginated response
     */
    public static function paginated(
        array $items,
        int $total,
        int $page,
        int $perPage,
        ?string $message = null
    ): void {
        $totalPages = (int) ceil($total / $perPage);

        $response = [
            'success' => true,
            'data' => $items,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages,
            ],
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        self::json($response);
    }
}

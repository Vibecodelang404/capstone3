<?php
/**
 * Base Controller Class
 * Provides common functionality for all controllers
 */

declare(strict_types=1);

abstract class Controller
{
    /**
     * Handle HTTP request
     */
    abstract public function handleRequest(string $method, ?string $id, ?string $action): void;

    /**
     * Get request data (JSON or form)
     */
    protected function getRequestData(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            return json_decode(file_get_contents('php://input'), true) ?? [];
        }

        return $_POST ?? [];
    }

    /**
     * Require authentication
     */
    protected function requireAuth(): array
    {
        if (!function_exists('verifyJWT')) {
            require_once __DIR__ . '/../middleware/auth.php';
        }

        $token = $this->getBearerToken();
        if (!$token) {
            Response::unauthorized('No token provided');
        }

        $payload = verifyJWT($token);
        if (!$payload) {
            Response::unauthorized('Invalid or expired token');
        }

        return $payload;
    }

    /**
     * Require specific role
     */
    protected function requireRole(string|array $roles): array
    {
        $payload = $this->requireAuth();
        $roles = is_string($roles) ? [$roles] : $roles;

        if (!in_array($payload['role'], $roles)) {
            Response::forbidden('Insufficient permissions');
        }

        return $payload;
    }

    /**
     * Get Bearer token from header
     */
    private function getBearerToken(): ?string
    {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (preg_match('/Bearer\s+(\S+)/', $auth, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Validate request data
     */
    protected function validate(array $data, array $rules): array
    {
        if (!function_exists('validateRequest')) {
            require_once __DIR__ . '/../middleware/validate.php';
        }

        return validateRequest($data, $rules);
    }

    /**
     * Get query parameter
     */
    protected function getQuery(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Check if resource exists
     */
    protected function checkExists($model, string $id, string $resourceName = 'Resource'): void
    {
        if (!$model->find($id)) {
            Response::notFound("{$resourceName} not found");
        }
    }
}

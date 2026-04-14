<?php
/**
 * Authentication Middleware
 * JWT verification and role-based access control
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../utils/Response.php';

class AuthMiddleware
{
    private static ?array $currentUser = null;

    /**
     * Verify JWT token and set current user
     */
    public static function authenticate(): bool
    {
        $token = JWT::extractAccessToken();

        if (!$token) {
            Response::unauthorized('No authentication token provided');
            return false;
        }

        $payload = JWT::decode($token);

        if (!$payload) {
            Response::unauthorized('Invalid or expired token');
            return false;
        }

        self::$currentUser = $payload['user'] ?? null;

        if (!self::$currentUser) {
            Response::unauthorized('Invalid token payload');
            return false;
        }

        return true;
    }

    /**
     * Get the currently authenticated user
     */
    public static function getCurrentUser(): ?array
    {
        return self::$currentUser;
    }

    /**
     * Get the current user ID
     */
    public static function getUserId(): ?string
    {
        return self::$currentUser['id'] ?? null;
    }

    /**
     * Get the current user role
     */
    public static function getRole(): ?string
    {
        return self::$currentUser['role'] ?? null;
    }

    /**
     * Check if user has required role(s)
     */
    public static function requireRole(array $allowedRoles): bool
    {
        if (!self::authenticate()) {
            return false;
        }

        $userRole = self::getRole();

        if (!in_array($userRole, $allowedRoles)) {
            Response::forbidden('You do not have permission to access this resource');
            return false;
        }

        return true;
    }

    /**
     * Require admin role
     */
    public static function requireAdmin(): bool
    {
        return self::requireRole(['admin']);
    }

    /**
     * Require admin or manager role
     */
    public static function requireManager(): bool
    {
        return self::requireRole(['admin', 'manager']);
    }

    /**
     * Require staff role (admin, manager, stockman, cashier)
     */
    public static function requireStaff(): bool
    {
        return self::requireRole(['admin', 'manager', 'stockman', 'cashier']);
    }

    /**
     * Optional authentication - doesn't fail if no token
     */
    public static function optionalAuth(): void
    {
        $token = JWT::extractAccessToken();

        if ($token) {
            $payload = JWT::decode($token);
            if ($payload && isset($payload['user'])) {
                self::$currentUser = $payload['user'];
            }
        }
    }

    /**
     * Check if a user owns a resource
     */
    public static function isOwner(string $resourceUserId): bool
    {
        return self::getUserId() === $resourceUserId;
    }

    /**
     * Check if user is admin or owns the resource
     */
    public static function isAdminOrOwner(string $resourceUserId): bool
    {
        return self::getRole() === 'admin' || self::isOwner($resourceUserId);
    }
}

<?php
/**
 * JWT (JSON Web Token) Configuration and Utilities
 * Uses Firebase\JWT\JWT for secure token encoding/decoding
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/database.php';

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;

class JWT
{
    private static string $algorithm = 'HS256';
    private static ?string $secret = null;

    /**
     * Get JWT secret from environment
     * @throws Exception if JWT_SECRET is not configured
     */
    private static function getSecret(): string
    {
        if (self::$secret === null) {
            $secret = Database::getConfig('JWT_SECRET');
            
            if (empty($secret)) {
                throw new Exception(
                    'FATAL: JWT_SECRET environment variable is not set. ' .
                    'Please configure JWT_SECRET in your .env file for secure token generation.'
                );
            }
            
            self::$secret = $secret;
        }
        
        return self::$secret;
    }

    /**
     * Get access token expiry time in seconds
     */
    public static function getAccessExpiry(): int
    {
        return (int) Database::getConfig('JWT_ACCESS_EXPIRY', 900); // 15 minutes
    }

    /**
     * Get refresh token expiry time in seconds
     */
    public static function getRefreshExpiry(): int
    {
        return (int) Database::getConfig('JWT_REFRESH_EXPIRY', 604800); // 7 days
    }

    /**
     * Create a JWT token using Firebase\JWT\JWT
     */
    public static function encode(array $payload): string
    {
        try {
            return FirebaseJWT::encode($payload, self::getSecret(), self::$algorithm);
        } catch (\Exception $e) {
            throw new \Exception("Failed to encode JWT: " . $e->getMessage());
        }
    }

    /**
     * Decode and verify a JWT token using Firebase\JWT\JWT
     */
    public static function decode(string $token): ?array
    {
        try {
            $decoded = FirebaseJWT::decode($token, new Key(self::getSecret(), self::$algorithm));
            return (array) $decoded;
        } catch (\Exception $e) {
            // Token is invalid or expired - return null silently
            return null;
        }
    }

    /**
     * Generate access token for a user
     */
    public static function generateAccessToken(array $user): string
    {
        $now = time();
        $payload = [
            'iss' => 'inventory-pos',
            'sub' => $user['id'],
            'iat' => $now,
            'exp' => $now + self::getAccessExpiry(),
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'firstName' => $user['first_name'],
                'lastName' => $user['last_name'],
                'role' => $user['role']
            ]
        ];

        return self::encode($payload);
    }

    /**
     * Generate refresh token
     */
    public static function generateRefreshToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Store refresh token in database
     */
    public static function storeRefreshToken(string $userId, string $token): bool
    {
        $db = Database::getInstance();
        $hash = password_hash($token, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', time() + self::getRefreshExpiry());

        $stmt = $db->prepare("
            INSERT INTO refresh_tokens (id, user_id, token_hash, expires_at)
            VALUES (:id, :user_id, :token_hash, :expires_at)
        ");

        return $stmt->execute([
            'id' => Database::generateUUID(),
            'user_id' => $userId,
            'token_hash' => $hash,
            'expires_at' => $expiresAt
        ]);
    }

    /**
     * Verify refresh token from database
     */
    public static function verifyRefreshToken(string $userId, string $token): bool
    {
        $db = Database::getInstance();

        $stmt = $db->prepare("
            SELECT token_hash FROM refresh_tokens 
            WHERE user_id = :user_id 
            AND expires_at > NOW() 
            AND revoked_at IS NULL
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $stmt->execute(['user_id' => $userId]);
        $tokens = $stmt->fetchAll();

        foreach ($tokens as $storedToken) {
            if (password_verify($token, $storedToken['token_hash'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Revoke all refresh tokens for a user
     */
    public static function revokeRefreshTokens(string $userId): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE refresh_tokens 
            SET revoked_at = NOW() 
            WHERE user_id = :user_id AND revoked_at IS NULL
        ");

        return $stmt->execute(['user_id' => $userId]);
    }

    /**
     * Extract token from Authorization header (deprecated - use extractAccessToken instead)
     */
    public static function extractFromHeader(): ?string
    {
        // Try getallheaders() first (available in web/Apache context)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        } else {
            // Fall back to $_SERVER for CLI or non-standard environments
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        }

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract access token from HttpOnly cookies
     * Supports both header-based and cookie-based authentication
     */
    public static function extractAccessToken(): ?string
    {
        // First, try to get token from HttpOnly cookie
        if (isset($_COOKIE['access_token']) && !empty($_COOKIE['access_token'])) {
            return $_COOKIE['access_token'];
        }

        // Fall back to Authorization header for backward compatibility
        return self::extractFromHeader();
    }
}

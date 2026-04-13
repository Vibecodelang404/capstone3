<?php
/**
 * JWT (JSON Web Token) Configuration and Utilities
 */

declare(strict_types=1);

require_once __DIR__ . '/database.php';

class JWT
{
    private static string $algorithm = 'HS256';

    /**
     * Get JWT secret from environment
     */
    private static function getSecret(): string
    {
        return Database::getConfig('JWT_SECRET', 'default-secret-change-me');
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
     * Base64 URL encode
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Create a JWT token
     */
    public static function encode(array $payload): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => self::$algorithm
        ];

        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac(
            'sha256',
            "{$headerEncoded}.{$payloadEncoded}",
            self::getSecret(),
            true
        );
        $signatureEncoded = self::base64UrlEncode($signature);

        return "{$headerEncoded}.{$payloadEncoded}.{$signatureEncoded}";
    }

    /**
     * Decode and verify a JWT token
     */
    public static function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        // Verify signature
        $signature = self::base64UrlDecode($signatureEncoded);
        $expectedSignature = hash_hmac(
            'sha256',
            "{$headerEncoded}.{$payloadEncoded}",
            self::getSecret(),
            true
        );

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        if (!$payload) {
            return null;
        }

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
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
     * Extract token from Authorization header
     */
    public static function extractFromHeader(): ?string
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}

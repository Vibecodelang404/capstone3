<?php
/**
 * Database Configuration
 * Singleton PDO connection with prepared statements
 */

declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;
    private static array $config = [];

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
    }

    /**
     * Load environment variables from .env file
     */
    private static function loadEnv(): void
    {
        if (!empty(self::$config)) {
            return;
        }

        $envFile = __DIR__ . '/../.env';
        if (!file_exists($envFile)) {
            $envFile = __DIR__ . '/../.env.example';
        }

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                
                list($key, $value) = explode('=', $line, 2);
                self::$config[trim($key)] = trim($value);
            }
        }
    }

    /**
     * Get configuration value
     */
    public static function getConfig(string $key, $default = null)
    {
        self::loadEnv();
        return self::$config[$key] ?? $default;
    }

    /**
     * Get the singleton PDO instance
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::loadEnv();

            $host = self::$config['DB_HOST'] ?? 'localhost';
            $port = self::$config['DB_PORT'] ?? '3306';
            $dbname = self::$config['DB_NAME'] ?? 'inventory_pos';
            $username = self::$config['DB_USER'] ?? 'root';
            $password = self::$config['DB_PASS'] ?? '';

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instance = new PDO($dsn, $username, $password, $options);
            } catch (PDOException $e) {
                if (self::getConfig('APP_DEBUG', 'false') === 'true') {
                    throw new PDOException("Database connection failed: " . $e->getMessage());
                }
                throw new PDOException("Database connection failed");
            }
        }

        return self::$instance;
    }

    /**
     * Generate a UUID v4
     */
    public static function generateUUID(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Close the database connection
     */
    public static function close(): void
    {
        self::$instance = null;
    }

    /**
     * Prevent cloning
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
}

<?php
/**
 * Database Configuration
 * Singleton PDO connection with prepared statements
 * Uses Dotenv library for environment variable management
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Ramsey\Uuid\Uuid;
use Dotenv\Dotenv;

if (!class_exists('Database')) {
    class Database
    {
    private static ?PDO $instance = null;
    private static bool $envLoaded = false;
    private static string $logPath = __DIR__ . '/../logs/error.log';

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
    }

    /**
     * Ensure logs directory exists
     */
    private static function ensureLogDirectory(): void
    {
        $logDir = dirname(self::$logPath);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
    }

    /**
     * Log error message securely
     */
    private static function logError(string $message): void
    {
        self::ensureLogDirectory();
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        error_log($logMessage, 3, self::$logPath);
    }

    /**
     * Load environment variables from .env file using Dotenv library
     */
    private static function loadEnv(): void
    {
        if (self::$envLoaded) {
            return;
        }

        $envPath = __DIR__ . '/..';

        // Determine which .env file to use
        $envFile = file_exists($envPath . '/.env') ? '.env' : '.env.example';

        try {
            $dotenv = Dotenv::createImmutable($envPath, $envFile);
            $dotenv->load();
            self::$envLoaded = true;
        } catch (\Exception $e) {
            if (getenv('APP_DEBUG') === 'true') {
                throw new \Exception("Failed to load environment file: " . $e->getMessage());
            }
            // Silently continue if env loading fails - will use defaults
            self::$envLoaded = true;
        }
    }

    /**
     * Get configuration value from environment variables
     * Uses getenv() which reads from $_ENV populated by Dotenv
     */
    public static function getConfig(string $key, $default = null)
    {
        self::loadEnv();
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }

    /**
     * Get the singleton PDO instance
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::loadEnv();

            $host = self::getConfig('DB_HOST', 'localhost');
            $port = self::getConfig('DB_PORT', '3306');
            $dbname = self::getConfig('DB_NAME', 'inventory_pos');
            $username = self::getConfig('DB_USER', 'root');
            $password = self::getConfig('DB_PASS', '');

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
                $isDebug = self::getConfig('APP_DEBUG', 'false') === 'true';

                if ($isDebug) {
                    // Development: expose detailed error message
                    throw new PDOException("Database connection failed: " . $e->getMessage());
                } else {
                    // Production: log detailed error securely, throw generic error to client
                    self::logError(
                        "Database Connection Error: " . $e->getMessage() . 
                        " | Host: {$host} | Database: {$dbname} | User: {$username}"
                    );
                    throw new PDOException("Database connection failed");
                }
            }
        }

        return self::$instance;
    }

    /**
     * Generate a UUID v4 using Ramsey\Uuid
     */
    public static function generateUUID(): string
    {
        return Uuid::uuid4()->toString();
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
}

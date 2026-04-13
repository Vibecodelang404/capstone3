<?php
/**
 * Auth Controller
 * Handles authentication (login, logout, refresh token)
 */

declare(strict_types=1);

class AuthController extends Controller
{
    private User $userModel;
    private RefreshToken $tokenModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->tokenModel = new RefreshToken();
    }

    public function handleRequest(string $method, ?string $id, ?string $action): void
    {
        match ($action) {
            'login' => $this->login($method),
            'register' => $this->register($method),
            'refresh' => $this->refreshToken($method),
            'logout' => $this->logout($method),
            default => Response::notFound('Auth endpoint not found'),
        };
    }

    /**
     * Login user
     * POST /api/auth/login
     */
    private function login(string $method): void
    {
        if ($method !== 'POST') {
            Response::methodNotAllowed('POST');
        }

        $data = $this->getRequestData();
        
        // Validate input
        $errors = $this->validate($data, [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($errors) {
            Response::badRequest('Validation failed', $errors);
        }

        // Find user
        $user = $this->userModel->findByEmail($data['email']);
        if (!$user || !$this->userModel->verifyPassword($data['password'], $user['password_hash'])) {
            Response::unauthorized('Invalid credentials');
        }

        if (!$user['is_active']) {
            Response::forbidden('Account is disabled');
        }

        // Update last login
        $this->userModel->updateLastLogin($user['id']);

        // Generate tokens
        $accessToken = $this->generateAccessToken($user);
        $refreshToken = $this->generateRefreshToken($user['id']);

        Response::success([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'user' => $this->sanitizeUser($user),
            'expires_in' => 900, // 15 minutes
        ], 'Login successful');
    }

    /**
     * Register new customer
     * POST /api/auth/register
     */
    private function register(string $method): void
    {
        if ($method !== 'POST') {
            Response::methodNotAllowed('POST');
        }

        $data = $this->getRequestData();

        $errors = $this->validate($data, [
            'email' => 'required|email',
            'password' => 'required|min:6',
            'first_name' => 'required|min:2',
            'last_name' => 'required|min:2',
        ]);

        if ($errors) {
            Response::badRequest('Validation failed', $errors);
        }

        // Check if email exists
        if ($this->userModel->findByEmail($data['email'])) {
            Response::badRequest('Email already registered');
        }

        // Create user
        $user = $this->userModel->create([
            'email' => $data['email'],
            'password_hash' => User::hashPassword($data['password']),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'role' => 'customer',
            'is_active' => 1,
        ]);

        $accessToken = $this->generateAccessToken($user);
        $refreshToken = $this->generateRefreshToken($user['id']);

        Response::success([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'user' => $this->sanitizeUser($user),
            'expires_in' => 900,
        ], 'Registration successful', 201);
    }

    /**
     * Refresh access token
     * POST /api/auth/refresh
     */
    private function refreshToken(string $method): void
    {
        if ($method !== 'POST') {
            Response::methodNotAllowed('POST');
        }

        $data = $this->getRequestData();

        if (!$data['refresh_token'] ?? null) {
            Response::badRequest('Refresh token required');
        }

        $token = $this->tokenModel->validateToken($data['refresh_token']);
        if (!$token) {
            Response::unauthorized('Invalid or expired refresh token');
        }

        $user = $this->userModel->find($token['user_id']);
        if (!$user || !$user['is_active']) {
            Response::forbidden('User no longer active');
        }

        $accessToken = $this->generateAccessToken($user);

        Response::success([
            'access_token' => $accessToken,
            'expires_in' => 900,
        ], 'Token refreshed');
    }

    /**
     * Logout user
     * POST /api/auth/logout
     */
    private function logout(string $method): void
    {
        if ($method !== 'POST') {
            Response::methodNotAllowed('POST');
        }

        $payload = $this->requireAuth();
        $data = $this->getRequestData();

        if ($data['refresh_token'] ?? null) {
            $this->tokenModel->revokeToken($data['refresh_token']);
        }

        Response::success([], 'Logout successful');
    }

    /**
     * Generate JWT access token
     */
    private function generateAccessToken(array $user): string
    {
        if (!function_exists('generateJWT')) {
            require_once __DIR__ . '/../config/jwt.php';
        }

        return generateJWT([
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'name' => $user['first_name'] . ' ' . $user['last_name'],
        ]);
    }

    /**
     * Generate refresh token
     */
    private function generateRefreshToken(string $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $this->tokenModel->create([
            'user_id' => $userId,
            'token_hash' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+7 days')),
        ]);

        return $token;
    }

    /**
     * Sanitize user data for response
     */
    private function sanitizeUser(array $user): array
    {
        unset($user['password_hash']);
        return $user;
    }
}

/**
 * RefreshToken Model (for storing refresh tokens)
 */
class RefreshToken extends Model
{
    protected static string $table = 'refresh_tokens';

    public function validateToken(string $token): ?array
    {
        $hash = hash('sha256', $token);
        return $this->queryOne(
            "SELECT rt.*, u.id FROM refresh_tokens rt 
             JOIN users u ON rt.user_id = u.id 
             WHERE rt.token_hash = ? AND rt.expires_at > NOW() AND rt.revoked_at IS NULL",
            [$hash]
        );
    }

    public function revokeToken(string $token): void
    {
        $hash = hash('sha256', $token);
        $sql = "UPDATE refresh_tokens SET revoked_at = NOW() WHERE token_hash = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$hash]);
    }
}

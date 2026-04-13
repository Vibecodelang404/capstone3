<?php
/**
 * CORS (Cross-Origin Resource Sharing) Configuration
 * Since frontend and API are on the same server, this is minimal
 */

declare(strict_types=1);

function setCorsHeaders(): void
{
    // Allow same-origin requests (frontend on same server)
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    // In production, you might want to be more restrictive
    $allowedOrigins = [
        'http://localhost:3000',
        'http://localhost:3001',
        'https://localhost:3000',
    ];

    // For same-server deployment, allow the request origin
    if (in_array($origin, $allowedOrigins) || empty($origin)) {
        if (!empty($origin)) {
            header("Access-Control-Allow-Origin: {$origin}");
        }
    }

    header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 86400"); // 24 hours cache for preflight

    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit();
    }
}

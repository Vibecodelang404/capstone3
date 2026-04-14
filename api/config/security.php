<?php
/**
 * Security Headers Configuration
 * Adds global security headers to all API responses
 */

declare(strict_types=1);

function setSecurityHeaders(): void
{
    // Strict-Transport-Security (HSTS)
    // Forces HTTPS and preloading for 1 year (31536000 seconds)
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains', true);

    // X-Content-Type-Options
    // Prevents browsers from MIME-type sniffing
    header('X-Content-Type-Options: nosniff', true);

    // X-Frame-Options
    // Prevents clickjacking attacks by denying iframe embedding
    header('X-Frame-Options: DENY', true);

    // Content Security Policy
    // Restricts the sources from which resources can be loaded
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:", true);

    // X-XSS-Protection (legacy, but good for older browsers)
    // Enables browser XSS protection
    header('X-XSS-Protection: 1; mode=block', true);

    // Referrer-Policy
    // Controls how much referrer information is shared
    header('Referrer-Policy: strict-origin-when-cross-origin', true);

    // Permissions-Policy (formerly Feature-Policy)
    // Restricts use of browser features and APIs
    header('Permissions-Policy: accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()', true);
}

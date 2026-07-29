<?php

namespace Drivejob\Middleware;

/**
 * CSRF Protection Middleware
 * 
 * Παρέχει προστασία από CSRF attacks για web forms
 * Κάνει bypass για API endpoints και JSON requests (stateless)
 */
class CsrfMiddleware
{
    /**
     * Handle CSRF protection
     * 
     * @param callable $next
     * @return mixed
     */
    public static function handle(callable $next)
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        // ✅ Skip CSRF για stateless API/JSON
        if (
            str_starts_with($uri, '/api/')
            || stripos($contentType, 'application/json') !== false
        ) {
            return $next();
        }

        // TODO: εδώ αφήνουμε τον υπάρχοντα έλεγχο CSRF για web φόρμες (αν υπάρχει)
        // π.χ. έλεγχος token από session vs hidden form field

        // For now, we'll implement basic CSRF protection for web forms
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            session_start();

            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            $sessionToken = $_SESSION['csrf_token'] ?? '';

            if (empty($token) || empty($sessionToken) || !hash_equals($sessionToken, $token)) {
                // For web requests, redirect with error
                if (!self::isAjaxRequest()) {
                    $_SESSION['error'] = 'CSRF token mismatch. Please try again.';
                    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
                    exit();
                } else {
                    // For AJAX requests, return JSON error
                    http_response_code(403);
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'CSRF token mismatch']);
                    exit();
                }
            }
        }

        return $next();
    }

    /**
     * Generate CSRF token
     * 
     * @return string
     */
    public static function generateToken(): string
    {
        session_start();

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Get CSRF token for forms
     * 
     * @return string
     */
    public static function getToken(): string
    {
        return self::generateToken();
    }

    /**
     * Generate CSRF token input field
     * 
     * @return string
     */
    public static function tokenField(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Check if request is AJAX
     * 
     * @return bool
     */
    private static function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

<?php

namespace Drivejob\Core;

class CSRFMiddleware
{
    /**
     * Ελέγχει το CSRF token για POST αιτήματα
     */
    public static function handle()
    {
        Session::start();

        // Skip CSRF validation for login and auth endpoints
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (
            strpos($requestUri, 'login') !== false ||
            strpos($requestUri, '/auth/') !== false ||
            strpos($requestUri, 'auth/login') !== false
        ) {
            return; // Skip CSRF validation
        }

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            // Έλεγχος αν υπάρχει το CSRF token
            if (!isset($_POST['csrf_token'])) {
                header("HTTP/1.0 403 Forbidden");
                die('Access Forbidden: Invalid CSRF token');
            }

            // Έλεγχος αν το token είναι έγκυρο
            if (!CSRF::validateToken($_POST['csrf_token'])) {
                header("HTTP/1.0 403 Forbidden");
                die('Access Forbidden: Invalid CSRF token');
            }
        }
    }
}

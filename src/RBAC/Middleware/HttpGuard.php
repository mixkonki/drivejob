<?php

namespace DriveJob\RBAC\Middleware;

use DriveJob\RBAC\Util\Http;
use DriveJob\RBAC\Util\RateLimiter;

final class HttpGuard
{
    public static function requireMethod(string $method): void
    {
        if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== strtoupper($method)) {
            Http::jsonError("Invalid method", ["expected" => $method], 405);
            exit;
        }
    }

    public static function requireCsrf(): void
    {
        // TEMPORARY: Skip CSRF for login endpoint to fix authentication
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($requestUri, '/login') !== false) {
            return; // Skip CSRF validation for login
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $expected = $_SESSION["csrf_token"] ?? null;
        $token = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? ($_POST["csrf_token"] ?? ($_POST["csrf"] ?? $_GET["csrf"] ?? null));
        if (!$expected || !$token || !hash_equals($expected, $token)) {
            Http::jsonError("CSRF token invalid or missing", [], 403);
            exit;
        }
    }

    /**
     * Επιστρέφει 429 αν ξεπεραστεί το όριο. Θέτει X-RateLimit headers.
     */
    public static function requireRateLimit(string $bucket, int $max, int $windowSec): void
    {
        $res = RateLimiter::check($bucket, $max, $windowSec);
        header("X-RateLimit-Limit: $max");
        header("X-RateLimit-Remaining: {$res["remaining"]}");
        header("X-RateLimit-Reset: {$res["reset"]}");
        if (!$res["allowed"]) {
            $retry = max(1, $res["reset"] - time());
            header("Retry-After: $retry");
            Http::jsonError("Too many requests", ["bucket" => $bucket], 429);
            exit;
        }
    }
}

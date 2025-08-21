<?php
// Minimal bootstrap for endpoints that need RBAC checks.
require_once __DIR__ . "/../../src/RBAC/DB.php";
require_once __DIR__ . "/../../src/RBAC/RBAC.php";
require_once __DIR__ . "/../../src/RBAC/Util/Http.php";
require_once __DIR__ . "/../../src/RBAC/Util/Security.php";
require_once __DIR__ . "/../../src/RBAC/Util/RateLimiter.php";
require_once __DIR__ . "/../../src/RBAC/Logger.php";
require_once __DIR__ . "/../../src/RBAC/Middleware/Guard.php";
require_once __DIR__ . "/../../src/RBAC/Middleware/HttpGuard.php";

use DriveJob\RBAC\RBAC;
use DriveJob\RBAC\Util\Http;
use DriveJob\RBAC\Util\Security;

// Start session (CSRF), apply security headers
if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
Security::headers();

// CSRF token bootstrap
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Global JSON error/exception handler
set_exception_handler(function ($e) {
    Http::json(["error" => "ServerError", "message" => $e->getMessage()], 500);
    exit;
});
set_error_handler(function ($severity, $message, $file, $line) {
    Http::json(["error" => "PhpError", "message" => $message, "file" => $file, "line" => $line], 500);
    exit;
});

/** Replace με πραγματικό auth/session. Για dev: ?uid=1 ή υπαρκτό id. */
function currentUserId(): ?int
{
    if (isset($_GET["uid"])) return max(1, (int)$_GET["uid"]);
    return 1; // default admin για dev
}

// Προφόρτωση permissions για το τρέχον request
RBAC::primePermissions((int) (currentUserId() ?? 0));

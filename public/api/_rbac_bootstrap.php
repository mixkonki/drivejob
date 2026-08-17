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
use DriveJob\RBAC\DB;

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

/** Επιστρέφει το user id ΜΟΝΟ από το session (το ?uid= dev fallback αφαιρέθηκε — security). */
function currentUserId(): ?int
{
    if (isset($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }
    return null;
}

// Προφόρτωση permissions για το τρέχον request
// Προαιρετικό cache warm-up — η μέθοδος δεν υπάρχει σε όλες τις εκδόσεις της RBAC
if (method_exists(RBAC::class, 'primePermissions')) {
    RBAC::primePermissions((int) (currentUserId() ?? 0));
}

// set actor for audit triggers
DB::pdo()->prepare("SET @rbac_actor_user_id = :uid")->execute([':uid' => (int)(currentUserId() ?? 0)]);

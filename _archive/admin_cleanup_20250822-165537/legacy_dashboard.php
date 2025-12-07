<?php

declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Αν έχεις κοινό bootstrap για admin, βάλε εδώ require_once προς αυτό.
// Για ασφάλεια, θα στηριχτούμε στο υπάρχον login/session της εφαρμογής.

// Προαιρετικός έλεγχος admin.access μέσω RBAC (αν θέλεις αυστηρό έλεγχο)
// require_once __DIR__ . "/../../src/RBAC/DB.php";
// require_once __DIR__ . "/../../src/RBAC/RBAC.php";
// use DriveJob\RBAC\RBAC;
// $uid = (int)($_SESSION['user_id'] ?? $_GET['uid'] ?? 0);
// if ($uid) { RBAC::requirePermission($uid, "admin.access"); }

$legacy = __DIR__ . "/../../src/Views/admin/dashboard_legacy.php";
if (!is_file($legacy)) {
    http_response_code(500);
    header("Content-Type: text/html; charset=utf-8");
    echo "<pre>Legacy dashboard not found: " . htmlspecialchars($legacy) . "</pre>";
    exit;
}
require $legacy;

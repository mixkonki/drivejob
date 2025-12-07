<?php

declare(strict_types=1);

// Minimal bootstrap (χωρίς αυστηρό RBAC εδώ)
require_once __DIR__ . "/../../src/RBAC/DB.php";
require_once __DIR__ . "/../../src/RBAC/Util/Http.php";
require_once __DIR__ . "/../../src/RBAC/Util/RateLimiter.php";
require_once __DIR__ . "/../../config/dev.php";

use DriveJob\RBAC\DB;
use DriveJob\RBAC\Util\Http;
use DriveJob\RBAC\Util\RateLimiter;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Check if POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php?err=3");
    exit;
}

// Rate limiting (skip in dev mode)
if (!defined('DEV_MODE') || DEV_MODE !== true) {
    $ip = $_SERVER["REMOTE_ADDR"] ?? "cli";
    $bucket = 'login:' . (preg_match('/^\d+(\.\d+){3}$/', $ip) ? $ip : substr(sha1((string)$ip), 0, 12));
    $result = RateLimiter::check($bucket, 10, 60);
    if (!$result['allowed']) {
        header("Location: login.php?err=3");
        exit;
    }
} else {
    header('X-RateLimit-Dev: bypassed');
}

$email = trim((string)($_POST["email"] ?? ""));
$pass  = (string)($_POST["password"] ?? "");

if ($email === "" || $pass === "") {
    header("Location: login.php?err=1");
    exit;
}

$pdo = DB::pdo();

// Locate password column
$col = "password";
$row = $pdo->query("SHOW COLUMNS FROM users LIKE 'password'")->fetch();
if (!$row) {
    $row = $pdo->query("SHOW COLUMNS FROM users LIKE 'pass'")->fetch();
    if ($row) $col = "pass";
}

$st = $pdo->prepare("SELECT id,email,username,{$col} AS password,is_active FROM users WHERE email=:e LIMIT 1");
$st->execute([":e" => $email]);
$u = $st->fetch(\PDO::FETCH_ASSOC);

if (!$u) {
    header("Location: login.php?err=1"); // no user
    exit;
}

if (isset($u["is_active"]) && (string)$u["is_active"] === "0") {
    header("Location: login.php?err=4");
    exit;
}

$hash = (string)$u["password"];
if (!password_verify($pass, $hash)) {
    header("Location: login.php?err=2"); // bad password
    exit;
}

// OK -> set session and go to unified dashboard
$_SESSION["user_id"] = (int)$u["id"];
header("Location: index.php");
exit;

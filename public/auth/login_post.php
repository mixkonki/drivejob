<?php

declare(strict_types=1);

require_once __DIR__ . "/../admin/_rbac_bootstrap.php";
require_once __DIR__ . "/../../src/RBAC/DB.php";
require_once __DIR__ . "/../../src/RBAC/RBAC.php";

use DriveJob\RBAC\DB;
use DriveJob\RBAC\RBAC;

@ini_set("display_errors", "0");

// --- 2.1 CSRF validate (strict) ---
session_start();

$posted = $_POST["csrf_token"] ?? "";
$server = $_SESSION["csrf_token"] ?? "";
if (!$posted || !$server || !hash_equals($server, $posted)) {
    header("Location: /drivejob/public/auth/login.php?err=csrf");
    exit;
}

// --- 2.2 (προαιρετικό) reset/soft rate-limit bucket στο επιτυχές CSRF (αφήνουμε το υπάρχον RL κώδικα αν υπάρχει αλλού) ---

// --- 2.3 Auth check (απλό, βασισμένο στη DB) ---
$email = trim((string)($_POST["email"] ?? ""));
$pass  = (string)($_POST["pass"] ?? $_POST["password"] ?? "");

if ($email === "" || $pass === "") {
    header("Location: /drivejob/public/auth/login.php?err=auth");
    exit;
}

$pdo = DB::pdo();
$user = null;
$userRole = null;

// Έλεγχος στον πίνακα users (admin)
$st = $pdo->prepare("SELECT id, email, username, password FROM users WHERE email=:e LIMIT 1");
$st->execute([":e" => $email]);
$u = $st->fetch(\PDO::FETCH_ASSOC);

if ($u && password_verify($pass, (string)$u["password"])) {
    $user = $u;
    $userRole = 'admin';
}

// Αν δεν βρέθηκε στους users, έλεγχος στους drivers
if (!$user) {
    $st = $pdo->prepare("SELECT id, email, first_name, last_name, password FROM drivers WHERE email=:e LIMIT 1");
    $st->execute([":e" => $email]);
    $u = $st->fetch(\PDO::FETCH_ASSOC);

    if ($u && password_verify($pass, (string)$u["password"])) {
        $user = $u;
        $userRole = 'driver';
    }
}

// Αν δεν βρέθηκε στους drivers, έλεγχος στις companies
if (!$user) {
    $st = $pdo->prepare("SELECT id, email, company_name, password FROM companies WHERE email=:e LIMIT 1");
    $st->execute([":e" => $email]);
    $u = $st->fetch(\PDO::FETCH_ASSOC);

    if ($u && password_verify($pass, (string)$u["password"])) {
        $user = $u;
        $userRole = 'company';
    }
}

// Αν δεν βρέθηκε σε κανέναν πίνακα
if (!$user) {
    header("Location: /drivejob/public/auth/login.php?err=auth");
    exit;
}

// login ok -> δέσε session
$_SESSION["user_id"] = (int)$user["id"];
$_SESSION["user_role"] = $userRole;
$_SESSION["user_email"] = $user["email"];

// Set user name based on role
if ($userRole === 'admin') {
    $_SESSION["user_name"] = $user["username"] ?? "Administrator";
} elseif ($userRole === 'driver') {
    $_SESSION["user_name"] = ($user["first_name"] ?? "") . " " . ($user["last_name"] ?? "");
} elseif ($userRole === 'company') {
    $_SESSION["user_name"] = $user["company_name"] ?? "Company";
}

// --- 2.4 Post-login redirect policy ---
if ($userRole === 'admin' && RBAC::userCan((int)$user["id"], "admin.access")) {
    header("Location: /drivejob/public/admin/index.php");
    exit;
} elseif ($userRole === 'driver') {
    header("Location: /drivejob/public/drivers/driver-profile.php");
    exit;
} elseif ($userRole === 'company') {
    header("Location: /drivejob/public/companies/company-profile.php");
    exit;
}

// Fallback
header("Location: /drivejob/public/");

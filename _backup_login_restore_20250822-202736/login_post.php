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
$pass  = (string)($_POST["pass"] ?? "");

if ($email === "" || $pass === "") {
    header("Location: /drivejob/public/auth/login.php?err=auth");
    exit;
}

$pdo = DB::pdo();
$col = "password";
$colRow = $pdo->query("SHOW COLUMNS FROM users LIKE 'password'")->fetch();
if (!$colRow) {
    $colRow = $pdo->query("SHOW COLUMNS FROM users LIKE 'pass'")->fetch();
    if ($colRow) $col = "pass";
}
$st = $pdo->prepare("SELECT id, email, username, {$col} AS password FROM users WHERE email=:e LIMIT 1");
$st->execute([":e" => $email]);
$u = $st->fetch(\PDO::FETCH_ASSOC);

if (!$u || !password_verify($pass, (string)$u["password"])) {
    header("Location: /drivejob/public/auth/login.php?err=auth");
    exit;
}

// login ok -> δέσε session και καθάρισε παλιά intended
$_SESSION["user_id"] = (int)$u["id"];

// --- 2.4 Post-login redirect policy ---
// Αν ο χρήστης έχει admin.access => admin unified dashboard
if (RBAC::userCan((int)$u["id"], "admin.access")) {
    header("Location: /drivejob/public/admin/index.php");
    exit;
}

// Διαφορετικά, άφησε την παλιά ροή (αν έχεις route), αλλιώς πήγαινέ τον στην home
$intended = $_SESSION["intended"] ?? null;
unset($_SESSION["intended"]);
if (is_string($intended) && $intended !== "") {
    header("Location: " . $intended);
    exit;
}
header("Location: /drivejob/public/");

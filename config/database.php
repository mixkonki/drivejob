<?php

// Database configuration constants
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'drivejob');
define('DB_USER', 'root');
define('DB_PASS', '');

// Σύνδεση στη βάση δεδομένων
$host = DB_HOST;
$db = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;

// Σύνδεση PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET NAMES utf8mb4");
} catch (PDOException $e) {
    die("Σφάλμα σύνδεσης: " . $e->getMessage());
}

// Επιστροφή του PDO
return $pdo;

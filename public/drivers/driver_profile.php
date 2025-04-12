<?php

// Αρχικοποίηση της εφαρμογής
require_once __DIR__ . '/../../src/bootstrap.php';
// Αυτόματη φόρτωση μέσω Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// Συμπερίληψη του config.php για να οριστούν οι σταθερές
require_once __DIR__ . '/../../config/config.php';

// Συμπερίληψη του database.php για σύνδεση με τη βάση δεδομένων
require_once ROOT_DIR . '/config/database.php';

// Ξεκίνημα ή συνέχιση session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι οδηγός
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Δημιουργία του controller και κλήση της μεθόδου profile
$controller = new \Drivejob\Controllers\DriversController($pdo);
$controller->profile();
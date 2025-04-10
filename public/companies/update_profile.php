<?php
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

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι εταιρεία
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'company') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Έλεγχος αν η μέθοδος είναι POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'companies/edit_profile.php');
    exit();
}

// Δημιουργία του controller και κλήση της μεθόδου update
$controller = new \Drivejob\Controllers\CompaniesController($pdo);
$controller->update();
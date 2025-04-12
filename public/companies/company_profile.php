<?php
// Αυτές πρέπει να είναι οι πρώτες γραμμές του αρχείου
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Αυτόματη φόρτωση μέσω Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// Συμπερίληψη του config.php για να οριστούν οι σταθερές
require_once __DIR__ . '/../../config/config.php';

// Συμπερίληψη του database.php για σύνδεση με τη βάση δεδομένων
require_once ROOT_DIR . '/config/database.php';

// Ξεκίνημα ή συνέχιση session
use Drivejob\Core\Session;
Session::start();

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι εταιρεία
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'company') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Δημιουργία του controller και κλήση της μεθόδου profile
$controller = new \Drivejob\Controllers\CompaniesController($pdo);
$controller->profile();
<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';
require_once ROOT_DIR . '/config/database.php';

use Drivejob\Core\Session;
Session::start();

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι εταιρεία
if (!Session::has('user_id') || !Session::has('role') || Session::get('role') !== 'driver') {
    // Αν δεν είναι συνδεδεμένος ή δεν είναι εταιρεία, ανακατεύθυνση στη σελίδα σύνδεσης
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Δημιουργία του controller και κλήση της μεθόδου edit
$controller = new \Drivejob\Controllers\DriversController($pdo);
$controller->edit();
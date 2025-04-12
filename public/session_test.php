<?php
// Αυτόματη φόρτωση μέσω Composer
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use Drivejob\Core\Session;

Session::start();

echo "<h1>Δοκιμή Συνεδρίας</h1>";
echo "<pre>";
echo "Current Session ID: " . session_id() . "\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "</pre>";

// Ορισμός δοκιμαστικών δεδομένων
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo "<p>Δεν είσαι συνδεδεμένος. Ορισμός δοκιμαστικών δεδομένων...</p>";
    
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'company';
    $_SESSION['user_name'] = 'Δοκιμαστικός Χρήστης';
    
    echo "<p>Δοκιμαστικά δεδομένα προστέθηκαν. <a href='session_test.php'>Ανανέωση</a></p>";
}

// Δοκιμαστικοί σύνδεσμοι
echo "<p>Δοκιμή συνδέσμων:</p>";
echo "<ul>";
echo "<li><a href='" . BASE_URL . "job-listings/create'>Δημιουργία Αγγελίας</a></li>";
echo "<li><a href='" . BASE_URL . "companies/edit_profile.php'>Επεξεργασία Προφίλ Εταιρείας</a></li>";
echo "<li><a href='" . BASE_URL . "drivers/edit_profile.php'>Επεξεργασία Προφίλ Οδηγού</a></li>";
echo "</ul>";
?>
<?php
// src/bootstrap.php

// Αυτόματη φόρτωση μέσω Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Συμπερίληψη του config.php για τον ορισμό σταθερών
require_once __DIR__ . '/../config/config.php';

// Σύνδεση με τη βάση δεδομένων
require_once __DIR__ . '/../config/database.php';

// Προσθέστε αυτή τη γραμμή στο bootstrap.php
require_once __DIR__ . '/Helpers/form_helpers.php';

// Ορισμός περιβάλλοντος
defined('ENVIRONMENT') or define('ENVIRONMENT', 'development');

// Ρυθμίσεις για το περιβάλλον
if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

// Αρχικοποίηση του Database Session Handler και έναρξη της συνεδρίας
use Drivejob\Core\Session;
use Drivejob\Core\DatabaseSessionHandler;

// Έλεγχος αν πρέπει να χρησιμοποιηθεί ο Database Session Handler
$useDbSessions = defined('USE_DB_SESSIONS') ? USE_DB_SESSIONS : false;

if ($useDbSessions) {
    // Ρύθμιση του Session Handler
    $sessionHandler = new DatabaseSessionHandler($pdo, [
        'lifetime' => 86400, // 24 ώρες
        'table' => 'sessions'
    ]);
    Session::setHandler($sessionHandler);
}

// Έναρξη της συνεδρίας
Session::start();

// Έλεγχος για λήξη συνεδρίας λόγω αδράνειας (30 λεπτά)
if (Session::isExpired(1800)) {
    // Καταγραφή λήξης συνεδρίας
    error_log("Session expired due to inactivity: " . Session::getId());
    
    // Καταστροφή της συνεδρίας και ανακατεύθυνση στη σελίδα σύνδεσης αν ο χρήστης είναι συνδεδεμένος
    if (Session::has('user_id')) {
        Session::destroy();
        if (!headers_sent() && !isset($_GET['ajax'])) {
            header('Location: ' . BASE_URL . 'login.php?expired=1');
            exit();
        }
    }
}

// Λοιπή αρχικοποίηση
// ...
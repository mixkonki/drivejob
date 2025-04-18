<?php
// src/bootstrap.php

// Ορισμός αν είμαστε σε περιβάλλον CLI (Command Line Interface)
if (!defined('IS_CLI')) {
    define('IS_CLI', php_sapi_name() === 'cli');
}

// Ορισμός του ROOT_DIR
define('ROOT_DIR', dirname(__DIR__));

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

// Ρύθμιση του session μόνο αν δεν είμαστε σε CLI περιβάλλον
if (!IS_CLI) {
    if ($useDbSessions) {
        // Ρύθμιση του Session Handler
        $sessionHandler = new DatabaseSessionHandler($pdo, [
            'lifetime' => 86400, // 24 ώρες
            'table' => 'sessions'
        ]);
        Session::setHandler($sessionHandler);
    }

    // Εκκίνηση της συνεδρίας
    Session::start();
    
    // Έλεγχος για μη ενεργές συνεδρίες
    if (Session::isExpired(1800)) { // 30 λεπτά
        // Καταγραφή λήξης συνεδρίας
        error_log("Session expired due to inactivity: " . Session::getId());
        
        // Καταστροφή της συνεδρίας και ανακατεύθυνση στη σελίδα σύνδεσης αν ο χρήστης είναι συνδεδεμένος
        if (Session::has('user_id')) {
            Session::destroy();
            Session::start();
            if (!headers_sent() && !isset($_GET['ajax'])) {
                header('Location: ' . BASE_URL . 'login.php?expired=1');
                exit();
            }
        } else {
            Session::destroy();
            Session::start();
        }
    }
}

// Λοιπή αρχικοποίηση
// ...
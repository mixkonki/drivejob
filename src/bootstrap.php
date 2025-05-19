<?php

// src/bootstrap.php

// Ορισμός αν είμαστε σε περιβάλλον CLI (Command Line Interface)
if (!defined('IS_CLI')) {
    define('IS_CLI', php_sapi_name() === 'cli');
}

// Ορισμός του ROOT_DIR
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__));
}

// Αυτόματη φόρτωση μέσω Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Συμπερίληψη του config.php για τον ορισμό σταθερών
require_once __DIR__ . '/../config/config.php';

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

// Αρχικοποίηση του ExceptionHandler αν δεν έχει απενεργοποιηθεί
if (!defined('DISABLE_EXCEPTION_HANDLER') || !DISABLE_EXCEPTION_HANDLER) {
    $exceptionHandler = new \Drivejob\Core\ExceptionHandler([
        'debug' => ENVIRONMENT === 'development',
        'log_exceptions' => true,
        'display_errors' => ENVIRONMENT === 'development',
        'error_view' => 'error',
        'error_layout' => 'layout',
        'error_views_path' => '/src/Views/errors/',
        'default_error_message' => 'Υπήρξε ένα σφάλμα συστήματος. Παρακαλώ δοκιμάστε ξανά αργότερα.'
    ]);
    $exceptionHandler->register();
}

// Αρχικοποίηση του Container
$container = \Drivejob\Core\Container::getInstance();

// Καταχώρηση των βασικών υπηρεσιών
$container->set('pdo', function () {
    // Σύνδεση με τη βάση δεδομένων
    require_once ROOT_DIR . '/config/database.php';
    return $pdo;
});

// Καταχώρηση των repositories
require_once __DIR__ . '/container_bindings.php';

// Αρχικοποίηση του Logger
\Drivejob\Core\Logger::init();

// Συμπερίληψη των βοηθητικών συναρτήσεων
require_once __DIR__ . '/Helpers/form_helpers.php';

// Αρχικοποίηση του Database Session Handler και έναρξη της συνεδρίας
use Drivejob\Core\Session;
use Drivejob\Core\DatabaseSessionHandler;

// Έλεγχος αν πρέπει να χρησιμοποιηθεί ο Database Session Handler
$useDbSessions = defined('USE_DB_SESSIONS') ? USE_DB_SESSIONS : false;

// Ρύθμιση του session μόνο αν δεν είμαστε σε CLI περιβάλλον
if (!IS_CLI) {
    if ($useDbSessions) {
        // Ρύθμιση του Session Handler
        $sessionHandler = new DatabaseSessionHandler($container->get('pdo'), [
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
        \Drivejob\Core\Logger::info("Session expired due to inactivity: " . Session::getId());

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

// Επιστροφή του Container
return $container;

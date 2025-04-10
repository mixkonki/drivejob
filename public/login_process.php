<?php
// public/login_process.php
// Αυτόματη φόρτωση μέσω Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Συμπερίληψη του config.php για τις σταθερές BASE_URL και ROOT_DIR
require_once '../config/config.php';

// Συμπερίληψη του database.php για τη σύνδεση στη βάση δεδομένων
require_once ROOT_DIR . '/config/database.php';

use Drivejob\Core\RateLimiter;
use Drivejob\Core\CSRF;

session_start(); // Έναρξη της συνεδρίας

// Έλεγχος CSRF token
if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
    $_SESSION['login_error'] = 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Έλεγχος rate limit
$ip = $_SERVER['REMOTE_ADDR'];
$checkLimit = RateLimiter::checkLimit($ip, 'login');

if ($checkLimit['limited']) {
    $_SESSION['login_error'] = "Πάρα πολλές προσπάθειες σύνδεσης. Παρακαλώ δοκιμάστε ξανά σε " . ceil($checkLimit['wait_time'] / 60) . " λεπτά.";
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Έλεγχος αν το αίτημα είναι POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Λήψη των δεδομένων από τη φόρμα
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Αναζήτηση στον πίνακα drivers
    $sql = "SELECT id, password, is_verified FROM drivers WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Έλεγχος αν ο λογαριασμός έχει επιβεβαιωθεί
        if (!$user['is_verified']) {
            $_SESSION['login_error'] = 'Ο λογαριασμός σας δεν έχει επιβεβαιωθεί. Παρακαλώ ελέγξτε το email σας.';
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
        
        // Επαλήθευση συνθηματικού για οδηγούς
        if (password_verify($password, $user['password'])) {
            // Επιτυχής σύνδεση - επαναφορά του rate limiter
            RateLimiter::reset($ip, 'login');
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'driver';
            header('Location: ' . BASE_URL . 'drivers/driver_profile.php');
            exit();
        } else {
            // Αύξηση του μετρητή προσπαθειών
            RateLimiter::increment($ip, 'login');
            $error = 'Το συνθηματικό δεν είναι σωστό.';
        }
    } else {
        // Αν δεν βρέθηκε στους drivers, αναζήτηση στους companies
        $sql = "SELECT id, password, is_verified FROM companies WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Έλεγχος αν ο λογαριασμός έχει επιβεβαιωθεί
            if (!$user['is_verified']) {
                $_SESSION['login_error'] = 'Ο λογαριασμός σας δεν έχει επιβεβαιωθεί. Παρακαλώ ελέγξτε το email σας.';
                header('Location: ' . BASE_URL . 'login.php');
                exit();
            }
            
            // Επαλήθευση συνθηματικού για εταιρείες
            if (password_verify($password, $user['password'])) {
                // Επιτυχής σύνδεση - επαναφορά του rate limiter
                RateLimiter::reset($ip, 'login');
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'company';
                header('Location: ' . BASE_URL . 'companies/company_profile.php');
                exit();
            } else {
                // Αύξηση του μετρητή προσπαθειών
                RateLimiter::increment($ip, 'login');
                $error = 'Το συνθηματικό δεν είναι σωστό.';
            }
        } else {
            // Αύξηση του μετρητή προσπαθειών
            RateLimiter::increment($ip, 'login');
            $error = 'Δεν βρέθηκε χρήστης με αυτό το email.';
        }
    }

    // Αποθήκευση του μηνύματος σφάλματος στο session
    if (isset($error)) {
        $_SESSION['login_error'] = $error;
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
} else {
    // Αν η μέθοδος δεν είναι POST, ανακατεύθυνση πίσω στη φόρμα
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}
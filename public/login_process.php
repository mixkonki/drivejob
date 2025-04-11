<?php
// Ενεργοποίηση εμφάνισης σφαλμάτων
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Αυτόματη φόρτωση μέσω Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Συμπερίληψη του config.php για τις σταθερές BASE_URL και ROOT_DIR
require_once '../config/config.php';

// Συμπερίληψη του database.php για τη σύνδεση στη βάση δεδομένων
require_once ROOT_DIR . '/config/database.php';

// Ξεκίνημα ή συνέχιση session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Έλεγχος αν το αίτημα είναι POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Λήψη των δεδομένων από τη φόρμα
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Καταγραφή για αποσφαλμάτωση
    file_put_contents('login_debug.log', "Login attempt - Email: $email\n", FILE_APPEND);
    
    // Αναζήτηση στον πίνακα drivers
    $sql = "SELECT * FROM drivers WHERE email = ?";
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
            // Για αποσφαλμάτωση
            file_put_contents('login_debug.log', "Driver login successful - ID: {$user['id']}\n", FILE_APPEND);
            
            // Αποθήκευση στο session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'driver';
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            
            // Ενημέρωση τελευταίας σύνδεσης
            $updateSql = "UPDATE drivers SET last_login = NOW() WHERE id = ?";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([$user['id']]);
            
            // Ανακατεύθυνση στο προφίλ
            header('Location: ' . BASE_URL . 'drivers/driver_profile.php');
            exit();
        } else {
            $error = 'Το συνθηματικό δεν είναι σωστό.';
        }
    } else {
        // Αν δεν βρέθηκε στους drivers, αναζήτηση στους companies
        $sql = "SELECT * FROM companies WHERE email = ?";
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
                // Για αποσφαλμάτωση
                file_put_contents('login_debug.log', "Company login successful - ID: {$user['id']}\n", FILE_APPEND);
                
                // Αποθήκευση στο session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'company';
                $_SESSION['user_name'] = $user['company_name'];
                
                // Ενημέρωση τελευταίας σύνδεσης
                $updateSql = "UPDATE companies SET last_login = NOW() WHERE id = ?";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([$user['id']]);
                
                // Για αποσφαλμάτωση
                file_put_contents('login_debug.log', "Redirecting to: " . BASE_URL . "companies/company_profile.php\n", FILE_APPEND);
                
                // Ανακατεύθυνση στο προφίλ
                header('Location: ' . BASE_URL . 'companies/company_profile.php');
                exit();
            } else {
                $error = 'Το συνθηματικό δεν είναι σωστό.';
            }
        } else {
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
?>
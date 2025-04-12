<?php
// Αυτόματη φόρτωση μέσω Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Συμπερίληψη του config.php για τις σταθερές BASE_URL και ROOT_DIR
require_once '../config/config.php';

// Συμπερίληψη του database.php για τη σύνδεση στη βάση δεδομένων
require_once ROOT_DIR . '/config/database.php';

// Εισαγωγή της κλάσης Session
use Drivejob\Core\Session;

// Αποσφαλμάτωση αρχικής κατάστασης πριν την έναρξη συνεδρίας
file_put_contents('login_debug.log', 
    date('[Y-m-d H:i:s] ') . 
    "Login process started. Session status: " . session_status() . "\n", 
    FILE_APPEND
);

// Καταστροφή προηγούμενης συνεδρίας για καθαρή εκκίνηση
if (session_status() === PHP_SESSION_ACTIVE) {
    session_unset();
    session_destroy();
    file_put_contents('login_debug.log', 
        date('[Y-m-d H:i:s] ') . 
        "Previous session destroyed.\n", 
        FILE_APPEND
    );
}

// Ξεκίνημα νέας συνεδρίας
Session::start();

file_put_contents('login_debug.log', 
    date('[Y-m-d H:i:s] ') . 
    "New session started. Session ID: " . session_id() . "\n", 
    FILE_APPEND
);

// Έλεγχος αν το αίτημα είναι POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Λήψη των δεδομένων από τη φόρμα
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Καταγραφή για αποσφαλμάτωση
    file_put_contents('login_debug.log', 
        date('[Y-m-d H:i:s] ') . 
        "Login attempt - Email: $email\n", 
        FILE_APPEND
    );
    
    // Αναζήτηση στον πίνακα drivers
    $sql = "SELECT * FROM drivers WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    file_put_contents('login_debug.log', 
        date('[Y-m-d H:i:s] ') . 
        "Driver lookup result: " . ($user ? "Found" : "Not found") . "\n", 
        FILE_APPEND
    );
    
    if ($user) {
        // Έλεγχος αν ο λογαριασμός έχει επιβεβαιωθεί
        if (!$user['is_verified']) {
            file_put_contents('login_debug.log', 
                date('[Y-m-d H:i:s] ') . 
                "Driver account not verified.\n", 
                FILE_APPEND
            );
            
            Session::set('login_error', 'Ο λογαριασμός σας δεν έχει επιβεβαιωθεί. Παρακαλώ ελέγξτε το email σας.');
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
        
        // Επαλήθευση συνθηματικού για οδηγούς
        $password_verify_result = password_verify($password, $user['password']);
        file_put_contents('login_debug.log', 
            date('[Y-m-d H:i:s] ') . 
            "Driver password verification: " . ($password_verify_result ? "Success" : "Failed") . "\n", 
            FILE_APPEND
        );
        
        if ($password_verify_result) {
            // Για αποσφαλμάτωση
            file_put_contents('login_debug.log', 
                date('[Y-m-d H:i:s] ') . 
                "Driver login successful - ID: {$user['id']}\n", 
                FILE_APPEND
            );
            
            // Καταγραφή κατάστασης συνεδρίας πριν την ενημέρωση
            file_put_contents('login_debug.log', 
                date('[Y-m-d H:i:s] ') . 
                "Session before update: " . print_r($_SESSION, true) . "\n", 
                FILE_APPEND
            );
            
            // Αποθήκευση στο session
            Session::set('user_id', $user['id']);
            Session::set('role', 'driver');
            Session::set('user_name', $user['first_name'] . ' ' . $user['last_name']);
            
            // Καταγραφή κατάστασης συνεδρίας μετά την ενημέρωση
            file_put_contents('login_debug.log', 
                date('[Y-m-d H:i:s] ') . 
                "Session after update: " . print_r($_SESSION, true) . "\n", 
                FILE_APPEND
            );
            
            // Ενημέρωση τελευταίας σύνδεσης
            $updateSql = "UPDATE drivers SET last_login = NOW() WHERE id = ?";
            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->execute([$user['id']]);
            
            // Ανακατεύθυνση στο προφίλ
            file_put_contents('login_debug.log', 
                date('[Y-m-d H:i:s] ') . 
                "Redirecting to driver profile.\n", 
                FILE_APPEND
            );
            
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
        
        file_put_contents('login_debug.log', 
            date('[Y-m-d H:i:s] ') . 
            "Company lookup result: " . ($user ? "Found" : "Not found") . "\n", 
            FILE_APPEND
        );
        
        if ($user) {
            // Έλεγχος αν ο λογαριασμός έχει επιβεβαιωθεί
            if (!$user['is_verified']) {
                file_put_contents('login_debug.log', 
                    date('[Y-m-d H:i:s] ') . 
                    "Company account not verified.\n", 
                    FILE_APPEND
                );
                
                Session::set('login_error', 'Ο λογαριασμός σας δεν έχει επιβεβαιωθεί. Παρακαλώ ελέγξτε το email σας.');
                header('Location: ' . BASE_URL . 'login.php');
                exit();
            }
            
            // Επαλήθευση συνθηματικού για εταιρείες
            $password_verify_result = password_verify($password, $user['password']);
            file_put_contents('login_debug.log', 
                date('[Y-m-d H:i:s] ') . 
                "Company password verification: " . ($password_verify_result ? "Success" : "Failed") . "\n", 
                FILE_APPEND
            );
            
            if ($password_verify_result) {
                // Για αποσφαλμάτωση
                file_put_contents('login_debug.log', 
                    date('[Y-m-d H:i:s] ') . 
                    "Company login successful - ID: {$user['id']} - Name: {$user['company_name']}\n", 
                    FILE_APPEND
                );
                
                // Καταγραφή κατάστασης συνεδρίας πριν την ενημέρωση
                file_put_contents('login_debug.log', 
                    date('[Y-m-d H:i:s] ') . 
                    "Session before update: " . print_r($_SESSION, true) . "\n", 
                    FILE_APPEND
                );
                
                // Αποθήκευση στο session
                Session::set('user_id', $user['id']);
                Session::set('role', 'company');
                Session::set('user_name', $user['company_name']);
                
                // Καταγραφή κατάστασης συνεδρίας μετά την ενημέρωση
                file_put_contents('login_debug.log', 
                    date('[Y-m-d H:i:s] ') . 
                    "Session after update: " . print_r($_SESSION, true) . "\n", 
                    FILE_APPEND
                );
                
                // Ενημέρωση τελευταίας σύνδεσης
                $updateSql = "UPDATE companies SET last_login = NOW() WHERE id = ?";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([$user['id']]);
                
                // Για αποσφαλμάτωση
                file_put_contents('login_debug.log', 
                    date('[Y-m-d H:i:s] ') . 
                    "Redirecting to: " . BASE_URL . "companies/company_profile.php\n", 
                    FILE_APPEND
                );
                
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
        file_put_contents('login_debug.log', 
            date('[Y-m-d H:i:s] ') . 
            "Login error: $error\n", 
            FILE_APPEND
        );
        
        Session::set('login_error', $error);
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
} else {
    // Αν η μέθοδος δεν είναι POST, ανακατεύθυνση πίσω στη φόρμα
    file_put_contents('login_debug.log', 
        date('[Y-m-d H:i:s] ') . 
        "Invalid request method: " . $_SERVER['REQUEST_METHOD'] . "\n", 
        FILE_APPEND
    );
    
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}
?>
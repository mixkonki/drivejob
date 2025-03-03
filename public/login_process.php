<?php
// Συμπερίληψη του config.php για τις σταθερές BASE_URL και ROOT_DIR
require_once '../config/config.php';

// Συμπερίληψη του database.php για τη σύνδεση στη βάση δεδομένων
require_once ROOT_DIR . '/config/database.php';

session_start(); // Έναρξη της συνεδρίας

// Έλεγχος αν το αίτημα είναι POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Λήψη των δεδομένων από τη φόρμα
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Αναζήτηση στον πίνακα drivers
    $sql = "SELECT id, password FROM drivers WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Επαλήθευση συνθηματικού για οδηγούς
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = 'driver';
            header('Location: ' . BASE_URL . 'drivers/driver_profile.php');
            exit();
        } else {
            $error = 'Το συνθηματικό δεν είναι σωστό.';
        }
    } else {
        // Αν δεν βρέθηκε στους drivers, αναζήτηση στους companies
        $sql = "SELECT id, password FROM companies WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Επαλήθευση συνθηματικού για εταιρείες
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = 'company';
                header('Location: ' . BASE_URL . 'companies/company_profile.php');
                exit();
            } else {
                $error = 'Το συνθηματικό δεν είναι σωστό.';
            }
        } else {
            $error = 'Δεν βρέθηκε χρήστης με αυτό το email.';
        }
    }
} else {
    // Αν η μέθοδος δεν είναι POST, ανακατεύθυνση πίσω στη φόρμα
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Εμφάνιση μηνύματος σφάλματος αν υπάρχει
if (isset($error)) {
    echo "<div class='error'>$error</div>";
}
?>

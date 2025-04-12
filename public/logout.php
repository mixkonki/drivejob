<?php
require_once '../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Drivejob\Core\Session;

// Έναρξη συνεδρίας αν δεν έχει ξεκινήσει
Session::start();

// Αποσφαλμάτωση - καταγραφή της συνεδρίας πριν την καταστροφή
file_put_contents('logout_debug.log', 
    date('[Y-m-d H:i:s] ') . 
    "Logout started - Session before destruction: " . print_r($_SESSION, true) . "\n", 
    FILE_APPEND
);

// Καθαρισμός όλων των μεταβλητών συνεδρίας
$_SESSION = array();

// Διαγραφή του cookie συνεδρίας
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Καταστροφή της συνεδρίας
session_destroy();

file_put_contents('logout_debug.log', 
    date('[Y-m-d H:i:s] ') . 
    "Session destroyed, redirecting to home page\n", 
    FILE_APPEND
);

// Ανακατεύθυνση στην αρχική σελίδα
header('Location: ' . BASE_URL);
exit();

// Αφού ολοκληρωθεί η αποσύνδεση, θα προσφέρουμε την επιλογή επιστροφής στην αρχική ή σύνδεσης ξανά
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Αποσύνδεση</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/styles.css">
</head>
<body>
    <!-- Header -->
    <?php include ROOT_DIR . '/src/Views/header.php'; ?>

    <main>
        <div class="logout-container">
            <h1>Αποσυνδεθήκατε επιτυχώς</h1>
            <p>Έχετε αποσυνδεθεί από τον λογαριασμό σας. Μπορείτε να συνδεθείτε ξανά ή να επιστρέψετε στην αρχική σελίδα.</p>
            <div class="logout-actions">
                <a href="<?php echo BASE_URL; ?>login.php" class="btn-primary">Σύνδεση</a>
                <a href="<?php echo BASE_URL; ?>" class="btn-secondary">Αρχική Σελίδα</a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include ROOT_DIR . '/src/Views/footer.php'; ?>
</body>
</html>
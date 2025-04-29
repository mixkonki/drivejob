<?php
// Συμπερίληψη του config.php για τη χρήση των σταθερών ROOT_DIR και BASE_URL
require_once __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Session;

Session::start();

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php';

// Σύνδεση με το styles.css αν δεν φορτώνεται μέσω header.php
echo '<link rel="stylesheet" href="' . BASE_URL . 'css/styles.css">';

echo '<main>'; // Έναρξη του main

// Έλεγχος αν έχουν δοθεί τα απαιτούμενα GET δεδομένα (email, role)
if (isset($_GET['email'], $_GET['role'])) {
    $email = $_GET['email']; // Λήψη email από το URL
    $role = $_GET['role'];   // Λήψη ρόλου από το URL

    // Επιλογή SQL ανάλογα με τον ρόλο (driver ή company)
    if ($role === 'driver') {
        $sql = "UPDATE drivers SET is_verified = 1 WHERE email = ?";
    } elseif ($role === 'company') {
        $sql = "UPDATE companies SET is_verified = 1 WHERE email = ?";
    } else {
        // Αν ο ρόλος δεν είναι έγκυρος
        echo "<div class='error'>Μη έγκυρος ρόλος.</div>";
        echo '</main>';
        include ROOT_DIR . '/src/Views/footer.php';
        exit();
    }

    // Προετοιμασία και εκτέλεση του SQL ερωτήματος
    $stmt = $pdo->prepare($sql);
    if ($stmt->execute([$email])) {
        // Αν η ενημέρωση στη βάση ήταν επιτυχής
        echo "<div class='container'><h1>Επιβεβαίωση Επιτυχής!</h1><p>Η εγγραφή σας επιβεβαιώθηκε. <a href='" . BASE_URL . "login.php'>Συνδεθείτε</a>.</p></div>";
    } else {
        // Αν απέτυχε η ενημέρωση στη βάση
        echo "<div class='container'><h1>Σφάλμα!</h1><p>Αποτυχία επιβεβαίωσης του email. Παρακαλώ δοκιμάστε ξανά.</p></div>";
    }
} else {
    // Αν δεν υπάρχουν τα απαραίτητα GET δεδομένα
    echo "<div class='container'><h1>Μη έγκυρο Αίτημα!</h1><p>Η επιβεβαίωση δεν είναι δυνατή.</p></div>";
}

echo '</main>'; // Κλείσιμο του main

// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php';
?>
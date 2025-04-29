<?php

// Αρχικοποίηση της εφαρμογής
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../../config/config.php'; // Πρώτα το config.php για να οριστούν οι σταθερές
require_once ROOT_DIR . '/config/database.php'; // Σύνδεση με τη βάση δεδομένων
require_once '../../src/helpers/email_helper.php'; // Εισαγωγή helper για το email
require_once '../../templates/email_template.php'; // Εισαγωγή template για το email

use Drivejob\Core\Session;

Session::start();

include ROOT_DIR . '/src/Views/header.php'; // Header

echo '<main>'; // Έναρξη του main

$errorMessage = ''; // Μεταβλητή για αποθήκευση μηνυμάτων σφάλματος

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Εισαγωγή δεδομένων από τη φόρμα
    $email = $_POST['email'];
    $password = $_POST['password'];
    $company_name = $_POST['company_name'];
    $phone = $_POST['phone'];

    // Ορισμός του ρόλου για τις επιχειρήσεις
    $role = 'company';

    // Έλεγχος αν υπάρχει ήδη η επιχείρηση με το ίδιο email
    $checkSql = "SELECT * FROM companies WHERE email = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$email]);

    if ($checkStmt->rowCount() > 0) {
        $errorMessage = 'Το email υπάρχει ήδη. Παρακαλώ χρησιμοποιήστε άλλο email.';
    } else {
        // Δημιουργία hash για το συνθηματικό
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Εισαγωγή δεδομένων στη βάση
        $sql = "INSERT INTO companies (email, password, company_name, phone) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        if ($stmt->execute([$email, $hashedPassword, $company_name, $phone])) {
            // Δημιουργία συνδέσμου επιβεβαίωσης
            $verificationLink = BASE_URL . "verify.php?email=" . urlencode($email) . "&role=" . $role;

            // Δημιουργία περιεχομένου email μέσω template
            $subject = "Επιβεβαίωση Εγγραφής";
            $message = createEmailTemplate($role, $verificationLink);

            // Αποστολή email
            if (sendEmail($email, $subject, $message)) {
                echo "<div class='success'>Η εγγραφή ήταν επιτυχής. Ελέγξτε το email σας για επιβεβαίωση.</div>";
            } else {
                $errorMessage = 'Η εγγραφή ήταν επιτυχής, αλλά απέτυχε η αποστολή του email επιβεβαίωσης.';
            }
        } else {
            $errorMessage = 'Σφάλμα κατά την εγγραφή. Παρακαλώ δοκιμάστε ξανά.';
        }
    }
} else {
    $errorMessage = 'Μη έγκυρη αίτηση.';
}

// Εμφάνιση μηνύματος σφάλματος αν υπάρχει
if (!empty($errorMessage)) {
    echo "<div class='error'>{$errorMessage}</div>";
}

echo '</main>'; // Κλείσιμο του main

include ROOT_DIR . '/src/Views/footer.php'; // Footer
?>
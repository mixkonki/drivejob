<?php
// Αποθήκευσε αυτό το αρχείο ως public/session_debug.php

// Ενεργοποίηση εμφάνισης σφαλμάτων
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Αυτόματη φόρτωση μέσω Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Συμπερίληψη του config.php για να οριστούν οι σταθερές
require_once __DIR__ . '/../config/config.php';

// Ξεκίνημα ή συνέχιση session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php';
?>

<main>
    <div class="container">
        <h1>Πληροφορίες Συνεδρίας</h1>
        
        <div class="debug-section">
            <h2>ID & Όνομα Συνεδρίας</h2>
            <p><strong>ID Συνεδρίας:</strong> <?php echo session_id(); ?></p>
            <p><strong>Όνομα Συνεδρίας:</strong> <?php echo session_name(); ?></p>
        </div>
        
        <div class="debug-section">
            <h2>Κατάσταση Σύνδεσης</h2>
            <?php if (isset($_SESSION['user_id'])): ?>
                <p><strong>Κατάσταση:</strong> <span style="color: green;">Συνδεδεμένος</span></p>
                <p><strong>ID Χρήστη:</strong> <?php echo $_SESSION['user_id']; ?></p>
                <p><strong>Ρόλος:</strong> <?php echo $_SESSION['role']; ?></p>
                <p><strong>Όνομα:</strong> <?php echo $_SESSION['user_name'] ?? 'Δεν έχει οριστεί'; ?></p>
            <?php else: ?>
                <p><strong>Κατάσταση:</strong> <span style="color: red;">Μη Συνδεδεμένος</span></p>
            <?php endif; ?>
        </div>
        
        <div class="debug-section">
            <h2>Περιεχόμενα Συνεδρίας</h2>
            <pre><?php print_r($_SESSION); ?></pre>
        </div>
        
        <div class="debug-section">
            <h2>Cookies</h2>
            <pre><?php print_r($_COOKIE); ?></pre>
        </div>

        <div class="debug-section">
            <h2>Δοκιμές Συνάρτησης Header</h2>
            <p>Η παρακάτω δοκιμή δείχνει αν η συνάρτηση header() λειτουργεί σωστά.</p>
            <form action="redirect_test.php" method="post">
                <button type="submit">Δοκιμή Ανακατεύθυνσης</button>
            </form>
        </div>
        
        <div class="debug-section">
            <h2>Δοκιμή Δημιουργίας Συνεδρίας</h2>
            <form action="set_session_test.php" method="post">
                <button type="submit">Δημιουργία Δοκιμαστικής Συνεδρίας</button>
            </form>
        </div>
    </div>
</main>

<style>
    .debug-section {
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
    }
    
    .debug-section h2 {
        margin-top: 0;
        padding-bottom: 10px;
        border-bottom: 1px solid #ddd;
    }
    
    pre {
        background-color: #f1f1f1;
        padding: 10px;
        border-radius: 4px;
        overflow-x: auto;
    }
</style>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php';
?>
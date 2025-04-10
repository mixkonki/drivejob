<?php
// Αποθηκεύστε αυτό το αρχείο ως public/session_debug.php

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
            <h2>Κατάσταση Σύνδεσης</h2>
            <?php if (isset($_SESSION['user_id'])): ?>
                <p><strong>Κατάσταση:</strong> <span style="color: green;">Συνδεδεμένος</span></p>
                <p><strong>ID Χρήστη:</strong> <?php echo $_SESSION['user_id']; ?></p>
                <p><strong>Ρόλος:</strong> <?php echo $_SESSION['role']; ?></p>
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
            <h2>Server Info</h2>
            <p><strong>PHP_SELF:</strong> <?php echo $_SERVER['PHP_SELF']; ?></p>
            <p><strong>REQUEST_URI:</strong> <?php echo $_SERVER['REQUEST_URI']; ?></p>
            <p><strong>SCRIPT_NAME:</strong> <?php echo $_SERVER['SCRIPT_NAME']; ?></p>
            <p><strong>REQUEST_METHOD:</strong> <?php echo $_SERVER['REQUEST_METHOD']; ?></p>
        </div>
        
        <div class="debug-section">
            <h2>Προσομοίωση Δημιουργίας Νέας Αγγελίας</h2>
            <p>Δοκιμάστε τους παρακάτω συνδέσμους:</p>
            <ul>
                <li><a href="<?php echo BASE_URL; ?>job-listings/create/">Link 1: job-listings/create/</a></li>
                <li><a href="<?php echo BASE_URL; ?>job-listings/create">Link 2: job-listings/create</a></li>
                <li><a href="<?php echo BASE_URL; ?>job-listings/create.php">Link 3: job-listings/create.php</a></li>
            </ul>
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
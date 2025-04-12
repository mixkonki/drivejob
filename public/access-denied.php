<?php
// public/access-denied.php

// Αρχικοποίηση της εφαρμογής
require_once __DIR__ . '/../src/bootstrap.php';

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php';
?>

<main>
    <div class="container">
        <div class="error-page">
            <h1>Πρόσβαση Άρνησης</h1>
            <h2>Δεν έχετε δικαίωμα πρόσβασης σε αυτή τη σελίδα</h2>
            <p>Δεν έχετε επαρκή δικαιώματα για να προβάλετε το περιεχόμενο που ζητήσατε.</p>
            <a href="<?php echo BASE_URL; ?>" class="btn-primary">Επιστροφή στην αρχική σελίδα</a>
        </div>
    </div>
</main>

<style>
    .error-page {
        text-align: center;
        padding: 50px 0;
    }
    
    .error-page h1 {
        font-size: 48px;
        color: #aa3636;
        margin-bottom: 10px;
    }
    
    .error-page h2 {
        font-size: 28px;
        margin-bottom: 20px;
    }
    
    .error-page p {
        font-size: 18px;
        color: #666;
        margin-bottom: 30px;
    }
</style>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php';
?>
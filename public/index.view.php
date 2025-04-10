<?php

// Αυτόματη φόρτωση μέσω Composer
require_once __DIR__ . '/../vendor/autoload.php';
// Συμπερίληψη του config.php για να οριστούν οι σταθερές
require_once __DIR__ . '/../config/config.php';

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php';
?>

<!-- Κύριο Περιεχόμενο -->
<main>
    <section class="welcome">
        <h1>Καλώς ήρθατε στο DriveJob<br>
        την ψηφιακή πλατφόρμα αγγελιών οδηγών</h1>
        <p>Το DriveJob συνδέει αποτελεσματικά οδηγούς με εταιρείες μέσω αλγορίθμων αντιστοίχισης.</p>
    </section>

    <section class="buttons">
        <a href="./drivers/drivers_registration.php" class="btn-secondary" aria-label="Εγγραφή Οδηγού">
            <img src="<?php echo BASE_URL; ?>img/driver_icon.png" alt="Εικονίδιο Οδηγού">
            Εγγραφή Οδηγού
        </a>
        <a href="./companies/company_registration.php" class="btn-secondary" aria-label="Εγγραφή Επιχείρησης">
        <img src="<?php echo BASE_URL; ?>img/company_icon.png" alt="Εικονίδιο Επιχείρησης">
            Εγγραφή Επιχείρησης
        </a>
    </section>
    <section class="welcome">
        <h1>Λογισμικό Διαχείρισης Ανθρώπινου Δυναμικού</h1>
        <p>Το DriveJobs προσφέρει λογισμικό για τη διαχείριση πληρωμών, αδειών και πιστοποιήσεων. Αξιοποιήστε τη δύναμη των εργαλείων μας για να εξοικονομήσετε χρόνο και να αυξήσετε την παραγωγικότητα.</p>
    </section>
</main>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php';
?>

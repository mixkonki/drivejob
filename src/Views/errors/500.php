<?php
// src/Views/errors/500.php

// Χρήση ROOT_DIR για σωστή διαδρομή
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<div class="container error-container">
    <div class="text-center mt-5">
        <h1 class="display-1">500</h1>
        <h2 class="mb-4">Σφάλμα διακομιστή</h2>
        <p class="lead">Υπήρξε ένα σφάλμα κατά την επεξεργασία του αιτήματός σας.</p>
        <p>Παρακαλώ δοκιμάστε ξανά αργότερα ή επικοινωνήστε με τον διαχειριστή του συστήματος.</p>
        <?php if (isset($message) && !empty($message)): ?>
            <div class="alert alert-danger mt-3">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>" class="btn btn-primary mt-3">Επιστροφή στην αρχική</a>
    </div>
</div>

<?php
// Χρήση ROOT_DIR για σωστή διαδρομή
include ROOT_DIR . '/src/Views/partials/footer.php';
?>
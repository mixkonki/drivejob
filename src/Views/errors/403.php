<?php
// src/Views/errors/403.php

// Χρήση ROOT_DIR για σωστή διαδρομή
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<div class="container error-container">
    <div class="text-center mt-5">
        <h1 class="display-1">403</h1>
        <h2 class="mb-4">Απαγορεύεται η πρόσβαση</h2>
        <p class="lead">Δεν έχετε δικαίωμα πρόσβασης σε αυτή τη σελίδα.</p>
        <p>Παρακαλώ βεβαιωθείτε ότι έχετε συνδεθεί με τον σωστό λογαριασμό ή επιστρέψτε στην αρχική σελίδα.</p>
        <a href="<?php echo BASE_URL; ?>" class="btn btn-primary mt-3">Επιστροφή στην αρχική</a>
    </div>
</div>

<?php
// Χρήση ROOT_DIR για σωστή διαδρομή
include ROOT_DIR . '/src/Views/partials/footer.php';
?>
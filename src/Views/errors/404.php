<?php
// src/Views/errors/404.php

// Χρήση ROOT_DIR για σωστή διαδρομή
include ROOT_DIR . '/src/Views/header.php'; // Διορθώθηκε
?>

<div class="container error-container">
    <div class="text-center mt-5">
        <h1 class="display-1">404</h1>
        <h2 class="mb-4">Η σελίδα δεν βρέθηκε</h2>
        <p class="lead">Η σελίδα που ζητήσατε δεν βρέθηκε στον εξυπηρετητή μας.</p>
        <p>Παρακαλώ βεβαιωθείτε ότι έχετε πληκτρολογήσει σωστά τη διεύθυνση ή επιστρέψτε στην αρχική σελίδα.</p>
        <a href="<?php echo BASE_URL; ?>" class="btn btn-primary mt-3">Επιστροφή στην αρχική</a>
    </div>
</div>

<?php
// Χρήση ROOT_DIR για σωστή διαδρομή
include ROOT_DIR . '/src/Views/footer.php'; // Διορθώθηκε
?>

<?php

/**
 * Αρχείο αποσφαλμάτωσης για τις αγγελίες
 * 
 * Εμφανίζει τα σφάλματα που αποθηκεύονται στο session κατά τη δημιουργία αγγελίας
 */

// Φόρτωση του bootstrap
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Core\Logger;

// Τίτλος σελίδας
$pageTitle = 'Αποσφαλμάτωση Αγγελιών';

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4>Αποσφαλμάτωση Αγγελιών</h4>
                </div>
                <div class="card-body">
                    <h5>Σφάλματα από το Session</h5>
                    <?php if (Session::has('debug_error')): ?>
                        <div class="alert alert-danger">
                            <h6>Σφάλμα:</h6>
                            <pre><?php print_r(Session::get('debug_error')); ?></pre>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Δεν υπάρχουν σφάλματα στο session.
                        </div>
                    <?php endif; ?>

                    <h5>Δεδομένα από το Session</h5>
                    <?php if (Session::has('old_input')): ?>
                        <div class="alert alert-warning">
                            <h6>Δεδομένα Φόρμας:</h6>
                            <pre><?php print_r(Session::get('old_input')); ?></pre>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Δεν υπάρχουν δεδομένα φόρμας στο session.
                        </div>
                    <?php endif; ?>

                    <h5>Σφάλματα Επικύρωσης</h5>
                    <?php if (Session::has('errors')): ?>
                        <div class="alert alert-danger">
                            <h6>Σφάλματα Επικύρωσης:</h6>
                            <pre><?php print_r(Session::get('errors')); ?></pre>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            Δεν υπάρχουν σφάλματα επικύρωσης στο session.
                        </div>
                    <?php endif; ?>

                    <h5>Μηνύματα</h5>
                    <?php if (Session::has('error_message')): ?>
                        <div class="alert alert-danger">
                            <h6>Μήνυμα Σφάλματος:</h6>
                            <p><?php echo Session::get('error_message'); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (Session::has('success_message')): ?>
                        <div class="alert alert-success">
                            <h6>Μήνυμα Επιτυχίας:</h6>
                            <p><?php echo Session::get('success_message'); ?></p>
                        </div>
                    <?php endif; ?>

                    <h5>Πληροφορίες Χρήστη</h5>
                    <?php if (Session::has('user_id') && Session::has('user_role')): ?>
                        <div class="alert alert-info">
                            <h6>Χρήστης:</h6>
                            <p>ID: <?php echo Session::get('user_id'); ?></p>
                            <p>Ρόλος: <?php echo Session::get('user_role'); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <p>Δεν υπάρχει συνδεδεμένος χρήστης.</p>
                        </div>
                    <?php endif; ?>

                    <h5>Όλα τα Δεδομένα του Session</h5>
                    <div class="alert alert-secondary">
                        <pre><?php print_r($_SESSION); ?></pre>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn btn-primary">Επιστροφή στη Δημιουργία Αγγελίας</a>
                    <a href="<?php echo BASE_URL; ?>job-listings" class="btn btn-secondary">Επιστροφή στις Αγγελίες</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>
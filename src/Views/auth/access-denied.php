<?php
// Ορισμός του τίτλου της σελίδας
$pageTitle = 'Άρνηση Πρόσβασης';

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h2 class="text-center">Άρνηση Πρόσβασης</h2>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <h4 class="alert-heading">Δεν έχετε πρόσβαση σε αυτή τη σελίδα!</h4>
                        <p>Δεν έχετε τα απαραίτητα δικαιώματα για να προβάλετε αυτή τη σελίδα.</p>
                        <hr>
                        <p class="mb-0">Παρακαλώ συνδεθείτε με έναν λογαριασμό που έχει τα κατάλληλα δικαιώματα ή επικοινωνήστε με τον διαχειριστή του συστήματος.</p>
                    </div>

                    <div class="text-center mt-4">
                        <a href="<?= BASE_URL ?>" class="btn btn-primary">Επιστροφή στην Αρχική Σελίδα</a>
                        <?php if (!\Drivejob\Core\Session::has('user_id')): ?>
                            <a href="<?= BASE_URL ?>auth/login" class="btn btn-outline-primary ml-2">Σύνδεση</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>
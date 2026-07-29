<?php
// Ορισμός του τίτλου της σελίδας
$pageTitle = 'Απαιτείται Επαλήθευση';

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning">
                    <h2 class="text-center">Απαιτείται Επαλήθευση</h2>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h4 class="alert-heading">Ο λογαριασμός σας δεν έχει επαληθευτεί!</h4>
                        <p>Για να αποκτήσετε πλήρη πρόσβαση στην πλατφόρμα, πρέπει να επαληθεύσετε τη διεύθυνση email σας.</p>
                        <hr>
                        <p class="mb-0">Έχουμε στείλει ένα email επαλήθευσης στη διεύθυνση που δηλώσατε κατά την εγγραφή σας. Παρακαλούμε ελέγξτε τα εισερχόμενά σας και ακολουθήστε τις οδηγίες για να ολοκληρώσετε τη διαδικασία επαλήθευσης.</p>
                    </div>

                    <div class="mt-4">
                        <h5>Δεν λάβατε το email επαλήθευσης;</h5>
                        <p>Αν δεν βρίσκετε το email επαλήθευσης, παρακαλούμε ελέγξτε το φάκελο ανεπιθύμητης αλληλογραφίας (spam) ή ζητήστε να σας αποσταλεί ξανά.</p>

                        <form action="<?= BASE_URL ?>auth/resend-verification" method="post" class="mt-3">
                            <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Αποστολή Νέου Email Επαλήθευσης</button>
                            </div>
                        </form>
                    </div>

                    <div class="text-center mt-4">
                        <a href="<?= BASE_URL ?>" class="btn btn-outline-secondary">Επιστροφή στην Αρχική Σελίδα</a>
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
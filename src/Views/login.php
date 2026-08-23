<?php
// Ορισμός του τίτλου της σελίδας
$pageTitle = 'Σύνδεση';

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="text-center">Σύνδεση</h2>
                </div>
                <div class="card-body">
                    <?php include ROOT_DIR . '/src/Views/partials/alerts.php'; ?>

                    <form action="<?= BASE_URL ?>auth/login" method="post">
                        <input type="hidden" name="csrf_token" value="<?= \Drivejob\Core\CSRF::token() ?>">

                        <div class="form-group mb-3">
                            <label for="email">Διεύθυνση Email</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                placeholder="Εισάγετε το email σας">
                        </div>

                        <div class="form-group mb-3">
                            <label for="password">Συνθηματικό</label>
                            <input type="password" class="form-control" id="password" name="password" required
                                placeholder="Εισάγετε το συνθηματικό σας">
                        </div>

                        <div class="form-group mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Να με θυμάσαι</label>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Σύνδεση</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <p>Ξεχάσατε το συνθηματικό σας; <a href="<?= BASE_URL ?>auth/password-reset">Πατήστε εδώ</a></p>
                    <p>Δεν έχετε λογαριασμό;
                        <a href="<?= BASE_URL ?>drivers/register">Εγγραφή ως Οδηγός</a> |
                        <a href="<?= BASE_URL ?>companies/register">Εγγραφή ως Εταιρεία</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>
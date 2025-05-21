<?php
// Ορισμός του τίτλου της σελίδας
$pageTitle = 'Επαναφορά Συνθηματικού';

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';

// Λήψη του token από το URL
$token = $token ?? '';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="text-center">Επαναφορά Συνθηματικού</h2>
                </div>
                <div class="card-body">
                    <?php include ROOT_DIR . '/src/Views/partials/alerts.php'; ?>

                    <p class="mb-4">Εισάγετε το νέο συνθηματικό σας παρακάτω.</p>

                    <form action="<?= BASE_URL ?>auth/password-reset/<?= htmlspecialchars($token) ?>" method="post">
                        <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                        <div class="form-group mb-3">
                            <label for="password">Νέο Συνθηματικό</label>
                            <input type="password" class="form-control" id="password" name="password" required
                                placeholder="Εισάγετε το νέο συνθηματικό σας" minlength="8">
                            <small class="form-text text-muted">Το συνθηματικό πρέπει να έχει τουλάχιστον 8 χαρακτήρες.</small>
                        </div>

                        <div class="form-group mb-3">
                            <label for="password_confirm">Επιβεβαίωση Συνθηματικού</label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required
                                placeholder="Επιβεβαιώστε το νέο συνθηματικό σας" minlength="8">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Επαναφορά Συνθηματικού</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <p>Θυμηθήκατε το συνθηματικό σας; <a href="<?= BASE_URL ?>auth/login">Επιστροφή στη σελίδα σύνδεσης</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>
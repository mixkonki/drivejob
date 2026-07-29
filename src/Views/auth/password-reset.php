<?php
// Ορισμός του τίτλου της σελίδας
$pageTitle = 'Επαναφορά Συνθηματικού';

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';

echo '<main>'; // Έναρξη του main
?>

<!-- Φόρμα Επαναφοράς Συνθηματικού -->
<div class="container">
    <div class="login-form-container">
        <h1>Επαναφορά Συνθηματικού</h1>

        <?php if (\Drivejob\Core\Session::has('error_message')) : ?>
            <div class="error-message">
                <?php echo \Drivejob\Core\Session::get('error_message'); ?>
                <?php \Drivejob\Core\Session::remove('error_message'); ?>
            </div>
        <?php endif; ?>

        <?php if (\Drivejob\Core\Session::has('success_message')) : ?>
            <div class="success-message">
                <?php echo \Drivejob\Core\Session::get('success_message'); ?>
                <?php \Drivejob\Core\Session::remove('success_message'); ?>
            </div>
        <?php endif; ?>

        <p>Εισάγετε τη διεύθυνση email που χρησιμοποιήσατε κατά την εγγραφή σας και θα σας στείλουμε έναν σύνδεσμο για να επαναφέρετε το συνθηματικό σας.</p>

        <form class="login-form" action="<?= BASE_URL ?>auth/password-reset" method="POST">
            <!-- CSRF token -->
            <?php echo \Drivejob\Core\CSRF::tokenField(); ?>

            <!-- Πεδίο Email -->
            <div>
                <label for="email"></label>
                <input class="login-input" type="email" id="email" name="email" placeholder="Εισάγετε το email σας" required>
            </div>

            <!-- Κουμπί Αποστολής -->
            <button class="login-btn" type="submit">Αποστολή Συνδέσμου Επαναφοράς</button>
        </form>

        <p>Θυμηθήκατε το συνθηματικό σας; <a href="<?= BASE_URL ?>auth/login">Επιστροφή στη σελίδα σύνδεσης</a></p>
    </div>
</div>

<?php
echo '</main>'; // Κλείσιμο του main
include ROOT_DIR . '/src/Views/partials/footer.php'; // Συμπερίληψη του footer
?>
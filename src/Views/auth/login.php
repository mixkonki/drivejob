<?php
// Ορισμός του τίτλου της σελίδας
$pageTitle = 'Σύνδεση';

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';

echo '<main>'; // Έναρξη του main
?>

<!-- Φόρμα Σύνδεσης -->
<div class="container">
    <div class="login-form-container">
        <h1>Σύνδεση</h1>

        <?php if (\Drivejob\Core\Session::has('login_error')) : ?>
            <div class="error-message">
                <?php echo \Drivejob\Core\Session::get('login_error'); ?>
                <?php \Drivejob\Core\Session::remove('login_error'); ?>
            </div>
        <?php endif; ?>

        <?php if (\Drivejob\Core\Session::has('error_message')) : ?>
            <div class="error-message">
                <?php echo \Drivejob\Core\Session::get('error_message'); ?>
                <?php \Drivejob\Core\Session::remove('error_message'); ?>
            </div>
        <?php endif; ?>

        <form class="login-form" action="<?= BASE_URL ?>auth/login" method="POST">
            <!-- CSRF token -->
            <?php echo \Drivejob\Core\CSRF::tokenField(); ?>

            <!-- Πεδίο Email -->
            <div>
                <label for="email"></label>
                <input class="login-input" type="email" id="email" name="email" placeholder="Εισάγετε το email σας" required>
            </div>
            <!-- Πεδίο Συνθηματικού -->
            <div>
                <label for="password"></label>
                <input class="login-input" type="password" id="password" name="password" placeholder="Εισάγετε το συνθηματικό σας" required>
            </div>
            <!-- Κουμπί Σύνδεσης -->
            <button class="login-btn" type="submit">Σύνδεση</button>
        </form>
        <p>Ξεχάσατε το συνθηματικό σας; <a href="<?= BASE_URL ?>auth/password-reset">Πατήστε εδώ</a></p>
        <p>Δεν έχετε λογαριασμό;
            <a href="<?= BASE_URL ?>drivers/register">Εγγραφή ως Οδηγός</a> |
            <a href="<?= BASE_URL ?>companies/register">Εγγραφή ως Εταιρεία</a>
        </p>
    </div>
</div>

<?php
echo '</main>'; // Κλείσιμο του main
include ROOT_DIR . '/src/Views/partials/footer.php'; // Συμπερίληψη του footer
?>
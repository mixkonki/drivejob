<?php
// public/login.php
// Αυτόματη φόρτωση μέσω Composer
require_once __DIR__ . '/../vendor/autoload.php';
// Συμπερίληψη του config.php για σταθερές BASE_URL και ROOT_DIR
require_once '../config/config.php';

use Drivejob\Core\Session;

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php';

echo '<main>'; // Έναρξη του main
?>

<!-- Φόρμα Σύνδεσης -->
<div class="container">
<div class="login-form-container">
        <h1>Σύνδεση</h1>
        
        <?php if (Session::has('login_error')): ?>
            <div class="error-message">
                <?php echo Session::get('login_error'); ?>
                <?php Session::remove('login_error'); ?>
            </div>
        <?php endif; ?>
        
        <form class="login-form" action="login_process.php" method="POST">
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
        <p>Ξεχάσατε το συνθηματικό σας; <a href="password_recovery.php">Πατήστε εδώ</a></p>
    </div>
</div>

<?php
echo '</main>'; // Κλείσιμο του main
include ROOT_DIR . '/src/Views/footer.php'; // Συμπερίληψη του footer
?>
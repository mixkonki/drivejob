<?php
// Συμπερίληψη του config.php για σταθερές BASE_URL και ROOT_DIR
require_once '../config/config.php';

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php';

echo '<main>'; // Έναρξη του main
?>

<!-- Φόρμα Σύνδεσης -->
<div class="container">
<div class="login-form-container">
        <h1>Σύνδεση</h1>
        <form class="login-form" action="login_process.php" method="POST">
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
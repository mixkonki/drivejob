<?php
// Ορισμός του τίτλου της σελίδας
$pageTitle = 'Εγγραφή Επιχείρησης';

// Προσθήκη επιπλέον CSS
$extraCss = ['company-registration.css'];

// Προσθήκη επιπλέον JS
$extraJs = ['company_registration.js'];
?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="DriveJob - Ψηφιακή Πλατφόρμα Πρόσληψης Οδηγών και Επιχειρήσεων.">
    <meta name="keywords" content="εργασία, οδηγοί, εταιρείες, πρόσληψη, πλατφόρμα">
    <meta name="author" content="DriveJob">
    <meta name="csrf-token" content="<?php echo \Drivejob\Core\CSRF::getCurrentToken(); ?>">

    <!-- Δυναμικός τίτλος σελίδας -->
    <title>DriveJob - <?php echo isset($pageTitle) ? $pageTitle : 'Καλώς Ήρθατε'; ?></title>

    <!-- Σύνδεση με το CSS αρχείο -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/company-registration.css">
    <link rel="icon" href="<?php echo BASE_URL; ?>img/favicon.ico" type="image/x-icon">
</head>

<body>

    <div class="container">
        <div class="form-container">
            <h1>Εγγραφή Επιχείρησης</h1>
            <p>Δημιουργήστε το προφίλ της επιχείρησής σας στο <br><strong>DriveJobs</strong> μέσα σε λίγα λεπτά!</p>

            <?php if (\Drivejob\Core\Session::has('error_message')) : ?>
                <div class="error-message">
                    <?php echo \Drivejob\Core\Session::get('error_message'); ?>
                    <?php \Drivejob\Core\Session::remove('error_message'); ?>
                </div>
            <?php endif; ?>

            <div>
                <div class="role_user">
                    <img src="<?= BASE_URL ?>img/company_icon.png" alt="Επιχείρηση">
                    <span>Επιχείρηση</span>
                </div>

                <form action="<?= BASE_URL ?>companies/register" method="POST">
                    <!-- CSRF token -->
                    <?php echo \Drivejob\Core\CSRF::tokenField(); ?>

                    <input type="text" id="company_name" name="company_name" placeholder="Όνομα Εταιρείας" required
                        value="<?= isset($_SESSION['old_input']['company_name']) ? htmlspecialchars($_SESSION['old_input']['company_name']) : '' ?>">

                    <input type="email" id="email" name="email" placeholder="Email Εταιρείας" required
                        value="<?= isset($_SESSION['old_input']['email']) ? htmlspecialchars($_SESSION['old_input']['email']) : '' ?>">

                    <input type="tel" id="phone" name="phone" placeholder="Τηλέφωνο Εταιρείας" required
                        value="<?= isset($_SESSION['old_input']['phone']) ? htmlspecialchars($_SESSION['old_input']['phone']) : '' ?>">

                    <input type="text" id="contact_person" name="contact_person" placeholder="Υπεύθυνος Επικοινωνίας" required
                        value="<?= isset($_SESSION['old_input']['contact_person']) ? htmlspecialchars($_SESSION['old_input']['contact_person']) : '' ?>">

                    <div class="password-visibility">
                        <input type="password" id="password" name="password" placeholder="Συνθηματικό" required>
                        <span class="password-toggle" onclick="togglePasswordVisibility()">
                            <img src="<?= BASE_URL ?>img/eye.png" alt="show/hide password" id="toggleIcon">
                        </span>
                    </div>

                    <div class="password-visibility">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Επιβεβαίωση Συνθηματικού" required>
                        <span class="password-toggle" onclick="toggleConfirmPasswordVisibility()">
                            <img src="<?= BASE_URL ?>img/eye.png" alt="show/hide password" id="toggleConfirmIcon">
                        </span>
                    </div>

                    <p class="text_pass">Το συνθηματικό πρέπει να περιέχει:</p>
                    <ul class="password-hint">
                        <li>8-16 χαρακτήρες</li>
                        <li>1 κεφαλαίο γράμμα</li>
                        <li>1 αριθμός</li>
                        <li>1 ειδικός χαρακτήρας</li>
                    </ul>

                    <hr class="divider">

                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="human_check" required>
                            Δεν είμαι ρομπότ
                        </label>
                        <label>
                            <input type="checkbox" name="terms_check" required>
                            Αποδέχομαι τους <a href="<?= BASE_URL ?>terms" target="_blank">Όρους Χρήσης</a> και την <a href="<?= BASE_URL ?>privacy" target="_blank">Πολιτική Απορρήτου</a>
                        </label>
                    </div>

                    <button type="submit" class="btn-primary">Εγγραφή</button>
                </form>

                <p class="login-link">Έχετε ήδη λογαριασμό; <a href="<?= BASE_URL ?>auth/login">Συνδεθείτε</a></p>
            </div>
        </div>

        <div class="info-box">
            <p>Με την εγγραφή σας σήμερα, θα έχετε πρόσβαση σε όλα τα προϊόντα DriveJobs. Δεν απαιτείται πιστωτική κάρτα!</p>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.src = '<?= BASE_URL ?>img/eye-slash.png';
            } else {
                passwordInput.type = 'password';
                toggleIcon.src = '<?= BASE_URL ?>img/eye.png';
            }
        }

        function toggleConfirmPasswordVisibility() {
            const confirmPasswordInput = document.getElementById('confirm_password');
            const toggleConfirmIcon = document.getElementById('toggleConfirmIcon');

            if (confirmPasswordInput.type === 'password') {
                confirmPasswordInput.type = 'text';
                toggleConfirmIcon.src = '<?= BASE_URL ?>img/eye-slash.png';
            } else {
                confirmPasswordInput.type = 'password';
                toggleConfirmIcon.src = '<?= BASE_URL ?>img/eye.png';
            }
        }
    </script>
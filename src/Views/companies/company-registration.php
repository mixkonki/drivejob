<?php
// Ορισμός του τίτλου της σελίδας
$pageTitle = 'Εγγραφή Επιχείρησης';

/**
 * Οι τιμές που πληκτρολόγησε ο χρήστης σε αποτυχημένη υποβολή.
 *
 * ΓΙΑΤΙ ΔΙΑΒΑΖΟΝΤΑΙ ΕΤΣΙ: το old_input δεν καθαριζόταν ποτέ από τη συνεδρία,
 * και οι δύο φόρμες εγγραφής (οδηγού και επιχείρησης) διάβαζαν το ΙΔΙΟ κλειδί.
 * Αποτέλεσμα: μια αποτυχημένη εγγραφή οδηγού άφηνε το email και το τηλέφωνό
 * του να εμφανίζονται στη φόρμα εγγραφής επιχείρησης — και να παραμένουν εκεί
 * επ' αόριστον, ακόμη και μετά από επιτυχή εγγραφή.
 *
 * Πλέον διαβάζονται μία φορά και σβήνονται αμέσως (flash), και μόνο όσα
 * ανήκουν σε ΑΥΤΗ τη φόρμα.
 */
$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

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
    <?= \Drivejob\Helpers\Asset::css('css/styles.css') ?>
    <?= \Drivejob\Helpers\Asset::css('css/company-registration.css') ?>
    <link rel="icon" href="<?php echo BASE_URL; ?>img/favicon.ico" type="image/x-icon">
</head>

<body>

    <div class="container">
        <div class="form-container">
            <?php include ROOT_DIR . '/src/Views/partials/brand-logo.php'; ?>
            <h1>Εγγραφή Επιχείρησης</h1>
            <p>Δημιουργήστε το προφίλ της επιχείρησής σας στο <br><a href="<?= BASE_URL ?>" class="dj-brand-link"><strong>DriveJobs</strong></a> μέσα σε λίγα λεπτά!</p>

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
                        value="<?= htmlspecialchars($old['company_name'] ?? '') ?>">

                    <input type="email" id="email" name="email" placeholder="Email Εταιρείας" required
                        value="<?= htmlspecialchars($old['email'] ?? '') ?>">

                    <input type="tel" id="phone" name="phone" placeholder="Τηλέφωνο Εταιρείας" required
                        value="<?= htmlspecialchars($old['phone'] ?? '') ?>">

                    <input type="text" id="contact_person" name="contact_person" placeholder="Υπεύθυνος Επικοινωνίας" required
                        value="<?= htmlspecialchars($old['contact_person'] ?? '') ?>">

                    <div class="password-visibility">
                        <input type="password" id="password" name="password" placeholder="Συνθηματικό" required>
                        <?php $passwordFieldId = 'password';
                              include ROOT_DIR . '/src/Views/partials/password-toggle.php'; ?>
                    </div>

                    <div class="password-visibility">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Επιβεβαίωση Συνθηματικού" required>
                        <?php $passwordFieldId = 'confirm_password';
                              include ROOT_DIR . '/src/Views/partials/password-toggle.php'; ?>
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
    </script>
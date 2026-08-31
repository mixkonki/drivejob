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

/**
 * Τα σφάλματα επικύρωσης της προηγούμενης υποβολής.
 *
 * Ο controller τα έγραφε στη συνεδρία και κανένα view δεν τα διάβαζε —
 * κάθε αποτυχία κατέληγε σε σιωπηλή ανακατεύθυνση χωρίς εξήγηση.
 * Βλ. src/Views/drivers/drivers-registration.php.
 */
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);

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
    <link rel="icon" href="<?= \Drivejob\Helpers\Asset::url('img/favicon.ico') ?>" type="image/x-icon">
</head>

<body>

    <div class="container">
        <div class="form-container">
            <?php include ROOT_DIR . '/src/Views/partials/brand-logo.php'; ?>
            <h1>Εγγραφή Επιχείρησης</h1>
            <p>Δημιουργήστε το προφίλ της επιχείρησής σας στο <br><a href="<?= BASE_URL ?>" class="dj-brand-link"><strong>DriveJob</strong></a> μέσα σε λίγα λεπτά!</p>

            <?php if (\Drivejob\Core\Session::has('error_message')) : ?>
                <div class="error-message">
                    <?php echo \Drivejob\Core\Session::get('error_message'); ?>
                    <?php \Drivejob\Core\Session::remove('error_message'); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)) : ?>
                <ul class="error-message dj-errors">
                    <?php foreach ($errors as $message) : ?>
                        <li><?= htmlspecialchars(is_array($message) ? implode(' ', $message) : (string) $message, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div>
                <div class="role_user">
                    <img src="<?= \Drivejob\Helpers\Asset::url('img/company_icon.png') ?>" alt="Επιχείρηση">
                    <span>Επιχείρηση</span>
                </div>

                <form action="<?= BASE_URL ?>companies/register" method="POST">
                    <!-- CSRF token -->
                    <?php echo \Drivejob\Core\CSRF::tokenField(); ?>

                    <input type="text" id="company_name" name="company_name" placeholder="Όνομα Εταιρείας" required
                        value="<?= htmlspecialchars($old['company_name'] ?? '') ?>">

                    <input type="email" id="email" name="email" placeholder="Email Εταιρείας" required autocomplete="email"
                        value="<?= htmlspecialchars($old['email'] ?? '') ?>">

                    <input type="tel" id="phone" name="phone" placeholder="Τηλέφωνο Εταιρείας" required
                        value="<?= htmlspecialchars($old['phone'] ?? '') ?>">

                    <input type="text" id="contact_person" name="contact_person" placeholder="Υπεύθυνος Επικοινωνίας" required
                        value="<?= htmlspecialchars($old['contact_person'] ?? '') ?>">

                    <div class="password-visibility">
                        <input type="password" id="password" name="password" placeholder="Συνθηματικό" required autocomplete="new-password">
                        <?php $passwordFieldId = 'password';
                              include ROOT_DIR . '/src/Views/partials/password-toggle.php'; ?>
                    </div>

                    <div class="password-visibility">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Επιβεβαίωση Συνθηματικού" required autocomplete="new-password">
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

                    <?php
                    // Δόλωμα + χρονικός έλεγχος στη θέση του «Δεν είμαι ρομπότ»,
                    // που δεν ελεγχόταν ποτέ στον server. Βλ. αναλυτικό σχόλιο
                    // στο src/Views/drivers/drivers-registration.php.
                    ?>
                    <div class="dj-trap" aria-hidden="true">
                        <label for="website_url">Μην συμπληρώσεις αυτό το πεδίο</label>
                        <input type="text" id="website_url" name="website_url" tabindex="-1" autocomplete="off">
                    </div>
                    <input type="hidden" name="form_started" value="<?= time() ?>">

                    <div class="checkbox-group">
                        <label class="dj-consent">
                            <input type="checkbox" name="terms_check" required>
                            <span>Αποδέχομαι τους <a href="<?= BASE_URL ?>terms" target="_blank">Όρους Χρήσης</a> και την <a href="<?= BASE_URL ?>privacy" target="_blank">Πολιτική Απορρήτου</a></span>
                        </label>
                    </div>

                    <button type="submit" class="btn-primary">Εγγραφή</button>
                </form>

                <p class="login-link">Έχετε ήδη λογαριασμό; <a href="<?= BASE_URL ?>auth/login">Συνδεθείτε</a></p>
            </div>
        </div>

        <?php /* Οφέλη εταιρείας αντί για γενικόλογο μήνυμα (01/09 — αίτημα Κώστα). */ ?>
        <div class="info-box reg-benefits">
            <h3>Γιατί DriveJob;</h3>
            <ul>
                <li><strong>Υποψήφιοι με αποδείξεις, όχι λόγια.</strong> Κάθε αίτηση
                    έρχεται με το προφίλ προσόντων του οδηγού: διπλώματα, ΠΕΙ, ADR,
                    ταχογράφος, προϋπηρεσία.</li>
                <li><strong>Αγγελία σε 2 λεπτά.</strong> Δηλώνεις τις πραγματικές
                    απαιτήσεις της θέσης — η πλατφόρμα τη δείχνει στους οδηγούς
                    που τις καλύπτουν.</li>
                <li><strong>Αυτόματο ταίριασμα.</strong> Οι κατάλληλοι οδηγοί βλέπουν
                    τη θέση σου ψηλά στα ταιριάσματά τους — χωρίς να τη χάνουν
                    σε γενικές λίστες.</li>
                <li><strong>Όλα σε ένα σημείο.</strong> Αιτήσεις, προεπιλογή,
                    συνομιλία με τους υποψηφίους — μέσα στην πλατφόρμα.</li>
                <li><strong>Δωρεάν στη φάση beta.</strong> Χωρίς πιστωτική κάρτα,
                    χωρίς δεσμεύσεις.</li>
            </ul>
        </div>

        <style>
            .reg-benefits h3 { margin: 0 0 .8rem; font-size: 1.1rem; color: var(--dj-brand, #aa3636); }
            .reg-benefits ul { list-style: none; margin: 0; padding: 0; text-align: left; }
            .reg-benefits li { margin-bottom: .75rem; line-height: 1.5; color: var(--dj-ink-soft, #374151); padding-left: 1.4em; position: relative; }
            .reg-benefits li::before { content: '✓'; position: absolute; left: 0; color: var(--dj-ok, #15803d); font-weight: 700; }
        </style>
    </div>

    <script>
    </script>
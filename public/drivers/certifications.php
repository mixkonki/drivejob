<?php

// Αρχικοποίηση της εφαρμογής
$container = require_once __DIR__ . '/../../src/bootstrap.php';

// Λήψη του PDO από το container
$pdo = $container->get('pdo');

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι οδηγός
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Λήψη του ID του οδηγού
$driverId = $_SESSION['user_id'];

// Δημιουργία του service για τα προφίλ οδηγών
$driverProfileService = new \Drivejob\Services\DriverProfileService($pdo);

// Λήψη του προφίλ του οδηγού
$driverProfile = $driverProfileService->getDriverProfile($driverId);

if (!$driverProfile) {
    $_SESSION['error_message'] = 'Δεν βρέθηκε το προφίλ του οδηγού.';
    header('Location: ' . BASE_URL . 'drivers/edit-profile.php');
    exit();
}

// Λήψη των πιστοποιητικών εκπαίδευσης του οδηγού
$driverCertifications = $driverProfile['certifications'] ?? [];

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/certifications.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/form-buttons-fix.css">
<script src="<?php echo BASE_URL; ?>js/certifications.js"></script>

<main>
    <div class="container">
        <h1>Διαχείριση Πιστοποιητικών Εκπαίδευσης</h1>

        <?php if (isset($_SESSION['success_message'])) : ?>
            <div class="success-message">
                <?php echo $_SESSION['success_message']; ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])) : ?>
            <div class="error-message">
                <?php echo $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <div class="form-description">
            <p>Σε αυτή τη σελίδα μπορείτε να διαχειριστείτε τα πιστοποιητικά εκπαίδευσης και τα σεμινάρια που έχετε παρακολουθήσει. Τα πιστοποιητικά αυτά θα εμφανίζονται στο προφίλ σας και θα είναι ορατά στους εργοδότες.</p>
        </div>

        <form action="<?php echo BASE_URL; ?>drivers/update-certifications.php" method="POST" id="certificationsForm" enctype="multipart/form-data">
            <?php echo \Drivejob\Core\CSRF::tokenField(); ?>

            <!-- Φόρμα πιστοποιητικών -->
            <?php include ROOT_DIR . '/src/Views/drivers/certification-form.php'; ?>

            <!-- Κουμπιά αποθήκευσης και ακύρωσης -->
            <div class="form-actions">
                <div class="form-buttons">
                    <button type="submit" class="btn-primary btn-save">Αποθήκευση Αλλαγών</button>
                    <a href="<?php echo BASE_URL; ?>drivers/edit-profile.php" class="btn-secondary">Επιστροφή στο Προφίλ</a>
                </div>
            </div>
        </form>

        <!-- Διαγνωστικές πληροφορίες -->
        <?php if (isset($_GET['debug']) && $_GET['debug'] === '1') : ?>
            <div class="debug-info">
                <h3>Διαγνωστικές Πληροφορίες</h3>
                <pre><?php print_r($driverCertifications); ?></pre>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>
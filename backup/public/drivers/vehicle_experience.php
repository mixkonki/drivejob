<?php
// Αρχικοποίηση της εφαρμογής
$container = require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Services\DriverProfileService;

// Λήψη του PDO από το container
$pdo = $container->get('pdo');

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι οδηγός
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Λήψη του ID του οδηγού
$driverId = $_SESSION['user_id'];

// Δημιουργία της υπηρεσίας προφίλ οδηγού
$driverProfileService = new DriverProfileService($pdo);

// Λήψη πλήρους προφίλ του οδηγού
$driverProfile = $driverProfileService->getDriverProfile($driverId);

if (!$driverProfile) {
    Session::set('error_message', 'Τα στοιχεία του οδηγού δεν βρέθηκαν.');
    header('Location: ' . BASE_URL);
    exit();
}

// Αντιστοίχιση μεταβλητών για συμβατότητα με το view
$driverData = $driverProfile;
$driverVehicleExperience = $driverProfile['vehicle_experience'] ?? [];

// Τίτλος σελίδας
$pageTitle = 'Προϋπηρεσία σε Οχήματα';

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver_profile.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver_edit_profile.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/vehicle-experience.css">
<script src="<?php echo BASE_URL; ?>js/console-display.js"></script>
<script src="<?php echo BASE_URL; ?>js/vehicle-experience.js"></script>

<main>
    <div class="container">
        <h1>Προϋπηρεσία σε Οχήματα</h1>

        <div class="breadcrumbs">
            <a href="<?php echo BASE_URL; ?>drivers/driver_profile">Προφίλ</a> &gt;
            <a href="<?php echo BASE_URL; ?>drivers/edit_profile">Επεξεργασία Προφίλ</a> &gt;
            <span>Προϋπηρεσία σε Οχήματα</span>
            <div style="float: right;">
                <a href="<?php echo BASE_URL; ?>drivers/diagnostics_help.php" class="btn-secondary" style="font-size: 12px; padding: 5px 10px;">
                    <i class="fas fa-question-circle"></i> Βοήθεια Διαγνωστικών
                </a>
            </div>
        </div>

        <?php if (Session::has('confirm_delete_experience')): ?>
            <div class="alert alert-warning">
                <p><strong>Προσοχή!</strong> Δεν έχετε προσθέσει καμία εγγραφή προϋπηρεσίας. Αν συνεχίσετε, όλες οι υπάρχουσες εγγραφές προϋπηρεσίας θα διαγραφούν.</p>
                <div style="margin-top: 10px;">
                    <form action="<?php echo BASE_URL; ?>drivers/update_vehicle_experience.php" method="POST">
                        <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
                        <input type="hidden" name="confirm_delete" value="1">
                        <button type="submit" class="btn-danger">Διαγραφή όλων των εγγραφών</button>
                        <a href="<?php echo BASE_URL; ?>drivers/vehicle_experience" class="btn-secondary">Ακύρωση</a>
                    </form>
                </div>
            </div>
            <?php Session::remove('confirm_delete_experience'); ?>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>drivers/update_vehicle_experience.php" method="POST" enctype="multipart/form-data" id="vehicleExperienceForm">
            <?php echo \Drivejob\Core\CSRF::tokenField(); ?>

            <!-- Κουμπιά αποθήκευσης και ακύρωσης (πάνω) -->
            <div class="form-actions top-actions">
                <div class="form-buttons">
                    <button type="submit" class="btn-primary btn-save">Αποθήκευση Αλλαγών</button>
                    <a href="<?php echo BASE_URL; ?>drivers/edit_profile" class="btn-secondary">Επιστροφή στην Επεξεργασία Προφίλ</a>
                </div>
            </div>

            <!-- Ενσωμάτωση του vehicle_experience.php -->
            <?php include ROOT_DIR . '/src/Views/drivers/vehicle_experience.php'; ?>
        </form>
    </div>
</main>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>
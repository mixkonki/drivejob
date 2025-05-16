<?php
// Αρχικοποίηση της εφαρμογής
$container = require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Services\DriverProfileService;
use Drivejob\Core\Logger;

// Ενεργοποίηση καταγραφής σφαλμάτων
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Αρχικοποίηση του Logger
Logger::init();
Logger::info("Εκτέλεση του debug_vehicle_experience.php", "VehicleExperience");

// Λήψη του PDO από το container
$pdo = $container->get('pdo');

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι οδηγός
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    Logger::error("Μη εξουσιοδοτημένη πρόσβαση: user_id=" . ($_SESSION['user_id'] ?? 'null') . ", role=" . ($_SESSION['role'] ?? 'null'), "VehicleExperience");
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Λήψη του ID του οδηγού
$driverId = $_SESSION['user_id'];
Logger::info("Driver ID: $driverId", "VehicleExperience");

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
$pageTitle = 'Διαγνωστικά Προϋπηρεσίας σε Οχήματα';

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver_profile.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver_edit_profile.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/vehicle-experience.css">
<script src="<?php echo BASE_URL; ?>js/console-display.js"></script>
<script src="<?php echo BASE_URL; ?>js/vehicle-experience.js"></script>

<style>
    .debug-section {
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
    }
    .debug-section h3 {
        margin-top: 0;
        color: #333;
    }
    .debug-data {
        background-color: #fff;
        border: 1px solid #eee;
        border-radius: 3px;
        padding: 10px;
        max-height: 300px;
        overflow-y: auto;
        font-family: monospace;
        font-size: 12px;
        white-space: pre-wrap;
    }
    .debug-form {
        margin-top: 20px;
        padding: 15px;
        background-color: #e9ecef;
        border-radius: 5px;
    }
    .debug-form textarea {
        width: 100%;
        height: 150px;
        font-family: monospace;
        margin-bottom: 10px;
    }
    .debug-form button {
        padding: 8px 16px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .debug-form button:hover {
        background-color: #0069d9;
    }
    .debug-log {
        background-color: #f5f5f5;
        border: 1px solid #ddd;
        border-radius: 3px;
        padding: 10px;
        max-height: 400px;
        overflow-y: auto;
        font-family: monospace;
        font-size: 12px;
        white-space: pre-wrap;
        margin-top: 20px;
    }
    .debug-log-entry {
        margin-bottom: 5px;
        padding-bottom: 5px;
        border-bottom: 1px dotted #ccc;
    }
    .debug-log-entry.error {
        color: #dc3545;
    }
    .debug-log-entry.warning {
        color: #ffc107;
    }
    .debug-log-entry.info {
        color: #17a2b8;
    }
</style>

<main>
    <div class="container">
        <h1>Διαγνωστικά Προϋπηρεσίας σε Οχήματα</h1>

        <div class="breadcrumbs">
            <a href="<?php echo BASE_URL; ?>drivers/driver_profile">Προφίλ</a> &gt;
            <a href="<?php echo BASE_URL; ?>drivers/edit_profile">Επεξεργασία Προφίλ</a> &gt;
            <a href="<?php echo BASE_URL; ?>drivers/vehicle_experience">Προϋπηρεσία σε Οχήματα</a> &gt;
            <span>Διαγνωστικά</span>
        </div>

        <?php if (Session::has('error_message')): ?>
            <div class="alert alert-danger">
                <?php echo Session::get('error_message'); ?>
                <?php Session::remove('error_message'); ?>
            </div>
        <?php endif; ?>

        <?php if (Session::has('success_message')): ?>
            <div class="alert alert-success">
                <?php echo Session::get('success_message'); ?>
                <?php Session::remove('success_message'); ?>
            </div>
        <?php endif; ?>

        <!-- Ενότητα διαγνωστικών -->
        <div class="debug-section">
            <h3>Τρέχοντα Δεδομένα Προϋπηρεσίας</h3>
            <div class="debug-data">
                <?php
                if (empty($driverVehicleExperience)) {
                    echo "Δεν υπάρχουν καταχωρημένα δεδομένα προϋπηρεσίας.";
                } else {
                    echo "<pre>";
                    print_r($driverVehicleExperience);
                    echo "</pre>";
                }
                ?>
            </div>
        </div>

        <!-- Ενότητα ελέγχου βάσης δεδομένων -->
        <div class="debug-section">
            <h3>Έλεγχος Πίνακα Βάσης Δεδομένων</h3>
            <div class="debug-data">
                <?php
                try {
                    // Έλεγχος δομής πίνακα
                    $sql = "DESCRIBE driver_vehicle_experience";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute();
                    $tableStructure = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    echo "<h4>Δομή Πίνακα:</h4>";
                    echo "<pre>";
                    print_r($tableStructure);
                    echo "</pre>";

                    // Έλεγχος εγγραφών για τον οδηγό
                    $sql = "SELECT * FROM driver_vehicle_experience WHERE driver_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$driverId]);
                    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    echo "<h4>Εγγραφές για τον οδηγό (ID: $driverId):</h4>";
                    if (empty($records)) {
                        echo "Δεν βρέθηκαν εγγραφές στη βάση δεδομένων.";
                    } else {
                        echo "<pre>";
                        print_r($records);
                        echo "</pre>";
                    }
                } catch (PDOException $e) {
                    echo "Σφάλμα κατά τον έλεγχο της βάσης δεδομένων: " . $e->getMessage();
                }
                ?>
            </div>
        </div>

        <!-- Ενότητα αρχείων καταγραφής -->
        <div class="debug-section">
            <h3>Αρχεία Καταγραφής</h3>
            <div class="debug-log">
                <?php
                $logFile = ROOT_DIR . '/logs/vehicle_experience_debug.log';
                if (file_exists($logFile)) {
                    $logContent = file_get_contents($logFile);
                    echo nl2br(htmlspecialchars($logContent));
                } else {
                    echo "Το αρχείο καταγραφής δεν υπάρχει.";
                }
                ?>
            </div>
        </div>

        <!-- Φόρμα δοκιμαστικής αποθήκευσης -->
        <div class="debug-section">
            <h3>Δοκιμαστική Αποθήκευση</h3>
            <div class="debug-form">
                <form action="<?php echo BASE_URL; ?>drivers/apply_vehicle_experience_fix.php" method="POST">
                    <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
                    <input type="hidden" name="driver_id" value="<?php echo $driverId; ?>">
                    
                    <div class="form-group">
                        <label for="test_data">Δεδομένα JSON για δοκιμή (Προϋπηρεσία Οχημάτων):</label>
                        <textarea id="test_data" name="test_data" class="form-control">[
    {
        "vehicle_category": "rigid_truck",
        "vehicle_type": "distribution_truck",
        "transport_type": "freight",
        "employment_type": "employee",
        "years": 2,
        "months": 6,
        "days": 15,
        "start_date": "2020-01-01",
        "end_date": "2022-07-15",
        "description": "Οδηγός φορτηγού διανομών"
    },
    {
        "vehicle_category": "articulated",
        "vehicle_type": "curtainsider",
        "transport_type": "freight",
        "employment_type": "employee",
        "years": 1,
        "months": 3,
        "days": 0,
        "start_date": "2022-08-01",
        "end_date": "2023-11-01",
        "description": "Οδηγός επικαθήμενου με μουσαμά"
    }
]</textarea>
                    </div>
                    
                    <button type="submit" class="btn-primary">Εφαρμογή Δοκιμαστικών Δεδομένων</button>
                </form>
            </div>
        </div>

        <!-- Σύνδεσμοι ενεργειών -->
        <div class="form-actions">
            <div class="form-buttons">
                <a href="<?php echo BASE_URL; ?>drivers/vehicle_experience" class="btn-primary">Επιστροφή στην Προϋπηρεσία</a>
                <a href="<?php echo BASE_URL; ?>drivers/edit_profile" class="btn-secondary">Επιστροφή στην Επεξεργασία Προφίλ</a>
            </div>
        </div>
    </div>
</main>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php';
?>

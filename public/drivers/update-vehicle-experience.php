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
Logger::info("Εκτέλεση του update_vehicle_experience.php", "VehicleExperience");

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

// Δημιουργία αρχείου καταγραφής για διαγνωστικούς σκοπούς
$logFile = ROOT_DIR . '/logs/vehicle_experience_debug.log';
file_put_contents($logFile, "=== " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
file_put_contents($logFile, "Driver ID: $driverId\n", FILE_APPEND);
file_put_contents($logFile, "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
file_put_contents($logFile, "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

// Έλεγχος αν η φόρμα υποβλήθηκε με τη μέθοδο POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Logger::info("Υποβολή φόρμας με μέθοδο POST", "VehicleExperience");
    file_put_contents($logFile, "Υποβολή φόρμας με μέθοδο POST\n", FILE_APPEND);

    // Έλεγχος CSRF token
    if (!isset($_POST['csrf_token']) || !\Drivejob\Core\CSRF::validateToken($_POST['csrf_token'])) {
        Logger::error("Μη έγκυρο CSRF token", "VehicleExperience");
        file_put_contents($logFile, "Μη έγκυρο CSRF token\n", FILE_APPEND);
        Session::set('error_message', 'Μη έγκυρο CSRF token. Παρακαλώ δοκιμάστε ξανά.');
        header('Location: ' . BASE_URL . 'drivers/vehicle_experience');
        exit();
    }

    // Δημιουργία της υπηρεσίας προφίλ οδηγού
    $driverProfileService = new DriverProfileService($pdo);

    // Έλεγχος αν ο χρήστης επιβεβαίωσε τη διαγραφή των υπαρχόντων δεδομένων
    $confirmDelete = isset($_POST['confirm_delete']) && $_POST['confirm_delete'] == '1';

    // Λήψη των δεδομένων προϋπηρεσίας από τη φόρμα
    $vehicleExperience = $_POST['vehicle_experience'] ?? [];
    Logger::info("Δεδομένα προϋπηρεσίας: " . print_r($vehicleExperience, true), "VehicleExperience");
    file_put_contents($logFile, "Δεδομένα προϋπηρεσίας: " . print_r($vehicleExperience, true) . "\n", FILE_APPEND);

    // Έλεγχος αν υπάρχουν δεδομένα προϋπηρεσίας ή αν ο χρήστης επιβεβαίωσε τη διαγραφή
    if (empty($vehicleExperience) && !$confirmDelete) {
        // Έλεγχος αν υπάρχουν ήδη δεδομένα προϋπηρεσίας στη βάση
        $existingExperience = $driverProfileService->getDriverProfile($driverId)['vehicle_experience'] ?? [];

        if (!empty($existingExperience)) {
            // Αν υπάρχουν ήδη δεδομένα και ο χρήστης υποβάλλει κενή φόρμα,
            // ρωτάμε αν θέλει να διαγράψει τα υπάρχοντα δεδομένα
            Logger::warning("Υποβολή κενής φόρμας ενώ υπάρχουν ήδη δεδομένα προϋπηρεσίας", "VehicleExperience");
            file_put_contents($logFile, "Υποβολή κενής φόρμας ενώ υπάρχουν ήδη δεδομένα προϋπηρεσίας\n", FILE_APPEND);

            // Αποθήκευση του μηνύματος στο session και ανακατεύθυνση
            Session::set('warning_message', 'Δεν υπάρχουν δεδομένα προϋπηρεσίας για αποθήκευση. Αν συνεχίσετε, όλες οι υπάρχουσες εγγραφές προϋπηρεσίας θα διαγραφούν.');
            Session::set('confirm_delete_experience', true);
            header('Location: ' . BASE_URL . 'drivers/vehicle_experience');
            exit();
        } else {
            // Αν δεν υπάρχουν ήδη δεδομένα, απλά ενημερώνουμε τον χρήστη
            Logger::warning("Δεν υπάρχουν δεδομένα προϋπηρεσίας για αποθήκευση", "VehicleExperience");
            file_put_contents($logFile, "Δεν υπάρχουν δεδομένα προϋπηρεσίας για αποθήκευση\n", FILE_APPEND);
            Session::set('warning_message', 'Δεν υπάρχουν δεδομένα προϋπηρεσίας για αποθήκευση.');
            header('Location: ' . BASE_URL . 'drivers/vehicle_experience');
            exit();
        }
    }

    // Αν ο χρήστης επιβεβαίωσε τη διαγραφή, καταγράφουμε το γεγονός
    if ($confirmDelete) {
        Logger::info("Ο χρήστης επιβεβαίωσε τη διαγραφή των υπαρχόντων δεδομένων προϋπηρεσίας", "VehicleExperience");
        file_put_contents($logFile, "Ο χρήστης επιβεβαίωσε τη διαγραφή των υπαρχόντων δεδομένων προϋπηρεσίας\n", FILE_APPEND);
    }

    try {
        // Ενημέρωση της προϋπηρεσίας του οδηγού
        $result = $driverProfileService->updateDriverVehicleExperience($driverId, $vehicleExperience);
        Logger::info("Αποτέλεσμα ενημέρωσης: " . ($result ? 'Επιτυχία' : 'Αποτυχία'), "VehicleExperience");
        file_put_contents($logFile, "Αποτέλεσμα ενημέρωσης: " . ($result ? 'Επιτυχία' : 'Αποτυχία') . "\n", FILE_APPEND);

        if ($result) {
            // Επιτυχής ενημέρωση
            Logger::info("Επιτυχής ενημέρωση προϋπηρεσίας", "VehicleExperience");
            file_put_contents($logFile, "Επιτυχής ενημέρωση προϋπηρεσίας\n", FILE_APPEND);
            Session::set('success_message', 'Η προϋπηρεσία σας ενημερώθηκε επιτυχώς.');
            header('Location: ' . BASE_URL . 'drivers/vehicle_experience');
            exit();
        } else {
            // Αποτυχία ενημέρωσης
            Logger::error("Αποτυχία ενημέρωσης προϋπηρεσίας", "VehicleExperience");
            file_put_contents($logFile, "Αποτυχία ενημέρωσης προϋπηρεσίας\n", FILE_APPEND);
            Session::set('error_message', 'Παρουσιάστηκε σφάλμα κατά την ενημέρωση της προϋπηρεσίας σας. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'drivers/vehicle_experience');
            exit();
        }
    } catch (Exception $e) {
        // Καταγραφή του σφάλματος
        Logger::error("Εξαίρεση κατά την ενημέρωση προϋπηρεσίας: " . $e->getMessage(), "VehicleExperience");
        file_put_contents($logFile, "Εξαίρεση κατά την ενημέρωση προϋπηρεσίας: " . $e->getMessage() . "\n", FILE_APPEND);
        file_put_contents($logFile, "Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);

        // Ενημέρωση του χρήστη
        Session::set('error_message', 'Παρουσιάστηκε σφάλμα κατά την ενημέρωση της προϋπηρεσίας σας: ' . $e->getMessage());
        header('Location: ' . BASE_URL . 'drivers/vehicle_experience');
        exit();
    }
} else {
    // Αν η φόρμα δεν υποβλήθηκε με τη μέθοδο POST, ανακατεύθυνση στη σελίδα προϋπηρεσίας
    Logger::warning("Η φόρμα δεν υποβλήθηκε με τη μέθοδο POST", "VehicleExperience");
    file_put_contents($logFile, "Η φόρμα δεν υποβλήθηκε με τη μέθοδο POST\n", FILE_APPEND);
    header('Location: ' . BASE_URL . 'drivers/vehicle_experience');
    exit();
}

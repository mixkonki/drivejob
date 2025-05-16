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
Logger::info("Εκτέλεση του apply_vehicle_experience_fix.php", "VehicleExperience");

// Δημιουργία αρχείου καταγραφής για διαγνωστικούς σκοπούς
$logFile = ROOT_DIR . '/logs/vehicle_experience_fix.log';
file_put_contents($logFile, "=== " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
file_put_contents($logFile, "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
file_put_contents($logFile, "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);

// Λήψη του PDO από το container
$pdo = $container->get('pdo');

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι οδηγός
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    Logger::error("Μη εξουσιοδοτημένη πρόσβαση: user_id=" . ($_SESSION['user_id'] ?? 'null') . ", role=" . ($_SESSION['role'] ?? 'null'), "VehicleExperience");
    file_put_contents($logFile, "Μη εξουσιοδοτημένη πρόσβαση\n", FILE_APPEND);
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Λήψη του ID του οδηγού
$driverId = $_SESSION['user_id'];
if (isset($_POST['driver_id']) && is_numeric($_POST['driver_id'])) {
    $driverId = intval($_POST['driver_id']);
}
Logger::info("Driver ID: $driverId", "VehicleExperience");
file_put_contents($logFile, "Driver ID: $driverId\n", FILE_APPEND);

// Έλεγχος CSRF token
if (!isset($_POST['csrf_token']) || !\Drivejob\Core\CSRF::validateToken($_POST['csrf_token'])) {
    Logger::error("Μη έγκυρο CSRF token", "VehicleExperience");
    file_put_contents($logFile, "Μη έγκυρο CSRF token\n", FILE_APPEND);
    Session::set('error_message', 'Μη έγκυρο CSRF token. Παρακαλώ δοκιμάστε ξανά.');
    header('Location: ' . BASE_URL . 'drivers/debug_vehicle_experience.php');
    exit();
}

// Δημιουργία της υπηρεσίας προφίλ οδηγού
$driverProfileService = new DriverProfileService($pdo);

// Έλεγχος αν υπάρχουν δεδομένα JSON για δοκιμή
if (isset($_POST['test_data']) && !empty($_POST['test_data'])) {
    try {
        // Αποκωδικοποίηση των δεδομένων JSON
        $vehicleExperience = json_decode($_POST['test_data'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Σφάλμα αποκωδικοποίησης JSON: " . json_last_error_msg());
        }
        
        file_put_contents($logFile, "Αποκωδικοποιημένα δεδομένα JSON: " . print_r($vehicleExperience, true) . "\n", FILE_APPEND);
        Logger::info("Αποκωδικοποιημένα δεδομένα JSON: " . print_r($vehicleExperience, true), "VehicleExperience");
        
        // Έλεγχος της δομής του πίνακα driver_vehicle_experience
        try {
            $sql = "DESCRIBE driver_vehicle_experience";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $tableStructure = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            file_put_contents($logFile, "Δομή πίνακα: " . print_r($tableStructure, true) . "\n", FILE_APPEND);
            Logger::info("Δομή πίνακα: " . print_r($tableStructure, true), "VehicleExperience");
            
            // Έλεγχος αν υπάρχουν όλα τα απαραίτητα πεδία
            $requiredFields = ['driver_id', 'vehicle_category', 'vehicle_type', 'transport_type', 'employment_type', 
                              'years', 'months', 'days', 'start_date', 'end_date', 'description'];
            $missingFields = [];
            
            $tableFields = array_column($tableStructure, 'Field');
            foreach ($requiredFields as $field) {
                if (!in_array($field, $tableFields)) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                file_put_contents($logFile, "Λείπουν τα εξής πεδία από τον πίνακα: " . implode(', ', $missingFields) . "\n", FILE_APPEND);
                Logger::warning("Λείπουν τα εξής πεδία από τον πίνακα: " . implode(', ', $missingFields), "VehicleExperience");
                
                // Προσθήκη των πεδίων που λείπουν
                foreach ($missingFields as $field) {
                    $alterSql = "";
                    switch ($field) {
                        case 'transport_type':
                            $alterSql = "ALTER TABLE driver_vehicle_experience ADD COLUMN transport_type ENUM('freight', 'passenger') NOT NULL DEFAULT 'freight' AFTER vehicle_type";
                            break;
                        case 'employment_type':
                            $alterSql = "ALTER TABLE driver_vehicle_experience ADD COLUMN employment_type ENUM('own_business', 'employee', 'contractor') NOT NULL DEFAULT 'employee' AFTER transport_type";
                            break;
                        // Προσθήκη άλλων περιπτώσεων αν χρειάζεται
                    }
                    
                    if (!empty($alterSql)) {
                        try {
                            $pdo->exec($alterSql);
                            file_put_contents($logFile, "Προστέθηκε το πεδίο $field στον πίνακα\n", FILE_APPEND);
                            Logger::info("Προστέθηκε το πεδίο $field στον πίνακα", "VehicleExperience");
                        } catch (PDOException $e) {
                            file_put_contents($logFile, "Σφάλμα κατά την προσθήκη του πεδίου $field: " . $e->getMessage() . "\n", FILE_APPEND);
                            Logger::error("Σφάλμα κατά την προσθήκη του πεδίου $field: " . $e->getMessage(), "VehicleExperience");
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            file_put_contents($logFile, "Σφάλμα κατά τον έλεγχο της δομής του πίνακα: " . $e->getMessage() . "\n", FILE_APPEND);
            Logger::error("Σφάλμα κατά τον έλεγχο της δομής του πίνακα: " . $e->getMessage(), "VehicleExperience");
        }
        
        // Διαγραφή προηγούμενων εγγραφών
        try {
            $sql = "DELETE FROM driver_vehicle_experience WHERE driver_id = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$driverId]);
            
            file_put_contents($logFile, "Διαγραφή προηγούμενων εγγραφών: " . ($result ? 'Επιτυχία' : 'Αποτυχία') . "\n", FILE_APPEND);
            Logger::info("Διαγραφή προηγούμενων εγγραφών: " . ($result ? 'Επιτυχία' : 'Αποτυχία'), "VehicleExperience");
        } catch (PDOException $e) {
            file_put_contents($logFile, "Σφάλμα κατά τη διαγραφή προηγούμενων εγγραφών: " . $e->getMessage() . "\n", FILE_APPEND);
            Logger::error("Σφάλμα κατά τη διαγραφή προηγούμενων εγγραφών: " . $e->getMessage(), "VehicleExperience");
        }
        
        // Εισαγωγή νέων εγγραφών
        $sql = "INSERT INTO driver_vehicle_experience (
            driver_id, vehicle_category, vehicle_type, transport_type, employment_type,
            years, months, days, start_date, end_date, description
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $insertCount = 0;
        $errorCount = 0;
        
        foreach ($vehicleExperience as $exp) {
            // Παραλείπουμε εγγραφές χωρίς επιλεγμένη κατηγορία οχήματος
            if (empty($exp['vehicle_category'])) {
                continue;
            }
            
            // Προετοιμασία των παραμέτρων
            $params = [
                $driverId,
                $exp['vehicle_category'],
                $exp['vehicle_type'] ?? '',
                $exp['transport_type'] ?? 'freight',
                $exp['employment_type'] ?? 'employee',
                intval($exp['years'] ?? 0),
                intval($exp['months'] ?? 0),
                intval($exp['days'] ?? 0),
                $exp['start_date'] ?? null,
                $exp['end_date'] ?? null,
                $exp['description'] ?? ''
            ];
            
            file_put_contents($logFile, "Εισαγωγή εγγραφής με παραμέτρους: " . print_r($params, true) . "\n", FILE_APPEND);
            Logger::info("Εισαγωγή εγγραφής με παραμέτρους: " . print_r($params, true), "VehicleExperience");
            
            try {
                $result = $stmt->execute($params);
                
                if ($result) {
                    $insertCount++;
                    file_put_contents($logFile, "Επιτυχής εισαγωγή εγγραφής\n", FILE_APPEND);
                    Logger::info("Επιτυχής εισαγωγή εγγραφής", "VehicleExperience");
                } else {
                    $errorCount++;
                    $errorInfo = $stmt->errorInfo();
                    file_put_contents($logFile, "Αποτυχία εισαγωγής εγγραφής: " . print_r($errorInfo, true) . "\n", FILE_APPEND);
                    Logger::error("Αποτυχία εισαγωγής εγγραφής: " . print_r($errorInfo, true), "VehicleExperience");
                }
            } catch (PDOException $e) {
                $errorCount++;
                file_put_contents($logFile, "Εξαίρεση κατά την εισαγωγή εγγραφής: " . $e->getMessage() . "\n", FILE_APPEND);
                Logger::error("Εξαίρεση κατά την εισαγωγή εγγραφής: " . $e->getMessage(), "VehicleExperience");
            }
        }
        
        file_put_contents($logFile, "Σύνοψη εισαγωγής: $insertCount επιτυχείς, $errorCount αποτυχημένες\n", FILE_APPEND);
        Logger::info("Σύνοψη εισαγωγής: $insertCount επιτυχείς, $errorCount αποτυχημένες", "VehicleExperience");
        
        // Έλεγχος των εγγραφών μετά την εισαγωγή
        try {
            $sql = "SELECT * FROM driver_vehicle_experience WHERE driver_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$driverId]);
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            file_put_contents($logFile, "Εγγραφές μετά την εισαγωγή: " . print_r($records, true) . "\n", FILE_APPEND);
            Logger::info("Εγγραφές μετά την εισαγωγή: " . print_r($records, true), "VehicleExperience");
            
            if (count($records) === $insertCount) {
                Session::set('success_message', "Η διόρθωση εφαρμόστηκε επιτυχώς. Εισήχθησαν $insertCount εγγραφές.");
            } else {
                Session::set('warning_message', "Η διόρθωση εφαρμόστηκε με προειδοποιήσεις. Εισήχθησαν $insertCount εγγραφές, αλλά βρέθηκαν " . count($records) . " εγγραφές στη βάση.");
            }
        } catch (PDOException $e) {
            file_put_contents($logFile, "Σφάλμα κατά τον έλεγχο των εγγραφών μετά την εισαγωγή: " . $e->getMessage() . "\n", FILE_APPEND);
            Logger::error("Σφάλμα κατά τον έλεγχο των εγγραφών μετά την εισαγωγή: " . $e->getMessage(), "VehicleExperience");
        }
        
        // Ενημέρωση του πεδίου experience_years στον πίνακα drivers
        try {
            // Υπολογισμός συνολικής προϋπηρεσίας
            $totalYears = 0;
            $totalMonths = 0;
            $totalDays = 0;
            
            foreach ($vehicleExperience as $exp) {
                $totalYears += intval($exp['years'] ?? 0);
                $totalMonths += intval($exp['months'] ?? 0);
                $totalDays += intval($exp['days'] ?? 0);
            }
            
            // Κανονικοποίηση
            $totalMonths += floor($totalDays / 30);
            $totalDays = $totalDays % 30;
            $totalYears += floor($totalMonths / 12);
            $totalMonths = $totalMonths % 12;
            
            // Στρογγυλοποίηση των ετών προϋπηρεσίας στον πλησιέστερο ακέραιο
            $totalDecimalYears = $totalYears + ($totalMonths / 12) + ($totalDays / 365);
            $roundedTotalYears = round($totalDecimalYears);
            
            file_put_contents($logFile, "Συνολική προϋπηρεσία: $totalYears έτη, $totalMonths μήνες, $totalDays ημέρες\n", FILE_APPEND);
            file_put_contents($logFile, "Δεκαδικά έτη: $totalDecimalYears, Στρογγυλοποιημένα έτη: $roundedTotalYears\n", FILE_APPEND);
            Logger::info("Συνολική προϋπηρεσία: $totalYears έτη, $totalMonths μήνες, $totalDays ημέρες", "VehicleExperience");
            Logger::info("Δεκαδικά έτη: $totalDecimalYears, Στρογγυλοποιημένα έτη: $roundedTotalYears", "VehicleExperience");
            
            // Ενημέρωση του πεδίου experience_years
            $sql = "UPDATE drivers SET experience_years = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$roundedTotalYears, $driverId]);
            
            file_put_contents($logFile, "Ενημέρωση του πεδίου experience_years: " . ($result ? 'Επιτυχία' : 'Αποτυχία') . "\n", FILE_APPEND);
            Logger::info("Ενημέρωση του πεδίου experience_years: " . ($result ? 'Επιτυχία' : 'Αποτυχία'), "VehicleExperience");
        } catch (PDOException $e) {
            file_put_contents($logFile, "Σφάλμα κατά την ενημέρωση του πεδίου experience_years: " . $e->getMessage() . "\n", FILE_APPEND);
            Logger::error("Σφάλμα κατά την ενημέρωση του πεδίου experience_years: " . $e->getMessage(), "VehicleExperience");
        }
        
    } catch (Exception $e) {
        file_put_contents($logFile, "Σφάλμα: " . $e->getMessage() . "\n", FILE_APPEND);
        file_put_contents($logFile, "Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
        Logger::error("Σφάλμα: " . $e->getMessage(), "VehicleExperience");
        Session::set('error_message', 'Σφάλμα κατά την εφαρμογή της διόρθωσης: ' . $e->getMessage());
    }
} else {
    file_put_contents($logFile, "Δεν παρέχονται δεδομένα JSON για δοκιμή\n", FILE_APPEND);
    Logger::warning("Δεν παρέχονται δεδομένα JSON για δοκιμή", "VehicleExperience");
    Session::set('warning_message', 'Δεν παρέχονται δεδομένα JSON για δοκιμή.');
}

// Ανακατεύθυνση πίσω στη σελίδα διαγνωστικών
header('Location: ' . BASE_URL . 'drivers/debug_vehicle_experience.php');
exit();

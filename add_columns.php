<?php
// Ορισμός των παραμέτρων σύνδεσης στη βάση δεδομένων
$host = 'localhost';
$db = 'drivejob';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Έλεγχος αν υπάρχει η στήλη transport_type
    $stmt = $pdo->query("SHOW COLUMNS FROM job_listings LIKE 'transport_type'");
    $transportTypeExists = $stmt->rowCount() > 0;

    // Προσθήκη της στήλης transport_type αν δεν υπάρχει
    if (!$transportTypeExists) {
        $pdo->exec("ALTER TABLE job_listings ADD COLUMN transport_type ENUM('freight', 'passenger', 'machinery') AFTER listing_type");
        echo "Η στήλη 'transport_type' προστέθηκε επιτυχώς στον πίνακα job_listings.\n";
    } else {
        echo "Η στήλη 'transport_type' υπάρχει ήδη στον πίνακα job_listings.\n";
    }

    // Έλεγχος αν υπάρχει η στήλη vehicle_types
    $stmt = $pdo->query("SHOW COLUMNS FROM job_listings LIKE 'vehicle_types'");
    $vehicleTypesExists = $stmt->rowCount() > 0;

    // Προσθήκη της στήλης vehicle_types αν δεν υπάρχει
    if (!$vehicleTypesExists) {
        $pdo->exec("ALTER TABLE job_listings ADD COLUMN vehicle_types TEXT AFTER specialized_experience");
        echo "Η στήλη 'vehicle_types' προστέθηκε επιτυχώς στον πίνακα job_listings.\n";
    } else {
        echo "Η στήλη 'vehicle_types' υπάρχει ήδη στον πίνακα job_listings.\n";
    }

    // Έλεγχος αν υπάρχει η στήλη machinery_types
    $stmt = $pdo->query("SHOW COLUMNS FROM job_listings LIKE 'machinery_types'");
    $machineryTypesExists = $stmt->rowCount() > 0;

    // Προσθήκη της στήλης machinery_types αν δεν υπάρχει
    if (!$machineryTypesExists) {
        $pdo->exec("ALTER TABLE job_listings ADD COLUMN machinery_types TEXT AFTER vehicle_types");
        echo "Η στήλη 'machinery_types' προστέθηκε επιτυχώς στον πίνακα job_listings.\n";
    } else {
        echo "Η στήλη 'machinery_types' υπάρχει ήδη στον πίνακα job_listings.\n";
    }

    echo "\nΗ ενημέρωση του πίνακα job_listings ολοκληρώθηκε επιτυχώς.\n";
} catch (PDOException $e) {
    echo "Σφάλμα βάσης δεδομένων: " . $e->getMessage();
}

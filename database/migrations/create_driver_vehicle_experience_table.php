<?php
// Αρχικοποίηση της εφαρμογής
$container = require_once __DIR__ . '/../../src/bootstrap.php';

// Λήψη του PDO από το container
$pdo = $container->get('pdo');

// Έλεγχος αν ο πίνακας υπάρχει ήδη
$tableExists = false;
try {
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'driver_vehicle_experience'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;
} catch (PDOException $e) {
    die("Σφάλμα κατά τον έλεγχο ύπαρξης του πίνακα: " . $e->getMessage());
}

// Αν ο πίνακας υπάρχει ήδη, ρωτάμε τον χρήστη αν θέλει να τον διαγράψει και να τον δημιουργήσει ξανά
if ($tableExists) {
    echo "Ο πίνακας driver_vehicle_experience υπάρχει ήδη.\n";
    echo "Θέλετε να τον διαγράψετε και να τον δημιουργήσετε ξανά; (y/n): ";
    $answer = trim(fgets(STDIN));

    if (strtolower($answer) === 'y') {
        try {
            $pdo->exec("DROP TABLE driver_vehicle_experience");
            echo "Ο πίνακας driver_vehicle_experience διαγράφηκε επιτυχώς.\n";
            $tableExists = false;
        } catch (PDOException $e) {
            die("Σφάλμα κατά τη διαγραφή του πίνακα: " . $e->getMessage());
        }
    } else {
        echo "Η διαδικασία ακυρώθηκε.\n";
        exit;
    }
}

// Δημιουργία του πίνακα αν δεν υπάρχει
if (!$tableExists) {
    try {
        $sql = "CREATE TABLE driver_vehicle_experience (
            id INT AUTO_INCREMENT PRIMARY KEY,
            driver_id INT NOT NULL,
            vehicle_category VARCHAR(50) NOT NULL,
            vehicle_type VARCHAR(50) NOT NULL,
            transport_type ENUM('freight', 'passenger') NOT NULL,
            employment_type ENUM('own_business', 'employee', 'contractor') NOT NULL,
            start_date DATE,
            end_date DATE,
            years INT NOT NULL DEFAULT 0,
            months INT NOT NULL DEFAULT 0,
            days INT NOT NULL DEFAULT 0,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE
        )";

        $pdo->exec($sql);
        echo "Ο πίνακας driver_vehicle_experience δημιουργήθηκε επιτυχώς.\n";
    } catch (PDOException $e) {
        die("Σφάλμα κατά τη δημιουργία του πίνακα: " . $e->getMessage());
    }
}

echo "Η διαδικασία ολοκληρώθηκε επιτυχώς.\n";

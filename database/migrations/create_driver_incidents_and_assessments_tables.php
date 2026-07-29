<?php

/**
 * Δημιουργία των πινάκων driver_incidents και driver_assessments
 */

require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Core\Logger;

try {
    // Σύνδεση με τη βάση δεδομένων
    $pdo = require_once __DIR__ . '/../../config/database.php';

    // Έλεγχος αν υπάρχει ήδη ο πίνακας driver_incidents
    $checkDriverIncidentsTable = "SHOW TABLES LIKE 'driver_incidents'";
    $driverIncidentsTableExists = $pdo->query($checkDriverIncidentsTable)->rowCount() > 0;

    // Έλεγχος αν υπάρχει ήδη ο πίνακας driver_assessments
    $checkDriverAssessmentsTable = "SHOW TABLES LIKE 'driver_assessments'";
    $driverAssessmentsTableExists = $pdo->query($checkDriverAssessmentsTable)->rowCount() > 0;

    // Δημιουργία του πίνακα driver_incidents αν δεν υπάρχει
    if (!$driverIncidentsTableExists) {
        $createDriverIncidentsTable = "CREATE TABLE driver_incidents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            driver_id INT NOT NULL,
            incident_type VARCHAR(100) NOT NULL,
            incident_date DATE NOT NULL,
            description TEXT NOT NULL,
            location VARCHAR(255),
            severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            file_path VARCHAR(255),
            created_at DATETIME NOT NULL,
            updated_at DATETIME,
            FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($createDriverIncidentsTable);
        echo "Ο πίνακας driver_incidents δημιουργήθηκε με επιτυχία.\n";
    } else {
        echo "Ο πίνακας driver_incidents υπάρχει ήδη.\n";
    }

    // Δημιουργία του πίνακα driver_assessments αν δεν υπάρχει
    if (!$driverAssessmentsTableExists) {
        $createDriverAssessmentsTable = "CREATE TABLE driver_assessments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            driver_id INT NOT NULL,
            driving_skills INT NOT NULL DEFAULT 3,
            vehicle_knowledge INT NOT NULL DEFAULT 3,
            safety_awareness INT NOT NULL DEFAULT 3,
            time_management INT NOT NULL DEFAULT 3,
            customer_service INT NOT NULL DEFAULT 3,
            stress_handling INT NOT NULL DEFAULT 3,
            comments TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME,
            FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
            UNIQUE KEY unique_driver_assessment (driver_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($createDriverAssessmentsTable);
        echo "Ο πίνακας driver_assessments δημιουργήθηκε με επιτυχία.\n";
    } else {
        echo "Ο πίνακας driver_assessments υπάρχει ήδη.\n";
    }

    echo "Η δημιουργία των πινάκων ολοκληρώθηκε με επιτυχία.\n";
} catch (PDOException $e) {
    Logger::error('Σφάλμα κατά τη δημιουργία των πινάκων: ' . $e->getMessage());
    echo "Σφάλμα: " . $e->getMessage() . "\n";
}

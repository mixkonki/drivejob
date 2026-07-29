<?php

/**
 * Migration για τη δημιουργία του πίνακα driver_certifications
 * 
 * Αυτό το αρχείο δημιουργεί τον πίνακα driver_certifications στη βάση δεδομένων
 * για την αποθήκευση των πιστοποιητικών εκπαίδευσης των οδηγών.
 */

// Φόρτωση του container
$container = require_once __DIR__ . '/../../src/bootstrap.php';

// Λήψη του PDO από το container
$pdo = $container->get('pdo');

// Έλεγχος αν ο πίνακας υπάρχει ήδη
$tableExists = false;
$stmt = $pdo->query("SHOW TABLES LIKE 'driver_certifications'");
if ($stmt->rowCount() > 0) {
    $tableExists = true;
    echo "Ο πίνακας driver_certifications υπάρχει ήδη.\n";
}

// Αν ο πίνακας δεν υπάρχει, τον δημιουργούμε
if (!$tableExists) {
    try {
        // Δημιουργία του πίνακα
        $sql = "CREATE TABLE driver_certifications (
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            driver_id INT(11) UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            provider VARCHAR(255) NULL,
            category VARCHAR(50) NULL,
            transport_type ENUM('freight', 'passenger', 'both') NOT NULL DEFAULT 'both',
            date DATE NULL,
            expiry DATE NULL,
            duration INT(11) NULL,
            description TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (driver_id),
            INDEX (category),
            INDEX (transport_type),
            INDEX (date),
            INDEX (expiry)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $pdo->exec($sql);
        echo "Ο πίνακας driver_certifications δημιουργήθηκε επιτυχώς.\n";
    } catch (PDOException $e) {
        echo "Σφάλμα κατά τη δημιουργία του πίνακα driver_certifications: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    // Έλεγχος αν χρειάζεται να προσθέσουμε νέες στήλες
    $columnsToAdd = [
        'category' => "ALTER TABLE driver_certifications ADD COLUMN category VARCHAR(50) NULL AFTER provider, ADD INDEX (category)",
        'transport_type' => "ALTER TABLE driver_certifications ADD COLUMN transport_type ENUM('freight', 'passenger', 'both') NOT NULL DEFAULT 'both' AFTER category, ADD INDEX (transport_type)",
        'duration' => "ALTER TABLE driver_certifications ADD COLUMN duration INT(11) NULL AFTER expiry"
    ];

    foreach ($columnsToAdd as $column => $alterSql) {
        $stmt = $pdo->query("SHOW COLUMNS FROM driver_certifications LIKE '$column'");
        if ($stmt->rowCount() == 0) {
            try {
                $pdo->exec($alterSql);
                echo "Η στήλη $column προστέθηκε στον πίνακα driver_certifications.\n";
            } catch (PDOException $e) {
                echo "Σφάλμα κατά την προσθήκη της στήλης $column: " . $e->getMessage() . "\n";
            }
        } else {
            echo "Η στήλη $column υπάρχει ήδη στον πίνακα driver_certifications.\n";
        }
    }
}

echo "Η διαδικασία migration ολοκληρώθηκε επιτυχώς.\n";

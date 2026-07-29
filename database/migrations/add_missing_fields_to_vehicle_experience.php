<?php
// Αρχικοποίηση της εφαρμογής
$container = require_once __DIR__ . '/../../src/bootstrap.php';

// Λήψη του PDO από το container
$pdo = $container->get('pdo');

// Έλεγχος αν ο πίνακας υπάρχει
try {
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'driver_vehicle_experience'");
    $stmt->execute();
    $tableExists = $stmt->rowCount() > 0;

    if (!$tableExists) {
        echo "Ο πίνακας driver_vehicle_experience δεν υπάρχει.\n";
        echo "Παρακαλώ εκτελέστε πρώτα το script create_driver_vehicle_experience_table.php\n";
        exit;
    }

    echo "Ο πίνακας driver_vehicle_experience υπάρχει.\n";

    // Έλεγχος των πεδίων του πίνακα
    $stmt = $pdo->prepare("DESCRIBE driver_vehicle_experience");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Έλεγχος για τα απαραίτητα πεδία
    $requiredFields = [
        'transport_type' => "ALTER TABLE driver_vehicle_experience ADD COLUMN transport_type ENUM('freight', 'passenger') NOT NULL DEFAULT 'freight' AFTER vehicle_type",
        'employment_type' => "ALTER TABLE driver_vehicle_experience ADD COLUMN employment_type ENUM('own_business', 'employee', 'contractor') NOT NULL DEFAULT 'employee' AFTER transport_type",
        'months' => "ALTER TABLE driver_vehicle_experience ADD COLUMN months INT NOT NULL DEFAULT 0 AFTER years",
        'days' => "ALTER TABLE driver_vehicle_experience ADD COLUMN days INT NOT NULL DEFAULT 0 AFTER months"
    ];

    $existingFields = array_column($columns, 'Field');
    $missingFields = [];

    foreach ($requiredFields as $field => $alterSql) {
        if (!in_array($field, $existingFields)) {
            $missingFields[$field] = $alterSql;
        }
    }

    if (empty($missingFields)) {
        echo "Όλα τα απαραίτητα πεδία υπάρχουν ήδη στον πίνακα.\n";
    } else {
        echo "Λείπουν τα ακόλουθα πεδία από τον πίνακα:\n";
        foreach ($missingFields as $field => $alterSql) {
            echo "- $field\n";
        }

        echo "\nΠροσθήκη των πεδίων που λείπουν...\n";

        foreach ($missingFields as $field => $alterSql) {
            try {
                $pdo->exec($alterSql);
                echo "Το πεδίο '$field' προστέθηκε επιτυχώς.\n";
            } catch (PDOException $e) {
                echo "Σφάλμα κατά την προσθήκη του πεδίου '$field': " . $e->getMessage() . "\n";
            }
        }
    }

    // Έλεγχος των πεδίων του πίνακα μετά τις αλλαγές
    $stmt = $pdo->prepare("DESCRIBE driver_vehicle_experience");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nΠεδία του πίνακα driver_vehicle_experience μετά τις αλλαγές:\n";
    echo str_repeat('-', 80) . "\n";
    echo sprintf("%-20s %-30s %-10s %-10s %-10s\n", "Πεδίο", "Τύπος", "Null", "Key", "Default");
    echo str_repeat('-', 80) . "\n";

    foreach ($columns as $column) {
        echo sprintf(
            "%-20s %-30s %-10s %-10s %-10s\n",
            $column['Field'],
            $column['Type'],
            $column['Null'],
            $column['Key'],
            $column['Default'] ?? 'NULL'
        );
    }

    echo str_repeat('-', 80) . "\n";

    echo "\nΗ διαδικασία ολοκληρώθηκε επιτυχώς.\n";
} catch (PDOException $e) {
    die("Σφάλμα κατά την εκτέλεση του script: " . $e->getMessage());
}

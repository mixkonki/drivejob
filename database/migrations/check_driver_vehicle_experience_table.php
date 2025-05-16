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

    echo "\nΠεδία του πίνακα driver_vehicle_experience:\n";
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

    // Έλεγχος για τα απαραίτητα πεδία
    $requiredFields = [
        'id',
        'driver_id',
        'vehicle_category',
        'vehicle_type',
        'transport_type',
        'employment_type',
        'start_date',
        'end_date',
        'years',
        'months',
        'days',
        'description'
    ];

    $missingFields = [];
    $existingFields = array_column($columns, 'Field');

    foreach ($requiredFields as $field) {
        if (!in_array($field, $existingFields)) {
            $missingFields[] = $field;
        }
    }

    if (!empty($missingFields)) {
        echo "\nΛείπουν τα ακόλουθα πεδία από τον πίνακα:\n";
        foreach ($missingFields as $field) {
            echo "- $field\n";
        }

        echo "\nΘα πρέπει να προσθέσετε τα παραπάνω πεδία στον πίνακα ή να εκτελέσετε ξανά το script create_driver_vehicle_experience_table.php\n";
    } else {
        echo "\nΌλα τα απαραίτητα πεδία υπάρχουν στον πίνακα.\n";
    }

    // Έλεγχος για το foreign key
    $stmt = $pdo->prepare("
        SELECT * FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'driver_vehicle_experience'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    $stmt->execute();
    $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($foreignKeys)) {
        echo "\nΠροειδοποίηση: Δεν βρέθηκαν foreign keys στον πίνακα.\n";
        echo "Θα πρέπει να υπάρχει ένα foreign key από το πεδίο driver_id στον πίνακα drivers.\n";
    } else {
        echo "\nForeign keys του πίνακα:\n";
        echo str_repeat('-', 80) . "\n";
        echo sprintf("%-15s %-15s %-20s %-15s %-15s\n", "Column", "Referenced Table", "Referenced Column", "Constraint Name", "On Delete");
        echo str_repeat('-', 80) . "\n";

        foreach ($foreignKeys as $fk) {
            echo sprintf(
                "%-15s %-15s %-20s %-15s %-15s\n",
                $fk['COLUMN_NAME'],
                $fk['REFERENCED_TABLE_NAME'],
                $fk['REFERENCED_COLUMN_NAME'],
                $fk['CONSTRAINT_NAME'],
                $fk['DELETE_RULE'] ?? 'N/A'
            );
        }

        echo str_repeat('-', 80) . "\n";
    }

    // Έλεγχος για εγγραφές στον πίνακα
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM driver_vehicle_experience");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    echo "\nΑριθμός εγγραφών στον πίνακα: $count\n";

    if ($count > 0) {
        echo "\nΠαραδείγματα εγγραφών:\n";
        $stmt = $pdo->prepare("SELECT * FROM driver_vehicle_experience LIMIT 5");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo str_repeat('-', 120) . "\n";
        echo sprintf(
            "%-5s %-10s %-15s %-15s %-15s %-15s %-12s %-12s %-5s %-5s %-5s\n",
            "ID",
            "Driver ID",
            "Category",
            "Type",
            "Transport",
            "Employment",
            "Start Date",
            "End Date",
            "Years",
            "Months",
            "Days"
        );
        echo str_repeat('-', 120) . "\n";

        foreach ($rows as $row) {
            echo sprintf(
                "%-5s %-10s %-15s %-15s %-15s %-15s %-12s %-12s %-5s %-5s %-5s\n",
                $row['id'],
                $row['driver_id'],
                $row['vehicle_category'],
                $row['vehicle_type'],
                $row['transport_type'],
                $row['employment_type'],
                $row['start_date'] ?? 'NULL',
                $row['end_date'] ?? 'NULL',
                $row['years'],
                $row['months'],
                $row['days']
            );
        }

        echo str_repeat('-', 120) . "\n";
    }
} catch (PDOException $e) {
    die("Σφάλμα κατά τον έλεγχο του πίνακα: " . $e->getMessage());
}

echo "\nΟ έλεγχος του πίνακα ολοκληρώθηκε.\n";

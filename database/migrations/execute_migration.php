<?php

// Φόρτωση του bootstrap της εφαρμογής
require_once __DIR__ . '/../../src/bootstrap.php';

// Λήψη του PDO από το container
$container = \Drivejob\Core\Container::getInstance();
$pdo = $container->get('pdo');

// Φόρτωση του SQL script
$sqlFile = __DIR__ . '/add_is_active_to_users.sql';
$sql = file_get_contents($sqlFile);

// Διαχωρισμός των εντολών SQL
$queries = explode(';', $sql);

// Εκτέλεση κάθε εντολής
$success = true;
foreach ($queries as $query) {
    $query = trim($query);
    if (empty($query)) {
        continue;
    }

    try {
        $result = $pdo->exec($query);
        echo "Εκτέλεση εντολής: " . substr($query, 0, 50) . "...\n";
        echo "Αποτέλεσμα: " . ($result !== false ? "Επιτυχία" : "Αποτυχία") . "\n\n";

        if ($result === false) {
            $success = false;
            echo "Σφάλμα: " . print_r($pdo->errorInfo(), true) . "\n";
        }
    } catch (PDOException $e) {
        $success = false;
        echo "Σφάλμα: " . $e->getMessage() . "\n";
    }
}

echo "\n" . ($success ? "Η μετανάστευση ολοκληρώθηκε με επιτυχία!" : "Η μετανάστευση απέτυχε!") . "\n";

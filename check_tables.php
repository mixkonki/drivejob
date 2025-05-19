<?php

// Ορισμός του ROOT_DIR
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', __DIR__);
}

// Σύνδεση στη βάση δεδομένων
require_once ROOT_DIR . '/config/database.php';

// Έλεγχος των πινάκων
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Πίνακες στη βάση δεδομένων:\n";
foreach ($tables as $table) {
    echo "- $table\n";
}

// Έλεγχος αν υπάρχει ο πίνακας companies
if (in_array('companies', $tables)) {
    echo "\nΟ πίνακας 'companies' υπάρχει.\n";

    // Έλεγχος της δομής του πίνακα companies
    $stmt = $pdo->query('DESCRIBE companies');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nΔομή του πίνακα 'companies':\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
} else {
    echo "\nΟ πίνακας 'companies' δεν υπάρχει.\n";
}

// Έλεγχος αν υπάρχει ο πίνακας drivers
if (in_array('drivers', $tables)) {
    echo "\nΟ πίνακας 'drivers' υπάρχει.\n";
} else {
    echo "\nΟ πίνακας 'drivers' δεν υπάρχει.\n";
}

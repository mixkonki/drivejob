<?php

// Λήψη της σύνδεσης με τη βάση δεδομένων
$pdo = require_once __DIR__ . '/config/database.php';

// Έλεγχος της δομής του πίνακα drivers
$stmt = $pdo->query("DESCRIBE drivers");
echo "Στήλες του πίνακα 'drivers':\n";
while ($row = $stmt->fetch()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n";

// Έλεγχος της δομής του πίνακα companies
$stmt = $pdo->query("DESCRIBE companies");
echo "Στήλες του πίνακα 'companies':\n";
while ($row = $stmt->fetch()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

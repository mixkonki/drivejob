<?php

// Λήψη της σύνδεσης με τη βάση δεδομένων
$pdo = require_once __DIR__ . '/config/database.php';

// Έλεγχος της δομής του πίνακα users
try {
    $stmt = $pdo->query("SHOW CREATE TABLE users");
    $row = $stmt->fetch();
    echo "Δομή του πίνακα 'users':\n";
    echo $row[1] . "\n";
} catch (PDOException $e) {
    echo "Σφάλμα: " . $e->getMessage() . "\n";
}

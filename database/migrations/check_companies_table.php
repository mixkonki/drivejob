<?php

/**
 * Script για έλεγχο της δομής του πίνακα companies
 */

// Σύνδεση στη βάση δεδομένων
$pdo = require __DIR__ . '/../../config/database.php';

try {
    echo "=== ΔΟΜΗ ΠΙΝΑΚΑ COMPANIES ===\n\n";

    $stmt = $pdo->query("DESCRIBE companies");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Στήλες:\n";
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }

    echo "\n\n=== ΔΕΙΓΜΑ ΔΕΔΟΜΕΝΩΝ ===\n\n";

    $stmt = $pdo->query("SELECT * FROM companies LIMIT 1");
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($company) {
        foreach ($company as $key => $value) {
            echo "{$key}: " . ($value ?? 'NULL') . "\n";
        }
    }
} catch (PDOException $e) {
    echo "Σφάλμα: " . $e->getMessage() . "\n";
}

<?php
require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = require __DIR__ . '/../../config/database.php';

    echo "Έλεγχος δομής πίνακα users...\n\n";

    $stmt = $pdo->query('DESCRIBE users');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Στήλες στον πίνακα users:\n";
    echo str_repeat('-', 50) . "\n";

    foreach ($columns as $col) {
        echo sprintf("%-20s | %-30s\n", $col['Field'], $col['Type']);
    }

    echo "\n";
} catch (PDOException $e) {
    echo "Σφάλμα: " . $e->getMessage() . "\n";
}

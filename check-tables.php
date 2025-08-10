<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=drivejob;charset=utf8', 'root', '');
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Available tables:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }

    // Check specifically for matching tables
    echo "\nChecking for matching-related tables:\n";
    $matchingTables = ['match_history', 'match_preferences', 'ai_matching_scores'];
    foreach ($matchingTables as $table) {
        if (in_array($table, $tables)) {
            echo "✅ $table exists\n";
        } else {
            echo "❌ $table missing\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

<?php
require_once __DIR__ . '/../src/bootstrap.php';

header('Content-Type: text/plain');

echo "=== Job Listings Table Structure ===\n\n";

try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

    // Get table structure
    $stmt = $pdo->query("DESCRIBE job_listings");
    $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    echo "Columns in job_listings table:\n";
    echo str_pad("Field", 30) . str_pad("Type", 20) . str_pad("Null", 10) . str_pad("Key", 10) . "\n";
    echo str_repeat("-", 70) . "\n";

    foreach ($columns as $column) {
        echo str_pad($column['Field'], 30);
        echo str_pad($column['Type'], 20);
        echo str_pad($column['Null'], 10);
        echo str_pad($column['Key'], 10);
        echo "\n";
    }
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;

$pdo = Database::getInstance()->getConnection();

// Check drivers table columns
$stmt = $pdo->query("SHOW COLUMNS FROM drivers");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Drivers Table Columns:</h2>";
echo "<pre>";
foreach ($columns as $column) {
    echo $column['Field'] . " - " . $column['Type'] . "\n";
}
echo "</pre>";

// Check if is_verified exists
$hasIsVerified = false;
foreach ($columns as $column) {
    if ($column['Field'] === 'is_verified') {
        $hasIsVerified = true;
        break;
    }
}

echo "<p>Has is_verified column: " . ($hasIsVerified ? 'YES' : 'NO') . "</p>";

<?php
require_once __DIR__ . '/../src/bootstrap.php';

$pdo = \Drivejob\Core\Database::getInstance()->getConnection();

echo "<h2>Checking Table Structures</h2>";
echo "<pre>";

// Check companies table
echo "=== COMPANIES TABLE ===\n";
$stmt = $pdo->query("SHOW COLUMNS FROM companies");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo $col['Field'] . " - " . $col['Type'] . "\n";
}

echo "\n=== DRIVERS TABLE ===\n";
$stmt = $pdo->query("SHOW COLUMNS FROM drivers");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo $col['Field'] . " - " . $col['Type'] . "\n";
}

echo "\n=== CONVERSATIONS TABLE ===\n";
$stmt = $pdo->query("SHOW COLUMNS FROM conversations");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo $col['Field'] . " - " . $col['Type'] . "\n";
}

// Test query
echo "\n=== TEST QUERY ===\n";
try {
    $stmt = $pdo->query("
        SELECT c.*, 
               comp.company_name,
               CONCAT(d.first_name, ' ', d.last_name) as driver_name
        FROM conversations c
        JOIN companies comp ON c.company_id = comp.id
        JOIN drivers d ON c.driver_id = d.id
        LIMIT 1
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        print_r($result);
    } else {
        echo "No conversations found\n";
    }
} catch (Exception $e) {
    echo "Query error: " . $e->getMessage() . "\n";
}

echo "</pre>";

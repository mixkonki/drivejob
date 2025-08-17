<?php
require_once __DIR__ . '/../src/bootstrap.php';

echo "🔍 CHECKING DRIVERS TABLE STRUCTURE\n";
echo "===================================\n\n";

try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

    // Check drivers table structure
    $stmt = $pdo->prepare("DESCRIBE drivers");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "📊 Drivers table columns:\n";
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }

    echo "\n";

    // Check if we have driver with ID 26
    $stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = 26 LIMIT 1");
    $stmt->execute();
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($driver) {
        echo "✅ Driver with ID 26 found:\n";
        foreach ($driver as $key => $value) {
            echo "   {$key}: {$value}\n";
        }
    } else {
        echo "❌ No driver with ID 26 found\n";

        // Check what drivers exist
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, email FROM drivers LIMIT 5");
        $stmt->execute();
        $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "\n📋 Available drivers:\n";
        foreach ($drivers as $d) {
            echo "   ID: {$d['id']}, Name: {$d['first_name']} {$d['last_name']}, Email: {$d['email']}\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Check completed\n";

<?php
require_once __DIR__ . '/../src/bootstrap.php';

$pdo = \Drivejob\Core\Database::getInstance()->getConnection();

echo "<h2>Fixing Notifications Table</h2>";
echo "<pre>";

try {
    // Check current structure
    echo "Current structure:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $existingColumns = [];
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
        $existingColumns[] = $col['Field'];
    }

    echo "\n";

    // Add missing columns
    if (!in_array('title', $existingColumns)) {
        echo "Adding 'title' column...\n";
        $pdo->exec("ALTER TABLE notifications ADD COLUMN title VARCHAR(255) NOT NULL AFTER type");
        echo "✅ Added 'title' column\n";
    }

    if (!in_array('message', $existingColumns)) {
        echo "Adding 'message' column...\n";
        $pdo->exec("ALTER TABLE notifications ADD COLUMN message TEXT NOT NULL AFTER title");
        echo "✅ Added 'message' column\n";
    }

    if (!in_array('is_read', $existingColumns)) {
        echo "Adding 'is_read' column...\n";
        $pdo->exec("ALTER TABLE notifications ADD COLUMN is_read BOOLEAN DEFAULT FALSE AFTER data");
        echo "✅ Added 'is_read' column\n";
    }

    // Show updated structure
    echo "\nUpdated structure:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }

    echo "\n✅ Notifications table fixed successfully!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";

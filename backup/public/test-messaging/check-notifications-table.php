<?php
require_once __DIR__ . '/../src/bootstrap.php';

$pdo = \Drivejob\Core\Database::getInstance()->getConnection();

echo "<h2>Checking Notifications Table</h2>";
echo "<pre>";

// Check if notifications table exists
$stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
if ($stmt->fetch()) {
    echo "✅ Notifications table exists\n\n";

    // Show columns
    echo "=== NOTIFICATIONS TABLE COLUMNS ===\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
} else {
    echo "❌ Notifications table does NOT exist!\n";
    echo "Creating table...\n\n";

    // Create the table
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_type ENUM('company', 'driver') NOT NULL,
        user_id INT NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        data JSON DEFAULT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        read_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        INDEX idx_user_unread (user_type, user_id, is_read),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    try {
        $pdo->exec($sql);
        echo "✅ Notifications table created successfully!\n";
    } catch (Exception $e) {
        echo "❌ Error creating table: " . $e->getMessage() . "\n";
    }
}

echo "</pre>";

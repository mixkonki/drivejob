<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;

$pdo = Database::getInstance()->getConnection();

echo "<h2>Έλεγχος Πινάκων Messaging System</h2>";

// Check conversations table
echo "<h3>1. Πίνακας conversations:</h3>";
try {
    $stmt = $pdo->query("DESCRIBE conversations");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Count records
    $count = $pdo->query("SELECT COUNT(*) FROM conversations")->fetchColumn();
    echo "<p>Total conversations: $count</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Check messages table
echo "<h3>2. Πίνακας messages:</h3>";
try {
    $stmt = $pdo->query("DESCRIBE messages");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Count records
    $count = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
    echo "<p>Total messages: $count</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Check notifications table
echo "<h3>3. Πίνακας notifications:</h3>";
try {
    $stmt = $pdo->query("DESCRIBE notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Count records
    $count = $pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
    echo "<p>Total notifications: $count</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Test sending a message
echo "<h3>4. Test Messaging Service:</h3>";
try {
    $messagingService = new \Drivejob\Services\MessagingService();

    // Check for test conversation
    $stmt = $pdo->query("SELECT * FROM conversations ORDER BY id DESC LIMIT 1");
    $lastConv = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($lastConv) {
        echo "<p>Last conversation ID: {$lastConv['id']}</p>";
        echo "<p>Company ID: {$lastConv['company_id']}, Driver ID: {$lastConv['driver_id']}</p>";
        echo "<p>Subject: {$lastConv['subject']}</p>";
        echo "<p>Status: {$lastConv['status']}</p>";

        // Get messages
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE conversation_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$lastConv['id']]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h4>Recent messages:</h4>";
        if ($messages) {
            foreach ($messages as $msg) {
                echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px;'>";
                echo "<strong>{$msg['sender_type']} (ID: {$msg['sender_id']}):</strong><br>";
                echo htmlspecialchars($msg['message'] ?? $msg['content'] ?? 'No content') . "<br>";
                echo "<small>Created: {$msg['created_at']}</small>";
                echo "</div>";
            }
        } else {
            echo "<p>No messages found</p>";
        }
    } else {
        echo "<p>No conversations found</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error testing messaging: " . $e->getMessage() . "</p>";
}

// Check if message column exists
echo "<h3>5. Check message column name:</h3>";
$stmt = $pdo->query("SHOW COLUMNS FROM messages WHERE Field IN ('message', 'content')");
$msgColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($msgColumns) {
    echo "<p>Message column found:</p>";
    foreach ($msgColumns as $col) {
        echo "<p>- Field: {$col['Field']}, Type: {$col['Type']}</p>";
    }
} else {
    echo "<p style='color: red;'>No message or content column found!</p>";
}

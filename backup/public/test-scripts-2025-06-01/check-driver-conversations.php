<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;

$pdo = Database::getInstance()->getConnection();

echo "<h1>Έλεγχος Driver Conversations</h1>";

// Check conversations for driver 26 (or any test driver)
$driverId = 26; // Adjust this based on your test driver

echo "<h2>Conversations για Driver ID: $driverId</h2>";

// Get all conversations
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        comp.company_name,
        j.title as job_title
    FROM conversations c
    JOIN companies comp ON c.company_id = comp.id
    JOIN job_listings j ON c.job_id = j.id
    WHERE c.driver_id = ?
    ORDER BY c.updated_at DESC
");
$stmt->execute([$driverId]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Βρέθηκαν " . count($conversations) . " conversations</p>";

foreach ($conversations as $conv) {
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
    echo "<h3>{$conv['subject']}</h3>";
    echo "<p>Company: {$conv['company_name']}</p>";
    echo "<p>Job: {$conv['job_title']}</p>";
    echo "<p>Status: {$conv['status']}</p>";
    echo "<p>Created: {$conv['created_at']}</p>";

    // Get messages count
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE conversation_id = ?");
    $stmt2->execute([$conv['id']]);
    $msgCount = $stmt2->fetchColumn();
    echo "<p>Messages: $msgCount</p>";
    echo "</div>";
}

// Check if there are messages without conversations
echo "<h2>Messages χωρίς Conversations</h2>";
$stmt = $pdo->query("
    SELECT m.*, d.first_name, d.last_name 
    FROM messages m
    LEFT JOIN conversations c ON m.conversation_id = c.id
    JOIN drivers d ON m.sender_id = d.id AND m.sender_type = 'driver'
    WHERE c.id IS NULL
");
$orphanMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($orphanMessages)) {
    echo "<p style='color: red;'>Βρέθηκαν " . count($orphanMessages) . " messages χωρίς conversation!</p>";
}

// Check unique drivers with conversations
echo "<h2>Drivers με Conversations</h2>";
$stmt = $pdo->query("
    SELECT DISTINCT d.id, d.first_name, d.last_name, COUNT(c.id) as conv_count
    FROM drivers d
    JOIN conversations c ON d.id = c.driver_id
    GROUP BY d.id
");
$driversWithConv = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($driversWithConv as $driver) {
    echo "<p>Driver {$driver['first_name']} {$driver['last_name']} (ID: {$driver['id']}): {$driver['conv_count']} conversations</p>";
}

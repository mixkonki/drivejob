<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;

$pdo = Database::getInstance()->getConnection();

echo "<h2>Έλεγχος Εμφάνισης Μηνυμάτων</h2>";

// Test as company ID 2
$companyId = 2;
echo "<h3>Συνομιλίες για Company ID $companyId:</h3>";

$stmt = $pdo->prepare("
    SELECT 
        c.id,
        c.subject,
        c.status,
        c.created_at,
        c.updated_at,
        d.first_name,
        d.last_name,
        j.title as job_title,
        (SELECT COUNT(*) FROM messages m 
         WHERE m.conversation_id = c.id 
         AND m.is_read = 0 
         AND m.sender_type = 'driver') as unread_count,
        (SELECT message FROM messages m2 
         WHERE m2.conversation_id = c.id 
         ORDER BY m2.created_at DESC 
         LIMIT 1) as last_message,
        (SELECT COUNT(*) FROM messages m3 
         WHERE m3.conversation_id = c.id) as total_messages
    FROM conversations c
    JOIN drivers d ON c.driver_id = d.id
    JOIN job_listings j ON c.job_id = j.id
    WHERE c.company_id = ?
    ORDER BY c.updated_at DESC
");
$stmt->execute([$companyId]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Βρέθηκαν " . count($conversations) . " συνομιλίες</p>";

foreach ($conversations as $conv) {
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
    echo "<strong>ID: {$conv['id']}</strong> - {$conv['subject']}<br>";
    echo "Οδηγός: {$conv['first_name']} {$conv['last_name']}<br>";
    echo "Θέση: {$conv['job_title']}<br>";
    echo "Συνολικά μηνύματα: {$conv['total_messages']}<br>";
    echo "Μη αναγνωσμένα: {$conv['unread_count']}<br>";
    echo "Τελευταίο μήνυμα: " . substr($conv['last_message'], 0, 50) . "...<br>";
    echo "</div>";
}

// Test as driver ID 26
$driverId = 26;
echo "<hr><h3>Συνομιλίες για Driver ID $driverId:</h3>";

$stmt = $pdo->prepare("
    SELECT 
        c.id,
        c.subject,
        c.status,
        c.created_at,
        c.updated_at,
        comp.company_name,
        j.title as job_title,
        (SELECT COUNT(*) FROM messages m 
         WHERE m.conversation_id = c.id 
         AND m.is_read = 0 
         AND m.sender_type = 'company') as unread_count,
        (SELECT message FROM messages m2 
         WHERE m2.conversation_id = c.id 
         ORDER BY m2.created_at DESC 
         LIMIT 1) as last_message,
        (SELECT COUNT(*) FROM messages m3 
         WHERE m3.conversation_id = c.id) as total_messages
    FROM conversations c
    JOIN companies comp ON c.company_id = comp.id
    JOIN job_listings j ON c.job_id = j.id
    WHERE c.driver_id = ?
    ORDER BY c.updated_at DESC
");
$stmt->execute([$driverId]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Βρέθηκαν " . count($conversations) . " συνομιλίες</p>";

foreach ($conversations as $conv) {
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
    echo "<strong>ID: {$conv['id']}</strong> - {$conv['subject']}<br>";
    echo "Εταιρεία: {$conv['company_name']}<br>";
    echo "Θέση: {$conv['job_title']}<br>";
    echo "Συνολικά μηνύματα: {$conv['total_messages']}<br>";
    echo "Μη αναγνωσμένα: {$conv['unread_count']}<br>";
    echo "Τελευταίο μήνυμα: " . substr($conv['last_message'], 0, 50) . "...<br>";
    echo "</div>";
}

// Check all conversations
echo "<hr><h3>Όλες οι συνομιλίες στο σύστημα:</h3>";
$stmt = $pdo->query("
    SELECT c.*, 
           comp.company_name,
           d.first_name, d.last_name,
           (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id) as message_count
    FROM conversations c
    LEFT JOIN companies comp ON c.company_id = comp.id
    LEFT JOIN drivers d ON c.driver_id = d.id
    ORDER BY c.updated_at DESC
");
$allConversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Company</th><th>Driver</th><th>Subject</th><th>Messages</th><th>Updated</th></tr>";
foreach ($allConversations as $conv) {
    echo "<tr>";
    echo "<td>{$conv['id']}</td>";
    echo "<td>{$conv['company_name']} (ID: {$conv['company_id']})</td>";
    echo "<td>{$conv['first_name']} {$conv['last_name']} (ID: {$conv['driver_id']})</td>";
    echo "<td>{$conv['subject']}</td>";
    echo "<td>{$conv['message_count']}</td>";
    echo "<td>{$conv['updated_at']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check messages in conversation 12
echo "<hr><h3>Μηνύματα στη συνομιλία ID 12:</h3>";
$stmt = $pdo->prepare("
    SELECT * FROM messages 
    WHERE conversation_id = 12 
    ORDER BY created_at ASC
");
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Βρέθηκαν " . count($messages) . " μηνύματα</p>";
foreach ($messages as $msg) {
    echo "<div style='margin: 10px; padding: 10px; background: " . ($msg['sender_type'] == 'company' ? '#e3f2fd' : '#f5f5f5') . ";'>";
    echo "<strong>{$msg['sender_type']}:</strong> {$msg['message']}<br>";
    echo "<small>{$msg['created_at']}</small>";
    echo "</div>";
}

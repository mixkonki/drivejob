<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;

$pdo = Database::getInstance()->getConnection();

echo "<h2>Δημιουργία Test Συνομιλιών</h2>";

try {
    // Get company and driver IDs
    $stmt = $pdo->prepare("SELECT id FROM companies WHERE email = ?");
    $stmt->execute(['info@thessdrive.gr']);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    $companyId = $company['id'];
    echo "<p>Company ID (info@thessdrive.gr): $companyId</p>";

    $stmt = $pdo->prepare("SELECT id FROM drivers WHERE email = ?");
    $stmt->execute(['kostas.michailidis@hotmail.gr']);
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);
    $driverId = $driver['id'];
    echo "<p>Driver ID (kostas.michailidis@hotmail.gr): $driverId</p>";

    // Get some other drivers
    $stmt = $pdo->query("SELECT id, first_name, last_name FROM drivers WHERE id != $driverId LIMIT 3");
    $otherDrivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get job listings from this company
    $stmt = $pdo->prepare("SELECT id, title FROM job_listings WHERE company_id = ? AND listing_type = 'job_offer' LIMIT 5");
    $stmt->execute([$companyId]);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($jobs)) {
        echo "<p>⚠️ Δεν βρέθηκαν αγγελίες για την εταιρεία</p>";
        exit;
    }

    echo "<h3>Δημιουργία νέων συνομιλιών:</h3>";

    // Create conversations with other drivers
    foreach ($otherDrivers as $index => $otherDriver) {
        if ($index >= count($jobs)) break;

        $job = $jobs[$index];

        // Check if conversation already exists
        $stmt = $pdo->prepare("
            SELECT id FROM conversations 
            WHERE company_id = ? AND driver_id = ? AND job_id = ?
        ");
        $stmt->execute([$companyId, $otherDriver['id'], $job['id']]);
        $existing = $stmt->fetch();

        if (!$existing) {
            // Create new conversation
            $stmt = $pdo->prepare("
                INSERT INTO conversations (
                    company_id, driver_id, job_id, 
                    participant1_id, participant2_id,
                    participant1_type, participant2_type,
                    subject, status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, 'company', 'driver', ?, 'active', NOW(), NOW())
            ");

            $subject = "Ενδιαφέρον για: " . $job['title'];
            $stmt->execute([
                $companyId,
                $otherDriver['id'],
                $job['id'],
                $companyId,
                $otherDriver['id'],
                $subject
            ]);

            $conversationId = $pdo->lastInsertId();

            // Add some messages
            $messages = [
                ['sender' => 'driver', 'message' => 'Καλησπέρα, ενδιαφέρομαι για τη θέση ' . $job['title'] . '. Έχω 5 χρόνια εμπειρία.'],
                ['sender' => 'company', 'message' => 'Καλησπέρα! Ευχαριστούμε για το ενδιαφέρον σας. Μπορείτε να μας στείλετε το βιογραφικό σας;'],
                ['sender' => 'driver', 'message' => 'Φυσικά! Το στέλνω αμέσως. Ποια είναι η διαδικασία επιλογής;'],
                ['sender' => 'company', 'message' => 'Αφού λάβουμε το βιογραφικό σας, θα επικοινωνήσουμε για συνέντευξη.']
            ];

            foreach ($messages as $msg) {
                $stmt = $pdo->prepare("
                    INSERT INTO messages (
                        conversation_id, sender_id, sender_type, 
                        receiver_id, receiver_type,
                        message, is_read, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");

                if ($msg['sender'] === 'driver') {
                    $senderId = $otherDriver['id'];
                    $receiverId = $companyId;
                    $receiverType = 'company';
                    $isRead = rand(0, 1); // Random read status
                } else {
                    $senderId = $companyId;
                    $receiverId = $otherDriver['id'];
                    $receiverType = 'driver';
                    $isRead = 1; // Company messages are read
                }

                $stmt->execute([
                    $conversationId,
                    $senderId,
                    $msg['sender'],
                    $receiverId,
                    $receiverType,
                    $msg['message'],
                    $isRead
                ]);
            }

            // Update conversation last_message_at
            $stmt = $pdo->prepare("
                UPDATE conversations 
                SET last_message_at = NOW(),
                    company_unread_count = (SELECT COUNT(*) FROM messages WHERE conversation_id = ? AND sender_type = 'driver' AND is_read = 0),
                    driver_unread_count = (SELECT COUNT(*) FROM messages WHERE conversation_id = ? AND sender_type = 'company' AND is_read = 0)
                WHERE id = ?
            ");
            $stmt->execute([$conversationId, $conversationId, $conversationId]);

            echo "<p>✅ Δημιουργήθηκε συνομιλία με {$otherDriver['first_name']} {$otherDriver['last_name']} για τη θέση: {$job['title']}</p>";
        } else {
            echo "<p>⚠️ Υπάρχει ήδη συνομιλία με {$otherDriver['first_name']} {$otherDriver['last_name']}</p>";
        }
    }

    // Show current conversations
    echo "<hr><h3>Τρέχουσες συνομιλίες για την εταιρεία (ID: $companyId):</h3>";
    $stmt = $pdo->prepare("
        SELECT c.*, d.first_name, d.last_name, j.title,
               (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id) as message_count
        FROM conversations c
        JOIN drivers d ON c.driver_id = d.id
        JOIN job_listings j ON c.job_id = j.id
        WHERE c.company_id = ?
        ORDER BY c.updated_at DESC
    ");
    $stmt->execute([$companyId]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Οδηγός</th><th>Θέση</th><th>Μηνύματα</th><th>Ημερομηνία</th></tr>";
    foreach ($conversations as $conv) {
        echo "<tr>";
        echo "<td>{$conv['id']}</td>";
        echo "<td>{$conv['first_name']} {$conv['last_name']}</td>";
        echo "<td>{$conv['title']}</td>";
        echo "<td>{$conv['message_count']}</td>";
        echo "<td>{$conv['updated_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p>❌ Σφάλμα: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='/drivejob/public/login.php'>Login Page</a></p>";
echo "<p>Company: info@thessdrive.gr / 123456</p>";
echo "<p>Driver: kostas.michailidis@hotmail.gr / 123456</p>";

<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;

$pdo = Database::getInstance()->getConnection();

echo "<h2>Δημιουργία Συνομιλιών για Οδηγό</h2>";

try {
    // Get driver ID
    $stmt = $pdo->prepare("SELECT id FROM drivers WHERE email = ?");
    $stmt->execute(['kostas.michailidis@hotmail.gr']);
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);
    $driverId = $driver['id'];
    echo "<p>Driver ID (kostas.michailidis@hotmail.gr): $driverId</p>";

    // Get other companies (not Thessdrive)
    $stmt = $pdo->query("SELECT id, company_name FROM companies WHERE id != 2 LIMIT 3");
    $otherCompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($otherCompanies)) {
        echo "<p>⚠️ Δεν βρέθηκαν άλλες εταιρείες</p>";

        // Create a test company
        $stmt = $pdo->prepare("
            INSERT INTO companies (company_name, email, password, city, address, phone, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
        ");

        $testCompanies = [
            ['Transport Solutions Ltd', 'info@transportsolutions.gr', password_hash('123456', PASSWORD_DEFAULT), 'Αθήνα', 'Κηφισίας 100', '2101234567'],
            ['Logistics Express', 'contact@logisticsexpress.gr', password_hash('123456', PASSWORD_DEFAULT), 'Πειραιάς', 'Ακτή Μιαούλη 50', '2104567890']
        ];

        foreach ($testCompanies as $tc) {
            $stmt->execute($tc);
        }

        // Get the new companies
        $stmt = $pdo->query("SELECT id, company_name FROM companies WHERE id != 2 LIMIT 3");
        $otherCompanies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo "<h3>Δημιουργία συνομιλιών με άλλες εταιρείες:</h3>";

    foreach ($otherCompanies as $company) {
        // Create a job listing for this company if it doesn't have one
        $stmt = $pdo->prepare("SELECT id, title FROM job_listings WHERE company_id = ? AND listing_type = 'job_offer' LIMIT 1");
        $stmt->execute([$company['id']]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            // Create a job listing
            $stmt = $pdo->prepare("
                INSERT INTO job_listings (
                    company_id, title, listing_type, transport_type, job_type,
                    required_license, description, salary_min, salary_max, salary_type,
                    location, experience_years, status, created_at
                ) VALUES (?, ?, 'job_offer', 'freight', 'full_time', 'C', ?, 1300, 1800, 'monthly', ?, 2, 'active', NOW())
            ");

            $jobTitle = 'Οδηγός για ' . $company['company_name'];
            $jobDesc = 'Ζητείται έμπειρος οδηγός για μεταφορές. Ανταγωνιστικές αποδοχές και άριστο εργασιακό περιβάλλον.';
            $location = 'Αττική';

            $stmt->execute([$company['id'], $jobTitle, $jobDesc, $location]);
            $jobId = $pdo->lastInsertId();

            $job = ['id' => $jobId, 'title' => $jobTitle];
        }

        // Check if conversation already exists
        $stmt = $pdo->prepare("
            SELECT id FROM conversations 
            WHERE company_id = ? AND driver_id = ?
        ");
        $stmt->execute([$company['id'], $driverId]);
        $existing = $stmt->fetch();

        if (!$existing) {
            // Create conversation
            $stmt = $pdo->prepare("
                INSERT INTO conversations (
                    company_id, driver_id, job_id, 
                    participant1_id, participant2_id,
                    participant1_type, participant2_type,
                    subject, status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, 'company', 'driver', ?, 'active', NOW(), NOW())
            ");

            $subject = "Ερώτηση για: " . $job['title'];
            $stmt->execute([
                $company['id'],
                $driverId,
                $job['id'],
                $company['id'],
                $driverId,
                $subject
            ]);

            $conversationId = $pdo->lastInsertId();

            // Add messages
            $messages = [
                ['sender' => 'driver', 'message' => 'Καλησπέρα, είδα την αγγελία σας και ενδιαφέρομαι. Ποιες είναι οι ακριβείς απαιτήσεις;'],
                ['sender' => 'company', 'message' => 'Καλησπέρα! Χαιρόμαστε για το ενδιαφέρον σας. Απαιτείται δίπλωμα C και εμπειρία 2 ετών.'],
                ['sender' => 'driver', 'message' => 'Έχω δίπλωμα C+E και 10 χρόνια εμπειρία. Ποιο είναι το ωράριο εργασίας;'],
                ['sender' => 'company', 'message' => 'Τέλεια! Το ωράριο είναι 08:00-16:00, Δευτέρα-Παρασκευή. Θα θέλατε να κανονίσουμε συνέντευξη;'],
                ['sender' => 'driver', 'message' => 'Ναι, είμαι διαθέσιμος οποιαδήποτε μέρα αυτή την εβδομάδα.']
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
                    $senderId = $driverId;
                    $receiverId = $company['id'];
                    $receiverType = 'company';
                    $isRead = 0; // Unread by company
                } else {
                    $senderId = $company['id'];
                    $receiverId = $driverId;
                    $receiverType = 'driver';
                    $isRead = rand(0, 1); // Random read status
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

            // Update conversation
            $stmt = $pdo->prepare("
                UPDATE conversations 
                SET last_message_at = NOW(),
                    company_unread_count = (SELECT COUNT(*) FROM messages WHERE conversation_id = ? AND sender_type = 'driver' AND is_read = 0),
                    driver_unread_count = (SELECT COUNT(*) FROM messages WHERE conversation_id = ? AND sender_type = 'company' AND is_read = 0)
                WHERE id = ?
            ");
            $stmt->execute([$conversationId, $conversationId, $conversationId]);

            echo "<p>✅ Δημιουργήθηκε συνομιλία με {$company['company_name']} για τη θέση: {$job['title']}</p>";
        } else {
            echo "<p>⚠️ Υπάρχει ήδη συνομιλία με {$company['company_name']}</p>";
        }
    }

    // Show driver's conversations
    echo "<hr><h3>Συνομιλίες του οδηγού (ID: $driverId):</h3>";
    $stmt = $pdo->prepare("
        SELECT c.*, comp.company_name, j.title,
               (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id) as message_count
        FROM conversations c
        JOIN companies comp ON c.company_id = comp.id
        JOIN job_listings j ON c.job_id = j.id
        WHERE c.driver_id = ?
        ORDER BY c.updated_at DESC
    ");
    $stmt->execute([$driverId]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Εταιρεία</th><th>Θέση</th><th>Μηνύματα</th><th>Ημερομηνία</th></tr>";
    foreach ($conversations as $conv) {
        echo "<tr>";
        echo "<td>{$conv['id']}</td>";
        echo "<td>{$conv['company_name']}</td>";
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
echo "<p>Τώρα μπορείτε να συνδεθείτε και να δείτε πολλαπλές συνομιλίες:</p>";
echo "<p><strong>Οδηγός:</strong> kostas.michailidis@hotmail.gr / 123456</p>";
echo "<p><strong>Εταιρεία:</strong> info@thessdrive.gr / 123456</p>";

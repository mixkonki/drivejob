<?php

/**
 * Create messaging system tables
 * For communication between companies and drivers
 */

require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Core\Database;

try {
    $pdo = Database::getInstance()->getConnection();

    // Create conversations table
    $sql = "CREATE TABLE IF NOT EXISTS conversations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        driver_id INT NOT NULL,
        job_id INT DEFAULT NULL,
        subject VARCHAR(255) NOT NULL,
        status ENUM('active', 'archived', 'deleted') DEFAULT 'active',
        last_message_at TIMESTAMP NULL,
        company_unread_count INT DEFAULT 0,
        driver_unread_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
        FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
        FOREIGN KEY (job_id) REFERENCES job_listings(id) ON DELETE SET NULL,
        
        INDEX idx_company_status (company_id, status),
        INDEX idx_driver_status (driver_id, status),
        INDEX idx_last_message (last_message_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);
    echo "✅ Created conversations table\n";

    // Create messages table
    $sql = "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        conversation_id INT NOT NULL,
        sender_type ENUM('company', 'driver') NOT NULL,
        sender_id INT NOT NULL,
        message TEXT NOT NULL,
        attachments JSON DEFAULT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        read_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
        
        INDEX idx_conversation_created (conversation_id, created_at),
        INDEX idx_unread (conversation_id, is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);
    echo "✅ Created messages table\n";

    // Create message_templates table for quick responses
    $sql = "CREATE TABLE IF NOT EXISTS message_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        title VARCHAR(100) NOT NULL,
        content TEXT NOT NULL,
        category VARCHAR(50) DEFAULT 'general',
        usage_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
        
        INDEX idx_company_category (company_id, category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);
    echo "✅ Created message_templates table\n";

    // Create notifications table for real-time alerts
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

    $pdo->exec($sql);
    echo "✅ Created notifications table\n";

    // Insert sample message templates
    $templates = [
        ['title' => 'Πρόσκληση σε Συνέντευξη', 'content' => 'Γεια σας, θα θέλαμε να σας προσκαλέσουμε σε συνέντευξη για τη θέση {job_title}. Είστε διαθέσιμος;', 'category' => 'interview'],
        ['title' => 'Αίτημα Εγγράφων', 'content' => 'Παρακαλούμε στείλτε μας αντίγραφο της άδειας οδήγησης και του ΠΕΙ σας.', 'category' => 'documents'],
        ['title' => 'Επιβεβαίωση Ενδιαφέροντος', 'content' => 'Ευχαριστούμε για το ενδιαφέρον σας. Θα επικοινωνήσουμε μαζί σας σύντομα.', 'category' => 'general']
    ];

    $stmt = $pdo->prepare("INSERT INTO message_templates (company_id, title, content, category) VALUES (?, ?, ?, ?)");
    foreach ($templates as $template) {
        $stmt->execute([4, $template['title'], $template['content'], $template['category']]);
    }
    echo "✅ Inserted sample message templates\n";

    echo "\n✅ Messaging system tables created successfully!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

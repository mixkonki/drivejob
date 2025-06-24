<?php

require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Core\Database;

try {
    $pdo = Database::getInstance()->getConnection();

    echo "Fixing messaging tables...\n";

    // 1. Create conversations table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS conversations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            driver_id INT NOT NULL,
            job_id INT,
            subject VARCHAR(255) NOT NULL,
            status ENUM('active', 'closed') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_message_at TIMESTAMP NULL,
            company_unread_count INT DEFAULT 0,
            driver_unread_count INT DEFAULT 0,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
            FOREIGN KEY (job_id) REFERENCES job_listings(id) ON DELETE SET NULL,
            INDEX idx_company (company_id),
            INDEX idx_driver (driver_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Conversations table created/verified\n";

    // 2. Create messages table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            conversation_id INT NOT NULL,
            sender_type ENUM('company', 'driver') NOT NULL,
            sender_id INT NOT NULL,
            message TEXT NOT NULL,
            attachments JSON,
            is_read BOOLEAN DEFAULT FALSE,
            read_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
            INDEX idx_conversation (conversation_id),
            INDEX idx_sender (sender_type, sender_id),
            INDEX idx_read (is_read)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Messages table created/verified\n";

    // 3. Check if 'content' column exists and rename to 'message'
    $stmt = $pdo->query("SHOW COLUMNS FROM messages WHERE Field = 'content'");
    if ($stmt->rowCount() > 0) {
        $pdo->exec("ALTER TABLE messages CHANGE COLUMN content message TEXT NOT NULL");
        echo "✓ Renamed 'content' column to 'message'\n";
    }

    // 4. Create notifications table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_type ENUM('company', 'driver', 'admin') NOT NULL,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            data JSON,
            is_read BOOLEAN DEFAULT FALSE,
            read_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_type, user_id),
            INDEX idx_read (is_read),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Notifications table created/verified\n";

    // 5. Create message_templates table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS message_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            category VARCHAR(50),
            subject VARCHAR(255),
            content TEXT NOT NULL,
            usage_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            INDEX idx_company (company_id),
            INDEX idx_category (category)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Message templates table created/verified\n";

    // 6. Add missing columns to conversations if they don't exist
    $columns = ['last_message_at', 'company_unread_count', 'driver_unread_count'];
    foreach ($columns as $column) {
        $stmt = $pdo->query("SHOW COLUMNS FROM conversations WHERE Field = '$column'");
        if ($stmt->rowCount() == 0) {
            switch ($column) {
                case 'last_message_at':
                    $pdo->exec("ALTER TABLE conversations ADD COLUMN last_message_at TIMESTAMP NULL AFTER updated_at");
                    break;
                case 'company_unread_count':
                    $pdo->exec("ALTER TABLE conversations ADD COLUMN company_unread_count INT DEFAULT 0");
                    break;
                case 'driver_unread_count':
                    $pdo->exec("ALTER TABLE conversations ADD COLUMN driver_unread_count INT DEFAULT 0");
                    break;
            }
            echo "✓ Added column '$column' to conversations table\n";
        }
    }

    // 7. Update last_message_at for existing conversations
    $pdo->exec("
        UPDATE conversations c
        SET last_message_at = (
            SELECT MAX(created_at) 
            FROM messages m 
            WHERE m.conversation_id = c.id
        )
        WHERE last_message_at IS NULL
    ");
    echo "✓ Updated last_message_at for existing conversations\n";

    echo "\n✅ Messaging tables fixed successfully!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

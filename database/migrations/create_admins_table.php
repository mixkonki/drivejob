<?php

/**
 * Migration για τη δημιουργία του πίνακα admins
 * 
 * Δημιουργεί τον πίνακα για τους διαχειριστές του συστήματος
 * με όλα τα απαραίτητα πεδία για authentication και authorization
 */

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = require __DIR__ . '/../../config/database.php';

    echo "Δημιουργία πίνακα admins...\n";

    // Δημιουργία πίνακα admins
    $sql = "CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        role_level ENUM('super_admin', 'admin', 'moderator') DEFAULT 'admin',
        permissions JSON,
        is_active BOOLEAN DEFAULT TRUE,
        last_login TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        reset_code VARCHAR(255) NULL,
        reset_expires TIMESTAMP NULL,
        two_factor_enabled BOOLEAN DEFAULT FALSE,
        two_factor_secret VARCHAR(255) NULL,
        login_attempts INT DEFAULT 0,
        locked_until TIMESTAMP NULL,
        
        INDEX idx_email (email),
        INDEX idx_role_level (role_level),
        INDEX idx_is_active (is_active),
        INDEX idx_reset_code (reset_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);
    echo "✅ Πίνακας admins δημιουργήθηκε επιτυχώς\n";

    // Δημιουργία default super admin
    echo "Δημιουργία default super admin...\n";

    $defaultAdminEmail = 'admin@drivejob.gr';
    $defaultAdminPassword = password_hash('DriveJob2025!', PASSWORD_DEFAULT);

    // Έλεγχος αν υπάρχει ήδη admin
    $checkSql = "SELECT COUNT(*) FROM admins WHERE email = :email";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute(['email' => $defaultAdminEmail]);

    if ($checkStmt->fetchColumn() == 0) {
        $insertSql = "INSERT INTO admins (
            email, 
            password, 
            first_name, 
            last_name, 
            role_level, 
            permissions,
            is_active
        ) VALUES (
            :email, 
            :password, 
            :first_name, 
            :last_name, 
            :role_level, 
            :permissions,
            :is_active
        )";

        $permissions = json_encode([
            'users' => ['create', 'read', 'update', 'delete'],
            'companies' => ['create', 'read', 'update', 'delete'],
            'drivers' => ['create', 'read', 'update', 'delete'],
            'job_listings' => ['create', 'read', 'update', 'delete'],
            'system' => ['settings', 'logs', 'analytics', 'backup'],
            'billing' => ['view', 'manage', 'reports'],
            'support' => ['tickets', 'chat', 'knowledge_base']
        ]);

        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->execute([
            'email' => $defaultAdminEmail,
            'password' => $defaultAdminPassword,
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'role_level' => 'super_admin',
            'permissions' => $permissions,
            'is_active' => 1
        ]);

        echo "✅ Default super admin δημιουργήθηκε:\n";
        echo "   Email: {$defaultAdminEmail}\n";
        echo "   Password: DriveJob2025!\n";
        echo "   ⚠️  ΣΗΜΑΝΤΙΚΟ: Αλλάξτε τον κωδικό μετά την πρώτη σύνδεση!\n";
    } else {
        echo "ℹ️  Super admin υπάρχει ήδη\n";
    }

    // Δημιουργία πίνακα admin_sessions για session management
    echo "Δημιουργία πίνακα admin_sessions...\n";

    $sessionsSql = "CREATE TABLE IF NOT EXISTS admin_sessions (
        id VARCHAR(128) PRIMARY KEY,
        admin_id INT NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
        INDEX idx_admin_id (admin_id),
        INDEX idx_last_activity (last_activity)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sessionsSql);
    echo "✅ Πίνακας admin_sessions δημιουργήθηκε επιτυχώς\n";

    // Δημιουργία πίνακα admin_activity_logs
    echo "Δημιουργία πίνακα admin_activity_logs...\n";

    $activityLogsSql = "CREATE TABLE IF NOT EXISTS admin_activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        resource_type VARCHAR(50),
        resource_id INT,
        details JSON,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
        INDEX idx_admin_id (admin_id),
        INDEX idx_action (action),
        INDEX idx_resource (resource_type, resource_id),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($activityLogsSql);
    echo "✅ Πίνακας admin_activity_logs δημιουργήθηκε επιτυχώς\n";

    echo "\n🎉 Admin system migration ολοκληρώθηκε επιτυχώς!\n";
    echo "\nΕπόμενα βήματα:\n";
    echo "1. Συνδεθείτε ως admin στο: /admin/login\n";
    echo "2. Αλλάξτε τον default κωδικό\n";
    echo "3. Δημιουργήστε επιπλέον admin χρήστες αν χρειάζεται\n";
} catch (PDOException $e) {
    echo "❌ Σφάλμα κατά τη δημιουργία του admin system: " . $e->getMessage() . "\n";
    exit(1);
}

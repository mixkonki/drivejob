<?php

/**
 * Migration: create_system_monitoring_tables
 * 
 * Δημιουργία πινάκων για το σύστημα παρακολούθησης
 */

// Σύνδεση στη βάση δεδομένων
$pdo = require __DIR__ . '/../../config/database.php';

try {
    // Πίνακας για τα σφάλματα συστήματος
    $pdo->exec("CREATE TABLE IF NOT EXISTS error_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        file VARCHAR(255),
        line INT,
        trace TEXT,
        context TEXT,
        user_id INT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Πίνακας για τα logs απόδοσης
    $pdo->exec("CREATE TABLE IF NOT EXISTS performance_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_uri VARCHAR(255) NOT NULL,
        method VARCHAR(10) NOT NULL,
        response_time FLOAT NOT NULL,
        memory_usage INT NOT NULL,
        user_id INT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Πίνακας για τα logs χρήσης
    $pdo->exec("CREATE TABLE IF NOT EXISTS usage_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        page VARCHAR(255) NOT NULL,
        action VARCHAR(50),
        is_mobile TINYINT(1) DEFAULT 0,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Πίνακας για τα γενικά logs συστήματος
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL,
        message TEXT NOT NULL,
        context TEXT,
        user_id INT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Πίνακας για τα αντίγραφα ασφαλείας
    $pdo->exec("CREATE TABLE IF NOT EXISTS database_backups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        size BIGINT NOT NULL,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Δημιουργία ευρετηρίων για καλύτερη απόδοση
    $pdo->exec("CREATE INDEX idx_error_logs_type ON error_logs (type)");
    $pdo->exec("CREATE INDEX idx_error_logs_created_at ON error_logs (created_at)");
    $pdo->exec("CREATE INDEX idx_error_logs_user_id ON error_logs (user_id)");

    $pdo->exec("CREATE INDEX idx_performance_logs_created_at ON performance_logs (created_at)");
    $pdo->exec("CREATE INDEX idx_performance_logs_user_id ON performance_logs (user_id)");

    $pdo->exec("CREATE INDEX idx_usage_logs_user_id ON usage_logs (user_id)");
    $pdo->exec("CREATE INDEX idx_usage_logs_created_at ON usage_logs (created_at)");
    $pdo->exec("CREATE INDEX idx_usage_logs_page ON usage_logs (page)");

    $pdo->exec("CREATE INDEX idx_system_logs_type ON system_logs (type)");
    $pdo->exec("CREATE INDEX idx_system_logs_created_at ON system_logs (created_at)");
    $pdo->exec("CREATE INDEX idx_system_logs_user_id ON system_logs (user_id)");

    echo "Οι πίνακες για το σύστημα παρακολούθησης δημιουργήθηκαν επιτυχώς.\n";
} catch (PDOException $e) {
    die("Σφάλμα κατά τη δημιουργία των πινάκων: " . $e->getMessage() . "\n");
}

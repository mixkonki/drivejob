<?php

/**
 * Δημιουργία του πίνακα match_preferences
 * 
 * Αυτός ο πίνακας αποθηκεύει τις προτιμήσεις ταιριάσματος των χρηστών (οδηγών και εταιρειών)
 */

// Φόρτωση των παραμέτρων σύνδεσης
$dbConfig = require_once __DIR__ . '/../../config/database.php';

try {
    // Χρήση του PDO από το config/database.php
    $pdo = $dbConfig;

    // Έλεγχος αν υπάρχει ήδη ο πίνακας
    $stmt = $pdo->query("SHOW TABLES LIKE 'match_preferences'");
    if ($stmt->rowCount() > 0) {
        echo "Ο πίνακας match_preferences υπάρχει ήδη.\n";
        exit;
    }

    // Δημιουργία του πίνακα match_preferences
    $sql = "CREATE TABLE match_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_type ENUM('driver', 'company') NOT NULL,
        location_weight DECIMAL(3,2) DEFAULT 0.8,
        job_type_weight DECIMAL(3,2) DEFAULT 0.7,
        vehicle_type_weight DECIMAL(3,2) DEFAULT 0.9,
        license_weight DECIMAL(3,2) DEFAULT 1.0,
        experience_weight DECIMAL(3,2) DEFAULT 0.6,
        skills_weight DECIMAL(3,2) DEFAULT 0.5,
        schedule_weight DECIMAL(3,2) DEFAULT 0.4,
        rating_weight DECIMAL(3,2) DEFAULT 0.3,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user (user_id, user_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);
    echo "Ο πίνακας match_preferences δημιουργήθηκε με επιτυχία.\n";
} catch (PDOException $e) {
    die("Σφάλμα κατά τη δημιουργία του πίνακα match_preferences: " . $e->getMessage() . "\n");
}

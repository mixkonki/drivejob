<?php

/**
 * Δημιουργία του πίνακα match_history
 * 
 * Αυτός ο πίνακας αποθηκεύει το ιστορικό ταιριάσματος μεταξύ οδηγών και αγγελιών
 */

// Φόρτωση των παραμέτρων σύνδεσης
$dbConfig = require_once __DIR__ . '/../../config/database.php';

try {
    // Χρήση του PDO από το config/database.php
    $pdo = $dbConfig;

    // Έλεγχος αν υπάρχει ήδη ο πίνακας
    $stmt = $pdo->query("SHOW TABLES LIKE 'match_history'");
    if ($stmt->rowCount() > 0) {
        echo "Ο πίνακας match_history υπάρχει ήδη.\n";
        exit;
    }

    // Δημιουργία του πίνακα match_history
    $sql = "CREATE TABLE match_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        driver_id INT NOT NULL,
        job_listing_id INT NOT NULL,
        match_score DECIMAL(5,2) NOT NULL,
        driver_action ENUM('viewed', 'applied', 'rejected', 'no_action') DEFAULT 'no_action',
        company_action ENUM('viewed', 'accepted', 'rejected', 'no_action') DEFAULT 'no_action',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_match (driver_id, job_listing_id),
        INDEX idx_driver_id (driver_id),
        INDEX idx_job_listing_id (job_listing_id),
        INDEX idx_match_score (match_score),
        FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
        FOREIGN KEY (job_listing_id) REFERENCES job_listings(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);
    echo "Ο πίνακας match_history δημιουργήθηκε με επιτυχία.\n";
} catch (PDOException $e) {
    die("Σφάλμα κατά τη δημιουργία του πίνακα match_history: " . $e->getMessage() . "\n");
}

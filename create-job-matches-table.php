<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=drivejob;charset=utf8', 'root', '');

    $sql = "CREATE TABLE IF NOT EXISTS job_matches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        driver_id INT NOT NULL,
        company_listing_id INT NOT NULL,
        match_score DECIMAL(5,2) DEFAULT 0.00,
        is_viewed_by_driver TINYINT(1) DEFAULT 0,
        is_viewed_by_company TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_driver_id (driver_id),
        INDEX idx_company_listing_id (company_listing_id),
        INDEX idx_match_score (match_score),
        UNIQUE KEY unique_match (driver_id, company_listing_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);
    echo "✅ job_matches table created successfully!\n";

    // Check if table was created
    $stmt = $pdo->query("SHOW TABLES LIKE 'job_matches'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table confirmed to exist\n";
    } else {
        echo "❌ Table creation failed\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

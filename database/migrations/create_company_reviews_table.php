<?php

// database/migrations/create_company_reviews_table.php

// Ορισμός του ROOT_DIR
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__, 2));
}

try {
    // Σύνδεση στη βάση δεδομένων
    require_once ROOT_DIR . '/config/database.php';
    $db = $pdo;

    // Έλεγχος αν ο πίνακας υπάρχει ήδη
    $checkTableQuery = "SHOW TABLES LIKE 'company_reviews'";
    $stmt = $db->prepare($checkTableQuery);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo "Ο πίνακας 'company_reviews' υπάρχει ήδη.\n";
    } else {
        // Δημιουργία του πίνακα company_reviews
        $createTableQuery = "
        CREATE TABLE company_reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            driver_id INT NOT NULL,
            rating DECIMAL(3,1) NOT NULL,
            reliability_rating DECIMAL(3,1) NULL,
            communication_rating DECIMAL(3,1) NULL,
            payment_rating DECIMAL(3,1) NULL,
            working_conditions_rating DECIMAL(3,1) NULL,
            comment TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE,
            UNIQUE KEY unique_review (company_id, driver_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $db->exec($createTableQuery);
        echo "Ο πίνακας 'company_reviews' δημιουργήθηκε με επιτυχία.\n";

        // Δημιουργία του πίνακα company_ratings για τις συνολικές βαθμολογίες
        $createRatingsTableQuery = "
        CREATE TABLE company_ratings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            reliability_score DECIMAL(5,2) DEFAULT 0,
            communication_score DECIMAL(5,2) DEFAULT 0,
            payment_score DECIMAL(5,2) DEFAULT 0,
            working_conditions_score DECIMAL(5,2) DEFAULT 0,
            total_score DECIMAL(5,2) DEFAULT 0,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
            UNIQUE KEY unique_company_rating (company_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        $db->exec($createRatingsTableQuery);
        echo "Ο πίνακας 'company_ratings' δημιουργήθηκε με επιτυχία.\n";

        // Έλεγχος αν οι στήλες rating και rating_count υπάρχουν ήδη στον πίνακα companies
        $checkColumnsQuery = "SHOW COLUMNS FROM companies LIKE 'rating'";
        $stmt = $db->prepare($checkColumnsQuery);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            // Προσθήκη στηλών rating και rating_count στον πίνακα companies
            $alterCompaniesTableQuery = "
            ALTER TABLE companies 
            ADD COLUMN rating DECIMAL(3,1) DEFAULT 0,
            ADD COLUMN rating_count INT DEFAULT 0;
            ";

            $db->exec($alterCompaniesTableQuery);
            echo "Οι στήλες 'rating' και 'rating_count' προστέθηκαν στον πίνακα 'companies'.\n";
        } else {
            echo "Οι στήλες 'rating' και 'rating_count' υπάρχουν ήδη στον πίνακα 'companies'.\n";
        }
    }

    echo "Η διαδικασία migration ολοκληρώθηκε με επιτυχία.\n";
} catch (PDOException $e) {
    die("Σφάλμα κατά τη δημιουργία του πίνακα: " . $e->getMessage() . "\n");
}

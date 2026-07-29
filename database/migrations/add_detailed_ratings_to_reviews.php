<?php

/**
 * Προσθήκη πεδίων για λεπτομερείς αξιολογήσεις στους πίνακες driver_reviews και company_reviews
 */

require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Core\Logger;

try {
    // Χρήση του config/database.php για τη σύνδεση με τη βάση δεδομένων
    $pdo = require_once __DIR__ . '/../../config/database.php';

    // Έλεγχος αν υπάρχουν ήδη τα πεδία στον πίνακα driver_reviews
    $checkDriverReviewsColumns = "SHOW COLUMNS FROM driver_reviews LIKE 'professionalism_rating'";
    $driverReviewsColumnsExist = $pdo->query($checkDriverReviewsColumns)->rowCount() > 0;

    // Έλεγχος αν υπάρχουν ήδη τα πεδία στον πίνακα company_reviews
    $checkCompanyReviewsColumns = "SHOW COLUMNS FROM company_reviews LIKE 'reliability_rating'";
    $companyReviewsColumnsExist = $pdo->query($checkCompanyReviewsColumns)->rowCount() > 0;

    // Προσθήκη πεδίων στον πίνακα driver_reviews αν δεν υπάρχουν ήδη
    if (!$driverReviewsColumnsExist) {
        $alterDriverReviewsTable = "ALTER TABLE driver_reviews 
            ADD COLUMN professionalism_rating FLOAT DEFAULT NULL COMMENT 'Βαθμολογία επαγγελματισμού (0-5)',
            ADD COLUMN driving_skills_rating FLOAT DEFAULT NULL COMMENT 'Βαθμολογία οδηγικών ικανοτήτων (0-5)',
            ADD COLUMN reliability_rating FLOAT DEFAULT NULL COMMENT 'Βαθμολογία αξιοπιστίας (0-5)',
            ADD COLUMN communication_rating FLOAT DEFAULT NULL COMMENT 'Βαθμολογία επικοινωνίας (0-5)',
            ADD COLUMN technical_skills_rating FLOAT DEFAULT NULL COMMENT 'Βαθμολογία τεχνικών δεξιοτήτων (0-5)'";

        $pdo->exec($alterDriverReviewsTable);
        echo "Προστέθηκαν τα πεδία λεπτομερών αξιολογήσεων στον πίνακα driver_reviews.\n";
    } else {
        echo "Τα πεδία λεπτομερών αξιολογήσεων υπάρχουν ήδη στον πίνακα driver_reviews.\n";
    }

    // Προσθήκη πεδίων στον πίνακα company_reviews αν δεν υπάρχουν ήδη
    if (!$companyReviewsColumnsExist) {
        $alterCompanyReviewsTable = "ALTER TABLE company_reviews 
            ADD COLUMN reliability_rating FLOAT DEFAULT NULL COMMENT 'Βαθμολογία αξιοπιστίας (0-5)',
            ADD COLUMN communication_rating FLOAT DEFAULT NULL COMMENT 'Βαθμολογία επικοινωνίας (0-5)',
            ADD COLUMN payment_rating FLOAT DEFAULT NULL COMMENT 'Βαθμολογία πληρωμής (0-5)',
            ADD COLUMN working_conditions_rating FLOAT DEFAULT NULL COMMENT 'Βαθμολογία συνθηκών εργασίας (0-5)'";

        $pdo->exec($alterCompanyReviewsTable);
        echo "Προστέθηκαν τα πεδία λεπτομερών αξιολογήσεων στον πίνακα company_reviews.\n";
    } else {
        echo "Τα πεδία λεπτομερών αξιολογήσεων υπάρχουν ήδη στον πίνακα company_reviews.\n";
    }

    echo "Η προσθήκη των πεδίων λεπτομερών αξιολογήσεων ολοκληρώθηκε με επιτυχία.\n";
} catch (PDOException $e) {
    Logger::error('Σφάλμα κατά την προσθήκη των πεδίων λεπτομερών αξιολογήσεων: ' . $e->getMessage());
    echo "Σφάλμα: " . $e->getMessage() . "\n";
}

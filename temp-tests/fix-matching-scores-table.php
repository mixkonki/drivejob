<?php
require_once '../src/bootstrap.php';

use Drivejob\Core\Database;

try {
    $pdo = Database::getInstance()->getConnection();

    echo "=== ΔΙΌΡΘΩΣΗ ΠΊΝΑΚΑ MATCHING_SCORES ===\n\n";

    // Έλεγχος τρέχουσας δομής
    echo "Τρέχουσα δομή overall_score:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM matching_scores LIKE 'overall_score'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "- Type: {$column['Type']}\n\n";

    // Αλλαγή του τύπου δεδομένων για να υποστηρίζει τιμές 0-100
    echo "Αλλαγή τύπου δεδομένων...\n";

    $alterQueries = [
        "ALTER TABLE matching_scores MODIFY COLUMN overall_score DECIMAL(6,2) NOT NULL",
        "ALTER TABLE matching_scores MODIFY COLUMN skill_match_score DECIMAL(5,4) NULL",
        "ALTER TABLE matching_scores MODIFY COLUMN location_match_score DECIMAL(5,4) NULL",
        "ALTER TABLE matching_scores MODIFY COLUMN experience_match_score DECIMAL(5,4) NULL",
        "ALTER TABLE matching_scores MODIFY COLUMN availability_match_score DECIMAL(5,4) NULL",
        "ALTER TABLE matching_scores MODIFY COLUMN ml_confidence DECIMAL(5,4) NULL"
    ];

    foreach ($alterQueries as $query) {
        try {
            $pdo->exec($query);
            echo "✓ Εκτελέστηκε: " . substr($query, 0, 50) . "...\n";
        } catch (Exception $e) {
            echo "✗ Σφάλμα: " . $e->getMessage() . "\n";
        }
    }

    echo "\nΝέα δομή overall_score:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM matching_scores LIKE 'overall_score'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "- Type: {$column['Type']}\n\n";

    // Δοκιμή insert με νέα δομή
    echo "Δοκιμή insert με νέα δομή...\n";

    $testDriverId = 26; // Χρήση υπάρχοντος driver
    $testJobId = 15;    // Χρήση υπάρχοντος job
    $testOverallScore = 75.50;

    $stmt = $pdo->prepare("
        UPDATE matching_scores 
        SET overall_score = ?
        WHERE driver_id = ? AND job_id = ?
    ");

    $result = $stmt->execute([$testOverallScore, $testDriverId, $testJobId]);

    if ($result) {
        echo "Update επιτυχής!\n";

        // Έλεγχος τι αποθηκεύτηκε
        $stmt = $pdo->prepare("
            SELECT overall_score 
            FROM matching_scores 
            WHERE driver_id = ? AND job_id = ?
        ");
        $stmt->execute([$testDriverId, $testJobId]);
        $stored = $stmt->fetch(PDO::FETCH_ASSOC);

        echo "Αποθηκεύτηκε: {$stored['overall_score']}%\n";
        echo "Αναμενόμενο: {$testOverallScore}%\n\n";

        if (abs($stored['overall_score'] - $testOverallScore) < 0.01) {
            echo "✓ Η διόρθωση λειτούργησε!\n";
        } else {
            echo "✗ Εξακολουθεί να υπάρχει πρόβλημα.\n";
        }
    } else {
        echo "Update απέτυχε!\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

<?php
require_once '../src/bootstrap.php';

use Drivejob\Core\Database;

try {
    $pdo = Database::getInstance()->getConnection();

    echo "=== ΈΛΕΓΧΟΣ ΠΊΝΑΚΑ MATCHING_SCORES ===\n\n";

    // Έλεγχος δομής πίνακα
    $stmt = $pdo->query("DESCRIBE matching_scores");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Δομή πίνακα matching_scores:\n";
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']}) - Null: {$col['Null']} - Default: {$col['Default']}\n";
    }
    echo "\n";

    // Έλεγχος τιμών που αποθηκεύονται
    $stmt = $pdo->query("
        SELECT 
            driver_id,
            job_id,
            overall_score,
            skill_match_score,
            location_match_score,
            experience_match_score,
            availability_match_score,
            created_at
        FROM matching_scores 
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Πρόσφατα αποθηκευμένα scores:\n";
    foreach ($recent as $row) {
        echo "Driver {$row['driver_id']} -> Job {$row['job_id']} ({$row['created_at']}):\n";
        echo "  Overall: {$row['overall_score']}%\n";
        echo "  Skills: {$row['skill_match_score']}\n";
        echo "  Location: {$row['location_match_score']}\n";
        echo "  Experience: {$row['experience_match_score']}\n";
        echo "  Availability: {$row['availability_match_score']}\n\n";
    }

    // Δοκιμή manual insert για να δούμε αν το πρόβλημα είναι στην αποθήκευση
    echo "Δοκιμή manual insert...\n";

    $testDriverId = 999;
    $testJobId = 999;
    $testOverallScore = 75.5;
    $testSkillScore = 0.8;

    $stmt = $pdo->prepare("
        INSERT INTO matching_scores 
        (driver_id, job_id, overall_score, skill_match_score, location_match_score, 
         experience_match_score, availability_match_score, factors, ml_confidence)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            overall_score = VALUES(overall_score),
            skill_match_score = VALUES(skill_match_score)
    ");

    $result = $stmt->execute([
        $testDriverId,
        $testJobId,
        $testOverallScore,
        $testSkillScore,
        0.6,
        0.7,
        0.9,
        '{"test": true}',
        0.85
    ]);

    if ($result) {
        echo "Manual insert επιτυχής!\n";

        // Έλεγχος τι αποθηκεύτηκε
        $stmt = $pdo->prepare("
            SELECT overall_score, skill_match_score 
            FROM matching_scores 
            WHERE driver_id = ? AND job_id = ?
        ");
        $stmt->execute([$testDriverId, $testJobId]);
        $stored = $stmt->fetch(PDO::FETCH_ASSOC);

        echo "Αποθηκεύτηκε: Overall = {$stored['overall_score']}%, Skills = {$stored['skill_match_score']}\n";
        echo "Αναμενόμενο: Overall = {$testOverallScore}%, Skills = {$testSkillScore}\n\n";

        // Καθαρισμός test data
        $pdo->prepare("DELETE FROM matching_scores WHERE driver_id = ? AND job_id = ?")->execute([$testDriverId, $testJobId]);
        echo "Test data καθαρίστηκε.\n";
    } else {
        echo "Manual insert απέτυχε!\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

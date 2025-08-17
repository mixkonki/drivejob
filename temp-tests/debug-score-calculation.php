<?php
require_once '../src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Services\AI\ScoreCalculator;

try {
    $pdo = Database::getInstance()->getConnection();

    echo "=== ΈΛΕΓΧΟΣ ΥΠΟΛΟΓΙΣΜΟΥ SCORES ===\n\n";

    // Έλεγχος δομής πίνακα drivers
    $stmt = $pdo->query("DESCRIBE drivers");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Δομή πίνακα drivers:\n";
    foreach ($columns as $col) {
        echo "- {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";

    // Δοκιμή υπολογισμού με πραγματικά δεδομένα
    $calculator = new ScoreCalculator();

    // Παράδειγμα scores που βλέπουμε στη βάση
    $testScores = [
        'skill_match' => 0.9,
        'location_match' => 0.5,
        'experience_match' => 0.2,
        'availability_match' => 1.0
    ];

    echo "Test scores:\n";
    foreach ($testScores as $key => $value) {
        echo "- {$key}: {$value}\n";
    }
    echo "\n";

    $overallScore = $calculator->calculateOverallScore($testScores);
    echo "Υπολογισμένο overall score: {$overallScore}%\n\n";

    // Έλεγχος βαρών
    $weights = $calculator->getWeights();
    echo "Βάρη κριτηρίων:\n";
    foreach ($weights as $criterion => $weight) {
        echo "- {$criterion}: {$weight}\n";
    }
    echo "\n";

    // Χειροκίνητος υπολογισμός
    $manualScore = 0;
    $totalWeight = 0;
    foreach ($weights as $criterion => $weight) {
        if (isset($testScores[$criterion])) {
            $manualScore += $testScores[$criterion] * $weight;
            $totalWeight += $weight;
            echo "Adding: {$testScores[$criterion]} * {$weight} = " . ($testScores[$criterion] * $weight) . "\n";
        }
    }

    $normalizedScore = $totalWeight > 0 ? ($manualScore / $totalWeight) * 100 : 0;
    echo "Χειροκίνητος υπολογισμός: {$manualScore} / {$totalWeight} * 100 = {$normalizedScore}%\n\n";

    // Έλεγχος πραγματικών δεδομένων από τη βάση
    $stmt = $pdo->query("
        SELECT 
            skill_match_score,
            location_match_score,
            experience_match_score,
            availability_match_score,
            overall_score
        FROM matching_scores 
        WHERE skill_match_score > 0 OR location_match_score > 0
        LIMIT 3
    ");
    $realScores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Πραγματικά δεδομένα από βάση:\n";
    foreach ($realScores as $i => $row) {
        echo "Δείγμα " . ($i + 1) . ":\n";
        echo "- Skills: {$row['skill_match_score']}\n";
        echo "- Location: {$row['location_match_score']}\n";
        echo "- Experience: {$row['experience_match_score']}\n";
        echo "- Availability: {$row['availability_match_score']}\n";
        echo "- Overall (αποθηκευμένο): {$row['overall_score']}%\n";

        // Επανυπολογισμός
        $recalculated = $calculator->calculateOverallScore([
            'skill_match' => $row['skill_match_score'],
            'location_match' => $row['location_match_score'],
            'experience_match' => $row['experience_match_score'],
            'availability_match' => $row['availability_match_score']
        ]);
        echo "- Overall (επανυπολογισμένο): {$recalculated}%\n\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

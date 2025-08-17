<?php
require_once '../src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Services\AI\MatchingService;
use Drivejob\Services\AI\ScoreCalculator;

try {
    $pdo = Database::getInstance()->getConnection();
    $matchingService = new MatchingService();
    $calculator = new ScoreCalculator();

    echo "=== ΕΠΑΝΥΠΟΛΟΓΙΣΜΟΣ ΌΛΩΝ ΤΩΝ MATCHING SCORES ===\n\n";

    // Λήψη όλων των υπαρχόντων matching scores
    $stmt = $pdo->query("
        SELECT 
            driver_id,
            job_id,
            skill_match_score,
            location_match_score,
            experience_match_score,
            availability_match_score,
            overall_score as old_score
        FROM matching_scores
    ");
    $allScores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Βρέθηκαν " . count($allScores) . " matching scores για επανυπολογισμό.\n\n";

    $updated = 0;
    $errors = 0;

    foreach ($allScores as $score) {
        try {
            // Επανυπολογισμός του overall score με τα υπάρχοντα individual scores
            $scores = [
                'skill_match' => $score['skill_match_score'],
                'location_match' => $score['location_match_score'],
                'experience_match' => $score['experience_match_score'],
                'availability_match' => $score['availability_match_score']
            ];

            $newOverallScore = $calculator->calculateOverallScore($scores);

            // Update στη βάση
            $updateStmt = $pdo->prepare("
                UPDATE matching_scores 
                SET overall_score = ?
                WHERE driver_id = ? AND job_id = ?
            ");

            $result = $updateStmt->execute([
                $newOverallScore,
                $score['driver_id'],
                $score['job_id']
            ]);

            if ($result) {
                $updated++;
                if ($updated % 20 == 0) {
                    echo "Ενημερώθηκαν {$updated} scores...\n";
                }
            } else {
                $errors++;
            }
        } catch (Exception $e) {
            $errors++;
            echo "Σφάλμα για Driver {$score['driver_id']} -> Job {$score['job_id']}: " . $e->getMessage() . "\n";
        }
    }

    echo "\n=== ΑΠΟΤΕΛΕΣΜΑΤΑ ===\n";
    echo "Επιτυχώς ενημερώθηκαν: {$updated} scores\n";
    echo "Σφάλματα: {$errors}\n\n";

    // Έλεγχος νέας κατανομής
    $stmt = $pdo->query("
        SELECT 
            CASE 
                WHEN overall_score >= 80 THEN '80-100%'
                WHEN overall_score >= 60 THEN '60-79%'
                WHEN overall_score >= 40 THEN '40-59%'
                WHEN overall_score >= 20 THEN '20-39%'
                ELSE '0-19%'
            END as score_range,
            COUNT(*) as count
        FROM matching_scores 
        GROUP BY 
            CASE 
                WHEN overall_score >= 80 THEN '80-100%'
                WHEN overall_score >= 60 THEN '60-79%'
                WHEN overall_score >= 40 THEN '40-59%'
                WHEN overall_score >= 20 THEN '20-39%'
                ELSE '0-19%'
            END
        ORDER BY MIN(overall_score) DESC
    ");
    $newDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Νέα κατανομή scores:\n";
    foreach ($newDistribution as $row) {
        echo "- {$row['score_range']}: {$row['count']} ταιριάσματα\n";
    }
    echo "\n";

    // Έλεγχος high-quality matches
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM matching_scores WHERE overall_score >= 70");
    $highQuality = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "High-quality matches (≥70%): {$highQuality['count']}\n";

    // Έλεγχος μέσου όρου
    $stmt = $pdo->query("SELECT AVG(overall_score) as avg_score FROM matching_scores");
    $avgScore = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Νέος μέσος όρος: " . round($avgScore['avg_score'], 2) . "%\n\n";

    echo "✓ Επανυπολογισμός ολοκληρώθηκε!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

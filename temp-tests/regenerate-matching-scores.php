<?php
require_once __DIR__ . '/../src/bootstrap.php';

echo "🔄 REGENERATING MATCHING SCORES WITH ENHANCED AI\n";
echo "===============================================\n\n";

try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();
    $enhancedService = new \Drivejob\Services\EnhancedMatchingService();

    // Get all active drivers and jobs
    $stmt = $pdo->prepare("SELECT id FROM drivers WHERE is_active = 1");
    $stmt->execute();
    $drivers = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare("SELECT id FROM job_listings WHERE is_active = 1");
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "📊 Found " . count($drivers) . " drivers and " . count($jobs) . " jobs\n";
    echo "🎯 Total combinations to process: " . (count($drivers) * count($jobs)) . "\n\n";

    $processed = 0;
    $improved = 0;
    $totalOldScore = 0;
    $totalNewScore = 0;

    echo "🚀 Starting score regeneration...\n\n";

    foreach ($drivers as $driverId) {
        foreach ($jobs as $jobId) {
            // Get old score
            $stmt = $pdo->prepare("
                SELECT overall_score 
                FROM matching_scores 
                WHERE driver_id = ? AND job_id = ?
            ");
            $stmt->execute([$driverId, $jobId]);
            $oldResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $oldScore = $oldResult ? $oldResult['overall_score'] : 0;

            // Calculate new score with enhanced service
            $newScore = $enhancedService->calculateMatchScore($driverId, $jobId);

            $processed++;
            $totalOldScore += $oldScore;
            $totalNewScore += $newScore;

            if ($newScore > $oldScore) {
                $improved++;
            }

            // Progress indicator
            if ($processed % 10 == 0) {
                $avgOld = $processed > 0 ? round($totalOldScore / $processed, 1) : 0;
                $avgNew = $processed > 0 ? round($totalNewScore / $processed, 1) : 0;
                $improvementRate = $processed > 0 ? round(($improved / $processed) * 100, 1) : 0;

                echo "Progress: {$processed}/" . (count($drivers) * count($jobs)) .
                    " | Avg Old: {$avgOld}% | Avg New: {$avgNew}% | Improved: {$improvementRate}%\n";
            }
        }
    }

    echo "\n🎉 REGENERATION COMPLETED!\n";
    echo "========================\n\n";

    $avgOldScore = $processed > 0 ? round($totalOldScore / $processed, 2) : 0;
    $avgNewScore = $processed > 0 ? round($totalNewScore / $processed, 2) : 0;
    $improvementRate = $processed > 0 ? round(($improved / $processed) * 100, 1) : 0;
    $scoreImprovement = $avgOldScore > 0 ? round((($avgNewScore - $avgOldScore) / $avgOldScore) * 100, 1) : 0;

    echo "📈 RESULTS SUMMARY:\n";
    echo "Total Processed: {$processed}\n";
    echo "Scores Improved: {$improved} ({$improvementRate}%)\n";
    echo "Average Old Score: {$avgOldScore}%\n";
    echo "Average New Score: {$avgNewScore}%\n";
    echo "Score Improvement: +{$scoreImprovement}%\n\n";

    // Show top matches for driver 26
    echo "🎯 TOP MATCHES FOR DRIVER 26 (After Enhancement):\n";
    $topMatches = $enhancedService->getTopMatchesForDriver(26, 5);

    foreach ($topMatches as $match) {
        $score = $match['overall_score'] ? round($match['overall_score'], 1) . '%' : 'New Score';
        echo "- Job {$match['id']}: {$match['title']} ({$match['company_name']}) - Score: {$score}\n";
    }

    echo "\n✅ Enhanced matching system is now active!\n";
    echo "🚀 Users will see improved scores immediately!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n🏁 Process completed\n";

<?php
require_once __DIR__ . '/../src/bootstrap.php';

echo "🎯 ΔΗΜΙΟΥΡΓΙΑ ΥΠΟΛΟΙΠΩΝ MATCHING SCORES\n";
echo "======================================\n\n";

try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();
    $enhancedService = new \Drivejob\Services\EnhancedMatchingService();

    // Get all jobs that don't have scores for driver 26
    $stmt = $pdo->prepare("
        SELECT j.id, j.title 
        FROM job_listings j 
        WHERE j.is_active = 1 
        AND j.id NOT IN (
            SELECT job_id FROM matching_scores WHERE driver_id = 26
        )
    ");
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "📊 Jobs without scores: " . count($jobs) . "\n\n";

    foreach ($jobs as $job) {
        try {
            $score = $enhancedService->calculateMatchScore(26, $job['id']);
            echo "✅ Job {$job['id']}: {$score}%\n";
        } catch (Exception $e) {
            echo "❌ Error for job {$job['id']}: " . $e->getMessage() . "\n";
        }
    }

    echo "\n🎯 Testing Enhanced Service after score generation:\n";
    $matches = $enhancedService->getTopMatchesForDriver(26, 5);
    echo "Matches found: " . count($matches) . "\n";

    foreach ($matches as $match) {
        $score = $match['overall_score'] ?? 'N/A';
        echo "- {$match['title']} ({$match['company_name']}) - Score: {$score}%\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Score generation completed\n";

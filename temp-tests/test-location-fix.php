<?php
require_once __DIR__ . '/../src/bootstrap.php';

echo "🎯 ΤΕΣΤ ΔΙΟΡΘΩΣΗΣ LOCATION MATCHING\n";
echo "==================================\n\n";

try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();
    $enhancedService = new \Drivejob\Services\EnhancedMatchingService();

    // Get driver 26 info
    $stmt = $pdo->prepare("SELECT city, country FROM drivers WHERE id = 26");
    $stmt->execute();
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "👤 Driver 26: {$driver['city']}, {$driver['country']}\n\n";

    // Get some jobs with different locations
    $stmt = $pdo->prepare("
        SELECT id, title, location, company_id 
        FROM job_listings 
        WHERE is_active = 1 
        AND location IS NOT NULL 
        ORDER BY id 
        LIMIT 5
    ");
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "🏢 Testing location matching:\n";
    foreach ($jobs as $job) {
        echo "\nJob {$job['id']}: {$job['title']}\n";
        echo "Location: {$job['location']}\n";

        // Delete old score to force recalculation
        $stmt = $pdo->prepare("DELETE FROM matching_scores WHERE driver_id = 26 AND job_id = ?");
        $stmt->execute([$job['id']]);

        // Calculate new score
        $score = $enhancedService->calculateMatchScore(26, $job['id']);
        echo "New Score: {$score}%\n";

        // Get the detailed scores
        $stmt = $pdo->prepare("
            SELECT location_match_score, overall_score 
            FROM matching_scores 
            WHERE driver_id = 26 AND job_id = ?
        ");
        $stmt->execute([$job['id']]);
        $details = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($details) {
            echo "Location Match Score: " . ($details['location_match_score'] * 100) . "%\n";
        }

        echo "---\n";
    }

    echo "\n🎯 Final Test - Top Matches:\n";
    $matches = $enhancedService->getTopMatchesForDriver(26, 5);

    foreach ($matches as $match) {
        $score = $match['overall_score'] ?? 'N/A';
        echo "- {$match['title']} ({$match['location']}) - Score: {$score}%\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n✅ Location fix test completed\n";

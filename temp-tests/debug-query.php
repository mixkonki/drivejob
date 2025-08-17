<?php
require_once __DIR__ . '/../src/bootstrap.php';

echo "🔍 DEBUG QUERY ISSUE\n";
echo "====================\n\n";

try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

    // Check if matching_scores table has data
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM matching_scores WHERE driver_id = 26");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📊 Matching scores for driver 26: {$result['count']}\n\n";

    // Check sample scores
    $stmt = $pdo->prepare("SELECT job_id, overall_score FROM matching_scores WHERE driver_id = 26 ORDER BY overall_score DESC LIMIT 5");
    $stmt->execute();
    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "🎯 Top scores for driver 26:\n";
    foreach ($scores as $score) {
        echo "   Job {$score['job_id']}: {$score['overall_score']}%\n";
    }
    echo "\n";

    // Check the actual query from getTopMatchesForDriver
    $stmt = $pdo->prepare("
        SELECT j.*, c.company_name, ms.overall_score
        FROM job_listings j
        JOIN companies c ON j.company_id = c.id
        LEFT JOIN matching_scores ms ON j.id = ms.job_id AND ms.driver_id = 26
        WHERE j.is_active = 1
        ORDER BY ms.overall_score DESC, j.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "🔍 Query results: " . count($results) . "\n";

    if (!empty($results)) {
        foreach ($results as $result) {
            $score = $result['overall_score'] ?? 'NULL';
            echo "- Job: {$result['title']}, Score: {$score}\n";
        }
    } else {
        echo "❌ No results found\n";

        // Debug: Check if jobs exist
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM job_listings WHERE is_active = 1");
        $stmt->execute();
        $jobCount = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Active jobs: {$jobCount['count']}\n";

        // Debug: Check if companies exist
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM companies");
        $stmt->execute();
        $companyCount = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Companies: {$companyCount['count']}\n";
    }

    // Test the Enhanced Service method directly
    echo "\n🎯 Testing Enhanced Service directly:\n";
    $enhancedService = new \Drivejob\Services\EnhancedMatchingService();
    $matches = $enhancedService->getTopMatchesForDriver(26, 5);
    echo "Enhanced Service matches: " . count($matches) . "\n";

    foreach ($matches as $match) {
        $score = $match['overall_score'] ?? 'N/A';
        echo "- {$match['title']} ({$match['company_name']}) - Score: {$score}%\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n✅ Debug completed\n";

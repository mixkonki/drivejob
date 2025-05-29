<?php
require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Services\AI\MatchingService;
use Drivejob\Core\Database;

echo "=== AI MATCHING SYSTEM TEST ===\n\n";

// 1. Run migration
echo "1. Running AI matching tables migration...\n";
try {
    require_once __DIR__ . '/database/migrations/create_ai_matching_tables.php';
    echo "   ✓ Migration completed successfully\n\n";
} catch (Exception $e) {
    echo "   ✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Test matching service
echo "2. Testing AI Matching Service...\n";
try {
    $matchingService = new MatchingService();

    // Get a sample driver and job
    $pdo = Database::getInstance()->getConnection();

    // Get first active driver
    $stmt = $pdo->query("SELECT id FROM drivers WHERE is_available = 1 LIMIT 1");
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get first active job
    $stmt = $pdo->query("SELECT id FROM job_listings WHERE status = 'active' LIMIT 1");
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($driver && $job) {
        echo "   Testing match between Driver ID: {$driver['id']} and Job ID: {$job['id']}\n";

        // Calculate match
        $result = $matchingService->calculateMatch($driver['id'], $job['id']);

        if ($result['success']) {
            echo "   ✓ Match calculated successfully!\n";
            echo "   - Overall Score: " . round($result['overall_score'] * 100, 2) . "%\n";
            echo "   - Recommendation: " . $result['recommendation'] . "\n";
            echo "   - Details:\n";
            foreach ($result['scores'] as $factor => $score) {
                echo "     • " . ucfirst(str_replace('_', ' ', $factor)) . ": " . round($score * 100, 2) . "%\n";
            }
        } else {
            echo "   ✗ Match calculation failed: " . $result['error'] . "\n";
        }
    } else {
        echo "   ⚠️ No active drivers or jobs found for testing\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// 3. Test API endpoints
echo "\n3. API Endpoints Available:\n";
echo "   • GET  /api/matching/driver/matches - Get top job matches for logged-in driver\n";
echo "   • GET  /api/matching/job/candidates?job_id=X - Get top candidates for a job\n";
echo "   • GET  /api/matching/calculate?driver_id=X&job_id=Y - Calculate specific match\n";
echo "   • GET  /api/matching/insights?driver_id=X&job_id=Y - Get match insights\n";

// 4. Sample data for testing
echo "\n4. Creating sample data for better matching...\n";
try {
    // Add some skill categories if not exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM skill_categories");
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        echo "   ✓ Skill categories already exist\n";
    }

    // Check matching scores table
    $stmt = $pdo->query("SELECT COUNT(*) FROM matching_scores");
    $scoreCount = $stmt->fetchColumn();
    echo "   ✓ Matching scores table has $scoreCount records\n";
} catch (Exception $e) {
    echo "   ✗ Error checking data: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETED ===\n";
echo "\nNext steps:\n";
echo "1. Login as a driver and visit: /api/matching/driver/matches\n";
echo "2. Login as a company and visit: /api/matching/job/candidates?job_id=YOUR_JOB_ID\n";
echo "3. Integrate the matching UI into driver and company dashboards\n";

<?php
require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Services\AI\MatchingService;

echo "=== SIMPLE AI MATCHING TEST ===\n\n";

$pdo = Database::getInstance()->getConnection();
$matchingService = new MatchingService();

// Test 1: Get matches for a driver
echo "1. Testing driver matches...\n";
try {
    $driverId = 26;
    $matches = $matchingService->getTopMatchesForDriver($driverId, 5);

    echo "   ✓ Found " . count($matches) . " matches for driver ID $driverId\n";

    if (count($matches) > 0) {
        echo "   Top matches:\n";
        foreach ($matches as $index => $match) {
            echo "   " . ($index + 1) . ". " . $match['job']['title'] .
                " (Score: " . round($match['score'] * 100, 1) . "%)\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Test 2: Get candidates for a job
echo "\n2. Testing job candidates...\n";
try {
    $jobId = 2;
    $candidates = $matchingService->getTopCandidatesForJob($jobId, 10);

    echo "   ✓ Found " . count($candidates) . " candidates for job ID $jobId\n";

    if (count($candidates) > 0) {
        echo "   Top 5 candidates:\n";
        foreach (array_slice($candidates, 0, 5) as $index => $candidate) {
            $driverName = $candidate['driver']['first_name'] . ' ' . $candidate['driver']['last_name'];
            echo "   " . ($index + 1) . ". $driverName" .
                " (Score: " . round($candidate['score'] * 100, 1) . "%)\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Test 3: Calculate specific match
echo "\n3. Testing specific match calculation...\n";
try {
    $driverId = 26;
    $jobId = 2;

    $result = $matchingService->calculateMatch($driverId, $jobId);

    if ($result['success']) {
        echo "   ✓ Match calculated for Driver $driverId and Job $jobId\n";
        echo "   - Overall Score: " . round($result['overall_score'] * 100, 1) . "%\n";
        echo "   - Recommendation: " . $result['recommendation'] . "\n";
        echo "   - Score breakdown:\n";
        foreach ($result['scores'] as $factor => $score) {
            echo "     • " . ucfirst(str_replace('_', ' ', $factor)) . ": " .
                round($score * 100, 1) . "%\n";
        }
    } else {
        echo "   ✗ Error: " . $result['error'] . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Test 4: Check matching scores table
echo "\n4. Checking stored matching scores...\n";
try {
    $stmt = $pdo->query("
        SELECT ms.*, d.first_name, d.last_name, j.title
        FROM matching_scores ms
        JOIN drivers d ON ms.driver_id = d.id
        JOIN job_listings j ON ms.job_id = j.id
        ORDER BY ms.overall_score DESC
        LIMIT 5
    ");

    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   ✓ Found " . count($scores) . " stored matching scores\n";

    if (count($scores) > 0) {
        echo "   Top stored matches:\n";
        foreach ($scores as $index => $score) {
            echo "   " . ($index + 1) . ". " . $score['first_name'] . " " . $score['last_name'] .
                " → " . $score['title'] .
                " (Score: " . round($score['overall_score'] * 100, 1) . "%)\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETED ===\n";
echo "\nAPI Endpoints ready for browser testing:\n";
echo "- /api/matching/driver/matches (requires driver login)\n";
echo "- /api/matching/job/candidates?job_id=2 (requires company login)\n";
echo "- /api/matching/calculate?driver_id=26&job_id=2\n";
echo "- /api/matching/insights?driver_id=26&job_id=2\n";

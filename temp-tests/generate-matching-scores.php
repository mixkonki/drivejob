<?php
require_once __DIR__ . '/../src/bootstrap.php';

echo "🎯 ΔΗΜΙΟΥΡΓΙΑ MATCHING SCORES\n";
echo "=============================\n\n";

try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();
    $enhancedService = new \Drivejob\Services\EnhancedMatchingService();

    // Get all active drivers
    $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM drivers WHERE is_active = 1 LIMIT 5");
    $stmt->execute();
    $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all active jobs
    $stmt = $pdo->prepare("SELECT id, title FROM job_listings WHERE is_active = 1 LIMIT 10");
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "👥 Active Drivers: " . count($drivers) . "\n";
    echo "💼 Active Jobs: " . count($jobs) . "\n\n";

    $totalCalculations = 0;
    $successfulCalculations = 0;

    foreach ($drivers as $driver) {
        echo "🔄 Processing Driver {$driver['id']}: {$driver['first_name']} {$driver['last_name']}\n";

        foreach ($jobs as $job) {
            $totalCalculations++;

            try {
                $score = $enhancedService->calculateMatchScore($driver['id'], $job['id']);

                if ($score > 0) {
                    $successfulCalculations++;
                    echo "   ✅ Job {$job['id']}: {$score}%\n";
                } else {
                    echo "   ⚠️ Job {$job['id']}: 0% (no match)\n";
                }
            } catch (Exception $e) {
                echo "   ❌ Job {$job['id']}: Error - " . $e->getMessage() . "\n";
            }
        }
        echo "\n";
    }

    echo "📊 SUMMARY:\n";
    echo "Total calculations: {$totalCalculations}\n";
    echo "Successful calculations: {$successfulCalculations}\n";
    echo "Success rate: " . round(($successfulCalculations / $totalCalculations) * 100, 2) . "%\n\n";

    // Now test getTopMatchesForDriver
    echo "🎯 Testing getTopMatchesForDriver for Driver 26:\n";
    $matches = $enhancedService->getTopMatchesForDriver(26, 5);
    echo "Matches found: " . count($matches) . "\n";

    foreach ($matches as $match) {
        $score = $match['overall_score'] ?? 'N/A';
        echo "- {$match['title']} ({$match['company_name']}) - Score: {$score}%\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n✅ Score generation completed\n";

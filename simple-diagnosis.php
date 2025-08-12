<?php
// Simple diagnosis without session conflicts
require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Services\MatchingService;

echo "=== SIMPLE AI MATCHING DIAGNOSIS ===\n\n";

try {
    $pdo = Database::getInstance()->getConnection();
    echo "✅ Database connection: OK\n";

    // Check matches in database
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM job_matches WHERE driver_id = 26");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Database matches for driver 26: {$count['count']}\n";

    // Test MatchingService
    $matchingService = new MatchingService($pdo);
    $result = $matchingService->findDriverMatches(26, 1, 3);
    echo "✅ MatchingService: OK - {$result['pagination']['total']} total, " . count($result['results']) . " returned\n";

    // Check if driver profile includes matching widget
    $profilePath = __DIR__ . '/src/Views/drivers/driver-profile.php';
    if (file_exists($profilePath)) {
        $content = file_get_contents($profilePath);
        if (strpos($content, 'matching-widget.php') !== false) {
            echo "✅ Driver profile includes matching widget\n";
        } else {
            echo "❌ Driver profile does NOT include matching widget\n";
            echo "   This is likely the main problem!\n";
        }
    } else {
        echo "❌ Driver profile file not found\n";
    }

    // Check if matching widget exists
    $widgetPath = __DIR__ . '/src/Views/drivers/partials/matching-widget.php';
    if (file_exists($widgetPath)) {
        echo "✅ Matching widget file exists\n";
    } else {
        echo "❌ Matching widget file missing\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== DIAGNOSIS COMPLETE ===\n";

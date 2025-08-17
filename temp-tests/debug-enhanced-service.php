<?php
require_once __DIR__ . '/../src/bootstrap.php';

echo "🔍 DEBUG ENHANCED MATCHING SERVICE\n";
echo "==================================\n\n";

try {
    // Check if EnhancedMatchingService exists
    if (class_exists('Drivejob\Services\EnhancedMatchingService')) {
        echo "✅ EnhancedMatchingService class exists\n";

        $service = new \Drivejob\Services\EnhancedMatchingService();
        echo "✅ Service instantiated successfully\n";

        // Check if method exists
        if (method_exists($service, 'getTopMatchesForDriver')) {
            echo "✅ getTopMatchesForDriver method exists\n";

            // Check database data
            $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM job_listings WHERE is_active = 1");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "📊 Active jobs: {$result['count']}\n";

            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM drivers WHERE user_id = 26");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "👤 Driver 26 exists: {$result['count']}\n";

            // Try to get matches
            echo "\n🎯 Attempting to get matches...\n";
            $matches = $service->getTopMatchesForDriver(26, 5);
            echo "📊 Matches returned: " . count($matches) . "\n";

            if (empty($matches)) {
                echo "⚠️ No matches returned. Checking method implementation...\n";

                // Let's try to call calculateMatchScore directly
                if (method_exists($service, 'calculateMatchScore')) {
                    echo "✅ calculateMatchScore method exists\n";
                    try {
                        $score = $service->calculateMatchScore(26, 15); // Job 15 should exist
                        echo "🎯 Direct score calculation: {$score}%\n";
                    } catch (Exception $e) {
                        echo "❌ Direct score calculation failed: " . $e->getMessage() . "\n";
                    }
                } else {
                    echo "❌ calculateMatchScore method does not exist\n";
                }
            } else {
                echo "✅ Matches found! First match:\n";
                $firstMatch = $matches[0];
                echo "   Title: " . ($firstMatch['title'] ?? 'N/A') . "\n";
                echo "   Score: " . ($firstMatch['overall_score'] ?? 'N/A') . "%\n";
            }
        } else {
            echo "❌ getTopMatchesForDriver method does not exist\n";
        }
    } else {
        echo "❌ EnhancedMatchingService class does not exist\n";
        echo "Checking if file exists...\n";

        $filePath = ROOT_DIR . '/src/Services/EnhancedMatchingService.php';
        if (file_exists($filePath)) {
            echo "✅ File exists at: {$filePath}\n";
        } else {
            echo "❌ File does not exist at: {$filePath}\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n✅ Debug completed\n";

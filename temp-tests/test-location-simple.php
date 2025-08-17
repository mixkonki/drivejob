<?php
require_once __DIR__ . '/../src/bootstrap.php';

echo "🎯 ΑΠΛΟ ΤΕΣΤ LOCATION MATCHING\n";
echo "=============================\n\n";

try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

    // Get driver 26 info
    $stmt = $pdo->prepare("SELECT city, country FROM drivers WHERE id = 26");
    $stmt->execute();
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "👤 Driver 26: {$driver['city']}, {$driver['country']}\n\n";

    // Test locations manually
    $testLocations = [
        'Θεσσαλονίκη, Ελλάδα',
        'Αθήνα, Ελλάδα',
        'Λάρισα, Ελλάδα',
        'Πάτρα, Ελλάδα',
        'Αττική'
    ];

    // Create a simple instance to test location scoring
    $enhancedService = new \Drivejob\Services\EnhancedMatchingService();

    // Use reflection to access private method
    $reflection = new ReflectionClass($enhancedService);
    $method = $reflection->getMethod('calculateLocationScore');
    $method->setAccessible(true);

    echo "🏢 Testing location scores:\n";
    foreach ($testLocations as $location) {
        $jobData = ['location' => $location];
        $score = $method->invoke($enhancedService, $driver, $jobData);
        echo "- {$location}: " . ($score * 100) . "%\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n✅ Simple location test completed\n";

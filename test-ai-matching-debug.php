<?php
require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Services\AI\MatchingService;
use Drivejob\Services\AI\FeatureExtractor;
use Drivejob\Core\Database;

echo "=== AI MATCHING DEBUG TEST ===\n\n";

$pdo = Database::getInstance()->getConnection();

// Test Feature Extractor
echo "1. Testing Feature Extractor...\n";
try {
    $featureExtractor = new FeatureExtractor();

    // Get a test driver
    $stmt = $pdo->query("SELECT id, email FROM drivers WHERE is_available = 1 LIMIT 1");
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($driver) {
        echo "   Testing driver ID: {$driver['id']} ({$driver['email']})\n";
        $driverFeatures = $featureExtractor->extractDriverFeatures($driver['id']);
        echo "   Driver features extracted:\n";
        foreach ($driverFeatures as $key => $value) {
            if (is_array($value)) {
                echo "     - $key: " . json_encode($value) . "\n";
            } else {
                echo "     - $key: $value\n";
            }
        }
    }

    // Get a test job
    $stmt = $pdo->query("SELECT id, title FROM job_listings WHERE status = 'active' LIMIT 1");
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($job) {
        echo "\n   Testing job ID: {$job['id']} ({$job['title']})\n";
        $jobFeatures = $featureExtractor->extractJobFeatures($job['id']);
        echo "   Job features extracted:\n";
        foreach ($jobFeatures as $key => $value) {
            if (is_array($value)) {
                echo "     - $key: " . json_encode($value) . "\n";
            } else {
                echo "     - $key: $value\n";
            }
        }
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Test Matching Service with error details
echo "\n2. Testing Matching Service with detailed error reporting...\n";
try {
    if (isset($driver) && isset($job)) {
        $matchingService = new MatchingService();

        // Enable error reporting
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $result = $matchingService->calculateMatch($driver['id'], $job['id']);

        if ($result['success']) {
            echo "   ✓ Match calculated successfully!\n";
            echo "   - Overall Score: " . round($result['overall_score'] * 100, 2) . "%\n";
            echo "   - Recommendation: " . $result['recommendation'] . "\n";
        } else {
            echo "   ✗ Match calculation failed: " . $result['error'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ Exception: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Check database structure
echo "\n3. Checking database structure...\n";
$tables = ['drivers', 'job_listings', 'driver_licenses', 'driver_certifications', 'driver_vehicle_experience'];
foreach ($tables as $table) {
    echo "   Table: $table\n";
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "     Columns: " . implode(', ', array_slice($columns, 0, 10)) . "...\n";
    } catch (Exception $e) {
        echo "     ✗ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n=== DEBUG TEST COMPLETED ===\n";

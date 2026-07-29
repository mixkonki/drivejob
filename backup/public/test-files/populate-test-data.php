<?php

/**
 * Populate test data for AI Matching
 */

require_once '../src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Services\AI\MatchingService;

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Populate Test Data</h1>";
echo "<pre>";

try {
    $pdo = Database::getInstance()->getConnection();
    $matchingService = new MatchingService();

    echo "Getting active drivers and jobs...\n";

    // Get active drivers
    $stmt = $pdo->query("SELECT id FROM drivers WHERE is_active = 1 LIMIT 5");
    $drivers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Found " . count($drivers) . " active drivers\n";

    // Get active jobs
    $stmt = $pdo->query("SELECT id FROM job_listings WHERE is_active = 1 LIMIT 10");
    $jobs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Found " . count($jobs) . " active jobs\n\n";

    if (empty($drivers) || empty($jobs)) {
        echo "ERROR: No active drivers or jobs found!\n";
        exit;
    }

    echo "Calculating matches...\n";
    $matchCount = 0;

    foreach ($drivers as $driverId) {
        foreach ($jobs as $jobId) {
            echo "Calculating match for Driver $driverId and Job $jobId... ";

            try {
                $result = $matchingService->calculateMatch($driverId, $jobId);

                if ($result['success']) {
                    echo "Score: " . round($result['overall_score'] * 100) . "%\n";
                    $matchCount++;
                } else {
                    echo "Failed: " . $result['error'] . "\n";
                }
            } catch (Exception $e) {
                echo "Error: " . $e->getMessage() . "\n";
            }
        }
        echo "\n";
    }

    echo "\nTotal matches calculated: $matchCount\n";

    // Show sample matches for driver 26
    echo "\nSample matches for Driver 26:\n";
    $stmt = $pdo->prepare("
        SELECT 
            ms.*,
            jl.title as job_title,
            c.company_name as company_name
        FROM matching_scores ms
        JOIN job_listings jl ON ms.job_id = jl.id
        LEFT JOIN companies c ON jl.company_id = c.id
        WHERE ms.driver_id = 26
        ORDER BY ms.overall_score DESC
        LIMIT 5
    ");
    $stmt->execute();
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($matches) {
        foreach ($matches as $match) {
            echo sprintf(
                "- Job: %s (%s) - Score: %.1f%%\n",
                $match['job_title'],
                $match['company_name'] ?: 'Unknown Company',
                $match['overall_score'] * 100
            );
        }
    } else {
        echo "No matches found for Driver 26\n";
    }
} catch (Exception $e) {
    echo "\nException caught:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "</pre>";

echo '<p><a href="test-ai-widgets.php">Go back to Widget Test</a></p>';

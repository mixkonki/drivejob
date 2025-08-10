<?php
// Disable exception handler για το test script
define('DISABLE_EXCEPTION_HANDLER', true);

require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Services\MatchingService;

// Start output buffering για καλύτερη διαχείριση errors
ob_start();

try {
    $pdo = Database::getInstance()->getConnection();

    echo "<h2>Test AI Matching System</h2>";

    // Test 1: Check if MatchingService exists
    echo "<h3>1. Checking MatchingService...</h3>";
    if (class_exists('Drivejob\Services\MatchingService')) {
        echo "✅ MatchingService class exists<br>";

        $matchingService = new MatchingService($pdo);
        echo "✅ MatchingService instantiated successfully<br>";
    } else {
        echo "❌ MatchingService class not found<br>";
        exit;
    }

    // Test 2: Get a test driver
    echo "<h3>2. Getting test driver...</h3>";
    $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM drivers WHERE email = ?");
    $stmt->execute(['kostas.michailidis@hotmail.gr']);
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($driver) {
        echo "✅ Found driver: {$driver['first_name']} {$driver['last_name']} (ID: {$driver['id']})<br>";
    } else {
        echo "❌ Driver not found<br>";
        exit;
    }

    // Test 3: Get a test company
    echo "<h3>3. Getting test company...</h3>";
    $stmt = $pdo->prepare("SELECT id, company_name FROM companies WHERE email = ?");
    $stmt->execute(['info@thessdrive.gr']);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($company) {
        echo "✅ Found company: {$company['company_name']} (ID: {$company['id']})<br>";
    } else {
        echo "❌ Company not found<br>";
        exit;
    }

    // Test 4: Get active job listings
    echo "<h3>4. Getting active job listings...</h3>";
    $stmt = $pdo->query("
    SELECT j.*, c.company_name 
    FROM job_listings j
    JOIN companies c ON j.company_id = c.id
    WHERE j.status = 'active' 
    AND j.listing_type = 'job_offer'
    LIMIT 5
");
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($jobs) . " active job listings<br>";
    if (!empty($jobs)) {
        echo "<ul>";
        foreach ($jobs as $job) {
            echo "<li>ID: {$job['id']} - {$job['title']} ({$job['company_name']})</li>";
        }
        echo "</ul>";
    }

    // Test 5: Test matching for driver
    echo "<h3>5. Testing job matching for driver...</h3>";
    try {
        $matches = $matchingService->findJobsForDriver($driver['id']);

        if (isset($matches['results']) && !empty($matches['results'])) {
            echo "✅ Found " . count($matches['results']) . " job matches<br>";
            echo "<table border='1'>";
            echo "<tr><th>Job ID</th><th>Title</th><th>Company</th><th>Match %</th></tr>";
            foreach ($matches['results'] as $match) {
                echo "<tr>";
                echo "<td>{$match['id']}</td>";
                echo "<td>{$match['title']}</td>";
                echo "<td>{$match['company_name']}</td>";
                echo "<td>{$match['match_percentage']}%</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "⚠️ No matches found for driver<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error in findJobsForDriver: " . $e->getMessage() . "<br>";
    }

    // Test 6: Test matching for company
    echo "<h3>6. Testing driver matching for company...</h3>";
    if (!empty($jobs)) {
        $testJobId = $jobs[0]['id'];
        echo "Using job ID: $testJobId<br>";

        try {
            $candidates = $matchingService->findDriversForJob($testJobId);

            if (isset($candidates['results']) && !empty($candidates['results'])) {
                echo "✅ Found " . count($candidates['results']) . " driver matches<br>";
                echo "<table border='1'>";
                echo "<tr><th>Driver ID</th><th>Name</th><th>Match %</th></tr>";
                foreach ($candidates['results'] as $candidate) {
                    echo "<tr>";
                    echo "<td>{$candidate['id']}</td>";
                    echo "<td>{$candidate['first_name']} {$candidate['last_name']}</td>";
                    echo "<td>{$candidate['match_percentage']}%</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "⚠️ No driver matches found for job<br>";
            }
        } catch (Exception $e) {
            echo "❌ Error in findDriversForJob: " . $e->getMessage() . "<br>";
        }
    }

    // Test 7: Check matching_history table
    echo "<h3>7. Checking matching_history table...</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM matching_history");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Matching history records: " . $count['count'] . "<br>";

    // Test 8: Check matching_scores table
    echo "<h3>8. Checking matching_scores table...</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM matching_scores");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Matching scores records: " . $count['count'] . "<br>";

    echo "<hr>";
    echo "<p><a href='/drivejob/public/'>Back to Home</a></p>";
} catch (Exception $e) {
    echo "❌ Critical Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
} finally {
    // Flush output buffer
    ob_end_flush();
}

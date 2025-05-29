<?php
require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Controllers\Api\MatchingController;

echo "=== AI MATCHING API TEST ===\n\n";

// Simulate logged-in driver
Session::start();
$_SESSION['user_id'] = 26;
$_SESSION['user_role'] = 'driver';
$_SESSION['email'] = 'kostas.michailidis@hotmail.gr';

echo "1. Testing Driver Matches API...\n";
$controller = new MatchingController();
$_GET['limit'] = 5;

$response = $controller->getDriverMatches();
$data = json_decode($response, true);

if ($data['success']) {
    echo "   ✓ Successfully retrieved driver matches\n";
    echo "   - Found " . $data['data']['count'] . " matches\n";

    if ($data['data']['count'] > 0) {
        echo "   - Top matches:\n";
        foreach ($data['data']['matches'] as $index => $match) {
            echo "     " . ($index + 1) . ". " . $match['job']['title'] .
                " (Score: " . round($match['score'] * 100, 1) . "%)\n";
        }
    }
} else {
    echo "   ✗ Error: " . $data['error'] . "\n";
}

// Test company candidates
echo "\n2. Testing Company Candidates API...\n";
$_SESSION['user_role'] = 'company';
$_SESSION['user_id'] = 1; // Assuming company ID 1
$_GET['job_id'] = 2;
$_GET['limit'] = 10;

$response = $controller->getJobCandidates();
$data = json_decode($response, true);

if ($data['success']) {
    echo "   ✓ Successfully retrieved job candidates\n";
    echo "   - Found " . $data['data']['count'] . " candidates\n";

    if ($data['data']['count'] > 0) {
        echo "   - Top candidates:\n";
        foreach (array_slice($data['data']['candidates'], 0, 5) as $index => $candidate) {
            echo "     " . ($index + 1) . ". Driver ID " . $candidate['driver']['id'] .
                " (Score: " . round($candidate['score'] * 100, 1) . "%)\n";
        }
    }
} else {
    echo "   ✗ Error: " . $data['error'] . "\n";
}

// Test match calculation
echo "\n3. Testing Match Calculation API...\n";
$_GET['driver_id'] = 26;
$_GET['job_id'] = 2;

$response = $controller->calculateMatch();
$data = json_decode($response, true);

if ($data['success']) {
    echo "   ✓ Match calculation successful\n";
    echo "   - Overall Score: " . round($data['data']['overall_score'] * 100, 1) . "%\n";
    echo "   - Recommendation: " . $data['data']['recommendation'] . "\n";
    echo "   - Score breakdown:\n";
    foreach ($data['data']['scores'] as $factor => $score) {
        echo "     • " . ucfirst(str_replace('_', ' ', $factor)) . ": " .
            round($score * 100, 1) . "%\n";
    }
} else {
    echo "   ✗ Error: " . $data['error'] . "\n";
}

// Test match insights
echo "\n4. Testing Match Insights API...\n";
$response = $controller->getMatchInsights();
$data = json_decode($response, true);

if ($data['success']) {
    echo "   ✓ Match insights retrieved\n";
    echo "   - Match Score: " . round($data['data']['match_score'] * 100, 1) . "%\n";
    echo "   - Insights:\n";
    foreach ($data['data']['insights'] as $insight) {
        $icon = $insight['type'] === 'success' ? '✓' : ($insight['type'] === 'warning' ? '⚠' : 'ℹ');
        echo "     $icon " . $insight['message'] . "\n";
    }
} else {
    echo "   ✗ Error: " . $data['error'] . "\n";
}

// Clean up session
session_destroy();

echo "\n=== API TEST COMPLETED ===\n";
echo "\nTo test in browser:\n";
echo "1. Login as driver and visit: http://localhost/drivejob/api/matching/driver/matches\n";
echo "2. Login as company and visit: http://localhost/drivejob/api/matching/job/candidates?job_id=2\n";

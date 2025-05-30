<?php
// Test direct API call
define('DISABLE_EXCEPTION_HANDLER', true);
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Session;

// Set plain text header for debugging
header('Content-Type: text/plain');

echo "=== API Debug Test ===\n\n";

// Check session
echo "Session ID: " . Session::getId() . "\n";
echo "User ID: " . (Session::has('user_id') ? Session::get('user_id') : 'Not set') . "\n";
echo "User Role: " . (Session::has('user_role') ? Session::get('user_role') : 'Not set') . "\n\n";

// Test with job_id=15
$jobId = 15;
echo "Testing with job_id=$jobId\n\n";

if (!Session::has('user_id') || Session::get('user_role') !== 'company') {
    echo "ERROR: Not logged in as company\n";
    exit;
}

$companyId = Session::get('user_id');
echo "Company ID: $companyId\n\n";

try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

    // Check job exists
    $stmt = $pdo->prepare("SELECT id, company_id, title FROM job_listings WHERE id = ?");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch(\PDO::FETCH_ASSOC);

    if ($job) {
        echo "Job found:\n";
        echo "- ID: " . $job['id'] . "\n";
        echo "- Title: " . $job['title'] . "\n";
        echo "- Company ID: " . $job['company_id'] . "\n";
        echo "- Belongs to current company: " . ($job['company_id'] == $companyId ? 'YES' : 'NO') . "\n\n";
    } else {
        echo "Job NOT found\n\n";
    }

    // Check matching scores
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM matching_scores WHERE job_id = ?");
    $stmt->execute([$jobId]);
    $result = $stmt->fetch(\PDO::FETCH_ASSOC);
    echo "Matching scores for this job: " . $result['count'] . "\n\n";

    // Test the actual API
    echo "Now calling the API endpoint...\n";
    echo "URL: " . BASE_URL . "api/matching/job/candidates/index.php?job_id=$jobId&limit=5\n\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

<?php
require_once __DIR__ . '/../../../../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Core\JsonResponse;
use Drivejob\Core\Database;
use Drivejob\Services\AI\MatchingService;

// Start session
Session::start();

// Check if user is logged in and is a driver
if (!Session::has('user_id') || Session::get('user_role') !== 'driver') {
    JsonResponse::error('Unauthorized access', 401);
}

$driverId = Session::get('user_id');

try {
    $pdo = Database::getInstance()->getConnection();

    // Get limit from query params
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

    // Initialize matching service
    $matchingService = new MatchingService();

    // Get matched jobs for driver
    $matches = $matchingService->getTopMatchesForDriver($driverId, $limit);

    // Format response
    $formattedMatches = [];
    foreach ($matches as $match) {
        $formattedMatches[] = [
            'job_id' => $match['job']['id'],
            'score' => $match['score'],
            'details' => $match['details'],
            'job' => $match['job'],
            'insights' => generateInsights($match)
        ];
    }

    JsonResponse::success([
        'matches' => $formattedMatches,
        'total' => count($formattedMatches),
        'limit' => $limit
    ]);
} catch (Exception $e) {
    error_log("Driver matches API error: " . $e->getMessage());
    JsonResponse::error('Σφάλμα κατά την ανάκτηση των προτάσεων: ' . $e->getMessage());
}

function generateInsights($match)
{
    $insights = [];
    $job = $match['job'];
    $score = $match['score'];
    $details = $match['details'];

    // High match score
    if ($score >= 0.8) {
        $insights[] = [
            'type' => 'success',
            'message' => 'Εξαιρετική συμβατότητα με αυτή τη θέση!'
        ];
    }

    // Location match
    if (isset($details['location_match']) && $details['location_match'] >= 0.9) {
        $insights[] = [
            'type' => 'success',
            'message' => 'Η θέση βρίσκεται κοντά στην τοποθεσία σας'
        ];
    } elseif (isset($details['location_match']) && $details['location_match'] < 0.5) {
        $insights[] = [
            'type' => 'warning',
            'message' => 'Η θέση απαιτεί μετεγκατάσταση ή μεγάλες μετακινήσεις'
        ];
    }

    // Urgent job
    if ($job['is_urgent'] ?? false) {
        $insights[] = [
            'type' => 'info',
            'message' => 'Επείγουσα θέση - Γρήγορη πρόσληψη'
        ];
    }

    // High salary
    if (isset($job['salary_max']) && $job['salary_max'] > 2000) {
        $insights[] = [
            'type' => 'success',
            'message' => 'Ανταγωνιστικός μισθός'
        ];
    }

    return $insights;
}

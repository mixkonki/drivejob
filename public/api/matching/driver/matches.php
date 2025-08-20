<?php
require_once __DIR__ . '/../../../../src/bootstrap.php';

use Drivejob\Middleware\AuthenticationMiddleware as Auth;
use Drivejob\Core\JsonResponse;
use Drivejob\Core\Database;
use Drivejob\Services\MatchingService;

// Require driver role
if (!Auth::requireDriver(true)) {
    return;
}

$user = Auth::getCurrentUser();
$driverId = $user['id'];

try {
    $pdo = Database::getInstance()->getConnection();

    // Get limit from query params
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;

    // Initialize matching service
    $matchingService = new MatchingService($pdo);

    // Get matched jobs for driver using the correct method
    $result = $matchingService->findDriverMatches($driverId, $page, $limit);

    // Format response
    $formattedMatches = [];
    foreach ($result['results'] as $match) {
        $formattedMatches[] = [
            'job_id' => $match['company_listing_id'],
            'score' => floatval($match['match_score']) / 100, // Convert to 0-1 scale
            'details' => [
                'location_match' => 0.8, // Default values for now
                'skill_match' => 0.7,
                'experience_match' => 0.9,
                'availability_match' => 0.8
            ],
            'job' => [
                'id' => $match['company_listing_id'],
                'title' => $match['title'],
                'description' => $match['description'],
                'location' => $match['location'],
                'company_name' => $match['company_name'],
                'company_city' => $match['city'],
                'salary_min' => $match['salary_min'],
                'salary_max' => $match['salary_max'],
                'salary_period' => $match['salary_period'] ?? 'monthly',
                'job_type' => $match['job_type'],
                'vehicle_type' => $match['vehicle_type'],
                'created_at' => $match['listing_created_at'],
                'is_urgent' => false
            ],
            'insights' => generateInsights($match)
        ];
    }

    JsonResponse::success([
        'data' => [
            'matches' => $formattedMatches,
            'total' => $result['pagination']['total'],
            'limit' => $limit,
            'page' => $page,
            'pages' => $result['pagination']['pages']
        ]
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

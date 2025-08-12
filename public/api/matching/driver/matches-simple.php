<?php

/**
 * Simple AI Matching API που δεν χρησιμοποιεί session authentication
 * Καλεί απευθείας το MatchingService
 */

require_once __DIR__ . '/../../../../src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Services\MatchingService;

// Set JSON header
header('Content-Type: application/json');

try {
    // Get driver ID from query parameter (for testing)
    $driverId = isset($_GET['driver_id']) ? intval($_GET['driver_id']) : 26; // Default to test driver
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;

    $pdo = Database::getInstance()->getConnection();
    $matchingService = new MatchingService($pdo);

    // Get matched jobs for driver
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
                'company_city' => $match['city'] ?? '',
                'salary_min' => $match['salary_min'],
                'salary_max' => $match['salary_max'],
                'created_at' => $match['listing_created_at'],
                'is_urgent' => false
            ],
            'insights' => generateInsights($match)
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'matches' => $formattedMatches,
            'total' => $result['pagination']['total'],
            'limit' => $limit,
            'page' => $page,
            'pages' => $result['pagination']['pages']
        ]
    ]);
} catch (Exception $e) {
    error_log("Simple matches API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Σφάλμα κατά την ανάκτηση των προτάσεων: ' . $e->getMessage()
    ]);
}

function generateInsights($match)
{
    $insights = [];
    $score = floatval($match['match_score']);

    // High match score
    if ($score >= 90) {
        $insights[] = [
            'type' => 'success',
            'message' => 'Εξαιρετική συμβατότητα με αυτή τη θέση!'
        ];
    } elseif ($score >= 70) {
        $insights[] = [
            'type' => 'info',
            'message' => 'Καλή συμβατότητα με τις απαιτήσεις της θέσης'
        ];
    }

    // Location insights
    if (strpos(strtolower($match['location']), 'θεσσαλονίκη') !== false) {
        $insights[] = [
            'type' => 'success',
            'message' => 'Η θέση βρίσκεται στην περιοχή σας'
        ];
    }

    // High salary
    if (isset($match['salary_max']) && floatval($match['salary_max']) > 1500) {
        $insights[] = [
            'type' => 'success',
            'message' => 'Ανταγωνιστικός μισθός'
        ];
    }

    return $insights;
}

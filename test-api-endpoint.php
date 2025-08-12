<?php
require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Services\MatchingService;

try {
    $pdo = Database::getInstance()->getConnection();

    echo "=== ΔΟΚΙΜΗ API ENDPOINT ===\n\n";

    // Test driver ID
    $driverId = 26;

    // Initialize matching service
    $matchingService = new MatchingService($pdo);

    // Get matched jobs for driver
    $result = $matchingService->findDriverMatches($driverId, 1, 3);

    echo "Αποτελέσματα από MatchingService:\n";
    echo "Total: " . $result['pagination']['total'] . "\n";
    echo "Results count: " . count($result['results']) . "\n\n";

    if (!empty($result['results'])) {
        echo "Matches βρέθηκαν:\n";
        foreach ($result['results'] as $match) {
            echo "- Job ID: {$match['company_listing_id']}\n";
            echo "  Title: {$match['title']}\n";
            echo "  Score: {$match['match_score']}\n";
            echo "  Company: {$match['company_name']}\n";
            echo "  Location: {$match['location']}\n\n";
        }

        // Format response like the API does
        $formattedMatches = [];
        foreach ($result['results'] as $match) {
            $formattedMatches[] = [
                'job_id' => $match['company_listing_id'],
                'score' => floatval($match['match_score']) / 100,
                'job' => [
                    'id' => $match['company_listing_id'],
                    'title' => $match['title'],
                    'description' => $match['description'],
                    'location' => $match['location'],
                    'company_name' => $match['company_name'],
                    'company_city' => $match['city'],
                    'salary_min' => $match['salary_min'],
                    'salary_max' => $match['salary_max'],
                    'created_at' => $match['listing_created_at']
                ]
            ];
        }

        echo "Formatted για API:\n";
        echo json_encode([
            'success' => true,
            'data' => [
                'matches' => $formattedMatches,
                'total' => $result['pagination']['total']
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo "❌ Δεν βρέθηκαν matches!\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

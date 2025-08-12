<?php

/**
 * AI-Powered Job Matching API
 * 
 * Χρησιμοποιεί πραγματικούς AI αλγορίθμους για intelligent matching
 */

require_once __DIR__ . '/../../../../src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Core\Session;
use Drivejob\Core\JsonResponse;
use Drivejob\Services\AIMatchingService;

// Set JSON header
header('Content-Type: application/json');

// Start session
Session::start();

// Check if user is logged in and is a driver
if (!Session::has('user_id') || Session::get('user_role') !== 'driver') {
    JsonResponse::error('Unauthorized access - AI matching requires authentication', 401);
}

$driverId = Session::get('user_id');

try {
    $pdo = Database::getInstance()->getConnection();

    // Get parameters
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;

    // Initialize AI Matching Service
    $aiMatchingService = new AIMatchingService($pdo);

    // Get AI-powered matches
    $result = $aiMatchingService->findAIMatches($driverId, $page, $limit);

    // Format response for frontend
    $formattedMatches = [];
    foreach ($result['matches'] as $match) {
        $job = $match['job'];
        $score = $match['score'];
        $insights = $match['ai_insights'];
        $factors = $match['match_factors'];

        $formattedMatches[] = [
            'job_id' => $job['id'],
            'score' => $score,
            'ai_powered' => true,
            'confidence' => calculateConfidence($score, $factors),
            'details' => [
                'license_compatibility' => $factors['license_compatibility'],
                'experience_relevance' => $factors['experience_relevance'],
                'location_proximity' => $factors['location_proximity'],
                'availability_alignment' => $factors['availability_alignment'],
                'semantic_similarity' => $factors['semantic_similarity']
            ],
            'job' => [
                'id' => $job['id'],
                'title' => $job['title'],
                'description' => $job['description'],
                'location' => $job['location'],
                'company_name' => $job['company_name'],
                'company_city' => $job['company_city'] ?? '',
                'salary_min' => $job['salary_min'],
                'salary_max' => $job['salary_max'],
                'created_at' => $job['created_at'],
                'is_urgent' => detectUrgency($job),
                'job_type' => $job['job_type'] ?? 'full_time',
                'vehicle_type' => extractVehicleType($job)
            ],
            'ai_insights' => $insights,
            'match_explanation' => generateMatchExplanation($score, $factors),
            'recommendation_strength' => getRecommendationStrength($score)
        ];
    }

    JsonResponse::success([
        'data' => [
            'matches' => $formattedMatches,
            'total' => $result['total'],
            'limit' => $limit,
            'page' => $page,
            'pages' => $result['pages'],
            'ai_powered' => true,
            'algorithm_version' => '2.1',
            'processing_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'match_quality' => assessOverallMatchQuality($formattedMatches)
        ]
    ]);
} catch (Exception $e) {
    error_log("AI Matching API error: " . $e->getMessage());
    JsonResponse::error('AI matching system temporarily unavailable: ' . $e->getMessage());
}

/**
 * Helper functions για AI matching
 */
function calculateConfidence($score, $factors)
{
    // Calculate confidence based on score consistency across factors
    $factorValues = array_values($factors);
    $variance = calculateVariance($factorValues);

    // High score + low variance = high confidence
    $baseConfidence = $score;
    $variancePenalty = $variance * 0.3;

    return max(0.1, min(0.99, $baseConfidence - $variancePenalty));
}

function calculateVariance($values)
{
    $mean = array_sum($values) / count($values);
    $variance = 0;

    foreach ($values as $value) {
        $variance += pow($value - $mean, 2);
    }

    return $variance / count($values);
}

function detectUrgency($job)
{
    $urgentKeywords = ['επείγον', 'άμεσα', 'urgent', 'asap'];
    $text = strtolower($job['title'] . ' ' . $job['description']);

    foreach ($urgentKeywords as $keyword) {
        if (strpos($text, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

function extractVehicleType($job)
{
    $text = strtolower($job['title'] . ' ' . $job['description']);

    if (strpos($text, 'φορτηγ') !== false) return 'truck';
    if (strpos($text, 'λεωφορ') !== false) return 'bus';
    if (strpos($text, 'βυτίο') !== false) return 'tanker';
    if (strpos($text, 'βαν') !== false) return 'van';

    return 'other';
}

function generateMatchExplanation($score, $factors)
{
    $explanations = [];

    if ($factors['license_compatibility'] >= 0.9) {
        $explanations[] = "Πλήρης συμβατότητα αδειών οδήγησης";
    }

    if ($factors['experience_relevance'] >= 0.8) {
        $explanations[] = "Ιδανική εμπειρία για τη θέση";
    }

    if ($factors['location_proximity'] >= 0.9) {
        $explanations[] = "Εξαιρετική γεωγραφική τοποθεσία";
    }

    if ($factors['semantic_similarity'] >= 0.7) {
        $explanations[] = "Υψηλή συμβατότητα δεξιοτήτων";
    }

    if (empty($explanations)) {
        $explanations[] = "Βασική συμβατότητα με τις απαιτήσεις";
    }

    return implode(", ", $explanations);
}

function getRecommendationStrength($score)
{
    if ($score >= 0.9) return 'Ισχυρή Σύσταση';
    if ($score >= 0.8) return 'Καλή Σύσταση';
    if ($score >= 0.7) return 'Μέτρια Σύσταση';
    if ($score >= 0.5) return 'Αδύναμη Σύσταση';

    return 'Χαμηλή Σύσταση';
}

function assessOverallMatchQuality($matches)
{
    if (empty($matches)) {
        return [
            'rating' => 'poor',
            'message' => 'Δεν βρέθηκαν κατάλληλα ταιριάσματα'
        ];
    }

    $avgScore = array_sum(array_column($matches, 'score')) / count($matches);
    $highQualityMatches = count(array_filter($matches, function ($m) {
        return $m['score'] >= 0.8;
    }));

    if ($avgScore >= 0.8 && $highQualityMatches >= 3) {
        return [
            'rating' => 'excellent',
            'message' => 'Εξαιρετικά ταιριάσματα διαθέσιμα'
        ];
    } elseif ($avgScore >= 0.7 && $highQualityMatches >= 1) {
        return [
            'rating' => 'good',
            'message' => 'Καλά ταιριάσματα βρέθηκαν'
        ];
    } elseif ($avgScore >= 0.5) {
        return [
            'rating' => 'fair',
            'message' => 'Μέτρια ταιριάσματα διαθέσιμα'
        ];
    } else {
        return [
            'rating' => 'poor',
            'message' => 'Περιορισμένα ταιριάσματα'
        ];
    }
}

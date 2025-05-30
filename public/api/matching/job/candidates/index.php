<?php
// API endpoint for job candidates - Fixed version with proper error handling
error_reporting(0); // Disable all error reporting to prevent HTML output
ini_set('display_errors', 0);

// Disable output buffering
if (ob_get_level()) {
    ob_end_clean();
}

// Include bootstrap
require_once __DIR__ . '/../../../../../src/bootstrap.php';

use Drivejob\Core\Session;

// Set JSON header immediately
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Credentials: true');

// Helper function to send JSON response
function sendJsonResponse($success, $data = null, $error = null, $statusCode = 200)
{
    http_response_code($statusCode);

    $response = ['success' => $success];

    if ($success && $data !== null) {
        $response['data'] = $data;
    } elseif (!$success && $error !== null) {
        $response['error'] = $error;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Check if user is logged in as company
    if (!Session::has('user_id') || Session::get('user_role') !== 'company') {
        sendJsonResponse(false, null, 'Unauthorized - Please login as a company', 401);
    }

    $companyId = Session::get('user_id');
    $jobId = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;

    if (!$jobId) {
        sendJsonResponse(false, null, 'Job ID is required', 400);
    }

    // Get database connection
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

    // Check that the job belongs to the company
    $stmt = $pdo->prepare("SELECT id FROM job_listings WHERE id = ? AND company_id = ? AND is_active = 1");
    $stmt->execute([$jobId, $companyId]);

    if (!$stmt->fetch()) {
        sendJsonResponse(false, null, 'Job not found or access denied', 404);
    }

    // Get candidates from matching_scores
    $stmt = $pdo->prepare("
        SELECT 
            ms.*,
            d.id as driver_id,
            d.first_name,
            d.last_name,
            d.email,
            d.city,
            d.experience_years,
            d.available_for_work,
            d.rating
        FROM matching_scores ms
        JOIN drivers d ON ms.driver_id = d.id
        WHERE ms.job_id = ?
        AND d.available_for_work = 1
        ORDER BY ms.overall_score DESC
        LIMIT ?
    ");
    $stmt->execute([$jobId, $limit]);
    $candidates = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    // Format results
    $formattedCandidates = [];
    foreach ($candidates as $candidate) {
        $score = floatval($candidate['overall_score']);

        // Determine recommendation based on score
        if ($score >= 0.9) {
            $recommendation = 'Εξαιρετική αντιστοιχία!';
        } elseif ($score >= 0.75) {
            $recommendation = 'Πολύ καλή αντιστοιχία';
        } elseif ($score >= 0.6) {
            $recommendation = 'Καλή αντιστοιχία';
        } elseif ($score >= 0.4) {
            $recommendation = 'Μέτρια αντιστοιχία';
        } else {
            $recommendation = 'Χαμηλή αντιστοιχία';
        }

        $formattedCandidates[] = [
            'driver_id' => intval($candidate['driver_id']),
            'name' => trim($candidate['first_name'] . ' ' . $candidate['last_name']),
            'email' => $candidate['email'],
            'city' => $candidate['city'] ?: 'Δεν έχει οριστεί',
            'experience_years' => intval($candidate['experience_years']),
            'rating' => $candidate['rating'] ? round(floatval($candidate['rating']), 1) : 0,
            'match_score' => round($score * 100, 1),
            'recommendation' => $recommendation
        ];
    }

    sendJsonResponse(true, [
        'candidates' => $formattedCandidates,
        'count' => count($formattedCandidates)
    ]);
} catch (\Exception $e) {
    // Log error but don't output it
    error_log("Error in job candidates API: " . $e->getMessage());
    sendJsonResponse(false, null, 'Failed to get candidates', 500);
}

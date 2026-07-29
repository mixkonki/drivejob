<?php
// API endpoint for job candidates
require_once __DIR__ . '/../../../../../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Core\JsonResponse;

// Start session
Session::start();

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in as company
if (!Session::has('user_id') || Session::get('user_role') !== 'company') {
    JsonResponse::error('Unauthorized', 401);
    exit;
}

$companyId = Session::get('user_id');
$jobId = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;

if (!$jobId) {
    JsonResponse::error('Job ID is required', 400);
    exit;
}

try {
    // Get database connection
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

    // Check that the job belongs to the company
    $stmt = $pdo->prepare("SELECT id FROM job_listings WHERE id = ? AND company_id = ? AND is_active = 1");
    $stmt->execute([$jobId, $companyId]);

    if (!$stmt->fetch()) {
        JsonResponse::error('Job not found or access denied', 404);
        exit;
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
        $formattedCandidates[] = [
            'driver_id' => $candidate['driver_id'],
            'name' => $candidate['first_name'] . ' ' . $candidate['last_name'],
            'email' => $candidate['email'],
            'city' => $candidate['city'],
            'experience_years' => $candidate['experience_years'],
            'rating' => round($candidate['rating'], 1),
            'match_score' => round($candidate['overall_score'] * 100, 1),
            'recommendation' => getRecommendation($candidate['overall_score'])
        ];
    }

    JsonResponse::success([
        'candidates' => $formattedCandidates,
        'count' => count($formattedCandidates)
    ]);
} catch (\Exception $e) {
    error_log("Error getting job candidates: " . $e->getMessage());
    JsonResponse::error('Failed to get candidates', 500);
}

function getRecommendation($score)
{
    if ($score >= 0.9) return 'Εξαιρετική αντιστοιχία!';
    if ($score >= 0.75) return 'Πολύ καλή αντιστοιχία';
    if ($score >= 0.6) return 'Καλή αντιστοιχία';
    if ($score >= 0.4) return 'Μέτρια αντιστοιχία';
    return 'Χαμηλή αντιστοιχία';
}

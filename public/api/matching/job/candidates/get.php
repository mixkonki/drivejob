<?php
// Clean API endpoint - no chance of HTML output
ob_start();
ob_clean();

// Disable all error output
error_reporting(0);
ini_set('display_errors', 0);
ini_set('html_errors', 0);

// Clear any existing output
while (ob_get_level()) {
    ob_end_clean();
}

// Start fresh output buffer
ob_start();

try {
    // Include bootstrap
    require_once __DIR__ . '/../../../../../src/bootstrap.php';

    // Clear any output from bootstrap
    ob_clean();

    // Set headers
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');

    // Check session
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'company') {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $companyId = $_SESSION['user_id'];
    $jobId = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;

    if (!$jobId) {
        echo json_encode(['success' => false, 'error' => 'Job ID required']);
        exit;
    }

    // Get database connection
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

    // Verify job belongs to company
    $stmt = $pdo->prepare("SELECT id FROM job_listings WHERE id = ? AND company_id = ? AND is_active = 1");
    $stmt->execute([$jobId, $companyId]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Job not found']);
        exit;
    }

    // Get candidates
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

    // Clear any accidental output
    ob_clean();

    // Send JSON
    echo json_encode([
        'success' => true,
        'data' => [
            'candidates' => $formattedCandidates,
            'count' => count($formattedCandidates)
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Server error']);
}

// End output buffering and send
ob_end_flush();
exit;

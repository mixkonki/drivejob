<?php
// Test API endpoint that returns matches for driver 26
require_once '../src/bootstrap.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

    $stmt = $pdo->prepare("
        SELECT 
            ms.*,
            jl.id as job_id,
            jl.title as job_title,
            jl.description,
            jl.location,
            jl.salary_range,
            c.company_name,
            c.id as company_id
        FROM matching_scores ms
        JOIN job_listings jl ON ms.job_id = jl.id
        LEFT JOIN companies c ON jl.company_id = c.id
        WHERE ms.driver_id = 26
        AND jl.is_active = 1
        ORDER BY ms.overall_score DESC
        LIMIT 5
    ");
    $stmt->execute();
    $matches = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'matches' => $matches,
            'count' => count($matches)
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

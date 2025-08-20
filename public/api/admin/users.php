<?php
require_once __DIR__ . '/../../../src/bootstrap.php';

use Drivejob\Middleware\AuthenticationMiddleware as Auth;
use Drivejob\Core\JsonResponse;
use Drivejob\Core\Database;

// Require admin role
if (!Auth::requireAdmin(true)) {
    return;
}

// Set JSON content type
header('Content-Type: application/json');

try {
    $pdo = Database::getInstance()->getConnection();

    // Get users based on type filter
    $type = $_GET['type'] ?? 'all';
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $offset = ($page - 1) * $limit;

    $whereClause = '';
    $params = [];

    if ($type !== 'all') {
        $whereClause = 'WHERE u.role = ?';
        $params[] = $type;
    }

    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM users u $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];

    // Get users
    $sql = "
        SELECT 
            u.id,
            u.email,
            u.role,
            u.is_active,
            u.is_verified,
            u.created_at,
            CASE 
                WHEN u.role = 'driver' THEN CONCAT(d.first_name, ' ', d.last_name)
                WHEN u.role = 'company' THEN c.company_name
                ELSE u.email
            END as name
        FROM users u
        LEFT JOIN drivers d ON u.id = d.user_id AND u.role = 'driver'
        LEFT JOIN companies c ON u.id = c.user_id AND u.role = 'company'
        $whereClause
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?
    ";

    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    JsonResponse::success([
        'users' => $users,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]
    ]);
} catch (\Exception $e) {
    error_log("Admin users API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch users",
        "error"   => $e->getMessage(),
        "trace"   => $e->getTraceAsString()
    ]);
}

<?php
require_once __DIR__ . '/../../../src/bootstrap.php';

use Drivejob\Middleware\AuthenticationMiddleware as Auth;
use Drivejob\Core\JsonResponse;
use Drivejob\Core\Database;

// Require admin role
if (!Auth::requireAdmin(true)) {
    exit;
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
        $whereClause = 'WHERE r.name = ?';
        $params[] = $type;
    }

    // Get total count with RBAC
    $countSql = "
        SELECT COUNT(DISTINCT u.id) as total 
        FROM users u
        LEFT JOIN user_roles ur ON ur.user_id = u.id
        LEFT JOIN roles r ON r.id = ur.role_id
        $whereClause
    ";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];

    // Get users with RBAC roles
    $sql = "
        SELECT 
            u.id,
            u.username,
            u.email,
            u.is_active,
            u.is_verified,
            u.created_at,
            GROUP_CONCAT(r.name ORDER BY ur.is_primary DESC, r.name) AS roles,
            GROUP_CONCAT(r.id ORDER BY ur.is_primary DESC, r.name) AS role_ids,
            c.id   AS company_id,
            c.company_name,
            c.phone AS company_phone,
            d.id   AS driver_id,
            d.first_name,
            d.last_name,
            d.phone AS driver_phone,
            CASE 
                WHEN d.first_name IS NOT NULL THEN CONCAT(d.first_name, ' ', d.last_name)
                WHEN c.company_name IS NOT NULL THEN c.company_name
                ELSE u.username
            END as name
        FROM users u
        LEFT JOIN user_roles ur ON ur.user_id = u.id
        LEFT JOIN roles r ON r.id = ur.role_id
        LEFT JOIN companies c ON c.user_id = u.id
        LEFT JOIN drivers d ON d.user_id = u.id
        $whereClause
        GROUP BY u.id, u.username, u.email, u.is_active, u.is_verified, u.created_at,
                 c.id, c.company_name, c.phone, d.id, d.first_name, d.last_name, d.phone
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
        "message" => "Failed to fetch users"
    ]);
}

<?php
require_once __DIR__ . '/../../../src/bootstrap.php';

use Drivejob\Middleware\AuthenticationMiddleware as Auth;
use Drivejob\Core\JsonResponse;
use Drivejob\Core\Database;

// Require company role
if (!Auth::requireCompany(true)) {
    return;
}

// Set JSON content type
header('Content-Type: application/json');

try {
    $pdo = Database::getInstance()->getConnection();
    $user = Auth::getCurrentUser();
    $companyId = $user['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get company profile
        $stmt = $pdo->prepare("
            SELECT 
                c.*,
                u.email,
                u.is_verified,
                u.created_at
            FROM companies c
            JOIN users u ON c.user_id = u.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$companyId]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$company) {
            JsonResponse::error('Company profile not found', 404);
        }

        JsonResponse::success(['company' => $company]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Update company profile
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            JsonResponse::error('Invalid JSON input', 400);
        }

        $allowedFields = [
            'company_name',
            'description',
            'industry',
            'company_size',
            'website',
            'phone',
            'address',
            'city',
            'postal_code'
        ];

        $updateFields = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }

        if (empty($updateFields)) {
            JsonResponse::error('No valid fields to update', 400);
        }

        $params[] = $companyId;

        $sql = "UPDATE companies SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        JsonResponse::success(['message' => 'Profile updated successfully']);
    } else {
        JsonResponse::error('Method not allowed', 405);
    }
} catch (\Exception $e) {
    error_log("Company profile API error: " . $e->getMessage());
    JsonResponse::error('Failed to process request', 500);
}

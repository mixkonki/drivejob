<?php
require_once __DIR__ . '/../../../src/bootstrap.php';

use Drivejob\Middleware\AuthenticationMiddleware as Auth;
use Drivejob\Core\JsonResponse;
use Drivejob\Core\Database;

// Require driver role
if (!Auth::requireDriver(true)) {
    return;
}

// Set JSON content type
header('Content-Type: application/json');

try {
    $pdo = Database::getInstance()->getConnection();
    $user = Auth::getCurrentUser();
    $driverId = $user['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get driver profile
        $stmt = $pdo->prepare("
            SELECT 
                d.*,
                u.email,
                u.is_verified,
                u.created_at
            FROM drivers d
            JOIN users u ON d.user_id = u.id
            WHERE d.user_id = ?
        ");
        $stmt->execute([$driverId]);
        $driver = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$driver) {
            JsonResponse::error('Driver profile not found', 404);
        }

        JsonResponse::success(['driver' => $driver]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // Update driver profile
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            JsonResponse::error('Invalid JSON input', 400);
        }

        $allowedFields = [
            'first_name',
            'last_name',
            'phone',
            'date_of_birth',
            'address',
            'city',
            'postal_code',
            'license_number',
            'license_expiry',
            'experience_years',
            'available_for_work',
            'preferred_job_type',
            'preferred_location',
            'bio'
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

        $params[] = $driverId;

        $sql = "UPDATE drivers SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        JsonResponse::success(['message' => 'Profile updated successfully']);
    } else {
        JsonResponse::error('Method not allowed', 405);
    }
} catch (\Exception $e) {
    error_log("Driver profile API error: " . $e->getMessage());
    JsonResponse::error('Failed to process request', 500);
}

<?php
require_once __DIR__ . '/../../../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Core\JsonResponse;
use Drivejob\Core\Database;
use Drivejob\Models\AuthModel;

// Set JSON content type
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    JsonResponse::error('Method not allowed', 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    JsonResponse::error('Invalid JSON input', 400);
}

$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    JsonResponse::error('Email and password are required', 400);
}

try {
    $pdo = Database::getInstance()->getConnection();
    $authModel = new AuthModel($pdo);

    // Authenticate user
    $user = $authModel->authenticate($email, $password);

    if (!$user) {
        JsonResponse::error('Invalid credentials', 401);
    }

    // Start session and set user data
    Session::start();
    Session::set('user_id', $user['user_id']);
    Session::set('user_role', $user['role']);
    Session::set('user_name', $user['name']);
    Session::set('user_email', $email);
    Session::set('is_verified', $user['is_verified'] ?? true);

    // Generate JWT token
    $payload = [
        'sub' => $user['user_id'],
        'role' => $user['role'],
        'email' => $email,
        'name' => $user['name'] ?? '',
        'is_verified' => (bool)($user['is_verified'] ?? false),
        'iat' => time(),
        'exp' => time() + 86400 // 1 day
    ];

    $token = null;
    try {
        $token = \Drivejob\Core\Jwt::encode($payload);
    } catch (\Exception $e) {
        // JWT generation failed, continue without token
        error_log("JWT generation failed: " . $e->getMessage());
    }

    $response = [
        'success' => true,
        'user' => [
            'id' => $user['user_id'],
            'role' => $user['role'],
            'email' => $email,
            'name' => $user['name'] ?? ''
        ]
    ];

    if ($token) {
        $response['token'] = $token;
    }

    JsonResponse::success($response);
} catch (\Exception $e) {
    error_log("API Login error: " . $e->getMessage());
    JsonResponse::error('Authentication failed', 500);
}

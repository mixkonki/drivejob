<?php
// API endpoint for sending messages
error_reporting(0);
ini_set('display_errors', 0);

// Clear any output
while (ob_get_level()) {
    ob_end_clean();
}

require_once __DIR__ . '/../../../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Services\MessagingService;

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Helper function for JSON response
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

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        sendJsonResponse(false, null, 'Invalid JSON input', 400);
    }

    $companyId = Session::get('user_id');
    $driverId = isset($input['driver_id']) ? intval($input['driver_id']) : 0;
    $jobId = isset($input['job_id']) ? intval($input['job_id']) : null;
    $subject = isset($input['subject']) ? trim($input['subject']) : '';
    $message = isset($input['message']) ? trim($input['message']) : '';

    // Validate required fields
    if (!$driverId || !$subject || !$message) {
        sendJsonResponse(false, null, 'Missing required fields', 400);
    }

    // Verify driver exists
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();
    $stmt = $pdo->prepare("SELECT id FROM drivers WHERE id = ?");
    $stmt->execute([$driverId]);

    if (!$stmt->fetch()) {
        sendJsonResponse(false, null, 'Driver not found', 404);
    }

    // If job_id provided, verify it belongs to the company
    if ($jobId) {
        $stmt = $pdo->prepare("SELECT id FROM job_listings WHERE id = ? AND company_id = ?");
        $stmt->execute([$jobId, $companyId]);

        if (!$stmt->fetch()) {
            sendJsonResponse(false, null, 'Job not found or access denied', 404);
        }
    }

    // Check if conversation already exists
    $stmt = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE company_id = ? AND driver_id = ? AND status = 'active'
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$companyId, $driverId]);
    $existingConversation = $stmt->fetch();

    $messagingService = new MessagingService();

    if ($existingConversation) {
        // Send message to existing conversation
        $conversationId = $existingConversation['id'];
        $messageId = $messagingService->sendMessage($conversationId, 'company', $companyId, $message);
    } else {
        // Start new conversation
        $conversationId = $messagingService->startConversation($companyId, $driverId, $jobId, $subject, $message);
    }

    sendJsonResponse(true, [
        'conversation_id' => $conversationId,
        'message' => 'Message sent successfully'
    ]);
} catch (\Exception $e) {
    error_log("Error in messaging API: " . $e->getMessage());
    sendJsonResponse(false, null, 'Failed to send message', 500);
}

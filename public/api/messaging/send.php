<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Core\JsonResponse;
use Drivejob\Services\MessagingService;
use Drivejob\Core\Database;

// Start session
Session::start();

// Check if user is logged in and is a company
if (!Session::has('user_id') || Session::get('user_role') !== 'company') {
    JsonResponse::error('Unauthorized access', 401);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
$driverId = $input['driver_id'] ?? null;
$jobId = $input['job_id'] ?? null;
$subject = $input['subject'] ?? '';
$message = $input['message'] ?? '';

if (!$driverId || !$jobId || !$subject || !$message) {
    JsonResponse::error('Missing required fields');
}

try {
    $pdo = Database::getInstance()->getConnection();
    $companyId = Session::get('user_id');

    // Create messaging service instance
    $messagingService = new MessagingService();

    // Check if conversation already exists
    $stmt = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE company_id = ? AND driver_id = ? AND job_id = ?
        AND status = 'active'
    ");
    $stmt->execute([$companyId, $driverId, $jobId]);
    $existingConversation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingConversation) {
        // Send message to existing conversation
        $conversationId = $existingConversation['id'];
        $messagingService->sendMessage($conversationId, 'company', $companyId, $message);
    } else {
        // Start new conversation
        $conversationId = $messagingService->startConversation($companyId, $driverId, $jobId, $subject, $message);
    }

    // Return success response
    JsonResponse::success([
        'conversation_id' => $conversationId,
        'message' => 'Το μήνυμα στάλθηκε επιτυχώς'
    ]);
} catch (Exception $e) {
    error_log("Messaging API Error: " . $e->getMessage());
    JsonResponse::error('Σφάλμα κατά την αποστολή του μηνύματος: ' . $e->getMessage());
}

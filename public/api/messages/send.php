<?php
require_once __DIR__ . '/../../../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Core\Database;

Session::start();

header('Content-Type: application/json');

if (!Session::has('user_id')) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$conversationId = $data['conversation_id'] ?? null;
$receiverId = $data['receiver_id'] ?? null;
$message = $data['message'] ?? null;

if (!$conversationId || !$receiverId || !$message) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit();
}

$pdo = Database::getInstance()->getConnection();

// Insert message
$stmt = $pdo->prepare("
    INSERT INTO messages (conversation_id, sender_id, receiver_id, message, created_at)
    VALUES (?, ?, ?, ?, NOW())
");

$stmt->execute([
    $conversationId,
    Session::get('user_id'),
    $receiverId,
    $message
]);

// Update conversation last message
$stmt = $pdo->prepare("
    UPDATE conversations 
    SET last_message_at = NOW() 
    WHERE id = ?
");
$stmt->execute([$conversationId]);

echo json_encode([
    'success' => true,
    'message' => [
        'id' => $pdo->lastInsertId(),
        'text' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]
]);

<?php
// Direct test of messaging API
session_start();

// Set session for testing
$_SESSION['user_id'] = 4;
$_SESSION['user_role'] = 'company';

// Set POST data
$_POST = json_decode(json_encode([
    'driver_id' => 30,
    'job_id' => 18,
    'subject' => 'Test Message Direct',
    'message' => 'This is a direct test message.'
]), true);

// Simulate JSON input
$input = json_encode($_POST);

// Override file_get_contents
function file_get_contents($filename)
{
    global $input;
    if ($filename === 'php://input') {
        return $input;
    }
    return \file_get_contents($filename);
}

echo "<h2>Direct Messaging API Test</h2>";
echo "<pre>";
echo "Session data:\n";
print_r($_SESSION);
echo "\nInput data:\n";
print_r($_POST);

// Capture output
ob_start();

// Include the API file directly
try {
    include __DIR__ . '/api/messaging/send.php';
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

$output = ob_get_clean();

echo "\nAPI Output:\n";
echo $output . "\n";

// Try to decode
$data = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "\nDecoded Response:\n";
    print_r($data);
} else {
    echo "\nJSON decode error: " . json_last_error_msg() . "\n";
    echo "First 500 chars of output:\n";
    echo substr($output, 0, 500) . "\n";
}

// Check database
require_once __DIR__ . '/../src/bootstrap.php';
$pdo = \Drivejob\Core\Database::getInstance()->getConnection();

echo "\n\nDatabase Check:\n";
echo "================\n";

// Check conversations
$stmt = $pdo->query("SELECT COUNT(*) as count FROM conversations");
$result = $stmt->fetch();
echo "Total conversations: " . $result['count'] . "\n";

// Check messages
$stmt = $pdo->query("SELECT COUNT(*) as count FROM messages");
$result = $stmt->fetch();
echo "Total messages: " . $result['count'] . "\n";

// Show last conversation
$stmt = $pdo->query("SELECT * FROM conversations ORDER BY created_at DESC LIMIT 1");
$conv = $stmt->fetch(PDO::FETCH_ASSOC);
if ($conv) {
    echo "\nLast conversation:\n";
    print_r($conv);
}

// Show last message
$stmt = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 1");
$msg = $stmt->fetch(PDO::FETCH_ASSOC);
if ($msg) {
    echo "\nLast message:\n";
    print_r($msg);
}

echo "</pre>";

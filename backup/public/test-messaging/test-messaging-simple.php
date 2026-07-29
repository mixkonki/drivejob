<?php
// Simple test of messaging functionality
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Services\MessagingService;

// Start session and set test user
Session::start();
Session::set('user_id', 4);
Session::set('user_role', 'company');

echo "<h2>Simple Messaging Test</h2>";
echo "<pre>";

try {
    // Test data
    $companyId = 4;
    $driverId = 30;
    $jobId = 18;
    $subject = "Test Message - " . date('Y-m-d H:i:s');
    $message = "This is a test message sent directly through MessagingService.";

    echo "Test Parameters:\n";
    echo "Company ID: $companyId\n";
    echo "Driver ID: $driverId\n";
    echo "Job ID: $jobId\n";
    echo "Subject: $subject\n";
    echo "Message: $message\n\n";

    // Create messaging service
    $messagingService = new MessagingService();

    // Try to start a conversation
    echo "Starting conversation...\n";
    $conversationId = $messagingService->startConversation($companyId, $driverId, $jobId, $subject, $message);
    echo "✅ Conversation created with ID: $conversationId\n\n";

    // Get conversation details
    echo "Getting conversation details...\n";
    $conversation = $messagingService->getConversation($conversationId);
    print_r($conversation);
    echo "\n";

    // Get messages
    echo "Getting messages...\n";
    $messages = $messagingService->getMessages($conversationId);
    print_r($messages);
    echo "\n";

    // Get unread count for driver
    echo "Getting unread count for driver...\n";
    $unreadCount = $messagingService->getUnreadCount('driver', $driverId);
    echo "Unread messages for driver: $unreadCount\n\n";

    // Get notifications
    echo "Getting notifications for driver...\n";
    $notifications = $messagingService->getNotifications('driver', $driverId, true);
    print_r($notifications);

    echo "\n✅ Test completed successfully!\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";

// Add a simple form for browser testing
?>

<h2>Browser Test Form</h2>
<div id="testResult"></div>

<button onclick="testMessaging()">Test Messaging API</button>

<script>
    function testMessaging() {
        const resultDiv = document.getElementById('testResult');
        resultDiv.innerHTML = 'Testing...';

        const testData = {
            driver_id: 30,
            job_id: 18,
            subject: 'Browser Test - ' + new Date().toLocaleString(),
            message: 'This is a test message from the browser.'
        };

        fetch('<?php echo BASE_URL; ?>api/messaging/send.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify(testData)
            })
            .then(response => {
                console.log('Response:', response);
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                resultDiv.innerHTML = '<pre>Response:\n' + text + '</pre>';

                try {
                    const data = JSON.parse(text);
                    console.log('Parsed data:', data);
                    if (data.success) {
                        resultDiv.innerHTML += '<p style="color: green;">✅ Success! Conversation ID: ' + data.data.conversation_id + '</p>';
                    } else {
                        resultDiv.innerHTML += '<p style="color: red;">❌ Error: ' + (data.error || 'Unknown error') + '</p>';
                    }
                } catch (e) {
                    console.error('Parse error:', e);
                    resultDiv.innerHTML += '<p style="color: red;">❌ JSON Parse Error</p>';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                resultDiv.innerHTML = '<p style="color: red;">❌ Network Error: ' + error + '</p>';
            });
    }
</script>
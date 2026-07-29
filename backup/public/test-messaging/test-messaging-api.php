<?php
// Test messaging API
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Session;

// Set test session
Session::set('user_id', 4);
Session::set('user_role', 'company');

echo "<h2>Testing Messaging API</h2>";
echo "<pre>";
echo "Session user_id: " . Session::get('user_id') . "\n";
echo "Session user_role: " . Session::get('user_role') . "\n\n";

// Test data
$testData = [
    'driver_id' => 30,
    'job_id' => 18,
    'subject' => 'Test Message',
    'message' => 'This is a test message from the API test script.'
];

echo "Test data:\n";
print_r($testData);
echo "\n";

// Make API call using cURL
$ch = curl_init('http://localhost/drivejob/public/api/messaging/send.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Cookie: ' . session_name() . '=' . session_id()
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Response Code: $httpCode\n";
echo "Raw Response:\n$response\n\n";

// Try to decode JSON
$data = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "Decoded Response:\n";
    print_r($data);
} else {
    echo "JSON decode error: " . json_last_error_msg() . "\n";
}

// Check database directly
echo "\n\nChecking database for conversations:\n";
$pdo = \Drivejob\Core\Database::getInstance()->getConnection();
$stmt = $pdo->query("SELECT * FROM conversations ORDER BY created_at DESC LIMIT 5");
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($conversations);

echo "\n\nChecking database for messages:\n";
$stmt = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 5");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($messages);

echo "</pre>";

// Add a form to test from browser
?>

<h2>Test Form</h2>
<form id="testForm">
    <p>Driver ID: <input type="number" id="driverId" value="30"></p>
    <p>Job ID: <input type="number" id="jobId" value="18"></p>
    <p>Subject: <input type="text" id="subject" value="Test Message"></p>
    <p>Message: <textarea id="message">This is a test message from the form.</textarea></p>
    <button type="submit">Send Test Message</button>
</form>

<div id="result"></div>

<script>
    document.getElementById('testForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const data = {
            driver_id: parseInt(document.getElementById('driverId').value),
            job_id: parseInt(document.getElementById('jobId').value),
            subject: document.getElementById('subject').value,
            message: document.getElementById('message').value
        };

        console.log('Sending data:', data);

        fetch('<?php echo BASE_URL; ?>api/messaging/send.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify(data)
            })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                document.getElementById('result').innerHTML = '<pre>' + text + '</pre>';

                try {
                    const json = JSON.parse(text);
                    console.log('Parsed JSON:', json);
                } catch (e) {
                    console.error('JSON parse error:', e);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('result').innerHTML = '<pre>Error: ' + error + '</pre>';
            });
    });
</script>
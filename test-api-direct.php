<?php
// Simulate a session for testing
session_start();
$_SESSION['user_id'] = 26;
$_SESSION['user_role'] = 'driver';

// Test the API endpoint directly
$url = 'http://localhost/drivejob/public/api/matching/driver/matches.php?limit=3';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Cookie: ' . session_name() . '=' . session_id()
        ]
    ]
]);

echo "=== ΔΟΚΙΜΗ API ENDPOINT ΑΠΕΥΘΕΙΑΣ ===\n\n";
echo "URL: $url\n\n";

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "❌ Αποτυχία κλήσης API\n";
    $error = error_get_last();
    if ($error) {
        echo "Error: " . $error['message'] . "\n";
    }
} else {
    echo "✅ Επιτυχής κλήση API\n";
    echo "Response:\n";
    echo $response . "\n";

    // Try to decode JSON
    $data = json_decode($response, true);
    if ($data === null) {
        echo "\n❌ Invalid JSON response\n";
        echo "JSON Error: " . json_last_error_msg() . "\n";
    } else {
        echo "\n✅ Valid JSON response\n";
        if (isset($data['success']) && $data['success']) {
            echo "Success: true\n";
            if (isset($data['data']['matches'])) {
                echo "Matches found: " . count($data['data']['matches']) . "\n";
            }
        } else {
            echo "Success: false\n";
            if (isset($data['error'])) {
                echo "Error: " . $data['error'] . "\n";
            }
        }
    }
}

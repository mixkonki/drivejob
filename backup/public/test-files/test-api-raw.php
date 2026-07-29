<?php
// Test API endpoint directly
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Manually set session for testing
session_start();
$_SESSION['user_id'] = 1; // Assuming company ID 1 exists
$_SESSION['user_role'] = 'company';

echo "Session set for testing:\n";
echo "User ID: " . $_SESSION['user_id'] . "\n";
echo "User Role: " . $_SESSION['user_role'] . "\n\n";

// Now call the API
$url = "http://localhost/drivejob/public/api/matching/job/candidates/index.php?job_id=15&limit=5";
echo "Calling API: $url\n\n";

// Use cURL to call the API with the same session
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

curl_close($ch);

echo "HTTP Code: $httpCode\n\n";
echo "Headers:\n$headers\n";
echo "Body:\n$body\n";

// Try to decode JSON
$json = json_decode($body, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "\nDecoded JSON:\n";
    print_r($json);
} else {
    echo "\nJSON decode error: " . json_last_error_msg() . "\n";
}

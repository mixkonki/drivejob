<?php
// Simple test for the API endpoint
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Session;

// Set session for testing
Session::set('user_id', 4);
Session::set('user_role', 'company');

// Test the API directly
$jobId = 18; // The test job we created

echo "Testing API endpoint...\n\n";
echo "Session user_id: " . Session::get('user_id') . "\n";
echo "Session user_role: " . Session::get('user_role') . "\n\n";

// Call the API using file_get_contents
$url = "http://localhost/drivejob/public/api/matching/job/candidates/index.php?job_id=$jobId&limit=5";
echo "API URL: $url\n\n";

// Create context with session cookie
$opts = [
    'http' => [
        'method' => 'GET',
        'header' => [
            'Accept: application/json',
            'Cookie: ' . session_name() . '=' . session_id()
        ]
    ]
];

$context = stream_context_create($opts);
$response = @file_get_contents($url, false, $context);

echo "Raw Response:\n";
echo "==============\n";
echo $response . "\n\n";

if ($response) {
    echo "Trying to decode JSON...\n";
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "JSON decoded successfully:\n";
        print_r($data);
    } else {
        echo "JSON decode error: " . json_last_error_msg() . "\n";
    }
} else {
    echo "No response received!\n";
    echo "HTTP response headers:\n";
    print_r($http_response_header);
}

<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to prevent header issues
ob_start();

// Include the index.php to initialize routing
$_SERVER['REQUEST_URI'] = '/api/matching/driver/matches?limit=5';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Capture any output
require_once __DIR__ . '/index.php';

$output = ob_get_clean();

// Display the output
echo "<h1>API Response:</h1>";
echo "<pre>";
echo htmlspecialchars($output);
echo "</pre>";

// Check if there were any errors
if (error_get_last()) {
    echo "<h2>Last Error:</h2>";
    echo "<pre>";
    print_r(error_get_last());
    echo "</pre>";
}

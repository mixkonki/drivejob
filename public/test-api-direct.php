<?php

/**
 * Direct API Test
 */

require_once '../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Controllers\Api\MatchingController;

// Start session
Session::start();

// Set test driver session
Session::set('user_id', 26);
Session::set('user_role', 'driver');
Session::set('user_type', 'drivers');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Direct API Test</h1>";
echo "<pre>";
echo "Session Data:\n";
print_r($_SESSION);
echo "\n\n";

try {
    echo "Creating MatchingController...\n";
    $controller = new MatchingController();

    echo "Calling getDriverMatches()...\n";
    $_GET['limit'] = 5; // Set the limit parameter
    $result = $controller->getDriverMatches();

    echo "\nResult:\n";
    echo $result;
} catch (Exception $e) {
    echo "\nException caught:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";

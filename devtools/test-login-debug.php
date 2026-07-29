<?php

/**
 * Debug script για το login system
 */

require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Core\CSRF;

// Έναρξη session
Session::start();

echo "=== Session Debug Info ===\n\n";

// Session Information
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Status: " . session_status() . " (1=disabled, 2=active, 3=none)\n";
echo "Session Save Path: " . session_save_path() . "\n\n";

// Cookie Parameters
echo "=== Cookie Parameters ===\n";
$params = session_get_cookie_params();
print_r($params);
echo "\n";

// Session Data
echo "=== Session Data ===\n";
print_r($_SESSION);
echo "\n";

// CSRF Token Test
echo "=== CSRF Token Test ===\n";
$token = CSRF::tokenField();
echo "Token Field HTML: " . $token . "\n";
echo "Current Token: " . CSRF::getCurrentToken() . "\n\n";

// Cookie Test
echo "=== Cookies ===\n";
print_r($_COOKIE);
echo "\n";

// Test CSRF Validation
echo "=== CSRF Validation Test ===\n";
$currentToken = CSRF::getCurrentToken();
if ($currentToken) {
    $isValid = CSRF::validateToken($currentToken);
    echo "Token validation: " . ($isValid ? "VALID" : "INVALID") . "\n";
} else {
    echo "No token found in session\n";
}
echo "\n";

// Headers Test
echo "=== Headers Test ===\n";
echo "Headers sent: " . (headers_sent() ? "YES" : "NO") . "\n";
if (headers_sent($file, $line)) {
    echo "Headers sent from: $file at line $line\n";
}
echo "\n";

echo "=== Test Complete ===\n";

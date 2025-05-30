<?php
// Simple session test
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Session;

header('Content-Type: text/plain');

echo "=== Session Information ===\n\n";
echo "Session ID: " . Session::getId() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "User ID: " . (Session::has('user_id') ? Session::get('user_id') : 'Not set') . "\n";
echo "User Role: " . (Session::has('user_role') ? Session::get('user_role') : 'Not set') . "\n";
echo "Company Name: " . (Session::has('company_name') ? Session::get('company_name') : 'Not set') . "\n\n";

echo "All Session Data:\n";
print_r($_SESSION);

echo "\n\nCookies:\n";
print_r($_COOKIE);

// Instructions
echo "\n\n=== Instructions ===\n";
echo "1. First login as a company at: http://localhost/drivejob/public/login.php\n";
echo "2. Then visit this page again to see the session data\n";
echo "3. Then test the company profile at: http://localhost/drivejob/public/companies/profile\n";

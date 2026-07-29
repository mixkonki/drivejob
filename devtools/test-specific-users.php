<?php
require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Models\AuthModel;

$pdo = require __DIR__ . '/config/database.php';
$auth = new AuthModel($pdo);

echo "=== Testing Specific Users ===\n\n";

// Test 1: Company that doesn't work
echo "Test 1: Company (info@thessdrive.gr / 123456)\n";
$result = $auth->authenticate('info@thessdrive.gr', '123456');
echo "Result: ";
var_dump($result);
echo "\n";

// Check if company exists in database
echo "Checking company in database:\n";
$stmt = $pdo->prepare("SELECT id, email, company_name, is_verified, is_active FROM companies WHERE email = ?");
$stmt->execute(['info@thessdrive.gr']);
$company = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Company data: ";
var_dump($company);
echo "\n";

// Check password
if ($company) {
    $stmt = $pdo->prepare("SELECT password FROM companies WHERE email = ?");
    $stmt->execute(['info@thessdrive.gr']);
    $pwd = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Password hash: " . substr($pwd['password'], 0, 20) . "...\n";
    echo "Password verify result: " . (password_verify('123456', $pwd['password']) ? 'TRUE' : 'FALSE') . "\n";
}
echo "\n";

// Test 2: Driver that doesn't work
echo "Test 2: Driver (kostas.michailidis1@gmail.com / 123456)\n";
$result = $auth->authenticate('kostas.michailidis1@gmail.com', '123456');
echo "Result: ";
var_dump($result);
echo "\n";

// Check if driver exists in database
echo "Checking driver in database:\n";
$stmt = $pdo->prepare("SELECT id, email, first_name, last_name, is_verified, is_active FROM drivers WHERE email = ?");
$stmt->execute(['kostas.michailidis1@gmail.com']);
$driver = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Driver data: ";
var_dump($driver);
echo "\n";

// Check password
if ($driver) {
    $stmt = $pdo->prepare("SELECT password FROM drivers WHERE email = ?");
    $stmt->execute(['kostas.michailidis1@gmail.com']);
    $pwd = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Password hash: " . substr($pwd['password'], 0, 20) . "...\n";
    echo "Password verify result: " . (password_verify('123456', $pwd['password']) ? 'TRUE' : 'FALSE') . "\n";
}
echo "\n";

// Test 3: Working driver for comparison
echo "Test 3: Working Driver (kostas.michailidis@hotmail.gr / 123456)\n";
$result = $auth->authenticate('kostas.michailidis@hotmail.gr', '123456');
echo "Result: ";
var_dump($result);
echo "\n";

<?php
require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Models\AuthModel;

$pdo = require __DIR__ . '/config/database.php';
$auth = new AuthModel($pdo);

echo "=== Testing Authentication Directly ===\n\n";

// Test 1: Admin
echo "Test 1: Admin (admin@drivejob.gr / admin123)\n";
$result = $auth->authenticate('admin@drivejob.gr', 'admin123');
echo "Result: ";
var_dump($result);
echo "\n";

// Test 2: Driver
echo "Test 2: Driver (kostas.michailidis@hotmail.gr / 123456)\n";
$result = $auth->authenticate('kostas.michailidis@hotmail.gr', '123456');
echo "Result: ";
var_dump($result);
echo "\n";

// Test 3: Company
echo "Test 3: Company (info@thessdrive.gr / 123456)\n";
$result = $auth->authenticate('info@thessdrive.gr', '123456');
echo "Result: ";
var_dump($result);
echo "\n";

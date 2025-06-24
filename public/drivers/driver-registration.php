<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Controllers\Driver\DriversController;
use Drivejob\Core\Session;
use Drivejob\Core\Database;

Session::start();

// If already logged in, redirect to profile
if (Session::has('user_id')) {
    header('Location: ' . BASE_URL . 'drivers/profile');
    exit();
}

// Get PDO instance
$pdo = Database::getInstance()->getConnection();

$controller = new DriversController($pdo);
$controller->showRegistrationForm();

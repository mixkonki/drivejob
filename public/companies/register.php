<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Controllers\Company\CompaniesController;
use Drivejob\Core\Session;
use Drivejob\Core\Database;

Session::start();

// If already logged in, redirect to profile
if (Session::has('user_id')) {
    header('Location: ' . BASE_URL . 'companies/profile');
    exit();
}

// Get PDO instance
$pdo = Database::getInstance()->getConnection();

$controller = new CompaniesController($pdo);
$controller->showRegistrationForm();

<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Middleware\AuthenticationMiddleware;
use Drivejob\Controllers\Driver\DriversController;
use Drivejob\Core\Database;

// Require driver role
AuthenticationMiddleware::requireRole('driver');

// Get PDO instance
$pdo = Database::getInstance()->getConnection();

// Initialize controller and show profile
$controller = new DriversController($pdo);
$controller->profile();

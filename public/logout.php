<?php

/**
 * Logout Page - Direct Entry Point
 * Handles user logout
 */
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Controllers\AuthController;

// Create controller instance and logout
$controller = new AuthController();
$controller->logout();

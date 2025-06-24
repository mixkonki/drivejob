<?php
/**
 * Company Edit Profile Page
 */

require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Controllers\Company\CompaniesController;
use Drivejob\Core\Session;

Session::start();

// Check authentication
if (!Session::has('user_id') || Session::get('user_role') !== 'company') {
    // Redirect to login with return URL
    $returnUrl = '/drivejob/public/companies/edit-profile';
    header('Location: /drivejob/public/login.php?redirect=' . urlencode($returnUrl));
    exit;
}

// Initialize controller
$controller = new CompaniesController();

// Handle the request
$controller->edit();
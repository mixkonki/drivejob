<?php

/**
 * Login Page - Direct Entry Point with Diagnostics
 * Loads the AuthController to show login form
 */
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Controllers\AuthController;
use Drivejob\Core\CSRF;
use Drivejob\Core\Session;

// Start session (already started in bootstrap but ensure it's active)
Session::start();

// ΔΙΑΓΝΩΣΤΙΚΟΣ ΚΩΔΙΚΑΣ
error_log("=== LOGIN PAGE LOADED ===");
error_log("Session ID: " . session_id());
error_log("Session Status: " . session_status());
error_log("Has user_id: " . (Session::has('user_id') ? 'YES' : 'NO'));
error_log("Has user_role: " . (Session::has('user_role') ? 'YES' : 'NO'));
if (Session::has('user_id')) {
    error_log("User ID: " . Session::get('user_id'));
    error_log("User Role: " . Session::get('user_role'));
}
error_log("Session Data: " . print_r($_SESSION, true));
error_log("Cookies: " . print_r($_COOKIE, true));

// Έλεγχος αν ο χρήστης είναι ήδη συνδεδεμένος
if (Session::has('user_id') && Session::has('user_role')) {
    error_log("User already logged in, redirecting...");
    $role = Session::get('user_role');
    if ($role === 'driver') {
        header('Location: ' . BASE_URL . 'drivers/profile.php');
        exit();
    } elseif ($role === 'company') {
        header('Location: ' . BASE_URL . 'companies/profile.php');
        exit();
    } elseif ($role === 'admin') {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
        exit();
    }
}

// Prevent caching of this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

error_log("Showing login form...");

// Create controller instance and show login form
$controller = new AuthController();
$controller->showLoginForm();

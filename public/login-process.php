<?php

/**
 * Login Process - Handles form submission with diagnostics
 * Processes login via AuthController
 */
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Controllers\AuthController;
use Drivejob\Core\Session;
use Drivejob\Core\Logger;

// ΔΙΑΓΝΩΣΤΙΚΟΣ ΚΩΔΙΚΑΣ - Αρχή
error_log("=== LOGIN PROCESS START ===");
error_log("Session ID: " . session_id());
error_log("Session Status: " . session_status());
error_log("POST Data: " . print_r($_POST, true));
error_log("Session Data BEFORE: " . print_r($_SESSION, true));
error_log("Cookies: " . print_r($_COOKIE, true));

// Έλεγχος CSRF token
if (isset($_POST['csrf_token'])) {
    error_log("CSRF Token from POST: " . $_POST['csrf_token']);
    error_log("CSRF Token from SESSION: " . (Session::get('csrf_token') ?? 'NOT SET'));
}

// Create controller instance and process login
$controller = new AuthController();
$controller->login();

// Αυτό δεν θα εκτελεστεί ποτέ γιατί το login() κάνει redirect
// Αλλά το αφήνουμε για debug
error_log("=== LOGIN PROCESS END (This should not appear) ===");

<?php

// Αρχικοποίηση της εφαρμογής
$container = require_once __DIR__ . '/../../src/bootstrap.php';

// Λήψη του PDO από το container
$pdo = $container->get('pdo');

use Drivejob\Core\Session;
// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι εταιρεία
if (!Session::has('user_id') || !Session::has('role') || Session::get('role') !== 'company') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Δημιουργία του controller και κλήση της μεθόδου edit
$controller = new \Drivejob\Controllers\Company\CompaniesController($pdo);
$controller->edit();

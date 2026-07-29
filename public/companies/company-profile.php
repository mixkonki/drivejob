<?php

// Αρχικοποίηση της εφαρμογής
$container = require_once __DIR__ . '/../../src/bootstrap.php';

// Λήψη του PDO από το container
$pdo = $container->get('pdo');

// Ξεκίνημα ή συνέχιση session
use Drivejob\Core\Session;

Session::start();

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι εταιρεία
if (!Session::has('user_id') || !Session::has('user_role') || Session::get('user_role') !== 'company') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Δημιουργία του controller και κλήση της μεθόδου profile
$controller = new \Drivejob\Controllers\Company\CompaniesController($pdo);
$controller->profile();

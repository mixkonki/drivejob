<?php

// Αρχικοποίηση της εφαρμογής
$container = require_once __DIR__ . '/../../src/bootstrap.php';

// Λήψη του PDO από το container
$pdo = $container->get('pdo');

// Ξεκίνημα ή συνέχιση session
use Drivejob\Core\Session;

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι εταιρεία
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'company') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Δημιουργία του controller και κλήση της μεθόδου profile
$controller = new \Drivejob\Controllers\CompaniesController($pdo);
$controller->profile();

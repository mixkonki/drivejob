<?php

// public/index.php

// Ρύθμιση καταγραφής σφαλμάτων
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/logs/php_errors.log');

// Δημιουργία του φακέλου logs αν δεν υπάρχει
if (!is_dir(dirname(__DIR__) . '/logs')) {
    mkdir(dirname(__DIR__) . '/logs', 0755, true);
}

// Αρχικοποίηση της εφαρμογής
$container = require_once __DIR__ . '/../src/bootstrap.php';

// Εκτέλεση των middleware
\Drivejob\Core\CSRFMiddleware::handle();

// Αρχικοποίηση του FrontController
\Drivejob\Core\FrontController::initialize();

// Δρομολόγηση της αίτησης
\Drivejob\Core\FrontController::dispatch();

// Φόρτωση του FPDF αν δεν υπάρχει ήδη
if (!class_exists('FPDF')) {
    require_once __DIR__ . '/../vendor/fpdf/fpdf.php';
}

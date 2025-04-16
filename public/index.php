<?php
// public/index.php
// Ρύθμιση καταγραφής σφαλμάτων
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/logs/php_errors.log');

// Δημιουργία του φακέλου logs αν δεν υπάρχει
if (!is_dir(dirname(__DIR__) . '/logs')) {
    mkdir(dirname(__DIR__) . '/logs', 0755, true);
}
// Αρχικοποίηση της εφαρμογής
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\FrontController;
use Drivejob\Core\CSRFMiddleware;

// Εκτέλεση των middleware
CSRFMiddleware::handle();

// Αρχικοποίηση του FrontController
FrontController::initialize();

// Δρομολόγηση της αίτησης
FrontController::dispatch();
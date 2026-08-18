<?php

// public/index.php

// Καταγραφή σφαλμάτων — η ΕΜΦΑΝΙΣΗ ρυθμίζεται από το bootstrap ανά περιβάλλον
// (Πακέτο 9: σε παραγωγή τα σφάλματα ΔΕΝ εμφανίζονται ποτέ στον χρήστη)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/logs/php_errors.log');

// Δημιουργία του φακέλου logs αν δεν υπάρχει
if (!is_dir(dirname(__DIR__) . '/logs')) {
    mkdir(dirname(__DIR__) . '/logs', 0755, true);
}

// Αρχικοποίηση της εφαρμογής
$container = require_once __DIR__ . '/../src/bootstrap.php';

/* Κεφαλίδες ασφαλείας για ΚΑΘΕ απόκριση (Πακέτο 9) —
   ισχύουν και σε JSON/αρχεία, όχι μόνο στις σελίδες με header.php */
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(self), camera=(), microphone=(), payment=()');
    // HSTS μόνο όταν το site σερβίρεται πραγματικά σε HTTPS
    if (defined('IS_HTTPS') && IS_HTTPS) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

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

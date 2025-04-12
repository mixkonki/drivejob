<?php
// src/bootstrap.php

// Αυτόματη φόρτωση μέσω Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Συμπερίληψη του config.php για τον ορισμό σταθερών
require_once __DIR__ . '/../config/config.php';

// Έναρξη της συνεδρίας
use Drivejob\Core\Session;
Session::start();

// Ορισμός περιβάλλοντος
defined('ENVIRONMENT') or define('ENVIRONMENT', 'development');

// Ρυθμίσεις για το περιβάλλον
if (ENVIRONMENT === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

// Σύνδεση με τη βάση δεδομένων
require_once __DIR__ . '/../config/database.php';

// Λοιπή αρχικοποίηση
// ...
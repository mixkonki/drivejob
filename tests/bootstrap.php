<?php

/**
 * Bootstrap αρχείο για τις δοκιμές PHPUnit
 * 
 * Αυτό το αρχείο φορτώνεται πριν από την εκτέλεση των δοκιμών
 * και ρυθμίζει το περιβάλλον δοκιμών
 */

// Ορισμός του ROOT_DIR
define('ROOT_DIR', dirname(__DIR__));

// Φόρτωση του autoloader του Composer
require_once ROOT_DIR . '/vendor/autoload.php';

// Φόρτωση .env (αν υπάρχει) ώστε τα DB_* να είναι διαθέσιμα στα tests
if (class_exists(\Dotenv\Dotenv::class) && file_exists(ROOT_DIR . '/.env')) {
    \Dotenv\Dotenv::createImmutable(ROOT_DIR)->safeLoad();
}

// Ορισμός της σταθεράς BASE_URL για το περιβάλλον δοκιμών
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/');
}

// Δημιουργία mock για τη βάση δεδομένων για τις δοκιμές
class MockPDO extends PDO
{
    public function __construct()
    {
        // Κενός constructor για να αποφύγουμε τη σύνδεση με τη βάση δεδομένων
    }
}

// Συνάρτηση για τη δημιουργία mock PDO για τις δοκιμές
function createMockPDO()
{
    return new MockPDO();
}

// Ρύθμιση του error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Ρύθμιση της ζώνης ώρας
date_default_timezone_set('Europe/Athens');

// Ρύθμιση της κωδικοποίησης
mb_internal_encoding('UTF-8');

echo "PHPUnit bootstrap file loaded successfully.\n";

<?php

// Αυτόματη φόρτωση μέσω Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Συμπερίληψη του config.php για να οριστούν οι σταθερές ROOT_DIR και BASE_URL
require_once __DIR__ . '/../config/config.php';

// Έλεγχος της ROOT_DIR
//echo "Η ROOT_DIR είναι: " . ROOT_DIR . "<br>";

use Drivejob\Controllers\HomeController;

// Φόρτωση των περιβαλλοντικών μεταβλητών
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../config');
$dotenv->load();

// Απόδοση της αρχικής σελίδας
$controller = new HomeController();
$controller->renderHomePage();

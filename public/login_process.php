<?php

// public/login_process.php

// Αρχικοποίηση της εφαρμογής
$container = require_once __DIR__ . '/../src/bootstrap.php';

// Λήψη του PDO από το container
$pdo = $container->get('pdo');

// Ανακατεύθυνση στο νέο controller
$controller = new \Drivejob\Controllers\AuthController($pdo);
$controller->processLogin();

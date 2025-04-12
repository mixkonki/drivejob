<?php
// public/logout.php

// Αρχικοποίηση της εφαρμογής
require_once __DIR__ . '/../src/bootstrap.php';

// Ανακατεύθυνση στο νέο controller
$controller = new \Drivejob\Controllers\AuthController($pdo);
$controller->logout();
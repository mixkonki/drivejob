<?php
// Αρχικοποίηση της εφαρμογής
require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Controllers\Company\CompaniesController;
use Drivejob\Core\Database;

// Δημιουργία του controller με PDO instance
$pdo = Database::getInstance()->getConnection();
$controller = new CompaniesController($pdo);

// Κλήση της μεθόδου update
$controller->update();

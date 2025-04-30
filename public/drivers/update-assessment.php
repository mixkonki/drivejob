<?php
// Ανακατεύθυνση στη σωστή διαδρομή
require_once __DIR__ . '/../../src/bootstrap.php';

// Δημιουργία του controller και κλήση της μεθόδου
$driversController = new \Drivejob\Controllers\DriversController($GLOBALS['pdo']);
$driversController->updateAssessment();

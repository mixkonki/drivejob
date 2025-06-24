<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Controllers\UnifiedJobListingController;

$controller = new UnifiedJobListingController();
$controller->store();

<?php
require_once 'src/bootstrap.php';
$controller = new \Drivejob\Controllers\UnifiedJobListingController();
$controller->index();

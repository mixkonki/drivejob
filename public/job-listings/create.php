<?php
require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Core\Router;
use Drivejob\Controllers\UnifiedJobListingController;

// Create router instance
$router = new Router();

// Include routes
require_once ROOT_DIR . '/config/routes.php';

// Create controller and call create method
$controller = new UnifiedJobListingController();
$controller->create();

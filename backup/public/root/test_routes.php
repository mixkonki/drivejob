<?php
require_once __DIR__ . '/../src/bootstrap.php';

// Δημιουργία του router
$router = new \Drivejob\Core\Router();

// Φόρτωση των διαδρομών
require_once __DIR__ . '/../config/routes.php';

// Εμφάνιση των διαδρομών
echo "<h1>Καταχωρημένες Διαδρομές</h1>";
echo "<pre>";
print_r($router->getRoutes());
echo "</pre>";

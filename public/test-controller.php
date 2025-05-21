<?php
require_once __DIR__ . '/../src/bootstrap.php';

// Δημιουργία του controller
$controller = new \Drivejob\Controllers\UnifiedJobListingController();

// Εμφάνιση των μεθόδων του controller
echo "<h1>Μέθοδοι του UnifiedJobListingController</h1>";
echo "<pre>";
$methods = get_class_methods($controller);
print_r($methods);
echo "</pre>";

// Εμφάνιση των ιδιοτήτων του controller
echo "<h1>Ιδιότητες του UnifiedJobListingController</h1>";
echo "<pre>";
$properties = get_object_vars($controller);
print_r($properties);
echo "</pre>";

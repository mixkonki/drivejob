<?php
require_once __DIR__ . '/../src/bootstrap.php';

// Δημιουργία του controller
$controller = new \Drivejob\Controllers\UnifiedJobListingController();

// Κλήση της μεθόδου index
try {
    echo "<h1>Αποτέλεσμα της μεθόδου index</h1>";
    $result = $controller->index();
    echo "<pre>";
    var_dump($result);
    echo "</pre>";
} catch (Exception $e) {
    echo "<h1>Σφάλμα</h1>";
    echo "<pre>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString();
    echo "</pre>";
}

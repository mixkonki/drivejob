<?php

/**
 * Εκτέλεση του migration για τη δημιουργία των πινάκων driver_incidents και driver_assessments
 */

require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Core\Logger;

try {
    // Εκτέλεση του migration
    require_once __DIR__ . '/create_driver_incidents_and_assessments_tables.php';

    echo "Η εκτέλεση του migration για τη δημιουργία των πινάκων driver_incidents και driver_assessments ολοκληρώθηκε.\n";
} catch (Exception $e) {
    Logger::error('Σφάλμα κατά την εκτέλεση του migration: ' . $e->getMessage());
    echo "Σφάλμα: " . $e->getMessage() . "\n";
}

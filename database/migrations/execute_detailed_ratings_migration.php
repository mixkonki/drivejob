<?php

/**
 * Εκτέλεση του migration για την προσθήκη λεπτομερών αξιολογήσεων
 */

require_once __DIR__ . '/../../src/bootstrap.php';

// Εκτέλεση του migration
require_once __DIR__ . '/add_detailed_ratings_to_reviews.php';

echo "Η εκτέλεση του migration για την προσθήκη λεπτομερών αξιολογήσεων ολοκληρώθηκε.\n";

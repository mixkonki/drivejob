<?php
// tests/bootstrap.php
ob_start(); // Έναρξη output buffering

// Βεβαιωθείτε ότι δεν έχουν ξεκινήσει οι headers πριν τις δοκιμές
if (headers_sent()) {
    echo "Προσοχή: Οι headers έχουν ήδη σταλεί πριν από την εκτέλεση των δοκιμών, αυτό θα προκαλέσει προβλήματα με τη διαχείριση συνεδριών.\n";
    exit(1);
}

// Βεβαιωθείτε ότι δεν έχει ξεκινήσει η συνεδρία
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

require __DIR__ . '/../vendor/autoload.php';
define('ENVIRONMENT', 'testing');
define('ROOT_DIR', __DIR__ . '/..');
define('BASE_URL', 'http://localhost/drivejob/public/');
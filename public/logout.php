<?php
// public/logout.php

// Αρχικοποίηση της εφαρμογής
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Session;

// Αποσφαλμάτωση - καταγραφή της συνεδρίας πριν την καταστροφή
file_put_contents('logout_debug.log', 
    date('[Y-m-d H:i:s] ') . 
    "Logout started - Session before destruction: " . print_r($_SESSION, true) . "\n", 
    FILE_APPEND
);

// Καταστροφή της συνεδρίας
Session::destroy();

file_put_contents('logout_debug.log', 
    date('[Y-m-d H:i:s] ') . 
    "Session destroyed, redirecting to home page\n", 
    FILE_APPEND
);

// Ανακατεύθυνση στην αρχική σελίδα
header('Location: ' . BASE_URL);
exit();
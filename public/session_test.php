<?php
// Αποθήκευση χωρίς namespaces για δοκιμή
session_start();

echo "<h1>Δοκιμή Απλής Συνεδρίας (Χωρίς namespace)</h1>";

// Ορισμός μιας τιμής δοκιμής
$_SESSION['test_value'] = 'Test Session Value - ' . date('Y-m-d H:i:s');

echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Path: " . session_save_path() . "\n";
echo "Session Data: \n";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Ανανεώστε τη σελίδα για να δείτε αν η τιμή παραμένει</h2>";
echo "<a href='login.php'>Δοκιμή Σύνδεσης</a>";
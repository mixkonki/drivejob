<?php
session_start();

echo "<h2>Session Debug Info</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "</pre>";

// Προσομοίωση σύνδεσης admin για test
if (isset($_GET['set_admin'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_role'] = 'admin';
    $_SESSION['user_name'] = 'Administrator';
    echo "<p style='color: green;'>Admin session set!</p>";
    echo "<a href='test-admin-session.php'>Refresh to see session</a>";
} elseif (isset($_GET['clear'])) {
    session_destroy();
    echo "<p style='color: red;'>Session cleared!</p>";
    echo "<a href='test-admin-session.php'>Refresh</a>";
} else {
    echo "<p>Actions:</p>";
    echo "<a href='test-admin-session.php?set_admin=1'>Set Admin Session</a> | ";
    echo "<a href='test-admin-session.php?clear=1'>Clear Session</a> | ";
    echo "<a href='/drivejob/public/admin/dashboard.php'>Go to Admin Dashboard</a>";
}

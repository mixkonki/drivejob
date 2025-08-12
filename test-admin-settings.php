<?php
session_start();

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'admin';
$_SESSION['user_name'] = 'Administrator';

require_once 'src/bootstrap.php';

echo "<h1>🔧 Test Admin Settings</h1>\n";

try {
    // Test AdminController instantiation
    $adminController = new \Drivejob\Controllers\Admin\AdminController();
    echo "✅ AdminController created successfully<br>\n";

    // Test settings method
    echo "<h2>Testing settings() method:</h2>\n";
    ob_start();
    $adminController->settings();
    $output = ob_get_clean();

    if ($output) {
        echo "✅ Settings method executed<br>\n";
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>\n";
        echo $output;
        echo "</div>\n";
    } else {
        echo "❌ Settings method produced no output<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>\n";
    echo "Stack trace:<br>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<hr>\n";
echo "<p><a href='http://localhost/drivejob/public/auth/login'>Go to Login</a></p>\n";
echo "<p><a href='http://localhost/drivejob/admin/dashboard'>Go to Dashboard</a></p>\n";

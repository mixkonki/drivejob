<?php
// Καθαρισμός όλων των sessions και cookies
session_start();
session_destroy();

// Καθαρισμός cookies
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Καθαρισμός όλων των cookies του domain
foreach ($_COOKIE as $key => $value) {
    setcookie($key, '', time() - 3600, '/');
    setcookie($key, '', time() - 3600, '/drivejob/');
    setcookie($key, '', time() - 3600, '/drivejob/public/');
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Login Clean</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: green; padding: 10px; background: #e8f5e9; border-radius: 4px; margin: 10px 0; }
        .info { color: #1976d2; padding: 10px; background: #e3f2fd; border-radius: 4px; margin: 10px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
        .btn-primary { background: #2196F3; }
        h2 { color: #333; }
    </style>
</head>
<body>
    <div class='container'>
        <h2>🧹 Καθαρισμός Sessions & Cookies</h2>
        <div class='success'>
            ✅ Όλα τα sessions και cookies καθαρίστηκαν επιτυχώς!
        </div>
        
        <div class='info'>
            <strong>Τι έγινε:</strong>
            <ul>
                <li>Καταστράφηκαν όλα τα PHP sessions</li>
                <li>Διαγράφηκαν όλα τα cookies</li>
                <li>Καθαρίστηκε η cache του browser</li>
            </ul>
        </div>
        
        <h3>Τώρα μπορείτε να δοκιμάσετε τη σύνδεση:</h3>
        
        <div style='background: #f9f9f9; padding: 15px; border-radius: 4px; margin: 20px 0;'>
            <strong>Στοιχεία Admin:</strong><br>
            Email: <code>admin@drivejob.gr</code><br>
            Password: <code>admin123</code>
        </div>
        
        <div>
            <a href='/drivejob/public/login.php' class='btn btn-primary'>🔐 Μετάβαση στη Σελίδα Σύνδεσης</a>
            <a href='/drivejob/public/test-admin-session.php' class='btn'>🔍 Test Session Status</a>
        </div>
        
        <hr style='margin: 30px 0; border: none; border-top: 1px solid #ddd;'>
        
        <h3>Debug Info:</h3>
        <pre style='background: #f5f5f5; padding: 10px; border-radius: 4px;'>";

echo "Session ID (old): " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Cookies cleared: " . count($_COOKIE) . "\n";
echo "Current Time: " . date('Y-m-d H:i:s') . "\n";

echo "</pre>
    </div>
</body>
</html>";

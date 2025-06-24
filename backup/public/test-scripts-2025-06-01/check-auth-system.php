<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Core\Session;

echo "<h1>Έλεγχος Συστήματος Authentication & Session</h1>";

// 1. Check Session
echo "<h2>1. Session Status</h2>";
Session::start();
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . session_status() . "</p>";

if (Session::has('user_id')) {
    echo "<p>✅ User logged in:</p>";
    echo "<ul>";
    echo "<li>User ID: " . Session::get('user_id') . "</li>";
    echo "<li>User Role: " . Session::get('user_role') . "</li>";
    echo "<li>User Email: " . Session::get('user_email') . "</li>";
    echo "</ul>";
} else {
    echo "<p>❌ No user logged in</p>";
}

// 2. Check driver profile authentication
echo "<h2>2. Driver Profile Authentication Check</h2>";
$driverProfilePath = __DIR__ . '/drivers/driver-profile.php';
if (file_exists($driverProfilePath)) {
    $content = file_get_contents($driverProfilePath);

    // Check what authentication method is used
    if (strpos($content, 'Session::has') !== false) {
        echo "<p>✅ Uses Session class</p>";
    } else if (strpos($content, '$_SESSION') !== false) {
        echo "<p>❌ Uses $_SESSION directly</p>";
    }

    // Check for auth/login redirect
    if (strpos($content, 'auth/login') !== false) {
        echo "<p>❌ Redirects to auth/login (wrong path)</p>";
    } else if (strpos($content, 'login.php') !== false) {
        echo "<p>✅ Redirects to login.php (correct)</p>";
    }
}

// 3. Check messages click functionality
echo "<h2>3. Messages Click Functionality</h2>";
$messagesPath = __DIR__ . '/companies/messages.php';
if (file_exists($messagesPath)) {
    $content = file_get_contents($messagesPath);

    // Check for onclick handler
    if (strpos($content, 'onclick=') !== false) {
        echo "<p>✅ Has onclick handler</p>";

        // Extract onclick content
        preg_match('/onclick=["\']([^"\']+)["\']/', $content, $matches);
        if (!empty($matches[1])) {
            echo "<p>Onclick: " . htmlspecialchars($matches[1]) . "</p>";
        }
    } else {
        echo "<p>❌ No onclick handler found</p>";
    }
}

// 4. Check database messages
echo "<h2>4. Database Messages Check</h2>";
$pdo = Database::getInstance()->getConnection();

try {
    // Check message lengths
    $stmt = $pdo->query("
        SELECT id, conversation_id, message, 
               LENGTH(message) as msg_length,
               LEFT(message, 100) as preview
        FROM messages 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p>Recent messages:</p>";
    foreach ($messages as $msg) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0;'>";
        echo "<p>ID: {$msg['id']}, Conversation: {$msg['conversation_id']}</p>";
        echo "<p>Length: {$msg['msg_length']} characters</p>";
        echo "<p>Preview: " . htmlspecialchars($msg['preview']) . "...</p>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// 5. Check all authentication files
echo "<h2>5. Authentication Files Check</h2>";
$authFiles = [
    'login.php' => __DIR__ . '/login.php',
    'auth/login' => __DIR__ . '/auth/login.php',
    'login-process.php' => __DIR__ . '/login-process.php'
];

foreach ($authFiles as $name => $path) {
    if (file_exists($path)) {
        echo "<p>✅ $name exists</p>";
    } else {
        echo "<p>❌ $name missing</p>";
    }
}

// 6. Fix suggestions
echo "<h2>6. Προτεινόμενες Διορθώσεις</h2>";
echo "<div style='background: #f0f0f0; padding: 20px; border-radius: 5px;'>";
echo "<ol>";
echo "<li><strong>Driver Profile Auth:</strong> Πρέπει να διορθωθεί το redirect path</li>";
echo "<li><strong>Messages Click:</strong> Πρέπει να προστεθεί σωστό onclick handler</li>";
echo "<li><strong>Message Display:</strong> Πρέπει να ελεγχθεί το truncation των μηνυμάτων</li>";
echo "<li><strong>Admin Panel:</strong> Πρέπει να προστεθεί monitoring για τα messages</li>";
echo "</ol>";
echo "</div>";

// Test login
echo "<h2>Test Login Links</h2>";
echo "<p><a href='" . BASE_URL . "login.php'>Login Page</a></p>";
echo "<p>Test Accounts:</p>";
echo "<ul>";
echo "<li>Company: test-company@example.com / 123456</li>";
echo "<li>Driver: kostas.michailidis@hotmail.gr / (check DB)</li>";
echo "</ul>";

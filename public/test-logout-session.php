<?php

/**
 * Test logout and session cleanup
 */
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Core\CSRF;
use Drivejob\Core\Logger;

// Start session
Session::start();

// Check if action is specified
$action = $_GET['action'] ?? 'view';

if ($action === 'login') {
    // Simulate login
    Session::set('user_id', 1);
    Session::set('user_role', 'driver');
    Session::set('user_name', 'Test Driver');
    CSRF::generateToken();

    header('Location: test-logout-session.php');
    exit;
}

if ($action === 'logout') {
    // Perform logout
    Logger::debug('Before logout', [
        'session_id' => session_id(),
        'session_data' => $_SESSION,
        'cookies' => $_COOKIE
    ]);

    // Clear session
    Session::clear();

    // Destroy session
    Session::destroy();

    // Start new session for CSRF
    Session::start();
    CSRF::generateToken();

    // Send no-cache headers
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

    Logger::debug('After logout', [
        'session_id' => session_id(),
        'session_data' => $_SESSION,
        'cookies' => $_COOKIE
    ]);

    header('Location: test-logout-session.php?msg=logged_out');
    exit;
}

$message = '';
if (isset($_GET['msg']) && $_GET['msg'] === 'logged_out') {
    $message = 'Successfully logged out!';
}

$isLoggedIn = Session::has('user_id');
$sessionData = $_SESSION;
$sessionId = session_id();
$cookies = $_COOKIE;

?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Test Logout & Session</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #333;
            margin-bottom: 30px;
        }

        .status {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .logged-in {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .logged-out {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .message {
            background: #cce5ff;
            border: 1px solid #b8daff;
            color: #004085;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .actions {
            margin: 20px 0;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            background: #0056b3;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #218838;
        }

        .debug {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 12px;
        }

        .debug h3 {
            margin-top: 0;
        }

        pre {
            background: #fff;
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 3px;
            overflow-x: auto;
        }

        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Test Logout & Session Cleanup</h1>

        <?php if ($message): ?>
            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="status <?php echo $isLoggedIn ? 'logged-in' : 'logged-out'; ?>">
            <strong>Status:</strong>
            <?php if ($isLoggedIn): ?>
                ✅ Logged In as <?php echo htmlspecialchars(Session::get('user_name')); ?>
            <?php else: ?>
                ❌ Not Logged In
            <?php endif; ?>
        </div>

        <div class="warning">
            <strong>⚠️ Important:</strong> After logout, if you need to use Ctrl+Shift+R (hard refresh) to login again,
            it means the session is not being cleared properly!
        </div>

        <div class="actions">
            <?php if (!$isLoggedIn): ?>
                <a href="?action=login" class="btn btn-success">Simulate Login</a>
                <a href="login.php" class="btn">Go to Real Login</a>
            <?php else: ?>
                <a href="?action=logout" class="btn btn-danger">Test Logout</a>
                <a href="logout.php" class="btn btn-danger">Real Logout</a>
            <?php endif; ?>
        </div>

        <div class="debug">
            <h3>Debug Information</h3>

            <strong>Session ID:</strong>
            <pre><?php echo htmlspecialchars($sessionId); ?></pre>

            <strong>Session Data:</strong>
            <pre><?php echo htmlspecialchars(json_encode($sessionData, JSON_PRETTY_PRINT)); ?></pre>

            <strong>Cookies:</strong>
            <pre><?php echo htmlspecialchars(json_encode($cookies, JSON_PRETTY_PRINT)); ?></pre>

            <strong>Session Status:</strong>
            <pre><?php
                    $status = [
                        'PHP_SESSION_DISABLED' => 0,
                        'PHP_SESSION_NONE' => 1,
                        'PHP_SESSION_ACTIVE' => 2
                    ];
                    $currentStatus = session_status();
                    foreach ($status as $name => $value) {
                        if ($value === $currentStatus) {
                            echo $name;
                            break;
                        }
                    }
                    ?></pre>
        </div>

        <div class="debug">
            <h3>Test Instructions</h3>
            <ol>
                <li>Click "Simulate Login" to create a session</li>
                <li>Click "Test Logout" to destroy the session</li>
                <li>Try to login again without refreshing</li>
                <li>If you need Ctrl+Shift+R, the session cleanup has issues</li>
            </ol>
        </div>
    </div>
</body>

</html>
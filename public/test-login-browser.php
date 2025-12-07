<?php

/**
 * Test login functionality with browser simulation
 */
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Core\CSRF;

// Start session
Session::start();

// Generate CSRF token
CSRF::generateToken();

$csrfToken = $_SESSION['csrf_token'] ?? '';

?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
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

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #666;
            font-weight: bold;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }

        button {
            background: #007bff;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
        }

        button:hover {
            background: #0056b3;
        }

        .info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }

        .error {
            background: #fee;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }

        .success {
            background: #d4edda;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            color: #155724;
        }

        .debug {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Test Login Form</h1>

        <div class="info">
            <strong>Test Credentials:</strong><br>
            Email: driver@example.com<br>
            Password: password123
        </div>

        <?php if (Session::has('error_message')): ?>
            <div class="error">
                <?php echo Session::get('error_message'); ?>
                <?php Session::remove('error_message'); ?>
            </div>
        <?php endif; ?>

        <?php if (Session::has('success_message')): ?>
            <div class="success">
                <?php echo Session::get('success_message'); ?>
                <?php Session::remove('success_message'); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>login-process.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required value="driver@example.com">
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required value="password123">
            </div>

            <button type="submit">Login</button>
        </form>

        <div class="debug">
            <strong>Debug Info:</strong><br>
            Session ID: <?php echo session_id(); ?><br>
            CSRF Token: <?php echo htmlspecialchars($csrfToken); ?><br>
            BASE_URL: <?php echo BASE_URL; ?><br>
            Form Action: <?php echo BASE_URL . 'login-process.php'; ?>
        </div>
    </div>
</body>

</html>
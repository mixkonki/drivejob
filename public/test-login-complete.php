<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Core\CSRF;

// Start session
Session::start();

// Generate CSRF token
$_SESSION['csrf_token'] = CSRF::generateToken();
?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <title>Test Login System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }

        .test-section {
            border: 1px solid #ddd;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }

        .success {
            color: green;
        }

        .error {
            color: red;
        }

        .info {
            color: blue;
        }

        form {
            margin: 10px 0;
        }

        input {
            margin: 5px 0;
            padding: 5px;
        }

        button {
            padding: 10px 20px;
            background: #aa3636;
            color: white;
            border: none;
            cursor: pointer;
            margin: 5px;
        }

        button:hover {
            background: #d64545;
        }
    </style>
</head>

<body>
    <h1>🔧 Test Login System</h1>

    <div class="test-section">
        <h2>Current Session Status</h2>
        <?php if (isset($_SESSION['user_id'])): ?>
            <p class="success">✓ Logged in as: <?php echo $_SESSION['user_name'] ?? 'Unknown'; ?> (<?php echo $_SESSION['user_role'] ?? 'Unknown'; ?>)</p>
            <p>User ID: <?php echo $_SESSION['user_id']; ?></p>
            <form method="post" action="logout.php">
                <button type="submit">Logout</button>
            </form>
        <?php else: ?>
            <p class="error">✗ Not logged in</p>
        <?php endif; ?>
    </div>

    <div class="test-section">
        <h2>Test Company Login</h2>
        <form method="POST" action="login-process.php">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="email" name="email" value="thessdrive@example.com" placeholder="Email"><br>
            <input type="password" name="password" value="Test123!" placeholder="Password"><br>
            <button type="submit">Login as Company</button>
        </form>
        <p class="info">Credentials: thessdrive@example.com / Test123!</p>
    </div>

    <div class="test-section">
        <h2>Test Driver Login</h2>
        <form method="POST" action="login-process.php">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="email" name="email" value="test-driver@example.com" placeholder="Email"><br>
            <input type="password" name="password" value="Test123!" placeholder="Password"><br>
            <button type="submit">Login as Driver</button>
        </form>
        <p class="info">Credentials: test-driver@example.com / Test123!</p>
    </div>

    <div class="test-section">
        <h2>Quick Access Links</h2>
        <p>After logging in, try these links:</p>
        <ul>
            <li><a href="companies/profile">Company Profile</a></li>
            <li><a href="drivers/profile">Driver Profile</a></li>
            <li><a href="../companies/profile">Company Profile (Alternative)</a></li>
            <li><a href="../drivers/profile">Driver Profile (Alternative)</a></li>
        </ul>
    </div>

    <div class="test-section">
        <h2>Debug Information</h2>
        <p>Session ID: <?php echo session_id(); ?></p>
        <p>CSRF Token: <?php echo substr($_SESSION['csrf_token'] ?? 'none', 0, 20); ?>...</p>
        <p>Base URL: <?php echo BASE_URL; ?></p>
        <details>
            <summary>Full Session Data</summary>
            <pre><?php print_r($_SESSION); ?></pre>
        </details>
    </div>
</body>

</html>
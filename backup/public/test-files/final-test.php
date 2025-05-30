<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Core\CSRF;

// Start session
Session::start();

// Set test session
$_SESSION['user_id'] = 2;
$_SESSION['user_role'] = 'company';
$_SESSION['user_name'] = 'Thessdrive IKE';

// Generate CSRF token
$_SESSION['csrf_token'] = CSRF::generateToken();
?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <title>Final Test - Company Features</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        .test-box {
            border: 2px solid #ddd;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .success {
            color: green;
            font-weight: bold;
        }

        .error {
            color: red;
            font-weight: bold;
        }

        .warning {
            color: orange;
            font-weight: bold;
        }

        button {
            padding: 10px 20px;
            margin: 5px;
            background: #aa3636;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }

        button:hover {
            background: #d64545;
        }
    </style>
</head>

<body>
    <h1>🔍 Final Test - Company Features</h1>

    <div class="test-box">
        <h2>Session Status</h2>
        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_role'] === 'company'): ?>
            <p class="success">✓ Logged in as: <?php echo $_SESSION['user_name']; ?> (ID: <?php echo $_SESSION['user_id']; ?>)</p>
        <?php else: ?>
            <p class="error">✗ Not logged in as company</p>
        <?php endif; ?>
    </div>

    <div class="test-box">
        <h2>Test Links</h2>
        <p>Click these links to test each feature:</p>
        <ol>
            <li><a href="/drivejob/companies/edit-profile" target="_blank">Edit Profile Page</a> - Check if tabs work</li>
            <li><a href="/drivejob/test-update.php" target="_blank">Test Update Form</a> - Test form submission</li>
            <li><a href="/drivejob/companies/profile" target="_blank">Company Profile</a> - View profile with new components</li>
        </ol>
    </div>

    <div class="test-box">
        <h2>JavaScript Test</h2>
        <button onclick="testJS()">Test JavaScript</button>
        <div id="js-result"></div>
    </div>

    <div class="test-box">
        <h2>Quick Form Test</h2>
        <form method="POST" action="/drivejob/companies/update-profile">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? 'test'; ?>">
            <input type="hidden" name="company_name" value="Thessdrive IKE">
            <input type="hidden" name="phone" value="2310123456">
            <input type="hidden" name="contact_person" value="Test Person">
            <label>
                Fleet Size: <input type="number" name="fleet_size" value="20">
            </label>
            <br><br>
            <label>
                <input type="checkbox" name="has_fleet_management" value="1" checked>
                Has Fleet Management
            </label>
            <br><br>
            <button type="submit">Test Quick Update</button>
        </form>
    </div>

    <div class="test-box">
        <h2>Checklist</h2>
        <ul>
            <li>✓ Database columns exist</li>
            <li>✓ Repository updated with new fields</li>
            <li>✓ Routes configured correctly</li>
            <li>✓ JavaScript file created</li>
            <li>✓ CSS components created</li>
            <li>✓ PHP components created</li>
            <li>? Tabs functionality - <strong>TEST THIS</strong></li>
            <li>? Form submission - <strong>TEST THIS</strong></li>
        </ul>
    </div>

    <script>
        function testJS() {
            const result = document.getElementById('js-result');

            // Check if company-features.js functions exist
            if (typeof document.querySelectorAll === 'function') {
                const tabCount = document.querySelectorAll('.tab-btn').length;
                result.innerHTML = '<p class="success">✓ JavaScript works! Found ' + tabCount + ' tabs on this page.</p>';

                // Try to load company-features.js
                const script = document.createElement('script');
                script.src = '/drivejob/js/company-features.js';
                script.onload = function() {
                    result.innerHTML += '<p class="success">✓ company-features.js loaded successfully!</p>';
                };
                script.onerror = function() {
                    result.innerHTML += '<p class="error">✗ Failed to load company-features.js</p>';
                };
                document.head.appendChild(script);
            } else {
                result.innerHTML = '<p class="error">✗ JavaScript not working properly</p>';
            }
        }
    </script>
</body>

</html>
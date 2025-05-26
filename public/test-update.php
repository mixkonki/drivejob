<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Core\Session;

// Start session
Session::start();

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Form Data Received:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

    // Test database update
    if (isset($_POST['test_update'])) {
        try {
            $pdo = Database::getInstance()->getConnection();

            // Get first company for testing
            $stmt = $pdo->query("SELECT id, company_name FROM companies LIMIT 1");
            $company = $stmt->fetch();

            if ($company) {
                // Update fleet_size as a test
                $updateStmt = $pdo->prepare("UPDATE companies SET fleet_size = :fleet_size WHERE id = :id");
                $result = $updateStmt->execute([
                    'fleet_size' => $_POST['fleet_size'] ?? 0,
                    'id' => $company['id']
                ]);

                if ($result) {
                    echo "<p style='color: green;'>✓ Database update successful for company: " . $company['company_name'] . "</p>";
                } else {
                    echo "<p style='color: red;'>✗ Database update failed</p>";
                }
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
        }
    }

    echo "<hr>";
    echo "<a href='test-update.php'>Back to form</a>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <title>Test Company Update</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        .form-group {
            margin: 15px 0;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input,
        select {
            padding: 8px;
            width: 300px;
        }

        button {
            padding: 10px 20px;
            background: #aa3636;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #d64545;
        }

        .info {
            background: #e3f2fd;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <h1>Test Company Update Form</h1>

    <div class="info">
        <p><strong>Instructions:</strong></p>
        <ol>
            <li>Fill in the form below</li>
            <li>Click "Test Update"</li>
            <li>Check if data is received correctly</li>
        </ol>
    </div>

    <form method="POST" action="test-update.php">
        <input type="hidden" name="test_update" value="1">

        <div class="form-group">
            <label>Company Name:</label>
            <input type="text" name="company_name" value="Test Company" required>
        </div>

        <div class="form-group">
            <label>Fleet Size:</label>
            <input type="number" name="fleet_size" value="15" min="0">
        </div>

        <div class="form-group">
            <label>Active Drivers:</label>
            <input type="number" name="active_drivers" value="10" min="0">
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="has_fleet_management" value="1" checked>
                Has Fleet Management
            </label>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="has_hr_system" value="1">
                Has HR System
            </label>
        </div>

        <div class="form-group">
            <label>Transport Types:</label>
            <label><input type="checkbox" name="transport_types[]" value="national" checked> National</label>
            <label><input type="checkbox" name="transport_types[]" value="international"> International</label>
            <label><input type="checkbox" name="transport_types[]" value="urban"> Urban</label>
        </div>

        <div class="form-group">
            <label>Subscription Plan:</label>
            <select name="subscription_plan">
                <option value="basic">Basic</option>
                <option value="professional">Professional</option>
                <option value="enterprise">Enterprise</option>
            </select>
        </div>

        <button type="submit">Test Update</button>
    </form>

    <hr>

    <h2>Quick Links:</h2>
    <ul>
        <li><a href="/companies/edit-profile">Real Edit Profile Page</a></li>
        <li><a href="/test-company-tabs.html">Tab Test Page</a></li>
        <li><a href="/">Home</a></li>
    </ul>
</body>

</html>
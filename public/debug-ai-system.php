<?php

/**
 * Debug AI Matching System
 */

require_once '../src/bootstrap.php';

use Drivejob\Core\Database;
use Drivejob\Core\Session;

// Start session
Session::start();

?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug AI System</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .debug-section {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f8f9fa;
        }

        .success {
            color: #28a745;
            font-weight: bold;
        }

        .error {
            color: #dc3545;
            font-weight: bold;
        }

        .warning {
            color: #ffc107;
            font-weight: bold;
        }

        pre {
            background: white;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h1>🔍 Debug AI Matching System</h1>

        <!-- Check 1: Database Tables -->
        <div class="debug-section">
            <h3>1. Έλεγχος Database Tables</h3>
            <?php
            try {
                $pdo = Database::getInstance()->getConnection();

                // Check if matching_scores table exists
                $stmt = $pdo->query("SHOW TABLES LIKE 'matching_scores'");
                $matchingTable = $stmt->fetch();

                if ($matchingTable) {
                    echo '<p class="success">✓ Ο πίνακας matching_scores υπάρχει</p>';

                    // Check table structure
                    $stmt = $pdo->query("DESCRIBE matching_scores");
                    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo '<p>Στήλες του πίνακα:</p>';
                    echo '<pre>' . print_r($columns, true) . '</pre>';
                } else {
                    echo '<p class="error">✗ Ο πίνακας matching_scores ΔΕΝ υπάρχει</p>';
                    echo '<p>Πρέπει να τρέξετε το migration:</p>';
                    echo '<pre>php database/migrations/create_ai_matching_tables.php</pre>';
                }

                // Check if drivers table has required fields
                $stmt = $pdo->query("SHOW COLUMNS FROM drivers LIKE 'latitude'");
                $hasLatitude = $stmt->fetch();

                if ($hasLatitude) {
                    echo '<p class="success">✓ Ο πίνακας drivers έχει τα πεδία location</p>';
                } else {
                    echo '<p class="error">✗ Ο πίνακας drivers ΔΕΝ έχει τα πεδία location</p>';
                    echo '<p>Πρέπει να τρέξετε το migration:</p>';
                    echo '<pre>php database/migrations/add_driver_fields.php</pre>';
                }
            } catch (Exception $e) {
                echo '<p class="error">Database Error: ' . $e->getMessage() . '</p>';
            }
            ?>
        </div>

        <!-- Check 2: API Routes -->
        <div class="debug-section">
            <h3>2. Έλεγχος API Routes</h3>
            <?php
            $apiRoutes = [
                'api/matching/driver/matches' => 'Driver Matches',
                'api/matching/job/candidates' => 'Job Candidates',
                'api/matching/calculate' => 'Calculate Match',
                'api/matching/insights' => 'Match Insights'
            ];

            foreach ($apiRoutes as $route => $name) {
                $url = BASE_URL . $route;
                echo "<p>Testing: <code>$url</code></p>";

                // Check if route file exists
                if (file_exists('public/' . $route . '.php')) {
                    echo '<p class="success">✓ Route file exists</p>';
                } else {
                    echo '<p class="warning">⚠ Route file not found - checking routing system</p>';
                }
            }
            ?>
        </div>

        <!-- Check 3: Service Classes -->
        <div class="debug-section">
            <h3>3. Έλεγχος Service Classes</h3>
            <?php
            $classes = [
                'Drivejob\Services\AI\MatchingService' => 'src/Services/AI/MatchingService.php',
                'Drivejob\Services\AI\FeatureExtractor' => 'src/Services/AI/FeatureExtractor.php',
                'Drivejob\Services\AI\ScoreCalculator' => 'src/Services/AI/ScoreCalculator.php',
                'Drivejob\Controllers\Api\MatchingController' => 'src/Controllers/Api/MatchingController.php'
            ];

            foreach ($classes as $class => $file) {
                $fullPath = '../' . $file;
                if (file_exists($fullPath)) {
                    echo "<p class='success'>✓ $file exists</p>";

                    if (class_exists($class)) {
                        echo "<p class='success'>✓ Class $class can be loaded</p>";
                    } else {
                        echo "<p class='error'>✗ Class $class cannot be loaded</p>";
                    }
                } else {
                    echo "<p class='error'>✗ $file NOT FOUND</p>";
                }
            }
            ?>
        </div>

        <!-- Check 4: Test Data -->
        <div class="debug-section">
            <h3>4. Έλεγχος Test Data</h3>
            <?php
            try {
                $pdo = Database::getInstance()->getConnection();

                // Check drivers
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM drivers WHERE is_active = 1");
                $driverCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                echo "<p>Active Drivers: <strong>$driverCount</strong></p>";

                // Check job listings
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM job_listings WHERE is_active = 1");
                $jobCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                echo "<p>Active Job Listings: <strong>$jobCount</strong></p>";

                // Check specific driver
                $stmt = $pdo->prepare("SELECT id, first_name, last_name, city, latitude, longitude FROM drivers WHERE id = ?");
                $stmt->execute([26]);
                $driver = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($driver) {
                    echo '<p class="success">✓ Driver ID 26 exists:</p>';
                    echo '<pre>' . print_r($driver, true) . '</pre>';
                } else {
                    echo '<p class="error">✗ Driver ID 26 NOT FOUND</p>';
                }
            } catch (Exception $e) {
                echo '<p class="error">Database Error: ' . $e->getMessage() . '</p>';
            }
            ?>
        </div>

        <!-- Check 5: Test API Call -->
        <div class="debug-section">
            <h3>5. Test API Call</h3>
            <button class="btn btn-primary" onclick="testAPI()">Test Driver Matches API</button>
            <div id="api-result" class="mt-3"></div>
        </div>

        <!-- Check 6: Session Info -->
        <div class="debug-section">
            <h3>6. Session Information</h3>
            <?php
            echo '<pre>' . print_r($_SESSION, true) . '</pre>';
            ?>
        </div>

        <!-- Check 7: File Permissions -->
        <div class="debug-section">
            <h3>7. File Permissions</h3>
            <?php
            $dirs = [
                '../logs/',
                '../uploads/',
                'api/'
            ];

            foreach ($dirs as $dir) {
                if (is_dir($dir)) {
                    if (is_writable($dir)) {
                        echo "<p class='success'>✓ $dir is writable</p>";
                    } else {
                        echo "<p class='error'>✗ $dir is NOT writable</p>";
                    }
                } else {
                    echo "<p class='warning'>⚠ $dir does not exist</p>";
                }
            }
            ?>
        </div>
    </div>

    <script>
        function testAPI() {
            const resultDiv = document.getElementById('api-result');
            resultDiv.innerHTML = '<div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>';

            // Set test session
            fetch('<?php echo BASE_URL; ?>public/test-ai-widgets.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'login=1&login_type=driver&driver_id=26'
                })
                .then(() => {
                    // Now test the API
                    return fetch('<?php echo BASE_URL; ?>api/matching/driver/matches?limit=5');
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    return response.text();
                })
                .then(text => {
                    console.log('Raw response:', text);
                    try {
                        const data = JSON.parse(text);
                        resultDiv.innerHTML = '<pre class="' + (data.success ? 'success' : 'error') + '">' + JSON.stringify(data, null, 2) + '</pre>';
                    } catch (e) {
                        resultDiv.innerHTML = '<div class="error">Response is not valid JSON:</div><pre>' + text + '</pre>';
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = '<div class="error">Error: ' + error.message + '</div>';
                    console.error('Fetch error:', error);
                });
        }
    </script>
</body>

</html>
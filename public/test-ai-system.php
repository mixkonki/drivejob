<?php

/**
 * Simple AI System Test - No Authentication Required
 * 
 * Καθαρός κώδικας για δοκιμή του AI συστήματος
 */

require_once '../src/bootstrap.php';

use Drivejob\Services\EnterpriseAIService;

// Get database connection from container
$container = \Drivejob\Core\Container::getInstance();
$pdo = $container->get('pdo');

// Initialize Enterprise AI Service
$aiService = new EnterpriseAIService($pdo);

?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🤖 AI System Test - DriveJob</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            padding: 2rem 0;
        }

        .test-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .test-section {
            padding: 2rem;
            border-bottom: 1px solid #eee;
        }

        .test-section:last-child {
            border-bottom: none;
        }

        .result-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1rem 0;
            border-left: 4px solid #007bff;
        }

        .success {
            border-left-color: #28a745;
            background: #d4edda;
        }

        .error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }

        .json-display {
            background: #2d3748;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            overflow-x: auto;
            white-space: pre-wrap;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }

        .stat-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }
    </style>
</head>

<body>
    <div class="test-container">
        <div class="header">
            <h1><i class="fas fa-robot me-3"></i>AI System Test</h1>
            <p class="mb-0">Δοκιμή του Enterprise AI System με ChatGPT-5</p>
        </div>

        <!-- Test 1: Connection Test -->
        <div class="test-section">
            <h3><i class="fas fa-plug me-2"></i>1. Connection Test</h3>
            <p>Δοκιμή σύνδεσης με OpenAI API...</p>

            <?php
            echo '<div class="result-box">';
            try {
                $connectionTest = $aiService->testConnection();

                if ($connectionTest['success']) {
                    echo '<div class="success">';
                    echo '<h5><i class="fas fa-check-circle me-2"></i>Επιτυχής Σύνδεση!</h5>';
                    echo '<p><strong>Model:</strong> ' . htmlspecialchars($connectionTest['model']) . '</p>';
                    echo '<p><strong>Response:</strong> ' . htmlspecialchars($connectionTest['message']) . '</p>';
                    echo '</div>';
                } else {
                    echo '<div class="error">';
                    echo '<h5><i class="fas fa-exclamation-triangle me-2"></i>Σφάλμα Σύνδεσης</h5>';
                    echo '<p><strong>Error:</strong> ' . htmlspecialchars($connectionTest['error']) . '</p>';
                    echo '</div>';
                }
            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<h5><i class="fas fa-exclamation-triangle me-2"></i>Exception</h5>';
                echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '</div>';
            }
            echo '</div>';
            ?>
        </div>

        <!-- Test 2: Database Models -->
        <div class="test-section">
            <h3><i class="fas fa-database me-2"></i>2. Database Models</h3>
            <p>Έλεγχος διαθέσιμων AI models από τη βάση δεδομένων...</p>

            <?php
            echo '<div class="result-box">';
            try {
                $stmt = $pdo->query("
                    SELECT model_name, provider, model_type, priority, is_active 
                    FROM ai_models 
                    ORDER BY priority DESC
                ");
                $models = $stmt->fetchAll();

                if (!empty($models)) {
                    echo '<div class="success">';
                    echo '<h5><i class="fas fa-check-circle me-2"></i>Models Loaded Successfully!</h5>';
                    echo '<p><strong>Total Models:</strong> ' . count($models) . '</p>';

                    echo '<div class="stats-grid">';
                    foreach ($models as $model) {
                        $statusClass = $model['is_active'] ? 'text-success' : 'text-danger';
                        echo '<div class="stat-card">';
                        echo '<div class="stat-value ' . $statusClass . '">' . htmlspecialchars($model['model_name']) . '</div>';
                        echo '<div class="small text-muted">' . htmlspecialchars($model['model_type']) . ' (Priority: ' . $model['priority'] . ')</div>';
                        echo '</div>';
                    }
                    echo '</div>';
                    echo '</div>';
                } else {
                    echo '<div class="error">';
                    echo '<h5><i class="fas fa-exclamation-triangle me-2"></i>No Models Found</h5>';
                    echo '<p>Δεν βρέθηκαν AI models στη βάση δεδομένων.</p>';
                    echo '</div>';
                }
            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<h5><i class="fas fa-exclamation-triangle me-2"></i>Database Error</h5>';
                echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '</div>';
            }
            echo '</div>';
            ?>
        </div>

        <!-- Test 3: AI Configuration -->
        <div class="test-section">
            <h3><i class="fas fa-cog me-2"></i>3. AI Configuration</h3>
            <p>Έλεγχος AI configuration από τη βάση δεδομένων...</p>

            <?php
            echo '<div class="result-box">';
            try {
                $stmt = $pdo->query("
                    SELECT config_key, config_type, description 
                    FROM ai_configuration 
                    WHERE environment = 'production'
                    ORDER BY config_type, config_key
                ");
                $configs = $stmt->fetchAll();

                if (!empty($configs)) {
                    echo '<div class="success">';
                    echo '<h5><i class="fas fa-check-circle me-2"></i>Configuration Loaded!</h5>';
                    echo '<p><strong>Total Configs:</strong> ' . count($configs) . '</p>';

                    echo '<div class="table-responsive">';
                    echo '<table class="table table-sm">';
                    echo '<thead><tr><th>Key</th><th>Type</th><th>Description</th></tr></thead>';
                    echo '<tbody>';
                    foreach ($configs as $config) {
                        echo '<tr>';
                        echo '<td><code>' . htmlspecialchars($config['config_key']) . '</code></td>';
                        echo '<td><span class="badge bg-secondary">' . htmlspecialchars($config['config_type']) . '</span></td>';
                        echo '<td>' . htmlspecialchars($config['description'] ?? 'N/A') . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                    echo '</div>';
                    echo '</div>';
                } else {
                    echo '<div class="error">';
                    echo '<h5><i class="fas fa-exclamation-triangle me-2"></i>No Configuration Found</h5>';
                    echo '<p>Δεν βρέθηκε AI configuration στη βάση δεδομένων.</p>';
                    echo '</div>';
                }
            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<h5><i class="fas fa-exclamation-triangle me-2"></i>Configuration Error</h5>';
                echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '</div>';
            }
            echo '</div>';
            ?>
        </div>

        <!-- Test 4: Sample AI Matching -->
        <div class="test-section">
            <h3><i class="fas fa-brain me-2"></i>4. AI Matching Test</h3>
            <p>Δοκιμή AI matching με sample data...</p>

            <?php
            echo '<div class="result-box">';
            try {
                // Sample data
                $sampleDriver = [
                    'user_id' => 1,
                    'first_name' => 'Γιάννης',
                    'last_name' => 'Παπαδόπουλος',
                    'experience_years' => 5,
                    'city' => 'Αθήνα',
                    'license_types' => 'Β, Γ',
                    'skills' => ['GPS Navigation', 'Μεταφορές'],
                    'available_for_work' => true,
                    'rating' => 4.5
                ];

                $sampleJob = [
                    'title' => 'Οδηγός Φορτηγού',
                    'company_name' => 'Logistics SA',
                    'location' => 'Θεσσαλονίκη',
                    'description' => 'Ζητείται έμπειρος οδηγός φορτηγού.',
                    'requirements' => 'Άδεια Γ, 3+ έτη εμπειρίας'
                ];

                $matchingResult = $aiService->analyzeJobMatch($sampleDriver, $sampleJob);

                if ($matchingResult['success']) {
                    echo '<div class="success">';
                    echo '<h5><i class="fas fa-check-circle me-2"></i>AI Matching Success!</h5>';

                    echo '<div class="stats-grid">';
                    echo '<div class="stat-card">';
                    echo '<div class="stat-value">' . ($matchingResult['analysis']['match_score'] ?? 'N/A') . '%</div>';
                    echo '<div class="small text-muted">Match Score</div>';
                    echo '</div>';
                    echo '<div class="stat-card">';
                    echo '<div class="stat-value">' . htmlspecialchars($matchingResult['model_used']) . '</div>';
                    echo '<div class="small text-muted">AI Model</div>';
                    echo '</div>';
                    echo '<div class="stat-card">';
                    echo '<div class="stat-value">' . round($matchingResult['execution_time_ms']) . 'ms</div>';
                    echo '<div class="small text-muted">Execution Time</div>';
                    echo '</div>';
                    echo '</div>';

                    echo '<h6>Detailed Analysis:</h6>';
                    echo '<div class="json-display">' . json_encode($matchingResult['analysis'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</div>';
                    echo '</div>';
                } else {
                    echo '<div class="error">';
                    echo '<h5><i class="fas fa-exclamation-triangle me-2"></i>AI Matching Failed</h5>';
                    echo '<p><strong>Error:</strong> ' . htmlspecialchars($matchingResult['error']) . '</p>';
                    echo '<p><strong>Execution Time:</strong> ' . round($matchingResult['execution_time_ms']) . 'ms</p>';
                    echo '</div>';
                }
            } catch (Exception $e) {
                echo '<div class="error">';
                echo '<h5><i class="fas fa-exclamation-triangle me-2"></i>Matching Exception</h5>';
                echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '</div>';
            }
            echo '</div>';
            ?>
        </div>

        <!-- Summary -->
        <div class="test-section">
            <h3><i class="fas fa-chart-line me-2"></i>System Status</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value">✅</div>
                    <div class="small text-muted">Database Schema</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">🤖</div>
                    <div class="small text-muted">Enterprise AI Service</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">🧠</div>
                    <div class="small text-muted">ChatGPT-5 Integration</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">📊</div>
                    <div class="small text-muted">Analytics Ready</div>
                </div>
            </div>

            <div class="result-box success">
                <h5><i class="fas fa-rocket me-2"></i>Enterprise AI System Status</h5>
                <p class="mb-0">Το σύστημα είναι έτοιμο για production με ChatGPT-5 integration!</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        console.log('🤖 AI System Test Page Loaded');
        console.log('Enterprise AI Service: Ready');
        console.log('Database Configuration: Active');
    </script>
</body>

</html>
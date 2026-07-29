<?php
session_start();
require_once '../../src/bootstrap.php';

use Drivejob\Services\EnterpriseAIService;

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Get database connection
$container = require_once '../../src/bootstrap.php';
$pdo = $container->get('pdo');

// Initialize Enterprise AI Service
$aiService = new EnterpriseAIService($pdo);

// Handle form submission
$message = '';
$messageType = '';

if ($_POST) {
    try {
        // Update API key
        if (!empty($_POST['api_key'])) {
            $aiService->updateConfiguration('openai.api_key', $_POST['api_key'], 'api_key');
        }

        // Update model configurations
        if (!empty($_POST['matching_model'])) {
            $aiService->updateConfiguration('ai.matching.default_model', $_POST['matching_model'], 'model_config');
        }
        if (!empty($_POST['insights_model'])) {
            $aiService->updateConfiguration('ai.insights.default_model', $_POST['insights_model'], 'model_config');
        }
        if (!empty($_POST['analysis_model'])) {
            $aiService->updateConfiguration('ai.analysis.default_model', $_POST['analysis_model'], 'model_config');
        }
        if (!empty($_POST['general_model'])) {
            $aiService->updateConfiguration('ai.general.default_model', $_POST['general_model'], 'model_config');
        }

        // Test connection if API key was changed
        if (!empty($_POST['api_key'])) {
            $testResult = $aiService->testConnection($_POST['matching_model'] ?? null);
            if (!$testResult['success']) {
                throw new Exception('Σφάλμα σύνδεσης με OpenAI: ' . $testResult['error']);
            }
        }

        $message = 'Οι ρυθμίσεις AI αποθηκεύτηκαν επιτυχώς!';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'error';
    }
}

// Load current configuration from database
try {
    $stmt = $pdo->query("
        SELECT config_key, config_value, config_type, description 
        FROM ai_configuration 
        WHERE environment = 'production'
        ORDER BY config_type, config_key
    ");
    $dbConfig = [];
    while ($row = $stmt->fetch()) {
        $dbConfig[$row['config_key']] = [
            'value' => json_decode($row['config_value'], true),
            'type' => $row['config_type'],
            'description' => $row['description']
        ];
    }
} catch (Exception $e) {
    $dbConfig = [];
}

// Load available models from database
try {
    $stmt = $pdo->query("
        SELECT * FROM ai_models 
        WHERE is_active = 1 
        ORDER BY priority DESC
    ");
    $availableModels = $stmt->fetchAll();
} catch (Exception $e) {
    $availableModels = [];
}

// Test current connection
$connectionStatus = $aiService->testConnection();

// Get current API key
$currentApiKey = $dbConfig['openai.api_key']['value'] ?? '';
$matchingModel = $dbConfig['ai.matching.default_model']['value'] ?? 'o1-preview';
$insightsModel = $dbConfig['ai.insights.default_model']['value'] ?? 'o1-mini';
$analysisModel = $dbConfig['ai.analysis.default_model']['value'] ?? 'gpt-4o';
$generalModel = $dbConfig['ai.general.default_model']['value'] ?? 'gpt-4o-mini';
?>

<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🤖 Ρυθμίσεις AI - DriveJob Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --ai-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-color: #27ae60;
            --danger-color: #e74c3c;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .ai-header {
            background: var(--ai-gradient);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .ai-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .ai-card-header {
            background: var(--ai-gradient);
            color: white;
            padding: 1.5rem;
        }

        .status-connected {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success-color);
            border: 2px solid var(--success-color);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
        }

        .status-error {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
            border: 2px solid var(--danger-color);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
        }

        .ai-button {
            background: var(--ai-gradient);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .ai-button:hover {
            transform: translateY(-2px);
            color: white;
        }

        .api-key-input {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 0.75rem;
        }
    </style>
</head>

<body>
    <div class="ai-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="fas fa-robot me-3"></i>
                        Ρυθμίσεις AI System
                    </h1>
                    <p class="mb-0 mt-2 opacity-75">
                        Διαχείριση ChatGPT-5 & OpenAI Integration
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="<?php echo $connectionStatus['success'] ? 'status-connected' : 'status-error'; ?>">
                        <i class="fas fa-<?php echo $connectionStatus['success'] ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                        <?php echo $connectionStatus['success'] ? 'Συνδεδεμένο' : 'Σφάλμα'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST">
            <!-- API Configuration -->
            <div class="ai-card">
                <div class="ai-card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-key me-2"></i>
                        API Configuration
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label for="api_key" class="form-label fw-bold">
                            OpenAI API Key
                            <span class="text-danger">*</span>
                        </label>
                        <input type="password" class="form-control api-key-input" id="api_key" name="api_key"
                            value="<?php echo htmlspecialchars($currentApiKey); ?>"
                            placeholder="sk-proj-...">
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Το API key με πρόσβαση σε ChatGPT-5 (o1 models)
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-success" onclick="testConnection()">
                            <i class="fas fa-plug me-2"></i>
                            Δοκιμή Σύνδεσης
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="toggleApiKey()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>

                    <div id="connectionResult" class="mt-3"></div>
                </div>
            </div>

            <!-- Model Configuration -->
            <div class="ai-card">
                <div class="ai-card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-brain me-2"></i>
                        Model Configuration
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Matching Model</label>
                            <select class="form-select" name="matching_model">
                                <?php foreach ($availableModels as $model): ?>
                                    <option value="<?php echo $model['model_name']; ?>"
                                        <?php echo $matchingModel === $model['model_name'] ? 'selected' : ''; ?>>
                                        <?php echo $model['model_name']; ?> (<?php echo $model['model_type']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Insights Model</label>
                            <select class="form-select" name="insights_model">
                                <?php foreach ($availableModels as $model): ?>
                                    <option value="<?php echo $model['model_name']; ?>"
                                        <?php echo $insightsModel === $model['model_name'] ? 'selected' : ''; ?>>
                                        <?php echo $model['model_name']; ?> (<?php echo $model['model_type']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Analysis Model</label>
                            <select class="form-select" name="analysis_model">
                                <?php foreach ($availableModels as $model): ?>
                                    <option value="<?php echo $model['model_name']; ?>"
                                        <?php echo $analysisModel === $model['model_name'] ? 'selected' : ''; ?>>
                                        <?php echo $model['model_name']; ?> (<?php echo $model['model_type']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">General Model</label>
                            <select class="form-select" name="general_model">
                                <?php foreach ($availableModels as $model): ?>
                                    <option value="<?php echo $model['model_name']; ?>"
                                        <?php echo $generalModel === $model['model_name'] ? 'selected' : ''; ?>>
                                        <?php echo $model['model_name']; ?> (<?php echo $model['model_type']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Status -->
            <div class="ai-card">
                <div class="ai-card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>
                        Current Status
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="h4 text-primary mb-1">
                                <?php echo $connectionStatus['success'] ? '✅' : '❌'; ?>
                            </div>
                            <div class="small text-muted">API Status</div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="h4 text-info mb-1">
                                <?php echo $matchingModel; ?>
                            </div>
                            <div class="small text-muted">Primary Model</div>
                        </div>
                    </div>

                    <?php if ($connectionStatus['success']): ?>
                        <div class="alert alert-info">
                            <h6 class="fw-bold mb-2">
                                <i class="fas fa-info-circle me-2"></i>
                                Connection Details
                            </h6>
                            <p><strong>Model:</strong> <?php echo htmlspecialchars($connectionStatus['model']); ?></p>
                            <p><strong>Response:</strong> <?php echo htmlspecialchars($connectionStatus['message']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="ai-card">
                <div class="card-body p-4 text-center">
                    <button type="submit" class="ai-button me-3">
                        <i class="fas fa-save me-2"></i>
                        Αποθήκευση Ρυθμίσεων
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>
                        Επιστροφή στο Admin
                    </a>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleApiKey() {
            const input = document.getElementById('api_key');
            const icon = document.getElementById('toggleIcon');

            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        async function testConnection() {
            const apiKey = document.getElementById('api_key').value;
            const resultDiv = document.getElementById('connectionResult');

            if (!apiKey) {
                resultDiv.innerHTML = '<div class="alert alert-warning">Παρακαλώ εισάγετε το API key πρώτα.</div>';
                return;
            }

            resultDiv.innerHTML = '<div class="alert alert-info">Δοκιμή σύνδεσης...</div>';

            try {
                const response = await fetch('test-connection.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        api_key: apiKey,
                        model: 'gpt-4o-mini'
                    })
                });

                const result = await response.json();

                if (result.success) {
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Επιτυχής σύνδεση!</strong><br>
                            <small>Model: ${result.model}<br>
                            Response: ${result.message}</small>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Σφάλμα σύνδεσης:</strong><br>
                            <small>${result.error}</small>
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Σφάλμα:</strong> ${error.message}
                    </div>
                `;
            }
        }
    </script>
</body>

</html>
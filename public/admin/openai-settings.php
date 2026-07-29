<?php

/**
 * Admin OpenAI Settings Panel
 * 
 * Διαχείριση ρυθμίσεων OpenAI από το admin panel
 */

require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Core\AuthMiddleware;
use Drivejob\Services\OpenAIService;

// Check admin authentication
Session::start();
AuthMiddleware::hasRole('admin');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $configPath = ROOT_DIR . '/config/openai.php';
    $currentConfig = include $configPath;

    // Update configuration
    $newConfig = $currentConfig;
    $newConfig['api_key'] = $_POST['api_key'] ?? $currentConfig['api_key'];
    $newConfig['default_model'] = $_POST['default_model'] ?? $currentConfig['default_model'];
    $newConfig['models']['matching'] = $_POST['matching_model'] ?? $currentConfig['models']['matching'];
    $newConfig['models']['insights'] = $_POST['insights_model'] ?? $currentConfig['models']['insights'];
    $newConfig['models']['analysis'] = $_POST['analysis_model'] ?? $currentConfig['models']['analysis'];
    $newConfig['max_tokens'] = intval($_POST['max_tokens'] ?? $currentConfig['max_tokens']);
    $newConfig['temperature'] = floatval($_POST['temperature'] ?? $currentConfig['temperature']);
    $newConfig['timeout'] = intval($_POST['timeout'] ?? $currentConfig['timeout']);

    // Save configuration
    $configContent = "<?php\n\n/**\n * OpenAI Configuration\n * \n * Ρυθμίσεις για την ενσωμάτωση του OpenAI API\n */\n\nreturn " . var_export($newConfig, true) . ";\n";

    if (file_put_contents($configPath, $configContent)) {
        $success_message = "Οι ρυθμίσεις OpenAI ενημερώθηκαν με επιτυχία!";

        // Test connection
        try {
            $openAIService = new OpenAIService();
            $testResult = $openAIService->testConnection();
            if ($testResult['success']) {
                $success_message .= " Η σύνδεση με το OpenAI API είναι επιτυχής.";
            } else {
                $error_message = "Προσοχή: " . $testResult['message'];
            }
        } catch (Exception $e) {
            $error_message = "Σφάλμα σύνδεσης: " . $e->getMessage();
        }
    } else {
        $error_message = "Σφάλμα κατά την αποθήκευση των ρυθμίσεων.";
    }
}

// Load current configuration
$config = include ROOT_DIR . '/config/openai.php';

include ROOT_DIR . '/src/Views/partials/header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/admin.css">

<main class="admin-main">
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class="fas fa-robot"></i> Ρυθμίσεις OpenAI</h1>
            <p>Διαχείριση παραμέτρων του AI-powered matching system</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="admin-content">
            <form method="POST" class="openai-settings-form">
                <div class="settings-section">
                    <h2><i class="fas fa-key"></i> API Configuration</h2>

                    <div class="form-group">
                        <label for="api_key">OpenAI API Key:</label>
                        <input type="password" id="api_key" name="api_key"
                            value="<?php echo htmlspecialchars($config['api_key']); ?>"
                            class="form-control" required>
                        <small class="form-help">Το API key από το OpenAI dashboard</small>
                    </div>
                </div>

                <div class="settings-section">
                    <h2><i class="fas fa-brain"></i> Model Configuration</h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="default_model">Default Model:</label>
                            <select id="default_model" name="default_model" class="form-control">
                                <option value="gpt-4" <?php echo $config['default_model'] === 'gpt-4' ? 'selected' : ''; ?>>GPT-4 (Recommended)</option>
                                <option value="gpt-4-turbo" <?php echo $config['default_model'] === 'gpt-4-turbo' ? 'selected' : ''; ?>>GPT-4 Turbo</option>
                                <option value="gpt-3.5-turbo" <?php echo $config['default_model'] === 'gpt-3.5-turbo' ? 'selected' : ''; ?>>GPT-3.5 Turbo</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="matching_model">Matching Model:</label>
                            <select id="matching_model" name="matching_model" class="form-control">
                                <option value="gpt-4" <?php echo $config['models']['matching'] === 'gpt-4' ? 'selected' : ''; ?>>GPT-4</option>
                                <option value="gpt-4-turbo" <?php echo $config['models']['matching'] === 'gpt-4-turbo' ? 'selected' : ''; ?>>GPT-4 Turbo</option>
                                <option value="gpt-3.5-turbo" <?php echo $config['models']['matching'] === 'gpt-3.5-turbo' ? 'selected' : ''; ?>>GPT-3.5 Turbo</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="insights_model">Insights Model:</label>
                            <select id="insights_model" name="insights_model" class="form-control">
                                <option value="gpt-4" <?php echo $config['models']['insights'] === 'gpt-4' ? 'selected' : ''; ?>>GPT-4</option>
                                <option value="gpt-4-turbo" <?php echo $config['models']['insights'] === 'gpt-4-turbo' ? 'selected' : ''; ?>>GPT-4 Turbo</option>
                                <option value="gpt-3.5-turbo" <?php echo $config['models']['insights'] === 'gpt-3.5-turbo' ? 'selected' : ''; ?>>GPT-3.5 Turbo</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="analysis_model">Analysis Model:</label>
                            <select id="analysis_model" name="analysis_model" class="form-control">
                                <option value="gpt-4" <?php echo $config['models']['analysis'] === 'gpt-4' ? 'selected' : ''; ?>>GPT-4</option>
                                <option value="gpt-4-turbo" <?php echo $config['models']['analysis'] === 'gpt-4-turbo' ? 'selected' : ''; ?>>GPT-4 Turbo</option>
                                <option value="gpt-3.5-turbo" <?php echo $config['models']['analysis'] === 'gpt-3.5-turbo' ? 'selected' : ''; ?>>GPT-3.5 Turbo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="settings-section">
                    <h2><i class="fas fa-cogs"></i> Request Parameters</h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="max_tokens">Max Tokens:</label>
                            <input type="number" id="max_tokens" name="max_tokens"
                                value="<?php echo $config['max_tokens']; ?>"
                                class="form-control" min="100" max="4000">
                            <small class="form-help">Μέγιστος αριθμός tokens ανά request (100-4000)</small>
                        </div>

                        <div class="form-group">
                            <label for="temperature">Temperature:</label>
                            <input type="number" id="temperature" name="temperature"
                                value="<?php echo $config['temperature']; ?>"
                                class="form-control" min="0" max="2" step="0.1">
                            <small class="form-help">Creativity level (0.0-2.0, 0.7 recommended)</small>
                        </div>

                        <div class="form-group">
                            <label for="timeout">Timeout (seconds):</label>
                            <input type="number" id="timeout" name="timeout"
                                value="<?php echo $config['timeout']; ?>"
                                class="form-control" min="10" max="120">
                            <small class="form-help">Timeout για API requests (10-120 δευτερόλεπτα)</small>
                        </div>
                    </div>
                </div>

                <div class="settings-section">
                    <h2><i class="fas fa-chart-line"></i> Current Status</h2>

                    <div class="status-grid">
                        <div class="status-item">
                            <div class="status-label">API Status:</div>
                            <div class="status-value" id="api-status">
                                <i class="fas fa-spinner fa-spin"></i> Checking...
                            </div>
                        </div>

                        <div class="status-item">
                            <div class="status-label">Current Model:</div>
                            <div class="status-value"><?php echo htmlspecialchars($config['default_model']); ?></div>
                        </div>

                        <div class="status-item">
                            <div class="status-label">Max Tokens:</div>
                            <div class="status-value"><?php echo $config['max_tokens']; ?></div>
                        </div>

                        <div class="status-item">
                            <div class="status-label">Temperature:</div>
                            <div class="status-value"><?php echo $config['temperature']; ?></div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Αποθήκευση Ρυθμίσεων
                    </button>

                    <button type="button" class="btn btn-secondary" onclick="testConnection()">
                        <i class="fas fa-plug"></i> Δοκιμή Σύνδεσης
                    </button>

                    <a href="<?php echo BASE_URL; ?>admin/dashboard" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Επιστροφή
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<style>
    .openai-settings-form {
        max-width: 1000px;
        margin: 0 auto;
    }

    .settings-section {
        background: white;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .settings-section h2 {
        color: #333;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 5px;
        color: #555;
    }

    .form-control {
        width: 100%;
        padding: 10px;
        border: 2px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
        transition: border-color 0.3s;
    }

    .form-control:focus {
        border-color: #007bff;
        outline: none;
    }

    .form-help {
        display: block;
        margin-top: 5px;
        color: #666;
        font-size: 12px;
    }

    .status-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }

    .status-item {
        padding: 15px;
        background: #f8f9fa;
        border-radius: 5px;
        border-left: 4px solid #007bff;
    }

    .status-label {
        font-weight: 600;
        color: #555;
        margin-bottom: 5px;
    }

    .status-value {
        font-size: 16px;
        color: #333;
    }

    .form-actions {
        text-align: center;
        padding: 25px;
        background: #f8f9fa;
        border-radius: 8px;
        margin-top: 25px;
    }

    .btn {
        padding: 12px 25px;
        margin: 0 10px;
        border: none;
        border-radius: 5px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
    }

    .btn-primary {
        background: #007bff;
        color: white;
    }

    .btn-primary:hover {
        background: #0056b3;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-outline {
        background: transparent;
        color: #007bff;
        border: 2px solid #007bff;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        font-weight: 500;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>

<script>
    // Test OpenAI connection
    function testConnection() {
        const statusElement = document.getElementById('api-status');
        statusElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';

        fetch('<?php echo BASE_URL; ?>test-openai.php')
            .then(response => response.text())
            .then(data => {
                if (data.includes('SUCCESS')) {
                    statusElement.innerHTML = '<i class="fas fa-check-circle" style="color: green;"></i> Connected';
                } else {
                    statusElement.innerHTML = '<i class="fas fa-times-circle" style="color: red;"></i> Failed';
                }
            })
            .catch(error => {
                statusElement.innerHTML = '<i class="fas fa-times-circle" style="color: red;"></i> Error';
            });
    }

    // Check status on page load
    document.addEventListener('DOMContentLoaded', function() {
        testConnection();
    });
</script>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>
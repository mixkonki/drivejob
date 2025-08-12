<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚙️ Ρυθμίσεις Συστήματος - DriveJob Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --admin-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #3498db;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .admin-header {
            background: var(--admin-gradient);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .settings-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }

        .settings-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .settings-card-header {
            background: var(--admin-gradient);
            color: white;
            padding: 1.5rem;
            border-bottom: none;
        }

        .settings-item {
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }

        .settings-item:last-child {
            border-bottom: none;
        }

        .settings-item:hover {
            background: #f8f9fa;
        }

        .settings-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-right: 1rem;
        }

        .ai-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .database-icon {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .security-icon {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .system-icon {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        .users-icon {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }

        .logs-icon {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        }

        .settings-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .settings-description {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .settings-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .status-warning {
            background: rgba(243, 156, 18, 0.1);
            color: var(--warning-color);
            border: 1px solid var(--warning-color);
        }

        .status-inactive {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }

        .admin-button {
            background: var(--admin-gradient);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .admin-button:hover {
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .breadcrumb-custom {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
    </style>
</head>

<body>
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-0">
                        <i class="fas fa-cogs me-3"></i>
                        Ρυθμίσεις Συστήματος
                    </h1>
                    <p class="mb-0 mt-2 opacity-75">
                        Διαχείριση και ρύθμιση του DriveJob Platform
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="text-white">
                        <i class="fas fa-user-shield me-2"></i>
                        <?php echo htmlspecialchars($admin['name'] ?? 'Administrator'); ?>
                    </div>
                    <div class="small opacity-75">
                        Super Administrator
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb-custom">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="<?php echo BASE_URL; ?>admin/dashboard">
                        <i class="fas fa-home me-1"></i>
                        Admin Panel
                    </a>
                </li>
                <li class="breadcrumb-item active">
                    <i class="fas fa-cogs me-1"></i>
                    Ρυθμίσεις
                </li>
            </ol>
        </nav>

        <!-- AI & Machine Learning Settings -->
        <div class="settings-card">
            <div class="settings-card-header">
                <h5 class="mb-0">
                    <i class="fas fa-robot me-2"></i>
                    AI & Machine Learning
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="settings-item">
                    <div class="d-flex align-items-center">
                        <div class="settings-icon ai-icon">
                            <i class="fas fa-brain"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="settings-title">OpenAI Integration</div>
                            <div class="settings-description">
                                Διαχείριση ChatGPT-5 models, API keys και AI configurations
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="settings-status status-active">
                                    <i class="fas fa-check-circle me-1"></i>
                                    Ενεργό
                                </span>
                                <a href="<?php echo BASE_URL; ?>admin/ai-settings" class="admin-button">
                                    <i class="fas fa-cog me-2"></i>
                                    Ρυθμίσεις AI
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Database & Storage Settings -->
        <div class="settings-card">
            <div class="settings-card-header">
                <h5 class="mb-0">
                    <i class="fas fa-database me-2"></i>
                    Database & Storage
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="settings-item">
                    <div class="d-flex align-items-center">
                        <div class="settings-icon database-icon">
                            <i class="fas fa-server"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="settings-title">Database Configuration</div>
                            <div class="settings-description">
                                MySQL settings, backup configurations και performance tuning
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="settings-status status-active">
                                    <i class="fas fa-check-circle me-1"></i>
                                    Λειτουργικό
                                </span>
                                <a href="#" class="admin-button">
                                    <i class="fas fa-database me-2"></i>
                                    Database Settings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Settings -->
        <div class="settings-card">
            <div class="settings-card-header">
                <h5 class="mb-0">
                    <i class="fas fa-shield-alt me-2"></i>
                    Security & Authentication
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="settings-item">
                    <div class="d-flex align-items-center">
                        <div class="settings-icon security-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="settings-title">Security Policies</div>
                            <div class="settings-description">
                                Password policies, session management και access controls
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="settings-status status-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Χρειάζεται Ενημέρωση
                                </span>
                                <a href="#" class="admin-button">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    Security Settings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Settings -->
        <div class="settings-card">
            <div class="settings-card-header">
                <h5 class="mb-0">
                    <i class="fas fa-cog me-2"></i>
                    System Configuration
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="settings-item">
                    <div class="d-flex align-items-center">
                        <div class="settings-icon system-icon">
                            <i class="fas fa-sliders-h"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="settings-title">General Settings</div>
                            <div class="settings-description">
                                Site configuration, email settings και general preferences
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="settings-status status-active">
                                    <i class="fas fa-check-circle me-1"></i>
                                    Ρυθμισμένο
                                </span>
                                <a href="#" class="admin-button">
                                    <i class="fas fa-cog me-2"></i>
                                    System Settings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Management -->
        <div class="settings-card">
            <div class="settings-card-header">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>
                    User Management
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="settings-item">
                    <div class="d-flex align-items-center">
                        <div class="settings-icon users-icon">
                            <i class="fas fa-user-cog"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="settings-title">User Roles & Permissions</div>
                            <div class="settings-description">
                                Διαχείριση χρηστών, ρόλων και δικαιωμάτων πρόσβασης
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="settings-status status-active">
                                    <i class="fas fa-check-circle me-1"></i>
                                    Ενεργό
                                </span>
                                <a href="<?php echo BASE_URL; ?>admin/users" class="admin-button">
                                    <i class="fas fa-users me-2"></i>
                                    User Settings
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs & Monitoring -->
        <div class="settings-card">
            <div class="settings-card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>
                    Logs & Monitoring
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="settings-item">
                    <div class="d-flex align-items-center">
                        <div class="settings-icon logs-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="settings-title">System Logs</div>
                            <div class="settings-description">
                                Error logs, access logs και system monitoring
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="settings-status status-active">
                                    <i class="fas fa-check-circle me-1"></i>
                                    Καταγραφή Ενεργή
                                </span>
                                <a href="<?php echo BASE_URL; ?>admin/monitoring/logs" class="admin-button">
                                    <i class="fas fa-chart-line me-2"></i>
                                    View Logs
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="settings-card">
            <div class="settings-card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>
                    Quick Actions
                </h5>
            </div>
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo BASE_URL; ?>test-ai-system.php" class="admin-button w-100 text-center">
                            <i class="fas fa-vial me-2"></i>
                            Test AI System
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="#" class="admin-button w-100 text-center">
                            <i class="fas fa-sync me-2"></i>
                            Clear Cache
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo BASE_URL; ?>admin/monitoring/backup-database" class="admin-button w-100 text-center">
                            <i class="fas fa-download me-2"></i>
                            Backup System
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="<?php echo BASE_URL; ?>admin/dashboard" class="admin-button w-100 text-center">
                            <i class="fas fa-arrow-left me-2"></i>
                            Back to Admin
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        console.log('🔧 Admin Settings Panel Loaded');
        console.log('AI Integration: Active');
        console.log('Database: Connected');
        console.log('Security: Monitoring');
    </script>
</body>

</html>
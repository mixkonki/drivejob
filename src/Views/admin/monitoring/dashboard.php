<?php
// Admin System Monitoring Dashboard
// Χρησιμοποιούμε τη βοηθητική συνάρτηση για τη μορφοποίηση των bytes
use Drivejob\Helpers\MonitoringHelper;

// Συμπεριλαμβάνουμε το header
include ROOT_DIR . '/src/Views/partials/admin-header.php';

// Έλεγχος αν έχουμε τη νέα δομή δεδομένων
$hasNewStructure = isset($systemStats['system_status']);
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>System Monitoring</h1>
        <div class="admin-actions">
            <button class="btn btn-secondary" onclick="refreshStats()">
                <i class="icon-refresh"></i> Ανανέωση
            </button>
        </div>
    </div>

    <!-- Μηνύματα -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="icon-check"></i>
            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error">
            <i class="icon-error"></i>
            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <?php if ($hasNewStructure): ?>
        <!-- Κατάσταση Συστήματος -->
        <div class="stats-section">
            <h2>Κατάσταση Συστήματος</h2>
            <div class="system-status-grid">
                <!-- Overall Status -->
                <div class="status-card <?php echo MonitoringHelper::getStatusClass($systemStats['system_status']['status'] ?? 'unknown'); ?>">
                    <h3>Γενική Κατάσταση</h3>
                    <div class="status-value"><?php echo ucfirst($systemStats['system_status']['status'] ?? 'unknown'); ?></div>
                    <div class="status-time">Τελευταίος έλεγχος: <?php echo date('H:i:s', strtotime($systemStats['system_status']['timestamp'] ?? 'now')); ?></div>
                </div>

                <!-- Database Status -->
                <div class="status-card">
                    <h3>Βάση Δεδομένων</h3>
                    <div class="status-item">
                        <span class="label">Κατάσταση:</span>
                        <span class="value <?php echo MonitoringHelper::getStatusClass($systemStats['system_status']['database']['status'] ?? 'unknown'); ?>">
                            <?php echo $systemStats['system_status']['database']['connected'] ? 'Συνδεδεμένη' : 'Αποσυνδεδεμένη'; ?>
                        </span>
                    </div>
                    <div class="status-item">
                        <span class="label">Χρόνος απόκρισης:</span>
                        <span class="value"><?php echo $systemStats['system_status']['database']['response_time'] ?? 'N/A'; ?> ms</span>
                    </div>
                </div>

                <!-- Disk Space -->
                <div class="status-card">
                    <h3>Χώρος Δίσκου</h3>
                    <div class="status-item">
                        <span class="label">Χρησιμοποιημένος:</span>
                        <span class="value"><?php echo $systemStats['system_status']['disk_space']['used'] ?? 'N/A'; ?> / <?php echo $systemStats['system_status']['disk_space']['total'] ?? 'N/A'; ?></span>
                    </div>
                    <?php echo MonitoringHelper::createProgressBar($systemStats['system_status']['disk_space']['percentage'] ?? 0, '', true); ?>
                </div>

                <!-- Memory Usage -->
                <div class="status-card">
                    <h3>Χρήση Μνήμης</h3>
                    <div class="status-item">
                        <span class="label">Τρέχουσα:</span>
                        <span class="value"><?php echo $systemStats['system_status']['memory']['current'] ?? 'N/A'; ?> / <?php echo $systemStats['system_status']['memory']['limit'] ?? 'N/A'; ?></span>
                    </div>
                    <?php echo MonitoringHelper::createProgressBar($systemStats['system_status']['memory']['percentage'] ?? 0, '', true); ?>
                </div>

                <!-- CPU Usage -->
                <div class="status-card">
                    <h3>Χρήση CPU</h3>
                    <div class="status-item">
                        <span class="label">Χρήση:</span>
                        <span class="value"><?php echo $systemStats['system_status']['cpu']['usage'] ?? 'N/A'; ?>%</span>
                    </div>
                    <?php echo MonitoringHelper::createProgressBar($systemStats['system_status']['cpu']['usage'] ?? 0, '', true); ?>
                </div>
            </div>
        </div>

        <!-- Μετρικές Απόδοσης -->
        <?php if (isset($systemStats['performance'])): ?>
            <div class="stats-section">
                <h2>Μετρικές Απόδοσης</h2>
                <div class="metrics-grid">
                    <div class="metric-card">
                        <h4>Μέσος Χρόνος Απόκρισης</h4>
                        <div class="metric-value"><?php echo $systemStats['performance']['response_time']['average'] ?? 'N/A'; ?> ms</div>
                    </div>
                    <div class="metric-card">
                        <h4>Συνολικά Αιτήματα</h4>
                        <div class="metric-value"><?php echo number_format($systemStats['performance']['requests']['total'] ?? 0); ?></div>
                        <div class="metric-sub"><?php echo number_format($systemStats['performance']['requests']['per_minute'] ?? 0, 2); ?> / λεπτό</div>
                    </div>
                    <div class="metric-card">
                        <h4>Ποσοστό Σφαλμάτων</h4>
                        <div class="metric-value"><?php echo number_format($systemStats['performance']['error_rate'] ?? 0, 2); ?>%</div>
                    </div>
                    <div class="metric-card">
                        <h4>Ενεργοί Χρήστες</h4>
                        <div class="metric-value"><?php echo number_format($systemStats['performance']['active_users'] ?? 0); ?></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Πρόσφατα Σφάλματα -->
        <?php if (isset($systemStats['errors']) && is_array($systemStats['errors'])): ?>
            <div class="stats-section">
                <h2>Πρόσφατα Σφάλματα</h2>
                <?php if (empty($systemStats['errors'])): ?>
                    <p class="no-data">Δεν υπάρχουν πρόσφατα σφάλματα</p>
                <?php else: ?>
                    <div class="errors-list">
                        <?php foreach (array_slice($systemStats['errors'], 0, 5) as $error): ?>
                            <div class="error-item <?php echo 'error-' . ($error['error_type'] ?? 'info'); ?>">
                                <div class="error-header">
                                    <span class="error-type"><?php echo ucfirst($error['error_type'] ?? 'info'); ?></span>
                                    <span class="error-time"><?php echo date('d/m H:i', strtotime($error['created_at'] ?? 'now')); ?></span>
                                </div>
                                <div class="error-message"><?php echo htmlspecialchars($error['error_message'] ?? 'No message'); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="view-all">
                        <a href="<?php echo BASE_URL; ?>admin/monitoring/errors" class="btn btn-sm btn-info">
                            Προβολή Όλων των Σφαλμάτων
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Στατιστικά Χρήσης -->
        <?php if (isset($systemStats['usage'])): ?>
            <div class="stats-section">
                <h2>Στατιστικά Χρήσης</h2>

                <!-- Δημοφιλείς Σελίδες -->
                <?php if (!empty($systemStats['usage']['popular_pages'])): ?>
                    <div class="subsection">
                        <h3>Δημοφιλείς Σελίδες</h3>
                        <div class="popular-pages">
                            <?php foreach ($systemStats['usage']['popular_pages'] as $page): ?>
                                <div class="page-item">
                                    <span class="page-url"><?php echo htmlspecialchars($page['page_url']); ?></span>
                                    <span class="page-views"><?php echo number_format($page['views']); ?> προβολές</span>
                                    <span class="page-time"><?php echo number_format($page['avg_load_time']); ?> ms</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Τύποι Χρηστών -->
                <?php if (!empty($systemStats['usage']['user_types'])): ?>
                    <div class="subsection">
                        <h3>Χρήστες ανά Τύπο</h3>
                        <div class="user-types">
                            <?php foreach ($systemStats['usage']['user_types'] as $userType): ?>
                                <div class="user-type-card">
                                    <h4><?php echo ucfirst($userType['type']); ?></h4>
                                    <div class="user-type-stats">
                                        <div class="stat">
                                            <span class="label">Σύνολο:</span>
                                            <span class="value"><?php echo number_format($userType['total']); ?></span>
                                        </div>
                                        <div class="stat">
                                            <span class="label">Ενεργοί:</span>
                                            <span class="value"><?php echo number_format($userType['active']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Fallback για παλιά δομή δεδομένων -->
        <div class="alert alert-warning">
            <i class="icon-warning"></i>
            Το σύστημα χρησιμοποιεί παλιά δομή δεδομένων. Παρακαλώ ενημερώστε το σύστημα.
        </div>
    <?php endif; ?>

    <!-- Γρήγορες Ενέργειες -->
    <div class="stats-section">
        <h2>Γρήγορες Ενέργειες</h2>
        <div class="quick-actions">
            <a href="<?php echo BASE_URL; ?>admin/monitoring/errors" class="quick-action-btn">
                <i class="icon-error"></i>
                <span>Σφάλματα</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/monitoring/performance" class="quick-action-btn">
                <i class="icon-performance"></i>
                <span>Απόδοση</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/monitoring/usage" class="quick-action-btn">
                <i class="icon-usage"></i>
                <span>Χρήση</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/monitoring/logs" class="quick-action-btn">
                <i class="icon-logs"></i>
                <span>Logs</span>
            </a>
        </div>
    </div>
</div>

<style>
    /* System Monitoring Dashboard Styles */
    .admin-container {
        padding: 20px;
        max-width: 1400px;
        margin: 0 auto;
    }

    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .admin-header h1 {
        color: #333;
        margin: 0;
    }

    .admin-actions {
        display: flex;
        gap: 10px;
    }

    /* Status Grid */
    .system-status-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .status-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        padding: 20px;
        border-left: 4px solid #e0e0e0;
    }

    .status-card.status-good {
        border-left-color: #28a745;
    }

    .status-card.status-warning {
        border-left-color: #ffc107;
    }

    .status-card.status-critical {
        border-left-color: #dc3545;
    }

    .status-card h3 {
        margin: 0 0 15px 0;
        font-size: 16px;
        color: #666;
    }

    .status-value {
        font-size: 24px;
        font-weight: bold;
        color: #333;
    }

    .status-time {
        font-size: 12px;
        color: #999;
        margin-top: 10px;
    }

    .status-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .status-item .label {
        color: #666;
    }

    .status-item .value {
        font-weight: 500;
    }

    /* Progress Bars */
    .progress-container {
        margin-top: 10px;
    }

    .progress {
        height: 20px;
        background-color: #f0f0f0;
        border-radius: 10px;
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
        transition: width 0.3s ease;
    }

    /* Metrics Grid */
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }

    .metric-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
    }

    .metric-card h4 {
        margin: 0 0 10px 0;
        font-size: 14px;
        color: #666;
    }

    .metric-value {
        font-size: 28px;
        font-weight: bold;
        color: #333;
    }

    .metric-sub {
        font-size: 12px;
        color: #999;
        margin-top: 5px;
    }

    /* Errors List */
    .errors-list {
        max-height: 400px;
        overflow-y: auto;
    }

    .error-item {
        background: #f8f9fa;
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 10px;
        border-left: 4px solid #e0e0e0;
    }

    .error-item.error-critical,
    .error-item.error-error {
        border-left-color: #dc3545;
    }

    .error-item.error-warning {
        border-left-color: #ffc107;
    }

    .error-item.error-info {
        border-left-color: #17a2b8;
    }

    .error-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .error-type {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 12px;
    }

    .error-time {
        font-size: 12px;
        color: #999;
    }

    .error-message {
        font-size: 14px;
        color: #333;
    }

    /* Popular Pages */
    .popular-pages {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
    }

    .page-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #e0e0e0;
    }

    .page-item:last-child {
        border-bottom: none;
    }

    .page-url {
        font-weight: 500;
        color: #333;
    }

    .page-views {
        color: #666;
        font-size: 14px;
    }

    .page-time {
        color: #999;
        font-size: 12px;
    }

    /* User Types */
    .user-types {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }

    .user-type-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
    }

    .user-type-card h4 {
        margin: 0 0 15px 0;
        font-size: 16px;
        color: #333;
    }

    .user-type-stats .stat {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }

    .user-type-stats .label {
        color: #666;
    }

    .user-type-stats .value {
        font-weight: 600;
        color: #333;
    }

    /* Stats Section */
    .stats-section {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        padding: 25px;
        margin-bottom: 30px;
    }

    .stats-section h2 {
        margin: 0 0 20px 0;
        color: #333;
        font-size: 20px;
        border-bottom: 2px solid #f0f0f0;
        padding-bottom: 10px;
    }

    .subsection {
        margin-bottom: 30px;
    }

    .subsection h3 {
        margin: 0 0 15px 0;
        color: #666;
        font-size: 16px;
    }

    /* Quick Actions */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
    }

    .quick-action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
        text-decoration: none;
        color: #333;
        transition: all 0.3s;
        text-align: center;
    }

    .quick-action-btn i {
        font-size: 24px;
        margin-bottom: 10px;
    }

    .quick-action-btn:hover {
        background: #e9ecef;
        transform: translateY(-3px);
    }

    /* Utilities */
    .no-data {
        text-align: center;
        color: #999;
        padding: 40px;
        font-style: italic;
    }

    .view-all {
        text-align: center;
        margin-top: 20px;
    }

    /* Status Classes */
    .status-good {
        color: #28a745;
    }

    .status-warning {
        color: #ffc107;
    }

    .status-critical {
        color: #dc3545;
    }

    .status-unknown {
        color: #6c757d;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .system-status-grid {
            grid-template-columns: 1fr;
        }

        .metrics-grid {
            grid-template-columns: 1fr 1fr;
        }

        .quick-actions {
            grid-template-columns: 1fr 1fr;
        }
    }

    /* Icons */
    .icon-refresh::before {
        content: '🔄';
    }

    .icon-check::before {
        content: '✓';
    }

    .icon-error::before {
        content: '⚠️';
    }

    .icon-warning::before {
        content: '⚠️';
    }

    .icon-performance::before {
        content: '📊';
    }

    .icon-usage::before {
        content: '👥';
    }

    .icon-logs::before {
        content: '📝';
    }
</style>

<script>
    function refreshStats() {
        window.location.reload();
    }
</script>

<?php
include ROOT_DIR . '/src/Views/partials/admin-footer.php';
?>
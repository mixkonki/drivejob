<?php
// Admin System Monitoring Dashboard
include ROOT_DIR . '/src/Views/partials/admin-header.php';
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>System Monitoring</h1>
        <div class="admin-actions">
            <form method="POST" action="<?php echo BASE_URL; ?>admin/monitoring/backup-database" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="icon-database"></i> Backup Database
                </button>
            </form>
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

    <!-- Συνοπτικά Στατιστικά Συστήματος -->
    <div class="stats-section">
        <h2>Πληροφορίες Συστήματος</h2>
        <div class="system-info-grid">
            <div class="system-info-card">
                <h3>Server</h3>
                <div class="info-item">
                    <span class="info-label">PHP Version:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemStats['server']['php_version']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Server Software:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemStats['server']['server_software']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Memory Usage:</span>
                    <span class="info-value"><?php echo formatBytes($systemStats['server']['memory_usage']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Memory Limit:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemStats['server']['memory_limit']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Max Execution Time:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemStats['server']['max_execution_time']); ?> seconds</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Upload Max Filesize:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemStats['server']['upload_max_filesize']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Post Max Size:</span>
                    <span class="info-value"><?php echo htmlspecialchars($systemStats['server']['post_max_size']); ?></span>
                </div>
            </div>

            <div class="system-info-card">
                <h3>Database</h3>
                <div class="info-item">
                    <span class="info-label">Database Size:</span>
                    <span class="info-value"><?php echo formatBytes($systemStats['database']['size']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tables Count:</span>
                    <span class="info-value"><?php echo number_format($systemStats['database']['tables_count']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Largest Tables:</span>
                    <div class="table-rows">
                        <?php
                        $tableRows = array_slice($systemStats['database']['table_rows'], 0, 5);
                        foreach ($tableRows as $table):
                        ?>
                            <div class="table-row-item">
                                <span class="table-name"><?php echo htmlspecialchars($table['table_name']); ?></span>
                                <span class="table-count"><?php echo number_format($table['table_rows']); ?> rows</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="system-info-card">
                <h3>Errors</h3>
                <div class="info-item">
                    <span class="info-label">Total Errors:</span>
                    <span class="info-value"><?php echo number_format($systemStats['errors']['total']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Errors by Type:</span>
                    <div class="error-types">
                        <?php foreach ($systemStats['errors']['per_type'] as $errorType): ?>
                            <div class="error-type-item">
                                <span class="error-type"><?php echo htmlspecialchars($errorType['type']); ?></span>
                                <span class="error-count"><?php echo number_format($errorType['count']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="info-item">
                    <a href="<?php echo BASE_URL; ?>admin/monitoring/errors" class="btn btn-sm btn-info">
                        View All Errors
                    </a>
                </div>
            </div>

            <div class="system-info-card">
                <h3>Performance</h3>
                <div class="info-item">
                    <span class="info-label">Avg Response Time:</span>
                    <span class="info-value"><?php echo number_format($systemStats['performance']['avg_response_time'] * 1000, 2); ?> ms</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Max Response Time:</span>
                    <span class="info-value"><?php echo number_format($systemStats['performance']['max_response_time'] * 1000, 2); ?> ms</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Requests (7 days):</span>
                    <div class="requests-per-day">
                        <?php foreach ($systemStats['performance']['requests_per_day'] as $day): ?>
                            <div class="request-day-item">
                                <span class="request-day"><?php echo date('d/m', strtotime($day['date'])); ?></span>
                                <span class="request-count"><?php echo number_format($day['count']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="info-item">
                    <a href="<?php echo BASE_URL; ?>admin/monitoring/performance" class="btn btn-sm btn-info">
                        View Performance Data
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Γραφήματα -->
    <div class="stats-section">
        <h2>Γραφήματα Απόδοσης</h2>
        <div class="charts-container">
            <div class="chart-card">
                <h3>Σφάλματα ανά Ημέρα</h3>
                <div class="chart-container">
                    <canvas id="errorsChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h3>Χρόνος Απόκρισης</h3>
                <div class="chart-container">
                    <canvas id="responseTimeChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h3>Ενεργοί Χρήστες</h3>
                <div class="chart-container">
                    <canvas id="activeUsersChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h3>Κατανομή Συσκευών</h3>
                <div class="chart-container">
                    <canvas id="deviceDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Πρόσφατα Αντίγραφα Ασφαλείας -->
    <div class="stats-section">
        <h2>Αντίγραφα Ασφαλείας</h2>
        <?php if ($systemStats['backups']['last_backup']): ?>
            <div class="last-backup">
                <div class="backup-info">
                    <div class="backup-label">Τελευταίο Αντίγραφο:</div>
                    <div class="backup-value"><?php echo date('d/m/Y H:i', strtotime($systemStats['backups']['last_backup']['created_at'])); ?></div>
                </div>
                <div class="backup-info">
                    <div class="backup-label">Όνομα Αρχείου:</div>
                    <div class="backup-value"><?php echo htmlspecialchars($systemStats['backups']['last_backup']['filename']); ?></div>
                </div>
                <div class="backup-info">
                    <div class="backup-label">Μέγεθος:</div>
                    <div class="backup-value"><?php echo formatBytes($systemStats['backups']['last_backup']['size']); ?></div>
                </div>
            </div>
        <?php else: ?>
            <div class="no-backups">
                Δεν υπάρχουν αντίγραφα ασφαλείας.
            </div>
        <?php endif; ?>
        <div class="backup-actions">
            <form method="POST" action="<?php echo BASE_URL; ?>admin/monitoring/backup-database">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                <button type="submit" class="btn btn-primary">
                    <i class="icon-database"></i> Δημιουργία Αντιγράφου
                </button>
            </form>
        </div>
    </div>

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

    /* System Info Grid */
    .system-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }

    .system-info-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        padding: 20px;
    }

    .system-info-card h3 {
        margin-top: 0;
        margin-bottom: 15px;
        color: #333;
        font-size: 18px;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }

    .info-item {
        margin-bottom: 12px;
    }

    .info-label {
        font-weight: 500;
        color: #666;
        display: block;
        margin-bottom: 5px;
    }

    .info-value {
        color: #333;
    }

    /* Table Rows */
    .table-rows {
        margin-top: 10px;
    }

    .table-row-item {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        border-bottom: 1px dashed #eee;
    }

    .table-row-item:last-child {
        border-bottom: none;
    }

    .table-name {
        font-size: 13px;
    }

    .table-count {
        font-size: 13px;
        color: #666;
    }

    /* Error Types */
    .error-types {
        margin-top: 10px;
    }

    .error-type-item {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
        border-bottom: 1px dashed #eee;
    }

    .error-type-item:last-child {
        border-bottom: none;
    }

    .error-type {
        font-size: 13px;
    }

    .error-count {
        font-size: 13px;
        color: #666;
    }

    /* Requests Per Day */
    .requests-per-day {
        margin-top: 10px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .request-day-item {
        background: #f8f9fa;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 13px;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .request-day {
        font-weight: 500;
    }

    .request-count {
        color: #666;
    }

    /* Charts */
    .charts-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
        gap: 20px;
    }

    .chart-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        padding: 20px;
    }

    .chart-card h3 {
        margin-top: 0;
        margin-bottom: 15px;
        color: #333;
        font-size: 18px;
    }

    .chart-container {
        height: 300px;
    }

    /* Backups */
    .last-backup {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-bottom: 20px;
    }

    .backup-info {
        display: flex;
        margin-bottom: 10px;
    }

    .backup-label {
        font-weight: 500;
        width: 150px;
    }

    .backup-value {
        flex: 1;
    }

    .no-backups {
        background: #f8f9fa;
        padding: 20px;
        text-align: center;
        border-radius: 8px;
        color: #666;
        margin-bottom: 20px;
    }

    .backup-actions {
        margin-top: 20px;
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

    /* Stats Section */
    .stats-section {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-bottom: 30px;
    }

    .stats-section h2 {
        margin-top: 0;
        margin-bottom: 20px;
        color: #333;
        font-size: 18px;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .system-info-grid {
            grid-template-columns: 1fr;
        }

        .charts-container {
            grid-template-columns: 1fr;
        }

        .quick-actions {
            grid-template-columns: 1fr 1fr;
        }
    }

    /* Icons */
    .icon-database::before {
        content: '🗄️';
    }

    .icon-refresh::before {
        content: '🔄';
    }

    .icon-check::before {
        content: '✓';
    }

    .icon-error::before {
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Helper function to format dates
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.getDate() + '/' + (date.getMonth() + 1);
    }

    // Errors Chart
    const errorsCtx = document.getElementById('errorsChart').getContext('2d');
    const errorsData = <?php echo json_encode($systemStats['errors']['per_day'] ?? []); ?>;

    new Chart(errorsCtx, {
        type: 'bar',
        data: {
            labels: errorsData.map(item => formatDate(item.date)),
            datasets: [{
                label: 'Errors',
                data: errorsData.map(item => item.count),
                backgroundColor: 'rgba(255, 99, 132, 0.5)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Response Time Chart
    const responseTimeCtx = document.getElementById('responseTimeChart').getContext('2d');
    const performanceData = <?php echo json_encode($systemStats['performance']['requests_per_day'] ?? []); ?>;

    new Chart(responseTimeCtx, {
        type: 'line',
        data: {
            labels: performanceData.map(item => formatDate(item.date)),
            datasets: [{
                label: 'Avg Response Time (ms)',
                data: performanceData.map(item => item.avg_response_time * 1000),
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Active Users Chart
    const activeUsersCtx = document.getElementById('activeUsersChart').getContext('2d');
    const usageData = <?php echo json_encode($systemStats['usage']['active_users_per_day'] ?? []); ?>;

    new Chart(activeUsersCtx, {
        type: 'line',
        data: {
            labels: usageData.map(item => formatDate(item.date)),
            datasets: [{
                label: 'Active Users',
                data: usageData.map(item => item.active_users),
                backgroundColor: 'rgba(75, 192, 192, 0.5)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });

    // Device Distribution Chart
    const deviceDistributionCtx = document.getElementById('deviceDistributionChart').getContext('2d');
    const deviceData = <?php echo json_encode($systemStats['usage']['device_distribution'] ?? ['mobile' => 0, 'desktop' => 0]); ?>;

    new Chart(deviceDistributionCtx, {
        type: 'pie',
        data: {
            labels: ['Mobile', 'Desktop'],
            datasets: [{
                data: [deviceData.mobile, deviceData.desktop],
                backgroundColor: [
                    'rgba(255, 159, 64, 0.5)',
                    'rgba(153, 102, 255, 0.5)'
                ],
                borderColor: [
                    'rgba(255, 159, 64, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    function refreshStats() {
        window.location.reload();
    }
</script>

<?php
// Helper function to format bytes
function formatBytes($bytes, $precision = 2)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>

<?php
include ROOT_DIR . '/src/Views/partials/admin-footer.php';
?>
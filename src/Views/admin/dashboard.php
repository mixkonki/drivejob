<?php
// Admin Dashboard Page
include ROOT_DIR . '/src/Views/partials/admin-header.php';
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Admin Dashboard</h1>
        <div class="admin-actions">
            <button class="btn btn-primary" onclick="refreshStats()">
                <i class="icon-refresh"></i> Ανανέωση
            </button>
        </div>
    </div>

    <!-- Συνοπτικά Στατιστικά -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-icon">👤</div>
            <div class="stat-content">
                <h3>Οδηγοί</h3>
                <div class="stat-value"><?php echo number_format($stats['total_drivers']); ?></div>
                <div class="stat-label">Σύνολο οδηγών</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🏢</div>
            <div class="stat-content">
                <h3>Εταιρείες</h3>
                <div class="stat-value"><?php echo number_format($stats['total_companies']); ?></div>
                <div class="stat-label">Σύνολο εταιρειών</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-content">
                <h3>Αγγελίες</h3>
                <div class="stat-value"><?php echo number_format($stats['total_job_listings']); ?></div>
                <div class="stat-label">Σύνολο αγγελιών</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🔄</div>
            <div class="stat-content">
                <h3>Ενεργά Matches</h3>
                <div class="stat-value"><?php echo number_format($stats['active_matches']); ?></div>
                <div class="stat-label">Τρέχοντα ταιριάσματα</div>
            </div>
        </div>
    </div>

    <!-- Σημερινά Στατιστικά -->
    <div class="stats-section">
        <h2>Σημερινή Δραστηριότητα</h2>
        <div class="stats-cards small">
            <div class="stat-card">
                <div class="stat-icon small">👥</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['new_registrations_today']); ?></div>
                    <div class="stat-label">Νέες εγγραφές</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon small">📝</div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['new_job_listings_today']); ?></div>
                    <div class="stat-label">Νέες αγγελίες</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Πρόσφατη Δραστηριότητα -->
    <div class="stats-section">
        <h2>Πρόσφατη Δραστηριότητα</h2>
        <div class="activity-list">
            <?php if (!empty($stats['recent_activity'])): ?>
                <?php foreach ($stats['recent_activity'] as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <?php if ($activity['type'] === 'driver_registration'): ?>
                                👤
                            <?php elseif ($activity['type'] === 'company_registration'): ?>
                                🏢
                            <?php elseif ($activity['type'] === 'job_listing'): ?>
                                📋
                            <?php else: ?>
                                🔔
                            <?php endif; ?>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">
                                <?php if ($activity['type'] === 'driver_registration'): ?>
                                    Νέος οδηγός: <?php echo htmlspecialchars($activity['description']); ?>
                                <?php elseif ($activity['type'] === 'company_registration'): ?>
                                    Νέα εταιρεία: <?php echo htmlspecialchars($activity['description']); ?>
                                <?php elseif ($activity['type'] === 'job_listing'): ?>
                                    Νέα αγγελία: <?php echo htmlspecialchars($activity['description']); ?>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($activity['description']); ?>
                                <?php endif; ?>
                            </div>
                            <div class="activity-time">
                                <?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">Δεν υπάρχει πρόσφατη δραστηριότητα</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Γρήγορες Ενέργειες -->
    <div class="stats-section">
        <h2>Γρήγορες Ενέργειες</h2>
        <div class="quick-actions">
            <a href="<?php echo BASE_URL; ?>admin/users" class="quick-action-btn">
                <i class="icon-users"></i>
                <span>Διαχείριση Χρηστών</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/job-listings" class="quick-action-btn">
                <i class="icon-briefcase"></i>
                <span>Διαχείριση Αγγελιών</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/analytics" class="quick-action-btn">
                <i class="icon-chart"></i>
                <span>Στατιστικά & Analytics</span>
            </a>
            <a href="<?php echo BASE_URL; ?>admin/settings" class="quick-action-btn">
                <i class="icon-settings"></i>
                <span>Ρυθμίσεις Συστήματος</span>
            </a>
        </div>
    </div>
</div>

<style>
    /* Dashboard Styles */
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

    /* Stats Cards */
    .stats-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stats-cards.small {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }

    .stat-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        padding: 20px;
        display: flex;
        align-items: center;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .stat-icon {
        font-size: 36px;
        margin-right: 20px;
        background: #f0f4ff;
        width: 70px;
        height: 70px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .stat-icon.small {
        font-size: 24px;
        width: 50px;
        height: 50px;
    }

    .stat-content {
        flex: 1;
    }

    .stat-content h3 {
        margin: 0 0 5px 0;
        color: #333;
        font-size: 16px;
    }

    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .stats-cards.small .stat-value {
        font-size: 22px;
    }

    .stat-label {
        font-size: 14px;
        color: #666;
    }

    /* Stats Sections */
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

    /* Activity List */
    .activity-list {
        max-height: 400px;
        overflow-y: auto;
    }

    .activity-item {
        display: flex;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #eee;
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-icon {
        font-size: 24px;
        margin-right: 15px;
        width: 40px;
        height: 40px;
        background: #f0f4ff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .activity-content {
        flex: 1;
    }

    .activity-title {
        font-weight: 500;
        margin-bottom: 5px;
    }

    .activity-time {
        font-size: 12px;
        color: #666;
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

    /* No Data */
    .no-data {
        text-align: center;
        padding: 20px;
        color: #666;
        font-style: italic;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .stats-cards {
            grid-template-columns: 1fr;
        }

        .quick-actions {
            grid-template-columns: 1fr 1fr;
        }
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
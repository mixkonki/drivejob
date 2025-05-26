<?php

/**
 * Driver Management Card Component
 * Εμφανίζει τις πληροφορίες διαχείρισης οδηγών
 */
?>
<div class="feature-card driver-management-card">
    <div class="card-header">
        <div class="card-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"></path>
                <path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z"></path>
                <path d="M8 11v1a3 3 0 0 0 6 0v-1"></path>
            </svg>
        </div>
        <div class="card-title">
            <h3>DriveManager Pro</h3>
            <span class="badge <?php echo $companyData['has_hr_system'] ? 'badge-active' : 'badge-inactive'; ?>">
                <?php echo $companyData['has_hr_system'] ? 'Ενεργό' : 'Ανενεργό'; ?>
            </span>
        </div>
    </div>

    <div class="card-content">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-value"><?php echo $companyData['active_drivers'] ?? 0; ?></div>
                <div class="stat-label">Ενεργοί Οδηγοί</div>
            </div>
            <?php if ($companyData['average_hiring_time']) : ?>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $companyData['average_hiring_time']; ?></div>
                    <div class="stat-label">Μέσος Χρόνος Πρόσληψης (ημέρες)</div>
                </div>
            <?php endif; ?>
        </div>

        <div class="features-list">
            <div class="feature-item <?php echo $companyData['has_hr_system'] ? 'active' : ''; ?>">
                <svg class="feature-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M9 5H7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2V7a2 2 0 0 0 -2 -2h-2"></path>
                    <rect x="9" y="3" width="6" height="4" rx="2"></rect>
                    <line x1="9" y1="12" x2="9.01" y2="12"></line>
                    <line x1="13" y1="12" x2="15" y2="12"></line>
                </svg>
                <span>Σύστημα HR</span>
            </div>
            <div class="feature-item <?php echo $companyData['has_payroll_system'] ? 'active' : ''; ?>">
                <svg class="feature-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <rect x="3" y="7" width="18" height="13" rx="2"></rect>
                    <path d="M3 10h18"></path>
                    <path d="M7 15h.01"></path>
                    <path d="M11 15h2"></path>
                </svg>
                <span>Σύστημα Μισθοδοσίας</span>
            </div>
            <div class="feature-item <?php echo $companyData['has_training_program'] ? 'active' : ''; ?>">
                <svg class="feature-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M22 9l-10 -4l-10 4l10 4l10 -4v6"></path>
                    <path d="M6 10.6v5.4a6 3 0 0 0 12 0v-5.4"></path>
                </svg>
                <span>Πρόγραμμα Εκπαίδευσης</span>
            </div>
        </div>

        <?php if (!$companyData['has_hr_system'] && $companyData['active_drivers'] > 5) : ?>
            <div class="card-cta">
                <p>Αυτοματοποιήστε τη διαχείριση του προσωπικού σας</p>
                <a href="<?php echo BASE_URL; ?>companies/edit-profile#driver-management" class="btn-upgrade">
                    Ενεργοποίηση DriveManager
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
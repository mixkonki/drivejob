<?php

/**
 * Subscription Card Component
 * Εμφανίζει το πακέτο συνδρομής και τα ενεργά modules
 */
?>
<div class="feature-card subscription-card">
    <div class="card-header">
        <div class="card-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87l1.18 6.88L12 17.77l-6.18 3.25L7 14.14L2 9.27l6.91-1.01L12 2z"></path>
            </svg>
        </div>
        <div class="card-title">
            <h3>Πακέτο Συνδρομής</h3>
            <?php
            $planLabels = [
                'basic' => 'Basic',
                'professional' => 'Professional',
                'enterprise' => 'Enterprise',
                'custom' => 'Custom'
            ];
            $currentPlan = $companyData['subscription_plan'] ?? 'basic';
            ?>
            <span class="badge badge-plan-<?php echo $currentPlan; ?>">
                <?php echo $planLabels[$currentPlan] ?? 'Basic'; ?>
            </span>
        </div>
    </div>

    <div class="card-content">
        <?php if ($companyData['subscription_expires_at']) : ?>
            <div class="subscription-info">
                <div class="info-row">
                    <span class="info-label">Λήξη Συνδρομής:</span>
                    <span class="info-value">
                        <?php
                        $expiryDate = new DateTime($companyData['subscription_expires_at']);
                        $today = new DateTime();
                        $daysLeft = $today->diff($expiryDate)->days;
                        $isExpired = $expiryDate < $today;
                        ?>
                        <?php echo $expiryDate->format('d/m/Y'); ?>
                        <?php if (!$isExpired) : ?>
                            <small>(<?php echo $daysLeft; ?> ημέρες)</small>
                        <?php else : ?>
                            <small class="text-danger">(Έληξε)</small>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>

        <?php
        $enabledModules = json_decode($companyData['enabled_modules'] ?? '[]', true) ?: [];
        $allModules = [
            'job_posting' => ['icon' => '📢', 'label' => 'Δημοσίευση Αγγελιών'],
            'driver_search' => ['icon' => '🔍', 'label' => 'Αναζήτηση Οδηγών'],
            'ats' => ['icon' => '📋', 'label' => 'Applicant Tracking System'],
            'driver_management' => ['icon' => '👥', 'label' => 'DriveManager Pro'],
            'fleet_management' => ['icon' => '🚛', 'label' => 'DriveFleet Solutions'],
            'compliance' => ['icon' => '⚖️', 'label' => 'Legal & Compliance Hub'],
            'analytics' => ['icon' => '📊', 'label' => 'Advanced Analytics'],
            'api_access' => ['icon' => '🔌', 'label' => 'API Access']
        ];
        ?>

        <div class="modules-section">
            <h4>Ενεργά Modules:</h4>
            <div class="modules-grid">
                <?php foreach ($allModules as $moduleKey => $module) : ?>
                    <div class="module-item <?php echo in_array($moduleKey, $enabledModules) ? 'active' : 'inactive'; ?>">
                        <span class="module-icon"><?php echo $module['icon']; ?></span>
                        <span class="module-label"><?php echo $module['label']; ?></span>
                        <?php if (in_array($moduleKey, $enabledModules)) : ?>
                            <svg class="check-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M5 12l5 5L20 7"></path>
                            </svg>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($currentPlan !== 'enterprise') : ?>
            <div class="upgrade-section">
                <p>Αναβαθμίστε για περισσότερες δυνατότητες</p>
                <a href="<?php echo BASE_URL; ?>pricing" class="btn-upgrade">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M12 5v14"></path>
                        <path d="M5 12l7 -7l7 7"></path>
                    </svg>
                    Αναβάθμιση Πακέτου
                </a>
            </div>
        <?php endif; ?>

        <div class="usage-stats">
            <div class="stat-row">
                <span>Μηνιαίες Αγγελίες:</span>
                <span><?php echo $companyData['monthly_job_posts'] ?? 0; ?></span>
            </div>
            <div class="stat-row">
                <span>Επιτυχημένες Προσλήψεις:</span>
                <span><?php echo $companyData['successful_hires'] ?? 0; ?></span>
            </div>
        </div>
    </div>
</div>
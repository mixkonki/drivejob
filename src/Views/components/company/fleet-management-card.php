<?php

/**
 * Fleet Management Card Component
 * Εμφανίζει τις πληροφορίες διαχείρισης στόλου
 */
?>
<div class="feature-card fleet-management-card">
    <div class="card-header">
        <div class="card-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M7 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                <path d="M17 17m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                <path d="M5 17h-2v-6l2 -5h9l4 5h1a2 2 0 0 1 2 2v4h-2m-4 0h-6m-6 -6h15m-6 0v-5"></path>
            </svg>
        </div>
        <div class="card-title">
            <h3>DriveFleet Solutions</h3>
            <span class="badge <?php echo $companyData['has_fleet_management'] ? 'badge-active' : 'badge-inactive'; ?>">
                <?php echo $companyData['has_fleet_management'] ? 'Ενεργό' : 'Ανενεργό'; ?>
            </span>
        </div>
    </div>

    <div class="card-content">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-value"><?php echo $companyData['fleet_size'] ?? 0; ?></div>
                <div class="stat-label">Οχήματα Στόλου</div>
            </div>
            <?php if ($companyData['has_fleet_management']) : ?>
                <div class="stat-item">
                    <div class="stat-icon">
                        <?php if ($companyData['has_telematics']) : ?>
                            <svg class="icon-check" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M5 12l5 5L20 7"></path>
                            </svg>
                        <?php else : ?>
                            <svg class="icon-x" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M18 6L6 18M6 6l12 12"></path>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <div class="stat-label">Telematics</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <?php if ($companyData['has_route_optimization']) : ?>
                            <svg class="icon-check" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M5 12l5 5L20 7"></path>
                            </svg>
                        <?php else : ?>
                            <svg class="icon-x" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M18 6L6 18M6 6l12 12"></path>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <div class="stat-label">Route Optimization</div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($companyData['maintenance_provider']) : ?>
            <div class="info-row">
                <span class="info-label">Πάροχος Συντήρησης:</span>
                <span class="info-value"><?php echo htmlspecialchars($companyData['maintenance_provider']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!$companyData['has_fleet_management'] && $companyData['fleet_size'] > 0) : ?>
            <div class="card-cta">
                <p>Βελτιστοποιήστε τη διαχείριση του στόλου σας με προηγμένα εργαλεία</p>
                <a href="<?php echo BASE_URL; ?>companies/edit-profile#fleet-management" class="btn-upgrade">
                    Ενεργοποίηση DriveFleet
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
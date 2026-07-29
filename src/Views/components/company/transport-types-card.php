<?php

/**
 * Transport Types Card Component
 * Εμφανίζει τους τύπους μεταφορών που εκτελεί η εταιρεία
 */
?>
<div class="feature-card transport-types-card">
    <div class="card-header">
        <div class="card-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M7 18a4.6 4.4 0 0 1 0 -9a5 4.5 0 0 1 11 2h1a3.5 3.5 0 0 1 0 7h-12"></path>
                <path d="M9 15l-3 -3l3 -3"></path>
                <path d="M15 15l3 -3l-3 -3"></path>
            </svg>
        </div>
        <div class="card-title">
            <h3>Τύποι Μεταφορών</h3>
            <?php
            $transportTypes = json_decode($companyData['transport_types'] ?? '[]', true) ?: [];
            ?>
            <span class="badge badge-info"><?php echo count($transportTypes); ?> τύποι</span>
        </div>
    </div>

    <div class="card-content">
        <?php if (!empty($transportTypes)) : ?>
            <div class="transport-types-grid">
                <?php
                $typeIcons = [
                    'national' => ['icon' => '🇬🇷', 'label' => 'Εθνικές Μεταφορές'],
                    'international' => ['icon' => '🌍', 'label' => 'Διεθνείς Μεταφορές'],
                    'urban' => ['icon' => '🏙️', 'label' => 'Αστικές Διανομές'],
                    'refrigerated' => ['icon' => '❄️', 'label' => 'Ψυγεία'],
                    'hazmat' => ['icon' => '⚠️', 'label' => 'Επικίνδυνα Φορτία (ADR)'],
                    'bulk' => ['icon' => '📦', 'label' => 'Χύδην Φορτία'],
                    'container' => ['icon' => '🚢', 'label' => 'Containers'],
                    'vehicle_transport' => ['icon' => '🚗', 'label' => 'Μεταφορά Οχημάτων'],
                    'livestock' => ['icon' => '🐄', 'label' => 'Μεταφορά Ζώων'],
                    'oversized' => ['icon' => '🚛', 'label' => 'Υπερμεγέθη Φορτία']
                ];

                foreach ($transportTypes as $type) :
                    if (isset($typeIcons[$type])) :
                ?>
                        <div class="transport-type-item">
                            <div class="type-icon"><?php echo $typeIcons[$type]['icon']; ?></div>
                            <div class="type-label"><?php echo $typeIcons[$type]['label']; ?></div>
                        </div>
                <?php
                    endif;
                endforeach;
                ?>
            </div>

            <?php
            // Έλεγχος για ειδικές απαιτήσεις
            $hasSpecialRequirements = false;
            foreach ($transportTypes as $type) {
                if (in_array($type, ['refrigerated', 'hazmat', 'livestock', 'oversized'])) {
                    $hasSpecialRequirements = true;
                    break;
                }
            }

            if ($hasSpecialRequirements) :
            ?>
                <div class="special-requirements-notice">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span>Εκτελείτε μεταφορές με ειδικές απαιτήσεις</span>
                </div>
            <?php endif; ?>

        <?php else : ?>
            <div class="empty-state">
                <p>Δεν έχετε καταχωρήσει τους τύπους μεταφορών που εκτελείτε</p>
                <a href="<?php echo BASE_URL; ?>companies/edit-profile#company-details" class="btn-secondary">
                    Προσθήκη Τύπων Μεταφορών
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php

/**
 * Compliance Card Component
 * Εμφανίζει πληροφορίες συμμόρφωσης και πιστοποιήσεων
 */
?>
<div class="feature-card compliance-card">
    <div class="card-header">
        <div class="card-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 11l3 3l8 -8"></path>
                <path d="M20 12v6a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12a2 2 0 0 1 2 -2h9"></path>
            </svg>
        </div>
        <div class="card-title">
            <h3>Compliance & Legal</h3>
            <span class="badge <?php echo $companyData['has_legal_support'] ? 'badge-active' : 'badge-inactive'; ?>">
                <?php echo $companyData['has_legal_support'] ? 'Με Νομική Υποστήριξη' : 'Χωρίς Νομική Υποστήριξη'; ?>
            </span>
        </div>
    </div>

    <div class="card-content">
        <?php if ($companyData['operates_internationally']) : ?>
            <div class="international-badge">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="2" y1="12" x2="22" y2="12"></line>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10a15.3 15.3 0 0 1 -4 10a15.3 15.3 0 0 1 -4 -10a15.3 15.3 0 0 1 4 -10z"></path>
                </svg>
                <span>Διεθνείς Μεταφορές</span>
            </div>

            <?php
            $countries = json_decode($companyData['operating_countries'] ?? '[]', true) ?: [];
            if (!empty($countries)) :
            ?>
                <div class="countries-list">
                    <h4>Χώρες Δραστηριοποίησης:</h4>
                    <div class="country-tags">
                        <?php
                        $countryNames = [
                            'GR' => '🇬🇷 Ελλάδα',
                            'DE' => '🇩🇪 Γερμανία',
                            'IT' => '🇮🇹 Ιταλία',
                            'FR' => '🇫🇷 Γαλλία',
                            'ES' => '🇪🇸 Ισπανία',
                            'NL' => '🇳🇱 Ολλανδία',
                            'BE' => '🇧🇪 Βέλγιο',
                            'AT' => '🇦🇹 Αυστρία',
                            'PL' => '🇵🇱 Πολωνία',
                            'RO' => '🇷🇴 Ρουμανία',
                            'BG' => '🇧🇬 Βουλγαρία',
                            'HU' => '🇭🇺 Ουγγαρία',
                            'CZ' => '🇨🇿 Τσεχία',
                            'SK' => '🇸🇰 Σλοβακία'
                        ];
                        foreach ($countries as $code) :
                            if (isset($countryNames[$code])) :
                        ?>
                                <span class="country-tag"><?php echo $countryNames[$code]; ?></span>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php
        $specializations = json_decode($companyData['specializations'] ?? '[]', true) ?: [];
        if (!empty($specializations)) :
        ?>
            <div class="certifications-section">
                <h4>Πιστοποιήσεις & Εξειδικεύσεις:</h4>
                <div class="certification-grid">
                    <?php foreach ($specializations as $spec) : ?>
                        <div class="certification-item">
                            <?php if (strpos($spec, 'ADR') !== false) : ?>
                                <div class="cert-icon danger">⚠️</div>
                            <?php elseif (strpos($spec, 'ATP') !== false) : ?>
                                <div class="cert-icon cold">❄️</div>
                            <?php elseif (strpos($spec, 'ISO') !== false) : ?>
                                <div class="cert-icon quality">✓</div>
                            <?php else : ?>
                                <div class="cert-icon">📋</div>
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars($spec); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (empty($specializations) && !$companyData['operates_internationally']) : ?>
            <div class="empty-state">
                <p>Προσθέστε τις πιστοποιήσεις και εξειδικεύσεις σας για να ξεχωρίσετε</p>
                <a href="<?php echo BASE_URL; ?>companies/edit-profile#compliance" class="btn-secondary">
                    Προσθήκη Πιστοποιήσεων
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
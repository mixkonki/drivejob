<?php /* Καρτέλα «tachograph-card» της φόρμας επεξεργασίας προφίλ — αποσπάστηκε από το edit-profile.php (Πακέτο 5.4).
   Μοιράζεται το scope μεταβλητών του γονικού view (include). */ ?>
                    <div class="tab-pane" id="tachograph-card">
                        <h2>Κάρτα Ψηφιακού Ταχογράφου</h2>

                        <div class="license-section">
                            <div class="form-group checkbox-group">
                                <label for="tachograph_card" class="checkbox-label">
                                    <input type="checkbox" id="tachograph_card" name="tachograph_card" value="1" <?php echo (isset($driverTachograph) && $driverTachograph) ? 'checked' : ''; ?>>
                                    <span>Διαθέτω κάρτα ψηφιακού ταχογράφου</span>
                                </label>
                            </div>

                            <div id="tachograph_card_tab" class="license-details-tab <?php echo (!isset($driverTachograph) || !$driverTachograph) ? 'hidden' : ''; ?>">
                                <?php // Κάρτες εικόνων: κοινό partial (κλικ = επιλογή, live preview)
                                $docImages = [
                                    ['id' => 'tachograph_front_image', 'label' => 'Εμπρόσθια Όψη Κάρτας', 'scan_id' => 'scan-tachograph-front'],
                                    ['id' => 'tachograph_back_image', 'label' => 'Οπίσθια Όψη Κάρτας', 'scan_id' => 'scan-tachograph-back']
                                ];
                                include __DIR__ . '/_doc-upload.php';
                                ?>

                                <?php /* Πεδία κατά ΚΥΑ οικ.12527/1159/2014: 5β αριθμός κάρτας,
                                   4α έκδοση, 4β λήξη (5ετία, ανανέωση έως 2 μήνες πριν). */ ?>
                                <div class="license-basic-info">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="tachograph_card_number">Αριθμός Κάρτας Οδηγού</label>
                                            <input type="text" id="tachograph_card_number" name="tachograph_card_number" value="<?php echo old('tachograph_card_number', $driverTachograph['card_number'] ?? ''); ?>" placeholder="16 χαρακτήρες">
                                            <p class="form-hint">Πεδίο 5β της κάρτας</p>
                                        </div>

                                        <div class="form-group">
                                            <label for="tachograph_card_issue">Ημερομηνία Έκδοσης</label>
                                            <input type="date" id="tachograph_card_issue" name="tachograph_card_issue" value="<?php echo old('tachograph_card_issue', $driverTachograph['issue_date'] ?? ''); ?>">
                                            <p class="form-hint">Πεδίο 4α της κάρτας</p>
                                        </div>

                                        <div class="form-group">
                                            <label for="tachograph_card_expiry">Ημερομηνία Λήξης</label>
                                            <input type="date" id="tachograph_card_expiry" name="tachograph_card_expiry" value="<?php echo old('tachograph_card_expiry', $driverTachograph['expiry_date'] ?? ''); ?>">
                                            <p class="form-hint">Πεδίο 4β — ισχύς 5 έτη</p>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                // Υπενθύμιση ΜΟΝΟ στο δίμηνο παράθυρο ανανέωσης της κάρτας.
                                echo \Drivejob\Helpers\RenewalAlerts::render(
                                    $driverTachograph['expiry_date'] ?? null,
                                    \Drivejob\Helpers\RenewalAlerts::WINDOW_TACHOGRAPH,
                                    'Η κάρτα ψηφιακού ταχογράφου',
                                    'Η αίτηση ανανέωσης γίνεται ηλεκτρονικά: '
                                        . \Drivejob\Helpers\RenewalAlerts::link(\Drivejob\Helpers\RenewalAlerts::URL_TACHOGRAPH, 'gov.gr — Κάρτα ψηφιακού ταχογράφου')
                                );
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Tab για Ειδικές Άδειες -->

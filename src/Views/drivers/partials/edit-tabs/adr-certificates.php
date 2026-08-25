<?php /* Καρτέλα «adr-certificates» της φόρμας επεξεργασίας προφίλ — αποσπάστηκε από το edit-profile.php (Πακέτο 5.4).
   Μοιράζεται το scope μεταβλητών του γονικού view (include). */ ?>
                    <div class="tab-pane" id="adr-certificates">
                        <h2>Πιστοποιητικά ADR</h2>

                        <div class="license-section">
                            <div class="form-group checkbox-group">
                                <label for="adr_certificate" class="checkbox-label">
                                    <input type="checkbox" id="adr_certificate" name="adr_certificate" value="1" <?php echo ($driverADR) ? 'checked' : ''; ?>>
                                    <span>Διαθέτω πιστοποιητικό ADR</span>
                                </label>
                            </div>

                            <div id="adr_certificate_tab" class="license-details-tab <?php echo (!$driverADR) ? 'hidden' : ''; ?>">
                                <!-- Εικόνες πιστοποιητικού ADR και σκανάρισμα -->
                                <div class="license-visual">
                                    <?php
                                    $adrImages = [
                                        ['id' => 'adr_front_image', 'label' => 'Εμπρόσθια Όψη Πιστοποιητικού ADR', 'scan_id' => 'scan-adr-front'],
                                        ['id' => 'adr_back_image', 'label' => 'Οπίσθια Όψη Πιστοποιητικού ADR', 'scan_id' => 'scan-adr-back']
                                    ];

                                    foreach ($adrImages as $image) :
                                    ?>
                                        <div class="form-group">
                                            <label for="<?php echo $image['id']; ?>"><?php echo $image['label']; ?></label>
                                            <?php if (isset($driverData[$image['id']]) && $driverData[$image['id']]) : ?>
                                                <div class="current-image">
                                                    <img src="<?php echo BASE_URL . htmlspecialchars($driverData[$image['id']]); ?>" alt="<?php echo $image['label']; ?>">
                                                    <p>Τρέχουσα εικόνα</p>
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" id="<?php echo $image['id']; ?>" name="<?php echo $image['id']; ?>" accept="image/jpeg, image/png, image/gif">
                                            <button type="button" id="<?php echo $image['scan_id']; ?>" class="btn-scan">
                                                <img src="<?= \Drivejob\Helpers\Asset::url('img/scan_icon.png') ?>" alt="Scan" class="scan-icon">
                                                Σκανάρισμα με OCR
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Βασικές πληροφορίες πιστοποιητικού ADR -->
                                <div class="license-basic-info">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="adr_certificate_number">Αριθμός Πιστοποιητικού ADR</label>
                                            <input type="text" id="adr_certificate_number" name="adr_certificate_number" value="<?php echo old('adr_certificate_number', $driverADR['certificate_number'] ?? ''); ?>" placeholder="π.χ. GR1234567">
                                        </div>

                                        <div class="form-group">
                                            <label for="adr_certificate_expiry">Ημερομηνία Λήξης</label>
                                            <input type="date" id="adr_certificate_expiry" name="adr_certificate_expiry" value="<?php echo old('adr_certificate_expiry', $driverADR ? $driverADR['expiry_date'] : ''); ?>">
                                            <p class="form-hint">Το πιστοποιητικό ADR ανανεώνεται κάθε 5 έτη, και η ανανέωση μπορεί να γίνει κατά τον τελευταίο χρόνο πριν τη λήξη.</p>
                                        </div>
                                    </div>
                                </div>

                                <h4>Κατηγορίες Πιστοποιητικού ADR</h4>
                                <div class="adr-categories">
                                    <?php
                                    $adrCategories = [
                                        ['value' => 'Π1', 'label' => 'Π1 - Βασική + Πρακτική'],
                                        ['value' => 'Π2', 'label' => 'Π2 - Βασική + Κλάση 1 (εκρηκτικά)'],
                                        ['value' => 'Π3', 'label' => 'Π3 - Βασική + Κλάση 7 (ραδιενεργά)'],
                                        ['value' => 'Π4', 'label' => 'Π4 - Βασική + Κλάση 1 (εκρηκτικά) + Κλάση 7 (ραδιενεργά)'],
                                        ['value' => 'Π5', 'label' => 'Π5 - Βασική + Βυτία'],
                                        ['value' => 'Π6', 'label' => 'Π6 - Βασική + Βυτία + Κλάση 1 (εκρηκτικά)'],
                                        ['value' => 'Π7', 'label' => 'Π7 - Βασική + Βυτία + Κλάση 7 (ραδιενεργά)'],
                                        ['value' => 'Π8', 'label' => 'Π8 - Βασική + Βυτία + Κλάση 1 (εκρηκτικά) + Κλάση 7 (ραδιενεργά)']
                                    ];

                                    // Χωρισμός σε δύο στήλες
                                    $adrCategoriesChunks = array_chunk($adrCategories, ceil(count($adrCategories) / 2));

                                    foreach ($adrCategoriesChunks as $chunk) :
                                    ?>
                                        <div class="form-row">
                                            <?php foreach ($chunk as $category) : ?>
                                                <div class="form-group">
                                                    <label class="radio-label">
                                                        <input type="radio" name="adr_certificate_type" value="<?php echo $category['value']; ?>" <?php echo ($driverADR && $driverADR['adr_type'] == $category['value']) ? 'checked' : ''; ?>>
                                                        <span><?php echo $category['label']; ?></span>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php
                                // Υπενθύμιση ΜΟΝΟ στον τελευταίο χρόνο πριν τη λήξη (παράθυρο
                                // ανανέωσης ADR) — με αναζήτηση σχολής στην περιοχή του οδηγού.
                                echo \Drivejob\Helpers\RenewalAlerts::render(
                                    $driverADR['expiry_date'] ?? null,
                                    \Drivejob\Helpers\RenewalAlerts::WINDOW_ADR,
                                    'Το πιστοποιητικό ADR',
                                    'Η ανανέωση απαιτεί επαναληπτική εκπαίδευση σε εγκεκριμένη σχολή ΣΕΚΟΟΜΕΕ πριν τη λήξη. '
                                        . \Drivejob\Helpers\RenewalAlerts::schoolSearchLink('σχολή ADR ΣΕΚΟΟΜΕΕ', $driverData['city'] ?? null)
                                );
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Tab για Άδειες Χειριστή Μηχανημάτων Έργου -->

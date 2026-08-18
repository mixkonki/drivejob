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
                                <!-- Εικόνες κάρτας ταχογράφου και σκανάρισμα -->
                                <div class="license-visual">
                                    <?php
                                    $tachographImages = [
                                        ['id' => 'tachograph_front_image', 'label' => 'Εμπρόσθια Όψη Κάρτας Ταχογράφου', 'scan_id' => 'scan-tachograph-front'],
                                        ['id' => 'tachograph_back_image', 'label' => 'Οπίσθια Όψη Κάρτας Ταχογράφου', 'scan_id' => 'scan-tachograph-back']
                                    ];

                                    foreach ($tachographImages as $image) :
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
                                                <img src="<?php echo BASE_URL; ?>img/scan_icon.png" alt="Scan" class="scan-icon">
                                                Σκανάρισμα με OCR
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Βασικές πληροφορίες κάρτας ταχογράφου -->
                                <div class="license-basic-info">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="tachograph_card_number">Αριθμός Κάρτας Ταχογράφου</label>
                                            <input type="text" id="tachograph_card_number" name="tachograph_card_number" value="<?php echo old('tachograph_card_number', $driverTachograph['card_number'] ?? ''); ?>" placeholder="π.χ. GR1234567890">
                                        </div>

                                        <div class="form-group">
                                            <label for="tachograph_card_expiry">Ημερομηνία Λήξης</label>
                                            <input type="date" id="tachograph_card_expiry" name="tachograph_card_expiry" value="<?php echo old('tachograph_card_expiry', $driverTachograph['expiry_date'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Ενημερωτικό μήνυμα για την κάρτα ταχογράφου -->
                                <div class="expiry-reminder">
                                    <h4>Πληροφορίες για την Κάρτα Ψηφιακού Ταχογράφου</h4>
                                    <p>Με την κάρτα οδηγού ταυτοποιείται ο οδηγός και επιτρέπεται η αποθήκευση δεδομένων δραστηριότητας του οδηγού. Η κάρτα οδηγού είναι υποχρεωτική και η μοναδικότητά της ισχύει σε πανευρωπαϊκό επίπεδο.</p>
                                    <p>Η κάρτα οδηγού είναι εξατομικευμένη (φέρει την ψηφιοποιημένη φωτογραφία και υπογραφή του κατόχου της) και η ισχύς της είναι για πέντε (5) έτη. Η ανανέωση μπορεί να γίνει το νωρίτερο δύο μήνες πριν την ημερομηνία λήξης της κάρτας.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab για Ειδικές Άδειες -->

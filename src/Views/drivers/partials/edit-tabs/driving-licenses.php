<?php /* Καρτέλα «driving-licenses» της φόρμας επεξεργασίας προφίλ — αποσπάστηκε από το edit-profile.php (Πακέτο 5.4).
   Μοιράζεται το scope μεταβλητών του γονικού view (include). */ ?>
                    <div class="tab-pane" id="driving-licenses">
                        <h2>Άδειες Οδήγησης</h2>

                        <div class="license-section">
                            <div class="form-group checkbox-group">
                                <label for="driving_license" class="checkbox-label">
                                    <input type="checkbox" id="driving_license" name="driving_license" value="1" <?php echo (!empty($driverLicenseTypes)) ? 'checked' : ''; ?>>
                                    <span>Διαθέτω άδεια οδήγησης</span>
                                </label>
                            </div>

                            <div id="driving_license_tab" class="license-details-tab <?php echo (empty($driverLicenseTypes)) ? 'hidden' : ''; ?>">
                                <!-- Εικόνες διπλώματος και σκανάρισμα -->
                                <div class="license-visual">
                                    <?php
                                    $licenseImages = [
                                        ['id' => 'license_front_image', 'label' => 'Εμπρόσθια Όψη Διπλώματος', 'scan_id' => 'scan-license-front'],
                                        ['id' => 'license_back_image', 'label' => 'Οπίσθια Όψη Διπλώματος', 'scan_id' => 'scan-license-back']
                                    ];

                                    foreach ($licenseImages as $image) :
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

                                <!-- Βασικές πληροφορίες άδειας -->
                                <div class="license-basic-info">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="license_number">Αριθμός Άδειας Οδήγησης</label>
                                            <input type="text" id="license_number" name="license_number" value="<?php echo old('license_number', $driverData['license_number'] ?? ''); ?>" placeholder="π.χ. 123456789">
                                            <p class="form-hint">Εισάγετε τον αριθμό που αναγράφεται στο πεδίο 5 της άδειας οδήγησης</p>
                                        </div>

                                        <div class="form-group">
                                            <label for="license_document_expiry">Ημερομηνία Λήξης Εντύπου Άδειας</label>
                                            <input type="date" id="license_document_expiry" name="license_document_expiry" value="<?php echo old('license_document_expiry', $driverData['license_document_expiry'] ?? ''); ?>">
                                            <p class="form-hint">Εισάγετε την ημερομηνία που αναγράφεται στο πεδίο 4β της άδειας οδήγησης</p>
                                        </div>
                                    </div>

                                    <!-- Κωδικοί στήλης 12 του διπλώματος -->
                                    <div class="form-group">
                                        <label for="license_codes">Κωδικοί Περιορισμών/Πληροφοριών (Στήλη 12)</label>
                                        <input type="text" id="license_codes" name="license_codes" value="<?php echo old('license_codes', $driverData['license_codes'] ?? ''); ?>" placeholder="π.χ. 01.01, 78, 95">
                                        <p class="form-hint">Εισάγετε τους κωδικούς που αναγράφονται στη στήλη 12 του διπλώματος, χωρισμένους με κόμμα</p>
                                    </div>
                                </div>

                                <!-- Κατηγορίες Αδειών Οδήγησης με πίνακα -->
                                <h4>Κατηγορίες Αδειών Οδήγησης</h4>

                                <div class="license-categories-table">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Κατηγορία</th>
                                                <th>Περιγραφή</th>
                                                <th>Ενεργή</th>
                                                <th>Ημερομηνία Λήξης</th>
                                                <th>ΠΕΙ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Καθορισμός των κατηγοριών αδειών οδήγησης και ομαδοποίησή τους
                                            $licenseCategories = [
                                                'Δίκυκλα' => [
                                                    ['type' => 'AM', 'desc' => 'Μοτοποδήλατα', 'hasPei' => false],
                                                    ['type' => 'A1', 'desc' => 'Μοτοσυκλέτες έως 125 cc', 'hasPei' => false],
                                                    ['type' => 'A2', 'desc' => 'Μοτοσυκλέτες έως 35 kW', 'hasPei' => false],
                                                    ['type' => 'A', 'desc' => 'Μοτοσυκλέτες χωρίς περιορισμό', 'hasPei' => false]
                                                ],
                                                'Επιβατικά' => [
                                                    ['type' => 'B', 'desc' => 'Επιβατικά αυτοκίνητα', 'hasPei' => false],
                                                    ['type' => 'BE', 'desc' => 'Επιβατικά με ρυμουλκούμενο', 'hasPei' => false]
                                                ],
                                                'Φορτηγά' => [
                                                    ['type' => 'C1', 'desc' => 'Φορτηγά < 7.5t', 'hasPei' => true, 'peiType' => 'c'],
                                                    ['type' => 'C1E', 'desc' => 'Φορτηγά < 7.5t με ρυμουλκούμενο', 'hasPei' => true, 'peiType' => 'c'],
                                                    ['type' => 'C', 'desc' => 'Φορτηγά > 7.5t', 'hasPei' => true, 'peiType' => 'c'],
                                                    ['type' => 'CE', 'desc' => 'Φορτηγά με ρυμουλκούμενο', 'hasPei' => true, 'peiType' => 'c']
                                                ],
                                                'Λεωφορεία' => [
                                                    ['type' => 'D1', 'desc' => 'Μικρά λεωφορεία', 'hasPei' => true, 'peiType' => 'd'],
                                                    ['type' => 'D1E', 'desc' => 'Μικρά λεωφορεία με ρυμουλκούμενο', 'hasPei' => true, 'peiType' => 'd'],
                                                    ['type' => 'D', 'desc' => 'Λεωφορεία', 'hasPei' => true, 'peiType' => 'd'],
                                                    ['type' => 'DE', 'desc' => 'Λεωφορεία με ρυμουλκούμενο', 'hasPei' => true, 'peiType' => 'd']
                                                ]
                                            ];

                                            // Βοηθητική συνάρτηση για την εύρεση ημερομηνίας λήξης κατηγορίας
                                            function getExpiryDateForLicenseType($licenses, $type)
                                            {
                                                foreach ($licenses as $license) {
                                                    if ($license['license_type'] === $type) {
                                                        return $license['expiry_date'] ?? '';
                                                    }
                                                }
                                                return '';
                                            }

                                            // Εμφάνιση των κατηγοριών αδειών
                                            foreach ($licenseCategories as $categoryName => $licenses) :
                                            ?>
                                                <tr class="category-header">
                                                    <td colspan="<?php echo $categoryName === 'Φορτηγά' || $categoryName === 'Λεωφορεία' ? '4' : '5'; ?>"><strong><?php echo $categoryName; ?></strong></td>
                                                    <?php if ($categoryName === 'Φορτηγά' || $categoryName === 'Λεωφορεία') : ?>
                                                        <td><strong>ΠΕΙ</strong></td>
                                                    <?php endif; ?>
                                                </tr>
                                                <?php foreach ($licenses as $license) : ?>
                                                    <tr>
                                                        <td>
                                                            <div class="license-type-icon">

                                                                <span><?php echo $license['type']; ?></span>
                                                            </div>
                                                        </td>
                                                        <td><?php echo $license['desc']; ?></td>
                                                        <td>
                                                            <label class="toggle-switch">
                                                                <input type="checkbox" name="license_types[]" value="<?php echo $license['type']; ?>" <?php echo (in_array($license['type'], $driverLicenseTypes)) ? 'checked' : ''; ?>>
                                                                <span class="toggle-slider"></span>
                                                            </label>
                                                        </td>
                                                        <td>
                                                            <input type="date" name="license_expiry[<?php echo $license['type']; ?>]" value="<?php echo old('license_expiry[' . $license['type'] . ']', getExpiryDateForLicenseType($driverLicenses, $license['type'])); ?>">
                                                        </td>
                                                        <td>
                                                            <?php if ($license['hasPei']) : ?>
                                                                <div class="pei-field">
                                                                    <label class="checkbox-label">
                                                                        <input type="checkbox" name="has_pei_<?php echo strtolower($license['type']); ?>" value="1" <?php echo (in_array($license['type'], $driverPEI)) ? 'checked' : ''; ?>>
                                                                        <span class="checkmark"></span>
                                                                    </label>
                                                                    <input type="date" name="pei_<?php echo $license['peiType']; ?>_expiry" value="<?php echo old('pei_' . $license['peiType'] . '_expiry', ${$license['peiType'] == 'c' ? 'peiCExpiryDate' : 'peiDExpiryDate'} ?? ''); ?>" <?php echo (in_array($license['type'], $driverPEI)) ? '' : 'disabled'; ?> class="pei-expiry-date">
                                                                </div>
                                                            <?php else : ?>
                                                                — <!-- Δεν υπάρχει ΠΕΙ για αυτή την κατηγορία -->
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Ενημερωτικό μήνυμα για ανανέωση -->
                                <div class="expiry-reminder">
                                    <h4>Πληροφορίες για την ανανέωση</h4>
                                    <p>Η ανανέωση της άδειας οδήγησης μπορεί να γίνει στο χρονικό διάστημα δύο μηνών πριν την λήξη και το ΠΕΙ ενός έτους πριν την λήξη.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab για Πιστοποιητικά ADR -->

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
                                <?php // Κάρτες εικόνων: κοινό partial (κλικ = επιλογή, live preview)
                                $docImages = [
                                    ['id' => 'license_front_image', 'label' => 'Εμπρόσθια Όψη Διπλώματος', 'scan_id' => 'scan-license-front'],
                                    ['id' => 'license_back_image', 'label' => 'Οπίσθια Όψη Διπλώματος', 'scan_id' => 'scan-license-back']
                                ];
                                include __DIR__ . '/_doc-upload.php';
                                ?>

                                <!-- Βασικές πληροφορίες άδειας: μία γραμμή, 3 στήλες -->
                                <div class="license-basic-info">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="license_number">Αριθμός Άδειας Οδήγησης</label>
                                            <input type="text" id="license_number" name="license_number" value="<?php echo old('license_number', $driverData['license_number'] ?? ''); ?>" placeholder="π.χ. 123456789">
                                            <p class="form-hint">Πεδίο 5 της άδειας</p>
                                        </div>

                                        <div class="form-group">
                                            <label for="license_document_expiry">Λήξη Εντύπου Άδειας</label>
                                            <input type="date" id="license_document_expiry" name="license_document_expiry" value="<?php echo old('license_document_expiry', $driverData['license_document_expiry'] ?? ''); ?>">
                                            <p class="form-hint">Πεδίο 4β της άδειας</p>
                                        </div>

                                        <div class="form-group">
                                            <label for="license_codes">Κωδικοί Περιορισμών (Στήλη 12)</label>
                                            <input type="text" id="license_codes" name="license_codes" value="<?php echo old('license_codes', $driverData['license_codes'] ?? ''); ?>" placeholder="π.χ. 01.01, 78, 95">
                                            <p class="form-hint">Χωρισμένοι με κόμμα</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Κατηγορίες Αδειών Οδήγησης με πίνακα -->
                                <h4>Κατηγορίες Αδειών Οδήγησης</h4>

                                <?php /* 25/08: μία στήλη-«κατεβατό» → πλέγμα 2 στηλών: κάθε
                                   ομάδα (Δίκυκλα/Επιβατικά/Φορτηγά/Λεωφορεία) δικό της κουτί.
                                   Οι ομάδες χωρίς ΠΕΙ δεν κουβαλούν άδεια στήλη ΠΕΙ. */ ?>
                                <div class="license-cats-grid">
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

                                            // Εμφάνιση: ένα κουτί ανά ομάδα, σε πλέγμα 2 στηλών
                                            foreach ($licenseCategories as $categoryName => $licenses) :
                                                $groupHasPei = $licenses[0]['hasPei'];
                                            ?>
                                                <div class="license-cat">
                                                    <h5><?php echo $categoryName; ?></h5>
                                                    <table>
                                                        <thead>
                                                            <tr>
                                                                <th>Κατηγ.</th>
                                                                <th>Περιγραφή</th>
                                                                <th>Ενεργή</th>
                                                                <th>Λήξη</th>
                                                                <?php if ($groupHasPei) : ?><th>ΠΕΙ</th><?php endif; ?>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
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
                                                                    <?php if ($groupHasPei) : ?>
                                                                        <td>
                                                                            <div class="pei-field">
                                                                                <label class="checkbox-label">
                                                                                    <input type="checkbox" name="has_pei_<?php echo strtolower($license['type']); ?>" value="1" <?php echo (in_array($license['type'], $driverPEI)) ? 'checked' : ''; ?>>
                                                                                    <span class="checkmark"></span>
                                                                                </label>
                                                                                <input type="date" name="pei_<?php echo $license['peiType']; ?>_expiry" value="<?php echo old('pei_' . $license['peiType'] . '_expiry', ${$license['peiType'] == 'c' ? 'peiCExpiryDate' : 'peiDExpiryDate'} ?? ''); ?>" <?php echo (in_array($license['type'], $driverPEI)) ? '' : 'disabled'; ?> class="pei-expiry-date">
                                                                            </div>
                                                                        </td>
                                                                    <?php endif; ?>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php endforeach; ?>
                                </div><!-- /.license-cats-grid -->

                                <?php
                                // Υπενθυμίσεις ΜΟΝΟ όταν πλησιάζει η λήξη (όχι μόνιμα μπλοκ):
                                // έντυπο άδειας → 2 μήνες, ΠΕΙ → 1 έτος πριν.
                                

                                echo \Drivejob\Helpers\RenewalAlerts::render(
                                    $driverData['license_document_expiry'] ?? null,
                                    \Drivejob\Helpers\RenewalAlerts::WINDOW_LICENSE,
                                    'Το έντυπο της άδειας οδήγησης',
                                    'Η ανανέωση γίνεται ηλεκτρονικά: ' . \Drivejob\Helpers\RenewalAlerts::link(\Drivejob\Helpers\RenewalAlerts::URL_LICENSE, 'gov.gr — Ανανέωση άδειας οδήγησης')
                                );
                                echo \Drivejob\Helpers\RenewalAlerts::render(
                                    $peiCExpiryDate ?? null,
                                    \Drivejob\Helpers\RenewalAlerts::WINDOW_PEI,
                                    'Το ΠΕΙ εμπορευμάτων (C)',
                                    'Απαιτείται περιοδική κατάρτιση 35 ωρών σε σχολή ΠΕΙ. ' . \Drivejob\Helpers\RenewalAlerts::schoolSearchLink('σχολή ΠΕΙ περιοδική κατάρτιση', $driverData['city'] ?? null)
                                );
                                echo \Drivejob\Helpers\RenewalAlerts::render(
                                    $peiDExpiryDate ?? null,
                                    \Drivejob\Helpers\RenewalAlerts::WINDOW_PEI,
                                    'Το ΠΕΙ επιβατών (D)',
                                    'Απαιτείται περιοδική κατάρτιση 35 ωρών σε σχολή ΠΕΙ. ' . \Drivejob\Helpers\RenewalAlerts::schoolSearchLink('σχολή ΠΕΙ περιοδική κατάρτιση', $driverData['city'] ?? null)
                                );
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Tab για Πιστοποιητικά ADR -->

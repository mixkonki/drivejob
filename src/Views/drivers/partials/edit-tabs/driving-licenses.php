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
                                <?php /* 25/08: εικόνες διπλώματος ως κλικαμπλ κάρτες με
                                   προεπισκόπηση (ίδιο μοτίβο με το avatar) — το κλικ πάνω
                                   στην κάρτα ανοίγει τον επιλογέα αρχείου. */ ?>
                                <div class="doc-upload-grid">
                                    <?php
                                    $licenseImages = [
                                        ['id' => 'license_front_image', 'label' => 'Εμπρόσθια Όψη Διπλώματος', 'scan_id' => 'scan-license-front'],
                                        ['id' => 'license_back_image', 'label' => 'Οπίσθια Όψη Διπλώματος', 'scan_id' => 'scan-license-back']
                                    ];

                                    foreach ($licenseImages as $image) :
                                        $hasImg = !empty($driverData[$image['id']]);
                                    ?>
                                        <div class="doc-upload">
                                            <span class="doc-upload-title"><?php echo $image['label']; ?></span>
                                            <label class="doc-drop <?php echo $hasImg ? 'has-image' : ''; ?>" for="<?php echo $image['id']; ?>" title="Κλικ για επιλογή εικόνας">
                                                <img class="doc-preview"
                                                     src="<?php echo $hasImg ? BASE_URL . htmlspecialchars($driverData[$image['id']]) : ''; ?>"
                                                     alt="<?php echo $image['label']; ?>"
                                                     <?php echo $hasImg ? '' : 'style="display:none;"'; ?>
                                                     onerror="this.style.display='none';var p=this.parentElement.querySelector('.doc-placeholder');if(p)p.style.display='';">
                                                <span class="doc-placeholder" <?php echo $hasImg ? 'style="display:none;"' : ''; ?>>
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                                                    Κλικ για προσθήκη εικόνας
                                                </span>
                                                <span class="doc-change-overlay">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                                    Αλλαγή
                                                </span>
                                            </label>
                                            <input type="file" id="<?php echo $image['id']; ?>" name="<?php echo $image['id']; ?>" accept="image/jpeg, image/png, image/gif" class="doc-file-input">
                                            <button type="button" id="<?php echo $image['scan_id']; ?>" class="btn-scan">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M7 21H5a2 2 0 0 1-2-2v-2"/><line x1="7" y1="12" x2="17" y2="12"/></svg>
                                                Σκανάρισμα με OCR
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

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

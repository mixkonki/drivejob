<?php /* Καρτέλα «Άδειες Χειριστή ΜΕ» — v2 (25/08/2026).
   ΞΑΝΑΓΡΑΦΤΗΚΕ από το μηδέν μετά το feedback του Κώστα (εκπαιδευτή
   χειριστών): ο κάτοχος έχει ΕΝΑ βιβλιάριο (Αρ. Μητρώου, κοινή θεώρηση)
   με ΠΟΛΛΕΣ άδειες μέσα — κάθε άδεια = Ομάδα (Α΄/Β΄) + Ειδικότητα (1η-9η)
   + αριθμός + ημ. χορήγησης, και καλύπτει είτε ΤΟ ΣΥΝΟΛΟ της ειδικότητας
   είτε συγκεκριμένες υποειδικότητες. Χωρίς OCR: το βιβλιάριο είναι
   χειρόγραφο έντυπο — το σκανάρισμα δεν βγάζει τίποτα αξιόπιστο.
   Περιμένει: $driverOperatorLicenses (λίστα), $driverData. */ ?>
                    <div class="tab-pane" id="operator-licenses">
                        <h2>Άδειες Χειριστή Μηχανημάτων Έργου</h2>

                        <div class="license-section">
                            <div class="form-group checkbox-group">
                                <label for="operator_license" class="checkbox-label">
                                    <input type="checkbox" id="operator_license" name="operator_license" value="1" <?php echo !empty($driverOperatorLicenses) ? 'checked' : ''; ?>>
                                    <span>Διαθέτω άδεια χειριστή μηχανημάτων έργου (ΠΔ 113/2012)</span>
                                </label>
                            </div>

                            <div id="operator_license_tab" class="license-details-tab <?php echo empty($driverOperatorLicenses) ? 'hidden' : ''; ?>">
                                <?php // Εικόνες βιβλιαρίου — κοινό partial ΧΩΡΙΣ κουμπιά OCR
                                $docImages = [
                                    ['id' => 'operator_front_image', 'label' => 'Εξώφυλλο Βιβλιαρίου (Αρ. Μητρώου)'],
                                    ['id' => 'operator_back_image', 'label' => 'Σελίδα Άδειας (σφραγίδα υπηρεσίας)']
                                ];
                                include __DIR__ . '/_doc-upload.php';
                                ?>

                                <?php
                                $opInspectionUntil = '';
                                $opOldestIssue = null;
                                foreach ($driverOperatorLicenses ?? [] as $opLic) {
                                    if (!empty($opLic['expiry_date']) && $opLic['expiry_date'] > $opInspectionUntil) {
                                        $opInspectionUntil = $opLic['expiry_date'];
                                    }
                                    if (!empty($opLic['issue_date']) && ($opOldestIssue === null || $opLic['issue_date'] < $opOldestIssue)) {
                                        $opOldestIssue = $opLic['issue_date'];
                                    }
                                }
                                $opWindow = \Drivejob\Helpers\OperatorSpecialities::inspectionWindow($opOldestIssue);
                                ?>

                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="operator_registry_number">Αριθμός Μητρώου Βιβλιαρίου</label>
                                        <input type="text" id="operator_registry_number" name="operator_registry_number" value="<?php echo old('operator_registry_number', $driverData['operator_registry_number'] ?? ''); ?>" placeholder="π.χ. 4881">
                                        <p class="form-hint">Εξώφυλλο βιβλιαρίου — ένας ανά κάτοχο</p>
                                    </div>

                                    <div class="form-group">
                                        <label for="operator_inspection_until">Η Ισχύς Παρατείνεται Μέχρι (Θεώρηση)</label>
                                        <input type="date" id="operator_inspection_until" name="operator_inspection_until" value="<?php echo old('operator_inspection_until', $opInspectionUntil); ?>">
                                        <p class="form-hint">Πρώτη θεώρηση στη 12ετία, επόμενες ανά 8ετία (Ν.5203/2025)<?php
                                            if ($opWindow && !$opInspectionUntil) {
                                                echo ' — εκτίμηση προθεσμίας: ' . date('d/m/Y', strtotime($opWindow['start'])) . ' έως ' . date('d/m/Y', strtotime($opWindow['end']));
                                            }
                                        ?></p>
                                    </div>
                                </div>

                                <?php
                                // Υπενθύμιση θεώρησης ΜΟΝΟ όταν πλησιάζει (6 μήνες πριν).
                                // Δεν αφορά όσους εργάζονται αποκλειστικά ως μισθωτοί.
                                echo \Drivejob\Helpers\RenewalAlerts::render(
                                    $opInspectionUntil ?: null,
                                    \Drivejob\Helpers\RenewalAlerts::WINDOW_OPERATOR,
                                    'Η θεώρηση του βιβλιαρίου χειριστή',
                                    'Η θεώρηση γίνεται στη Διεύθυνση Ανάπτυξης της Περιφέρειας εντός του πρώτου εξαμήνου μετά τη λήξη. Δεν απαιτείται για όσους απασχολούνται αποκλειστικά με εξαρτημένη σχέση εργασίας.'
                                );
                                ?>

                                <h4>Οι Άδειές μου</h4>
                                <p class="form-info">Μία εγγραφή για κάθε άδεια του βιβλιαρίου — Ομάδα και Ειδικότητα, όπως αναγράφονται στη σελίδα με τη σφραγίδα (π.χ. «ΟΜΑΔΑΣ Α΄ 2ης ΕΙΔΙΚΟΤΗΤΑΣ»). Μπορείς να προσθέσεις όσες άδειες κατέχεις, από διαφορετικές ειδικότητες και ομάδες.</p>

                                <?php
                                /**
                                 * Ένα μπλοκ άδειας. Καλείται και για τις υπάρχουσες εγγραφές
                                 * και (με $idx='__IDX__') για το JS template — ΕΝΑ markup,
                                 * όχι δύο αντίγραφα που ξεσυγχρονίζονται.
                                 */
                                $renderOpLicBlock = function ($idx, array $lic = []) {
                                    $spec = (string) ($lic['speciality'] ?? '');
                                    $group = strtoupper((string) ($lic['group_type'] ?? 'A'));
                                    $coversAll = !empty($lic['covers_all']);
                                    $subs = array_map(
                                        static fn($s) => is_array($s) ? ($s['sub_speciality'] ?? '') : $s,
                                        $lic['sub_specialities'] ?? []
                                    );
                                    $subsJson = htmlspecialchars(json_encode(array_values(array_filter($subs))), ENT_QUOTES, 'UTF-8');
                                ?>
                                <div class="op-lic-item form-section" data-idx="<?php echo $idx; ?>" data-selected-subs="<?php echo $subsJson; ?>">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Ειδικότητα</label>
                                            <select name="op_lic[<?php echo $idx; ?>][speciality]" class="op-speciality">
                                                <option value="">Επιλέξτε</option>
                                                <?php foreach (\Drivejob\Helpers\OperatorSpecialities::SPECIALITIES as $sid => $sname) : ?>
                                                    <option value="<?php echo $sid; ?>" <?php echo $spec === (string) $sid ? 'selected' : ''; ?>><?php echo $sid . 'η — ' . $sname; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label>Ομάδα</label>
                                            <select name="op_lic[<?php echo $idx; ?>][group]">
                                                <?php foreach (\Drivejob\Helpers\OperatorSpecialities::GROUP_LABELS as $gid => $glabel) : ?>
                                                    <option value="<?php echo $gid; ?>" <?php echo $group === $gid ? 'selected' : ''; ?>><?php echo $glabel; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label>Αριθμός Άδειας</label>
                                            <input type="text" name="op_lic[<?php echo $idx; ?>][number]" value="<?php echo htmlspecialchars((string) ($lic['license_number'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="π.χ. 22244">
                                        </div>

                                        <div class="form-group">
                                            <label>Ημερομηνία Χορήγησης</label>
                                            <input type="date" name="op_lic[<?php echo $idx; ?>][issue_date]" value="<?php echo htmlspecialchars((string) ($lic['issue_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        </div>
                                    </div>

                                    <div class="form-group op-coverage">
                                        <label>Η άδεια αφορά:</label>
                                        <label class="radio-label"><input type="radio" name="op_lic[<?php echo $idx; ?>][covers_all]" value="1" <?php echo $coversAll ? 'checked' : ''; ?>> <span>Το σύνολο των μηχανημάτων της ειδικότητας</span></label>
                                        <label class="radio-label"><input type="radio" name="op_lic[<?php echo $idx; ?>][covers_all]" value="0" <?php echo $coversAll ? '' : 'checked'; ?>> <span>Συγκεκριμένα μηχανήματα (επιλογή παρακάτω)</span></label>
                                    </div>

                                    <div class="op-sub-wrap" <?php echo $coversAll ? 'style="display:none;"' : ''; ?>>
                                        <div class="op-sub-list skills-category"><!-- γεμίζει από JS βάσει ειδικότητας --></div>
                                    </div>

                                    <button type="button" class="btn-secondary op-lic-remove">Αφαίρεση άδειας</button>
                                </div>
                                <?php };

                                foreach (array_values($driverOperatorLicenses ?? []) as $i => $opLic) {
                                    $renderOpLicBlock($i, $opLic);
                                }
                                ?>

                                <div id="opLicList"><!-- εδώ προστίθενται νέα μπλοκ --></div>

                                <button type="button" id="addOpLic" class="btn-secondary">+ Προσθήκη άδειας</button>

                                <template id="opLicTemplate">
                                    <?php $renderOpLicBlock('__IDX__'); ?>
                                </template>

                                <script>
                                    // Ο επίσημος κατάλογος (9 ειδικότητες, ΥΑ 1032/166/2013 όπως
                                    // ισχύει) — μία πηγή αλήθειας: OperatorSpecialities helper.
                                    window.djOperatorCatalog = <?php echo json_encode(\Drivejob\Helpers\OperatorSpecialities::SUB_SPECIALITIES, JSON_UNESCAPED_UNICODE); ?>;
                                </script>
                                <?php echo \Drivejob\Helpers\Asset::js('js/operator-licenses.js', true); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Επόμενη καρτέλα -->

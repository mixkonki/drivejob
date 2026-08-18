<?php /* Καρτέλα «operator-licenses» της φόρμας επεξεργασίας προφίλ — αποσπάστηκε από το edit-profile.php (Πακέτο 5.4).
   Μοιράζεται το scope μεταβλητών του γονικού view (include). */ ?>
                    <div class="tab-pane" id="operator-licenses">
                        <h2>Άδειες Χειριστή Μηχανημάτων Έργου</h2>

                        <div class="license-section">
                            <div class="form-group checkbox-group">
                                <label for="operator_license" class="checkbox-label">
                                    <input type="checkbox" id="operator_license" name="operator_license" value="1" <?php echo (isset($driverOperator) && $driverOperator) ? 'checked' : ''; ?>>
                                    <span>Διαθέτω άδεια χειριστή μηχανημάτων έργου</span>
                                </label>
                            </div>

                            <div id="operator_license_tab" class="license-details-tab <?php echo (!isset($driverOperator) || !$driverOperator) ? 'hidden' : ''; ?>">
                                <!-- Εικόνες άδειας χειριστή και σκανάρισμα -->
                                <div class="license-visual">
                                    <?php
                                    $operatorImages = [
                                        ['id' => 'operator_front_image', 'label' => 'Εμπρόσθια Όψη Άδειας Χειριστή', 'scan_id' => 'scan-operator-front'],
                                        ['id' => 'operator_back_image', 'label' => 'Οπίσθια Όψη Άδειας Χειριστή', 'scan_id' => 'scan-operator-back']
                                    ];

                                    foreach ($operatorImages as $image) :
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

                                <!-- Βασικές πληροφορίες άδειας χειριστή -->
                                <div class="license-basic-info">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="operator_license_number">Αριθμός Άδειας Χειριστή</label>
                                            <input type="text" id="operator_license_number" name="operator_license_number" value="<?php echo old('operator_license_number', $driverOperator['license_number'] ?? ''); ?>" placeholder="π.χ. ΧΜΕ-1234">
                                        </div>

                                        <div class="form-group">
                                            <label for="operator_license_expiry">Ημερομηνία Θεώρησης</label>
                                            <input type="date" id="operator_license_expiry" name="operator_license_expiry" value="<?php echo old('operator_license_expiry', isset($driverOperator) && $driverOperator ? $driverOperator['expiry_date'] : ''); ?>">
                                            <p class="form-hint">Οι άδειες χειριστή μηχανημάτων έργου είναι αορίστου διάρκειας και θεωρούνται κάθε έντεκα (11) έτη.</p>
                                        </div>
                                    </div>
                                </div>

                                <h4>Επιλογή Ειδικότητας και Υποειδικοτήτων</h4>

                                <div class="form-group">
                                    <label for="operator_speciality">Επιλέξτε Ειδικότητα</label>
                                    <select id="operator_speciality" name="operator_speciality" onchange="loadSubSpecialities(this.value)">
                                        <option value="">Επιλέξτε</option>
                                        <?php
                                        $specialities = [
                                            '1' => 'Εργασίες εκσκαφής και χωματουργικές',
                                            '2' => 'Εργασίες ανύψωσης και μεταφοράς φορτίων',
                                            '3' => 'Εργασίες οδοστρωσίας',
                                            '4' => 'Εργασίες εξυπηρέτησης οδών και αεροδρομίων',
                                            '5' => 'Εργασίες υπόγειων έργων και μεταλλείων',
                                            '6' => 'Εργασίες έλξης',
                                            '7' => 'Εργασίες διάτρησης και κοπής εδαφών',
                                            '8' => 'Ειδικές εργασίες ανύψωσης'
                                        ];

                                        foreach ($specialities as $id => $name) :
                                        ?>
                                            <option value="<?php echo $id; ?>" <?php echo (isset($driverOperator) && $driverOperator && $driverOperator['speciality'] == $id) ? 'selected' : ''; ?>><?php echo $id; ?> - <?php echo $name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div id="subSpecialityContainer" class="form-group" style="display: <?php echo (isset($driverOperator) && $driverOperator && $driverOperator['speciality']) ? 'block' : 'none'; ?>;">
                                    <label>Επιλέξτε Υποειδικότητες</label>
                                    <div id="subSpecialities" class="sub-specialities">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th style="width: 15%">Κωδικός</th>
                                                    <th style="width: 50%">Υποειδικότητα</th>
                                                    <th style="width: 15%">Ενεργή</th>
                                                    <th style="width: 20%">Ομάδα</th>
                                                </tr>
                                            </thead>
                                            <tbody id="subSpecialitiesTableBody">
                                                <!-- Τα δεδομένα θα προστεθούν με JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Κρυφά πεδία για αποθήκευση επιλεγμένων υποειδικοτήτων και ομάδων -->
                                <input type="hidden" id="all_selected_subspecialities" name="all_selected_subspecialities" value="">
                                <input type="hidden" id="all_selected_groups" name="all_selected_groups" value="">

                                <!-- Εμφάνιση επιλεγμένων υποειδικοτήτων -->
                                <div class="selected-subspecialities">
                                    <h5>Επιλεγμένες Υποειδικότητες</h5>
                                    <?php if (isset($driverOperatorSubSpecialities) && !empty($driverOperatorSubSpecialities)) :
                                        // Ταξινόμηση των υποειδικοτήτων με βάση το ID
                                        usort($driverOperatorSubSpecialities, function ($a, $b) {
                                            $aSpecialityId = substr($a['sub_speciality'], 0, 1);
                                            $aSubId = substr($a['sub_speciality'], 2);

                                            $bSpecialityId = substr($b['sub_speciality'], 0, 1);
                                            $bSubId = substr($b['sub_speciality'], 2);

                                            if ($aSpecialityId == $bSpecialityId) {
                                                return intval($aSubId) - intval($bSubId);
                                            }

                                            return intval($aSpecialityId) - intval($bSpecialityId);
                                        });

                                        // Ομαδοποίηση ανά ειδικότητα
                                        $specialityGroups = [];
                                        foreach ($driverOperatorSubSpecialities as $subSpec) {
                                            $specialityId = substr($subSpec['sub_speciality'], 0, 1);
                                            if (!isset($specialityGroups[$specialityId])) {
                                                $specialityGroups[$specialityId] = [];
                                            }
                                            $specialityGroups[$specialityId][] = $subSpec;
                                        }

                                        // Ορισμός των ονομάτων ειδικοτήτων
                                        $specialityNames = $specialities;
                                    ?>
                                        <?php foreach ($specialityGroups as $specialityId => $subSpecialities) : ?>
                                            <div class="speciality-group">
                                                <h6><?php echo $specialityId . ' - ' . ($specialityNames[$specialityId] ?? 'Ειδικότητα ' . $specialityId); ?></h6>
                                                <ul class="selected-list">
                                                    <?php foreach ($subSpecialities as $subSpec) :
                                                        $subspecialityId = $subSpec['sub_speciality'];
                                                        $groupType = $subSpec['group_type'] ?? 'A';
                                                    ?>
                                                        <li>
                                                            <span class="subspeciality-id"><?php echo $subspecialityId; ?></span>
                                                            <span class="subspeciality-name"><?php echo $subSpec['name'] ?? "Υποειδικότητα {$subspecialityId}"; ?></span>
                                                            <span class="subspeciality-group">Ομάδα <?php echo $groupType; ?></span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <ul class="selected-list">
                                            <li class="no-items">Δεν έχουν επιλεγεί υποειδικότητες</li>
                                        </ul>
                                    <?php endif; ?>
                                </div>

                                <!-- Ενημερωτικό μήνυμα για άδεια χειριστή -->
                                <div class="expiry-reminder">
                                    <h4>Πληροφορίες για την Άδεια Χειριστή Μηχανημάτων Έργου</h4>
                                    <p>Οι άδειες χειριστή μηχανημάτων έργου είναι αόριστης διάρκειας και θεωρούνται κάθε οκτώ έτη. Με την παράγραφο 1 του άρθρου 145 Νόμος 4887 η προθεσμία θεώρησής των αδειών χειριστή μηχανημάτων έργου, μετά την παρέλευση οκτώ (8) ετών, παρατείνεται κατά τρία (3) έτη και άρα η θεώρηση πραγματοποιείτε στα έντεκα (11) έτη.</p>
                                    <p>Ως ημερομηνία έναρξης της ενδεκαετίας λαμβάνεται η 1η Ιανουαρίου του επόμενου έτους από τη χορήγηση ή την αντικατάσταση της άδειας χειριστή.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab για Κάρτα Ψηφιακού Ταχογράφου -->

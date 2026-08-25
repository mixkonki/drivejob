<?php /* Καρτέλα «qualifications» του προφίλ οδηγού — αποσπάστηκε από το driver-profile.php (Πακέτο 5.4).
   Μοιράζεται το scope μεταβλητών του γονικού view (include). */ ?>
            <!-- Καρτέλα Προσόντων & Πιστοποιήσεων -->
            <div class="tab-pane" id="qualifications">
                <!-- Προσωπικά Στοιχεία -->
                <div class="personal-details-section">
                    <h3>Προσωπικά Στοιχεία</h3>
                    <div class="skills-categories">
                        <!-- Ηλικία -->
                        <div class="skills-category">
                            <div class="category-header">
                                <div class="category-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                </div>
                                <h3>Ηλικία</h3>
                            </div>
                            <?php if (isset($driverData['birth_date']) && $driverData['birth_date']) :
                                $birthDate = new DateTime($driverData['birth_date']);
                                $now = new DateTime();
                                $age = $birthDate->diff($now)->y;
                            ?>
                                <div class="skill-tag"><?php echo $age; ?> ετών</div>
                            <?php else : ?>
                                <p class="no-skills">Δεν έχει καταχωρηθεί</p>
                            <?php endif; ?>
                        </div>

                        <!-- Οικογενειακή Κατάσταση -->
                        <div class="skills-category">
                            <div class="category-header">
                                <div class="category-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                </div>
                                <h3>Οικογενειακή Κατάσταση</h3>
                            </div>
                            <?php if (isset($driverData['marital_status']) && $driverData['marital_status']) :
                                $maritalStatus = [
                                    'single' => 'Άγαμος/η',
                                    'married' => 'Έγγαμος/η',
                                    'divorced' => 'Διαζευγμένος/η',
                                    'widowed' => 'Χήρος/α',
                                    'civil_partnership' => 'Σύμφωνο συμβίωσης',
                                    'no_answer' => 'Δεν απαντώ'
                                ];
                                $statusText = isset($maritalStatus[$driverData['marital_status']]) ?
                                    $maritalStatus[$driverData['marital_status']] : $driverData['marital_status'];
                            ?>
                                <div class="skill-tag"><?php echo $statusText; ?></div>
                            <?php else : ?>
                                <p class="no-skills">Δεν έχει καταχωρηθεί</p>
                            <?php endif; ?>
                        </div>

                        <!-- Επίπεδο Εκπαίδευσης -->
                        <div class="skills-category">
                            <div class="category-header">
                                <div class="category-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                                </div>
                                <h3>Επίπεδο Εκπαίδευσης</h3>
                            </div>
                            <?php if (isset($driverData['education_level']) && $driverData['education_level']) :
                                $educationLevels = [
                                    'primary' => 'Υποχρεωτική εκπαίδευση (Δημοτικό)',
                                    'secondary_low' => 'Υποχρεωτική εκπαίδευση (Γυμνάσιο)',
                                    'secondary_high' => 'Λύκειο',
                                    'vocational_low' => 'Επαγγελματική Εκπαίδευση (Γυμνάσιο)',
                                    'vocational' => 'Επαγγελματική Εκπαίδευση (Λύκειο)',
                                    'iek' => 'Ινστιτούτο Επαγγελματικής Κατάρισης (ΙΕΚ)',
                                    'tei' => 'Ανώτατο Τεχνολογικό Εκπαιδευτικό Ίδρυμα (ΑΤΕΙ)',
                                    'university' => 'Ανώτατο Εκπαιδευτικό Ίδρυμα (ΑΕΙ)',
                                    'postgraduate' => 'Μεταπτυχιακό',
                                    'doctorate' => 'Διδακτορικό',
                                    'no_answer' => 'Δεν απαντώ',
                                ];
                                $educationText = isset($educationLevels[$driverData['education_level']]) ?
                                    $educationLevels[$driverData['education_level']] : $driverData['education_level'];
                            ?>
                                <div class="skill-tag"><?php echo $educationText; ?></div>
                            <?php else : ?>
                                <p class="no-skills">Δεν έχει καταχωρηθεί</p>
                            <?php endif; ?>
                        </div>

                        <!-- Στρατιωτικές Υποχρεώσεις -->
                        <div class="skills-category">
                            <div class="category-header">
                                <div class="category-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                </div>
                                <h3>Στρατιωτικές Υποχρεώσεις</h3>
                            </div>
                            <?php if (isset($driverData['military_service']) && $driverData['military_service']) :
                                $militaryStatus = [
                                    'completed' => 'Εκπληρωμένες',
                                    'exempt' => 'Απαλλαγή',
                                    'postponed' => 'Αναβολή',
                                    'unfulfilled' => 'Μη εκπληρωμένες',
                                    'not_applicable' => 'Δεν απαιτείται',
                                    'no_answer' => 'Δεν απαντώ'
                                ];
                                $militaryText = isset($militaryStatus[$driverData['military_service']]) ?
                                    $militaryStatus[$driverData['military_service']] : $driverData['military_service'];
                            ?>
                                <div class="skill-tag"><?php echo $militaryText; ?></div>
                            <?php else : ?>
                                <p class="no-skills">Δεν έχει καταχωρηθεί</p>
                            <?php endif; ?>
                        </div>

                        <!-- Ποινικό Μητρώο -->
                        <div class="skills-category">
                            <div class="category-header">
                                <div class="category-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                                </div>
                                <h3>Ποινικό Μητρώο</h3>
                            </div>
                            <?php if (isset($driverData['legal_status']) && $driverData['legal_status']) : ?>
                                <div class="skill-tag"><?php echo ($driverData['legal_status'] == 'yes') ? 'Υπεύθυνη δήλωση λευκού μητρώου' : 'Όχι'; ?></div>
                            <?php else : ?>
                                <p class="no-skills">Δεν είναι διαθέσιμο</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="driver-skills-section">
                    <h3>Δεξιότητες Οδηγού</h3>
                    <div class="skills-summary">
                        <div class="skills-categories">
                            <!-- Οδηγικές Ικανότητες -->
                            <div class="skills-category">
                                <div class="category-header">
                                    <div class="category-icon">
                                        <img src="<?= \Drivejob\Helpers\Asset::url('img/driving_skills_icon.png') ?>" alt="Οδηγικές Ικανότητες">
                                    </div>
                                    <h3>Οδηγικές Ικανότητες</h3>
                                </div>

                                <?php
                                $drivingSkills = [
                                    'defensive_driving' => 'Αμυντική Οδήγηση',
                                    'eco_driving' => 'Οικολογική Οδήγηση',
                                    'night_driving' => 'Νυχτερινή Οδήγηση',
                                    'mountain_driving' => 'Οδήγηση σε Ορεινές Περιοχές',
                                    'extreme_conditions' => 'Οδήγηση σε Ακραίες Συνθήκες',
                                    'precision_handling' => 'Ακρίβεια Χειρισμών'
                                ];

                                $hasDrivingSkills = false;
                                foreach ($drivingSkills as $skill => $label) {
                                    if (isset($driverSkills[$skill]) && $driverSkills[$skill]) {
                                        $hasDrivingSkills = true;
                                        break;
                                    }
                                }

                                if ($hasDrivingSkills) :
                                    foreach ($drivingSkills as $skill => $label) :
                                        if (isset($driverSkills[$skill]) && $driverSkills[$skill]) :
                                ?>
                                            <div class="skill-tag"><?php echo $label; ?></div>
                                    <?php
                                        endif;
                                    endforeach;
                                else :
                                    ?>
                                    <p class="no-skills">Δεν έχουν καταχωρηθεί δεξιότητες</p>
                                <?php endif; ?>
                            </div>

                            <!-- Ασφάλεια & Συμμόρφωση -->
                            <div class="skills-category">
                                <div class="category-header">
                                    <div class="category-icon">
                                        <img src="<?= \Drivejob\Helpers\Asset::url('img/safety_icon.png') ?>" alt="Ασφάλεια & Συμμόρφωση">
                                    </div>
                                    <h3>Ασφάλεια & Συμμόρφωση</h3>
                                </div>

                                <?php
                                $safetySkills = [
                                    'loading_securing' => 'Φόρτωση & Ασφάλιση Φορτίου',
                                    'emergency_response' => 'Αντιμετώπιση Έκτακτων Καταστάσεων',
                                    'first_aid' => 'Πρώτες Βοήθειες',
                                    'dangerous_goods' => 'Διαχείριση Επικίνδυνων Εμπορευμάτων',
                                    'tacograph_compliance' => 'Συμμόρφωση με Ταχογράφο',
                                    'fire_safety' => 'Πυρασφάλεια',
                                    'vehicle_inspection' => 'Έλεγχος Οχημάτων'
                                ];

                                $hasSafetySkills = false;
                                foreach ($safetySkills as $skill => $label) {
                                    if (isset($driverSkills[$skill]) && $driverSkills[$skill]) {
                                        $hasSafetySkills = true;
                                        break;
                                    }
                                }

                                if ($hasSafetySkills) :
                                    foreach ($safetySkills as $skill => $label) :
                                        if (isset($driverSkills[$skill]) && $driverSkills[$skill]) :
                                ?>
                                            <div class="skill-tag"><?php echo $label; ?></div>
                                    <?php
                                        endif;
                                    endforeach;
                                else :
                                    ?>
                                    <p class="no-skills">Δεν έχουν καταχωρηθεί δεξιότητες</p>
                                <?php endif; ?>
                            </div>

                            <!-- Επαγγελματισμός -->
                            <div class="skills-category">
                                <div class="category-header">
                                    <div class="category-icon">
                                        <img src="<?= \Drivejob\Helpers\Asset::url('img/professionalism_icon.png') ?>" alt="Επαγγελματισμός">
                                    </div>
                                    <h3>Επαγγελματισμός</h3>
                                </div>

                                <?php
                                $professionalSkills = [
                                    'customer_service' => 'Εξυπηρέτηση Πελατών',
                                    'time_management' => 'Διαχείριση Χρόνου',
                                    'route_planning' => 'Σχεδιασμός Διαδρομής',
                                    'conflict_resolution' => 'Επίλυση Συγκρούσεων',
                                    'multilingual' => 'Πολύγλωσσος',
                                    'report_writing' => 'Σύνταξη Αναφορών',
                                    'inspection_behavior' => 'Συμπεριφορά σε Έλεγχο',
                                    'border_crossing' => 'Διέλευση Συνόρων'
                                ];

                                $hasProfessionalSkills = false;
                                foreach ($professionalSkills as $skill => $label) {
                                    if (isset($driverSkills[$skill]) && $driverSkills[$skill]) {
                                        $hasProfessionalSkills = true;
                                        break;
                                    }
                                }

                                if ($hasProfessionalSkills) :
                                    foreach ($professionalSkills as $skill => $label) :
                                        if (isset($driverSkills[$skill]) && $driverSkills[$skill]) :
                                ?>
                                            <div class="skill-tag"><?php echo $label; ?></div>
                                    <?php
                                        endif;
                                    endforeach;
                                else :
                                    ?>
                                    <p class="no-skills">Δεν έχουν καταχωρηθεί δεξιότητες</p>
                                <?php endif; ?>
                            </div>

                            <!-- Τεχνικές Γνώσεις -->
                            <div class="skills-category">
                                <div class="category-header">
                                    <div class="category-icon">
                                        <img src="<?= \Drivejob\Helpers\Asset::url('img/technical_icon.png') ?>" alt="Τεχνικές Γνώσεις">
                                    </div>
                                    <h3>Τεχνικές Γνώσεις</h3>
                                </div>

                                <?php
                                $technicalSkills = [
                                    'vehicle_maintenance' => 'Συντήρηση Οχήματος',
                                    'troubleshooting' => 'Αντιμετώπιση Βλαβών',
                                    'digital_tachograph' => 'Ψηφιακός Ταχογράφος',
                                    'gps_systems' => 'Συστήματα GPS',
                                    'logistics_software' => 'Λογισμικό Logistics',
                                    'technical_terms' => 'Γνώση Τεχνικών Όρων',
                                    'equipment_handling' => 'Γνώση Χειρισμού Εξοπλισμού',
                                    'checklists_usage' => 'Χρήση Λιστών Ελέγχου'
                                ];

                                $hasTechnicalSkills = false;
                                foreach ($technicalSkills as $skill => $label) {
                                    if (isset($driverSkills[$skill]) && $driverSkills[$skill]) {
                                        $hasTechnicalSkills = true;
                                        break;
                                    }
                                }

                                if ($hasTechnicalSkills) :
                                    foreach ($technicalSkills as $skill => $label) :
                                        if (isset($driverSkills[$skill]) && $driverSkills[$skill]) :
                                ?>
                                            <div class="skill-tag"><?php echo $label; ?></div>
                                    <?php
                                        endif;
                                    endforeach;
                                else :
                                    ?>
                                    <p class="no-skills">Δεν έχουν καταχωρηθεί δεξιότητες</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (isset($driverData['additional_skills']) && $driverData['additional_skills']) : ?>
                            <div class="additional-skills">
                                <h3>Επιπλέον Δεξιότητες</h3>
                                <div class="additional-skills-content">
                                    <?php echo nl2br(htmlspecialchars($driverData['additional_skills'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php /* Προϋπηρεσία + Γλώσσες δίπλα-δίπλα όταν χωράει η οθόνη (25/08) */ ?>
                <div class="profile-two-col">
                <!-- Προϋπηρεσία σε Οχήματα -->
                <?php if (isset($driverVehicleExperience) && !empty($driverVehicleExperience)) : ?>
                    <div class="vehicle-experience-section">
                        <h3><svg class="section-h3-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg> Προϋπηρεσία σε Οχήματα</h3>
                        <div class="vehicle-experience-list">
                            <?php foreach ($driverVehicleExperience as $exp) : ?>
                                <div class="vehicle-experience-item">
                                    <div class="vehicle-experience-header">
                                        <div class="vehicle-title">
                                            <?php
                                            // Κατηγορίες οχημάτων
                                            $vehicleCategories = [
                                                'lcv' => 'Ελαφρά Επαγγελματικά Οχήματα',
                                                'rigid_truck' => 'Μεσαία & Βαρέα Φορτηγά',
                                                'articulated' => 'Αρθρωτά/Συρόμενα Οχήματα',
                                                'taxi' => 'Ταξί',
                                                'minibus' => 'Μικρό Λεωφορείο',
                                                'bus' => 'Λεωφορεία & Πούλμαν',
                                                'utility' => 'Οχήματα Δημοτικά/Κοινής Ωφέλειας',
                                                'construction' => 'Οχήματα Έργων/Κατασκευών',
                                                'emergency' => 'Οχήματα Έκτακτης Ανάγκης',
                                                'specialized' => 'Εξειδικευμένα Οχήματα'
                                            ];

                                            $categoryName = isset($vehicleCategories[$exp['vehicle_category']]) ? $vehicleCategories[$exp['vehicle_category']] : $exp['vehicle_category'];
                                            echo $categoryName;

                                            if (!empty($exp['vehicle_type_name'])) {
                                                echo ' - ' . htmlspecialchars($exp['vehicle_type_name']);
                                            } elseif (!empty($exp['vehicle_type'])) {
                                                echo ' - ' . htmlspecialchars($exp['vehicle_type']);
                                            }
                                            ?>
                                        </div>
                                        <div class="vehicle-years"><?php echo $exp['years']; ?> <?php echo $exp['years'] == 1 ? 'έτος' : 'έτη'; ?></div>
                                    </div>

                                    <?php if (!empty($exp['start_date']) || !empty($exp['end_date'])) : ?>
                                        <div class="vehicle-period">
                                            Περίοδος:
                                            <?php
                                            echo !empty($exp['start_date']) ? date('m/Y', strtotime($exp['start_date'])) : '-';
                                            echo ' έως ';
                                            echo !empty($exp['end_date']) ? date('m/Y', strtotime($exp['end_date'])) : 'σήμερα';
                                            ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($exp['description'])) : ?>
                                        <div class="vehicle-description">
                                            <?php echo nl2br(htmlspecialchars($exp['description'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="languages-section">
                    <h3><svg class="section-h3-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg> Γλωσσικές Ικανότητες</h3>
                    <div class="languages-list">
                        <?php
                        /*
                         * Από τον πίνακα driver_languages (25/08/2026) — μία
                         * γραμμή ανά γλώσσα, όσες γλώσσες θέλει ο οδηγός.
                         * Οι παλιές στήλες language_* συγχρονίζονται μόνο για
                         * το PDF/οπτικό βιογραφικό μέχρι το beta-cleanup.
                         */
                        $languageLevelLabels = [
                            'native' => 'Μητρική Γλώσσα',
                            'fluent' => 'Άριστα',
                            'good' => 'Καλά',
                            'basic' => 'Βασικά'
                        ];
                        ?>
                        <?php if (!empty($driverLanguages)) : ?>
                            <?php foreach ($driverLanguages as $lang) : ?>
                                <div class="language-item">
                                    <div class="language-name"><?php echo htmlspecialchars($lang['language_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="language-level <?php echo htmlspecialchars($lang['level'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo $languageLevelLabels[$lang['level']] ?? $lang['level']; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p class="not-available" style="color:#6b7280;">Δεν έχουν καταχωρηθεί γλώσσες —
                                πρόσθεσέ τες από την επεξεργασία προφίλ.</p>
                        <?php endif; ?>
                    </div>
                </div>
                </div><!-- /.profile-two-col -->

                <?php if (isset($driverCertifications) && !empty($driverCertifications)) : ?>
                    <div class="certifications-section">
                        <h3>Πιστοποιήσεις & Σεμινάρια</h3>
                        <div class="certifications-list">
                            <?php foreach ($driverCertifications as $cert) : ?>
                                <div class="certification-item">
                                    <div class="certification-header">
                                        <h4><?php echo htmlspecialchars($cert['title']); ?></h4>
                                        <?php if (isset($cert['expiry']) && $cert['expiry']) :
                                            $isExpired = strtotime($cert['expiry']) < time();
                                            $expiresInThreeMonths = !$isExpired && (strtotime($cert['expiry']) - time()) < 60 * 60 * 24 * 90;
                                        ?>
                                            <div class="certification-status <?php echo $isExpired ? 'expired' : ($expiresInThreeMonths ? 'expiring-soon' : 'valid'); ?>">
                                                <?php
                                                if ($isExpired) {
                                                    echo "Έχει λήξει";
                                                } elseif ($expiresInThreeMonths) {
                                                    echo "Λήγει σύντομα";
                                                } else {
                                                    echo "Σε ισχύ";
                                                }
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="certification-details">
                                        <?php if (isset($cert['provider']) && $cert['provider']) : ?>
                                            <div class="certification-provider">
                                                <strong>Πάροχος:</strong> <?php echo htmlspecialchars($cert['provider']); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="certification-dates">
                                            <?php if (isset($cert['date']) && $cert['date']) : ?>
                                                <div class="certification-date">
                                                    <strong>Ημ/νία Απόκτησης:</strong> <?php echo date('d/m/Y', strtotime($cert['date'])); ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (isset($cert['expiry']) && $cert['expiry']) : ?>
                                                <div class="certification-expiry">
                                                    <strong>Ημ/νία Λήξης:</strong> <?php echo date('d/m/Y', strtotime($cert['expiry'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (isset($cert['description']) && $cert['description']) : ?>
                                            <div class="certification-description">
                                                <?php echo nl2br(htmlspecialchars($cert['description'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($driverData['training_seminars']) && $driverData['training_seminars'] && isset($driverData['training_details']) && $driverData['training_details']) : ?>
                    <div class="training-section">
                        <h3>Εκπαιδευτικά Σεμινάρια</h3>
                        <div class="training-details">
                            <?php echo nl2br(htmlspecialchars($driverData['training_details'])); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="skills-actions">
                    <a href="<?php echo BASE_URL; ?>drivers/edit-profile#skills-tab" class="btn-primary">Επεξεργασία Προσόντων</a>
                </div>
            </div>


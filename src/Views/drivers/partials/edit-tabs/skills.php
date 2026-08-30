<?php /* Καρτέλα «skills» της φόρμας επεξεργασίας προφίλ — αποσπάστηκε από το edit-profile.php (Πακέτο 5.4).
   Μοιράζεται το scope μεταβλητών του γονικού view (include). */ ?>
            <div class="tab-pane" id="skills-tab">
                <h2>Προσόντα & Πιστοποιήσεις</h2>

                <!-- Δηλώνει στον server ότι η καρτέλα δεξιοτήτων ήρθε στο POST:
                     χωρίς αυτό δεν ξεχωρίζει το «κανένα τσεκαρισμένο» από το
                     «η καρτέλα δεν στάλθηκε», και θα μηδενίζονταν δεξιότητες
                     από φόρμες που δεν την περιέχουν. -->
                <input type="hidden" name="skills_submitted" value="1">

                <!-- Φόρμα δεξιοτήτων -->
                <div class="form-section">
                    <h3>Επαγγελματικές Δεξιότητες</h3>
                    <p class="form-info">Επιλέξτε τις δεξιότητες που διαθέτετε:</p>

                    <div class="skills-container">
                        <div class="skills-row">
                            <!-- Οδηγικές Ικανότητες -->
                            <div class="skills-column">
                                <div class="skills-category">
                                    <h4>Οδηγικές Ικανότητες</h4>

                                    <div class="skill-item">
                                        <label class="skill-label" for="defensive_driving">
                                            Αμυντική Οδήγηση (Defensive Driving)
                                        </label>
                                        <input type="checkbox" id="defensive_driving" name="skills[defensive_driving]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['defensive_driving']) && $driverSkills['defensive_driving'] ? 'checked' : ''; ?>>
                                    </div>

                                    <div class="skill-item">
                                        <label class="skill-label" for="eco_driving">
                                            Οικολογική Οδήγηση (Eco-Driving)
                                        </label>
                                        <input type="checkbox" id="eco_driving" name="skills[eco_driving]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['eco_driving']) && $driverSkills['eco_driving'] ? 'checked' : ''; ?>>
                                    </div>

                                    <div class="skill-item">
                                        <label class="skill-label" for="night_driving">
                                            Νυχτερινή Οδήγηση
                                        </label>
                                        <input type="checkbox" id="night_driving" name="skills[night_driving]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['night_driving']) && $driverSkills['night_driving'] ? 'checked' : ''; ?>>
                                    </div>

                                    <div class="skill-item">
                                        <label class="skill-label" for="mountain_driving">
                                            Οδήγηση σε Ορεινές Περιοχές
                                        </label>
                                        <input type="checkbox" id="mountain_driving" name="skills[mountain_driving]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['mountain_driving']) && $driverSkills['mountain_driving'] ? 'checked' : ''; ?>>
                                    </div>

                                    <div class="skill-item">
                                        <label class="skill-label" for="extreme_conditions">
                                            Οδήγηση σε Ακραίες Συνθήκες
                                        </label>
                                        <input type="checkbox" id="extreme_conditions" name="skills[extreme_conditions]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['extreme_conditions']) && $driverSkills['extreme_conditions'] ? 'checked' : ''; ?>>
                                    </div>

                                    <div class="skill-item">
                                        <label class="skill-label" for="precision_handling">
                                            Ακρίβεια χειρισμών
                                        </label>
                                        <input type="checkbox" id="precision_handling" name="skills[precision_handling]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['precision_handling']) && $driverSkills['precision_handling'] ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                            </div>

                            <!-- Ασφάλεια & Συμμόρφωση -->
                            <div class="skills-column">
                                <div class="skills-category">
                                    <h4>Ασφάλεια & Συμμόρφωση</h4>

                                    <div class="skill-item">
                                        <label class="skill-label" for="loading_securing">
                                            Φόρτωση & Ασφάλιση Φορτίου
                                            <span class="freight-only-tag">Εμπορευματικές</span>
                                        </label>
                                        <div class="freight-only">
                                            <input type="checkbox" id="freight_only_loading" name="freight_only[loading_securing]" value="1" checked>
                                        </div>
                                        <input type="checkbox" id="loading_securing" name="skills[loading_securing]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['loading_securing']) && $driverSkills['loading_securing'] ? 'checked' : ''; ?>>
                                    </div>

                                    <div class="skill-item">
                                        <label class="skill-label" for="dangerous_goods">
                                            Διαχείριση Επικίνδυνων Εμπορευμάτων
                                            <span class="freight-only-tag">Εμπορευματικές</span>
                                        </label>
                                        <div class="freight-only">
                                            <input type="checkbox" id="freight_only_dangerous" name="freight_only[dangerous_goods]" value="1" checked>
                                        </div>
                                        <input type="checkbox" id="dangerous_goods" name="skills[dangerous_goods]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['dangerous_goods']) && $driverSkills['dangerous_goods'] ? 'checked' : ''; ?>>
                                    </div>

                                    <div class="skill-item">
                                        <label class="skill-label" for="emergency_response">
                                            Αντιμετώπιση Έκτακτων Καταστάσεων
                                        </label>
                                        <input type="checkbox" id="emergency_response" name="skills[emergency_response]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['emergency_response']) && $driverSkills['emergency_response'] ? 'checked' : ''; ?>>
                                    </div>

                                    <div class="skill-item">
                                        <label class="skill-label" for="first_aid">
                                            Πρώτες Βοήθειες
                                        </label>
                                        <input type="checkbox" id="first_aid" name="skills[first_aid]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['first_aid']) && $driverSkills['first_aid'] ? 'checked' : ''; ?>>
                                    </div>

                                    <div class="skill-item">
                                        <label class="skill-label" for="fire_safety">
                                            Πυρασφάλεια
                                        </label>
                                        <input type="checkbox" id="fire_safety" name="skills[fire_safety]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['fire_safety']) && $driverSkills['fire_safety'] ? 'checked' : ''; ?>>
                                    </div>

                                    <div class="skill-item">
                                        <label class="skill-label" for="vehicle_inspection">
                                            Έλεγχος οχημάτων
                                        </label>
                                        <input type="checkbox" id="vehicle_inspection" name="skills[vehicle_inspection]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['vehicle_inspection']) && $driverSkills['vehicle_inspection'] ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                            </div>

                            <!-- Επαγγελματισμός -->
                            <div class="skills-column">
                                <div class="skills-category">
                                    <h4>Επαγγελματισμός</h4>

                                    <div class="skill-item">
                                        <label class="skill-label" for="customer_service">
                                            Εξυπηρέτηση Πελατών
                                        </label>
                                        <input type="checkbox" id="customer_service" name="skills[customer_service]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['customer_service']) && $driverSkills['customer_service'] ? 'checked' : ''; ?>>
                                    </div>

                                    <div class="skill-item">
                                        <label class="skill-label" for="time_management">
                                            Διαχείριση Χρόνου
                                        </label>
                                        <input type="checkbox" id="time_management" name="skills[time_management]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['time_management']) && $driverSkills['time_management'] ? 'checked' : ''; ?>>
                                    </div>

                                    <div class="skill-item">
                                        <label class="skill-label" for="route_planning">
                                            Σχεδιασμός Διαδρομής
                                        </label>
                                        <input type="checkbox" id="route_planning" name="skills[route_planning]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['route_planning']) && $driverSkills['route_planning'] ? 'checked' : ''; ?>>
                                    </div>

                                    <div class="skill-item">
                                        <label class="skill-label" for="conflict_resolution">
                                            Επίλυση Συγκρούσεων
                                        </label>
                                        <input type="checkbox" id="conflict_resolution" name="skills[conflict_resolution]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['conflict_resolution']) && $driverSkills['conflict_resolution'] ? 'checked' : ''; ?>>
                                    </div>

                                    <div class="skill-item">
                                        <label class="skill-label" for="report_writing">
                                            Σύνταξη αναφορών
                                        </label>
                                        <input type="checkbox" id="report_writing" name="skills[report_writing]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['report_writing']) && $driverSkills['report_writing'] ? 'checked' : ''; ?>>
                                    </div>

                                    <div class="skill-item">
                                        <label class="skill-label" for="multilingual">
                                            Πολύγλωσσος
                                        </label>
                                        <input type="checkbox" id="multilingual" name="skills[multilingual]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['multilingual']) && $driverSkills['multilingual'] ? 'checked' : ''; ?>>
                                    </div>

                                    <div class="skill-item">
                                        <label class="skill-label" for="inspection_behavior">
                                            Συμπεριφορά σε έλεγχο
                                        </label>
                                        <input type="checkbox" id="inspection_behavior" name="skills[inspection_behavior]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['inspection_behavior']) && $driverSkills['inspection_behavior'] ? 'checked' : ''; ?>>
                                    </div>

                                    <div class="skill-item">
                                        <label class="skill-label" for="border_crossing">
                                            Διέλευση συνόρων
                                        </label>
                                        <input type="checkbox" id="border_crossing" name="skills[border_crossing]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['border_crossing']) && $driverSkills['border_crossing'] ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                            </div>

                            <!-- Τεχνικές Γνώσεις -->
                            <div class="skills-column">
                                <div class="skills-category">
                                    <h4>Τεχνικές Γνώσεις</h4>

                                    <div class="skill-item">
                                        <label class="skill-label" for="vehicle_maintenance">
                                            Συντήρηση Οχήματος
                                        </label>
                                        <input type="checkbox" id="vehicle_maintenance" name="skills[vehicle_maintenance]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['vehicle_maintenance']) && $driverSkills['vehicle_maintenance'] ? 'checked' : ''; ?>>
                                    </div>

                                    <div class="skill-item">
                                        <label class="skill-label" for="troubleshooting">
                                            Αντιμετώπιση Βλαβών
                                        </label>
                                        <input type="checkbox" id="troubleshooting" name="skills[troubleshooting]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['troubleshooting']) && $driverSkills['troubleshooting'] ? 'checked' : ''; ?>>
                                    </div>

                                    <div class="skill-item">
                                        <label class="skill-label" for="technical_terms">
                                            Γνώση τεχνικών όρων
                                        </label>
                                        <input type="checkbox" id="technical_terms" name="skills[technical_terms]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['technical_terms']) && $driverSkills['technical_terms'] ? 'checked' : ''; ?>>
                                    </div>

                                    <div class="skill-item">
                                        <label class="skill-label" for="equipment_handling">
                                            Γνώση χειρισμού εξοπλισμού
                                        </label>
                                        <input type="checkbox" id="equipment_handling" name="skills[equipment_handling]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['equipment_handling']) && $driverSkills['equipment_handling'] ? 'checked' : ''; ?>>
                                    </div>

                                    <div class="skill-item">
                                        <label class="skill-label" for="checklists_usage">
                                            Χρήση λιστών ελέγχου
                                        </label>
                                        <input type="checkbox" id="checklists_usage" name="skills[checklists_usage]" value="1" class="skill-checkbox" <?php echo isset($driverSkills['checklists_usage']) && $driverSkills['checklists_usage'] ? 'checked' : ''; ?>>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Αντικαταστήστε το μέρος του κώδικα για τις πιστοποιήσεις στο edit_profile.php -->
                <div class="form-section certifications-container">
                    <h4>Σεμινάρια & Πιστοποιητικά</h4>
                    <p class="form-info">Προσθήκη, διόρθωση και διαγραφή γίνονται εδώ — κάθε αλλαγή αποθηκεύεται αμέσως, χωρίς «Αποθήκευση Αλλαγών».</p>
                    <?php include ROOT_DIR . '/src/Views/drivers/partials/_certifications-manager.php'; ?>
                </div>

            
                <!-- Οι ενότητες αυτές ανήκουν ΣΤΗΝ καρτέλα «Προσόντα &
                     Πιστοποιήσεις». Πριν (25/08/2026) ζούσαν ΕΞΩ από κάθε
                     tab-pane και εμφανίζονταν κάτω από ΟΛΕΣ τις καρτέλες. -->

            <!-- Προϋπηρεσία σε Οχήματα -->
            <div class="form-section">
                <h3>Προϋπηρεσία σε Οχήματα</h3>

                <!-- Εμφάνιση του πίνακα προϋπηρεσίας -->
                <?php include ROOT_DIR . '/src/Views/drivers/vehicle-experience-summary.php'; ?>

                <div class="vehicle-experience-link" style="margin-top: 15px;">
                    <a href="<?php echo BASE_URL; ?>drivers/vehicle-experience" class="btn-primary">Διαχείριση Προϋπηρεσίας σε Οχήματα</a>
                </div>
            </div>


    <!-- Γλωσσικές Ικανότητες — αυτόνομες εγγραφές, άμεση αποθήκευση.
         Καμία σχέση με το κουμπί «Αποθήκευση Αλλαγών» της φόρμας: κάθε
         προσθήκη/διαγραφή γλώσσας γράφεται στη βάση τη στιγμή του κλικ
         (POST /drivers/languages), ίδια φιλοσοφία με την προϋπηρεσία. -->
    <div class="form-section" id="dj-languages">
        <h3>Γλωσσικές Ικανότητες</h3>
        <p class="form-info">Κάθε γλώσσα αποθηκεύεται αμέσως με το «Προσθήκη» — γράψε όσες γλώσσες γνωρίζεις.</p>

        <ul id="dj-lang-list" style="list-style:none; padding:0; margin:0 0 1rem; display:flex; flex-wrap:wrap; gap:.5rem;">
            <?php foreach (($driverLanguages ?? []) as $lang) : ?>
                <li data-id="<?php echo (int) $lang['id']; ?>"
                    style="display:flex; align-items:center; gap:.45rem; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:999px; padding:.3rem .4rem .3rem .9rem;">
                    <span><strong><?php echo htmlspecialchars($lang['language_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span style="color:#6b7280; font-size:.85em;">·
                            <?php echo ['native' => 'Μητρική', 'fluent' => 'Άριστα', 'good' => 'Καλά', 'basic' => 'Βασικά'][$lang['level']] ?? $lang['level']; ?></span>
                    </span>
                    <button type="button" class="dj-lang-del" data-id="<?php echo (int) $lang['id']; ?>" title="Διαγραφή"
                            style="border:0; background:#e5e7eb; color:#6b7280; border-radius:50%; width:22px; height:22px; line-height:1; cursor:pointer;">×</button>
                </li>
            <?php endforeach; ?>
        </ul>

        <div style="display:flex; flex-wrap:wrap; gap:.6rem; align-items:end;">
            <div class="form-group" style="margin:0;">
                <label for="dj-lang-name">Γλώσσα</label>
                <input type="text" id="dj-lang-name" list="dj-lang-suggestions" placeholder="π.χ. Βουλγαρικά" maxlength="50" style="min-width:180px;">
                <datalist id="dj-lang-suggestions">
                    <option value="Ελληνικά"></option><option value="Αγγλικά"></option><option value="Γερμανικά"></option>
                    <option value="Γαλλικά"></option><option value="Ιταλικά"></option><option value="Ισπανικά"></option>
                    <option value="Ρωσικά"></option><option value="Βουλγαρικά"></option><option value="Ρουμανικά"></option>
                    <option value="Τουρκικά"></option><option value="Αλβανικά"></option><option value="Σερβικά"></option>
                    <option value="Πολωνικά"></option><option value="Ουκρανικά"></option><option value="Αραβικά"></option>
                </datalist>
            </div>
            <div class="form-group" style="margin:0;">
                <label for="dj-lang-level">Επίπεδο</label>
                <select id="dj-lang-level">
                    <option value="basic">Βασικά</option>
                    <option value="good" selected>Καλά</option>
                    <option value="fluent">Άριστα</option>
                    <option value="native">Μητρική Γλώσσα</option>
                </select>
            </div>
            <button type="button" id="dj-lang-add" class="btn-primary" style="margin:0;">Προσθήκη</button>
        </div>
        <div id="dj-lang-msg" style="display:none; margin-top:.6rem; padding:.45rem .7rem; border-radius:6px; font-size:.88rem;"></div>
    </div>


            </div>

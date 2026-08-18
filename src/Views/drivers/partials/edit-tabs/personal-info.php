<?php /* Καρτέλα «personal-info» της φόρμας επεξεργασίας προφίλ — αποσπάστηκε από το edit-profile.php (Πακέτο 5.4).
   Μοιράζεται το scope μεταβλητών του γονικού view (include). */ ?>
                    <div class="tab-pane active" id="personal-info">
                        <h2>Προσωπικά Στοιχεία</h2>

                        <div class="form-row">
                            <div class="form-group <?php echo isset($errors['first_name']) ? 'has-error' : ''; ?>">
                                <label for="first_name">Όνομα</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo old('first_name', $driverData['first_name'] ?? ''); ?>" required>
                                <?php if (isset($errors['first_name'])) : ?>
                                    <div class="error-message"><?php echo $errors['first_name']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group <?php echo isset($errors['last_name']) ? 'has-error' : ''; ?>">
                                <label for="last_name">Επώνυμο</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo old('last_name', $driverData['last_name'] ?? ''); ?>" required>
                                <?php if (isset($errors['last_name'])) : ?>
                                    <div class="error-message"><?php echo $errors['last_name']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="birth_date">Ημερομηνία Γέννησης</label>
                                <input type="date" id="birth_date" name="birth_date" value="<?php echo old('birth_date', $driverData['birth_date'] ?? ''); ?>">
                                <div id="age_display" class="form-hint"></div>
                            </div>

                            <div class="form-group">
                                <label for="marital_status">Οικογενειακή Κατάσταση</label>
                                <select id="marital_status" name="marital_status">
                                    <option value="">Επιλέξτε</option>
                                    <option value="single" <?php echo old('marital_status', $driverData['marital_status'] ?? '') === 'single' ? 'selected' : ''; ?>>Άγαμος/η</option>
                                    <option value="married" <?php echo old('marital_status', $driverData['marital_status'] ?? '') === 'married' ? 'selected' : ''; ?>>Έγγαμος/η</option>
                                    <option value="divorced" <?php echo old('marital_status', $driverData['marital_status'] ?? '') === 'divorced' ? 'selected' : ''; ?>>Διαζευγμένος/η</option>
                                    <option value="widowed" <?php echo old('marital_status', $driverData['marital_status'] ?? '') === 'widowed' ? 'selected' : ''; ?>>Χήρος/α</option>
                                    <option value="separated" <?php echo old('marital_status', $driverData['marital_status'] ?? '') === 'separated' ? 'selected' : ''; ?>>Σε διάσταση</option>
                                    <option value="civil_partnership" <?php echo old('marital_status', $driverData['marital_status'] ?? '') === 'civil_partnership' ? 'selected' : ''; ?>>Σύμφωνο συμβίωσης</option>
                                    <option value="no_answer" <?php echo old('marital_status', $driverData['marital_status'] ?? '') === 'no_answer' ? 'selected' : ''; ?>>Δεν απαντώ</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="education_level">Γραμματικές Γνώσεις</label>
                                <select id="education_level" name="education_level">
                                    <option value="">Επιλέξτε</option>
                                    <option value="primary" <?php echo old('education_level', $driverData['education_level'] ?? '') === 'primary' ? 'selected' : ''; ?>>Υποχρεωτική εκπαίδευση (Δημοτικό)</option>
                                    <option value="secondary_low" <?php echo old('education_level', $driverData['education_level'] ?? '') === 'secondary_low' ? 'selected' : ''; ?>>Υποχρεωτική εκπαίδευση (Γυμνάσιο)</option>
                                    <option value="secondary_high" <?php echo old('education_level', $driverData['education_level'] ?? '') === 'secondary_high' ? 'selected' : ''; ?>>Λύκειο</option>
                                    <option value="vocational" <?php echo old('education_level', $driverData['education_level'] ?? '') === 'vocational' ? 'selected' : ''; ?>>Επαγγελματική Εκπαίδευση (Γυμνάσιο)</option>
                                    <option value="vocational_high" <?php echo old('education_level', $driverData['education_level'] ?? '') === 'vocational_high' ? 'selected' : ''; ?>>Επαγγελματική Εκπαίδευση (Λύκειο)</option>
                                    <option value="iek" <?php echo old('education_level', $driverData['education_level'] ?? '') === 'iek' ? 'selected' : ''; ?>>Ινστιτούτο Επαγγελματικής Κατάρισης (ΙΕΚ)</option>
                                    <option value="tei" <?php echo old('education_level', $driverData['education_level'] ?? '') === 'tei' ? 'selected' : ''; ?>>Ανώτατο Τεχνολογικό Εκπαιδευτικό Ίδρυμα (ΑΤΕΙ)</option>
                                    <option value="university" <?php echo old('education_level', $driverData['education_level'] ?? '') === 'university' ? 'selected' : ''; ?>>Ανώτατο Εκπαιδευτικό Ίδρυμα (ΑΕΙ)</option>
                                    <option value="postgraduate" <?php echo old('education_level', $driverData['education_level'] ?? '') === 'postgraduate' ? 'selected' : ''; ?>>Μεταπτυχιακό</option>
                                    <option value="doctorate" <?php echo old('education_level', $driverData['education_level'] ?? '') === 'doctorate' ? 'selected' : ''; ?>>Διδακτορικό</option>
                                    <option value="no_answer" <?php echo old('education_level', $driverData['education_level'] ?? '') === 'no_answer' ? 'selected' : ''; ?>>Δεν απαντώ</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="military_service">Στρατιωτικές Υποχρεώσεις</label>
                                <select id="military_service" name="military_service">
                                    <option value="">Επιλέξτε</option>
                                    <option value="completed" <?php echo old('military_service', $driverData['military_service'] ?? '') === 'completed' ? 'selected' : ''; ?>>Εκπληρωμένες</option>
                                    <option value="exempt" <?php echo old('military_service', $driverData['military_service'] ?? '') === 'exempt' ? 'selected' : ''; ?>>Απαλλαγή</option>
                                    <option value="postponed" <?php echo old('military_service', $driverData['military_service'] ?? '') === 'postponed' ? 'selected' : ''; ?>>Αναβολή</option>
                                    <option value="unfulfilled" <?php echo old('military_service', $driverData['military_service'] ?? '') === 'unfulfilled' ? 'selected' : ''; ?>>Μη εκπληρωμένες</option>
                                    <option value="not_applicable" <?php echo old('military_service', $driverData['military_service'] ?? '') === 'not_applicable' ? 'selected' : ''; ?>>Δεν απαιτείται</option>
                                    <option value="no_answer" <?php echo old('military_service', $driverData['military_service'] ?? '') === 'no_answer' ? 'selected' : ''; ?>>Δεν απαντώ</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="about_me">Σχετικά με εμένα</label>
                            <textarea id="about_me" name="about_me" rows="5"><?php echo old('about_me', $driverData['about_me'] ?? ''); ?></textarea>
                            <p class="form-hint">Περιγράψτε τον εαυτό σας, την εμπειρία και τις δεξιότητές σας ως οδηγός.</p>
                        </div>

                        <div class="form-group">
                            <label>Έτη Επαγγελματικής Εμπειρίας</label>
                            <div class="experience-display" style="display: flex; justify-content: space-between; margin-top: 10px; background-color: #f9f9f9; padding: 15px; border-radius: 5px; border: 1px solid #ddd;">
                                <div class="experience-column" style="flex: 1; text-align: center; padding: 0 10px;">
                                    <div style="font-weight: bold; margin-bottom: 5px;">Συνολική Προϋπηρεσία</div>
                                    <div style="font-size: 24px; color: #007bff;"><?php echo $driverData['experience_years'] ?? '0'; ?> έτη</div>
                                </div>
                                <div class="experience-column" style="flex: 1; text-align: center; padding: 0 10px; border-left: 1px solid #ddd; border-right: 1px solid #ddd;">
                                    <div style="font-weight: bold; margin-bottom: 5px;">Εμπορευματικές Μεταφορές</div>
                                    <div style="font-size: 24px; color: #28a745;"><?php echo $roundedFreightYears ?? '0'; ?> έτη</div>
                                </div>
                                <div class="experience-column" style="flex: 1; text-align: center; padding: 0 10px;">
                                    <div style="font-weight: bold; margin-bottom: 5px;">Επιβατικές Μεταφορές</div>
                                    <div style="font-size: 24px; color: #dc3545;"><?php echo $roundedPassengerYears ?? '0'; ?> έτη</div>
                                </div>
                            </div>
                            <!-- Κρυφό πεδίο για να διατηρήσουμε την τιμή -->
                            <input type="hidden" id="experience_years" name="experience_years" value="<?php echo old('experience_years', $driverData['experience_years'] ?? ''); ?>">
                            <p class="form-hint" style="margin-top: 5px;">
                                Η προϋπηρεσία υπολογίζεται αυτόματα από τα στοιχεία που έχετε καταχωρήσει στην ενότητα "Προϋπηρεσία σε Οχήματα".
                                <a href="<?php echo BASE_URL; ?>drivers/debug_experience.php" target="_blank" style="margin-left: 10px; color: #007bff;">Αναλυτικά διαγνωστικά</a>
                            </p>
                        </div>



                        <!-- Τρεις στήλες για τα έγγραφα -->
                        <div class="documents-row" style="display: flex; flex-wrap: nowrap; margin-right: -15px; margin-left: -15px; margin-top: 20px;">
                            <div class="document-column" style="flex: 1; max-width: 33.33%; padding-right: 15px; padding-left: 15px; position: relative;">
                                <div class="form-group">
                                    <label for="profile_image">Φωτογραφία Προφίλ</label>
                                    <?php if (isset($driverData['profile_image']) && $driverData['profile_image']) : ?>
                                        <div class="current-image">
                                            <img src="<?php echo BASE_URL . htmlspecialchars($driverData['profile_image']); ?>" alt="Τρέχουσα φωτογραφία">
                                            <p>Τρέχουσα φωτογραφία</p>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" id="profile_image" name="profile_image" accept="image/jpeg, image/png, image/gif">
                                    <p class="form-hint">Μέγιστο μέγεθος: 2MB. Επιτρεπόμενοι τύποι: JPEG, PNG, GIF</p>
                                </div>
                            </div>

                            <div class="document-column" style="flex: 1; max-width: 33.33%; padding-right: 15px; padding-left: 15px; position: relative;">
                                <div class="form-group">
                                    <label for="resume_file">Βιογραφικό</label>
                                    <?php if (isset($driverData['resume_file']) && $driverData['resume_file']) : ?>
                                        <div class="current-file">
                                            <a href="<?php echo BASE_URL . htmlspecialchars($driverData['resume_file']); ?>" target="_blank">Προβολή τρέχοντος βιογραφικού</a>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" id="resume_file" name="resume_file" accept=".pdf,.doc,.docx">
                                    <p class="form-hint">Μέγιστο μέγεθος: 5MB. Επιτρεπόμενοι τύποι: PDF, DOC, DOCX</p>
                                </div>
                            </div>

                            <div class="document-column" style="flex: 1; max-width: 33.33%; padding-right: 15px; padding-left: 15px; position: relative;">
                                <div class="form-group">
                                    <label>Ποινικό Μητρώο</label>
                                    <label class="checkbox-inline" style="display:block; margin-top:8px;">
                                        <input type="checkbox" name="legal_status" value="yes" <?php echo (isset($driverData["legal_status"]) && $driverData["legal_status"] == "yes") ? "checked" : ""; ?>>
                                        Δηλώνω υπεύθυνα ότι διαθέτω λευκό ποινικό μητρώο
                                    </label>
                                    <p class="form-hint">Δεν απαιτείται ανέβασμα αρχείου. Η δήλωση επέχει θέση υπεύθυνης δήλωσης και μπορεί να ζητηθεί επαλήθευση κατά τη συνέντευξη.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Καρτέλα Στοιχείων Επικοινωνίας -->

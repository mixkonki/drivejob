<?php /* Καρτέλα «personal-info» της φόρμας επεξεργασίας προφίλ — αποσπάστηκε από το edit-profile.php (Πακέτο 5.4).
   Μοιράζεται το scope μεταβλητών του γονικού view (include). */ ?>
                    <div class="tab-pane active" id="personal-info">
                        <?php /* Κάθε ομάδα σε δικό της «κουτί» (.form-section) — διακριτικό
                           περίγραμμα, εμφανής ομαδοποίηση (feedback 25/08). */ ?>
                        <div class="form-section">
                        <h2>Προσωπικά Στοιχεία</h2>

                        <?php /* 25/08: hero με κλικαμπλ avatar (ανέβασμα με ένα κλικ) +
                           πεδία σε 3 στήλες. Τα «Έτη Εμπειρίας» έφυγαν (ανήκουν στην
                           Προϋπηρεσία), το βιογραφικό-αρχείο αφαιρέθηκε (το προφίλ
                           ΕΙΝΑΙ το βιογραφικό — έρχεται εξαγωγή PDF). */ ?>
                        <div class="profile-hero">
                            <div class="avatar-block">
                                <label class="avatar-upload" for="profile_image" title="Αλλαγή φωτογραφίας προφίλ">
                                    <?php $hasAvatar = !empty($driverData['profile_image']); ?>
                                    <?php /* Και τα δύο πάντα στο DOM: αν η εικόνα λείπει ή
                                       σπάσει (onerror), φαίνεται το placeholder — ποτέ
                                       «σπασμένο εικονίδιο» μέσα στον κύκλο. */ ?>
                                    <img id="avatarPreview"
                                         src="<?php echo $hasAvatar ? BASE_URL . htmlspecialchars($driverData['profile_image']) : ''; ?>"
                                         alt="Φωτογραφία προφίλ"
                                         <?php echo $hasAvatar ? '' : 'style="display:none;"'; ?>
                                         onerror="this.style.display='none';var p=document.getElementById('avatarPlaceholder');if(p)p.style.display='';">
                                    <svg id="avatarPlaceholder" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" <?php echo $hasAvatar ? 'style="display:none;"' : ''; ?>><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    <span class="avatar-overlay">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                        Αλλαγή
                                    </span>
                                </label>
                                <input type="file" id="profile_image" name="profile_image" accept="image/jpeg, image/png, image/gif" class="avatar-file-input">

                                <?php /* 30/08 — ΔΙΟΡΘΩΣΗ ΑΝΑΚΑΛΥΨΙΜΟΤΗΤΑΣ.
                                   Ο επεξεργαστής άνοιγε ΜΟΝΟ όταν επιλεγόταν νέο αρχείο:
                                   λειτουργία αόρατη, και η ήδη ανεβασμένη φωτογραφία δεν
                                   μπορούσε να ξανα-καδραριστεί καθόλου. Το κουμπί είναι
                                   ορατό όταν υπάρχει εικόνα και ανοίγει τον επεξεργαστή
                                   πάνω στην ΥΠΑΡΧΟΥΣΑ φωτογραφία, χωρίς νέο ανέβασμα. */ ?>
                                <div class="avatar-actions">
                                    <button type="button" class="btn-secondary avatar-adjust-btn" id="avatarAdjust"
                                            data-current="<?php echo $hasAvatar ? BASE_URL . htmlspecialchars($driverData['profile_image'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                                            <?php echo $hasAvatar ? '' : 'hidden'; ?>>
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="M21 3l-7 7"/><path d="M3 21l7-7"/></svg>
                                        Προσαρμογή
                                    </button>
                                </div>
                                <p class="avatar-hint">JPEG/PNG/GIF έως 2MB — πατήστε «Προσαρμογή» για μεγέθυνση και κεντράρισμα στον κύκλο.</p>
                            </div>

                            <div class="profile-hero-fields">
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

                            <div class="form-group">
                                <label for="birth_date">Ημερομηνία Γέννησης</label>
                                <input type="date" id="birth_date" name="birth_date" value="<?php echo old('birth_date', $driverData['birth_date'] ?? ''); ?>">
                                <div id="age_display" class="form-hint"></div>
                            </div>
                        </div>

                        <div class="form-row">
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

                            <?php /* Ποινικό μητρώο: στην ίδια γραμμή με τις επιλογές —
                               ευθυγραμμισμένο με τα select (κενή ετικέτα από πάνω).
                               Το «Σχετικά με εμένα» αφαιρέθηκε (feedback 25/08). */ ?>
                            <div class="form-group legal-status-group">
                                <label>&nbsp;</label>
                                <label class="legal-status-line" title="Η δήλωση επέχει θέση υπεύθυνης δήλωσης και μπορεί να ζητηθεί επαλήθευση κατά τη συνέντευξη.">
                                    <input type="checkbox" name="legal_status" value="yes" <?php echo (isset($driverData["legal_status"]) && $driverData["legal_status"] == "yes") ? "checked" : ""; ?>>
                                    <span>Λευκό ποινικό μητρώο</span>
                                </label>
                            </div>
                        </div>
                            </div><!-- /.profile-hero-fields -->
                        </div><!-- /.profile-hero -->

                        <?php /* Κρατά την τιμή — η εμπειρία υπολογίζεται αυτόματα από την
                           Προϋπηρεσία σε Οχήματα και ΔΕΝ εμφανίζεται πια εδώ (25/08). */ ?>
                        <input type="hidden" id="experience_years" name="experience_years" value="<?php echo old('experience_years', $driverData['experience_years'] ?? ''); ?>">
                        </div><!-- /.form-section Προσωπικά -->

                        <?php /* Προσαρμογή φωτογραφίας (30/08): ο χρήστης μεγεθύνει και
                           μετακινεί την εικόνα μέσα στον κύκλο πριν την αποθήκευση.
                           Το αποτέλεσμα κόβεται σε τετράγωνο 512×512 στο canvas και
                           μπαίνει ΠΙΣΩ στο ίδιο file input — ο server δεν αλλάζει
                           καθόλου, και ανεβαίνει μικρότερο αρχείο. */ ?>
                        <div id="avatarEditor" class="avatar-editor" hidden>
                            <div class="avatar-editor-box">
                                <h4>Προσαρμογή φωτογραφίας</h4>
                                <p class="form-hint">Σύρετε την εικόνα για να την τοποθετήσετε και χρησιμοποιήστε τον διακόπτη για μεγέθυνση.</p>
                                <div class="avatar-stage" id="avatarStage">
                                    <canvas id="avatarCanvas" width="320" height="320"></canvas>
                                    <div class="avatar-mask"></div>
                                </div>
                                <div class="avatar-zoom">
                                    <span>Σμίκρυνση</span>
                                    <input type="range" id="avatarZoom" min="100" max="300" value="100">
                                    <span>Μεγέθυνση</span>
                                </div>
                                <div class="avatar-editor-actions">
                                    <button type="button" class="btn-primary" id="avatarApply">Εφαρμογή</button>
                                    <button type="button" class="btn-secondary" id="avatarCancel">Ακύρωση</button>
                                </div>
                            </div>
                        </div>
                        <?= \Drivejob\Helpers\Asset::js('js/avatar-editor.js', true) ?>

                        <?php // Στοιχεία Επικοινωνίας: ίδια καρτέλα, δική τους ενότητα ?>
                        <?php include __DIR__ . '/contact-info.php'; ?>
                    </div>

                    <!-- Επόμενη καρτέλα: Άδειες Οδήγησης -->

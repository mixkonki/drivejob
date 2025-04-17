<?php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php';

// Ανάκτηση σφαλμάτων και παλιών τιμών από το session
$errors = $_SESSION['errors'] ?? [];
$oldInput = $_SESSION['old_input'] ?? [];
unset($_SESSION['errors'], $_SESSION['old_input']);
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver_profile.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver_edit_profile.css">
<script src="<?php echo BASE_URL; ?>js/vendor/tesseract-bundle.js"></script>
<script src="<?php echo BASE_URL; ?>js/tesseract-fallback.js"></script>
<script src="<?php echo BASE_URL; ?>js/driver_edit_profile.js"></script>
<script src="<?php echo BASE_URL; ?>js/license-validation.js"></script>

<main>
    <div class="container">
        
        <h1>Επεξεργασία Προφίλ Οδηγού</h1>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                <?php echo $_SESSION['success_message']; ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message">
                <?php echo $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo BASE_URL; ?>drivers/update-profile" method="POST" enctype="multipart/form-data" id="driverProfileForm" class="edit-profile-form">
            <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
            
            <!-- Καρτέλες φόρμας -->
            <div class="form-tabs">
            <nav class="tabs-nav">
        <button type="button" class="tab-btn active" data-tab="personal-info">Προσωπικά Στοιχεία</button>
        <button type="button" class="tab-btn" data-tab="contact-info">Στοιχεία Επικοινωνίας</button>
        <button type="button" class="tab-btn" data-tab="driving-licenses">Άδειες Οδήγησης</button>
        <button type="button" class="tab-btn" data-tab="adr-certificates">Πιστοποιητικά ADR</button>
        <button type="button" class="tab-btn" data-tab="operator-licenses">Άδειες Χειριστή Μηχανημάτων</button>
        <button type="button" class="tab-btn" data-tab="tachograph-card">Κάρτα Ψηφιακού Ταχογράφου</button>
        <button type="button" class="tab-btn" data-tab="special-licenses">Ειδικές Άδειες</button>
    </nav>
                
                <div class="tab-content">
                    <!-- Καρτέλα Προσωπικών Στοιχείων -->
<div class="tab-pane active" id="personal-info">
    <h2>Προσωπικά Στοιχεία</h2>
    
    <div class="form-row">
        <div class="form-group <?php echo isset($errors['first_name']) ? 'has-error' : ''; ?>">
            <label for="first_name">Όνομα</label>
            <input type="text" id="first_name" name="first_name" value="<?php echo old('first_name', $driverData['first_name'] ?? ''); ?>" required>
            <?php if (isset($errors['first_name'])): ?>
                <div class="error-message"><?php echo $errors['first_name']; ?></div>
            <?php endif; ?>
        </div>
        
        <div class="form-group <?php echo isset($errors['last_name']) ? 'has-error' : ''; ?>">
            <label for="last_name">Επώνυμο</label>
            <input type="text" id="last_name" name="last_name" value="<?php echo old('last_name', $driverData['last_name'] ?? ''); ?>" required>
            <?php if (isset($errors['last_name'])): ?>
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
                <option value="secondary_low" <?php echo old('education_level', $driverData['education_level'] ?? '') === 'secondary_low' ? 'selected' : ''; ?>>Γυμνάσιο</option>
                <option value="secondary_high" <?php echo old('education_level', $driverData['education_level'] ?? '') === 'secondary_high' ? 'selected' : ''; ?>>Λύκειο</option>
                <option value="vocational" <?php echo old('education_level', $driverData['education_level'] ?? '') === 'vocational' ? 'selected' : ''; ?>>Επαγγελματική Σχολή</option>
                <option value="iek" <?php echo old('education_level', $driverData['education_level'] ?? '') === 'iek' ? 'selected' : ''; ?>>ΙΕΚ</option>
                <option value="tei" <?php echo old('education_level', $driverData['education_level'] ?? '') === 'tei' ? 'selected' : ''; ?>>ΤΕΙ</option>
                <option value="university" <?php echo old('education_level', $driverData['education_level'] ?? '') === 'university' ? 'selected' : ''; ?>>Πανεπιστήμιο</option>
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
        <label for="experience_years">Έτη Επαγγελματικής Εμπειρίας</label>
        <input type="number" id="experience_years" name="experience_years" min="0" max="50" value="<?php echo old('experience_years', $driverData['experience_years'] ?? ''); ?>">
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label for="languages">Ξένες Γλώσσες</label>
            <select id="languages" name="languages[]" multiple>
                <option value="english" <?php echo isset($driverData['languages']) && in_array('english', explode(',', $driverData['languages'])) ? 'selected' : ''; ?>>Αγγλικά</option>
                <option value="french" <?php echo isset($driverData['languages']) && in_array('french', explode(',', $driverData['languages'])) ? 'selected' : ''; ?>>Γαλλικά</option>
                <option value="german" <?php echo isset($driverData['languages']) && in_array('german', explode(',', $driverData['languages'])) ? 'selected' : ''; ?>>Γερμανικά</option>
                <option value="italian" <?php echo isset($driverData['languages']) && in_array('italian', explode(',', $driverData['languages'])) ? 'selected' : ''; ?>>Ιταλικά</option>
                <option value="spanish" <?php echo isset($driverData['languages']) && in_array('spanish', explode(',', $driverData['languages'])) ? 'selected' : ''; ?>>Ισπανικά</option>
                <option value="russian" <?php echo isset($driverData['languages']) && in_array('russian', explode(',', $driverData['languages'])) ? 'selected' : ''; ?>>Ρωσικά</option>
                <option value="bulgarian" <?php echo isset($driverData['languages']) && in_array('bulgarian', explode(',', $driverData['languages'])) ? 'selected' : ''; ?>>Βουλγαρικά</option>
                <option value="romanian" <?php echo isset($driverData['languages']) && in_array('romanian', explode(',', $driverData['languages'])) ? 'selected' : ''; ?>>Ρουμανικά</option>
                <option value="albanian" <?php echo isset($driverData['languages']) && in_array('albanian', explode(',', $driverData['languages'])) ? 'selected' : ''; ?>>Αλβανικά</option>
                <option value="turkish" <?php echo isset($driverData['languages']) && in_array('turkish', explode(',', $driverData['languages'])) ? 'selected' : ''; ?>>Τουρκικά</option>
                <option value="other" <?php echo isset($driverData['languages']) && in_array('other', explode(',', $driverData['languages'])) ? 'selected' : ''; ?>>Άλλο</option>
            </select>
            <p class="form-hint">Επιλέξτε τις γλώσσες που γνωρίζετε (ctrl+click για πολλαπλή επιλογή)</p>
        </div>
        
        <div class="form-group">
            <label for="language_notes">Σημειώσεις για τις γλώσσες</label>
            <textarea id="language_notes" name="language_notes" rows="2"><?php echo old('language_notes', $driverData['language_notes'] ?? ''); ?></textarea>
            <p class="form-hint">π.χ. επίπεδο γνώσης, πιστοποιήσεις κλπ.</p>
        </div>
    </div>
    
    <div class="form-group">
        <label for="profile_image">Φωτογραφία Προφίλ</label>
        <?php if (isset($driverData['profile_image']) && $driverData['profile_image']): ?>
            <div class="current-image">
                <img src="<?php echo BASE_URL . htmlspecialchars($driverData['profile_image']); ?>" alt="Τρέχουσα φωτογραφία">
                <p>Τρέχουσα φωτογραφία</p>
            </div>
        <?php endif; ?>
        <input type="file" id="profile_image" name="profile_image" accept="image/jpeg, image/png, image/gif">
        <p class="form-hint">Μέγιστο μέγεθος: 2MB. Επιτρεπόμενοι τύποι: JPEG, PNG, GIF</p>
    </div>
    
    <div class="form-group">
        <label for="resume_file">Βιογραφικό</label>
        <?php if (isset($driverData['resume_file']) && $driverData['resume_file']): ?>
            <div class="current-file">
                <a href="<?php echo BASE_URL . htmlspecialchars($driverData['resume_file']); ?>" target="_blank">Προβολή τρέχοντος βιογραφικού</a>
            </div>
        <?php endif; ?>
        <input type="file" id="resume_file" name="resume_file" accept=".pdf,.doc,.docx">
        <p class="form-hint">Μέγιστο μέγεθος: 5MB. Επιτρεπόμενοι τύποι: PDF, DOC, DOCX</p>
    </div>
</div>
                    
                    <!-- Καρτέλα Στοιχείων Επικοινωνίας (ενσωματωμένα με Μέσα & Λογαριασμοί) -->
                    <div class="tab-pane" id="contact-info">
                        <h2>Στοιχεία Επικοινωνίας</h2>
                        
                        <div class="form-group <?php echo isset($errors['phone']) ? 'has-error' : ''; ?>">
                            <label for="phone">Κινητό Τηλέφωνο</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo old('phone', $driverData['phone'] ?? ''); ?>" required>
                            <?php if (isset($errors['phone'])): ?>
                                <div class="error-message"><?php echo $errors['phone']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="landline">Σταθερό Τηλέφωνο</label>
                            <input type="tel" id="landline" name="landline" value="<?php echo old('landline', $driverData['landline'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo $driverData['email'] ?? ''; ?>" readonly>
                            <p class="form-hint">Το email δεν μπορεί να αλλάξει. Επικοινωνήστε με τη διαχείριση για αλλαγή email.</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Διεύθυνση</label>
                            <input type="text" id="address" name="address" value="<?php echo old('address', $driverData['address'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="house_number">Αριθμός</label>
                                <input type="text" id="house_number" name="house_number" value="<?php echo old('house_number', $driverData['house_number'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="postal_code">Ταχ. Κώδικας</label>
                                <input type="text" id="postal_code" name="postal_code" value="<?php echo old('postal_code', $driverData['postal_code'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city">Πόλη</label>
                                <input type="text" id="city" name="city" value="<?php echo old('city', $driverData['city'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="country">Χώρα</label>
                                <input type="text" id="country" name="country" value="<?php echo old('country', $driverData['country'] ?? ''); ?>">
                            </div>
                        </div>

                        <!-- Προσθήκη τμήματος Μέσα Κοινωνικής Δικτύωσης -->
                        <hr class="section-divider">
                        <h3>Μέσα Κοινωνικής Δικτύωσης</h3>
                        
                        <div class="form-group">
                            <label for="social_linkedin">LinkedIn</label>
                            <input type="url" id="social_linkedin" name="social_linkedin" value="<?php echo old('social_linkedin', $driverData['social_linkedin'] ?? ''); ?>" placeholder="https://www.linkedin.com/in/yourprofile">
                        </div>
                        
                        <div class="form-group">
                            <label for="social_facebook">Facebook</label>
                            <input type="url" id="social_facebook" name="social_facebook" value="<?php echo old('social_facebook', $driverData['social_facebook'] ?? ''); ?>" placeholder="https://www.facebook.com/yourprofile">
                        </div>
                        
                        <div class="form-group">
                            <label for="social_twitter">Twitter/X</label>
                            <input type="url" id="social_twitter" name="social_twitter" value="<?php echo old('social_twitter', $driverData['social_twitter'] ?? ''); ?>" placeholder="https://twitter.com/yourusername">
                        </div>
                        
                        <div class="form-group">
                            <label for="social_instagram">Instagram</label>
                            <input type="url" id="social_instagram" name="social_instagram" value="<?php echo old('social_instagram', $driverData['social_instagram'] ?? ''); ?>" placeholder="https://www.instagram.com/yourusername">
                        </div>
                        
                        <hr class="section-divider">
                        <h3>Αλλαγή Κωδικού Πρόσβασης</h3>
                        <p class="form-hint">Αφήστε τα πεδία κενά αν δεν επιθυμείτε να αλλάξετε τον κωδικό σας.</p>
                        
                        <div class="form-group">
                            <label for="current_password">Τρέχων Κωδικός</label>
                            <input type="password" id="current_password" name="current_password">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Νέος Κωδικός</label>
                            <input type="password" id="new_password" name="new_password">
                            <div id="password-strength" class="password-strength"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Επιβεβαίωση Νέου Κωδικού</label>
                            <input type="password" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                    
                    <!-- Tab για Άδειες Οδήγησης με βελτιωμένη διάταξη -->
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
                <div class="form-group">
                    <label for="license_front_image">Εμπρόσθια Όψη Διπλώματος</label>
                    <?php if (isset($driverData['license_front_image']) && $driverData['license_front_image']): ?>
                        <div class="current-image">
                            <img src="<?php echo BASE_URL . htmlspecialchars($driverData['license_front_image']); ?>" alt="Εμπρόσθια όψη διπλώματος">
                            <p>Τρέχουσα εικόνα</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="license_front_image" name="license_front_image" accept="image/jpeg, image/png, image/gif">
                    <button type="button" id="scan-license-front" class="btn-scan">
                        <img src="<?php echo BASE_URL; ?>img/scan_icon.png" alt="Scan" class="scan-icon">
                        Σκανάρισμα με OCR
                    </button>
                </div>
                
                <div class="form-group">
                    <label for="license_back_image">Οπίσθια Όψη Διπλώματος</label>
                    <?php if (isset($driverData['license_back_image']) && $driverData['license_back_image']): ?>
                        <div class="current-image">
                            <img src="<?php echo BASE_URL . htmlspecialchars($driverData['license_back_image']); ?>" alt="Οπίσθια όψη διπλώματος">
                            <p>Τρέχουσα εικόνα</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="license_back_image" name="license_back_image" accept="image/jpeg, image/png, image/gif">
                    <button type="button" id="scan-license-back" class="btn-scan">
                        <img src="<?php echo BASE_URL; ?>img/scan_icon.png" alt="Scan" class="scan-icon">
                        Σκανάρισμα με OCR
                    </button>
                </div>
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
                        <!-- Δίκυκλα -->
                        <tr class="category-header">
                            <td colspan="5"><strong>Δίκυκλα</strong></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="license-type-icon">
                                    <img src="<?php echo BASE_URL; ?>img/license_icons/am.png" alt="AM">
                                    <span>AM</span>
                                </div>
                            </td>
                            <td>Μοτοποδήλατα</td>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="license_types[]" value="AM" <?php echo (in_array('AM', $driverLicenseTypes)) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <input type="date" name="license_expiry[AM]" value="<?php 
                                    echo old('license_expiry[AM]',
                                    getExpiryDateForLicenseType($driverLicenses, 'AM'));
                                ?>">
                            </td>
                            <td>— <!-- Δεν υπάρχει ΠΕΙ για αυτή την κατηγορία --></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="license-type-icon">
                                    <img src="<?php echo BASE_URL; ?>img/license_icons/a1.png" alt="A1">
                                    <span>A1</span>
                                </div>
                            </td>
                            <td>Μοτοσυκλέτες έως 125 cc</td>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="license_types[]" value="A1" <?php echo (in_array('A1', $driverLicenseTypes)) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <input type="date" name="license_expiry[A1]" value="<?php 
                                    echo old('license_expiry[A1]',
                                    getExpiryDateForLicenseType($driverLicenses, 'A1'));
                                ?>">
                            </td>
                            <td>— <!-- Δεν υπάρχει ΠΕΙ για αυτή την κατηγορία --></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="license-type-icon">
                                    <img src="<?php echo BASE_URL; ?>img/license_icons/a2.png" alt="A2">
                                    <span>A2</span>
                                </div>
                            </td>
                            <td>Μοτοσυκλέτες έως 35 kW</td>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="license_types[]" value="A2" <?php echo (in_array('A2', $driverLicenseTypes)) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <input type="date" name="license_expiry[A2]" value="<?php 
                                    echo old('license_expiry[A2]',
                                    getExpiryDateForLicenseType($driverLicenses, 'A2'));
                                ?>">
                            </td>
                            <td>— <!-- Δεν υπάρχει ΠΕΙ για αυτή την κατηγορία --></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="license-type-icon">
                                    <img src="<?php echo BASE_URL; ?>img/license_icons/a.png" alt="A">
                                    <span>A</span>
                                </div>
                            </td>
                            <td>Μοτοσυκλέτες χωρίς περιορισμό</td>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="license_types[]" value="A" <?php echo (in_array('A', $driverLicenseTypes)) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <input type="date" name="license_expiry[A]" value="<?php 
                                    echo old('license_expiry[A]',
                                    getExpiryDateForLicenseType($driverLicenses, 'A'));
                                ?>">
                            </td>
                            <td>— <!-- Δεν υπάρχει ΠΕΙ για αυτή την κατηγορία --></td>
                        </tr>
                        
                        <!-- Επιβατικά -->
                        <tr class="category-header">
                            <td colspan="5"><strong>Επιβατικά</strong></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="license-type-icon">
                                    <img src="<?php echo BASE_URL; ?>img/license_icons/b.png" alt="B">
                                    <span>B</span>
                                </div>
                            </td>
                            <td>Επιβατικά αυτοκίνητα</td>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="license_types[]" value="B" <?php echo (in_array('B', $driverLicenseTypes)) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <input type="date" name="license_expiry[B]" value="<?php 
                                    echo old('license_expiry[B]',
                                    getExpiryDateForLicenseType($driverLicenses, 'B'));
                                ?>">
                            </td>
                            <td>— <!-- Δεν υπάρχει ΠΕΙ για αυτή την κατηγορία --></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="license-type-icon">
                                    <img src="<?php echo BASE_URL; ?>img/license_icons/be.png" alt="BE">
                                    <span>BE</span>
                                </div>
                            </td>
                            <td>Επιβατικά με ρυμουλκούμενο</td>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="license_types[]" value="BE" <?php echo (in_array('BE', $driverLicenseTypes)) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <input type="date" name="license_expiry[BE]" value="<?php 
                                    echo old('license_expiry[BE]',
                                    getExpiryDateForLicenseType($driverLicenses, 'BE'));
                                ?>">
                            </td>
                            <td>— <!-- Δεν υπάρχει ΠΕΙ για αυτή την κατηγορία --></td>
                        </tr>
                        
                        <!-- Φορτηγά -->
                        <tr class="category-header">
                            <td colspan="4"><strong>Φορτηγά</strong></td>
                            <td><strong>ΠΕΙ</strong></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="license-type-icon">
                                    <img src="<?php echo BASE_URL; ?>img/license_icons/c1.png" alt="C1">
                                    <span>C1</span>
                                </div>
                            </td>
                            <td>Φορτηγά < 7.5t</td>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="license_types[]" value="C1" <?php echo (in_array('C1', $driverLicenseTypes)) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <input type="date" name="license_expiry[C1]" value="<?php 
                                    echo old('license_expiry[C1]',
                                    getExpiryDateForLicenseType($driverLicenses, 'C1'));
                                ?>">
                            </td>
                            <td>
                                <div class="pei-field">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="has_pei_c1" value="1" <?php echo (in_array('C1', $driverPEI)) ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                    </label>
                                    <input type="date" name="pei_c_expiry" value="<?php echo old('pei_c_expiry', $peiCExpiryDate ?? ''); ?>" <?php echo (in_array('C1', $driverPEI)) ? '' : 'disabled'; ?> class="pei-expiry-date">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="license-type-icon">
                                    <img src="<?php echo BASE_URL; ?>img/license_icons/c1e.png" alt="C1E">
                                    <span>C1E</span>
                                </div>
                            </td>
                            <td>Φορτηγά < 7.5t με ρυμουλκούμενο</td>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="license_types[]" value="C1E" <?php echo (in_array('C1E', $driverLicenseTypes)) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <input type="date" name="license_expiry[C1E]" value="<?php 
                                    echo old('license_expiry[C1E]',
                                    getExpiryDateForLicenseType($driverLicenses, 'C1E'));
                                ?>">
                            </td>
                            <td>
                                <div class="pei-field">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="has_pei_c1e" value="1" <?php echo (in_array('C1E', $driverPEI)) ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                    </label>
                                    <input type="date" name="pei_c_expiry" value="<?php echo old('pei_c_expiry', $peiCExpiryDate ?? ''); ?>" <?php echo (in_array('C1E', $driverPEI)) ? '' : 'disabled'; ?> class="pei-expiry-date">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="license-type-icon">
                                    <img src="<?php echo BASE_URL; ?>img/license_icons/c.png" alt="C">
                                    <span>C</span>
                                </div>
                            </td>
                            <td>Φορτηγά > 7.5t</td>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="license_types[]" value="C" <?php echo (in_array('C', $driverLicenseTypes)) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <input type="date" name="license_expiry[C]" value="<?php 
                                    echo old('license_expiry[C]',
                                    getExpiryDateForLicenseType($driverLicenses, 'C'));
                                ?>">
                            </td>
                            <td>
                                <div class="pei-field">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="has_pei_c" value="1" <?php echo (in_array('C', $driverPEI)) ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                    </label>
                                    <input type="date" name="pei_c_expiry" value="<?php echo old('pei_c_expiry', $peiCExpiryDate ?? ''); ?>" <?php echo (in_array('C', $driverPEI)) ? '' : 'disabled'; ?> class="pei-expiry-date">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="license-type-icon">
                                    <img src="<?php echo BASE_URL; ?>img/license_icons/ce.png" alt="CE">
                                    <span>CE</span>
                                </div>
                            </td>
                            <td>Φορτηγά με ρυμουλκούμενο</td>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="license_types[]" value="CE" <?php echo (in_array('CE', $driverLicenseTypes)) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <input type="date" name="license_expiry[CE]" value="<?php 
                                    echo old('license_expiry[CE]',
                                    getExpiryDateForLicenseType($driverLicenses, 'CE'));
                                ?>">
                            </td>
                            <td>
                                <div class="pei-field">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="has_pei_ce" value="1" <?php echo (in_array('CE', $driverPEI)) ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                    </label>
                                    <input type="date" name="pei_c_expiry" value="<?php echo old('pei_c_expiry', $peiCExpiryDate ?? ''); ?>" <?php echo (in_array('CE', $driverPEI)) ? '' : 'disabled'; ?> class="pei-expiry-date">
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Λεωφορεία -->
                        <tr class="category-header">
                            <td colspan="4"><strong>Λεωφορεία</strong></td>
                            <td><strong>ΠΕΙ</strong></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="license-type-icon">
                                    <img src="<?php echo BASE_URL; ?>img/license_icons/d1.png" alt="D1">
                                    <span>D1</span>
                                </div>
                            </td>
                            <td>Μικρά λεωφορεία</td>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="license_types[]" value="D1" <?php echo (in_array('D1', $driverLicenseTypes)) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <input type="date" name="license_expiry[D1]" value="<?php 
                                    echo old('license_expiry[D1]',
                                    getExpiryDateForLicenseType($driverLicenses, 'D1'));
                                ?>">
                            </td>
                            <td>
                                <div class="pei-field">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="has_pei_d1" value="1" <?php echo (in_array('D1', $driverPEI)) ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                    </label>
                                    <input type="date" name="pei_d_expiry" value="<?php echo old('pei_d_expiry', $peiDExpiryDate ?? ''); ?>" <?php echo (in_array('D1', $driverPEI)) ? '' : 'disabled'; ?> class="pei-expiry-date">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="license-type-icon">
                                    <img src="<?php echo BASE_URL; ?>img/license_icons/d1e.png" alt="D1E">
                                    <span>D1E</span>
                                </div>
                            </td>
                            <td>Μικρά λεωφορεία με ρυμουλκούμενο</td>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="license_types[]" value="D1E" <?php echo (in_array('D1E', $driverLicenseTypes)) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <input type="date" name="license_expiry[D1E]" value="<?php 
                                    echo old('license_expiry[D1E]',
                                    getExpiryDateForLicenseType($driverLicenses, 'D1E'));
                                ?>">
                            </td>
                            <td>
                                <div class="pei-field">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="has_pei_d1e" value="1" <?php echo (in_array('D1E', $driverPEI)) ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                    </label>
                                    <input type="date" name="pei_d_expiry" value="<?php echo old('pei_d_expiry', $peiDExpiryDate ?? ''); ?>" <?php echo (in_array('D1E', $driverPEI)) ? '' : 'disabled'; ?> class="pei-expiry-date">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="license-type-icon">
                                    <img src="<?php echo BASE_URL; ?>img/license_icons/d.png" alt="D">
                                    <span>D</span>
                                </div>
                            </td>
                            <td>Λεωφορεία</td>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="license_types[]" value="D" <?php echo (in_array('D', $driverLicenseTypes)) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <input type="date" name="license_expiry[D]" value="<?php 
                                    echo old('license_expiry[D]',
                                    getExpiryDateForLicenseType($driverLicenses, 'D'));
                                ?>">
                            </td>
                            <td>
                                <div class="pei-field">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="has_pei_d" value="1" <?php echo (in_array('D', $driverPEI)) ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                    </label>
                                    <input type="date" name="pei_d_expiry" value="<?php echo old('pei_d_expiry', $peiDExpiryDate ?? ''); ?>" <?php echo (in_array('D', $driverPEI)) ? '' : 'disabled'; ?> class="pei-expiry-date">
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="license-type-icon">
                                    <img src="<?php echo BASE_URL; ?>img/license_icons/de.png" alt="DE">
                                    <span>DE</span>
                                </div>
                            </td>
                            <td>Λεωφορεία με ρυμουλκούμενο</td>
                            <td>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="license_types[]" value="DE" <?php echo (in_array('DE', $driverLicenseTypes)) ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </td>
                            <td>
                                <input type="date" name="license_expiry[DE]" value="<?php 
                                    echo old('license_expiry[DE]',
                                    getExpiryDateForLicenseType($driverLicenses, 'DE'));
                                ?>">
                            </td>
                            <td>
                                <div class="pei-field">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="has_pei_de" value="1" <?php echo (in_array('DE', $driverPEI)) ? 'checked' : ''; ?>>
                                        <span class="checkmark"></span>
                                    </label>
                                    <input type="date" name="pei_d_expiry" value="<?php echo old('pei_d_expiry', $peiDExpiryDate ?? ''); ?>" <?php echo (in_array('DE', $driverPEI)) ? '' : 'disabled'; ?> class="pei-expiry-date">
                                </div>
                            </td>
                        </tr>
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
    
<?php
// Βοηθητική συνάρτηση για την εύρεση ημερομηνίας λήξης κατηγορίας
function getExpiryDateForLicenseType($licenses, $type) {
    foreach ($licenses as $license) {
        if ($license['license_type'] === $type) {
            return $license['expiry_date'] ?? '';
        }
    }
    return '';
}
?>
    
<!-- Tab για Πιστοποιητικά ADR -->
<div class="tab-pane" id="adr-certificates">
    <h2>Πιστοποιητικά ADR</h2>
                     
    <div class="license-section">
        <div class="form-group checkbox-group">
            <label for="adr_certificate" class="checkbox-label">
                <input type="checkbox" id="adr_certificate" name="adr_certificate" value="1" <?php echo ($driverADR) ? 'checked' : ''; ?>>
                <span>Διαθέτω πιστοποιητικό ADR</span>
            </label>
        </div>
        
        <div id="adr_certificate_tab" class="license-details-tab <?php echo (!$driverADR) ? 'hidden' : ''; ?>">
            <!-- Εικόνες πιστοποιητικού ADR και σκανάρισμα -->
            <div class="license-visual">
                <div class="form-group">
                    <label for="adr_front_image">Εμπρόσθια Όψη Πιστοποιητικού ADR</label>
                    <?php if (isset($driverData['adr_front_image']) && $driverData['adr_front_image']): ?>
                        <div class="current-image">
                            <img src="<?php echo BASE_URL . htmlspecialchars($driverData['adr_front_image']); ?>" alt="Εμπρόσθια όψη ADR">
                            <p>Τρέχουσα εικόνα</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="adr_front_image" name="adr_front_image" accept="image/jpeg, image/png, image/gif">
                    <button type="button" id="scan-adr-front" class="btn-scan">
                        <img src="<?php echo BASE_URL; ?>img/scan_icon.png" alt="Scan" class="scan-icon">
                        Σκανάρισμα με OCR
                    </button>
                </div>
                
                <div class="form-group">
                    <label for="adr_back_image">Οπίσθια Όψη Πιστοποιητικού ADR</label>
                    <?php if (isset($driverData['adr_back_image']) && $driverData['adr_back_image']): ?>
                        <div class="current-image">
                            <img src="<?php echo BASE_URL . htmlspecialchars($driverData['adr_back_image']); ?>" alt="Οπίσθια όψη ADR">
                            <p>Τρέχουσα εικόνα</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="adr_back_image" name="adr_back_image" accept="image/jpeg, image/png, image/gif">
                    <button type="button" id="scan-adr-back" class="btn-scan">
                        <img src="<?php echo BASE_URL; ?>img/scan_icon.png" alt="Scan" class="scan-icon">
                        Σκανάρισμα με OCR
                    </button>
                </div>
            </div>
            
            <!-- Βασικές πληροφορίες πιστοποιητικού ADR -->
            <div class="license-basic-info">
                <div class="form-row">
                    <div class="form-group">
                        <label for="adr_certificate_number">Αριθμός Πιστοποιητικού ADR</label>
                        <input type="text" id="adr_certificate_number" name="adr_certificate_number" value="<?php echo old('adr_certificate_number', $driverADR['certificate_number'] ?? ''); ?>" placeholder="π.χ. GR1234567">
                    </div>
                    
                    <div class="form-group">
                        <label for="adr_certificate_expiry">Ημερομηνία Λήξης</label>
                        <input type="date" id="adr_certificate_expiry" name="adr_certificate_expiry" value="<?php echo old('adr_certificate_expiry', $driverADR ? $driverADR['expiry_date'] : ''); ?>">
                        <p class="form-hint">Το πιστοποιητικό ADR ανανεώνεται κάθε 5 έτη, και η ανανέωση μπορεί να γίνει κατά τον τελευταίο χρόνο πριν τη λήξη.</p>
                    </div>
                </div>
            </div>
            
            <h4>Κατηγορίες Πιστοποιητικού ADR</h4>
            <div class="adr-categories">
                <div class="form-row">
                    <div class="form-group">
                        <label class="radio-label">
                            <input type="radio" name="adr_certificate_type" value="Π1" <?php echo ($driverADR && $driverADR['adr_type'] == 'Π1') ? 'checked' : ''; ?>>
                            <span>Π1 - Βασική + Πρακτική</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="radio-label">
                            <input type="radio" name="adr_certificate_type" value="Π2" <?php echo ($driverADR && $driverADR['adr_type'] == 'Π2') ? 'checked' : ''; ?>>
                            <span>Π2 - Βασική + Κλάση 1 (εκρηκτικά)</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="radio-label">
                            <input type="radio" name="adr_certificate_type" value="Π3" <?php echo ($driverADR && $driverADR['adr_type'] == 'Π3') ? 'checked' : ''; ?>>
                            <span>Π3 - Βασική + Κλάση 7 (ραδιενεργά)</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="radio-label">
                            <input type="radio" name="adr_certificate_type" value="Π4" <?php echo ($driverADR && $driverADR['adr_type'] == 'Π4') ? 'checked' : ''; ?>>
                            <span>Π4 - Βασική + Κλάση 1 (εκρηκτικά) + Κλάση 7 (ραδιενεργά)</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="radio-label">
                            <input type="radio" name="adr_certificate_type" value="Π5" <?php echo ($driverADR && $driverADR['adr_type'] == 'Π5') ? 'checked' : ''; ?>>
                            <span>Π5 - Βασική + Βυτία</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="radio-label">
                            <input type="radio" name="adr_certificate_type" value="Π6" <?php echo ($driverADR && $driverADR['adr_type'] == 'Π6') ? 'checked' : ''; ?>>
                            <span>Π6 - Βασική + Βυτία + Κλάση 1 (εκρηκτικά)</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="radio-label">
                            <input type="radio" name="adr_certificate_type" value="Π7" <?php echo ($driverADR && $driverADR['adr_type'] == 'Π7') ? 'checked' : ''; ?>>
                            <span>Π7 - Βασική + Βυτία + Κλάση 7 (ραδιενεργά)</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="radio-label">
                            <input type="radio" name="adr_certificate_type" value="Π8" <?php echo ($driverADR && $driverADR['adr_type'] == 'Π8') ? 'checked' : ''; ?>>
                            <span>Π8 - Βασική + Βυτία + Κλάση 1 (εκρηκτικά) + Κλάση 7 (ραδιενεργά)</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Ενημερωτικό μήνυμα για ανανέωση -->
            <div class="expiry-reminder">
                <h4>Πληροφορίες για το πιστοποιητικό ADR</h4>
                <p>Το πιστοποιητικό ADR οδηγού δίνει το δικαίωμα σε οδηγούς οχημάτων να μεταφέρουν επικίνδυνα εμπορεύματα σε συσκευασίες ή με βυτιοφόρα, όπως προβλέπονται από την Ευρωπαϊκή Συμφωνία για την Οδική Μεταφορά Επικίνδυνων Εμπορευμάτων ADR.</p>
                <p>Ο οδηγός κατέχει μόνο μία από τις κατηγορίες ADR και η ανανέωση γίνεται κάθε 5 έτη κατά τον τελευταίο χρόνο πριν την λήξη του ADR.</p>
            </div>
        </div>
    </div>
</div>

<!-- Tab για Κάρτα Ψηφιακού Ταχογράφου -->
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
                <div class="form-group">
                    <label for="tachograph_front_image">Εμπρόσθια Όψη Κάρτας Ταχογράφου</label>
                    <?php if (isset($driverData['tachograph_front_image']) && $driverData['tachograph_front_image']): ?>
                        <div class="current-image">
                            <img src="<?php echo BASE_URL . htmlspecialchars($driverData['tachograph_front_image']); ?>" alt="Εμπρόσθια όψη κάρτας ταχογράφου">
                            <p>Τρέχουσα εικόνα</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="tachograph_front_image" name="tachograph_front_image" accept="image/jpeg, image/png, image/gif">
                    <button type="button" id="scan-tachograph-front" class="btn-scan">
                        <img src="<?php echo BASE_URL; ?>img/scan_icon.png" alt="Scan" class="scan-icon">
                        Σκανάρισμα με OCR
                    </button>
                </div>
                
                <div class="form-group">
                    <label for="tachograph_back_image">Οπίσθια Όψη Κάρτας Ταχογράφου</label>
                    <?php if (isset($driverData['tachograph_back_image']) && $driverData['tachograph_back_image']): ?>
                        <div class="current-image">
                            <img src="<?php echo BASE_URL . htmlspecialchars($driverData['tachograph_back_image']); ?>" alt="Οπίσθια όψη κάρτας ταχογράφου">
                            <p>Τρέχουσα εικόνα</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="tachograph_back_image" name="tachograph_back_image" accept="image/jpeg, image/png, image/gif">
                    <button type="button" id="scan-tachograph-back" class="btn-scan">
                        <img src="<?php echo BASE_URL; ?>img/scan_icon.png" alt="Scan" class="scan-icon">
                        Σκανάρισμα με OCR
                    </button>
                </div>
            </div>
            
            <!-- Βασικές πληροφορίες κάρτας ταχογράφου - ΔΙΟΡΘΩΜΕΝΟ ΤΜΗΜΑ -->
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

<!-- Tab για Άδειες Χειριστή Μηχανημάτων Έργου - ΔΙΟΡΘΩΜΕΝΟ -->
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
                <div class="form-group">
                    <label for="operator_front_image">Εμπρόσθια Όψη Άδειας Χειριστή</label>
                    <?php if (isset($driverData['operator_front_image']) && $driverData['operator_front_image']): ?>
                        <div class="current-image">
                            <img src="<?php echo BASE_URL . htmlspecialchars($driverData['operator_front_image']); ?>" alt="Εμπρόσθια όψη άδειας χειριστή">
                            <p>Τρέχουσα εικόνα</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="operator_front_image" name="operator_front_image" accept="image/jpeg, image/png, image/gif">
                    <button type="button" id="scan-operator-front" class="btn-scan">
                        <img src="<?php echo BASE_URL; ?>img/scan_icon.png" alt="Scan" class="scan-icon">
                        Σκανάρισμα με OCR
                    </button>
                </div>
                
                <div class="form-group">
                    <label for="operator_back_image">Οπίσθια Όψη Άδειας Χειριστή</label>
                    <?php if (isset($driverData['operator_back_image']) && $driverData['operator_back_image']): ?>
                        <div class="current-image">
                            <img src="<?php echo BASE_URL . htmlspecialchars($driverData['operator_back_image']); ?>" alt="Οπίσθια όψη άδειας χειριστή">
                            <p>Τρέχουσα εικόνα</p>
                        </div>
                    <?php endif; ?>
                    <input type="file" id="operator_back_image" name="operator_back_image" accept="image/jpeg, image/png, image/gif">
                    <button type="button" id="scan-operator-back" class="btn-scan">
                        <img src="<?php echo BASE_URL; ?>img/scan_icon.png" alt="Scan" class="scan-icon">
                        Σκανάρισμα με OCR
                    </button>
                </div>
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
                    <option value="1" <?php echo (isset($driverOperator) && $driverOperator && $driverOperator['speciality'] == '1') ? 'selected' : ''; ?>>1 - Εργασίες εκσκαφής και χωματουργικές</option>
                    <option value="2" <?php echo (isset($driverOperator) && $driverOperator && $driverOperator['speciality'] == '2') ? 'selected' : ''; ?>>2 - Εργασίες ανύψωσης και μεταφοράς φορτίων</option>
                    <option value="3" <?php echo (isset($driverOperator) && $driverOperator && $driverOperator['speciality'] == '3') ? 'selected' : ''; ?>>3 - Εργασίες οδοστρωσίας</option>
                    <option value="4" <?php echo (isset($driverOperator) && $driverOperator && $driverOperator['speciality'] == '4') ? 'selected' : ''; ?>>4 - Εργασίες εξυπηρέτησης οδών και αεροδρομίων</option>
                    <option value="5" <?php echo (isset($driverOperator) && $driverOperator && $driverOperator['speciality'] == '5') ? 'selected' : ''; ?>>5 - Εργασίες υπόγειων έργων και μεταλλείων</option>
                    <option value="6" <?php echo (isset($driverOperator) && $driverOperator && $driverOperator['speciality'] == '6') ? 'selected' : ''; ?>>6 - Εργασίες έλξης</option>
                    <option value="7" <?php echo (isset($driverOperator) && $driverOperator && $driverOperator['speciality'] == '7') ? 'selected' : ''; ?>>7 - Εργασίες διάτρησης και κοπής εδαφών</option>
                    <option value="8" <?php echo (isset($driverOperator) && $driverOperator && $driverOperator['speciality'] == '8') ? 'selected' : ''; ?>>8 - Ειδικές εργασίες ανύψωσης</option>
                </select>
            </div>
            
           <!-- Αυτό το τμήμα πρέπει να αντικαταστήσει το αντίστοιχο τμήμα στο αρχείο src/Views/drivers/edit_profile.php -->

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

<!-- Ενημερωτικό μήνυμα για άδεια χειριστή -->
<div class="expiry-reminder">
    <h4>Πληροφορίες για την Άδεια Χειριστή Μηχανημάτων Έργου</h4>
    <p>Οι άδειες χειριστή μηχανημάτων έργου είναι αόριστης διάρκειας και θεωρούνται κάθε οκτώ έτη. Με την παράγραφο 1 του άρθρου 145 Νόμος 4887 η προθεσμία θεώρησής των αδειών χειριστή μηχανημάτων έργου, μετά την παρέλευση οκτώ (8) ετών, παρατείνεται κατά τρία (3) έτη και άρα η θεώρηση πραγματοποιείτε στα έντεκα (11) έτη.</p>
    <p>Ως ημερομηνία έναρξης της ενδεκαετίας λαμβάνεται η 1η Ιανουαρίου του επόμενου έτους από τη χορήγηση ή την αντικατάσταση της άδειας χειριστή.</p>
</div>

<!-- Το κουμπί προσθήκης ειδικής άδειας πρέπει να είναι μόνο στην αντίστοιχη καρτέλα -->

<!-- Προσθέστε αυτό το script για να αρχικοποιήσετε τις επιλεγμένες υποειδικότητες -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Αρχικοποίηση των επιλεγμένων υποειδικοτήτων από τη βάση δεδομένων
        <?php if (isset($driverOperatorSubSpecialities) && !empty($driverOperatorSubSpecialities)): ?>
        window.selectedSubSpecialities = [
            <?php foreach ($driverOperatorSubSpecialities as $subSpec): ?>
            '<?php echo $subSpec['sub_speciality']; ?>',
            <?php endforeach; ?>
        ];
        <?php else: ?>
        window.selectedSubSpecialities = [];
        <?php endif; ?>
        
        // Αρχική φόρτωση των υποειδικοτήτων αν έχει επιλεγεί ειδικότητα
        const specialitySelect = document.getElementById('operator_speciality');
        if (specialitySelect && specialitySelect.value) {
            window.loadSubSpecialities(specialitySelect.value);
        }
    });
</script>
            <!-- Tab για Ειδικές Άδειες -->
<div class="tab-pane" id="special-licenses">
    <h2>Ειδικές Άδειες</h2>
    
    <div id="special-licenses-container">
        <!-- Λίστα ειδικών αδειών -->
        <?php if (isset($driverSpecialLicenses) && count($driverSpecialLicenses) > 0): ?>
            <?php foreach ($driverSpecialLicenses as $index => $license): ?>
                <div class="special-license-item" id="special-license-item-<?php echo $index; ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="special_license_type_<?php echo $index; ?>">Τύπος Άδειας</label>
                            <input type="text" id="special_license_type_<?php echo $index; ?>" name="special_license_type[]" value="<?php echo htmlspecialchars($license['license_type']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="special_license_number_<?php echo $index; ?>">Αριθμός Άδειας</label>
                            <input type="text" id="special_license_number_<?php echo $index; ?>" name="special_license_number[]" value="<?php echo htmlspecialchars($license['license_number'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="special_license_expiry_<?php echo $index; ?>">Ημερομηνία Λήξης</label>
                            <input type="date" id="special_license_expiry_<?php echo $index; ?>" name="special_license_expiry[]" value="<?php echo $license['expiry_date'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="special_license_details_<?php echo $index; ?>">Περιγραφή/Λεπτομέρειες</label>
                        <textarea id="special_license_details_<?php echo $index; ?>" name="special_license_details[]" rows="2"><?php echo htmlspecialchars($license['details'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="button" class="btn-secondary remove-special-license" data-index="<?php echo $index; ?>">Αφαίρεση</button>
                    <hr class="section-divider">
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Κενό στοιχείο για προσθήκη νέας άδειας (κρυμμένο αρχικά) -->
        <div class="special-license-item" id="special-license-template" style="display: none;">
            <div class="form-row">
                <div class="form-group">
                    <label for="special_license_type_new">Τύπος Άδειας</label>
                    <input type="text" id="special_license_type_new" name="special_license_type[]">
                </div>
                
                <div class="form-group">
                    <label for="special_license_number_new">Αριθμός Άδειας</label>
                    <input type="text" id="special_license_number_new" name="special_license_number[]">
                </div>
                
                <div class="form-group">
                    <label for="special_license_expiry_new">Ημερομηνία Λήξης</label>
                    <input type="date" id="special_license_expiry_new" name="special_license_expiry[]">
                </div>
            </div>
            
            <div class="form-group">
                <label for="special_license_details_new">Περιγραφή/Λεπτομέρειες</label>
                <textarea id="special_license_details_new" name="special_license_details[]" rows="2"></textarea>
            </div>
            
            <button type="button" class="btn-secondary remove-special-license" data-index="new">Αφαίρεση</button>
            <hr class="section-divider">
        </div>
    </div>
    
    <!-- Το κουμπί εμφανίζεται μόνο στην καρτέλα ειδικών αδειών -->
    <button type="button" id="add-special-license" class="btn-primary">Προσθήκη Ειδικής Άδειας</button>
</div>

<div id="training_seminars_tab" class="license-details-tab <?php echo !(old('training_seminars', $driverData['training_seminars'] ?? 0)) ? 'hidden' : ''; ?>">
    <div class="form-group">
        <label for="training_details">Λεπτομέρειες Σεμιναρίων</label>
        <textarea id="training_details" name="training_details" rows="4"><?php echo old('training_details', $driverData['training_details'] ?? ''); ?></textarea>
        <p class="form-hint">Καταγράψτε τα σεμινάρια που έχετε παρακολουθήσει, τις ημερομηνίες και τους φορείς υλοποίησης.</p>
    </div>
</div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary btn-save">Αποθήκευση Αλλαγών</button>
                <a href="<?php echo BASE_URL; ?>drivers/driver_profile" class="btn-secondary">Ακύρωση</a>
            </div>
        </form>
    </div>
    <script>
    // Έλεγχος για διαθεσιμότητα του TesseractWrapper
    document.addEventListener('DOMContentLoaded', function() {
        console.log('TesseractWrapper διαθέσιμο:', typeof TesseractWrapper !== 'undefined');
        console.log('TesseractWrapper:', TesseractWrapper);
        
        if (typeof TesseractWrapper !== 'undefined') {
            console.log('TesseractWrapper.preprocessImage:', typeof TesseractWrapper.preprocessImage);
        }
    });
    </script>
</main>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php';
?>
# Αναβαθμισμένη Φόρμα Επεξεργασίας Προφίλ Οδηγού (edit_profile.php)

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
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script src="<?php echo BASE_URL; ?>js/license_ocr.js"></script>

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
        <button type="button" class="tab-btn" data-tab="work-preferences">Εργασιακές Προτιμήσεις</button>
        <button type="button" class="tab-btn" data-tab="media-accounts">Μέσα & Λογαριασμοί</button>
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
                        
                        <div class="form-group">
                            <label for="birth_date">Ημερομηνία Γέννησης</label>
                            <input type="date" id="birth_date" name="birth_date" value="<?php echo old('birth_date', $driverData['birth_date'] ?? ''); ?>">
                            <div id="age_display" class="form-hint"></div>
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
                    
                    <!-- Καρτέλα Στοιχείων Επικοινωνίας -->
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
                
                <!-- Οπτική αναπαράσταση της άδειας οδήγησης -->
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
                    </div>
                </div>
            </div>

            <!-- Κατηγορίες Αδειών Οδήγησης με βελτιωμένη διάταξη σε πίνακα -->
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
                        <span>Π2 - Βασική + Κλάση 1</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="radio-label">
                        <input type="radio" name="adr_certificate_type" value="Π3" <?php echo ($driverADR && $driverADR['adr_type'] == 'Π3') ? 'checked' : ''; ?>>
                        <span>Π3 - Βασική + Κλάση 7</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="radio-label">
                        <input type="radio" name="adr_certificate_type" value="Π4" <?php echo ($driverADR && $driverADR['adr_type'] == 'Π4') ? 'checked' : ''; ?>>
                        <span>Π4 - Βασική + Κλάση 1 + Κλάση 7</span>
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
                        <span>Π6 - Βασική + Βυτία + Κλάση 1</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="radio-label">
                        <input type="radio" name="adr_certificate_type" value="Π7" <?php echo ($driverADR && $driverADR['adr_type'] == 'Π7') ? 'checked' : ''; ?>>
                        <span>Π7 - Βασική + Βυτία + Κλάση 7</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="radio-label">
                        <input type="radio" name="adr_certificate_type" value="Π8" <?php echo ($driverADR && $driverADR['adr_type'] == 'Π8') ? 'checked' : ''; ?>>
                        <span>Π8 - Βασική + Βυτία + Κλάση 1 + Κλάση 7</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="adr_certificate_expiry">Ημερομηνία Λήξης</label>
                <input type="date" id="adr_certificate_expiry" name="adr_certificate_expiry" value="<?php echo old('adr_certificate_expiry', $driverADR ? $driverADR['expiry_date'] : ''); ?>">
            </div>
        </div>
    </div>
</div>
</div>

<!-- Tab για Άδειες Χειριστή Μηχανημάτων -->
<div class="tab-pane" id="operator-licenses">
            <h2>Άδειες Χειριστή Μηχανημάτων</h2>
                        
<div class="license-section">
    <div class="form-group checkbox-group">
        <label for="operator_license" class="checkbox-label">
            <input type="checkbox" id="operator_license" name="operator_license" value="1" <?php echo ($driverOperator) ? 'checked' : ''; ?>>
            <span>Διαθέτω άδεια χειριστή μηχανημάτων</span>
        </label>
    </div>
    
    <div id="operator_license_tab" class="license-details-tab <?php echo (!$driverOperator) ? 'hidden' : ''; ?>">
        <div class="form-group">
            <label for="operator_speciality">Επιλέξτε Ειδικότητα</label>
            <select id="operator_speciality" name="operator_speciality" onchange="loadSubSpecialities(this.value)">
                <option value="">Επιλέξτε</option>
                <option value="1" <?php echo ($driverOperator && $driverOperator['speciality'] == '1') ? 'selected' : ''; ?>>1 - Εργασίες εκσκαφής και χωματουργικές</option>
                <option value="2" <?php echo ($driverOperator && $driverOperator['speciality'] == '2') ? 'selected' : ''; ?>>2 - Εργασίες ανύψωσης και μεταφοράς φορτίων</option>
                <option value="3" <?php echo ($driverOperator && $driverOperator['speciality'] == '3') ? 'selected' : ''; ?>>3 - Εργασίες οδοστρωσίας</option>
                <option value="4" <?php echo ($driverOperator && $driverOperator['speciality'] == '4') ? 'selected' : ''; ?>>4 - Εργασίες εξυπηρέτησης οδών και αεροδρομίων</option>
                <option value="5" <?php echo ($driverOperator && $driverOperator['speciality'] == '5') ? 'selected' : ''; ?>>5 - Εργασίες υπόγειων έργων και μεταλλείων</option>
                <option value="6" <?php echo ($driverOperator && $driverOperator['speciality'] == '6') ? 'selected' : ''; ?>>6 - Εργασίες έλξης</option>
                <option value="7" <?php echo ($driverOperator && $driverOperator['speciality'] == '7') ? 'selected' : ''; ?>>7 - Εργασίες διάτρησης και κοπής εδαφών</option>
                <option value="8" <?php echo ($driverOperator && $driverOperator['speciality'] == '8') ? 'selected' : ''; ?>>8 - Ειδικές εργασίες ανύψωσης</option>
            </select>
        </div>
        
        <div id="subSpecialityContainer" class="form-group" style="display: <?php echo ($driverOperator) ? 'block' : 'none'; ?>;">
            <label>Επιλέξτε Υποειδικότητες</label>
            <div id="subSpecialities" class="sub-specialities">
                <!-- Οι υποειδικότητες θα φορτωθούν με JavaScript -->
            </div>
        </div>
        
        <div class="form-group">
            <label for="operator_license_expiry">Ημερομηνία Λήξης</label>
            <input type="date" id="operator_license_expiry" name="operator_license_expiry" value="<?php echo old('operator_license_expiry', $driverOperator ? $driverOperator['expiry_date'] : ''); ?>">
        </div>
    </div>
</div>
</div>
<!-- Tab για Κάρτα Ψηφιακού Ταχογράφου -->
<div class="tab-pane" id="tachograph-card">
            <h2>Κάρτα Ψηφιακού Ταχογράφου</h2>
            <!-- Περιεχόμενο... -->
        </div>
        
        <!-- Tab για Ειδικές Άδειες -->
        <div class="tab-pane" id="special-licenses">
            <h2>Ειδικές Άδειες</h2>
            <!-- Περιεχόμενο... -->
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
                    
                    <!-- Καρτέλα Εργασιακών Προτιμήσεων -->
                    <div class="tab-pane" id="work-preferences">
                        <h2>Εργασιακές Προτιμήσεις</h2>
                        
                        <div class="form-group checkbox-group">
                            <label for="available_for_work" class="checkbox-label">
                                <input type="checkbox" id="available_for_work" name="available_for_work" value="1" <?php echo (old('available_for_work', $driverData['available_for_work'] ?? 1)) ? 'checked' : ''; ?>>
                                <span>Είμαι διαθέσιμος/η για εργασία</span>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label for="preferred_job_type">Προτιμώμενος Τύπος Εργασίας</label>
                            <select id="preferred_job_type" name="preferred_job_type">
                                <option value="">Επιλέξτε</option>
                                <option value="full_time" <?php echo old('preferred_job_type', $driverData['preferred_job_type'] ?? '') === 'full_time' ? 'selected' : ''; ?>>Πλήρης Απασχόληση</option>
                                <option value="part_time" <?php echo old('preferred_job_type', $driverData['preferred_job_type'] ?? '') === 'part_time' ? 'selected' : ''; ?>>Μερική Απασχόληση</option>
                                <option value="contract" <?php echo old('preferred_job_type', $driverData['preferred_job_type'] ?? '') === 'contract' ? 'selected' : ''; ?>>Σύμβαση Έργου</option>
                                <option value="temporary" <?php echo old('preferred_job_type', $driverData['preferred_job_type'] ?? '') === 'temporary' ? 'selected' : ''; ?>>Προσωρινή Απασχόληση</option>
                                <option value="any" <?php echo old('preferred_job_type', $driverData['preferred_job_type'] ?? '') === 'any' ? 'selected' : ''; ?>>Οποιοσδήποτε τύπος</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="preferred_vehicle_type">Προτιμώμενος Τύπος Οχήματος</label>
                            <select id="preferred_vehicle_type" name="preferred_vehicle_type">
                                <option value="">Επιλέξτε</option>
                                <option value="car" <?php echo old('preferred_vehicle_type', $driverData['preferred_vehicle_type'] ?? '') === 'car' ? 'selected' : ''; ?>>Αυτοκίνητο</option>
                                <option value="van" <?php echo old('preferred_vehicle_type', $driverData['preferred_vehicle_type'] ?? '') === 'van' ? 'selected' : ''; ?>>Βαν</option>
                                <option value="truck" <?php echo old('preferred_vehicle_type', $driverData['preferred_vehicle_type'] ?? '') === 'truck' ? 'selected' : ''; ?>>Φορτηγό</option>
                                <option value="bus" <?php echo old('preferred_vehicle_type', $driverData['preferred_vehicle_type'] ?? '') === 'bus' ? 'selected' : ''; ?>>Λεωφορείο</option>
                                <option value="machinery" <?php echo old('preferred_vehicle_type', $driverData['preferred_vehicle_type'] ?? '') === 'machinery' ? 'selected' : ''; ?>>Μηχάνημα Έργου</option>
                                <option value="any" <?php echo old('preferred_vehicle_type', $driverData['preferred_vehicle_type'] ?? '') === 'any' ? 'selected' : ''; ?>>Οποιοδήποτε</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="preferred_location">Προτιμώμενη Περιοχή Εργασίας</label>
                            <input type="text" id="preferred_location" name="preferred_location" value="<?php echo old('preferred_location', $driverData['preferred_location'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="preferred_radius">Μέγιστη Απόσταση από την Κατοικία (χλμ)</label>
                            <input type="number" id="preferred_radius" name="preferred_radius" min="0" max="500" step="5" value="<?php echo old('preferred_radius', $driverData['preferred_radius'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="salary_expectations">Προσδοκίες Αμοιβής (€)</label>
                            <div class="form-row">
                                <div class="form-group">
                                    <input type="number" id="salary_min" name="salary_min" min="0" step="100" placeholder="Ελάχιστο" value="<?php echo old('salary_min', $driverData['salary_min'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <input type="number" id="salary_max" name="salary_max" min="0" step="100" placeholder="Μέγιστο" value="<?php echo old('salary_max', $driverData['salary_max'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <select id="salary_period" name="salary_period">
                                        <option value="monthly" <?php echo old('salary_period', $driverData['salary_period'] ?? '') === 'monthly' ? 'selected' : ''; ?>>Μηνιαία</option>
                                        <option value="yearly" <?php echo old('salary_period', $driverData['salary_period'] ?? '') === 'yearly' ? 'selected' : ''; ?>>Ετήσια</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <label for="willing_to_relocate" class="checkbox-label">
                                <input type="checkbox" id="willing_to_relocate" name="willing_to_relocate" value="1" <?php echo (old('willing_to_relocate', $driverData['willing_to_relocate'] ?? 0)) ? 'checked' : ''; ?>>
                                <span>Είμαι διατεθειμένος/η να μετακομίσω για εργασία</span>
                            </label>
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <label for="willing_to_travel" class="checkbox-label">
                                <input type="checkbox" id="willing_to_travel" name="willing_to_travel" value="1" <?php echo (old('willing_to_travel', $driverData['willing_to_travel'] ?? 0)) ? 'checked' : ''; ?>>
                                <span>Είμαι διατεθειμένος/η να ταξιδεύω συχνά</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Καρτέλα Μέσων & Λογαριασμών -->
                    <div class="tab-pane" id="media-accounts">
                        <h2>Μέσα Κοινωνικής Δικτύωσης & Λογαριασμοί</h2>
                        
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
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary btn-save">Αποθήκευση Αλλαγών</button>
                <a href="<?php echo BASE_URL; ?>drivers/driver_profile" class="btn-secondary">Ακύρωση</a>
            </div>
        </form>
    </div>
</main>

<script src="<?php echo BASE_URL; ?>js/driver_edit_profile.js"></script>

<?php 
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php'; 
?>
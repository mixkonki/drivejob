```php
<?php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php';

// Συμπερίληψη του Logger
use Drivejob\Core\Logger;

// Αρχικοποίηση του Logger
Logger::init();
Logger::info("Φόρτωση της σελίδας edit_profile για τον οδηγό " . $driverId, "EditProfile");

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
<script>
// Αρχικοποίηση δεδομένων από τη βάση
window.driverOperatorSubSpecialities = <?php echo json_encode($driverOperatorSubSpecialities ?? []); ?>;
window.selectedSubSpecialities = <?php 
    echo json_encode(
        !empty($driverOperatorSubSpecialities) 
            ? array_column($driverOperatorSubSpecialities, 'sub_speciality') 
            : []
    ); 
?>;

// Μετατροπή των δεδομένων από PHP σε JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Αρχικοποίηση του global αντικειμένου από τα δεδομένα της βάσης
    window.allSelectedSubSpecialities = {};
    
    // Αν υπάρχουν δεδομένα από τη βάση, τα προσθέτουμε
    if (window.driverOperatorSubSpecialities && window.driverOperatorSubSpecialities.length > 0) {
        window.driverOperatorSubSpecialities.forEach(spec => {
            if (spec.sub_speciality) {
                window.allSelectedSubSpecialities[spec.sub_speciality] = {
                    checked: true,
                    group: spec.group_type || 'A'
                };
            }
        });
    }
});
</script>

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
                                    <?php 
                                    $languageOptions = [
                                        'english' => 'Αγγλικά',
                                        'french' => 'Γαλλικά',
                                        'german' => 'Γερμανικά',
                                        'italian' => 'Ιταλικά',
                                        'spanish' => 'Ισπανικά',
                                        'russian' => 'Ρωσικά',
                                        'bulgarian' => 'Βουλγαρικά',
                                        'romanian' => 'Ρουμανικά',
                                        'albanian' => 'Αλβανικά',
                                        'turkish' => 'Τουρκικά',
                                        'other' => 'Άλλο'
                                    ];
                                    
                                    $selectedLanguages = isset($driverData['languages']) ? explode(',', $driverData['languages']) : [];
                                    
                                    foreach ($languageOptions as $value => $label): 
                                    ?>
                                        <option value="<?php echo $value; ?>" <?php echo in_array($value, $selectedLanguages) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
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
                    
                    <!-- Tab για Άδειες Οδήγησης -->
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
                                    <?php 
                                    $licenseImages = [
                                        ['id' => 'license_front_image', 'label' => 'Εμπρόσθια Όψη Διπλώματος', 'scan_id' => 'scan-license-front'],
                                        ['id' => 'license_back_image', 'label' => 'Οπίσθια Όψη Διπλώματος', 'scan_id' => 'scan-license-back']
                                    ];
                                    
                                    foreach ($licenseImages as $image):
                                    ?>
                                    <div class="form-group">
                                        <label for="<?php echo $image['id']; ?>"><?php echo $image['label']; ?></label>
                                        <?php if (isset($driverData[$image['id']]) && $driverData[$image['id']]): ?>
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
                                            function getExpiryDateForLicenseType($licenses, $type) {
                                                foreach ($licenses as $license) {
                                                    if ($license['license_type'] === $type) {
                                                        return $license['expiry_date'] ?? '';
                                                    }
                                                }
                                                return '';
                                            }
                                            
                                            // Εμφάνιση των κατηγοριών αδειών
                                            foreach ($licenseCategories as $categoryName => $licenses):
                                            ?>
                                                <tr class="category-header">
                                                    <td colspan="<?php echo $categoryName === 'Φορτηγά' || $categoryName === 'Λεωφορεία' ? '4' : '5'; ?>"><strong><?php echo $categoryName; ?></strong></td>
                                                    <?php if ($categoryName === 'Φορτηγά' || $categoryName === 'Λεωφορεία'): ?>
                                                    <td><strong>ΠΕΙ</strong></td>
                                                    <?php endif; ?>
                                                </tr>
                                                <?php foreach ($licenses as $license): ?>
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
                                                        <input type="date" name="license_expiry[<?php echo $license['type']; ?>]" value="<?php echo old('license_expiry['.$license['type'].']', getExpiryDateForLicenseType($driverLicenses, $license['type'])); ?>">
                                                    </td>
                                                    <td>
                                                        <?php if ($license['hasPei']): ?>
                                                        <div class="pei-field">
                                                            <label class="checkbox-label">
                                                                <input type="checkbox" name="has_pei_<?php echo strtolower($license['type']); ?>" value="1" <?php echo (in_array($license['type'], $driverPEI)) ? 'checked' : ''; ?>>
                                                                <span class="checkmark"></span>
                                                            </label>
                                                            <input type="date" name="pei_<?php echo $license['peiType']; ?>_expiry" value="<?php echo old('pei_'.$license['peiType'].'_expiry', ${$license['peiType'] == 'c' ? 'peiCExpiryDate' : 'peiDExpiryDate'} ?? ''); ?>" <?php echo (in_array($license['type'], $driverPEI)) ? '' : 'disabled'; ?> class="pei-expiry-date">
                                                        </div>
                                                        <?php else: ?>
                                                        — <!-- Δεν υπάρχει ΠΕΙ για αυτή την κατηγορία -->
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
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
                                    <?php 
                                    $adrImages = [
                                        ['id' => 'adr_front_image', 'label' => 'Εμπρόσθια Όψη Πιστοποιητικού ADR', 'scan_id' => 'scan-adr-front'],
                                        ['id' => 'adr_back_image', 'label' => 'Οπίσθια Όψη Πιστοποιητικού ADR', 'scan_id' => 'scan-adr-back']
                                    ];
                                    
                                    foreach ($adrImages as $image):
                                    ?>
                                    <div class="form-group">
                                        <label for="<?php echo $image['id']; ?>"><?php echo $image['label']; ?></label>
                                        <?php if (isset($driverData[$image['id']]) && $driverData[$image['id']]): ?>
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
                                    <?php 
                                    $adrCategories = [
                                        ['value' => 'Π1', 'label' => 'Π1 - Βασική + Πρακτική'],
                                        ['value' => 'Π2', 'label' => 'Π2 - Βασική + Κλάση 1 (εκρηκτικά)'],
                                        ['value' => 'Π3', 'label' => 'Π3 - Βασική + Κλάση 7 (ραδιενεργά)'],
                                        ['value' => 'Π4', 'label' => 'Π4 - Βασική + Κλάση 1 (εκρηκτικά) + Κλάση 7 (ραδιενεργά)'],
                                        ['value' => 'Π5', 'label' => 'Π5 - Βασική + Βυτία'],
                                        ['value' => 'Π6', 'label' => 'Π6 - Βασική + Βυτία + Κλάση 1 (εκρηκτικά)'],
                                        ['value' => 'Π7', 'label' => 'Π7 - Βασική + Βυτία + Κλάση 7 (ραδιενεργά)'],
                                        ['value' => 'Π8', 'label' => 'Π8 - Βασική + Βυτία + Κλάση 1 (εκρηκτικά) + Κλάση 7 (ραδιενεργά)']
                                    ];
                                    
                                    // Χωρισμός σε δύο στήλες
                                    $adrCategoriesChunks = array_chunk($adrCategories, ceil(count($adrCategories) / 2));
                                    
                                    foreach ($adrCategoriesChunks as $chunk):
                                    ?>
                                    <div class="form-row">
                                        <?php foreach ($chunk as $category): ?>
                                        <div class="form-group">
                                            <label class="radio-label">
                                                <input type="radio" name="adr_certificate_type" value="<?php echo $category['value']; ?>" <?php echo ($driverADR && $driverADR['adr_type'] == $category['value']) ? 'checked' : ''; ?>>
                                                <span><?php echo $category['label']; ?></span>
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endforeach; ?>
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

                    <!-- Tab για Άδειες Χειριστή Μηχανημάτων Έργου -->
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
                                    
                                    foreach ($operatorImages as $image):
                                    ?>
                                    <div class="form-group">
                                        <label for="<?php echo $image['id']; ?>"><?php echo $image['label']; ?></label>
                                        <?php if (isset($driverData[$image['id']]) && $driverData[$image['id']]): ?>
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
                                        
                                        foreach ($specialities as $id => $name):
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
                                    <?php if (isset($driverOperatorSubSpecialities) && !empty($driverOperatorSubSpecialities)): 
                                        // Ταξινόμηση των υποειδικοτήτων με βάση το ID
                                        usort($driverOperatorSubSpecialities, function($a, $b) {
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
                                        <?php foreach ($specialityGroups as $specialityId => $subSpecialities): ?>
                                            <div class="speciality-group">
                                                <h6><?php echo $specialityId . ' - ' . ($specialityNames[$specialityId] ?? 'Ειδικότητα ' . $specialityId); ?></h6>
                                                <ul class="selected-list">
                                                    <?php foreach ($subSpecialities as $subSpec): 
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
                                    <?php else: ?>
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
                                    <?php 
                                    $tachographImages = [
                                        ['id' => 'tachograph_front_image', 'label' => 'Εμπρόσθια Όψη Κάρτας Ταχογράφου', 'scan_id' => 'scan-tachograph-front'],
                                        ['id' => 'tachograph_back_image', 'label' => 'Οπίσθια Όψη Κάρτας Ταχογράφου', 'scan_id' => 'scan-tachograph-back']
                                    ];
                                    
                                    foreach ($tachographImages as $image):
                                    ?>
                                    <div class="form-group">
                                        <label for="<?php echo $image['id']; ?>"><?php echo $image['label']; ?></label>
                                        <?php if (isset($driverData[$image['id']]) && $driverData[$image['id']]): ?>
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
                                
                                <!-- Βασικές πληροφορίες κάρτας ταχογράφου -->
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
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary btn-save">Αποθήκευση Αλλαγών</button>
                <a href="<?php echo BASE_URL; ?>drivers/driver_profile" class="btn-secondary">Ακύρωση</a>
            </div>
        </form>
    </div>
</main>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php';
?>
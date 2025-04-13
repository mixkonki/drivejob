# Αναβαθμισμένη Φόρμα Επεξεργασίας Προφίλ Οδηγού (edit_profile.php)

```php
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
                    <button type="button" class="tab-btn" data-tab="licenses-certifications">Άδειες & Πιστοποιήσεις</button>
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
                    
                    <!-- Καρτέλα Αδειών & Πιστοποιήσεων -->
                    <div class="tab-pane" id="licenses-certifications">
                        <h2>Άδειες & Πιστοποιήσεις</h2>
                        
                        <div class="license-section">
                            <div class="form-group checkbox-group">
                                <label for="driving_license" class="checkbox-label">
                                    <input type="checkbox" id="driving_license" name="driving_license" value="1" <?php echo (old('driving_license', $driverData['driving_license'] ?? 0)) ? 'checked' : ''; ?>>
                                    <span>Διαθέτω άδεια οδήγησης</span>
                                </label>
                            </div>
                            
                            <div id="driving_license_tab" class="license-details-tab <?php echo !(old('driving_license', $driverData['driving_license'] ?? 0)) ? 'hidden' : ''; ?>">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="driving_license_type">Κατηγορία Άδειας</label>
                                        <select id="driving_license_type" name="driving_license_type">
                                            <option value="">Επιλέξτε</option>
                                            <option value="B" <?php echo old('driving_license', $driverData['driving_license'] ?? '') === 'B' ? 'selected' : ''; ?>>B (Επιβατικά οχήματα)</option>
                                            <option value="C" <?php echo old('driving_license', $driverData['driving_license'] ?? '') === 'C' ? 'selected' : ''; ?>>C (Φορτηγά)</option>
                                            <option value="C+E" <?php echo old('driving_license', $driverData['driving_license'] ?? '') === 'C+E' ? 'selected' : ''; ?>>C+E (Φορτηγά με ρυμουλκούμενο)</option>
                                            <option value="D" <?php echo old('driving_license', $driverData['driving_license'] ?? '') === 'D' ? 'selected' : ''; ?>>D (Λεωφορεία)</option>
                                            <option value="D+E" <?php echo old('driving_license', $driverData['driving_license'] ?? '') === 'D+E' ? 'selected' : ''; ?>>D+E (Λεωφορεία με ρυμουλκούμενο)</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="driving_license_expiry">Ημερομηνία Λήξης</label>
                                        <input type="date" id="driving_license_expiry" name="driving_license_expiry" value="<?php echo old('driving_license_expiry', $driverData['driving_license_expiry'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="license-section">
                            <div class="form-group checkbox-group">
                                <label for="adr_certificate" class="checkbox-label">
                                    <input type="checkbox" id="adr_certificate" name="adr_certificate" value="1" <?php echo (old('adr_certificate', $driverData['adr_certificate'] ?? 0)) ? 'checked' : ''; ?>>
                                    <span>Διαθέτω πιστοποιητικό ADR</span>
                                </label>
                            </div>
                            
                            <div id="adr_certificate_tab" class="license-details-tab <?php echo !(old('adr_certificate', $driverData['adr_certificate'] ?? 0)) ? 'hidden' : ''; ?>">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="adr_certificate_expiry">Ημερομηνία Λήξης</label>
                                        <input type="date" id="adr_certificate_expiry" name="adr_certificate_expiry" value="<?php echo old('adr_certificate_expiry', $driverData['adr_certificate_expiry'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="adr_certificate_classes">Κλάσεις ADR</label>
                                        <select id="adr_certificate_classes" name="adr_certificate_classes[]" multiple>
                                            <option value="class1" <?php echo (isset($driverData['adr_classes']) && strpos($driverData['adr_classes'], 'class1') !== false) ? 'selected' : ''; ?>>Κλάση 1 - Εκρηκτικά</option>
                                            <option value="class2" <?php echo (isset($driverData['adr_classes']) && strpos($driverData['adr_classes'], 'class2') !== false) ? 'selected' : ''; ?>>Κλάση 2 - Αέρια</option>
                                            <option value="class3" <?php echo (isset($driverData['adr_classes']) && strpos($driverData['adr_classes'], 'class3') !== false) ? 'selected' : ''; ?>>Κλάση 3 - Εύφλεκτα υγρά</option>
                                            <option value="class4" <?php echo (isset($driverData['adr_classes']) && strpos($driverData['adr_classes'], 'class4') !== false) ? 'selected' : ''; ?>>Κλάση 4 - Εύφλεκτα στερεά</option>
                                            <option value="class5" <?php echo (isset($driverData['adr_classes']) && strpos($driverData['adr_classes'], 'class5') !== false) ? 'selected' : ''; ?>>Κλάση 5 - Οξειδωτικές ουσίες</option>
                                            <option value="class6" <?php echo (isset($driverData['adr_classes']) && strpos($driverData['adr_classes'], 'class6') !== false) ? 'selected' : ''; ?>>Κλάση 6 - Τοξικές ουσίες</option>
                                            <option value="class7" <?php echo (isset($driverData['adr_classes']) && strpos($driverData['adr_classes'], 'class7') !== false) ? 'selected' : ''; ?>>Κλάση 7 - Ραδιενεργά υλικά</option>
                                            <option value="class8" <?php echo (isset($driverData['adr_classes']) && strpos($driverData['adr_classes'], 'class8') !== false) ? 'selected' : ''; ?>>Κλάση 8 - Διαβρωτικές ουσίες</option>
                                            <option value="class9" <?php echo (isset($driverData['adr_classes']) && strpos($driverData['adr_classes'], 'class9') !== false) ? 'selected' : ''; ?>>Κλάση 9 - Λοιπές επικίνδυνες ουσίες</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="license-section">
                            <div class="form-group checkbox-group">
                                <label for="operator_license" class="checkbox-label">
                                    <input type="checkbox" id="operator_license" name="operator_license" value="1" <?php echo (old('operator_license', $driverData['operator_license'] ?? 0)) ? 'checked' : ''; ?>>
                                    <span>Διαθέτω άδεια χειριστή μηχανημάτων</span>
                                </label>
                            </div>
                            
                            <div id="operator_license_tab" class="license-details-tab <?php echo !(old('operator_license', $driverData['operator_license'] ?? 0)) ? 'hidden' : ''; ?>">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="operator_license_type">Ομάδα/Ειδικότητα</label>
                                        <select id="operator_license_type" name="operator_license_type">
                                            <option value="">Επιλέξτε</option>
                                            <option value="1.1" <?php echo (isset($driverData['operator_license_type']) && $driverData['operator_license_type'] === '1.1') ? 'selected' : ''; ?>>1.1 - Εκσκαφείς</option>
                                            <option value="1.2" <?php echo (isset($driverData['operator_license_type']) && $driverData['operator_license_type'] === '1.2') ? 'selected' : ''; ?>>1.2 - Φορτωτές</option>
                                            <option value="1.3" <?php echo (isset($driverData['operator_license_type']) && $driverData['operator_license_type'] === '1.3') ? 'selected' : ''; ?>>1.3 - Γερανοί</option>
                                            <option value="1.4" <?php echo (isset($driverData['operator_license_type']) && $driverData['operator_license_type'] === '1.4') ? 'selected' : ''; ?>>1.4 - Περονοφόρα</option>
                                            <option value="2.1" <?php echo (isset($driverData['operator_license_type']) && $driverData['operator_license_type'] === '2.1') ? 'selected' : ''; ?>>2.1 - Ισοπεδωτές</option>
                                            <option value="2.2" <?php echo (isset($driverData['operator_license_type']) && $driverData['operator_license_type'] === '2.2') ? 'selected' : ''; ?>>2.2 - Οδοστρωτήρες</option>
                                            <option value="3.1" <?php echo (isset($driverData['operator_license_type']) && $driverData['operator_license_type'] === '3.1') ? 'selected' : ''; ?>>3.1 - Γεωτρύπανα</option>
                                            <option value="4.1" <?php echo (isset($driverData['operator_license_type']) && $driverData['operator_license_type'] === '4.1') ? 'selected' : ''; ?>>4.1 - Μηχανήματα λιμένων</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="operator_license_expiry">Ημερομηνία Λήξης</label>
                                        <input type="date" id="operator_license_expiry" name="operator_license_expiry" value="<?php echo old('operator_license_expiry', $driverData['operator_license_expiry'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="license-section">
                            <div class="form-group checkbox-group">
                                <label for="training_seminars" class="checkbox-label">
                                    <input type="checkbox" id="training_seminars" name="training_seminars" value="1" <?php echo (old('training_seminars', $driverData['training_seminars'] ?? 0)) ? 'checked' : ''; ?>>
                                    <span>Έχω παρακολουθήσει σεμινάρια κατάρτισης</span>
                                </label>
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
```
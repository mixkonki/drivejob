```php
<?php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';

// Συμπερίληψη του Logger
use Drivejob\Core\Logger;

// Αρχικοποίηση του Logger
Logger::init();
Logger::info("Φόρτωση της σελίδας edit_profile για τον οδηγό " . $driverId, ["page" => "EditProfile"]);

// Ανάκτηση σφαλμάτων και παλιών τιμών από το session
$errors = $_SESSION['errors'] ?? [];
$oldInput = $_SESSION['old_input'] ?? [];
unset($_SESSION['errors'], $_SESSION['old_input']);
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver-profile.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver-edit-profile.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver-skills.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/toggle-switch.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/vehicle-experience.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/form-buttons-fix.css">
<script src="<?php echo BASE_URL; ?>js/vendor/tesseract-bundle.js"></script>
<script src="<?php echo BASE_URL; ?>js/tesseract-fallback.js"></script>
<script src="<?php echo BASE_URL; ?>js/driver_edit_profile.js"></script>
<script src="<?php echo BASE_URL; ?>js/license-validation.js"></script>
<script src="<?php echo BASE_URL; ?>js/country-phone-codes.js"></script>
<script src="<?php echo BASE_URL; ?>js/criminal-record-toggle.js"></script>
<script src="<?php echo BASE_URL; ?>js/vehicle-experience.js"></script>
<script src="<?php echo BASE_URL; ?>js/update-profile-experience.js"></script>
<script>
    // Αρχικοποίηση δεδομένων από τη βάση
    window.driverOperatorSubSpecialities = [];
    window.selectedSubSpecialities = [];

    <?php if (!empty($driverOperatorSubSpecialities)): ?>
        <?php foreach ($driverOperatorSubSpecialities as $spec): ?>
            window.driverOperatorSubSpecialities.push({
                sub_speciality: "<?php echo $spec['sub_speciality']; ?>",
                group_type: "<?php echo $spec['group_type'] ?? 'A'; ?>",
                name: "<?php echo $spec['name'] ?? ''; ?>"
            });
            window.selectedSubSpecialities.push("<?php echo $spec['sub_speciality']; ?>");
        <?php endforeach; ?>
    <?php endif; ?>

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

        <?php if (isset($_SESSION['success_message'])) : ?>
            <div class="success-message">
                <?php echo $_SESSION['success_message']; ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])) : ?>
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
                    <button type="button" class="tab-btn" data-tab="skills-tab">Προσόντα & Πιστοποιήσεις</button>
                </nav>

                <div class="tab-content">
                    <!-- Καρτέλα Προσωπικών Στοιχείων -->
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
                                    <div class="radio-group">
                                        <!-- Τα radio buttons παραμένουν για συμβατότητα αλλά θα κρύβονται με CSS -->
                                        <label class="radio-inline" style="display: none;">
                                            <input type="radio" name="legal_status" value="yes" <?php echo (isset($driverData['legal_status']) && $driverData['legal_status'] == 'yes') ? 'checked' : ''; ?>> Ναι
                                        </label>
                                        <label class="radio-inline" style="display: none;">
                                            <input type="radio" name="legal_status" value="no" <?php echo (isset($driverData['legal_status']) && $driverData['legal_status'] == 'no') ? 'checked' : ''; ?>> Όχι
                                        </label>

                                        <!-- Το div για το ανέβασμα αρχείου -->
                                        <div id="criminal_record_upload" class="criminal-record-upload" style="<?php echo (isset($driverData['legal_status']) && $driverData['legal_status'] == 'yes') ? 'display:inline-block;' : 'display:none;'; ?> margin-left: 20px;">
                                            <label for="criminal_record_file" class="file-upload-label">Ανέβασμα αρχείου:</label>
                                            <input type="file" id="criminal_record_file" name="criminal_record_file" accept=".pdf,.jpg,.jpeg,.png">
                                            <?php if (isset($driverData['criminal_record_file']) && $driverData['criminal_record_file']) : ?>
                                                <div class="current-file">
                                                    <a href="<?php echo BASE_URL . htmlspecialchars($driverData['criminal_record_file']); ?>" target="_blank">Προβολή τρέχοντος αρχείου</a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="form-hint">Επιλέξτε "Ναι" για να ανεβάστε το αρχείο. Μέγιστο μέγεθος: 5MB. Επιτρεπόμενοι τύποι: PDF, JPG, JPEG, PNG</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Καρτέλα Στοιχείων Επικοινωνίας -->
                    <div class="tab-pane" id="contact-info">
                        <h2>Στοιχεία Επικοινωνίας</h2>

                        <div class="form-group <?php echo isset($errors['phone']) ? 'has-error' : ''; ?>">
                            <label for="phone">Κινητό Τηλέφωνο</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo old('phone', $driverData['phone'] ?? ''); ?>" required>
                            <?php if (isset($errors['phone'])) : ?>
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
                                <select id="country" name="country" class="country-select">
                                    <option value="">Επιλέξτε χώρα</option>
                                    <option value="GR" <?php echo (old('country', $driverData['country'] ?? '') == 'GR') ? 'selected' : ''; ?>>Ελλάδα</option>
                                    <option value="CY" <?php echo (old('country', $driverData['country'] ?? '') == 'CY') ? 'selected' : ''; ?>>Κύπρος</option>
                                    <option value="DE" <?php echo (old('country', $driverData['country'] ?? '') == 'DE') ? 'selected' : ''; ?>>Γερμανία</option>
                                    <option value="FR" <?php echo (old('country', $driverData['country'] ?? '') == 'FR') ? 'selected' : ''; ?>>Γαλλία</option>
                                    <option value="IT" <?php echo (old('country', $driverData['country'] ?? '') == 'IT') ? 'selected' : ''; ?>>Ιταλία</option>
                                    <option value="ES" <?php echo (old('country', $driverData['country'] ?? '') == 'ES') ? 'selected' : ''; ?>>Ισπανία</option>
                                    <option value="GB" <?php echo (old('country', $driverData['country'] ?? '') == 'GB') ? 'selected' : ''; ?>>Ηνωμένο Βασίλειο</option>
                                    <option value="US" <?php echo (old('country', $driverData['country'] ?? '') == 'US') ? 'selected' : ''; ?>>Ηνωμένες Πολιτείες</option>
                                    <option value="CA" <?php echo (old('country', $driverData['country'] ?? '') == 'CA') ? 'selected' : ''; ?>>Καναδάς</option>
                                    <option value="AU" <?php echo (old('country', $driverData['country'] ?? '') == 'AU') ? 'selected' : ''; ?>>Αυστραλία</option>
                                    <option value="AT" <?php echo (old('country', $driverData['country'] ?? '') == 'AT') ? 'selected' : ''; ?>>Αυστρία</option>
                                    <option value="BE" <?php echo (old('country', $driverData['country'] ?? '') == 'BE') ? 'selected' : ''; ?>>Βέλγιο</option>
                                    <option value="BG" <?php echo (old('country', $driverData['country'] ?? '') == 'BG') ? 'selected' : ''; ?>>Βουλγαρία</option>
                                    <option value="HR" <?php echo (old('country', $driverData['country'] ?? '') == 'HR') ? 'selected' : ''; ?>>Κροατία</option>
                                    <option value="CZ" <?php echo (old('country', $driverData['country'] ?? '') == 'CZ') ? 'selected' : ''; ?>>Τσεχία</option>
                                    <option value="DK" <?php echo (old('country', $driverData['country'] ?? '') == 'DK') ? 'selected' : ''; ?>>Δανία</option>
                                    <option value="EE" <?php echo (old('country', $driverData['country'] ?? '') == 'EE') ? 'selected' : ''; ?>>Εσθονία</option>
                                    <option value="FI" <?php echo (old('country', $driverData['country'] ?? '') == 'FI') ? 'selected' : ''; ?>>Φινλανδία</option>
                                    <option value="HU" <?php echo (old('country', $driverData['country'] ?? '') == 'HU') ? 'selected' : ''; ?>>Ουγγαρία</option>
                                    <option value="IE" <?php echo (old('country', $driverData['country'] ?? '') == 'IE') ? 'selected' : ''; ?>>Ιρλανδία</option>
                                    <option value="LV" <?php echo (old('country', $driverData['country'] ?? '') == 'LV') ? 'selected' : ''; ?>>Λετονία</option>
                                    <option value="LT" <?php echo (old('country', $driverData['country'] ?? '') == 'LT') ? 'selected' : ''; ?>>Λιθουανία</option>
                                    <option value="LU" <?php echo (old('country', $driverData['country'] ?? '') == 'LU') ? 'selected' : ''; ?>>Λουξεμβούργο</option>
                                    <option value="MT" <?php echo (old('country', $driverData['country'] ?? '') == 'MT') ? 'selected' : ''; ?>>Μάλτα</option>
                                    <option value="NL" <?php echo (old('country', $driverData['country'] ?? '') == 'NL') ? 'selected' : ''; ?>>Ολλανδία</option>
                                    <option value="PL" <?php echo (old('country', $driverData['country'] ?? '') == 'PL') ? 'selected' : ''; ?>>Πολωνία</option>
                                    <option value="PT" <?php echo (old('country', $driverData['country'] ?? '') == 'PT') ? 'selected' : ''; ?>>Πορτογαλία</option>
                                    <option value="RO" <?php echo (old('country', $driverData['country'] ?? '') == 'RO') ? 'selected' : ''; ?>>Ρουμανία</option>
                                    <option value="SK" <?php echo (old('country', $driverData['country'] ?? '') == 'SK') ? 'selected' : ''; ?>>Σλοβακία</option>
                                    <option value="SI" <?php echo (old('country', $driverData['country'] ?? '') == 'SI') ? 'selected' : ''; ?>>Σλοβενία</option>
                                    <option value="SE" <?php echo (old('country', $driverData['country'] ?? '') == 'SE') ? 'selected' : ''; ?>>Σουηδία</option>
                                    <option value="CH" <?php echo (old('country', $driverData['country'] ?? '') == 'CH') ? 'selected' : ''; ?>>Ελβετία</option>
                                    <option value="NO" <?php echo (old('country', $driverData['country'] ?? '') == 'NO') ? 'selected' : ''; ?>>Νορβηγία</option>
                                    <option value="RS" <?php echo (old('country', $driverData['country'] ?? '') == 'RS') ? 'selected' : ''; ?>>Σερβία</option>
                                    <option value="TR" <?php echo (old('country', $driverData['country'] ?? '') == 'TR') ? 'selected' : ''; ?>>Τουρκία</option>
                                </select>
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

                                    foreach ($licenseImages as $image) :
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
                                            function getExpiryDateForLicenseType($licenses, $type)
                                            {
                                                foreach ($licenses as $license) {
                                                    if ($license['license_type'] === $type) {
                                                        return $license['expiry_date'] ?? '';
                                                    }
                                                }
                                                return '';
                                            }

                                            // Εμφάνιση των κατηγοριών αδειών
                                            foreach ($licenseCategories as $categoryName => $licenses) :
                                            ?>
                                                <tr class="category-header">
                                                    <td colspan="<?php echo $categoryName === 'Φορτηγά' || $categoryName === 'Λεωφορεία' ? '4' : '5'; ?>"><strong><?php echo $categoryName; ?></strong></td>
                                                    <?php if ($categoryName === 'Φορτηγά' || $categoryName === 'Λεωφορεία') : ?>
                                                        <td><strong>ΠΕΙ</strong></td>
                                                    <?php endif; ?>
                                                </tr>
                                                <?php foreach ($licenses as $license) : ?>
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
                                                            <input type="date" name="license_expiry[<?php echo $license['type']; ?>]" value="<?php echo old('license_expiry[' . $license['type'] . ']', getExpiryDateForLicenseType($driverLicenses, $license['type'])); ?>">
                                                        </td>
                                                        <td>
                                                            <?php if ($license['hasPei']) : ?>
                                                                <div class="pei-field">
                                                                    <label class="checkbox-label">
                                                                        <input type="checkbox" name="has_pei_<?php echo strtolower($license['type']); ?>" value="1" <?php echo (in_array($license['type'], $driverPEI)) ? 'checked' : ''; ?>>
                                                                        <span class="checkmark"></span>
                                                                    </label>
                                                                    <input type="date" name="pei_<?php echo $license['peiType']; ?>_expiry" value="<?php echo old('pei_' . $license['peiType'] . '_expiry', ${$license['peiType'] == 'c' ? 'peiCExpiryDate' : 'peiDExpiryDate'} ?? ''); ?>" <?php echo (in_array($license['type'], $driverPEI)) ? '' : 'disabled'; ?> class="pei-expiry-date">
                                                                </div>
                                                            <?php else : ?>
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

                                    foreach ($adrImages as $image) :
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

                                    foreach ($adrCategoriesChunks as $chunk) :
                                    ?>
                                        <div class="form-row">
                                            <?php foreach ($chunk as $category) : ?>
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

                                    foreach ($tachographImages as $image) :
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
                            <?php if (isset($driverSpecialLicenses) && count($driverSpecialLicenses) > 0) : ?>
                                <?php foreach ($driverSpecialLicenses as $index => $license) : ?>
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
            <!-- Προσθήκη στο αρχείο edit_profile.php στο κατάλληλο σημείο, όπου βρίσκονται οι καρτέλες -->
            <div class="tab-pane" id="skills-tab">
                <h2>Προσόντα & Πιστοποιήσεις</h2>

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
                <div class="certifications-container">
                    <h4>Πιστοποιήσεις</h4>



                    <!-- Σύνοψη πιστοποιητικών -->
                    <div class="certifications-summary">
                        <?php if (isset($driverCertifications) && !empty($driverCertifications)) : ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">#</th>
                                        <th style="width: 30%;">Τίτλος</th>
                                        <th>Θεματολογία</th>
                                        <th>Τύπος</th>
                                        <th>Ημ/νία Απόκτησης</th>
                                        <th>Ημ/νία Λήξης</th>
                                        <th>Διάρκεια (ώρες)</th>
                                        <th>Πιστοποιητικό</th>
                                        <th>Περιγραφή</th>
                                        <th>Βαθμοί</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Αντιστοίχιση κατηγοριών με ονόματα
                                    $categoryNames = [
                                        'road_safety' => 'Οδική ασφάλεια',
                                        'tachograph' => 'Ταχογράφος',
                                        'loading_securing' => 'Φόρτωση - Πρόσδεση',
                                        'technical' => 'Τεχνική επιμόρφωση',
                                        'commercial' => 'Εμπορική επιμόρφωση',
                                        'procedures' => 'Διαδικασίες',
                                        'inspections' => 'Έλεγχοι',
                                        'other' => 'Άλλο'
                                    ];

                                    // Αντιστοίχιση κατηγοριών με βαθμούς
                                    $categoryPoints = [
                                        'road_safety' => 50,
                                        'tachograph' => 20,
                                        'loading_securing' => 50,
                                        'technical' => 20,
                                        'commercial' => 20,
                                        'procedures' => 20,
                                        'inspections' => 20,
                                        'other' => 20
                                    ];

                                    // Αντιστοίχιση τύπων μεταφοράς με ονόματα
                                    $transportTypeNames = [
                                        'freight' => 'Εμπορευματικές',
                                        'passenger' => 'Επιβατικές',
                                        'both' => 'Εμπορευματικές & Επιβατικές'
                                    ];

                                    foreach ($driverCertifications as $index => $cert) :
                                        // Εύρεση του ονόματος της κατηγορίας
                                        $categoryName = $categoryNames[$cert['category'] ?? ''] ?? $cert['category'] ?? 'Μη καθορισμένο';

                                        // Εύρεση του ονόματος του τύπου μεταφοράς
                                        $transportTypeName = $transportTypeNames[$cert['transport_type'] ?? 'both'] ?? 'Εμπορευματικές & Επιβατικές';

                                        // Υπολογισμός των βαθμών
                                        $points = $categoryPoints[$cert['category'] ?? ''] ?? 0;

                                        // Προσθήκη στοιχείου προεπισκόπησης αρχείου αν υπάρχει
                                        $filePreview = '-';
                                        if (!empty($cert['certificate_file'])) {
                                            $fileExt = pathinfo($cert['certificate_file'], PATHINFO_EXTENSION);
                                            if (in_array(strtolower($fileExt), ['jpg', 'jpeg', 'png'])) {
                                                $filePreview = '<a href="' . BASE_URL . 'uploads/certificates/' . htmlspecialchars($cert['certificate_file']) . '" target="_blank" class="file-preview">
                                                        <img src="' . BASE_URL . 'uploads/certificates/' . htmlspecialchars($cert['certificate_file']) . '" alt="Προεπισκόπηση" width="30" height="30">
                                                        <span>Προβολή</span>
                                                    </a>';
                                            } else if (strtolower($fileExt) === 'pdf') {
                                                $filePreview = '<a href="' . BASE_URL . 'uploads/certificates/' . htmlspecialchars($cert['certificate_file']) . '" target="_blank" class="file-preview">
                                                        <i class="fas fa-file-pdf" style="font-size: 24px; color: #dc3545;"></i>
                                                        <span>Προβολή PDF</span>
                                                    </a>';
                                            } else {
                                                $filePreview = '<a href="' . BASE_URL . 'uploads/certificates/' . htmlspecialchars($cert['certificate_file']) . '" target="_blank" class="file-preview">
                                                        <i class="fas fa-file" style="font-size: 24px;"></i>
                                                        <span>Προβολή αρχείου</span>
                                                    </a>';
                                            }
                                        }
                                    ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($cert['title']); ?></td>
                                            <td><?php echo $categoryName; ?></td>
                                            <td><?php echo $transportTypeName; ?></td>
                                            <td><?php echo isset($cert['date']) && $cert['date'] ? date('d/m/Y', strtotime($cert['date'])) : '-'; ?></td>
                                            <td><?php echo isset($cert['expiry']) && $cert['expiry'] ? date('d/m/Y', strtotime($cert['expiry'])) : '-'; ?></td>
                                            <td><?php echo isset($cert['duration']) && $cert['duration'] ? $cert['duration'] : '-'; ?></td>
                                            <td><?php echo $filePreview; ?></td>
                                            <td title="<?php echo htmlspecialchars($cert['description'] ?? ''); ?>"><?php
                                                                                                                    echo isset($cert['description']) && $cert['description']
                                                                                                                        ? (strlen($cert['description']) > 50
                                                                                                                            ? htmlspecialchars(substr($cert['description'], 0, 50)) . '...'
                                                                                                                            : htmlspecialchars($cert['description']))
                                                                                                                        : '-';
                                                                                                                    ?></td>
                                            <td><?php echo $points; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <?php
                                    // Υπολογισμός συνολικών βαθμών
                                    $freightTotal = 0;
                                    $passengerTotal = 0;
                                    $grandTotal = 0;

                                    foreach ($driverCertifications as $cert) {
                                        $points = $categoryPoints[$cert['category'] ?? ''] ?? 0;

                                        if (($cert['transport_type'] ?? 'both') === 'freight' || ($cert['transport_type'] ?? 'both') === 'both') {
                                            $freightTotal += $points;
                                        }

                                        if (($cert['transport_type'] ?? 'both') === 'passenger' || ($cert['transport_type'] ?? 'both') === 'both') {
                                            $passengerTotal += $points;
                                        }

                                        // Για το συνολικό άθροισμα, μετράμε κάθε πιστοποιητικό μόνο μία φορά
                                        $grandTotal += $points;
                                    }
                                    ?>
                                    <tr class="total-row freight-total">
                                        <td colspan="9" class="text-right"><strong>Σύνολο για εμπορευματικές μεταφορές:</strong></td>
                                        <td><?php echo $freightTotal; ?></td>
                                    </tr>
                                    <tr class="total-row passenger-total">
                                        <td colspan="9" class="text-right"><strong>Σύνολο για επιβατικές μεταφορές:</strong></td>
                                        <td><?php echo $passengerTotal; ?></td>
                                    </tr>
                                    <tr class="total-row grand-total">
                                        <td colspan="9" class="text-right"><strong>Συνολικοί βαθμοί:</strong></td>
                                        <td><?php echo $grandTotal; ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        <?php else : ?>
                            <p>Δεν έχουν καταχωρηθεί πιστοποιητικά εκπαίδευσης.</p>
                        <?php endif; ?>
                    </div>
                    <!-- Σύνδεσμος προς τη σελίδα διαχείρισης πιστοποιητικών -->
                    <div class="certifications-link" style="margin-bottom: 20px;">
                        <a href="<?php echo BASE_URL; ?>drivers/certifications" class="btn-primary">Διαχείριση Πιστοποιητικών Εκπαίδευσης</a>
                    </div>
                </div>

            </div>



            <!-- Προϋπηρεσία σε Οχήματα -->
            <div class="form-section">
                <h3>Προϋπηρεσία σε Οχήματα</h3>

                <!-- Εμφάνιση του πίνακα προϋπηρεσίας -->
                <?php include ROOT_DIR . '/src/Views/drivers/vehicle-experience-summary.php'; ?>

                <div class="vehicle-experience-link" style="margin-top: 15px;">
                    <a href="<?php echo BASE_URL; ?>drivers/vehicle-experience" class="btn-primary">Διαχείριση Προϋπηρεσίας σε Οχήματα</a>
                </div>
            </div>

    </div>
    </div>
    <!-- Γλωσσικές Ικανότητες -->
    <div class="form-section">
        <h3>Γλωσσικές Ικανότητες</h3>
        <div class="language-form">
            <div class="form-group">
                <label for="languages[greek]">Ελληνικά:</label>
                <select name="languages[greek]" id="languages[greek]">
                    <option value="native" <?php echo (isset($driverData['language_greek']) && $driverData['language_greek'] == 'native') ? 'selected' : ''; ?>>Μητρική Γλώσσα</option>
                    <option value="fluent" <?php echo (isset($driverData['language_greek']) && $driverData['language_greek'] == 'fluent') ? 'selected' : ''; ?>>Άριστα</option>
                    <option value="good" <?php echo (isset($driverData['language_greek']) && $driverData['language_greek'] == 'good') ? 'selected' : ''; ?>>Καλά</option>
                    <option value="basic" <?php echo (isset($driverData['language_greek']) && $driverData['language_greek'] == 'basic') ? 'selected' : ''; ?>>Βασικά</option>
                    <option value="" <?php echo (!isset($driverData['language_greek']) || $driverData['language_greek'] == '') ? 'selected' : ''; ?>>Δεν γνωρίζω</option>
                </select>
            </div>
            <div class="form-group">
                <label for="languages[english]">Αγγλικά:</label>
                <select name="languages[english]" id="languages[english]">
                    <option value="native" <?php echo (isset($driverData['language_english']) && $driverData['language_english'] == 'native') ? 'selected' : ''; ?>>Μητρική Γλώσσα</option>
                    <option value="fluent" <?php echo (isset($driverData['language_english']) && $driverData['language_english'] == 'fluent') ? 'selected' : ''; ?>>Άριστα</option>
                    <option value="good" <?php echo (isset($driverData['language_english']) && $driverData['language_english'] == 'good') ? 'selected' : ''; ?>>Καλά</option>
                    <option value="basic" <?php echo (isset($driverData['language_english']) && $driverData['language_english'] == 'basic') ? 'selected' : ''; ?>>Βασικά</option>
                    <option value="" <?php echo (!isset($driverData['language_english']) || $driverData['language_english'] == '') ? 'selected' : ''; ?>>Δεν γνωρίζω</option>
                </select>
            </div>
            <div class="form-group">
                <label for="languages[german]">Γερμανικά:</label>
                <select name="languages[german]" id="languages[german]">
                    <option value="native" <?php echo (isset($driverData['language_german']) && $driverData['language_german'] == 'native') ? 'selected' : ''; ?>>Μητρική Γλώσσα</option>
                    <option value="fluent" <?php echo (isset($driverData['language_german']) && $driverData['language_german'] == 'fluent') ? 'selected' : ''; ?>>Άριστα</option>
                    <option value="good" <?php echo (isset($driverData['language_german']) && $driverData['language_german'] == 'good') ? 'selected' : ''; ?>>Καλά</option>
                    <option value="basic" <?php echo (isset($driverData['language_german']) && $driverData['language_german'] == 'basic') ? 'selected' : ''; ?>>Βασικά</option>
                    <option value="" <?php echo (!isset($driverData['language_german']) || $driverData['language_german'] == '') ? 'selected' : ''; ?>>Δεν γνωρίζω</option>
                </select>
            </div>
            <div class="form-group">
                <label for="languages[french]">Γαλλικά:</label>
                <select name="languages[french]" id="languages[french]">
                    <option value="native" <?php echo (isset($driverData['language_french']) && $driverData['language_french'] == 'native') ? 'selected' : ''; ?>>Μητρική Γλώσσα</option>
                    <option value="fluent" <?php echo (isset($driverData['language_french']) && $driverData['language_french'] == 'fluent') ? 'selected' : ''; ?>>Άριστα</option>
                    <option value="good" <?php echo (isset($driverData['language_french']) && $driverData['language_french'] == 'good') ? 'selected' : ''; ?>>Καλά</option>
                    <option value="basic" <?php echo (isset($driverData['language_french']) && $driverData['language_french'] == 'basic') ? 'selected' : ''; ?>>Βασικά</option>
                    <option value="" <?php echo (!isset($driverData['language_french']) || $driverData['language_french'] == '') ? 'selected' : ''; ?>>Δεν γνωρίζω</option>
                </select>
            </div>
            <div class="form-group">
                <label for="languages[italian]">Ιταλικά:</label>
                <select name="languages[italian]" id="languages[italian]">
                    <option value="native" <?php echo (isset($driverData['language_italian']) && $driverData['language_italian'] == 'native') ? 'selected' : ''; ?>>Μητρική Γλώσσα</option>
                    <option value="fluent" <?php echo (isset($driverData['language_italian']) && $driverData['language_italian'] == 'fluent') ? 'selected' : ''; ?>>Άριστα</option>
                    <option value="good" <?php echo (isset($driverData['language_italian']) && $driverData['language_italian'] == 'good') ? 'selected' : ''; ?>>Καλά</option>
                    <option value="basic" <?php echo (isset($driverData['language_italian']) && $driverData['language_italian'] == 'basic') ? 'selected' : ''; ?>>Βασικά</option>
                    <option value="" <?php echo (!isset($driverData['language_italian']) || $driverData['language_italian'] == '') ? 'selected' : ''; ?>>Δεν γνωρίζω</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="languages[other_name]">Άλλη γλώσσα:</label>
                    <input type="text" name="languages[other_name]" id="languages[other_name]" value="<?php echo isset($driverData['language_other_name']) ? htmlspecialchars($driverData['language_other_name']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="languages[other_level]">Επίπεδο:</label>
                    <select name="languages[other_level]" id="languages[other_level]">
                        <option value="native" <?php echo (isset($driverData['language_other_level']) && $driverData['language_other_level'] == 'native') ? 'selected' : ''; ?>>Μητρική Γλώσσα</option>
                        <option value="fluent" <?php echo (isset($driverData['language_other_level']) && $driverData['language_other_level'] == 'fluent') ? 'selected' : ''; ?>>Άριστα</option>
                        <option value="good" <?php echo (isset($driverData['language_other_level']) && $driverData['language_other_level'] == 'good') ? 'selected' : ''; ?>>Καλά</option>
                        <option value="basic" <?php echo (isset($driverData['language_other_level']) && $driverData['language_other_level'] == 'basic') ? 'selected' : ''; ?>>Βασικά</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Κουμπιά αποθήκευσης και ακύρωσης -->
    <div class="form-actions">
        <div class="availability-toggle-container">
            <label class="toggle-switch-label">
                <span class="toggle-label-text">Διαθεσιμότητα για εργασία:</span>
                <span class="toggle-switch">
                    <input type="checkbox" name="available_for_work" id="available_for_work" class="toggle-switch-input" value="1" <?php echo $driverData['available_for_work'] ? 'checked' : ''; ?>>
                    <span class="toggle-switch-slider"></span>
                </span>
                <span class="toggle-switch-text">
                    <?php echo $driverData['available_for_work'] ? 'Διαθέσιμος/η για εργασία' : 'Μη διαθέσιμος/η για εργασία'; ?>
                </span>
            </label>
        </div>

        <div class="form-buttons">
            <button type="submit" class="btn-primary btn-save">Αποθήκευση Αλλαγών</button>
            <a href="<?php echo BASE_URL; ?>drivers/driver-profile" class="btn-secondary">Ακύρωση</a>
        </div>

    </div>
    </form>
</main>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>
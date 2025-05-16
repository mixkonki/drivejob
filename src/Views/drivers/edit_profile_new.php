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
                            <label for="experience_years">Έτη Επαγγελματικής Εμπειρίας</label>
                            <input type="number" id="experience_years" name="experience_years" min="0" max="50" value="<?php echo old('experience_years', $driverData['experience_years'] ?? ''); ?>">
                        </div>



                        <!-- Τρεις στήλες για τα έγγραφα -->
                        <div class="documents-row" style="display: flex; flex-wrap: wrap; margin-right: -15px; margin-left: -15px; margin-top: 20px;">
                            <div class="document-column" style="flex: 0 0 33.33%; max-width: 33.33%; padding-right: 15px; padding-left: 15px; position: relative;">
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

                            <div class="document-column" style="flex: 0 0 33.33%; max-width: 33.33%; padding-right: 15px; padding-left: 15px; position: relative;">
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

                            <div class="document-column" style="flex: 0 0 33.33%; max-width: 33.33%; padding-right: 15px; padding-left: 15px; position: relative;">
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
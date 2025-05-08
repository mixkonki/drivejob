<?php
// Αρχικοποίηση της εφαρμογής
$container = require_once __DIR__ . '/../../src/bootstrap.php';

// Λήψη του PDO από το container
$pdo = $container->get('pdo');

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι οδηγός
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Δημιουργία των μοντέλων
$profileModel = new \Drivejob\Models\ProfileModel($pdo);
$driverId = $_SESSION['user_id'];

// Λήψη των στοιχείων του οδηγού
$driver = $profileModel->getDriverById($driverId);

// Προσωρινή λύση: Χρήση του υπάρχοντος μοντέλου για τις λειτουργίες που δεν έχουν μεταφερθεί ακόμα
$driverData = $driver;
$driverSkills = $driver['skills'] ?? [];
$driverLanguages = [];
$driverCertifications = [];

// Έλεγχος αν υπάρχει υποβολή φόρμας
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Επεξεργασία της φόρμας και αποθήκευση των δεξιοτήτων
    $result = true; // Προσωρινή τιμή

    if ($result) {
        $_SESSION['success_message'] = 'Οι δεξιότητες και τα προσόντα σας ενημερώθηκαν με επιτυχία.';
        header('Location: ' . BASE_URL . 'drivers/driver_profile');
        exit();
    } else {
        $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την ενημέρωση των δεξιοτήτων σας.';
    }
}

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php';
?>

<!-- Σύνδεση με το CSS αρχείο του προφίλ οδηγού και επεξεργασίας δεξιοτήτων -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver_profile.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver-skills.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/edit-skills.css">

<main>
    <div class="container">
        <div class="page-header">
            <h1>Επεξεργασία Δεξιοτήτων και Προσόντων</h1>
            <p>Διαχειριστείτε τις επαγγελματικές δεξιότητες, γλωσσικές ικανότητες, πιστοποιήσεις και σεμινάρια σας.</p>
        </div>

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

        <form method="POST" action="" enctype="multipart/form-data" id="edit-skills-form">
            <?php echo \Drivejob\Core\CSRF::tokenField(); ?>

            <!-- Επαγγελματικές Δεξιότητες -->
            <section class="edit-section">
                <h2>Επαγγελματικές Δεξιότητες</h2>

                <!-- Οδηγικές Ικανότητες -->
                <div class="skills-category">
                    <h3>Οδηγικές Ικανότητες</h3>
                    <div class="skills-options">
                        <div class="skill-checkbox">
                            <input type="checkbox" id="defensive_driving" name="skills[defensive_driving]" value="1" <?php echo isset($driverSkills['defensive_driving']) && $driverSkills['defensive_driving'] ? 'checked' : ''; ?>>
                            <label for="defensive_driving">Αμυντική Οδήγηση</label>
                        </div>
                        <div class="skill-checkbox">
                            <input type="checkbox" id="eco_driving" name="skills[eco_driving]" value="1" <?php echo isset($driverSkills['eco_driving']) && $driverSkills['eco_driving'] ? 'checked' : ''; ?>>
                            <label for="eco_driving">Οικολογική Οδήγηση</label>
                        </div>
                        <div class="skill-checkbox">
                            <input type="checkbox" id="night_driving" name="skills[night_driving]" value="1" <?php echo isset($driverSkills['night_driving']) && $driverSkills['night_driving'] ? 'checked' : ''; ?>>
                            <label for="night_driving">Νυχτερινή Οδήγηση</label>
                        </div>
                        <div class="skill-checkbox">
                            <input type="checkbox" id="mountain_driving" name="skills[mountain_driving]" value="1" <?php echo isset($driverSkills['mountain_driving']) && $driverSkills['mountain_driving'] ? 'checked' : ''; ?>>
                            <label for="mountain_driving">Ορεινή Οδήγηση</label>
                        </div>
                        <div class="skill-checkbox">
                            <input type="checkbox" id="extreme_conditions" name="skills[extreme_conditions]" value="1" <?php echo isset($driverSkills['extreme_conditions']) && $driverSkills['extreme_conditions'] ? 'checked' : ''; ?>>
                            <label for="extreme_conditions">Οδήγηση σε Ακραίες Συνθήκες</label>
                        </div>
                    </div>
                </div>

                <!-- Ασφάλεια & Συμμόρφωση -->
                <div class="skills-category">
                    <h3>Ασφάλεια & Συμμόρφωση</h3>
                    <div class="skills-options">
                        <div class="skill-checkbox">
                            <input type="checkbox" id="loading_securing" name="skills[loading_securing]" value="1" <?php echo isset($driverSkills['loading_securing']) && $driverSkills['loading_securing'] ? 'checked' : ''; ?>>
                            <label for="loading_securing">Φόρτωση & Ασφάλιση Φορτίου</label>
                        </div>
                        <div class="skill-checkbox">
                            <input type="checkbox" id="emergency_response" name="skills[emergency_response]" value="1" <?php echo isset($driverSkills['emergency_response']) && $driverSkills['emergency_response'] ? 'checked' : ''; ?>>
                            <label for="emergency_response">Αντιμετώπιση Έκτακτων Καταστάσεων</label>
                        </div>
                        <div class="skill-checkbox">
                            <input type="checkbox" id="first_aid" name="skills[first_aid]" value="1" <?php echo isset($driverSkills['first_aid']) && $driverSkills['first_aid'] ? 'checked' : ''; ?>>
                            <label for="first_aid">Πρώτες Βοήθειες</label>
                        </div>
                        <div class="skill-checkbox">
                            <input type="checkbox" id="dangerous_goods" name="skills[dangerous_goods]" value="1" <?php echo isset($driverSkills['dangerous_goods']) && $driverSkills['dangerous_goods'] ? 'checked' : ''; ?>>
                            <label for="dangerous_goods">Μεταφορά Επικίνδυνων Εμπορευμάτων</label>
                        </div>
                        <div class="skill-checkbox">
                            <input type="checkbox" id="tacograph_compliance" name="skills[tacograph_compliance]" value="1" <?php echo isset($driverSkills['tacograph_compliance']) && $driverSkills['tacograph_compliance'] ? 'checked' : ''; ?>>
                            <label for="tacograph_compliance">Συμμόρφωση με Κανονισμούς Ταχογράφου</label>
                        </div>
                    </div>
                </div>

                <!-- Επαγγελματισμός -->
                <div class="skills-category">
                    <h3>Επαγγελματισμός</h3>
                    <div class="skills-options">
                        <div class="skill-checkbox">
                            <input type="checkbox" id="customer_service" name="skills[customer_service]" value="1" <?php echo isset($driverSkills['customer_service']) && $driverSkills['customer_service'] ? 'checked' : ''; ?>>
                            <label for="customer_service">Εξυπηρέτηση Πελατών</label>
                        </div>
                        <div class="skill-checkbox">
                            <input type="checkbox" id="time_management" name="skills[time_management]" value="1" <?php echo isset($driverSkills['time_management']) && $driverSkills['time_management'] ? 'checked' : ''; ?>>
                            <label for="time_management">Διαχείριση Χρόνου</label>
                        </div>
                        <div class="skill-checkbox">
                            <input type="checkbox" id="route_planning" name="skills[route_planning]" value="1" <?php echo isset($driverSkills['route_planning']) && $driverSkills['route_planning'] ? 'checked' : ''; ?>>
                            <label for="route_planning">Σχεδιασμός Διαδρομής</label>
                        </div>
                        <div class="skill-checkbox">
                            <input type="checkbox" id="conflict_resolution" name="skills[conflict_resolution]" value="1" <?php echo isset($driverSkills['conflict_resolution']) && $driverSkills['conflict_resolution'] ? 'checked' : ''; ?>>
                            <label for="conflict_resolution">Επίλυση Συγκρούσεων</label>
                        </div>
                        <div class="skill-checkbox">
                            <input type="checkbox" id="multilingual" name="skills[multilingual]" value="1" <?php echo isset($driverSkills['multilingual']) && $driverSkills['multilingual'] ? 'checked' : ''; ?>>
                            <label for="multilingual">Πολύγλωσσος</label>
                        </div>
                    </div>
                </div>

                <!-- Τεχνικές Γνώσεις -->
                <div class="skills-category">
                    <h3>Τεχνικές Γνώσεις</h3>
                    <div class="skills-options">
                        <div class="skill-checkbox">
                            <input type="checkbox" id="vehicle_maintenance" name="skills[vehicle_maintenance]" value="1" <?php echo isset($driverSkills['vehicle_maintenance']) && $driverSkills['vehicle_maintenance'] ? 'checked' : ''; ?>>
                            <label for="vehicle_maintenance">Συντήρηση Οχήματος</label>
                        </div>
                        <div class="skill-checkbox">
                            <input type="checkbox" id="troubleshooting" name="skills[troubleshooting]" value="1" <?php echo isset($driverSkills['troubleshooting']) && $driverSkills['troubleshooting'] ? 'checked' : ''; ?>>
                            <label for="troubleshooting">Επίλυση Τεχνικών Προβλημάτων</label>
                        </div>
                        <div class="skill-checkbox">
                            <input type="checkbox" id="digital_tachograph" name="skills[digital_tachograph]" value="1" <?php echo isset($driverSkills['digital_tachograph']) && $driverSkills['digital_tachograph'] ? 'checked' : ''; ?>>
                            <label for="digital_tachograph">Χρήση Ψηφιακού Ταχογράφου</label>
                        </div>
                        <div class="skill-checkbox">
                            <input type="checkbox" id="gps_systems" name="skills[gps_systems]" value="1" <?php echo isset($driverSkills['gps_systems']) && $driverSkills['gps_systems'] ? 'checked' : ''; ?>>
                            <label for="gps_systems">Συστήματα GPS & Πλοήγησης</label>
                        </div>
                        <div class="skill-checkbox">
                            <input type="checkbox" id="logistics_software" name="skills[logistics_software]" value="1" <?php echo isset($driverSkills['logistics_software']) && $driverSkills['logistics_software'] ? 'checked' : ''; ?>>
                            <label for="logistics_software">Λογισμικό Logistics</label>
                        </div>
                    </div>
                </div>

                <!-- Επιπλέον Δεξιότητες -->
                <div class="skills-category">
                    <h3>Επιπλέον Δεξιότητες</h3>
                    <div class="form-group">
                        <label for="additional_skills">Περιγραφή Επιπλέον Δεξιοτήτων</label>
                        <textarea id="additional_skills" name="additional_skills" rows="5" placeholder="Περιγράψτε άλλες επαγγελματικές δεξιότητες που διαθέτετε..."><?php echo htmlspecialchars($driverData['additional_skills'] ?? ''); ?></textarea>
                    </div>
                </div>
            </section>

            <!-- Γλωσσικές Ικανότητες -->
            <section class="edit-section">
                <h2>Γλωσσικές Ικανότητες</h2>

                <div class="language-grid">
                    <div class="language-item">
                        <h3>Ελληνικά</h3>
                        <div class="form-group">
                            <label for="language_greek">Επίπεδο</label>
                            <select id="language_greek" name="languages[greek]">
                                <option value="native" <?php echo isset($driverData['language_greek']) && $driverData['language_greek'] === 'native' ? 'selected' : ''; ?>>Μητρική Γλώσσα</option>
                                <option value="fluent" <?php echo isset($driverData['language_greek']) && $driverData['language_greek'] === 'fluent' ? 'selected' : ''; ?>>Άριστα</option>
                                <option value="good" <?php echo isset($driverData['language_greek']) && $driverData['language_greek'] === 'good' ? 'selected' : ''; ?>>Καλά</option>
                                <option value="basic" <?php echo isset($driverData['language_greek']) && $driverData['language_greek'] === 'basic' ? 'selected' : ''; ?>>Βασικά</option>
                            </select>
                        </div>
                    </div>

                    <div class="language-item">
                        <h3>Αγγλικά</h3>
                        <div class="form-group">
                            <label for="language_english">Επίπεδο</label>
                            <select id="language_english" name="languages[english]">
                                <option value="">Δεν γνωρίζω</option>
                                <option value="native" <?php echo isset($driverData['language_english']) && $driverData['language_english'] === 'native' ? 'selected' : ''; ?>>Μητρική Γλώσσα</option>
                                <option value="fluent" <?php echo isset($driverData['language_english']) && $driverData['language_english'] === 'fluent' ? 'selected' : ''; ?>>Άριστα</option>
                                <option value="good" <?php echo isset($driverData['language_english']) && $driverData['language_english'] === 'good' ? 'selected' : ''; ?>>Καλά</option>
                                <option value="basic" <?php echo isset($driverData['language_english']) && $driverData['language_english'] === 'basic' ? 'selected' : ''; ?>>Βασικά</option>
                            </select>
                        </div>
                    </div>

                    <div class="language-item">
                        <h3>Γερμανικά</h3>
                        <div class="form-group">
                            <label for="language_german">Επίπεδο</label>
                            <select id="language_german" name="languages[german]">
                                <option value="">Δεν γνωρίζω</option>
                                <option value="native" <?php echo isset($driverData['language_german']) && $driverData['language_german'] === 'native' ? 'selected' : ''; ?>>Μητρική Γλώσσα</option>
                                <option value="fluent" <?php echo isset($driverData['language_german']) && $driverData['language_german'] === 'fluent' ? 'selected' : ''; ?>>Άριστα</option>
                                <option value="good" <?php echo isset($driverData['language_german']) && $driverData['language_german'] === 'good' ? 'selected' : ''; ?>>Καλά</option>
                                <option value="basic" <?php echo isset($driverData['language_german']) && $driverData['language_german'] === 'basic' ? 'selected' : ''; ?>>Βασικά</option>
                            </select>
                        </div>
                    </div>

                    <div class="language-item">
                        <h3>Γαλλικά</h3>
                        <div class="form-group">
                            <label for="language_french">Επίπεδο</label>
                            <select id="language_french" name="languages[french]">
                                <option value="">Δεν γνωρίζω</option>
                                <option value="native" <?php echo isset($driverData['language_french']) && $driverData['language_french'] === 'native' ? 'selected' : ''; ?>>Μητρική Γλώσσα</option>
                                <option value="fluent" <?php echo isset($driverData['language_french']) && $driverData['language_french'] === 'fluent' ? 'selected' : ''; ?>>Άριστα</option>
                                <option value="good" <?php echo isset($driverData['language_french']) && $driverData['language_french'] === 'good' ? 'selected' : ''; ?>>Καλά</option>
                                <option value="basic" <?php echo isset($driverData['language_french']) && $driverData['language_french'] === 'basic' ? 'selected' : ''; ?>>Βασικά</option>
                            </select>
                        </div>
                    </div>

                    <div class="language-item">
                        <h3>Ιταλικά</h3>
                        <div class="form-group">
                            <label for="language_italian">Επίπεδο</label>
                            <select id="language_italian" name="languages[italian]">
                                <option value="">Δεν γνωρίζω</option>
                                <option value="native" <?php echo isset($driverData['language_italian']) && $driverData['language_italian'] === 'native' ? 'selected' : ''; ?>>Μητρική Γλώσσα</option>
                                <option value="fluent" <?php echo isset($driverData['language_italian']) && $driverData['language_italian'] === 'fluent' ? 'selected' : ''; ?>>Άριστα</option>
                                <option value="good" <?php echo isset($driverData['language_italian']) && $driverData['language_italian'] === 'good' ? 'selected' : ''; ?>>Καλά</option>
                                <option value="basic" <?php echo isset($driverData['language_italian']) && $driverData['language_italian'] === 'basic' ? 'selected' : ''; ?>>Βασικά</option>
                            </select>
                        </div>
                    </div>

                    <div class="language-item other-language">
                        <h3>Άλλη Γλώσσα</h3>
                        <div class="form-group">
                            <label for="language_other_name">Όνομα Γλώσσας</label>
                            <input type="text" id="language_other_name" name="languages[other_name]" value="<?php echo htmlspecialchars($driverData['language_other_name'] ?? ''); ?>" placeholder="π.χ. Ισπανικά">
                        </div>
                        <div class="form-group">
                            <label for="language_other_level">Επίπεδο</label>
                            <select id="language_other_level" name="languages[other_level]">
                                <option value="">Δεν γνωρίζω</option>
                                <option value="native" <?php echo isset($driverData['language_other_level']) && $driverData['language_other_level'] === 'native' ? 'selected' : ''; ?>>Μητρική Γλώσσα</option>
                                <option value="fluent" <?php echo isset($driverData['language_other_level']) && $driverData['language_other_level'] === 'fluent' ? 'selected' : ''; ?>>Άριστα</option>
                                <option value="good" <?php echo isset($driverData['language_other_level']) && $driverData['language_other_level'] === 'good' ? 'selected' : ''; ?>>Καλά</option>
                                <option value="basic" <?php echo isset($driverData['language_other_level']) && $driverData['language_other_level'] === 'basic' ? 'selected' : ''; ?>>Βασικά</option>
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Πιστοποιήσεις και Σεμινάρια -->
            <section class="edit-section">
                <h2>Πιστοποιήσεις και Σεμινάρια</h2>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="training_seminars" value="1" <?php echo isset($driverData['training_seminars']) && $driverData['training_seminars'] ? 'checked' : ''; ?>>
                        Έχω παρακολουθήσει σεμινάρια επαγγελματικής κατάρτισης
                    </label>
                </div>

                <div class="certification-section" id="certification-section" <?php echo isset($driverData['training_seminars']) && $driverData['training_seminars'] ? '' : 'style="display: none;"'; ?>>
                    <div class="form-group">
                        <label for="training_details">Λεπτομέρειες Σεμιναρίων και Πιστοποιήσεων</label>
                        <textarea id="training_details" name="training_details" rows="5" placeholder="Περιγράψτε τα σεμινάρια που έχετε παρακολουθήσει, με ημερομηνίες, διάρκεια και φορέα διοργάνωσης..."><?php echo htmlspecialchars($driverData['training_details'] ?? ''); ?></textarea>
                    </div>

                    <div class="certification-list" id="certification-list">
                        <?php if (isset($driverCertifications) && !empty($driverCertifications)) : ?>
                            <?php foreach ($driverCertifications as $index => $certification) : ?>
                                <div class="certification-item">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="cert_title_<?php echo $index; ?>">Τίτλος Πιστοποίησης</label>
                                            <input type="text" id="cert_title_<?php echo $index; ?>" name="certifications[<?php echo $index; ?>][title]" value="<?php echo htmlspecialchars($certification['title']); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="cert_provider_<?php echo $index; ?>">Φορέας</label>
                                            <input type="text" id="cert_provider_<?php echo $index; ?>" name="certifications[<?php echo $index; ?>][provider]" value="<?php echo htmlspecialchars($certification['provider']); ?>">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="cert_date_<?php echo $index; ?>">Ημερομηνία</label>
                                            <input type="date" id="cert_date_<?php echo $index; ?>" name="certifications[<?php echo $index; ?>][date]" value="<?php echo htmlspecialchars($certification['date']); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label for="cert_expiry_<?php echo $index; ?>">Ημερομηνία Λήξης (αν υπάρχει)</label>
                                            <input type="date" id="cert_expiry_<?php echo $index; ?>" name="certifications[<?php echo $index; ?>][expiry]" value="<?php echo $certification['expiry'] ?? ''; ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="cert_description_<?php echo $index; ?>">Περιγραφή</label>
                                        <textarea id="cert_description_<?php echo $index; ?>" name="certifications[<?php echo $index; ?>][description]" rows="3"><?php echo htmlspecialchars($certification['description']); ?></textarea>
                                    </div>
                                    <button type="button" class="btn-remove-cert">Αφαίρεση</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="certification-item">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="cert_title_0">Τίτλος Πιστοποίησης</label>
                                        <input type="text" id="cert_title_0" name="certifications[0][title]" placeholder="π.χ. Σεμινάριο Αμυντικής Οδήγησης">
                                    </div>
                                    <div class="form-group">
                                        <label for="cert_provider_0">Φορέας</label>
                                        <input type="text" id="cert_provider_0" name="certifications[0][provider]" placeholder="π.χ. Κέντρο Επαγγελματικής Κατάρτισης">
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="cert_date_0">Ημερομηνία</label>
                                        <input type="date" id="cert_date_0" name="certifications[0][date]">
                                    </div>
                                    <div class="form-group">
                                        <label for="cert_expiry_0">Ημερομηνία Λήξης (αν υπάρχει)</label>
                                        <input type="date" id="cert_expiry_0" name="certifications[0][expiry]">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="cert_description_0">Περιγραφή</label>
                                    <textarea id="cert_description_0" name="certifications[0][description]" rows="3" placeholder="Σύντομη περιγραφή του περιεχομένου της πιστοποίησης..."></textarea>
                                </div>
                                <button type="button" class="btn-remove-cert">Αφαίρεση</button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="button" id="add-certification" class="btn-secondary">Προσθήκη Πιστοποίησης</button>
                </div>
            </section>

            <!-- Κουμπιά υποβολής -->
            <div class="form-actions">
                <button type="submit" class="btn-primary">Αποθήκευση</button>
                <a href="<?php echo BASE_URL; ?>drivers/driver_profile" class="btn-secondary">Ακύρωση</a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Εμφάνιση/απόκρυψη τμήματος πιστοποιήσεων
            const trainingSeminarsCheckbox = document.querySelector('input[name="training_seminars"]');
            const certificationSection = document.getElementById('certification-section');

            if (trainingSeminarsCheckbox && certificationSection) {
                trainingSeminarsCheckbox.addEventListener('change', function() {
                    certificationSection.style.display = this.checked ? 'block' : 'none';
                });
            }

            // Προσθήκη νέας πιστοποίησης
            const addCertificationBtn = document.getElementById('add-certification');
            const certificationList = document.getElementById('certification-list');

            if (addCertificationBtn && certificationList) {
                addCertificationBtn.addEventListener('click', function() {
                    const certItems = certificationList.querySelectorAll('.certification-item');
                    const newIndex = certItems.length;

                    const newCertHTML = `
                        <div class="certification-item">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="cert_title_${newIndex}">Τίτλος Πιστοποίησης</label>
                                    <input type="text" id="cert_title_${newIndex}" name="certifications[${newIndex}][title]" placeholder="π.χ. Σεμινάριο Αμυντικής Οδήγησης">
                                </div>
                                <div class="form-group">
                                    <label for="cert_provider_${newIndex}">Φορέας</label>
                                    <input type="text" id="cert_provider_${newIndex}" name="certifications[${newIndex}][provider]" placeholder="π.χ. Κέντρο Επαγγελματικής Κατάρτισης">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="cert_date_${newIndex}">Ημερομηνία</label>
                                    <input type="date" id="cert_date_${newIndex}" name="certifications[${newIndex}][date]">
                                </div>
                                <div class="form-group">
                                    <label for="cert_expiry_${newIndex}">Ημερομηνία Λήξης (αν υπάρχει)</label>
                                    <input type="date" id="cert_expiry_${newIndex}" name="certifications[${newIndex}][expiry]">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="cert_description_${newIndex}">Περιγραφή</label>
                                <textarea id="cert_description_${newIndex}" name="certifications[${newIndex}][description]" rows="3" placeholder="Σύντομη περιγραφή του περιεχομένου της πιστοποίησης..."></textarea>
                            </div>
                            <button type="button" class="btn-remove-cert">Αφαίρεση</button>
                        </div>
                    `;

                    certificationList.insertAdjacentHTML('beforeend', newCertHTML);
                    addRemoveCertListeners();
                });

                // Αφαίρεση πιστοποίησης
                function addRemoveCertListeners() {
                    document.querySelectorAll('.btn-remove-cert').forEach(button => {
                        button.addEventListener('click', function() {
                            const certItem = this.closest('.certification-item');
                            if (certItem) {
                                certItem.remove();

                                // Ενημέρωση των δεικτών (indexes) αν υπάρχουν περισσότερες πιστοποιήσεις
                                // Αυτό είναι απαραίτητο για να παραμείνει συνεχόμενη η αρίθμηση
                                const certItems = certificationList.querySelectorAll('.certification-item');
                                certItems.forEach((item, index) => {
                                    const inputs = item.querySelectorAll('input, textarea');
                                    inputs.forEach(input => {
                                        const name = input.getAttribute('name');
                                        if (name) {
                                            const newName = name.replace(/certifications\[\d+\]/, `certifications[${index}]`);
                                            input.setAttribute('name', newName);
                                        }

                                        const id = input.getAttribute('id');
                                        if (id) {
                                            const newId = id.replace(/_\d+$/, `_${index}`);
                                            input.setAttribute('id', newId);
                                        }
                                    });

                                    const labels = item.querySelectorAll('label');
                                    labels.forEach(label => {
                                        const forAttr = label.getAttribute('for');
                                        if (forAttr) {
                                            const newFor = forAttr.replace(/_\d+$/, `_${index}`);
                                            label.setAttribute('for', newFor);
                                        }
                                    });
                                });
                            }
                        });
                    });
                }

                // Αρχικοποίηση των listeners αφαίρεσης
                addRemoveCertListeners();
            }
        });
    </script>
</main>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php';
?>
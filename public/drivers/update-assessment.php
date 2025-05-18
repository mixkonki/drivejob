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
$assessmentModel = new \Drivejob\Models\DriversAssessmentModel($pdo);
$driverId = $_SESSION['user_id'];

// Λήψη των στοιχείων του οδηγού
$driverData = $profileModel->getDriverById($driverId);

// Λήψη των δεδομένων αυτοαξιολόγησης του οδηγού
$driverAssessment = $assessmentModel->getDriverAssessment($driverId);

// Έλεγχος αν υπάρχει υποβολή φόρμας
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Επεξεργασία της φόρμας και αποθήκευση της αυτοαξιολόγησης
    $result = $assessmentModel->updateDriverAssessment($driverId, $_POST['assessment'] ?? []);

    if ($result) {
        $_SESSION['success_message'] = 'Η αυτοαξιολόγησή σας ενημερώθηκε με επιτυχία.';
        header('Location: ' . BASE_URL . 'drivers/driver_profile#self-assessment');
        exit();
    } else {
        $_SESSION['error_message'] = 'Υπήρξε ένα σφάλμα κατά την ενημέρωση της αυτοαξιολόγησής σας.';
    }
}

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<!-- Σύνδεση με το CSS αρχείο του προφίλ οδηγού και της αυτοαξιολόγησης -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver_profile.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver-assessment.css">

<main>
    <div class="container">
        <div class="page-header">
            <h1>Αυτοαξιολόγηση Οδηγού</h1>
            <p>Αξιολογήστε τις οδηγικές και επαγγελματικές σας ικανότητες για να βελτιώσετε το προφίλ σας.</p>
        </div>

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

        <!-- Εμφάνιση των μετρήσεων που έχουν συλλεχθεί από το κινητό (αν υπάρχουν) -->
        <?php if (isset($driverAssessment['telemetry_data']) && !empty($driverAssessment['telemetry_data'])): ?>
            <section class="assessment-section telematics-section">
                <h2>Δεδομένα Τηλεματικής</h2>
                <div class="telematics-data">
                    <div class="telematics-info">
                        <p>Τα παρακάτω δεδομένα έχουν συλλεχθεί αυτόματα από την εφαρμογή παρακολούθησης οδηγικής συμπεριφοράς τις τελευταίες 30 ημέρες.</p>
                    </div>

                    <div class="telematics-metrics">
                        <div class="metric-card">
                            <div class="metric-icon speed-icon"></div>
                            <div class="metric-details">
                                <h3>Μέση Ταχύτητα</h3>
                                <div class="metric-value"><?php echo number_format($driverAssessment['telemetry_data']['avg_speed'], 1); ?> χλμ/ώρα</div>
                            </div>
                        </div>

                        <div class="metric-card">
                            <div class="metric-icon brake-icon"></div>
                            <div class="metric-details">
                                <h3>Απότομα Φρεναρίσματα</h3>
                                <div class="metric-value"><?php echo $driverAssessment['telemetry_data']['harsh_braking']; ?> περιστατικά</div>
                            </div>
                        </div>

                        <div class="metric-card">
                            <div class="metric-icon acceleration-icon"></div>
                            <div class="metric-details">
                                <h3>Απότομες Επιταχύνσεις</h3>
                                <div class="metric-value"><?php echo $driverAssessment['telemetry_data']['harsh_acceleration']; ?> περιστατικά</div>
                            </div>
                        </div>

                        <div class="metric-card">
                            <div class="metric-icon turn-icon"></div>
                            <div class="metric-details">
                                <h3>Απότομες Στροφές</h3>
                                <div class="metric-value"><?php echo $driverAssessment['telemetry_data']['harsh_cornering']; ?> περιστατικά</div>
                            </div>
                        </div>

                        <div class="metric-card">
                            <div class="metric-icon fuel-icon"></div>
                            <div class="metric-details">
                                <h3>Κατανάλωση Καυσίμου</h3>
                                <div class="metric-value"><?php echo number_format($driverAssessment['telemetry_data']['fuel_consumption'], 1); ?> λίτρα/100χλμ</div>
                            </div>
                        </div>

                        <div class="metric-card">
                            <div class="metric-icon distance-icon"></div>
                            <div class="metric-details">
                                <h3>Απόσταση</h3>
                                <div class="metric-value"><?php echo number_format($driverAssessment['telemetry_data']['total_distance']); ?> χλμ</div>
                            </div>
                        </div>
                    </div>

                    <div class="telematics-score">
                        <div class="score-circle">
                            <svg viewBox="0 0 100 100">
                                <circle class="score-background" cx="50" cy="50" r="45"></circle>
                                <circle class="score-progress" cx="50" cy="50" r="45" style="stroke-dashoffset: calc(283.5 - (283.5 * <?php echo $driverAssessment['telemetry_data']['score']; ?>) / 100)"></circle>
                            </svg>
                            <div class="score-text">
                                <span class="score-value"><?php echo $driverAssessment['telemetry_data']['score']; ?></span>
                                <span class="score-label">Βαθμολογία Τηλεματικής</span>
                            </div>
                        </div>
                    </div>

                    <div class="telematics-info-link">
                        <p>Κατεβάστε την εφαρμογή <a href="#">DriveJob Telemetry</a> για να βελτιώσετε τη βαθμολογία σας μέσω της αυτόματης παρακολούθησης της οδηγικής σας συμπεριφοράς.</p>
                    </div>
                </div>
            </section>
        <?php else: ?>
            <section class="assessment-section telematics-section inactive">
                <h2>Δεδομένα Τηλεματικής</h2>
                <div class="telematics-data">
                    <div class="telematics-info">
                        <p>Δεν έχουν συλλεχθεί δεδομένα τηλεματικής. Κατεβάστε την εφαρμογή <a href="#">DriveJob Telemetry</a> για να βελτιώσετε τη βαθμολογία σας μέσω της αυτόματης παρακολούθησης της οδηγικής σας συμπεριφοράς.</p>
                    </div>

                    <div class="app-download">
                        <a href="#" class="btn-app-download">
                            <img src="<?php echo BASE_URL; ?>img/app_download.png" alt="Κατέβασμα Εφαρμογής">
                            <span>Κατέβασμα Εφαρμογής</span>
                        </a>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- Φόρμα αυτοαξιολόγησης -->
        <form method="POST" action="" id="assessment-form">
            <?php echo \Drivejob\Core\CSRF::tokenField(); ?>

            <!-- Φόρμα αυτοαξιολόγησης -->
            <form method="POST" action="" id="assessment-form">
                <?php echo \Drivejob\Core\CSRF::tokenField(); ?>

                <!-- Οδηγικές Ικανότητες -->
                <section class="assessment-section">
                    <h2>Οδηγικές Ικανότητες</h2>

                    <div class="question-group">
                        <div class="question-item">
                            <label for="punctuality">Πόσο συχνά τηρείτε τα χρονοδιαγράμματα παράδοσης/παραλαβής;</label>
                            <select id="punctuality" name="assessment[punctuality]" required>
                                <option value="">Επιλέξτε</option>
                                <option value="1" <?php echo isset($driverAssessment['punctuality']) && $driverAssessment['punctuality'] == 1 ? 'selected' : ''; ?>>Συχνά καθυστερώ</option>
                                <option value="2" <?php echo isset($driverAssessment['punctuality']) && $driverAssessment['punctuality'] == 2 ? 'selected' : ''; ?>>Μερικές φορές καθυστερώ</option>
                                <option value="3" <?php echo isset($driverAssessment['punctuality']) && $driverAssessment['punctuality'] == 3 ? 'selected' : ''; ?>>Συνήθως είμαι στην ώρα μου</option>
                                <option value="4" <?php echo isset($driverAssessment['punctuality']) && $driverAssessment['punctuality'] == 4 ? 'selected' : ''; ?>>Σχεδόν πάντα είμαι στην ώρα μου</option>
                                <option value="5" <?php echo isset($driverAssessment['punctuality']) && $driverAssessment['punctuality'] == 5 ? 'selected' : ''; ?>>Πάντα είμαι στην ώρα μου ή νωρίτερα</option>
                            </select>
                        </div>

                        <div class="question-item">
                            <label for="customer_interaction">Πώς αξιολογείτε την επικοινωνία σας με πελάτες/παραλήπτες;</label>
                            <select id="customer_interaction" name="assessment[customer_interaction]" required>
                                <option value="">Επιλέξτε</option>
                                <option value="1" <?php echo isset($driverAssessment['customer_interaction']) && $driverAssessment['customer_interaction'] == 1 ? 'selected' : ''; ?>>Περιορισμένη επικοινωνία, μόνο τα απαραίτητα</option>
                                <option value="2" <?php echo isset($driverAssessment['customer_interaction']) && $driverAssessment['customer_interaction'] == 2 ? 'selected' : ''; ?>>Βασική επικοινωνία χωρίς ιδιαίτερη προσοχή</option>
                                <option value="3" <?php echo isset($driverAssessment['customer_interaction']) && $driverAssessment['customer_interaction'] == 3 ? 'selected' : ''; ?>>Ευγενική αλλά τυπική επικοινωνία</option>
                                <option value="4" <?php echo isset($driverAssessment['customer_interaction']) && $driverAssessment['customer_interaction'] == 4 ? 'selected' : ''; ?>>Φιλική και εξυπηρετική συμπεριφορά</option>
                                <option value="5" <?php echo isset($driverAssessment['customer_interaction']) && $driverAssessment['customer_interaction'] == 5 ? 'selected' : ''; ?>>Εξαιρετική επικοινωνία και εξυπηρέτηση</option>
                            </select>
                        </div>

                        <div class="question-item">
                            <label for="appearance">Πόσο προσέχετε την επαγγελματική σας εμφάνιση;</label>
                            <select id="appearance" name="assessment[appearance]" required>
                                <option value="">Επιλέξτε</option>
                                <option value="1" <?php echo isset($driverAssessment['appearance']) && $driverAssessment['appearance'] == 1 ? 'selected' : ''; ?>>Δεν δίνω ιδιαίτερη σημασία</option>
                                <option value="2" <?php echo isset($driverAssessment['appearance']) && $driverAssessment['appearance'] == 2 ? 'selected' : ''; ?>>Προσέχω μόνο τα βασικά</option>
                                <option value="3" <?php echo isset($driverAssessment['appearance']) && $driverAssessment['appearance'] == 3 ? 'selected' : ''; ?>>Φροντίζω για μια αξιοπρεπή εμφάνιση</option>
                                <option value="4" <?php echo isset($driverAssessment['appearance']) && $driverAssessment['appearance'] == 4 ? 'selected' : ''; ?>>Προσέχω ιδιαίτερα την επαγγελματική μου εμφάνιση</option>
                                <option value="5" <?php echo isset($driverAssessment['appearance']) && $driverAssessment['appearance'] == 5 ? 'selected' : ''; ?>>Πάντα άψογη επαγγελματική εμφάνιση</option>
                            </select>
                        </div>

                        <div class="question-item">
                            <label for="documentation">Πόσο προσεκτικοί είστε με τη συμπλήρωση/διαχείριση των εγγράφων;</label>
                            <select id="documentation" name="assessment[documentation]" required>
                                <option value="">Επιλέξτε</option>
                                <option value="1" <?php echo isset($driverAssessment['documentation']) && $driverAssessment['documentation'] == 1 ? 'selected' : ''; ?>>Συχνά κάνω λάθη ή παραλείψεις</option>
                                <option value="2" <?php echo isset($driverAssessment['documentation']) && $driverAssessment['documentation'] == 2 ? 'selected' : ''; ?>>Μερικές φορές κάνω λάθη</option>
                                <option value="3" <?php echo isset($driverAssessment['documentation']) && $driverAssessment['documentation'] == 3 ? 'selected' : ''; ?>>Συνήθως συμπληρώνω σωστά τα έγγραφα</option>
                                <option value="4" <?php echo isset($driverAssessment['documentation']) && $driverAssessment['documentation'] == 4 ? 'selected' : ''; ?>>Προσεκτικός με όλα τα έγγραφα</option>
                                <option value="5" <?php echo isset($driverAssessment['documentation']) && $driverAssessment['documentation'] == 5 ? 'selected' : ''; ?>>Πάντα άψογη και λεπτομερής διαχείριση εγγράφων</option>
                            </select>
                        </div>
                    </div>
                </section>

                <!-- Τεχνικές Γνώσεις -->
                <section class="assessment-section">
                    <h2>Τεχνικές Γνώσεις</h2>

                    <div class="question-group">
                        <div class="question-item">
                            <label for="vehicle_maintenance">Πόσο καλές είναι οι γνώσεις σας σχετικά με τη συντήρηση οχημάτων;</label>
                            <select id="vehicle_maintenance" name="assessment[vehicle_maintenance]" required>
                                <option value="">Επιλέξτε</option>
                                <option value="1" <?php echo isset($driverAssessment['vehicle_maintenance']) && $driverAssessment['vehicle_maintenance'] == 1 ? 'selected' : ''; ?>>Ελάχιστες γνώσεις</option>
                                <option value="2" <?php echo isset($driverAssessment['vehicle_maintenance']) && $driverAssessment['vehicle_maintenance'] == 2 ? 'selected' : ''; ?>>Βασικές γνώσεις</option>
                                <option value="3" <?php echo isset($driverAssessment['vehicle_maintenance']) && $driverAssessment['vehicle_maintenance'] == 3 ? 'selected' : ''; ?>>Μέτριες γνώσεις</option>
                                <option value="4" <?php echo isset($driverAssessment['vehicle_maintenance']) && $driverAssessment['vehicle_maintenance'] == 4 ? 'selected' : ''; ?>>Καλές γνώσεις</option>
                                <option value="5" <?php echo isset($driverAssessment['vehicle_maintenance']) && $driverAssessment['vehicle_maintenance'] == 5 ? 'selected' : ''; ?>>Άριστες γνώσεις</option>
                            </select>
                        </div>

                        <div class="question-item">
                            <label for="troubleshooting">Πόσο καλός είστε στην επίλυση τεχνικών προβλημάτων του οχήματος;</label>
                            <select id="troubleshooting" name="assessment[troubleshooting]" required>
                                <option value="">Επιλέξτε</option>
                                <option value="1" <?php echo isset($driverAssessment['troubleshooting']) && $driverAssessment['troubleshooting'] == 1 ? 'selected' : ''; ?>>Καλώ πάντα βοήθεια για κάθε πρόβλημα</option>
                                <option value="2" <?php echo isset($driverAssessment['troubleshooting']) && $driverAssessment['troubleshooting'] == 2 ? 'selected' : ''; ?>>Μπορώ να λύσω μόνο πολύ απλά προβλήματα</option>
                                <option value="3" <?php echo isset($driverAssessment['troubleshooting']) && $driverAssessment['troubleshooting'] == 3 ? 'selected' : ''; ?>>Λύνω αρκετά συνηθισμένα προβλήματα</option>
                                <option value="4" <?php echo isset($driverAssessment['troubleshooting']) && $driverAssessment['troubleshooting'] == 4 ? 'selected' : ''; ?>>Αντιμετωπίζω τα περισσότερα προβλήματα</option>
                                <option value="5" <?php echo isset($driverAssessment['troubleshooting']) && $driverAssessment['troubleshooting'] == 5 ? 'selected' : ''; ?>>Επιλύω σχεδόν όλα τα τεχνικά προβλήματα</option>
                            </select>
                        </div>

                        <div class="question-item">
                            <label for="navigation_skills">Πόσο καλός είστε στην πλοήγηση και εύρεση διαδρομών;</label>
                            <select id="navigation_skills" name="assessment[navigation_skills]" required>
                                <option value="">Επιλέξτε</option>
                                <option value="1" <?php echo isset($driverAssessment['navigation_skills']) && $driverAssessment['navigation_skills'] == 1 ? 'selected' : ''; ?>>Βασίζομαι πλήρως στο GPS</option>
                                <option value="2" <?php echo isset($driverAssessment['navigation_skills']) && $driverAssessment['navigation_skills'] == 2 ? 'selected' : ''; ?>>Συχνά χάνομαι χωρίς GPS</option>
                                <option value="3" <?php echo isset($driverAssessment['navigation_skills']) && $driverAssessment['navigation_skills'] == 3 ? 'selected' : ''; ?>>Βρίσκω τον δρόμο μου στις περισσότερες περιπτώσεις</option>
                                <option value="4" <?php echo isset($driverAssessment['navigation_skills']) && $driverAssessment['navigation_skills'] == 4 ? 'selected' : ''; ?>>Πολύ καλός στην πλοήγηση, χρήση χάρτη και GPS</option>
                                <option value="5" <?php echo isset($driverAssessment['navigation_skills']) && $driverAssessment['navigation_skills'] == 5 ? 'selected' : ''; ?>>Άριστη γνώση διαδρομών και εναλλακτικών</option>
                            </select>
                        </div>

                        <div class="question-item">
                            <label for="technical_knowledge">Πόσο καλά γνωρίζετε τα τεχνικά χαρακτηριστικά του οχήματός σας;</label>
                            <select id="technical_knowledge" name="assessment[technical_knowledge]" required>
                                <option value="">Επιλέξτε</option>
                                <option value="1" <?php echo isset($driverAssessment['technical_knowledge']) && $driverAssessment['technical_knowledge'] == 1 ? 'selected' : ''; ?>>Ελάχιστη γνώση</option>
                                <option value="2" <?php echo isset($driverAssessment['technical_knowledge']) && $driverAssessment['technical_knowledge'] == 2 ? 'selected' : ''; ?>>Βασική γνώση</option>
                                <option value="3" <?php echo isset($driverAssessment['technical_knowledge']) && $driverAssessment['technical_knowledge'] == 3 ? 'selected' : ''; ?>>Μέτρια γνώση</option>
                                <option value="4" <?php echo isset($driverAssessment['technical_knowledge']) && $driverAssessment['technical_knowledge'] == 4 ? 'selected' : ''; ?>>Καλή γνώση</option>
                                <option value="5" <?php echo isset($driverAssessment['technical_knowledge']) && $driverAssessment['technical_knowledge'] == 5 ? 'selected' : ''; ?>>Άριστη γνώση</option>
                            </select>
                        </div>
                    </div>
                </section>

                <!-- Σχόλια και Παρατηρήσεις -->
                <section class="assessment-section">
                    <h2>Επιπλέον Σχόλια</h2>

                    <div class="form-group">
                        <label for="comments">Προσθέστε οποιαδήποτε σχόλια ή παρατηρήσεις σχετικά με τις οδηγικές σας ικανότητες:</label>
                        <textarea id="comments" name="assessment[comments]" rows="5" placeholder="Περιγράψτε τυχόν επιπλέον πληροφορίες που θεωρείτε σημαντικές..."><?php echo htmlspecialchars($driverAssessment['comments'] ?? ''); ?></textarea>
                    </div>
                </section>

                <!-- Κουμπιά υποβολής -->
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Αποθήκευση Αυτοαξιολόγησης</button>
                    <a href="<?php echo BASE_URL; ?>drivers/driver_profile#self-assessment" class="btn-secondary">Ακύρωση</a>
                </div>
                <div class="question-item">
                    <label for="driving_experience">Πόσα χρόνια οδηγείτε επαγγελματικά;</label>
                    <select id="driving_experience" name="assessment[driving_experience]" required>
                        <option value="">Επιλέξτε</option>
                        <option value="1" <?php echo isset($driverAssessment['driving_experience']) && $driverAssessment['driving_experience'] == 1 ? 'selected' : ''; ?>>Λιγότερο από 1 έτος</option>
                        <option value="2" <?php echo isset($driverAssessment['driving_experience']) && $driverAssessment['driving_experience'] == 2 ? 'selected' : ''; ?>>1-3 έτη</option>
                        <option value="3" <?php echo isset($driverAssessment['driving_experience']) && $driverAssessment['driving_experience'] == 3 ? 'selected' : ''; ?>>3-5 έτη</option>
                        <option value="4" <?php echo isset($driverAssessment['driving_experience']) && $driverAssessment['driving_experience'] == 4 ? 'selected' : ''; ?>>5-10 έτη</option>
                        <option value="5" <?php echo isset($driverAssessment['driving_experience']) && $driverAssessment['driving_experience'] == 5 ? 'selected' : ''; ?>>Περισσότερο από 10 έτη</option>
                    </select>
                </div>

                <div class="question-item">
                    <label for="annual_kilometers">Πόσα χιλιόμετρα οδηγείτε ετησίως;</label>
                    <select id="annual_kilometers" name="assessment[annual_kilometers]" required>
                        <option value="">Επιλέξτε</option>
                        <option value="1" <?php echo isset($driverAssessment['annual_kilometers']) && $driverAssessment['annual_kilometers'] == 1 ? 'selected' : ''; ?>>Λιγότερα από 10.000 χλμ</option>
                        <option value="2" <?php echo isset($driverAssessment['annual_kilometers']) && $driverAssessment['annual_kilometers'] == 2 ? 'selected' : ''; ?>>10.000-30.000 χλμ</option>
                        <option value="3" <?php echo isset($driverAssessment['annual_kilometers']) && $driverAssessment['annual_kilometers'] == 3 ? 'selected' : ''; ?>>30.000-50.000 χλμ</option>
                        <option value="4" <?php echo isset($driverAssessment['annual_kilometers']) && $driverAssessment['annual_kilometers'] == 4 ? 'selected' : ''; ?>>50.000-100.000 χλμ</option>
                        <option value="5" <?php echo isset($driverAssessment['annual_kilometers']) && $driverAssessment['annual_kilometers'] == 5 ? 'selected' : ''; ?>>Περισσότερα από 100.000 χλμ</option>
                    </select>
                </div>

                <div class="question-item">
                    <label for="driving_conditions">Σε ποιες συνθήκες οδηγείτε συχνότερα;</label>
                    <select id="driving_conditions" name="assessment[driving_conditions]" required>
                        <option value="">Επιλέξτε</option>
                        <option value="1" <?php echo isset($driverAssessment['driving_conditions']) && $driverAssessment['driving_conditions'] == 1 ? 'selected' : ''; ?>>Μόνο τοπικές διαδρομές σε αστικό περιβάλλον</option>
                        <option value="2" <?php echo isset($driverAssessment['driving_conditions']) && $driverAssessment['driving_conditions'] == 2 ? 'selected' : ''; ?>>Κυρίως αστικές διαδρομές με λίγα υπεραστικά</option>
                        <option value="3" <?php echo isset($driverAssessment['driving_conditions']) && $driverAssessment['driving_conditions'] == 3 ? 'selected' : ''; ?>>Μικτές συνθήκες (αστικό, υπεραστικό, αυτοκινητόδρομοι)</option>
                        <option value="4" <?php echo isset($driverAssessment['driving_conditions']) && $driverAssessment['driving_conditions'] == 4 ? 'selected' : ''; ?>>Κυρίως αυτοκινητόδρομοι και εθνικές οδοί</option>
                        <option value="5" <?php echo isset($driverAssessment['driving_conditions']) && $driverAssessment['driving_conditions'] == 5 ? 'selected' : ''; ?>>Διεθνείς μεταφορές και δύσκολες συνθήκες</option>
                    </select>
                </div>

                <div class="question-item">
                    <label for="eco_driving_rating">Πώς θα αξιολογούσατε την οικονομική οδήγησή σας;</label>
                    <select id="eco_driving_rating" name="assessment[eco_driving_rating]" required>
                        <option value="">Επιλέξτε</option>
                        <option value="1" <?php echo isset($driverAssessment['eco_driving_rating']) && $driverAssessment['eco_driving_rating'] == 1 ? 'selected' : ''; ?>>Δεν δίνω προσοχή στην κατανάλωση καυσίμου</option>
                        <option value="2" <?php echo isset($driverAssessment['eco_driving_rating']) && $driverAssessment['eco_driving_rating'] == 2 ? 'selected' : ''; ?>>Σπάνια παρακολουθώ την κατανάλωση</option>
                        <option value="3" <?php echo isset($driverAssessment['eco_driving_rating']) && $driverAssessment['eco_driving_rating'] == 3 ? 'selected' : ''; ?>>Μέτρια προσοχή στην οικονομική οδήγηση</option>
                        <option value="4" <?php echo isset($driverAssessment['eco_driving_rating']) && $driverAssessment['eco_driving_rating'] == 4 ? 'selected' : ''; ?>>Συχνά οδηγώ με στόχο την οικονομία καυσίμου</option>
                        <option value="5" <?php echo isset($driverAssessment['eco_driving_rating']) && $driverAssessment['eco_driving_rating'] == 5 ? 'selected' : ''; ?>>Πάντα οδηγώ με στόχο τη βέλτιστη οικονομία καυσίμου</option>
                    </select>
                </div>

                <div class="question-item">
                    <label for="night_driving">Πόσο συχνά οδηγείτε κατά τη διάρκεια της νύχτας;</label>
                    <select id="night_driving" name="assessment[night_driving]" required>
                        <option value="">Επιλέξτε</option>
                        <option value="1" <?php echo isset($driverAssessment['night_driving']) && $driverAssessment['night_driving'] == 1 ? 'selected' : ''; ?>>Σχεδόν ποτέ</option>
                        <option value="2" <?php echo isset($driverAssessment['night_driving']) && $driverAssessment['night_driving'] == 2 ? 'selected' : ''; ?>>Σπάνια (λιγότερο από 10% του χρόνου)</option>
                        <option value="3" <?php echo isset($driverAssessment['night_driving']) && $driverAssessment['night_driving'] == 3 ? 'selected' : ''; ?>>Μερικές φορές (10-30% του χρόνου)</option>
                        <option value="4" <?php echo isset($driverAssessment['night_driving']) && $driverAssessment['night_driving'] == 4 ? 'selected' : ''; ?>>Συχνά (30-50% του χρόνου)</option>
                        <option value="5" <?php echo isset($driverAssessment['night_driving']) && $driverAssessment['night_driving'] == 5 ? 'selected' : ''; ?>>Πολύ συχνά (πάνω από 50% του χρόνου)</option>
                    </select>
                </div>
    </div>
    </section>

    <!-- Ασφάλεια & Συμμόρφωση -->
    <section class="assessment-section">
        <h2>Ασφάλεια & Συμμόρφωση</h2>

        <div class="question-group">
            <div class="question-item">
                <label for="accidents">Πόσα ατυχήματα είχατε τα τελευταία 3 χρόνια;</label>
                <select id="accidents" name="assessment[accidents]" required>
                    <option value="">Επιλέξτε</option>
                    <option value="5" <?php echo isset($driverAssessment['accidents']) && $driverAssessment['accidents'] == 5 ? 'selected' : ''; ?>>Κανένα</option>
                    <option value="4" <?php echo isset($driverAssessment['accidents']) && $driverAssessment['accidents'] == 4 ? 'selected' : ''; ?>>1 μικρό ατύχημα</option>
                    <option value="3" <?php echo isset($driverAssessment['accidents']) && $driverAssessment['accidents'] == 3 ? 'selected' : ''; ?>>1-2 ατυχήματα</option>
                    <option value="2" <?php echo isset($driverAssessment['accidents']) && $driverAssessment['accidents'] == 2 ? 'selected' : ''; ?>>3-4 ατυχήματα</option>
                    <option value="1" <?php echo isset($driverAssessment['accidents']) && $driverAssessment['accidents'] == 1 ? 'selected' : ''; ?>>Περισσότερα από 4 ατυχήματα</option>
                </select>
            </div>

            <div class="question-item">
                <label for="traffic_violations">Πόσες παραβάσεις του Κ.Ο.Κ. είχατε τα τελευταία 3 χρόνια;</label>
                <select id="traffic_violations" name="assessment[traffic_violations]" required>
                    <option value="">Επιλέξτε</option>
                    <option value="5" <?php echo isset($driverAssessment['traffic_violations']) && $driverAssessment['traffic_violations'] == 5 ? 'selected' : ''; ?>>Καμία</option>
                    <option value="4" <?php echo isset($driverAssessment['traffic_violations']) && $driverAssessment['traffic_violations'] == 4 ? 'selected' : ''; ?>>1-2 μικρές παραβάσεις</option>
                    <option value="3" <?php echo isset($driverAssessment['traffic_violations']) && $driverAssessment['traffic_violations'] == 3 ? 'selected' : ''; ?>>3-5 παραβάσεις</option>
                    <option value="2" <?php echo isset($driverAssessment['traffic_violations']) && $driverAssessment['traffic_violations'] == 2 ? 'selected' : ''; ?>>6-10 παραβάσεις</option>
                    <option value="1" <?php echo isset($driverAssessment['traffic_violations']) && $driverAssessment['traffic_violations'] == 1 ? 'selected' : ''; ?>>Περισσότερες από 10 παραβάσεις</option>
                </select>
            </div>

            <div class="question-item">
                <label for="tachograph_compliance">Πόσο συχνά τηρείτε τους κανονισμούς του ταχογράφου και τους χρόνους οδήγησης/ανάπαυσης;</label>
                <select id="tachograph_compliance" name="assessment[tachograph_compliance]" required>
                    <option value="">Επιλέξτε</option>
                    <option value="1" <?php echo isset($driverAssessment['tachograph_compliance']) && $driverAssessment['tachograph_compliance'] == 1 ? 'selected' : ''; ?>>Σπάνια τους τηρώ</option>
                    <option value="2" <?php echo isset($driverAssessment['tachograph_compliance']) && $driverAssessment['tachograph_compliance'] == 2 ? 'selected' : ''; ?>>Μερικές φορές τους παραβιάζω</option>
                    <option value="3" <?php echo isset($driverAssessment['tachograph_compliance']) && $driverAssessment['tachograph_compliance'] == 3 ? 'selected' : ''; ?>>Συνήθως τους τηρώ, με εξαιρέσεις</option>
                    <option value="4" <?php echo isset($driverAssessment['tachograph_compliance']) && $driverAssessment['tachograph_compliance'] == 4 ? 'selected' : ''; ?>>Σχεδόν πάντα τους τηρώ</option>
                    <option value="5" <?php echo isset($driverAssessment['tachograph_compliance']) && $driverAssessment['tachograph_compliance'] == 5 ? 'selected' : ''; ?>>Πάντα τους τηρώ αυστηρά</option>
                </select>
            </div>

            <div class="question-item">
                <label for="safety_check">Πόσο συχνά κάνετε έλεγχο ασφαλείας του οχήματος πριν την οδήγηση;</label>
                <select id="safety_check" name="assessment[safety_check]" required>
                    <option value="">Επιλέξτε</option>
                    <option value="1" <?php echo isset($driverAssessment['safety_check']) && $driverAssessment['safety_check'] == 1 ? 'selected' : ''; ?>>Ποτέ</option>
                    <option value="2" <?php echo isset($driverAssessment['safety_check']) && $driverAssessment['safety_check'] == 2 ? 'selected' : ''; ?>>Σπάνια (μόνο όταν υποψιάζομαι πρόβλημα)</option>
                    <option value="3" <?php echo isset($driverAssessment['safety_check']) && $driverAssessment['safety_check'] == 3 ? 'selected' : ''; ?>>Μερικές φορές (1-2 φορές την εβδομάδα)</option>
                    <option value="4" <?php echo isset($driverAssessment['safety_check']) && $driverAssessment['safety_check'] == 4 ? 'selected' : ''; ?>>Συχνά (σχεδόν καθημερινά)</option>
                    <option value="5" <?php echo isset($driverAssessment['safety_check']) && $driverAssessment['safety_check'] == 5 ? 'selected' : ''; ?>>Πάντα (πριν από κάθε βάρδια)</option>
                </select>
            </div>

            <div class="question-item">
                <label for="load_securing">Πόσο προσεκτικοί είστε με την ασφάλιση του φορτίου;</label>
                <select id="load_securing" name="assessment[load_securing]" required>
                    <option value="">Επιλέξτε</option>
                    <option value="1" <?php echo isset($driverAssessment['load_securing']) && $driverAssessment['load_securing'] == 1 ? 'selected' : ''; ?>>Δεν ασχολούμαι ιδιαίτερα</option>
                    <option value="2" <?php echo isset($driverAssessment['load_securing']) && $driverAssessment['load_securing'] == 2 ? 'selected' : ''; ?>>Βασική ασφάλιση χωρίς λεπτομέρειες</option>
                    <option value="3" <?php echo isset($driverAssessment['load_securing']) && $driverAssessment['load_securing'] == 3 ? 'selected' : ''; ?>>Τυπική ασφάλιση σύμφωνα με τις οδηγίες</option>
                    <option value="4" <?php echo isset($driverAssessment['load_securing']) && $driverAssessment['load_securing'] == 4 ? 'selected' : ''; ?>>Προσεκτική ασφάλιση και έλεγχοι κατά τη διαδρομή</option>
                    <option value="5" <?php echo isset($driverAssessment['load_securing']) && $driverAssessment['load_securing'] == 5 ? 'selected' : ''; ?>>Σχολαστική ασφάλιση και συχνοί έλεγχοι</option>
                </select>
            </div>
        </div>
    </section>

    <!-- Επαγγελματισμός -->
    <section class="assessment-section">
        <h2>Επαγγελματισμός</h2>

        <div class="question-group">
<?php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver_profile.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver-assessment.css">

<main>
    <div class="container">
        <h1>Αυτοαξιολόγηση Οδηγού</h1>
        <p class="lead">Συμπληρώστε την παρακάτω φόρμα για να αξιολογήσετε τις οδηγικές σας ικανότητες και δεξιότητες.</p>

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

        <form action="<?php echo BASE_URL; ?>drivers/save-assessment" method="post" class="assessment-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

            <!-- Οδηγικές Ικανότητες -->
            <div class="assessment-section">
                <h2>Οδηγικές Ικανότητες</h2>

                <!-- Εμπειρία Οδήγησης -->
                <div class="assessment-item">
                    <label>Εμπειρία Οδήγησης</label>
                    <div class="rating">
                        <input type="radio" id="driving_experience_1" name="driving_experience" value="1" <?php echo (isset($assessment['driving_experience']) && $assessment['driving_experience'] == 1) ? 'checked' : ''; ?>>
                        <label for="driving_experience_1">1</label>
                        <input type="radio" id="driving_experience_2" name="driving_experience" value="2" <?php echo (isset($assessment['driving_experience']) && $assessment['driving_experience'] == 2) ? 'checked' : ''; ?>>
                        <label for="driving_experience_2">2</label>
                        <input type="radio" id="driving_experience_3" name="driving_experience" value="3" <?php echo (isset($assessment['driving_experience']) && $assessment['driving_experience'] == 3) ? 'checked' : ''; ?>>
                        <label for="driving_experience_3">3</label>
                        <input type="radio" id="driving_experience_4" name="driving_experience" value="4" <?php echo (isset($assessment['driving_experience']) && $assessment['driving_experience'] == 4) ? 'checked' : ''; ?>>
                        <label for="driving_experience_4">4</label>
                        <input type="radio" id="driving_experience_5" name="driving_experience" value="5" <?php echo (isset($assessment['driving_experience']) && $assessment['driving_experience'] == 5) ? 'checked' : ''; ?>>
                        <label for="driving_experience_5">5</label>
                    </div>
                    <div class="rating-description">
                        <span>Περιορισμένη</span>
                        <span>Εξαιρετική</span>
                    </div>
                </div>

                <!-- Ετήσια Χιλιόμετρα -->
                <div class="assessment-item">
                    <label>Ετήσια Χιλιόμετρα</label>
                    <div class="rating">
                        <input type="radio" id="annual_kilometers_1" name="annual_kilometers" value="1" <?php echo (isset($assessment['annual_kilometers']) && $assessment['annual_kilometers'] == 1) ? 'checked' : ''; ?>>
                        <label for="annual_kilometers_1">1</label>
                        <input type="radio" id="annual_kilometers_2" name="annual_kilometers" value="2" <?php echo (isset($assessment['annual_kilometers']) && $assessment['annual_kilometers'] == 2) ? 'checked' : ''; ?>>
                        <label for="annual_kilometers_2">2</label>
                        <input type="radio" id="annual_kilometers_3" name="annual_kilometers" value="3" <?php echo (isset($assessment['annual_kilometers']) && $assessment['annual_kilometers'] == 3) ? 'checked' : ''; ?>>
                        <label for="annual_kilometers_3">3</label>
                        <input type="radio" id="annual_kilometers_4" name="annual_kilometers" value="4" <?php echo (isset($assessment['annual_kilometers']) && $assessment['annual_kilometers'] == 4) ? 'checked' : ''; ?>>
                        <label for="annual_kilometers_4">4</label>
                        <input type="radio" id="annual_kilometers_5" name="annual_kilometers" value="5" <?php echo (isset($assessment['annual_kilometers']) && $assessment['annual_kilometers'] == 5) ? 'checked' : ''; ?>>
                        <label for="annual_kilometers_5">5</label>
                    </div>
                    <div class="rating-description">
                        <span>&lt;10.000 χλμ</span>
                        <span>&gt;100.000 χλμ</span>
                    </div>
                </div>

                <!-- Συνθήκες Οδήγησης -->
                <div class="assessment-item">
                    <label>Ποικιλία Συνθηκών Οδήγησης</label>
                    <div class="rating">
                        <input type="radio" id="driving_conditions_1" name="driving_conditions" value="1" <?php echo (isset($assessment['driving_conditions']) && $assessment['driving_conditions'] == 1) ? 'checked' : ''; ?>>
                        <label for="driving_conditions_1">1</label>
                        <input type="radio" id="driving_conditions_2" name="driving_conditions" value="2" <?php echo (isset($assessment['driving_conditions']) && $assessment['driving_conditions'] == 2) ? 'checked' : ''; ?>>
                        <label for="driving_conditions_2">2</label>
                        <input type="radio" id="driving_conditions_3" name="driving_conditions" value="3" <?php echo (isset($assessment['driving_conditions']) && $assessment['driving_conditions'] == 3) ? 'checked' : ''; ?>>
                        <label for="driving_conditions_3">3</label>
                        <input type="radio" id="driving_conditions_4" name="driving_conditions" value="4" <?php echo (isset($assessment['driving_conditions']) && $assessment['driving_conditions'] == 4) ? 'checked' : ''; ?>>
                        <label for="driving_conditions_4">4</label>
                        <input type="radio" id="driving_conditions_5" name="driving_conditions" value="5" <?php echo (isset($assessment['driving_conditions']) && $assessment['driving_conditions'] == 5) ? 'checked' : ''; ?>>
                        <label for="driving_conditions_5">5</label>
                    </div>
                    <div class="rating-description">
                        <span>Περιορισμένες</span>
                        <span>Ποικίλες</span>
                    </div>
                </div>

                <!-- Οικολογική Οδήγηση -->
                <div class="assessment-item">
                    <label>Οικολογική Οδήγηση</label>
                    <div class="rating">
                        <input type="radio" id="eco_driving_rating_1" name="eco_driving_rating" value="1" <?php echo (isset($assessment['eco_driving_rating']) && $assessment['eco_driving_rating'] == 1) ? 'checked' : ''; ?>>
                        <label for="eco_driving_rating_1">1</label>
                        <input type="radio" id="eco_driving_rating_2" name="eco_driving_rating" value="2" <?php echo (isset($assessment['eco_driving_rating']) && $assessment['eco_driving_rating'] == 2) ? 'checked' : ''; ?>>
                        <label for="eco_driving_rating_2">2</label>
                        <input type="radio" id="eco_driving_rating_3" name="eco_driving_rating" value="3" <?php echo (isset($assessment['eco_driving_rating']) && $assessment['eco_driving_rating'] == 3) ? 'checked' : ''; ?>>
                        <label for="eco_driving_rating_3">3</label>
                        <input type="radio" id="eco_driving_rating_4" name="eco_driving_rating" value="4" <?php echo (isset($assessment['eco_driving_rating']) && $assessment['eco_driving_rating'] == 4) ? 'checked' : ''; ?>>
                        <label for="eco_driving_rating_4">4</label>
                        <input type="radio" id="eco_driving_rating_5" name="eco_driving_rating" value="5" <?php echo (isset($assessment['eco_driving_rating']) && $assessment['eco_driving_rating'] == 5) ? 'checked' : ''; ?>>
                        <label for="eco_driving_rating_5">5</label>
                    </div>
                    <div class="rating-description">
                        <span>Βασική</span>
                        <span>Άριστη</span>
                    </div>
                </div>

                <!-- Νυχτερινή Οδήγηση -->
                <div class="assessment-item">
                    <label>Νυχτερινή Οδήγηση</label>
                    <div class="rating">
                        <input type="radio" id="night_driving_1" name="night_driving" value="1" <?php echo (isset($assessment['night_driving']) && $assessment['night_driving'] == 1) ? 'checked' : ''; ?>>
                        <label for="night_driving_1">1</label>
                        <input type="radio" id="night_driving_2" name="night_driving" value="2" <?php echo (isset($assessment['night_driving']) && $assessment['night_driving'] == 2) ? 'checked' : ''; ?>>
                        <label for="night_driving_2">2</label>
                        <input type="radio" id="night_driving_3" name="night_driving" value="3" <?php echo (isset($assessment['night_driving']) && $assessment['night_driving'] == 3) ? 'checked' : ''; ?>>
                        <label for="night_driving_3">3</label>
                        <input type="radio" id="night_driving_4" name="night_driving" value="4" <?php echo (isset($assessment['night_driving']) && $assessment['night_driving'] == 4) ? 'checked' : ''; ?>>
                        <label for="night_driving_4">4</label>
                        <input type="radio" id="night_driving_5" name="night_driving" value="5" <?php echo (isset($assessment['night_driving']) && $assessment['night_driving'] == 5) ? 'checked' : ''; ?>>
                        <label for="night_driving_5">5</label>
                    </div>
                    <div class="rating-description">
                        <span>Περιορισμένη</span>
                        <span>Εκτεταμένη</span>
                    </div>
                </div>
            </div>

            <!-- Ασφάλεια & Συμμόρφωση -->
            <div class="assessment-section">
                <h2>Ασφάλεια & Συμμόρφωση</h2>

                <!-- Ατυχήματα -->
                <div class="assessment-item">
                    <label>Ιστορικό Ατυχημάτων</label>
                    <div class="rating">
                        <input type="radio" id="accidents_1" name="accidents" value="1" <?php echo (isset($assessment['accidents']) && $assessment['accidents'] == 1) ? 'checked' : ''; ?>>
                        <label for="accidents_1">1</label>
                        <input type="radio" id="accidents_2" name="accidents" value="2" <?php echo (isset($assessment['accidents']) && $assessment['accidents'] == 2) ? 'checked' : ''; ?>>
                        <label for="accidents_2">2</label>
                        <input type="radio" id="accidents_3" name="accidents" value="3" <?php echo (isset($assessment['accidents']) && $assessment['accidents'] == 3) ? 'checked' : ''; ?>>
                        <label for="accidents_3">3</label>
                        <input type="radio" id="accidents_4" name="accidents" value="4" <?php echo (isset($assessment['accidents']) && $assessment['accidents'] == 4) ? 'checked' : ''; ?>>
                        <label for="accidents_4">4</label>
                        <input type="radio" id="accidents_5" name="accidents" value="5" <?php echo (isset($assessment['accidents']) && $assessment['accidents'] == 5) ? 'checked' : ''; ?>>
                        <label for="accidents_5">5</label>
                    </div>
                    <div class="rating-description">
                        <span>Συχνά</span>
                        <span>Κανένα</span>
                    </div>
                </div>

                <!-- Παραβάσεις ΚΟΚ -->
                <div class="assessment-item">
                    <label>Παραβάσεις ΚΟΚ</label>
                    <div class="rating">
                        <input type="radio" id="traffic_violations_1" name="traffic_violations" value="1" <?php echo (isset($assessment['traffic_violations']) && $assessment['traffic_violations'] == 1) ? 'checked' : ''; ?>>
                        <label for="traffic_violations_1">1</label>
                        <input type="radio" id="traffic_violations_2" name="traffic_violations" value="2" <?php echo (isset($assessment['traffic_violations']) && $assessment['traffic_violations'] == 2) ? 'checked' : ''; ?>>
                        <label for="traffic_violations_2">2</label>
                        <input type="radio" id="traffic_violations_3" name="traffic_violations" value="3" <?php echo (isset($assessment['traffic_violations']) && $assessment['traffic_violations'] == 3) ? 'checked' : ''; ?>>
                        <label for="traffic_violations_3">3</label>
                        <input type="radio" id="traffic_violations_4" name="traffic_violations" value="4" <?php echo (isset($assessment['traffic_violations']) && $assessment['traffic_violations'] == 4) ? 'checked' : ''; ?>>
                        <label for="traffic_violations_4">4</label>
                        <input type="radio" id="traffic_violations_5" name="traffic_violations" value="5" <?php echo (isset($assessment['traffic_violations']) && $assessment['traffic_violations'] == 5) ? 'checked' : ''; ?>>
                        <label for="traffic_violations_5">5</label>
                    </div>
                    <div class="rating-description">
                        <span>Συχνές</span>
                        <span>Καμία</span>
                    </div>
                </div>

                <!-- Συμμόρφωση με ταχογράφο -->
                <div class="assessment-item">
                    <label>Συμμόρφωση με ταχογράφο</label>
                    <div class="rating">
                        <input type="radio" id="tachograph_compliance_1" name="tachograph_compliance" value="1" <?php echo (isset($assessment['tachograph_compliance']) && $assessment['tachograph_compliance'] == 1) ? 'checked' : ''; ?>>
                        <label for="tachograph_compliance_1">1</label>
                        <input type="radio" id="tachograph_compliance_2" name="tachograph_compliance" value="2" <?php echo (isset($assessment['tachograph_compliance']) && $assessment['tachograph_compliance'] == 2) ? 'checked' : ''; ?>>
                        <label for="tachograph_compliance_2">2</label>
                        <input type="radio" id="tachograph_compliance_3" name="tachograph_compliance" value="3" <?php echo (isset($assessment['tachograph_compliance']) && $assessment['tachograph_compliance'] == 3) ? 'checked' : ''; ?>>
                        <label for="tachograph_compliance_3">3</label>
                        <input type="radio" id="tachograph_compliance_4" name="tachograph_compliance" value="4" <?php echo (isset($assessment['tachograph_compliance']) && $assessment['tachograph_compliance'] == 4) ? 'checked' : ''; ?>>
                        <label for="tachograph_compliance_4">4</label>
                        <input type="radio" id="tachograph_compliance_5" name="tachograph_compliance" value="5" <?php echo (isset($assessment['tachograph_compliance']) && $assessment['tachograph_compliance'] == 5) ? 'checked' : ''; ?>>
                        <label for="tachograph_compliance_5">5</label>
                    </div>
                    <div class="rating-description">
                        <span>Περιστασιακή</span>
                        <span>Πλήρης</span>
                    </div>
                </div>

                <!-- Έλεγχος ασφάλειας οχήματος -->
                <div class="assessment-item">
                    <label>Έλεγχος ασφάλειας οχήματος</label>
                    <div class="rating">
                        <input type="radio" id="safety_check_1" name="safety_check" value="1" <?php echo (isset($assessment['safety_check']) && $assessment['safety_check'] == 1) ? 'checked' : ''; ?>>
                        <label for="safety_check_1">1</label>
                        <input type="radio" id="safety_check_2" name="safety_check" value="2" <?php echo (isset($assessment['safety_check']) && $assessment['safety_check'] == 2) ? 'checked' : ''; ?>>
                        <label for="safety_check_2">2</label>
                        <input type="radio" id="safety_check_3" name="safety_check" value="3" <?php echo (isset($assessment['safety_check']) && $assessment['safety_check'] == 3) ? 'checked' : ''; ?>>
                        <label for="safety_check_3">3</label>
                        <input type="radio" id="safety_check_4" name="safety_check" value="4" <?php echo (isset($assessment['safety_check']) && $assessment['safety_check'] == 4) ? 'checked' : ''; ?>>
                        <label for="safety_check_4">4</label>
                        <input type="radio" id="safety_check_5" name="safety_check" value="5" <?php echo (isset($assessment['safety_check']) && $assessment['safety_check'] == 5) ? 'checked' : ''; ?>>
                        <label for="safety_check_5">5</label>
                    </div>
                    <div class="rating-description">
                        <span>Σπάνια</span>
                        <span>Καθημερινά</span>
                    </div>
                </div>

                <!-- Ασφάλιση φορτίου -->
                <div class="assessment-item">
                    <label>Ασφάλιση φορτίου</label>
                    <div class="rating">
                        <input type="radio" id="load_securing_1" name="load_securing" value="1" <?php echo (isset($assessment['load_securing']) && $assessment['load_securing'] == 1) ? 'checked' : ''; ?>>
                        <label for="load_securing_1">1</label>
                        <input type="radio" id="load_securing_2" name="load_securing" value="2" <?php echo (isset($assessment['load_securing']) && $assessment['load_securing'] == 2) ? 'checked' : ''; ?>>
                        <label for="load_securing_2">2</label>
                        <input type="radio" id="load_securing_3" name="load_securing" value="3" <?php echo (isset($assessment['load_securing']) && $assessment['load_securing'] == 3) ? 'checked' : ''; ?>>
                        <label for="load_securing_3">3</label>
                        <input type="radio" id="load_securing_4" name="load_securing" value="4" <?php echo (isset($assessment['load_securing']) && $assessment['load_securing'] == 4) ? 'checked' : ''; ?>>
                        <label for="load_securing_4">4</label>
                        <input type="radio" id="load_securing_5" name="load_securing" value="5" <?php echo (isset($assessment['load_securing']) && $assessment['load_securing'] == 5) ? 'checked' : ''; ?>>
                        <label for="load_securing_5">5</label>
                    </div>
                    <div class="rating-description">
                        <span>Βασική</span>
                        <span>Άριστη</span>
                    </div>
                </div>
            </div>

            <!-- Επαγγελματισμός -->
            <div class="assessment-section">
                <h2>Επαγγελματισμός</h2>

                <!-- Συνέπεια -->
                <div class="assessment-item">
                    <label>Συνέπεια</label>
                    <div class="rating">
                        <input type="radio" id="punctuality_1" name="punctuality" value="1" <?php echo (isset($assessment['punctuality']) && $assessment['punctuality'] == 1) ? 'checked' : ''; ?>>
                        <label for="punctuality_1">1</label>
                        <input type="radio" id="punctuality_2" name="punctuality" value="2" <?php echo (isset($assessment['punctuality']) && $assessment['punctuality'] == 2) ? 'checked' : ''; ?>>
                        <label for="punctuality_2">2</label>
                        <input type="radio" id="punctuality_3" name="punctuality" value="3" <?php echo (isset($assessment['punctuality']) && $assessment['punctuality'] == 3) ? 'checked' : ''; ?>>
                        <label for="punctuality_3">3</label>
                        <input type="radio" id="punctuality_4" name="punctuality" value="4" <?php echo (isset($assessment['punctuality']) && $assessment['punctuality'] == 4) ? 'checked' : ''; ?>>
                        <label for="punctuality_4">4</label>
                        <input type="radio" id="punctuality_5" name="punctuality" value="5" <?php echo (isset($assessment['punctuality']) && $assessment['punctuality'] == 5) ? 'checked' : ''; ?>>
                        <label for="punctuality_5">5</label>
                    </div>
                    <div class="rating-description">
                        <span>Περιστασιακή</span>
                        <span>Πάντα</span>
                    </div>
                </div>

                <!-- Επικοινωνία με πελάτες -->
                <div class="assessment-item">
                    <label>Επικοινωνία με πελάτες</label>
                    <div class="rating">
                        <input type="radio" id="customer_interaction_1" name="customer_interaction" value="1" <?php echo (isset($assessment['customer_interaction']) && $assessment['customer_interaction'] == 1) ? 'checked' : ''; ?>>
                        <label for="customer_interaction_1">1</label>
                        <input type="radio" id="customer_interaction_2" name="customer_interaction" value="2" <?php echo (isset($assessment['customer_interaction']) && $assessment['customer_interaction'] == 2) ? 'checked' : ''; ?>>
                        <label for="customer_interaction_2">2</label>
                        <input type="radio" id="customer_interaction_3" name="customer_interaction" value="3" <?php echo (isset($assessment['customer_interaction']) && $assessment['customer_interaction'] == 3) ? 'checked' : ''; ?>>
                        <label for="customer_interaction_3">3</label>
                        <input type="radio" id="customer_interaction_4" name="customer_interaction" value="4" <?php echo (isset($assessment['customer_interaction']) && $assessment['customer_interaction'] == 4) ? 'checked' : ''; ?>>
                        <label for="customer_interaction_4">4</label>
                        <input type="radio" id="customer_interaction_5" name="customer_interaction" value="5" <?php echo (isset($assessment['customer_interaction']) && $assessment['customer_interaction'] == 5) ? 'checked' : ''; ?>>
                        <label for="customer_interaction_5">5</label>
                    </div>
                    <div class="rating-description">
                        <span>Βασική</span>
                        <span>Εξαιρετική</span>
                    </div>
                </div>

                <!-- Εμφάνιση -->
                <div class="assessment-item">
                    <label>Επαγγελματική εμφάνιση</label>
                    <div class="rating">
                        <input type="radio" id="appearance_1" name="appearance" value="1" <?php echo (isset($assessment['appearance']) && $assessment['appearance'] == 1) ? 'checked' : ''; ?>>
                        <label for="appearance_1">1</label>
                        <input type="radio" id="appearance_2" name="appearance" value="2" <?php echo (isset($assessment['appearance']) && $assessment['appearance'] == 2) ? 'checked' : ''; ?>>
                        <label for="appearance_2">2</label>
                        <input type="radio" id="appearance_3" name="appearance" value="3" <?php echo (isset($assessment['appearance']) && $assessment['appearance'] == 3) ? 'checked' : ''; ?>>
                        <label for="appearance_3">3</label>
                        <input type="radio" id="appearance_4" name="appearance" value="4" <?php echo (isset($assessment['appearance']) && $assessment['appearance'] == 4) ? 'checked' : ''; ?>>
                        <label for="appearance_4">4</label>
                        <input type="radio" id="appearance_5" name="appearance" value="5" <?php echo (isset($assessment['appearance']) && $assessment['appearance'] == 5) ? 'checked' : ''; ?>>
                        <label for="appearance_5">5</label>
                    </div>
                    <div class="rating-description">
                        <span>Ανεπαρκής</span>
                        <span>Άψογη</span>
                    </div>
                </div>

                <!-- Τεκμηρίωση -->
                <div class="assessment-item">
                    <label>Διαχείριση εγγράφων</label>
                    <div class="rating">
                        <input type="radio" id="documentation_1" name="documentation" value="1" <?php echo (isset($assessment['documentation']) && $assessment['documentation'] == 1) ? 'checked' : ''; ?>>
                        <label for="documentation_1">1</label>
                        <input type="radio" id="documentation_2" name="documentation" value="2" <?php echo (isset($assessment['documentation']) && $assessment['documentation'] == 2) ? 'checked' : ''; ?>>
                        <label for="documentation_2">2</label>
                        <input type="radio" id="documentation_3" name="documentation" value="3" <?php echo (isset($assessment['documentation']) && $assessment['documentation'] == 3) ? 'checked' : ''; ?>>
                        <label for="documentation_3">3</label>
                        <input type="radio" id="documentation_4" name="documentation" value="4" <?php echo (isset($assessment['documentation']) && $assessment['documentation'] == 4) ? 'checked' : ''; ?>>
                        <label for="documentation_4">4</label>
                        <input type="radio" id="documentation_5" name="documentation" value="5" <?php echo (isset($assessment['documentation']) && $assessment['documentation'] == 5) ? 'checked' : ''; ?>>
                        <label for="documentation_5">5</label>
                    </div>
                    <div class="rating-description">
                        <span>Ελλιπής</span>
                        <span>Άριστη</span>
                    </div>
                </div>
            </div>

            <!-- Τεχνικές Γνώσεις -->
            <div class="assessment-section">
                <h2>Τεχνικές Γνώσεις</h2>

                <!-- Συντήρηση οχήματος -->
                <div class="assessment-item">
                    <label>Συντήρηση οχήματος</label>
                    <div class="rating">
                        <input type="radio" id="vehicle_maintenance_1" name="vehicle_maintenance" value="1" <?php echo (isset($assessment['vehicle_maintenance']) && $assessment['vehicle_maintenance'] == 1) ? 'checked' : ''; ?>>
                        <label for="vehicle_maintenance_1">1</label>
                        <input type="radio" id="vehicle_maintenance_2" name="vehicle_maintenance" value="2" <?php echo (isset($assessment['vehicle_maintenance']) && $assessment['vehicle_maintenance'] == 2) ? 'checked' : ''; ?>>
                        <label for="vehicle_maintenance_2">2</label>
                        <input type="radio" id="vehicle_maintenance_3" name="vehicle_maintenance" value="3" <?php echo (isset($assessment['vehicle_maintenance']) && $assessment['vehicle_maintenance'] == 3) ? 'checked' : ''; ?>>
                        <label for="vehicle_maintenance_3">3</label>
                        <input type="radio" id="vehicle_maintenance_4" name="vehicle_maintenance" value="4" <?php echo (isset($assessment['vehicle_maintenance']) && $assessment['vehicle_maintenance'] == 4) ? 'checked' : ''; ?>>
                        <label for="vehicle_maintenance_4">4</label>
                        <input type="radio" id="vehicle_maintenance_5" name="vehicle_maintenance" value="5" <?php echo (isset($assessment['vehicle_maintenance']) && $assessment['vehicle_maintenance'] == 5) ? 'checked' : ''; ?>>
                        <label for="vehicle_maintenance_5">5</label>
                    </div>
                    <div class="rating-description">
                        <span>Βασική</span>
                        <span>Εξαιρετική</span>
                    </div>
                </div>

                <!-- Επίλυση προβλημάτων -->
                <div class="assessment-item">
                    <label>Επίλυση τεχνικών προβλημάτων</label>
                    <div class="rating">
                        <input type="radio" id="troubleshooting_1" name="troubleshooting" value="1" <?php echo (isset($assessment['troubleshooting']) && $assessment['troubleshooting'] == 1) ? 'checked' : ''; ?>>
                        <label for="troubleshooting_1">1</label>
                        <input type="radio" id="troubleshooting_2" name="troubleshooting" value="2" <?php echo (isset($assessment['troubleshooting']) && $assessment['troubleshooting'] == 2) ? 'checked' : ''; ?>>
                        <label for="troubleshooting_2">2</label>
                        <input type="radio" id="troubleshooting_3" name="troubleshooting" value="3" <?php echo (isset($assessment['troubleshooting']) && $assessment['troubleshooting'] == 3) ? 'checked' : ''; ?>>
                        <label for="troubleshooting_3">3</label>
                        <input type="radio" id="troubleshooting_4" name="troubleshooting" value="4" <?php echo (isset($assessment['troubleshooting']) && $assessment['troubleshooting'] == 4) ? 'checked' : ''; ?>>
                        <label for="troubleshooting_4">4</label>
                        <input type="radio" id="troubleshooting_5" name="troubleshooting" value="5" <?php echo (isset($assessment['troubleshooting']) && $assessment['troubleshooting'] == 5) ? 'checked' : ''; ?>>
                        <label for="troubleshooting_5">5</label>
                    </div>
                    <div class="rating-description">
                        <span>Περιορισμένη</span>
                        <span>Προηγμένη</span>
                    </div>
                </div>

                <!-- Ικανότητες πλοήγησης -->
                <div class="assessment-item">
                    <label>Ικανότητες πλοήγησης</label>
                    <div class="rating">
                        <input type="radio" id="navigation_skills_1" name="navigation_skills" value="1" <?php echo (isset($assessment['navigation_skills']) && $assessment['navigation_skills'] == 1) ? 'checked' : ''; ?>>
                        <label for="navigation_skills_1">1</label>
                        <input type="radio" id="navigation_skills_2" name="navigation_skills" value="2" <?php echo (isset($assessment['navigation_skills']) && $assessment['navigation_skills'] == 2) ? 'checked' : ''; ?>>
                        <label for="navigation_skills_2">2</label>
                        <input type="radio" id="navigation_skills_3" name="navigation_skills" value="3" <?php echo (isset($assessment['navigation_skills']) && $assessment['navigation_skills'] == 3) ? 'checked' : ''; ?>>
                        <label for="navigation_skills_3">3</label>
                        <input type="radio" id="navigation_skills_4" name="navigation_skills" value="4" <?php echo (isset($assessment['navigation_skills']) && $assessment['navigation_skills'] == 4) ? 'checked' : ''; ?>>
                        <label for="navigation_skills_4">4</label>
                        <input type="radio" id="navigation_skills_5" name="navigation_skills" value="5" <?php echo (isset($assessment['navigation_skills']) && $assessment['navigation_skills'] == 5) ? 'checked' : ''; ?>>
                        <label for="navigation_skills_5">5</label>
                    </div>
                    <div class="rating-description">
                        <span>Βασικές</span>
                        <span>Εξαιρετικές</span>
                    </div>
                </div>

                <!-- Τεχνικές γνώσεις -->
                <div class="assessment-item">
                    <label>Τεχνικές γνώσεις οχημάτων</label>
                    <div class="rating">
                        <input type="radio" id="technical_knowledge_1" name="technical_knowledge" value="1" <?php echo (isset($assessment['technical_knowledge']) && $assessment['technical_knowledge'] == 1) ? 'checked' : ''; ?>>
                        <label for="technical_knowledge_1">1</label>
                        <input type="radio" id="technical_knowledge_2" name="technical_knowledge" value="2" <?php echo (isset($assessment['technical_knowledge']) && $assessment['technical_knowledge'] == 2) ? 'checked' : ''; ?>>
                        <label for="technical_knowledge_2">2</label>
                        <input type="radio" id="technical_knowledge_3" name="technical_knowledge" value="3" <?php echo (isset($assessment['technical_knowledge']) && $assessment['technical_knowledge'] == 3) ? 'checked' : ''; ?>>
                        <label for="technical_knowledge_3">3</label>
                        <input type="radio" id="technical_knowledge_4" name="technical_knowledge" value="4" <?php echo (isset($assessment['technical_knowledge']) && $assessment['technical_knowledge'] == 4) ? 'checked' : ''; ?>>
                        <label for="technical_knowledge_4">4</label>
                        <input type="radio" id="technical_knowledge_5" name="technical_knowledge" value="5" <?php echo (isset($assessment['technical_knowledge']) && $assessment['technical_knowledge'] == 5) ? 'checked' : ''; ?>>
                        <label for="technical_knowledge_5">5</label>
                    </div>
                    <div class="rating-description">
                        <span>Βασικές</span>
                        <span>Προηγμένες</span>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Αποθήκευση Αυτοαξιολόγησης</button>
                <a href="<?php echo BASE_URL; ?>drivers/driver-rating" class="btn-secondary">Ακύρωση</a>
            </div>
        </form>
    </div>
</main>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php';
?>
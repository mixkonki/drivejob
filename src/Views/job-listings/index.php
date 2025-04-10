<?php
// Αυτόματη φόρτωση μέσω Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// Συμπερίληψη του config.php για να οριστούν οι σταθερές
require_once __DIR__ . '/../../config/config.php';

// Συμπερίληψη του database.php για σύνδεση με τη βάση δεδομένων
require_once ROOT_DIR . '/config/database.php';

// Ξεκίνημα ή συνέχιση session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Δημιουργία του controller και κλήση της μεθόδου index
$controller = new \Drivejob\Controllers\JobListingController($pdo);
$controller->index();

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php'; 

// Ορισμός των επιπλέον CSS αρχείων
$css_files = ['css/job-listings.css'];

// Ανάκτηση σφαλμάτων και παλιών τιμών από το session
$errors = $_SESSION['errors'] ?? [];
$oldInput = $_SESSION['old_input'] ?? [];

// Καθαρισμός των session μεταβλητών μετά την ανάκτησή τους
unset($_SESSION['errors'], $_SESSION['old_input']);

// Βοηθητική συνάρτηση για την εμφάνιση των παλιών τιμών
function old($field, $default = '') {
    global $oldInput;
    return $oldInput[$field] ?? $default;
}

// Βοηθητική συνάρτηση για την εμφάνιση των σφαλμάτων
function hasError($field) {
    global $errors;
    return isset($errors[$field]);
}

function getError($field) {
    global $errors;
    return $errors[$field] ?? '';
}
?>

<main>
    <div class="container">
        <h1>Δημιουργία Νέας Αγγελίας</h1>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message">
                <?php echo $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo BASE_URL; ?>job-listings/store" method="POST" class="job-listing-form">
            <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
            
            <!-- Βασικές πληροφορίες -->
            <section class="form-section">
                <h2>Βασικές Πληροφορίες</h2>
                
                <div class="form-group <?php echo hasError('title') ? 'has-error' : ''; ?>">
                    <label for="title">Τίτλος Αγγελίας</label>
                    <input type="text" id="title" name="title" value="<?php echo old('title'); ?>" required>
                    <?php if (hasError('title')): ?>
                        <div class="error-message"><?php echo getError('title'); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="listing_type">Τύπος Αγγελίας</label>
                    <select id="listing_type" name="listing_type" required>
                        <?php if ($_SESSION['role'] === 'company'): ?>
                            <option value="job_offer" <?php echo old('listing_type') === 'job_offer' ? 'selected' : ''; ?>>Προσφορά Εργασίας</option>
                        <?php else: ?>
                            <option value="job_search" <?php echo old('listing_type') === 'job_search' ? 'selected' : ''; ?>>Αναζήτηση Εργασίας</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="job_type">Τύπος Απασχόλησης</label>
                    <select id="job_type" name="job_type" required>
                        <option value="full_time" <?php echo old('job_type') === 'full_time' ? 'selected' : ''; ?>>Πλήρης Απασχόληση</option>
                        <option value="part_time" <?php echo old('job_type') === 'part_time' ? 'selected' : ''; ?>>Μερική Απασχόληση</option>
                        <option value="contract" <?php echo old('job_type') === 'contract' ? 'selected' : ''; ?>>Σύμβαση Έργου</option>
                        <option value="temporary" <?php echo old('job_type') === 'temporary' ? 'selected' : ''; ?>>Προσωρινή Απασχόληση</option>
                    </select>
                </div>
                
                <div class="form-group <?php echo hasError('description') ? 'has-error' : ''; ?>">
                    <label for="description">Περιγραφή</label>
                    <textarea id="description" name="description" rows="6" required><?php echo old('description'); ?></textarea>
                    <?php if (hasError('description')): ?>
                        <div class="error-message"><?php echo getError('description'); ?></div>
                    <?php endif; ?>
                </div>
            </section>
            
            <!-- Πληροφορίες αμοιβής -->
            <section class="form-section">
                <h2>Πληροφορίες Αμοιβής</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="salary_min">Ελάχιστη Αμοιβή (€)</label>
                        <input type="number" id="salary_min" name="salary_min" min="0" step="100" value="<?php echo old('salary_min'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="salary_max">Μέγιστη Αμοιβή (€)</label>
                        <input type="number" id="salary_max" name="salary_max" min="0" step="100" value="<?php echo old('salary_max'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="salary_type">Τύπος Αμοιβής</label>
                        <select id="salary_type" name="salary_type">
                            <option value="">Επιλέξτε</option>
                            <option value="hourly" <?php echo old('salary_type') === 'hourly' ? 'selected' : ''; ?>>Ανά ώρα</option>
                            <option value="daily" <?php echo old('salary_type') === 'daily' ? 'selected' : ''; ?>>Ανά ημέρα</option>
                            <option value="monthly" <?php echo old('salary_type') === 'monthly' ? 'selected' : ''; ?>>Ανά μήνα</option>
                            <option value="yearly" <?php echo old('salary_type') === 'yearly' ? 'selected' : ''; ?>>Ανά έτος</option>
                        </select>
                    </div>
                </div>
            </section>
            
            <!-- Τοποθεσία -->
            <section class="form-section">
                <h2>Τοποθεσία</h2>
                
                <div class="form-group <?php echo hasError('location') ? 'has-error' : ''; ?>">
                    <label for="location">Διεύθυνση/Περιοχή</label>
                    <input type="text" id="location" name="location" value="<?php echo old('location'); ?>" required>
                    <?php if (hasError('location')): ?>
                        <div class="error-message"><?php echo getError('location'); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="latitude">Γεωγραφικό Πλάτος</label>
                        <input type="text" id="latitude" name="latitude" value="<?php echo old('latitude'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="longitude">Γεωγραφικό Μήκος</label>
                        <input type="text" id="longitude" name="longitude" value="<?php echo old('longitude'); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="radius">Ακτίνα Αναζήτησης (km)</label>
                    <input type="number" id="radius" name="radius" min="0" value="<?php echo old('radius'); ?>">
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="remote_possible" name="remote_possible" value="1" <?php echo old('remote_possible') ? 'checked' : ''; ?>>
                        Δυνατότητα εργασίας από απόσταση
                    </label>
                </div>
            </section>
            
            <!-- Απαιτήσεις -->
            <section class="form-section">
                <h2>Απαιτήσεις</h2>
                
                <div class="form-group">
                    <label for="vehicle_type">Τύπος Οχήματος</label>
                    <select id="vehicle_type" name="vehicle_type" required>
                        <option value="car" <?php echo old('vehicle_type') === 'car' ? 'selected' : ''; ?>>Αυτοκίνητο</option>
                        <option value="van" <?php echo old('vehicle_type') === 'van' ? 'selected' : ''; ?>>Βαν</option>
                        <option value="truck" <?php echo old('vehicle_type') === 'truck' ? 'selected' : ''; ?>>Φορτηγό</option>
                        <option value="bus" <?php echo old('vehicle_type') === 'bus' ? 'selected' : ''; ?>>Λεωφορείο</option>
                        <option value="machinery" <?php echo old('vehicle_type') === 'machinery' ? 'selected' : ''; ?>>Μηχάνημα Έργου</option>
                    </select>
                </div>
                
                <div class="form-group <?php echo hasError('required_license') ? 'has-error' : ''; ?>">
                    <label for="required_license">Απαιτούμενη Άδεια</label>
                    <input type="text" id="required_license" name="required_license" value="<?php echo old('required_license'); ?>" required>
                    <?php if (hasError('required_license')): ?>
                        <div class="error-message"><?php echo getError('required_license'); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="experience_years">Έτη Εμπειρίας</label>
                    <input type="number" id="experience_years" name="experience_years" min="0" value="<?php echo old('experience_years'); ?>">
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="adr_certificate" name="adr_certificate" value="1" <?php echo old('adr_certificate') ? 'checked' : ''; ?>>
                        Απαιτείται Πιστοποιητικό ADR
                    </label>
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="operator_license" name="operator_license" value="1" <?php echo old('operator_license') ? 'checked' : ''; ?>>
                        Απαιτείται Άδεια Χειριστή Μηχανημάτων
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="required_training">Απαιτούμενη Εκπαίδευση</label>
                    <textarea id="required_training" name="required_training" rows="4"><?php echo old('required_training'); ?></textarea>
                </div>
            </section>
            
            <!-- Επιπλέον Πληροφορίες -->
            <section class="form-section">
                <h2>Επιπλέον Πληροφορίες</h2>
                
                <div class="form-group">
                    <label for="benefits">Παροχές</label>
                    <textarea id="benefits" name="benefits" rows="4"><?php echo old('benefits'); ?></textarea>
                </div>
                
                <div class="form-group <?php echo hasError('contact_email') ? 'has-error' : ''; ?>">
                    <label for="contact_email">Email Επικοινωνίας</label>
                    <input type="email" id="contact_email" name="contact_email" value="<?php echo old('contact_email'); ?>">
                    <?php if (hasError('contact_email')): ?>
                        <div class="error-message"><?php echo getError('contact_email'); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group <?php echo hasError('contact_phone') ? 'has-error' : ''; ?>">
                    <label for="contact_phone">Τηλέφωνο Επικοινωνίας</label>
                    <input type="tel" id="contact_phone" name="contact_phone" value="<?php echo old('contact_phone'); ?>">
                    <?php if (hasError('contact_phone')): ?>
                        <div class="error-message"><?php echo getError('contact_phone'); ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="expires_at">Ημερομηνία Λήξης</label>
                    <input type="date" id="expires_at" name="expires_at" value="<?php echo old('expires_at'); ?>">
                </div>
                
                <?php if (!empty($tags)): ?>
                <div class="form-group">
                    <label>Ετικέτες</label>
                    <div class="tags-container">
                        <?php foreach ($tags as $tag): ?>
                            <div class="tag-item">
                                <label>
                                    <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>" <?php echo in_array($tag['id'], old('tags', [])) ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($tag['name']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </section>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Δημιουργία Αγγελίας</button>
                <a href="<?php echo BASE_URL; ?>job-listings" class="btn-secondary">Ακύρωση</a>
            </div>
        </form>
    </div>
</main>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCgZpJWVYyrY0U8U1jBGelEWryur3vIrzc&libraries=places"></script>
<script>
    // Αυτόματη συμπλήρωση διεύθυνσης με Google Places API
    function initGooglePlaces() {
        const locationInput = document.getElementById('location');
        const autocomplete = new google.maps.places.Autocomplete(locationInput);
        
        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            
            if (!place.geometry) {
                return;
            }
            
            // Συμπλήρωση γεωγραφικών συντεταγμένων
            document.getElementById('latitude').value = place.geometry.location.lat();
            document.getElementById('longitude').value = place.geometry.location.lng();
        });
    }
    
    // Φόρτωση της λειτουργίας όταν φορτώσει η σελίδα
    document.addEventListener('DOMContentLoaded', initGooglePlaces);
</script>

<?php 
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php'; 
?>
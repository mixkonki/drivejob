<?php 
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php'; 
?>

<main>
    <div class="container">
        <h1>Δημιουργία Νέας Αγγελίας</h1>
        
        <?php
        use Drivejob\Core\Session;
        
        // Ανάκτηση σφαλμάτων και παλιών τιμών από το session
        $errors = Session::get('errors', []);
        $oldInput = Session::get('old_input', []);
        
        // Καθαρισμός των session μεταβλητών μετά την ανάκτησή τους
        Session::remove('errors');
        Session::remove('old_input');
        
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
                            <option value="job_offer">Προσφορά Εργασίας</option>
                        <?php else: ?>
                            <option value="job_search">Αναζήτηση Εργασίας</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="job_type">Τύπος Απασχόλησης</label>
                    <select id="job_type" name="job_type" required>
                        <option value="full_time">Πλήρης Απασχόληση</option>
                        <option value="part_time">Μερική Απασχόληση</option>
                        <option value="contract">Σύμβαση Έργου</option>
                        <option value="temporary">Προσωρινή Απασχόληση</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description">Περιγραφή</label>
                    <textarea id="description" name="description" rows="6" required></textarea>
                </div>
            </section>
            
            <!-- Πληροφορίες αμοιβής -->
            <section class="form-section">
                <h2>Πληροφορίες Αμοιβής</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="salary_min">Ελάχιστη Αμοιβή (€)</label>
                        <input type="number" id="salary_min" name="salary_min" min="0" step="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="salary_max">Μέγιστη Αμοιβή (€)</label>
                        <input type="number" id="salary_max" name="salary_max" min="0" step="100">
                    </div>
                    
                    <div class="form-group">
                        <label for="salary_type">Τύπος Αμοιβής</label>
                        <select id="salary_type" name="salary_type">
                            <option value="">Επιλέξτε</option>
                            <option value="hourly">Ανά ώρα</option>
                            <option value="daily">Ανά ημέρα</option>
                            <option value="monthly">Ανά μήνα</option>
                            <option value="yearly">Ανά έτος</option>
                        </select>
                    </div>
                </div>
            </section>
            
            <!-- Τοποθεσία -->
            <section class="form-section">
                <h2>Τοποθεσία</h2>
                
                <div class="form-group">
                    <label for="location">Διεύθυνση/Περιοχή</label>
                    <input type="text" id="location" name="location" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="latitude">Γεωγραφικό Πλάτος</label>
                        <input type="text" id="latitude" name="latitude">
                    </div>
                    
                    <div class="form-group">
                        <label for="longitude">Γεωγραφικό Μήκος</label>
                        <input type="text" id="longitude" name="longitude">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="radius">Ακτίνα Αναζήτησης (km)</label>
                    <input type="number" id="radius" name="radius" min="0">
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="remote_possible" name="remote_possible" value="1">
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
                        <option value="car">Αυτοκίνητο</option>
                        <option value="van">Βαν</option>
                        <option value="truck">Φορτηγό</option>
                        <option value="bus">Λεωφορείο</option>
                        <option value="machinery">Μηχάνημα Έργου</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="required_license">Απαιτούμενη Άδεια</label>
                    <input type="text" id="required_license" name="required_license" required>
                </div>
                
                <div class="form-group">
                    <label for="experience_years">Έτη Εμπειρίας</label>
                    <input type="number" id="experience_years" name="experience_years" min="0">
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="adr_certificate" name="adr_certificate" value="1">
                        Απαιτείται Πιστοποιητικό ADR
                    </label>
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="operator_license" name="operator_license" value="1">
                        Απαιτείται Άδεια Χειριστή Μηχανημάτων
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="required_training">Απαιτούμενη Εκπαίδευση</label>
                    <textarea id="required_training" name="required_training" rows="4"></textarea>
                </div>
            </section>
            
            <!-- Επιπλέον Πληροφορίες -->
            <section class="form-section">
                <h2>Επιπλέον Πληροφορίες</h2>
                
                <div class="form-group">
                    <label for="benefits">Παροχές</label>
                    <textarea id="benefits" name="benefits" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="contact_email">Email Επικοινωνίας</label>
                    <input type="email" id="contact_email" name="contact_email">
                </div>
                
                <div class="form-group">
                    <label for="contact_phone">Τηλέφωνο Επικοινωνίας</label>
                    <input type="tel" id="contact_phone" name="contact_phone">
                </div>
                
                <div class="form-group">
                    <label for="expires_at">Ημερομηνία Λήξης</label>
                    <input type="date" id="expires_at" name="expires_at">
                </div>
                <div class="form-group">
    <label>Ετικέτες</label>
    <div class="tags-container">
        <?php if (is_array($tags) && !empty($tags)): ?>
            <?php foreach ($tags as $tag): ?>
                <div class="tag-item">
                    <label>
                        <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>">
                        <?php echo htmlspecialchars($tag['name']); ?>
                    </label>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Δεν υπάρχουν διαθέσιμες ετικέτες.</p>
        <?php endif; ?>
    </div>
</div>

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
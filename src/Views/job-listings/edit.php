<?php 
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php'; 

use Drivejob\Core\Session;

$errors = Session::get('errors', []);
$oldInput = Session::get('old_input', []);
Session::remove('errors');
Session::remove('old_input');

?>

<main>
    <div class="container">
        <h1>Επεξεργασία Αγγελίας</h1>
        
        <form action="<?php echo BASE_URL; ?>job-listings/update/<?php echo $listing['id']; ?>" method="POST" class="job-listing-form">
        <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
            <!-- Βασικές πληροφορίες -->
            <section class="form-section">
                <h2>Βασικές Πληροφορίες</h2>
                
                <div class="form-group">
                    <label for="title">Τίτλος Αγγελίας</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($listing['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="job_type">Τύπος Απασχόλησης</label>
                    <select id="job_type" name="job_type" required>
                        <option value="full_time" <?php echo $listing['job_type'] === 'full_time' ? 'selected' : ''; ?>>Πλήρης Απασχόληση</option>
                        <option value="part_time" <?php echo $listing['job_type'] === 'part_time' ? 'selected' : ''; ?>>Μερική Απασχόληση</option>
                        <option value="contract" <?php echo $listing['job_type'] === 'contract' ? 'selected' : ''; ?>>Σύμβαση Έργου</option>
                        <option value="temporary" <?php echo $listing['job_type'] === 'temporary' ? 'selected' : ''; ?>>Προσωρινή Απασχόληση</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description">Περιγραφή</label>
                    <textarea id="description" name="description" rows="6" required><?php echo htmlspecialchars($listing['description']); ?></textarea>
                </div>
            </section>
            
            <!-- Πληροφορίες αμοιβής -->
            <section class="form-section">
                <h2>Πληροφορίες Αμοιβής</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="salary_min">Ελάχιστη Αμοιβή (€)</label>
                        <input type="number" id="salary_min" name="salary_min" min="0" step="100" value="<?php echo $listing['salary_min']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="salary_max">Μέγιστη Αμοιβή (€)</label>
                        <input type="number" id="salary_max" name="salary_max" min="0" step="100" value="<?php echo $listing['salary_max']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="salary_type">Τύπος Αμοιβής</label>
                        <select id="salary_type" name="salary_type">
                            <option value="" <?php echo $listing['salary_type'] === '' ? 'selected' : ''; ?>>Επιλέξτε</option>
                            <option value="hourly" <?php echo $listing['salary_type'] === 'hourly' ? 'selected' : ''; ?>>Ανά ώρα</option>
                            <option value="daily" <?php echo $listing['salary_type'] === 'daily' ? 'selected' : ''; ?>>Ανά ημέρα</option>
                            <option value="monthly" <?php echo $listing['salary_type'] === 'monthly' ? 'selected' : ''; ?>>Ανά μήνα</option>
                            <option value="yearly" <?php echo $listing['salary_type'] === 'yearly' ? 'selected' : ''; ?>>Ανά έτος</option>
                        </select>
                    </div>
                </div>
            </section>
            
            <!-- Τοποθεσία -->
            <section class="form-section">
                <h2>Τοποθεσία</h2>
                
                <div class="form-group">
                    <label for="location">Διεύθυνση/Περιοχή</label>
                    <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($listing['location']); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="latitude">Γεωγραφικό Πλάτος</label>
                        <input type="text" id="latitude" name="latitude" value="<?php echo $listing['latitude']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="longitude">Γεωγραφικό Μήκος</label>
                        <input type="text" id="longitude" name="longitude" value="<?php echo $listing['longitude']; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="radius">Ακτίνα Αναζήτησης (km)</label>
                    <input type="number" id="radius" name="radius" min="0" value="<?php echo $listing['radius']; ?>">
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="remote_possible" name="remote_possible" value="1" <?php echo $listing['remote_possible'] ? 'checked' : ''; ?>>
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
                        <option value="car" <?php echo $listing['vehicle_type'] === 'car' ? 'selected' : ''; ?>>Αυτοκίνητο</option>
                        <option value="van" <?php echo $listing['vehicle_type'] === 'van' ? 'selected' : ''; ?>>Βαν</option>
                        <option value="truck" <?php echo $listing['vehicle_type'] === 'truck' ? 'selected' : ''; ?>>Φορτηγό</option>
                        <option value="bus" <?php echo $listing['vehicle_type'] === 'bus' ? 'selected' : ''; ?>>Λεωφορείο</option>
                        <option value="machinery" <?php echo $listing['vehicle_type'] === 'machinery' ? 'selected' : ''; ?>>Μηχάνημα Έργου</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="required_license">Απαιτούμενη Άδεια</label>
                    <input type="text" id="required_license" name="required_license" value="<?php echo htmlspecialchars($listing['required_license']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="experience_years">Έτη Εμπειρίας</label>
                    <input type="number" id="experience_years" name="experience_years" min="0" value="<?php echo $listing['experience_years']; ?>">
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="adr_certificate" name="adr_certificate" value="1" <?php echo $listing['adr_certificate'] ? 'checked' : ''; ?>>
                        Απαιτείται Πιστοποιητικό ADR
                    </label>
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="operator_license" name="operator_license" value="1" <?php echo $listing['operator_license'] ? 'checked' : ''; ?>>
                        Απαιτείται Άδεια Χειριστή Μηχανημάτων
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="required_training">Απαιτούμενη Εκπαίδευση</label>
                    <textarea id="required_training" name="required_training" rows="4"><?php echo htmlspecialchars($listing['required_training']); ?></textarea>
                </div>
            </section>
            
            <!-- Επιπλέον Πληροφορίες -->
            <section class="form-section">
                <h2>Επιπλέον Πληροφορίες</h2>
                
                <div class="form-group">
                    <label for="benefits">Παροχές</label>
                    <textarea id="benefits" name="benefits" rows="4"><?php echo htmlspecialchars($listing['benefits']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="contact_email">Email Επικοινωνίας</label>
                    <input type="email" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($listing['contact_email']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="contact_phone">Τηλέφωνο Επικοινωνίας</label>
                    <input type="tel" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($listing['contact_phone']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="expires_at">Ημερομηνία Λήξης</label>
                    <input type="date" id="expires_at" name="expires_at" value="<?php echo $listing['expires_at'] ? date('Y-m-d', strtotime($listing['expires_at'])) : ''; ?>">
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo $listing['is_active'] ? 'checked' : ''; ?>>
                        Ενεργή Αγγελία
                    </label>
                </div>
                
                <div class="form-group">
                    <label>Ετικέτες</label>
                    <div class="tags-container">
                        <?php foreach ($allTags as $tag): ?>
                            <div class="tag-item">
                                <label>
                                    <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>" <?php echo in_array($tag['id'], $selectedTagIds) ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars($tag['name']); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Αποθήκευση Αλλαγών</button>
                <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>" class="btn-secondary">Ακύρωση</a>
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
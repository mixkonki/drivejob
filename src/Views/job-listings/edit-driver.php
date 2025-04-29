<?php 
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php'; 

// Ανάκτηση σφαλμάτων και παλιών τιμών από το session
use Drivejob\Core\Session;

$errors = Session::get('errors', []);
$oldInput = Session::get('old_input', []);
Session::remove('errors');
Session::remove('old_input');

// Μετατροπή vehicle_types από κείμενο σε πίνακα αν δεν είναι ήδη πίνακας
$vehicleTypesArray = [];
if (isset($listing['vehicle_types'])) {
    if (is_array($listing['vehicle_types'])) {
        $vehicleTypesArray = $listing['vehicle_types'];
    } else if (is_string($listing['vehicle_types']) && !empty($listing['vehicle_types'])) {
        $vehicleTypesArray = explode(',', $listing['vehicle_types']);
    }
}

// Μετατροπή preferred_schedule από κείμενο σε πίνακα αν δεν είναι ήδη πίνακας
$preferredScheduleArray = [];
if (isset($listing['preferred_schedule'])) {
    if (is_array($listing['preferred_schedule'])) {
        $preferredScheduleArray = $listing['preferred_schedule'];
    } else if (is_string($listing['preferred_schedule']) && !empty($listing['preferred_schedule'])) {
        $preferredScheduleArray = explode(',', $listing['preferred_schedule']);
    }
}
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/job-listing-form.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/range-slider.css">

<main>
    <div class="container">
        <h1>Επεξεργασία Αγγελίας Αναζήτησης Εργασίας</h1>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message">
                <?php echo $_SESSION['error_message']; ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo BASE_URL; ?>job-listings/update/<?php echo $listing['id']; ?>" method="POST" class="job-listing-form driver-form">
            <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
            
            <!-- Τύπος αγγελίας (κρυφό πεδίο) - για αγγελίες οδηγών πάντα job_search -->
            <input type="hidden" name="listing_type" value="job_search">
            
            <!-- Δύο στήλες για την κύρια φόρμα -->
            <div class="form-columns">
                <!-- Αριστερή στήλη -->
                <div class="form-column">
                    <!-- Βασικές πληροφορίες -->
                    <section class="form-section">
                        <h2>Βασικές Πληροφορίες</h2>
                        
                        <div class="form-group <?php echo isset($errors['title']) ? 'has-error' : ''; ?>">
                            <label for="title">Τίτλος Αγγελίας</label>
                            <input type="text" id="title" name="title" value="<?php echo isset($oldInput['title']) ? htmlspecialchars($oldInput['title']) : htmlspecialchars($listing['title']); ?>" required>
                            <?php if (isset($errors['title'])): ?>
                                <div class="error-message"><?php echo $errors['title']; ?></div>
                            <?php endif; ?>
                            <p class="help-text">Γράψτε έναν σύντομο, περιγραφικό τίτλο που αναδεικνύει την ειδικότητά σας</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="job_type">Τύπος Απασχόλησης</label>
                            <select id="job_type" name="job_type" required>
                                <option value="full_time" <?php echo (isset($oldInput['job_type']) && $oldInput['job_type'] === 'full_time') || $listing['job_type'] === 'full_time' ? 'selected' : ''; ?>>
                                    Πλήρης Απασχόληση
                                </option>
                                <option value="part_time" <?php echo (isset($oldInput['job_type']) && $oldInput['job_type'] === 'part_time') || $listing['job_type'] === 'part_time' ? 'selected' : ''; ?>>
                                    Μερική Απασχόληση
                                </option>
                                <option value="contract" <?php echo (isset($oldInput['job_type']) && $oldInput['job_type'] === 'contract') || $listing['job_type'] === 'contract' ? 'selected' : ''; ?>>
                                    Σύμβαση Έργου
                                </option>
                                <option value="temporary" <?php echo (isset($oldInput['job_type']) && $oldInput['job_type'] === 'temporary') || $listing['job_type'] === 'temporary' ? 'selected' : ''; ?>>
                                    Προσωρινή Απασχόληση
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group <?php echo isset($errors['description']) ? 'has-error' : ''; ?>">
                            <label for="description">Περιγραφή Υπηρεσιών</label>
                            <textarea id="description" name="description" rows="6" required><?php echo isset($oldInput['description']) ? htmlspecialchars($oldInput['description']) : htmlspecialchars($listing['description']); ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="error-message"><?php echo $errors['description']; ?></div>
                            <?php endif; ?>
                            <p class="help-text">Περιγράψτε την εμπειρία σας, τα προσόντα σας και τους τύπους εργασίας που αναζητάτε</p>
                        </div>
                    </section>
                    
                    <!-- Τοποθεσία -->
                    <section class="form-section">
                        <h2>Τοποθεσία</h2>
                        
                        <div class="form-group <?php echo isset($errors['location']) ? 'has-error' : ''; ?>">
                            <label for="location">Τοποθεσία</label>
                            <input type="text" id="location" name="location" 
                                value="<?php echo isset($oldInput['location']) ? htmlspecialchars($oldInput['location']) : htmlspecialchars($listing['location']); ?>" required>
                            <?php if (isset($errors['location'])): ?>
                                <div class="error-message"><?php echo $errors['location']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Κρυφά πεδία για συντεταγμένες που συμπληρώνονται αυτόματα -->
                        <input type="hidden" id="latitude" name="latitude" value="<?php echo isset($oldInput['latitude']) ? htmlspecialchars($oldInput['latitude']) : (isset($listing['latitude']) && $listing['latitude'] !== null ? htmlspecialchars($listing['latitude']) : ''); ?>">
<input type="hidden" id="longitude" name="longitude" value="<?php echo isset($oldInput['longitude']) ? htmlspecialchars($oldInput['longitude']) : (isset($listing['longitude']) && $listing['longitude'] !== null ? htmlspecialchars($listing['longitude']) : ''); ?>">
                        
                        <!-- Ακτίνα Αναζήτησης -->
                        <div class="form-group">
                            <label for="radius">Ακτίνα Αναζήτησης: <span id="radius-value"><?php echo isset($oldInput['radius']) ? htmlspecialchars($oldInput['radius']) : htmlspecialchars($listing['radius']); ?></span> χλμ</label>
                            <div class="range-slider">
                                <input type="range" id="radius-slider" min="0" max="300" step="5" 
                                    value="<?php echo isset($oldInput['radius']) ? htmlspecialchars($oldInput['radius']) : htmlspecialchars($listing['radius']); ?>"
                                    oninput="updateRadius(this.value)">
                            </div>
                            <input type="hidden" id="radius" name="radius" value="<?php echo isset($oldInput['radius']) ? htmlspecialchars($oldInput['radius']) : htmlspecialchars($listing['radius']); ?>">
                        </div>
                        
                        <!-- Προεπισκόπηση ακτίνας στο χάρτη -->
                        <div id="map-preview" class="map-preview-container"></div>
                        
                        <!-- Δυνατότητα απομακρυσμένης εργασίας -->
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" id="remote_possible" name="remote_possible" value="1" 
                                    <?php echo (isset($oldInput['remote_possible']) && $oldInput['remote_possible']) || $listing['remote_possible'] ? 'checked' : ''; ?>>
                                Διαθέσιμος/η για εργασία εξ αποστάσεως
                            </label>
                        </div>
                    </section>
                    
                    <!-- Πληροφορίες αμοιβής -->
                    <section class="form-section">
                        <h2>Πληροφορίες Αμοιβής</h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="salary_min">Ελάχιστη Αμοιβή (€)</label>
                                <input type="number" id="salary_min" name="salary_min" min="0" step="100" 
                                       value="<?php echo isset($oldInput['salary_min']) ? htmlspecialchars($oldInput['salary_min']) : htmlspecialchars($listing['salary_min']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="salary_max">Μέγιστη Αμοιβή (€)</label>
                                <input type="number" id="salary_max" name="salary_max" min="0" step="100" 
                                       value="<?php echo isset($oldInput['salary_max']) ? htmlspecialchars($oldInput['salary_max']) : htmlspecialchars($listing['salary_max']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="salary_type">Τύπος Αμοιβής</label>
                                <select id="salary_type" name="salary_type">
                                    <option value="monthly" <?php echo (isset($oldInput['salary_type']) && $oldInput['salary_type'] === 'monthly') || $listing['salary_type'] === 'monthly' ? 'selected' : ''; ?>>Ανά μήνα</option>
                                    <option value="yearly" <?php echo (isset($oldInput['salary_type']) && $oldInput['salary_type'] === 'yearly') || $listing['salary_type'] === 'yearly' ? 'selected' : ''; ?>>Ανά έτος</option>
                                    <option value="daily" <?php echo (isset($oldInput['salary_type']) && $oldInput['salary_type'] === 'daily') || $listing['salary_type'] === 'daily' ? 'selected' : ''; ?>>Ανά ημέρα</option>
                                    <option value="hourly" <?php echo (isset($oldInput['salary_type']) && $oldInput['salary_type'] === 'hourly') || $listing['salary_type'] === 'hourly' ? 'selected' : ''; ?>>Ανά ώρα</option>
                                </select>
                            </div>
                        </div>
                    </section>
                </div>
                
                <!-- Δεξιά στήλη -->
                <div class="form-column">
                    <!-- Διαθεσιμότητα & Προτιμώμενο Ωράριο -->
                    <section class="form-section">
                        <h2>Διαθεσιμότητα & Ωράριο</h2>
                        
                        <div class="form-group availability-status">
                            <h3>Κατάσταση Διαθεσιμότητας</h3>
                            <div class="toggle-container">
                                <label class="switch-toggle">
                                    <input type="checkbox" id="available_for_work" name="available_for_work" value="1" 
                                        <?php echo (isset($driverProfile['available_for_work']) && $driverProfile['available_for_work']) ? 'checked' : ''; ?>>
                                    <span class="slider round"></span>
                                </label>
                                <span class="toggle-label" id="availability-label">
                                    <?php echo (isset($driverProfile['available_for_work']) && $driverProfile['available_for_work']) ? 'Διαθέσιμος/η για εργασία' : 'Μη διαθέσιμος/η για εργασία'; ?>
                                </span>
                            </div>
                            <p class="help-text">Η δημιουργία αγγελίας απαιτεί να είστε διαθέσιμος/η για εργασία. Μπορείτε να αλλάξετε την κατάσταση εδώ ή στο <a href="<?php echo BASE_URL; ?>drivers/edit-profile">προφίλ σας</a>.</p>
                        </div>
                        
                        <!-- Προτιμώμενο Ωράριο -->
                        <div class="form-group">
                            <h3>Προτιμώμενο Ωράριο</h3>
                            <div class="schedule-options">
                                <div class="schedule-option">
                                    <label class="schedule-card">
                                        <input type="checkbox" name="preferred_schedule[]" value="morning" 
                                            <?php echo (isset($oldInput['preferred_schedule']) && in_array('morning', $oldInput['preferred_schedule'])) || in_array('morning', $preferredScheduleArray) ? 'checked' : ''; ?>>
                                        <div class="schedule-card-content">
                                            <div class="schedule-icon morning-icon"></div>
                                            <span class="schedule-name">Πρωινό</span>
                                            <span class="schedule-hours">06:00-14:00</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="schedule-option">
                                    <label class="schedule-card">
                                        <input type="checkbox" name="preferred_schedule[]" value="afternoon" 
                                            <?php echo (isset($oldInput['preferred_schedule']) && in_array('afternoon', $oldInput['preferred_schedule'])) || in_array('afternoon', $preferredScheduleArray) ? 'checked' : ''; ?>>
                                        <div class="schedule-card-content">
                                            <div class="schedule-icon afternoon-icon"></div>
                                            <span class="schedule-name">Απογευματινό</span>
                                            <span class="schedule-hours">14:00-22:00</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="schedule-option">
                                    <label class="schedule-card">
                                        <input type="checkbox" name="preferred_schedule[]" value="night" 
                                            <?php echo (isset($oldInput['preferred_schedule']) && in_array('night', $oldInput['preferred_schedule'])) || in_array('night', $preferredScheduleArray) ? 'checked' : ''; ?>>
                                        <div class="schedule-card-content">
                                            <div class="schedule-icon night-icon"></div>
                                            <span class="schedule-name">Βραδινό</span>
                                            <span class="schedule-hours">22:00-06:00</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="schedule-option">
                                    <label class="schedule-card">
                                        <input type="checkbox" name="preferred_schedule[]" value="shifts" 
                                            <?php echo (isset($oldInput['preferred_schedule']) && in_array('shifts', $oldInput['preferred_schedule'])) || in_array('shifts', $preferredScheduleArray) ? 'checked' : ''; ?>>
                                        <div class="schedule-card-content">
                                            <div class="schedule-icon shifts-icon"></div>
                                            <span class="schedule-name">Εναλλασσόμενες</span>
                                            <span class="schedule-hours">Βάρδιες</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="schedule-option">
                                    <label class="schedule-card">
                                        <input type="checkbox" name="preferred_schedule[]" value="weekend" 
                                            <?php echo (isset($oldInput['preferred_schedule']) && in_array('weekend', $oldInput['preferred_schedule'])) || in_array('weekend', $preferredScheduleArray) ? 'checked' : ''; ?>>
                                        <div class="schedule-card-content">
                                            <div class="schedule-icon weekend-icon"></div>
                                            <span class="schedule-name">Σαββατοκύριακα</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="schedule-option">
                                    <label class="schedule-card">
                                        <input type="checkbox" name="preferred_schedule[]" value="flexible" 
                                            <?php echo (isset($oldInput['preferred_schedule']) && in_array('flexible', $oldInput['preferred_schedule'])) || in_array('flexible', $preferredScheduleArray) ? 'checked' : ''; ?>>
                                        <div class="schedule-card-content">
                                            <div class="schedule-icon flexible-icon"></div>
                                            <span class="schedule-name">Ευέλικτο</span>
                                            <span class="schedule-hours">Ωράριο</span>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Μέγιστη Διάρκεια Απουσίας -->
                        <div class="form-group">
                            <h3>Μέγιστη Διάρκεια Απουσίας από Κατοικία</h3>
                            <div class="absence-selector">
                                <div class="absence-slider">
                                    <input type="range" id="absence-slider" name="max_days_away" min="0" max="999" step="1" 
                                        value="<?php echo isset($oldInput['max_days_away']) ? $oldInput['max_days_away'] : (isset($listing['max_days_away']) ? $listing['max_days_away'] : '0'); ?>"
                                        oninput="updateAbsenceSelection(this.value)">
                                    <div class="absence-markers">
                                        <span class="absence-marker" data-value="0">0</span>
                                        <span class="absence-marker" data-value="1">1</span>
                                        <span class="absence-marker" data-value="3">3</span>
                                        <span class="absence-marker" data-value="7">7</span>
                                        <span class="absence-marker" data-value="14">14</span>
                                        <span class="absence-marker" data-value="30">30</span>
                                        <span class="absence-marker" data-value="90">90</span>
                                        <span class="absence-marker" data-value="999">∞</span>
                                    </div>
                                </div>
                                <div class="absence-value" id="absence-value-display">
                                    Διάρκεια: <span id="absence-days-text">Χωρίς διανυκτέρευση</span>
                                </div>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Τύποι Οχημάτων -->
                    <section class="form-section">
                        <h2>Προτιμώμενοι Τύποι Οχημάτων</h2>
                        
                         <!-- Κύριες κατηγορίες οχημάτων -->
                         <div class="form-group vehicle-types-container <?php echo isset($errors['vehicle_types']) ? 'has-error' : ''; ?>">
    <!-- Κύριες κατηγορίες οχημάτων -->
    <div class="vehicle-categories">
        <div class="vehicle-type-grid">
            <label class="vehicle-type-card">
                <input type="checkbox" name="vehicle_types[]" value="car" 
                    <?php echo (isset($oldInput['vehicle_types']) && in_array('car', $oldInput['vehicle_types'])) || 
                              (isset($listing['vehicle_types']) && in_array('car', $listing['vehicle_types'])) ? 'checked' : ''; ?>>
                <div class="vehicle-card-content">
                    <div class="vehicle-icon car-icon"></div>
                    <span class="vehicle-name">Αυτοκίνητο</span>
                </div>
            </label>
            
            <label class="vehicle-type-card">
                <input type="checkbox" name="vehicle_types[]" value="van" 
                    <?php echo (isset($oldInput['vehicle_types']) && in_array('van', $oldInput['vehicle_types'])) || 
                              (isset($listing['vehicle_types']) && in_array('van', $listing['vehicle_types'])) ? 'checked' : ''; ?>>
                <div class="vehicle-card-content">
                    <div class="vehicle-icon van-icon"></div>
                    <span class="vehicle-name">Βαν</span>
                </div>
            </label>
            
            <label class="vehicle-type-card">
                <input type="checkbox" name="vehicle_types[]" value="truck" 
                    <?php echo (isset($oldInput['vehicle_types']) && in_array('truck', $oldInput['vehicle_types'])) || 
                              (isset($listing['vehicle_types']) && in_array('truck', $listing['vehicle_types'])) ? 'checked' : ''; ?>>
                <div class="vehicle-card-content">
                    <div class="vehicle-icon truck-icon"></div>
                    <span class="vehicle-name">Φορτηγό</span>
                </div>
            </label>
            
            <label class="vehicle-type-card">
                <input type="checkbox" name="vehicle_types[]" value="bus" 
                    <?php echo (isset($oldInput['vehicle_types']) && in_array('bus', $oldInput['vehicle_types'])) || 
                              (isset($listing['vehicle_types']) && in_array('bus', $listing['vehicle_types'])) ? 'checked' : ''; ?>>
                <div class="vehicle-card-content">
                    <div class="vehicle-icon bus-icon"></div>
                    <span class="vehicle-name">Λεωφορείο</span>
                </div>
            </label>
            
            <label class="vehicle-type-card">
                <input type="checkbox" name="vehicle_types[]" value="machinery" 
                    <?php echo (isset($oldInput['vehicle_types']) && in_array('machinery', $oldInput['vehicle_types'])) || 
                              (isset($listing['vehicle_types']) && in_array('machinery', $listing['vehicle_types'])) ? 'checked' : ''; ?>>
                <div class="vehicle-card-content">
                    <div class="vehicle-icon machinery-icon"></div>
                    <span class="vehicle-name">Μηχάνημα Έργου</span>
                </div>
            </label>
        </div>
    </div>
    
    <?php if (isset($errors['vehicle_types'])): ?>
        <div class="error-message"><?php echo $errors['vehicle_types']; ?></div>
    <?php endif; ?>
</div>
                    </section>
                    
                    <!-- Άδειες & Προσόντα -->
                    <section class="form-section">
                        <h2>Άδειες & Προσόντα</h2>
                        
                        <!-- Άδειες Οδήγησης -->
                        <div class="form-group">
                            <h3>Άδειες Οδήγησης</h3>
                            <?php if (isset($driverProfile) && !empty($driverLicenseTypes)): ?>
                                <div class="driver-licenses-summary">
                                    <p>Οι παρακάτω άδειες από το προφίλ σας θα εμφανίζονται στην αγγελία:</p>
                                    <div class="license-badges">
                                        <?php foreach ($driverLicenseTypes as $category): ?>
                                            <?php if (!empty(trim($category))): ?>
                                                <span class="license-badge"><?php echo htmlspecialchars(trim($category)); ?></span>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="help-text">Οι άδειες εμφανίζονται αυτόματα από το προφίλ σας. <a href="<?php echo BASE_URL; ?>drivers/edit-profile">Επεξεργασία προφίλ</a></p>
                                </div>
                            <?php else: ?>
                                <p class="no-licenses">Δεν έχετε καταχωρήσει άδειες στο προφίλ σας. 
                                    <a href="<?php echo BASE_URL; ?>drivers/edit-profile">Προσθέστε τώρα</a> για καλύτερη προβολή της αγγελίας σας.</p>
                            <?php endif; ?>
                            
                            <!-- Πεδίο για την απαιτούμενη άδεια (κρυφό, αλλά απαιτείται από τη βάση δεδομένων) -->
                            <input type="hidden" id="required_license" name="required_license" value="<?php echo !empty($driverLicenseTypes) ? implode(', ', $driverLicenseTypes) : $listing['required_license']; ?>">
                        </div>
                        
                        <!-- Έτη Εμπειρίας -->
                        <div class="form-group">
                            <label for="experience_years">Συνολικά Έτη Εμπειρίας</label>
                            <input type="number" id="experience_years" name="experience_years" min="0" 
                                   value="<?php echo isset($oldInput['experience_years']) ? htmlspecialchars($oldInput['experience_years']) : htmlspecialchars($listing['experience_years']); ?>">
                        </div>
                        
                        <!-- Εξειδικευμένη Εμπειρία -->
                        <div class="form-group">
                            <label for="specialized_experience">Σχετική/Εξειδικευμένη Εμπειρία</label>
                            <textarea id="specialized_experience" name="specialized_experience" rows="3"><?php echo isset($oldInput['specialized_experience']) ? htmlspecialchars($oldInput['specialized_experience']) : (isset($listing['specialized_experience']) ? htmlspecialchars($listing['specialized_experience']) : ''); ?></textarea>
                            <p class="help-text">Περιγράψτε συγκεκριμένη εμπειρία που είναι σχετική με τον τύπο εργασίας που αναζητάτε</p>
                        </div>
                        
                        <!-- Ειδικές Άδειες και Πιστοποιήσεις -->
                        <div class="form-group">
    <h3>Ειδικές Άδειες και Πιστοποιήσεις από το Προφίλ</h3>
    
    <div class="certifications-preview">
        <!-- Πιστοποιητικό ADR -->
        <div class="certification-item <?php echo (isset($hasAdr) && $hasAdr) ? 'available' : 'not-available'; ?>">
            <div class="certification-header">
                <label>
                    <input type="checkbox" name="show_adr" value="1" 
                        <?php echo (isset($listing['show_adr']) && $listing['show_adr']) || (isset($hasAdr) && $hasAdr) ? 'checked' : (isset($hasAdr) && !$hasAdr ? 'disabled' : ''); ?>>
                    <span class="certification-name">Πιστοποιητικό ADR</span>
                </label>
                <?php if (!(isset($hasAdr) && $hasAdr)): ?>
                    <span class="certification-missing">(Δεν έχετε δηλώσει)</span>
                <?php endif; ?>
            </div>
            <?php if (isset($hasAdr) && $hasAdr): ?>
                <div class="certification-details">
                    <p><strong>Κατηγορία:</strong> 
                    <?php 
                        echo isset($adrTypes) ? htmlspecialchars($adrTypes) : 'Δεν έχει καθοριστεί';
                    ?>
                    </p>
                    <?php if (isset($driverAdrDetails['expiry_date']) && $driverAdrDetails['expiry_date']): ?>
                        <p><strong>Λήξη:</strong> <?php echo date('d/m/Y', strtotime($driverAdrDetails['expiry_date'])); ?></p>
                    <?php elseif (isset($driverProfile['adr_certificate_expiry']) && $driverProfile['adr_certificate_expiry']): ?>
                        <p><strong>Λήξη:</strong> <?php echo date('d/m/Y', strtotime($driverProfile['adr_certificate_expiry'])); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Άδεια Χειριστή Μηχανημάτων -->
        <div class="certification-item <?php echo (isset($hasOperator) && $hasOperator) ? 'available' : 'not-available'; ?>">
            <div class="certification-header">
                <label>
                    <input type="checkbox" name="show_operator_license" value="1" 
                        <?php echo (isset($listing['show_operator_license']) && $listing['show_operator_license']) || (isset($hasOperator) && $hasOperator) ? 'checked' : (isset($hasOperator) && !$hasOperator ? 'disabled' : ''); ?>>
                    <span class="certification-name">Άδεια Χειριστή Μηχανημάτων</span>
                </label>
                <?php if (!(isset($hasOperator) && $hasOperator)): ?>
                    <span class="certification-missing">(Δεν έχετε δηλώσει)</span>
                <?php endif; ?>
            </div>
            <?php if (isset($hasOperator) && $hasOperator): ?>
                <div class="certification-details">
                    <?php if (isset($operatorSpecialities) && $operatorSpecialities): ?>
                        <p><strong>Ειδικότητες:</strong> <?php echo htmlspecialchars($operatorSpecialities); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($operatorSubSpecialities) && $operatorSubSpecialities): ?>
                        <p><strong>Υποειδικότητες:</strong> <?php echo htmlspecialchars($operatorSubSpecialities); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($operatorGroupedText) && $operatorGroupedText): ?>
                        <p><?php echo htmlspecialchars($operatorGroupedText); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($driverOperatorDetails['expiry_date']) && $driverOperatorDetails['expiry_date']): ?>
                        <p><strong>Λήξη:</strong> <?php echo date('d/m/Y', strtotime($driverOperatorDetails['expiry_date'])); ?></p>
                    <?php elseif (isset($driverProfile['operator_license_expiry']) && $driverProfile['operator_license_expiry']): ?>
                        <p><strong>Λήξη:</strong> <?php echo date('d/m/Y', strtotime($driverProfile['operator_license_expiry'])); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Κάρτα Ταχογράφου -->
        <div class="certification-item <?php echo (isset($hasTachograph) && $hasTachograph) ? 'available' : 'not-available'; ?>">
            <div class="certification-header">
                <label>
                    <input type="checkbox" name="show_tachograph" value="1" 
                        <?php echo (isset($listing['show_tachograph']) && $listing['show_tachograph']) || (isset($hasTachograph) && $hasTachograph) ? 'checked' : (isset($hasTachograph) && !$hasTachograph ? 'disabled' : ''); ?>>
                    <span class="certification-name">Κάρτα Ταχογράφου</span>
                </label>
                <?php if (!(isset($hasTachograph) && $hasTachograph)): ?>
                    <span class="certification-missing">(Δεν έχετε δηλώσει)</span>
                <?php endif; ?>
            </div>
            <?php if (isset($hasTachograph) && $hasTachograph): ?>
                <div class="certification-details">
                    <?php if (isset($driverTachographDetails['card_number']) && $driverTachographDetails['card_number']): ?>
                        <p><strong>Αριθμός:</strong> <?php echo htmlspecialchars($driverTachographDetails['card_number']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($driverTachographDetails['expiry_date']) && $driverTachographDetails['expiry_date']): ?>
                        <p><strong>Λήξη:</strong> <?php echo date('d/m/Y', strtotime($driverTachographDetails['expiry_date'])); ?></p>
                    <?php elseif (isset($driverProfile['tachograph_card_expiry']) && $driverProfile['tachograph_card_expiry']): ?>
                        <p><strong>Λήξη:</strong> <?php echo date('d/m/Y', strtotime($driverProfile['tachograph_card_expiry'])); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Ειδικές Άδειες -->
        <?php if (isset($hasSpecialLicenses) && $hasSpecialLicenses && isset($driverSpecialLicensesDetails) && is_array($driverSpecialLicensesDetails)): ?>
            <?php foreach ($driverSpecialLicensesDetails as $index => $specialLicense): ?>
                <div class="certification-item available">
                    <div class="certification-header">
                        <label>
                            <input type="checkbox" name="show_special_licenses[]" value="<?php echo $specialLicense['id']; ?>" 
                                <?php echo (isset($listing['show_special_licenses']) && in_array($specialLicense['id'], $listing['show_special_licenses'])) ? 'checked' : ''; ?>>
                            <span class="certification-name"><?php echo htmlspecialchars($specialLicense['license_type']); ?></span>
                        </label>
                    </div>
                    <div class="certification-details">
                        <?php if (isset($specialLicense['license_number']) && $specialLicense['license_number']): ?>
                            <p><strong>Αριθμός:</strong> <?php echo htmlspecialchars($specialLicense['license_number']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (isset($specialLicense['expiry_date']) && $specialLicense['expiry_date']): ?>
                            <p><strong>Λήξη:</strong> <?php echo date('d/m/Y', strtotime($specialLicense['expiry_date'])); ?></p>
                        <?php endif; ?>
                        
                        <?php if (isset($specialLicense['details']) && $specialLicense['details']): ?>
                            <p><?php echo htmlspecialchars($specialLicense['details']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

                        
                        <!-- Επιλογή για εμφάνιση βαθμολογίας -->
                        <div class="form-group checkbox-group">
    <label>
        <input type="checkbox" id="show_rating" name="show_rating" value="1" 
            <?php echo (isset($listing['show_rating']) && $listing['show_rating']) || !(isset($listing['show_rating'])) ? 'checked' : ''; ?>>
        Εμφάνιση βαθμολογίας οδηγού στην αγγελία
    </label>
    <p class="help-text">Η εμφάνιση της βαθμολογίας σας μπορεί να αυξήσει την αξιοπιστία σας</p>
</div>

<!-- Επιλογή για εμφάνιση δεξιοτήτων οδηγού -->
<div class="form-group checkbox-group">
    <label>
        <input type="checkbox" id="show_skills" name="show_skills" value="1" 
            <?php echo (isset($listing['show_skills']) && $listing['show_skills']) ? 'checked' : ''; ?>>
        Εμφάνιση δεξιοτήτων οδηγού στην αγγελία
    </label>
    <p class="help-text">Οι δεξιότητες που έχετε καταχωρήσει στο προφίλ σας θα εμφανίζονται αυτόματα στην αγγελία</p>
</div>

<!-- Επιλογή για εμφάνιση εμπειρίας οδηγού -->
<div class="form-group checkbox-group">
    <label>
        <input type="checkbox" id="show_experience" name="show_experience" value="1" 
            <?php echo (isset($listing['show_experience']) && $listing['show_experience']) ? 'checked' : ''; ?>>
        Εμφάνιση εμπειρίας οδηγού στην αγγελία
    </label>
    <p class="help-text">Η εμπειρία που έχετε καταχωρήσει στο προφίλ σας θα εμφανίζεται αυτόματα στην αγγελία</p>
</div>

<!-- Επιλογή για εμφάνιση ειδικών αδειών -->
<div class="form-group checkbox-group">
    <label>
        <input type="checkbox" id="show_special_licenses" name="show_special_licenses" value="1" 
            <?php echo (isset($listing['show_special_licenses']) && $listing['show_special_licenses']) ? 'checked' : ''; ?>>
        Εμφάνιση ειδικών αδειών στην αγγελία
    </label>
    <p class="help-text">Οι ειδικές άδειες που έχετε καταχωρήσει στο προφίλ σας θα εμφανίζονται αυτόματα στην αγγελία</p>
</div>

                    </section>
                    
                    <!-- Επιπλέον Πληροφορίες -->
                    <section class="form-section">
                        <h2>Επιπλέον Πληροφορίες</h2>
                        
                        <div class="form-group">
                            <label for="contact_email">Email Επικοινωνίας</label>
                            <input type="email" id="contact_email" name="contact_email" 
                                   value="<?php echo isset($oldInput['contact_email']) ? htmlspecialchars($oldInput['contact_email']) : htmlspecialchars($listing['contact_email']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="contact_phone">Τηλέφωνο Επικοινωνίας</label>
                            <input type="tel" id="contact_phone" name="contact_phone" 
                                   value="<?php echo isset($oldInput['contact_phone']) ? htmlspecialchars($oldInput['contact_phone']) : htmlspecialchars($listing['contact_phone']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="expires_at">Ημερομηνία Λήξης Αγγελίας</label>
                            <input type="date" id="expires_at" name="expires_at" 
                                   value="<?php echo isset($oldInput['expires_at']) ? htmlspecialchars($oldInput['expires_at']) : 
                                       (isset($listing['expires_at']) ? date('Y-m-d', strtotime($listing['expires_at'])) : date('Y-m-d', strtotime('+30 days'))); ?>">
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="checkbox" id="is_active" name="is_active" value="1" 
                                       <?php echo (isset($listing['is_active']) && $listing['is_active']) ? 'checked' : ''; ?>>
                                Ενεργή Αγγελία
                            </label>
                        </div>
                    </section>
                </div>
            </div>
            
            <!-- Ετικέτες - πλήρες πλάτος -->
            <section class="form-section">
                <h2>Ετικέτες</h2>
                
                <div class="form-group">
                    <div class="tags-container">
                        <?php if (isset($allTags) && is_array($allTags) && !empty($allTags)): ?>
                            <?php foreach ($allTags as $tag): ?>
                                <div class="tag-item">
                                    <label>
                                        <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>" <?php echo isset($selectedTagIds) && in_array($tag['id'], $selectedTagIds) ? 'checked' : ''; ?>>
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
            
            <!-- Προεπισκόπηση της αγγελίας -->
            <section class="form-section">
                <h2>Προεπισκόπηση Αγγελίας</h2>
                <div class="preview-button-container">
                    <button type="button" id="preview-button" class="btn-secondary">Προεπισκόπηση Αγγελίας</button>
                </div>
                <div id="listing-preview" class="listing-preview"></div>
            </section>
            
            <div class="form-actions">
                <button type="submit" class="btn-primary">Αποθήκευση Αλλαγών</button>
                <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>" class="btn-secondary">Ακύρωση</a>
            </div>
        </form>
    </div>
</main>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCgZpJWVYyrY0U8U1jBGelEWryur3vIrzc&libraries=places,geometry&callback=initMap" async defer></script>
<script>
    let map;
    let radiusCircle;
    
    // Αρχικοποίηση του χάρτη
    function initMap() {
        // Αρχική θέση (θέση από την αγγελία ή Αθήνα αν δεν υπάρχουν συντεταγμένες)
        const lat = parseFloat(document.getElementById('latitude').value) || 37.97918;
        const lng = parseFloat(document.getElementById('longitude').value) || 23.71632;
        const center = {lat: lat, lng: lng};
        
        const mapPreviewContainer = document.getElementById('map-preview');
        if (!mapPreviewContainer) return;
        
        // Δημιουργία του χάρτη
        map = new google.maps.Map(mapPreviewContainer, {
            center: center,
            zoom: 10,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false
        });
        
        // Δημιουργία του δείκτη
        new google.maps.Marker({
            position: center,
            map: map,
            title: 'Τοποθεσία Εργασίας'
        });
        
        // Δημιουργία του κύκλου ακτίνας
        const radius = parseInt(document.getElementById('radius').value) || 20;
        radiusCircle = new google.maps.Circle({
            map: map,
            center: center,
            radius: radius * 1000, // Μετατροπή σε μέτρα
            fillColor: '#4285F4',
            fillOpacity: 0.2,
            strokeColor: '#4285F4',
            strokeOpacity: 0.5,
            strokeWeight: 1
        });
        
        // Προσαρμογή του zoom ώστε να φαίνεται όλος ο κύκλος
        const bounds = radiusCircle.getBounds();
        map.fitBounds(bounds);
        
        // Αρχικοποίηση αυτόματης συμπλήρωσης τοποθεσίας
        initGooglePlaces();
    }
    
    // Αυτόματη συμπλήρωση διεύθυνσης με Google Places API
    function initGooglePlaces() {
        const locationInput = document.getElementById('location');
        if (!locationInput) return;
        
        const autocomplete = new google.maps.places.Autocomplete(locationInput);
        
        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            
            if (!place.geometry) {
                return;
            }
            
            // Συμπλήρωση γεωγραφικών συντεταγμένων
            document.getElementById('latitude').value = place.geometry.location.lat();
            document.getElementById('longitude').value = place.geometry.location.lng();
            
            // Ενημέρωση του χάρτη
            updateMapPreview();
        });
    }
    
    // Ενημέρωση του χάρτη προεπισκόπησης με τις νέες συντεταγμένες
    function updateMapPreview() {
        if (!map) return;
        
        const lat = parseFloat(document.getElementById('latitude').value);
        const lng = parseFloat(document.getElementById('longitude').value);
        
        if (isNaN(lat) || isNaN(lng)) return;
        
        const center = {lat: lat, lng: lng};
        map.setCenter(center);
        
        // Ενημέρωση του δείκτη
        const markers = map.markers || [];
        if (markers.length > 0) {
            markers.forEach(marker => marker.setMap(null));
        }
        
        const newMarker = new google.maps.Marker({
            position: center,
            map: map,
            title: 'Τοποθεσία Εργασίας'
        });
        
        map.markers = [newMarker];
        
        // Ενημέρωση του κύκλου ακτίνας
        if (radiusCircle) {
            radiusCircle.setMap(null);
        }
        
        const radius = parseInt(document.getElementById('radius').value) || 20;
        radiusCircle = new google.maps.Circle({
            map: map,
            center: center,
            radius: radius * 1000, // Μετατροπή σε μέτρα
            fillColor: '#4285F4',
            fillOpacity: 0.2,
            strokeColor: '#4285F4',
            strokeOpacity: 0.5,
            strokeWeight: 1
        });
        
        // Προσαρμογή του zoom ώστε να φαίνεται όλος ο κύκλος
        const bounds = radiusCircle.getBounds();
        map.fitBounds(bounds);
    }
    
    // Ενημέρωση της τιμής ακτίνας και του χάρτη
    function updateRadius(value) {
        document.getElementById('radius-value').textContent = value;
        document.getElementById('radius').value = value;
        
        // Ενημέρωση του κύκλου ακτίνας στο χάρτη
        if (radiusCircle) {
            radiusCircle.setRadius(parseInt(value) * 1000); // Μετατροπή σε μέτρα
            
            // Προσαρμογή του zoom ώστε να φαίνεται όλος ο κύκλος
            const bounds = radiusCircle.getBounds();
            if (map) {
                map.fitBounds(bounds);
            }
        }
    }
    
    // Ενημέρωση του κειμένου για τη μέγιστη διάρκεια απουσίας
    function updateAbsenceSelection(value) {
        const daysValue = parseInt(value);
        const absenceDaysText = document.getElementById('absence-days-text');
        
        if (!absenceDaysText) return;
        
        let text = '';
        
        if (daysValue === 0) {
            text = 'Χωρίς διανυκτέρευση';
        } else if (daysValue === 1) {
            text = '1 ημέρα';
        } else if (daysValue <= 6) {
            text = daysValue + ' ημέρες';
        } else if (daysValue === 7) {
            text = '1 εβδομάδα';
        } else if (daysValue === 14) {
            text = '2 εβδομάδες';
        } else if (daysValue === 30) {
            text = '1 μήνας';
        } else if (daysValue === 90) {
            text = '3 μήνες';
        } else if (daysValue === 999) {
            text = 'Απεριόριστο';
        } else {
            text = daysValue + ' ημέρες';
        }
        
        absenceDaysText.textContent = text;
    }
    
    // Ενημέρωση της ετικέτας διαθεσιμότητας όταν αλλάζει η κατάσταση
    document.addEventListener('DOMContentLoaded', function() {
        const availabilityToggle = document.getElementById('available_for_work');
        const availabilityLabel = document.getElementById('availability-label');
        
        if (availabilityToggle && availabilityLabel) {
            availabilityToggle.addEventListener('change', function() {
                if (this.checked) {
                    availabilityLabel.textContent = 'Διαθέσιμος/η για εργασία';
                    availabilityLabel.style.color = '#2e7d32';
                } else {
                    availabilityLabel.textContent = 'Μη διαθέσιμος/η για εργασία';
                    availabilityLabel.style.color = '#c62828';
                }
            });
        }
        
        // Αρχικοποίηση του slider απουσίας
        const absenceSlider = document.getElementById('absence-slider');
        if (absenceSlider) {
            updateAbsenceSelection(absenceSlider.value);
            absenceSlider.addEventListener('input', function() {
                updateAbsenceSelection(this.value);
            });
        }
        
        // Αρχικοποίηση ακτίνας
        const radiusSlider = document.getElementById('radius-slider');
        if (radiusSlider) {
            updateRadius(radiusSlider.value);
            radiusSlider.addEventListener('input', function() {
                updateRadius(this.value);
            });
        }
        
        // Προσθήκη event listener για το κουμπί προεπισκόπησης
        const previewButton = document.getElementById('preview-button');
        if (previewButton) {
            previewButton.addEventListener('click', generateListingPreview);
        }
    });
    
    // Δημιουργία της προεπισκόπησης αγγελίας
    function generateListingPreview() {
        const previewContainer = document.getElementById('listing-preview');
        if (!previewContainer) return;
        
        // Συλλογή δεδομένων από τη φόρμα
        const title = document.getElementById('title').value || 'Τίτλος Αγγελίας';
        const description = document.getElementById('description').value || 'Περιγραφή Αγγελίας';
        const jobType = document.getElementById('job_type');
        const jobTypeText = jobType ? jobType.options[jobType.selectedIndex].text : '';
        
        // Τύποι οχημάτων
        const vehicleTypes = [];
        document.querySelectorAll('input[name="vehicle_types[]"]:checked').forEach(checkbox => {
            vehicleTypes.push(checkbox.parentNode.querySelector('.vehicle-name').textContent);
        });
        
        // Ωράριο
        const scheduleTypes = [];
        document.querySelectorAll('input[name="preferred_schedule[]"]:checked').forEach(checkbox => {
            scheduleTypes.push(checkbox.parentNode.querySelector('.schedule-name').textContent);
        });
        
        // Απουσία
        const absenceDaysText = document.getElementById('absence-days-text');
        const absenceText = absenceDaysText ? absenceDaysText.textContent : 'Χωρίς διανυκτέρευση';
        
        // Τοποθεσία
        const location = document.getElementById('location').value || 'Δεν έχει οριστεί τοποθεσία';
        const radius = document.getElementById('radius-value').textContent || '20';
        
        // Μισθός
        const salaryMin = document.getElementById('salary_min').value;
        const salaryMax = document.getElementById('salary_max').value;
        const salaryType = document.getElementById('salary_type');
        const salaryTypeText = salaryType ? salaryType.options[salaryType.selectedIndex].text : '';
        
        // Εμπειρία
        const experienceYears = document.getElementById('experience_years').value || '0';
        const specializedExperience = document.getElementById('specialized_experience').value || '';
        
        // Άδειες & Πιστοποιήσεις
        const showADR = document.querySelector('input[name="show_adr"]')?.checked;
        const showOperator = document.querySelector('input[name="show_operator_license"]')?.checked;
        const showTachograph = document.querySelector('input[name="show_tachograph"]')?.checked;
        const showRating = document.querySelector('input[name="show_rating"]')?.checked;
        
        // Δημιουργία του HTML της προεπισκόπησης
        let html = `
            <div class="job-listing-preview">
                <h3>${title}</h3>
                <div class="job-type-badges">
                    <span class="job-type-badge">${jobTypeText}</span>
                    <span class="listing-type-badge">Αναζήτηση Εργασίας</span>
                </div>
                
                <div class="listing-details">
                    <div class="listing-detail">
                        <strong>Τοποθεσία:</strong> ${location} (ακτίνα: ${radius} χλμ)
                    </div>
                    
                    <div class="listing-detail">
                        <strong>Τύποι Οχημάτων:</strong> ${vehicleTypes.join(', ') || 'Δεν έχουν επιλεγεί'}
                    </div>
                    
                    ${scheduleTypes.length > 0 ? 
                      `<div class="listing-detail">
                          <strong>Προτιμώμενο Ωράριο:</strong> ${scheduleTypes.join(', ')}
                       </div>` : ''}
                    
                    <div class="listing-detail">
                        <strong>Μέγιστη διάρκεια απουσίας:</strong> ${absenceText}
                    </div>
                    
                    ${(salaryMin || salaryMax) ? 
                      `<div class="listing-detail">
                          <strong>Αμοιβή:</strong> 
                          ${salaryMin && salaryMax ? `${salaryMin}€ - ${salaryMax}€` : 
                            (salaryMin ? `Από ${salaryMin}€` : 
                             (salaryMax ? `Έως ${salaryMax}€` : ''))}
                          ${salaryTypeText ? ` / ${salaryTypeText}` : ''}
                       </div>` : ''}
                    
                    <div class="listing-detail">
                        <strong>Συνολική εμπειρία:</strong> ${experienceYears} έτη
                    </div>
                    
                    ${showRating ? 
                      `<div class="listing-detail driver-rating">
                          <strong>Βαθμολογία Οδηγού:</strong> 
                          <div class="stars">★★★★☆</div> 4.0/5
                       </div>` : ''}
                </div>
                
                <div class="listing-description">
                    <h4>Περιγραφή</h4>
                    <p>${description.replace(/\n/g, '<br>')}</p>
                    
                    ${specializedExperience ? 
                      `<h4>Εξειδικευμένη Εμπειρία</h4>
                       <p>${specializedExperience.replace(/\n/g, '<br>')}</p>` : ''}
                </div>
                
                ${(showADR || showOperator || showTachograph) ? 
                  `<div class="driver-certifications">
                      <h4>Πιστοποιήσεις & Άδειες</h4>
                      <ul>
                          ${showADR ? '<li><strong>Πιστοποιητικό ADR:</strong> ✓</li>' : ''}
                          ${showOperator ? '<li><strong>Άδεια Χειριστή Μηχανημάτων:</strong> ✓</li>' : ''}
                          ${showTachograph ? '<li><strong>Κάρτα Ταχογράφου:</strong> ✓</li>' : ''}
                      </ul>
                   </div>` : ''}
                
                <div class="preview-note">
                    <p><i>Αυτή είναι μια προεπισκόπηση της αγγελίας σας. Μπορείτε να επιστρέψετε στη φόρμα για να κάνετε αλλαγές.</i></p>
                </div>
            </div>
        `;
        
        previewContainer.innerHTML = html;
        previewContainer.scrollIntoView({ behavior: 'smooth' });
    }
</script>
<?php 
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php'; 
?>
<?php 
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php'; 

// Καθορισμός αν ο χρήστης είναι οδηγός ή εταιρεία
$isDriver = isset($_SESSION['role']) && $_SESSION['role'] === 'driver';
$listingType = $isDriver ? 'job_search' : 'job_offer';
$pageTitle = $isDriver ? 'Δημιουργία Αγγελίας Αναζήτησης Εργασίας' : 'Δημιουργία Αγγελίας Προσφοράς Εργασίας';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/job-listing-form.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/range-slider.css">

<main>
    <div class="container">
        <h1><?php echo $pageTitle; ?></h1>
        
        <?php
        use Drivejob\Core\Session;
        
        // Ανάκτηση σφαλμάτων και παλιών τιμών από το session
        $errors = Session::get('errors', []);
        $oldInput = Session::get('old_input', []);
        
        // Καθαρισμός των session μεταβλητών μετά την ανάκτησή τους
        Session::remove('errors');
        Session::remove('old_input');
        ?>
        
        <form action="<?php echo BASE_URL; ?>job-listings/store" method="POST" class="job-listing-form <?php echo $isDriver ? 'driver-form' : 'company-form'; ?>">
            <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
            
            <!-- Τύπος αγγελίας (κρυφό πεδίο) -->
            <input type="hidden" name="listing_type" value="<?php echo $listingType; ?>">
            
            <!-- Βασικές πληροφορίες -->
            <section class="form-section">
                <h2>Βασικές Πληροφορίες</h2>
                
                <div class="form-group <?php echo isset($errors['title']) ? 'has-error' : ''; ?>">
                    <label for="title">Τίτλος Αγγελίας</label>
                    <input type="text" id="title" name="title" value="<?php echo isset($oldInput['title']) ? htmlspecialchars($oldInput['title']) : ''; ?>" required>
                    <?php if (isset($errors['title'])): ?>
                        <div class="error-message"><?php echo $errors['title']; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="job_type">Τύπος Απασχόλησης</label>
                    <select id="job_type" name="job_type" required>
                        <option value="full_time" <?php echo isset($oldInput['job_type']) && $oldInput['job_type'] === 'full_time' ? 'selected' : 
                            (isset($driverProfile['preferred_job_type']) && $driverProfile['preferred_job_type'] === 'full_time' ? 'selected' : ''); ?>>
                            Πλήρης Απασχόληση
                        </option>
                        <option value="part_time" <?php echo isset($oldInput['job_type']) && $oldInput['job_type'] === 'part_time' ? 'selected' : 
                            (isset($driverProfile['preferred_job_type']) && $driverProfile['preferred_job_type'] === 'part_time' ? 'selected' : ''); ?>>
                            Μερική Απασχόληση
                        </option>
                        <option value="contract" <?php echo isset($oldInput['job_type']) && $oldInput['job_type'] === 'contract' ? 'selected' : 
                            (isset($driverProfile['preferred_job_type']) && $driverProfile['preferred_job_type'] === 'contract' ? 'selected' : ''); ?>>
                            Σύμβαση Έργου
                        </option>
                        <option value="temporary" <?php echo isset($oldInput['job_type']) && $oldInput['job_type'] === 'temporary' ? 'selected' : 
                            (isset($driverProfile['preferred_job_type']) && $driverProfile['preferred_job_type'] === 'temporary' ? 'selected' : ''); ?>>
                            Προσωρινή Απασχόληση
                        </option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description">Περιγραφή</label>
                    <textarea id="description" name="description" rows="6" required><?php echo isset($oldInput['description']) ? htmlspecialchars($oldInput['description']) : ''; ?></textarea>
                    <?php if ($isDriver): ?>
                    <p class="help-text">Περιγράψτε την εμπειρία σας, τα προσόντα σας και τους τύπους εργασίας που αναζητάτε.</p>
                    <?php else: ?>
                    <p class="help-text">Περιγράψτε λεπτομερώς τη θέση, τις αρμοδιότητες και το προφίλ του ιδανικού υποψηφίου.</p>
                    <?php endif; ?>
                </div>
            </section>
            
            <section class="form-section">
    <h2>Ωράριο και Διαθεσιμότητα</h2>
    
    <div class="form-group">
        <h3>Προτιμώμενο Ωράριο</h3>
        <div class="schedule-options">
            <div class="schedule-option">
                <label class="schedule-card">
                    <input type="checkbox" name="preferred_schedule[]" value="morning" 
                        <?php echo isset($oldInput['preferred_schedule']) && in_array('morning', $oldInput['preferred_schedule']) ? 'checked' : ''; ?>>
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
                        <?php echo isset($oldInput['preferred_schedule']) && in_array('afternoon', $oldInput['preferred_schedule']) ? 'checked' : ''; ?>>
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
                        <?php echo isset($oldInput['preferred_schedule']) && in_array('night', $oldInput['preferred_schedule']) ? 'checked' : ''; ?>>
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
                        <?php echo isset($oldInput['preferred_schedule']) && in_array('shifts', $oldInput['preferred_schedule']) ? 'checked' : ''; ?>>
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
                        <?php echo isset($oldInput['preferred_schedule']) && in_array('weekend', $oldInput['preferred_schedule']) ? 'checked' : ''; ?>>
                    <div class="schedule-card-content">
                        <div class="schedule-icon weekend-icon"></div>
                        <span class="schedule-name">Σαββατοκύριακα</span>
                    </div>
                </label>
            </div>
            <div class="schedule-option">
                <label class="schedule-card">
                    <input type="checkbox" name="preferred_schedule[]" value="flexible" 
                        <?php echo isset($oldInput['preferred_schedule']) && in_array('flexible', $oldInput['preferred_schedule']) ? 'checked' : ''; ?>>
                    <div class="schedule-card-content">
                        <div class="schedule-icon flexible-icon"></div>
                        <span class="schedule-name">Ευέλικτο</span>
                        <span class="schedule-hours">Ωράριο</span>
                    </div>
                </label>
            </div>
        </div>
    </div>
    
    <div class="form-group">
        <h3>Μέγιστη Διάρκεια Απουσίας από Κατοικία</h3>
        <div class="absence-selector">
            <div class="absence-slider">
                <input type="range" id="absence-slider" name="max_days_away" min="0" max="999" step="1" 
                       value="<?php echo isset($oldInput['max_days_away']) ? $oldInput['max_days_away'] : '0'; ?>"
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
<section class="form-section">
    <h2>Ανάδειξη Προσόντων και Εμπειρίας</h2>
    
    <div class="form-group">
        <h3>Πιστοποιήσεις και Άδειες από το Προφίλ σας</h3>
        
        <div class="certifications-preview">
    <!-- Πιστοποιητικό ADR -->
    <div class="certification-item <?php echo (isset($driverProfile['adr_certificate']) && $driverProfile['adr_certificate']) ? 'available' : 'not-available'; ?>">
        <div class="certification-header">
            <label>
                <input type="checkbox" name="show_adr" value="1" 
                    <?php echo (isset($driverProfile['adr_certificate']) && $driverProfile['adr_certificate']) ? 'checked' : 'disabled'; ?>>
                <span class="certification-name">Πιστοποιητικό ADR</span>
            </label>
            <?php if (!(isset($driverProfile['adr_certificate']) && $driverProfile['adr_certificate'])): ?>
                <span class="certification-missing">(Δεν έχετε δηλώσει)</span>
            <?php endif; ?>
        </div>
        <?php if (isset($driverProfile['adr_certificate']) && $driverProfile['adr_certificate']): ?>
            <div class="certification-details">
                <p><strong>Κατηγορία:</strong> 
                <?php 
                    $adrClasses = '';
                    if (isset($driverAdr) && !empty($driverAdr)) {
                        $types = array_column($driverAdr, 'adr_type');
                        $adrClasses = implode(', ', $types);
                    } else {
                        $adrClasses = $driverProfile['adr_classes'] ?? 'Δεν έχει καθοριστεί';
                    }
                    echo htmlspecialchars($adrClasses); 
                ?>
                </p>
                <?php if (isset($driverProfile['adr_certificate_expiry']) && $driverProfile['adr_certificate_expiry']): ?>
                    <p><strong>Λήξη:</strong> <?php echo date('d/m/Y', strtotime($driverProfile['adr_certificate_expiry'])); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Άδεια Χειριστή Μηχανημάτων -->
    <div class="certification-item <?php echo (isset($driverProfile['operator_license']) && $driverProfile['operator_license']) ? 'available' : 'not-available'; ?>">
        <div class="certification-header">
            <label>
                <input type="checkbox" name="show_operator_license" value="1" 
                    <?php echo (isset($driverProfile['operator_license']) && $driverProfile['operator_license']) ? 'checked' : 'disabled'; ?>>
                <span class="certification-name">Άδεια Χειριστή Μηχανημάτων</span>
            </label>
            <?php if (!(isset($driverProfile['operator_license']) && $driverProfile['operator_license'])): ?>
                <span class="certification-missing">(Δεν έχετε δηλώσει)</span>
            <?php endif; ?>
        </div>
        <?php if (isset($driverProfile['operator_license']) && $driverProfile['operator_license']): ?>
            <div class="certification-details">
                <p><strong>Τύπος:</strong> 
                <?php 
                    $operatorTypes = '';
                    if (isset($driverOperator) && !empty($driverOperator)) {
                        // Συγκέντρωση όλων των ειδικοτήτων
                        $specialities = array_column($driverOperator, 'speciality');
                        // Συγκέντρωση όλων των υπο-ειδικοτήτων
                        $subSpecialities = [];
                        foreach ($driverOperator as $license) {
                            if (isset($license['sub_specialities']) && !empty($license['sub_specialities'])) {
                                foreach ($license['sub_specialities'] as $subSpec) {
                                    $subSpecialities[] = $subSpec['sub_speciality'];
                                }
                            }
                        }
                        
                        if (!empty($subSpecialities)) {
                            $operatorTypes = implode(', ', $subSpecialities);
                        } else if (!empty($specialities)) {
                            $operatorTypes = implode(', ', $specialities);
                        } else {
                            $operatorTypes = 'Δεν έχει καθοριστεί';
                        }
                    } else {
                        $operatorTypes = $driverProfile['operator_license_type'] ?? 'Δεν έχει καθοριστεί';
                    }
                    echo htmlspecialchars($operatorTypes); 
                ?>
                </p>
                <?php if (isset($driverProfile['operator_license_expiry']) && $driverProfile['operator_license_expiry']): ?>
                    <p><strong>Λήξη:</strong> <?php echo date('d/m/Y', strtotime($driverProfile['operator_license_expiry'])); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Κάρτα Ταχογράφου -->
    <div class="certification-item <?php echo (isset($driverProfile['tachograph_card']) && $driverProfile['tachograph_card']) ? 'available' : 'not-available'; ?>">
        <div class="certification-header">
            <label>
                <input type="checkbox" name="show_tachograph" value="1" 
                    <?php echo (isset($driverProfile['tachograph_card']) && $driverProfile['tachograph_card']) ? 'checked' : 'disabled'; ?>>
                <span class="certification-name">Κάρτα Ταχογράφου</span>
            </label>
            <?php if (!(isset($driverProfile['tachograph_card']) && $driverProfile['tachograph_card'])): ?>
                <span class="certification-missing">(Δεν έχετε δηλώσει)</span>
            <?php endif; ?>
        </div>
        <?php if (isset($driverProfile['tachograph_card']) && $driverProfile['tachograph_card']): ?>
            <div class="certification-details">
                <?php 
                    $tachographNumber = '';
                    $tachographExpiry = null;
                    if (isset($driverTachograph) && !empty($driverTachograph)) {
                        $tachographNumber = $driverTachograph[0]['card_number'] ?? '';
                        $tachographExpiry = $driverTachograph[0]['expiry_date'] ?? null;
                    } else {
                        $tachographExpiry = $driverProfile['tachograph_card_expiry'] ?? null;
                    }
                    
                    if (!empty($tachographNumber)): 
                ?>
                    <p><strong>Αριθμός:</strong> <?php echo htmlspecialchars($tachographNumber); ?></p>
                <?php endif; ?>
                
                <?php if ($tachographExpiry): ?>
                    <p><strong>Λήξη:</strong> <?php echo date('d/m/Y', strtotime($tachographExpiry)); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
    <div class="form-group">
        <div class="profile-highlight">
            <h3>Ανάδειξη Προφίλ</h3>
            <div class="highlight-options">
                <label class="highlight-option">
                    <input type="checkbox" name="show_skills" value="1" checked>
                    <div class="highlight-content">
                        <div class="highlight-icon skills-icon"></div>
                        <span>Δεξιότητες και Ικανότητες</span>
                    </div>
                </label>
                
                <label class="highlight-option">
                    <input type="checkbox" name="show_experience" value="1" checked>
                    <div class="highlight-content">
                        <div class="highlight-icon experience-icon"></div>
                        <span>Προϋπηρεσία</span>
                    </div>
                </label>
                
                <label class="highlight-option">
                    <input type="checkbox" name="show_rating" value="1" checked>
                    <div class="highlight-content">
                        <div class="highlight-icon rating-icon"></div>
                        <span>Βαθμολογία Οδηγού</span>
                    </div>
                </label>
                
                <label class="highlight-option">
                    <input type="checkbox" name="show_profile_link" value="1" checked>
                    <div class="highlight-content">
                        <div class="highlight-icon profile-icon"></div>
                        <span>Σύνδεσμος Πλήρους Προφίλ</span>
                    </div>
                </label>
            </div>
        </div>
    </div>
</section>
            <!-- Πληροφορίες αμοιβής -->
            <section class="form-section">
                <h2>Πληροφορίες Αμοιβής</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="salary_min">Ελάχιστη Αμοιβή (€)</label>
                        <input type="number" id="salary_min" name="salary_min" min="0" step="100" 
                               value="<?php echo isset($oldInput['salary_min']) ? htmlspecialchars($oldInput['salary_min']) : 
                                   ($isDriver && isset($driverProfile['salary_expectations']) ? $driverProfile['salary_expectations'] : ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="salary_max">Μέγιστη Αμοιβή (€)</label>
                        <input type="number" id="salary_max" name="salary_max" min="0" step="100" 
                               value="<?php echo isset($oldInput['salary_max']) ? htmlspecialchars($oldInput['salary_max']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="salary_type">Τύπος Αμοιβής</label>
                        <select id="salary_type" name="salary_type">
                            <?php if ($isDriver): ?>
                                <option value="monthly" <?php echo isset($oldInput['salary_type']) && $oldInput['salary_type'] === 'monthly' ? 'selected' : 'selected'; ?>>Ανά μήνα</option>
                                <option value="yearly" <?php echo isset($oldInput['salary_type']) && $oldInput['salary_type'] === 'yearly' ? 'selected' : ''; ?>>Ανά έτος</option>
                            <?php else: ?>
                                <option value="" <?php echo !isset($oldInput['salary_type']) || $oldInput['salary_type'] === '' ? 'selected' : ''; ?>>Επιλέξτε</option>
                                <option value="hourly" <?php echo isset($oldInput['salary_type']) && $oldInput['salary_type'] === 'hourly' ? 'selected' : ''; ?>>Ανά ώρα</option>
                                <option value="daily" <?php echo isset($oldInput['salary_type']) && $oldInput['salary_type'] === 'daily' ? 'selected' : ''; ?>>Ανά ημέρα</option>
                                <option value="monthly" <?php echo isset($oldInput['salary_type']) && $oldInput['salary_type'] === 'monthly' ? 'selected' : ''; ?>>Ανά μήνα</option>
                                <option value="yearly" <?php echo isset($oldInput['salary_type']) && $oldInput['salary_type'] === 'yearly' ? 'selected' : ''; ?>>Ανά έτος</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </section>
            
            <!-- Τοποθεσία -->
            <section class="form-section">
                <h2>Τοποθεσία</h2>
                
                <div class="form-group">
                    <label for="location">Διεύθυνση/Περιοχή</label>
                    <input type="text" id="location" name="location" 
                           value="<?php echo isset($oldInput['location']) ? htmlspecialchars($oldInput['location']) : 
                               ($isDriver && isset($driverProfile['city']) ? $driverProfile['city'] . ', ' . $driverProfile['country'] : ''); ?>" required>
                    <?php if ($isDriver): ?>
                    <div class="location-options">
                        <label>
                            <input type="checkbox" id="use_profile_location" name="use_profile_location" value="1" checked>
                            Χρήση τοποθεσίας από το προφίλ μου
                        </label>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Κρυφά πεδία για συντεταγμένες που συμπληρώνονται αυτόματα -->
                <input type="hidden" id="latitude" name="latitude" value="<?php echo isset($oldInput['latitude']) ? htmlspecialchars($oldInput['latitude']) : 
                    ($isDriver && isset($driverProfile['latitude']) ? $driverProfile['latitude'] : ''); ?>">
                <input type="hidden" id="longitude" name="longitude" value="<?php echo isset($oldInput['longitude']) ? htmlspecialchars($oldInput['longitude']) : 
                    ($isDriver && isset($driverProfile['longitude']) ? $driverProfile['longitude'] : ''); ?>">
                
                <div class="form-group">
                    <label for="radius">Ακτίνα Αναζήτησης: <span id="radius-value">20</span> χλμ</label>
                    <div class="range-slider">
                        <input type="range" id="radius-slider" min="0" max="300" step="5" 
                               value="<?php echo isset($oldInput['radius']) ? htmlspecialchars($oldInput['radius']) : 
                                   ($isDriver && isset($driverProfile['preferred_radius']) ? $driverProfile['preferred_radius'] : '20'); ?>"
                               oninput="updateRadius(this.value)">
                    </div>
                    <input type="hidden" id="radius" name="radius" value="<?php echo isset($oldInput['radius']) ? htmlspecialchars($oldInput['radius']) : 
                               ($isDriver && isset($driverProfile['preferred_radius']) ? $driverProfile['preferred_radius'] : '20'); ?>">
                </div>
                
                <!-- Προεπισκόπηση ακτίνας στο χάρτη -->
                <div id="map-preview" class="map-preview-container"></div>
                
                <div class="form-group checkbox-group">
    <label>
        <input type="checkbox" id="show_rating" name="show_rating" value="1" checked>
        Εμφάνιση βαθμολογίας οδηγού στην αγγελία
    </label>
    <p class="help-text">Η εμφάνιση της βαθμολογίας σας μπορεί να αυξήσει την αξιοπιστία σας</p>
</div>

<!-- 4. Προσθήκη τμήματος δεξιοτήτων οδηγού -->
<div class="form-group checkbox-group">
    <label>
        <input type="checkbox" id="show_skills" name="show_skills" value="1" checked>
        Εμφάνιση δεξιοτήτων οδηγού στην αγγελία
    </label>
    <p class="help-text">Οι δεξιότητες που έχετε καταχωρήσει στο προφίλ σας θα εμφανίζονται αυτόματα στην αγγελία</p>
</div>

<!-- 5. Προσθήκη τμήματος εμπειρίας οδηγού -->
<div class="form-group checkbox-group">
    <label>
        <input type="checkbox" id="show_experience" name="show_experience" value="1" checked>
        Εμφάνιση εμπειρίας οδηγού στην αγγελία
    </label>
    <p class="help-text">Η εμπειρία που έχετε καταχωρήσει στο προφίλ σας θα εμφανίζεται αυτόματα στην αγγελία</p>
</div>
            </section>
            
            <section class="form-section">
    <h2><?php echo $isDriver ? 'Προτιμώμενοι Τύποι Οχημάτων' : 'Τύποι Οχημάτων'; ?></h2>
    
    <div class="form-group vehicle-types-container">
        <!-- Κύριες κατηγορίες οχημάτων -->
        <div class="form-group vehicle-types-container <?php echo isset($errors['vehicle_types']) ? 'has-error' : ''; ?>">
    <!-- Κύριες κατηγορίες οχημάτων -->
    <div class="vehicle-categories">
        <div class="vehicle-type-grid">
            <label class="vehicle-type-card">
                <input type="checkbox" name="vehicle_types[]" value="car" 
                    <?php echo isset($oldInput['vehicle_types']) && in_array('car', $oldInput['vehicle_types']) ? 'checked' : 
                        (isset($driverProfile['preferred_vehicle_type']) && $driverProfile['preferred_vehicle_type'] === 'car' ? 'checked' : ''); ?>>
                <div class="vehicle-card-content">
                    <div class="vehicle-icon car-icon"></div>
                    <span class="vehicle-name">Αυτοκίνητο</span>
                </div>
            </label>
            
            <label class="vehicle-type-card">
                <input type="checkbox" name="vehicle_types[]" value="van" 
                    <?php echo isset($oldInput['vehicle_types']) && in_array('van', $oldInput['vehicle_types']) ? 'checked' : 
                        (isset($driverProfile['preferred_vehicle_type']) && $driverProfile['preferred_vehicle_type'] === 'van' ? 'checked' : ''); ?>>
                <div class="vehicle-card-content">
                    <div class="vehicle-icon van-icon"></div>
                    <span class="vehicle-name">Βαν</span>
                </div>
            </label>
            
            <label class="vehicle-type-card">
                <input type="checkbox" name="vehicle_types[]" value="truck" 
                    <?php echo isset($oldInput['vehicle_types']) && in_array('truck', $oldInput['vehicle_types']) ? 'checked' : 
                        (isset($driverProfile['preferred_vehicle_type']) && $driverProfile['preferred_vehicle_type'] === 'truck' ? 'checked' : ''); ?>>
                <div class="vehicle-card-content">
                    <div class="vehicle-icon truck-icon"></div>
                    <span class="vehicle-name">Φορτηγό</span>
                </div>
            </label>
            
            <label class="vehicle-type-card">
                <input type="checkbox" name="vehicle_types[]" value="bus" 
                    <?php echo isset($oldInput['vehicle_types']) && in_array('bus', $oldInput['vehicle_types']) ? 'checked' : 
                        (isset($driverProfile['preferred_vehicle_type']) && $driverProfile['preferred_vehicle_type'] === 'bus' ? 'checked' : ''); ?>>
                <div class="vehicle-card-content">
                    <div class="vehicle-icon bus-icon"></div>
                    <span class="vehicle-name">Λεωφορείο</span>
                </div>
            </label>
            
            <label class="vehicle-type-card">
                <input type="checkbox" name="vehicle_types[]" value="machinery" 
                    <?php echo isset($oldInput['vehicle_types']) && in_array('machinery', $oldInput['vehicle_types']) ? 'checked' : 
                        (isset($driverProfile['preferred_vehicle_type']) && $driverProfile['preferred_vehicle_type'] === 'machinery' ? 'checked' : ''); ?>>
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
        
        <!-- Υποκατηγορίες για φορτηγά (εμφανίζεται μόνο όταν επιλέγεται φορτηγό) -->
        <div class="vehicle-subcategories truck-subcategories" id="truck-subcategories" style="display: none;">
            <h3>Επιλέξτε υποκατηγορίες φορτηγών</h3>
            <div class="checkbox-group horizontal">
                <label>
                    <input type="checkbox" name="truck_types[]" value="light" class="subcategory-checkbox">
                    Ελαφρά Φορτηγά (έως 3.5τ)
                </label>
                <label>
                    <input type="checkbox" name="truck_types[]" value="medium" class="subcategory-checkbox">
                    Μεσαία Φορτηγά (3.5-7.5τ)
                </label>
                <label>
                    <input type="checkbox" name="truck_types[]" value="heavy" class="subcategory-checkbox">
                    Βαρέα Φορτηγά (άνω των 7.5τ)
                </label>
                <label>
                    <input type="checkbox" name="truck_types[]" value="articulated" class="subcategory-checkbox">
                    Αρθρωτά (με ρυμουλκούμενο)
                </label>
                <label>
                    <input type="checkbox" name="truck_types[]" value="tanker" class="subcategory-checkbox">
                    Βυτιοφόρα
                </label>
                <label>
                    <input type="checkbox" name="truck_types[]" value="refrigerated" class="subcategory-checkbox">
                    Ψυγεία
                </label>
            </div>
        </div>
        
        <!-- Υποχρεωτική επιλογή τουλάχιστον ενός τύπου οχήματος -->
        <div id="vehicle-types-error" class="error-message" style="display: none;">
            Πρέπει να επιλέξετε τουλάχιστον έναν τύπο οχήματος.
        </div>
    </div>
</section>
            
            <!-- Ενότητα με διαφορετικό περιεχόμενο ανάλογα με τον ρόλο -->
            <?php if ($isDriver): ?>
            <!-- Προσόντα & Δεξιότητες για Οδηγούς -->
            <section class="form-section">
                <h2>Προσόντα & Δεξιότητες</h2>
                
                <!-- Αντικατάσταση του περιττού πεδίου "Απαιτούμενη Άδεια" με απλή εμφάνιση των αδειών από το προφίλ -->
<div class="form-group">
    <label>Άδειες Οδήγησης</label>
    <?php if (isset($driverProfile) && !empty($driverProfile['license_codes'])): ?>
        <div class="driver-licenses-summary">
            <p>Οι παρακάτω άδειες από το προφίλ σας θα εμφανίζονται στην αγγελία:</p>
            <div class="license-badges">
                <?php foreach (explode(',', $driverProfile['license_codes']) as $category): ?>
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
</div>
                
                <!-- Επιλογή για εμφάνιση προσόντων από το προφίλ -->
                <div class="form-group">
                    <h3>Ειδικές Άδειες και Πιστοποιήσεις από το Προφίλ</h3>
                    
                    <div class="certifications-options">
                        <label>
                            <input type="checkbox" name="show_adr" value="1" checked>
                            Πιστοποιητικό ADR <?php echo (isset($driverProfile['adr_certificate']) && $driverProfile['adr_certificate']) ? '' : '(Δεν έχετε δηλώσει)'; ?>
                        </label>
                        
                        <label>
                            <input type="checkbox" name="show_operator_license" value="1" checked>
                            Άδεια Χειριστή Μηχανημάτων <?php echo (isset($driverProfile['operator_license']) && $driverProfile['operator_license']) ? '' : '(Δεν έχετε δηλώσει)'; ?>
                        </label>
                        
                        <label>
                            <input type="checkbox" name="show_tachograph" value="1" checked>
                            Κάρτα Ταχογράφου <?php echo (isset($driverProfile['tachograph_card']) && $driverProfile['tachograph_card']) ? '' : '(Δεν έχετε δηλώσει)'; ?>
                        </label>
                        
                        <label>
                            <input type="checkbox" name="show_skills" value="1" checked>
                            Δεξιότητες και Ικανότητες
                        </label>
                        
                        <label>
                            <input type="checkbox" name="show_experience" value="1" checked>
                            Προϋπηρεσία
                        </label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="experience_years">Συνολικά Έτη Εμπειρίας</label>
                    <input type="number" id="experience_years" name="experience_years" min="0" 
                           value="<?php echo isset($oldInput['experience_years']) ? htmlspecialchars($oldInput['experience_years']) : 
                               (isset($driverProfile['experience_years']) ? $driverProfile['experience_years'] : ''); ?>">
                </div>
                
                <!-- Επιλογή για εμφάνιση βαθμολογίας -->
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="show_rating" name="show_rating" value="1" checked>
                        Εμφάνιση βαθμολογίας οδηγού στην αγγελία
                    </label>
                </div>
            </section>
            </section>
            
            <?php else: ?>
            <!-- Απαιτήσεις για Επιχειρήσεις -->
            <section class="form-section">
                <h2>Απαιτήσεις</h2>
                
                <div class="form-group">
                    <label for="required_license">Απαιτούμενη Άδεια</label>
                    <input type="text" id="required_license" name="required_license" value="<?php echo isset($oldInput['required_license']) ? htmlspecialchars($oldInput['required_license']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="experience_years">Ελάχιστα Έτη Εμπειρίας</label>
                    <input type="number" id="experience_years" name="experience_years" min="0" value="<?php echo isset($oldInput['experience_years']) ? htmlspecialchars($oldInput['experience_years']) : ''; ?>">
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="adr_certificate" name="adr_certificate" value="1" <?php echo isset($oldInput['adr_certificate']) && $oldInput['adr_certificate'] ? 'checked' : ''; ?>>
                        Απαιτείται Πιστοποιητικό ADR
                    </label>
                </div>
                
                <div class="form-group checkbox-group">
                    <label>
                        <input type="checkbox" id="operator_license" name="operator_license" value="1" <?php echo isset($oldInput['operator_license']) && $oldInput['operator_license'] ? 'checked' : ''; ?>>
                        Απαιτείται Άδεια Χειριστή Μηχανημάτων
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="required_training">Απαιτούμενη Εκπαίδευση</label>
                    <textarea id="required_training" name="required_training" rows="4"><?php echo isset($oldInput['required_training']) ? htmlspecialchars($oldInput['required_training']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="benefits">Παροχές</label>
                    <textarea id="benefits" name="benefits" rows="4"><?php echo isset($oldInput['benefits']) ? htmlspecialchars($oldInput['benefits']) : ''; ?></textarea>
                </div>
            </section>
            <?php endif; ?>
            
            <!-- Επιπλέον Πληροφορίες -->
            <section class="form-section">
                <h2>Επιπλέον Πληροφορίες</h2>
                
                <div class="form-group">
                    <label for="contact_email">Email Επικοινωνίας</label>
                    <input type="email" id="contact_email" name="contact_email" 
                           value="<?php echo isset($oldInput['contact_email']) ? htmlspecialchars($oldInput['contact_email']) : 
                               (isset($driverProfile['email']) ? htmlspecialchars($driverProfile['email']) : ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="contact_phone">Τηλέφωνο Επικοινωνίας</label>
                    <input type="tel" id="contact_phone" name="contact_phone" 
                           value="<?php echo isset($oldInput['contact_phone']) ? htmlspecialchars($oldInput['contact_phone']) : 
                               (isset($driverProfile['phone']) ? htmlspecialchars($driverProfile['phone']) : ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="expires_at">Ημερομηνία Λήξης</label>
                    <input type="date" id="expires_at" name="expires_at" 
                           value="<?php echo isset($oldInput['expires_at']) ? htmlspecialchars($oldInput['expires_at']) : 
                               date('Y-m-d', strtotime('+30 days')); ?>">
                </div>
                
                <div class="form-group">
                    <label>Ετικέτες</label>
                    <div class="tags-container">
                        <?php if (is_array($tags) && !empty($tags)): ?>
                            <?php foreach ($tags as $tag): ?>
                                <div class="tag-item">
                                    <label>
                                        <input type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>" <?php echo isset($oldInput['tags']) && is_array($oldInput['tags']) && in_array($tag['id'], $oldInput['tags']) ? 'checked' : ''; ?>>
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
                <button type="submit" class="btn-primary">Δημιουργία Αγγελίας</button>
                <a href="<?php echo BASE_URL; ?>job-listings" class="btn-secondary">Ακύρωση</a>
            </div>
        </form>
    </div>
</main>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCgZpJWVYyrY0U8U1jBGelEWryur3vIrzc&libraries=places,geometry"></script>
<script>
    // Αυτόματη συμπλήρωση διεύθυνσης με Google Places API και εμφάνιση ακτίνας στο χάρτη
    let map = null;
    let radiusCircle = null;
    
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
            
            // Ενημέρωση του χάρτη
            updateMapPreview();
        });
        
        // Checkbox για χρήση τοποθεσίας από προφίλ
        const useProfileLocation = document.getElementById('use_profile_location');
        if (useProfileLocation) {
            useProfileLocation.addEventListener('change', function() {
                if (this.checked) {
                    // Επαναφορά των δεδομένων του προφίλ
                    locationInput.value = '<?php echo $isDriver && isset($driverProfile['city']) ? $driverProfile['city'] . ', ' . $driverProfile['country'] : ''; ?>';
                    document.getElementById('latitude').value = '<?php echo $isDriver && isset($driverProfile['latitude']) ? $driverProfile['latitude'] : ''; ?>';
                    document.getElementById('longitude').value = '<?php echo $isDriver && isset($driverProfile['longitude']) ? $driverProfile['longitude'] : ''; ?>';
                    
                    // Ενημέρωση του χάρτη
                    updateMapPreview();
                } else {
                    // Καθαρισμός των πεδίων για εισαγωγή νέας τοποθεσίας
                    locationInput.value = '';
                    document.getElementById('latitude').value = '';
                    document.getElementById('longitude').value = '';
                    
                    // Καθαρισμός του χάρτη
                    if (map) {
                        map.setCenter({lat: 37.97918, lng: 23.71632}); // Αθήνα
                        if (radiusCircle) {
                            radiusCircle.setMap(null);
                            radiusCircle = null;
                        }
                    }
                }
            });
        }
        
        // Αρχικοποίηση του χάρτη
        initMapPreview();
        
        // Αρχικοποίηση του slider ακτίνας
        const radiusSlider = document.getElementById('radius-slider');
        if (radiusSlider) {
            radiusSlider.addEventListener('input', function() {
                updateRadius(this.value);
                updateMapPreview();
            });
            
            // Αρχική ενημέρωση του πεδίου ακτίνας
            updateRadius(radiusSlider.value);
        }
        
        // Έλεγχος επιλογής τύπων οχημάτων
        const vehicleTypeCheckboxes = document.querySelectorAll('input[name="vehicle_types[]"]');
        const vehicleTypesError = document.getElementById('vehicle-types-error');
        
        vehicleTypeCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const atLeastOneChecked = Array.from(vehicleTypeCheckboxes).some(cb => cb.checked);
                if (!atLeastOneChecked) {
                    vehicleTypesError.style.display = 'block';
                } else {
                    vehicleTypesError.style.display = 'none';
                }
            });
        });
        
        // Κουμπί προεπισκόπησης
        const previewButton = document.getElementById('preview-button');
        if (previewButton) {
            previewButton.addEventListener('click', function() {
                generateListingPreview();
            });
        }
    }
    
    // Ενημέρωση της τιμής ακτίνας
    function updateRadius(value) {
        document.getElementById('radius-value').textContent = value;
        document.getElementById('radius').value = value;
        
        // Ενημέρωση του κύκλου ακτίνας στο χάρτη
        if (radiusCircle) {
            radiusCircle.setRadius(parseInt(value) * 1000); // Μετατροπή σε μέτρα
        }
    }
    
    // Αρχικοποίηση του χάρτη προεπισκόπησης
    function initMapPreview() {
        const mapPreviewContainer = document.getElementById('map-preview');
        if (!mapPreviewContainer) return;
        
        // Αρχική θέση (Αθήνα αν δεν υπάρχουν συντεταγμένες)
        const lat = parseFloat(document.getElementById('latitude').value) || 37.97918;
        const lng = parseFloat(document.getElementById('longitude').value) || 23.71632;
        const center = {lat: lat, lng: lng};
        
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
        markers.forEach(marker => marker.setMap(null));
        
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
    
    // Παράγει την προεπισκόπηση της αγγελίας
    function generateListingPreview() {
        const previewContainer = document.getElementById('listing-preview');
        if (!previewContainer) return;
        
        // Συλλογή δεδομένων από τη φόρμα
        const title = document.getElementById('title').value;
        const description = document.getElementById('description').value;
        const jobType = document.getElementById('job_type').value;
        const jobTypeText = document.getElementById('job_type').options[document.getElementById('job_type').selectedIndex].text;
        
        // Τύποι οχημάτων
        const vehicleTypes = [];
        document.querySelectorAll('input[name="vehicle_types[]"]:checked').forEach(checkbox => {
            vehicleTypes.push(checkbox.parentNode.querySelector('span').textContent);
        });
        
        // Ωράριο
        const scheduleTypes = [];
        document.querySelectorAll('input[name="preferred_schedule[]"]:checked').forEach(checkbox => {
            scheduleTypes.push(checkbox.parentNode.textContent.trim());
        });
        
        // Ημέρες απουσίας
        const maxDaysAway = document.getElementById('max_days_away');
        const maxDaysAwayText = maxDaysAway ? maxDaysAway.options[maxDaysAway.selectedIndex].text : '';
        
        // Μισθός
        const salaryMin = document.getElementById('salary_min').value;
        const salaryMax = document.getElementById('salary_max').value;
        const salaryType = document.getElementById('salary_type');
        const salaryTypeText = salaryType ? salaryType.options[salaryType.selectedIndex].text : '';
        
        // Τοποθεσία
        const location = document.getElementById('location').value;
        const radius = document.getElementById('radius').value;
        
        // Ειδικές άδειες
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
                    <span class="listing-type-badge">${<?php echo $isDriver ? "'Αναζήτηση Εργασίας'" : "'Προσφορά Εργασίας'"; ?>}</span>
                </div>
                
                <div class="listing-details">
                    <div class="listing-detail">
                        <strong>Τοποθεσία:</strong> ${location} (ακτίνα: ${radius} χλμ)
                    </div>
                    
                    <div class="listing-detail">
                        <strong>Τύποι Οχημάτων:</strong> ${vehicleTypes.join(', ')}
                    </div>
                    
                    ${scheduleTypes.length > 0 ? 
                      `<div class="listing-detail">
                          <strong>Προτιμώμενο Ωράριο:</strong> ${scheduleTypes.join(', ')}
                       </div>` : ''}
                    
                    ${maxDaysAwayText ? 
                      `<div class="listing-detail">
                          <strong>Μέγιστη διάρκεια απουσίας:</strong> ${maxDaysAwayText}
                       </div>` : ''}
                    
                    ${(salaryMin || salaryMax) ? 
                      `<div class="listing-detail">
                          <strong>Αμοιβή:</strong> 
                          ${salaryMin && salaryMax ? `${salaryMin}€ - ${salaryMax}€` : 
                            (salaryMin ? `Από ${salaryMin}€` : 
                             (salaryMax ? `Έως ${salaryMax}€` : ''))}
                          ${salaryTypeText ? ` / ${salaryTypeText}` : ''}
                       </div>` : ''}
                    
                    ${showRating ? 
                      `<div class="listing-detail driver-rating">
                          <strong>Βαθμολογία Οδηγού:</strong> 
                          <div class="stars">★★★★☆</div> 4.0/5
                       </div>` : ''}
                </div>
                
                <div class="listing-description">
                    <h4>Περιγραφή</h4>
                    <p>${description.replace(/\n/g, '<br>')}</p>
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
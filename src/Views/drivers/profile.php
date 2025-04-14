<?php 
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php'; 
?>
<!-- Σύνδεση με το CSS αρχείο του προφίλ οδηγού -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/driver_profile.css">
<!-- Μετά το link του CSS και πριν το </head> -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCgZpJWVYyrY0U8U1jBGelEWryur3vIrzc&libraries=places"></script>

<main>
    <div class="container">
        <!-- Επικεφαλίδα προφίλ με βασικές πληροφορίες -->
        <div class="profile-header">
            <div class="profile-image">
                <?php if (isset($driverData['profile_image']) && $driverData['profile_image']): ?>
                    <img src="<?php echo BASE_URL . htmlspecialchars($driverData['profile_image']); ?>" alt="Εικόνα προφίλ">
                <?php else: ?>
                    <img src="<?php echo BASE_URL; ?>img/default_profile.png" alt="Προεπιλεγμένη εικόνα προφίλ">
                <?php endif; ?>
            </div>
            
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($driverData['first_name'] . ' ' . $driverData['last_name']); ?></h1>
                
                <?php if (isset($driverData['city']) && $driverData['city']): ?>
                    <p class="profile-location">
                        <img src="<?php echo BASE_URL; ?>img/location_icon.png" alt="Τοποθεσία">
                        <?php echo htmlspecialchars($driverData['city'] . ', ' . $driverData['country']); ?>
                    </p>
                <?php endif; ?>
                
                <div class="driver-rating">
                    <div class="rating-stars">
                        <?php 
                        $rating = isset($driverData['rating']) ? floatval($driverData['rating']) : 0;
                        $fullStars = floor($rating);
                        $halfStar = $rating - $fullStars >= 0.5;
                        $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                        
                        for ($i = 0; $i < $fullStars; $i++): ?>
                            <i class="star full"></i>
                        <?php endfor; ?>
                        
                        <?php if ($halfStar): ?>
                            <i class="star half"></i>
                        <?php endif; ?>
                        
                        <?php for ($i = 0; $i < $emptyStars; $i++): ?>
                            <i class="star empty"></i>
                        <?php endfor; ?>
                        
                        <span class="rating-value"><?php echo number_format($rating, 1); ?></span>
                    </div>
                    <span class="rating-count">(<?php echo $driverData['rating_count'] ?? 0; ?> αξιολογήσεις)</span>
                </div>
                
                <?php if (isset($driverData['experience_years']) && $driverData['experience_years']): ?>
                    <div class="experience-badge">
                        <img src="<?php echo BASE_URL; ?>img/experience_icon.png" alt="Εμπειρία">
                        <span><?php echo $driverData['experience_years']; ?> έτη εμπειρίας</span>
                    </div>
                <?php endif; ?>
                
                <div class="profile-actions">
                    <a href="<?php echo BASE_URL; ?>drivers/edit-profile" class="btn-primary">Επεξεργασία Προφίλ</a>
                    <?php if (isset($driverData['resume_file']) && $driverData['resume_file']): ?>
                        <a href="<?php echo BASE_URL . htmlspecialchars($driverData['resume_file']); ?>" class="btn-secondary" target="_blank">Προβολή Βιογραφικού</a>
                    <?php endif; ?>
                </div>
            </div>
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
        
        <!-- Καρτέλες (tabs) με περιεχόμενο προφίλ -->
        <div class="profile-tabs">
            <nav class="tabs-nav">
                <button class="tab-btn active" data-tab="overview">Επισκόπηση</button>
                <button class="tab-btn" data-tab="qualifications">Προσόντα & Πιστοποιήσεις</button>
                <button class="tab-btn" data-tab="job-matches">Ταιριάσματα Εργασίας</button>
                <button class="tab-btn" data-tab="self-assessment">Αυτοαξιολόγηση</button>
                <button class="tab-btn" data-tab="my-listings">Αγγελίες</button>
            </nav>
            
            <div class="tab-content">
                <!-- Καρτέλα Επισκόπησης -->
                <div class="tab-pane active" id="overview">
                    <div class="profile-content">
                        <div class="profile-main">
                            <section class="profile-section">
                                <h2>Σχετικά με εμένα</h2>
                                                            <div class="profile-about">
                                    <?php if (isset($driverData['about_me']) && $driverData['about_me']): ?>
                                        <?php echo nl2br(htmlspecialchars($driverData['about_me'])); ?>
                                    <?php else: ?>
                                        <p class="profile-empty">Δεν έχετε προσθέσει πληροφορίες για τον εαυτό σας. <a href="<?php echo BASE_URL; ?>drivers/edit-profile">Προσθέστε τώρα!</a></p>
                                    <?php endif; ?>
                                </div>
                            </section>
                            
                            <!-- Σύνοψη προσόντων -->
                            <section class="profile-section">
                                <h2>Βασικά προσόντα</h2>
                                <div class="qualifications-summary">
                                    <div class="qualification-badges">
                                        <?php if (isset($driverData['driving_license']) && $driverData['driving_license']): ?>
                                            <div class="badge badge-active" title="Άδεια οδήγησης: <?php echo htmlspecialchars($driverData['driving_license']); ?>">
                                                <img src="<?php echo BASE_URL; ?>img/license_icon.png" alt="Άδεια Οδήγησης">
                                                <span>Άδεια οδήγησης</span>
                                            </div>
                                        <?php else: ?>
                                            <div class="badge badge-inactive" title="Δεν έχει καταχωρηθεί άδεια οδήγησης">
                                                <img src="<?php echo BASE_URL; ?>img/license_icon.png" alt="Άδεια Οδήγησης">
                                                <span>Άδεια οδήγησης</span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($driverData['adr_certificate']): ?>
                                            <div class="badge badge-active" title="Πιστοποιητικό ADR">
                                                <img src="<?php echo BASE_URL; ?>img/adr_icon.png" alt="Πιστοποιητικό ADR">
                                                <span>ADR</span>
                                            </div>
                                        <?php else: ?>
                                            <div class="badge badge-inactive" title="Δεν διαθέτει πιστοποιητικό ADR">
                                                <img src="<?php echo BASE_URL; ?>img/adr_icon.png" alt="Πιστοποιητικό ADR">
                                                <span>ADR</span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($driverData['operator_license']): ?>
                                            <div class="badge badge-active" title="Άδεια χειριστή μηχανημάτων">
                                                <img src="<?php echo BASE_URL; ?>img/operator_icon.png" alt="Άδεια Χειριστή">
                                                <span>Άδεια χειριστή</span>
                                            </div>
                                        <?php else: ?>
                                            <div class="badge badge-inactive" title="Δεν διαθέτει άδεια χειριστή μηχανημάτων">
                                                <img src="<?php echo BASE_URL; ?>img/operator_icon.png" alt="Άδεια Χειριστή">
                                                <span>Άδεια χειριστή</span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($driverData['training_seminars']): ?>
                                            <div class="badge badge-active" title="Συμμετοχή σε σεμινάρια κατάρτισης">
                                                <img src="<?php echo BASE_URL; ?>img/training_icon.png" alt="Σεμινάρια">
                                                <span>Σεμινάρια</span>
                                            </div>
                                        <?php else: ?>
                                            <div class="badge badge-inactive" title="Δεν έχει παρακολουθήσει σεμινάρια κατάρτισης">
                                                <img src="<?php echo BASE_URL; ?>img/training_icon.png" alt="Σεμινάρια">
                                                <span>Σεμινάρια</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </section>
                        </div>
                        <!-- Συστατικό προβολής άδειας οδήγησης για το προφίλ οδηγού (profile.php) -->
<div class="profile-section license-section">
    <h2>Άδεια Οδήγησης</h2>
    
    <?php if (isset($driverData['license_number']) && $driverData['license_number']): ?>
        <!-- Βασικές πληροφορίες άδειας -->
        <div class="license-info">
            <div class="license-header">
                <div class="license-number">
                    <span class="label">Αριθμός Άδειας:</span>
                    <span class="value"><?php echo htmlspecialchars($driverData['license_number']); ?></span>
                </div>
                
                <?php if (!empty($licenseDocumentExpiry)): ?>
                    <div class="license-expiry">
                        <span class="label">Ημερομηνία Λήξης Εντύπου:</span>
                        <span class="value <?php echo (strtotime($licenseDocumentExpiry) < time()) ? 'expired' : ((strtotime($licenseDocumentExpiry) - time()) < 60*60*24*90 ? 'expiring-soon' : ''); ?>">
                            <?php echo date('d/m/Y', strtotime($licenseDocumentExpiry)); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Προβολή των κατηγοριών άδειας οδήγησης -->
        <div class="license-categories">
            <h3>Κατηγορίες</h3>
            <?php if (count($driverLicenseTypes) > 0): ?>
                <?php if (isset($driverLicenseTypes) && count($driverLicenseTypes) > 0): ?>
    <!-- Κώδικας που χρησιμοποιεί το $driverLicenseTypes -->
<?php else: ?>
    <p class="no-categories">Δεν έχουν καταχωρηθεί κατηγορίες αδειών οδήγησης.</p>
<?php endif; ?>
            
                <div class="license-categories-grid">
                    <?php 
                    // Ομαδοποίηση κατηγοριών
                    $categoryGroups = [
                        'Δίκυκλα' => ['AM', 'A1', 'A2', 'A'],
                        'Επιβατικά' => ['B', 'BE'],
                        'Φορτηγά' => ['C1', 'C1E', 'C', 'CE'],
                        'Λεωφορεία' => ['D1', 'D1E', 'D', 'DE']
                    ];
                    
                    // Αντιστοίχιση περιγραφών
                    $categoryDescriptions = [
                        'AM' => 'Μοτοποδήλατα',
                        'A1' => 'Μοτοσυκλέτες έως 125 cc',
                        'A2' => 'Μοτοσυκλέτες έως 35 kW',
                        'A' => 'Μοτοσυκλέτες',
                        'B' => 'Επιβατικά',
                        'BE' => 'Επιβατικά με ρυμουλκούμενο',
                        'C1' => 'Φορτηγά < 7.5t',
                        'C1E' => 'Φορτηγά < 7.5t με ρυμουλκούμενο',
                        'C' => 'Φορτηγά',
                        'CE' => 'Φορτηγά με ρυμουλκούμενο',
                        'D1' => 'Μικρά λεωφορεία',
                        'D1E' => 'Μικρά λεωφορεία με ρυμουλκούμενο',
                        'D' => 'Λεωφορεία',
                        'DE' => 'Λεωφορεία με ρυμουλκούμενο'
                    ];
                    
                    // Προβολή ανά ομάδα κατηγοριών
                    foreach ($categoryGroups as $groupName => $categories):
                        $hasCategories = false;
                        foreach ($categories as $category) {
                            if (in_array($category, $driverLicenseTypes)) {
                                $hasCategories = true;
                                break;
                            }
                        }
                        
                        if ($hasCategories):
                    ?>
                        <div class="license-category-group">
                            <h4><?php echo $groupName; ?></h4>
                            <div class="license-category-items">
                                <?php foreach ($categories as $category): 
                                    if (in_array($category, $driverLicenseTypes)):
                                        // Λήψη της ημερομηνίας λήξης για την κατηγορία
                                        $expiryDate = null;
                                        $hasPei = false;
                                        
                                        foreach ($driverLicenses as $license) {
                                            if ($license['license_type'] === $category) {
                                                $expiryDate = $license['expiry_date'];
                                                $hasPei = ($license['has_pei'] == 1);
                                                break;
                                            }
                                        }
                                ?>
                                    <div class="license-category-item">
                                        <div class="category-icon">
                                            <img src="<?php echo BASE_URL; ?>img/license_icons/<?php echo strtolower($category); ?>.png" alt="<?php echo $category; ?>">
                                            <span class="category-code"><?php echo $category; ?></span>
                                        </div>
                                        <div class="category-details">
                                            <span class="category-name"><?php echo $categoryDescriptions[$category] ?? $category; ?></span>
                                            <?php if ($expiryDate): ?>
                                                <span class="category-expiry <?php echo (strtotime($expiryDate) < time()) ? 'expired' : ((strtotime($expiryDate) - time()) < 60*60*24*90 ? 'expiring-soon' : ''); ?>">
                                                    Λήξη: <?php echo date('d/m/Y', strtotime($expiryDate)); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($hasPei): ?>
                                                <span class="category-pei">ΠΕΙ</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; endforeach; ?>
                            </div>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
                
                <!-- Πληροφορίες ΠΕΙ -->
                <?php 
                $hasPeiC = false;
                $hasPeiD = false;
                $peiCExpiry = null;
                $peiDExpiry = null;
                
                foreach ($driverLicenses as $license) {
                    if ($license['has_pei'] == 1) {
                        if (in_array($license['license_type'], ['C', 'CE', 'C1', 'C1E'])) {
                            $hasPeiC = true;
                            if (!empty($license['pei_expiry_c']) && (empty($peiCExpiry) || strtotime($license['pei_expiry_c']) > strtotime($peiCExpiry))) {
                                $peiCExpiry = $license['pei_expiry_c'];
                            }
                        } else if (in_array($license['license_type'], ['D', 'DE', 'D1', 'D1E'])) {
                            $hasPeiD = true;
                            if (!empty($license['pei_expiry_d']) && (empty($peiDExpiry) || strtotime($license['pei_expiry_d']) > strtotime($peiDExpiry))) {
                                $peiDExpiry = $license['pei_expiry_d'];
                            }
                        }
                    }
                }
                
                if ($hasPeiC || $hasPeiD):
                ?>
              
    <?php if (isset($hasPeiC) && $hasPeiC || isset($hasPeiD) && $hasPeiD): ?>
    <div class="pei-info">
        <h3>Πιστοποιητικό Επαγγελματικής Ικανότητας (ΠΕΙ)</h3>
        <div class="pei-details">
            <?php if (isset($hasPeiC) && $hasPeiC): ?>
                <div class="pei-item">
                    <span class="pei-type">ΠΕΙ Εμπορευμάτων</span>
                    <?php if (isset($peiCExpiryDate) && $peiCExpiryDate): ?>
                        <span class="pei-expiry <?php echo (strtotime($peiCExpiryDate) < time()) ? 'expired' : ((strtotime($peiCExpiryDate) - time()) < 60*60*24*90 ? 'expiring-soon' : ''); ?>">
                            Λήξη: <?php echo date('d/m/Y', strtotime($peiCExpiryDate)); ?>
                        </span>
                    <?php else: ?>
                        <span class="pei-expiry">Δεν έχει καταχωρηθεί ημερομηνία λήξης</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($hasPeiD) && $hasPeiD): ?>
                <div class="pei-item">
                    <span class="pei-type">ΠΕΙ Επιβατών</span>
                    <?php if (isset($peiDExpiryDate) && $peiDExpiryDate): ?>
                        <span class="pei-expiry <?php echo (strtotime($peiDExpiryDate) < time()) ? 'expired' : ((strtotime($peiDExpiryDate) - time()) < 60*60*24*90 ? 'expiring-soon' : ''); ?>">
                            Λήξη: <?php echo date('d/m/Y', strtotime($peiDExpiryDate)); ?>
                        </span>
                    <?php else: ?>
                        <span class="pei-expiry">Δεν έχει καταχωρηθεί ημερομηνία λήξης</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="no-categories">Δεν έχουν καταχωρηθεί κατηγορίες αδειών οδήγησης.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <p class="profile-empty">Δεν έχετε καταχωρήσει στοιχεία άδειας οδήγησης. <a href="<?php echo BASE_URL; ?>drivers/edit-profile#driving-licenses">Προσθέστε τώρα!</a></p>
    <?php endif; ?>
</div>
                        
                        <div class="profile-sidebar">
                            <section class="profile-section">
                                <h2>Στοιχεία Επικοινωνίας</h2>
                                <ul class="contact-list">
                                    <li>
                                        <img src="<?php echo BASE_URL; ?>img/email_icon.png" alt="Email">
                                        <span><?php echo htmlspecialchars($driverData['email']); ?></span>
                                    </li>
                                    <li>
                                        <img src="<?php echo BASE_URL; ?>img/phone_icon.png" alt="Τηλέφωνο">
                                        <span><?php echo htmlspecialchars($driverData['phone']); ?></span>
                                    </li>
                                    <?php if (isset($driverData['landline']) && $driverData['landline']): ?>
                                        <li>
                                            <img src="<?php echo BASE_URL; ?>img/landline_icon.png" alt="Σταθερό Τηλέφωνο">
                                            <span><?php echo htmlspecialchars($driverData['landline']); ?></span>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (isset($driverData['social_linkedin']) && $driverData['social_linkedin']): ?>
                                        <li>
                                            <img src="<?php echo BASE_URL; ?>img/linkedin_icon.png" alt="LinkedIn">
                                            <a href="<?php echo htmlspecialchars($driverData['social_linkedin']); ?>" target="_blank">LinkedIn Προφίλ</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </section>
                            
                            <section class="profile-section">
                                <h2>Προτιμήσεις Εργασίας</h2>
                                <ul class="preferences-list">
                                    <li>
                                        <strong>Διαθεσιμότητα:</strong>
                                        <span class="availability-status <?php echo $driverData['available_for_work'] ? 'available' : 'unavailable'; ?>">
                                            <?php echo $driverData['available_for_work'] ? 'Διαθέσιμος/η για εργασία' : 'Μη διαθέσιμος/η για εργασία'; ?>
                                        </span>
                                    </li>
                                    <?php if (isset($driverData['preferred_job_type']) && $driverData['preferred_job_type']): ?>
                                        <li>
                                            <strong>Προτιμώμενος τύπος εργασίας:</strong>
                                            <span><?php echo htmlspecialchars($driverData['preferred_job_type']); ?></span>
                                        </li>
                                    <?php endif; ?>
                                    <?php if (isset($driverData['preferred_location']) && $driverData['preferred_location']): ?>
                                        <li>
                                            <strong>Προτιμώμενη τοποθεσία:</strong>
                                            <span><?php echo htmlspecialchars($driverData['preferred_location']); ?></span>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </section>
                            
                            <?php if (isset($driverData['address']) && $driverData['address'] && isset($driverData['city']) && $driverData['city']): ?>
                                <section class="profile-section">
                                    <h2>Τοποθεσία</h2>
                                    <div class="profile-map">
                                        <iframe
                                            width="100%"
                                            height="200"
                                            frameborder="0"
                                            scrolling="no"
                                            marginheight="0"
                                            marginwidth="0"
                                            src="https://maps.google.com/maps?q=<?php echo urlencode($driverData['address'] . ', ' . $driverData['city'] . ', ' . $driverData['country']); ?>&output=embed"
                                        ></iframe>
                                    </div>
                                </section>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Καρτέλα Προσόντων & Πιστοποιήσεων -->
                <div class="tab-pane" id="qualifications">
                    <h2>Προσόντα & Πιστοποιήσεις</h2>
                    <div class="profile-qualifications">
                        <div class="qualification-item">
                            <div class="qualification-icon">
                                <img src="<?php echo BASE_URL; ?>img/experience_icon.png" alt="Εμπειρία">
                            </div>
                            <div class="qualification-info">
                                <h3>Έτη Εμπειρίας</h3>
                                <?php if (isset($driverData['experience_years']) && $driverData['experience_years']): ?>
                                    <p><?php echo $driverData['experience_years']; ?> έτη</p>
                                <?php else: ?>
                                    <p class="profile-empty">Δεν έχει καταχωρηθεί</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="qualification-item">
                            <div class="qualification-icon">
                                <img src="<?php echo BASE_URL; ?>img/license_icon.png" alt="Άδεια Οδήγησης">
                            </div>
                            <div class="qualification-info">
                                <h3>Άδεια Οδήγησης</h3>
                                <?php if (isset($driverData['driving_license']) && $driverData['driving_license']): ?>
                                    <p><?php echo htmlspecialchars($driverData['driving_license']); ?></p>
                                    <?php if (isset($driverData['driving_license_expiry']) && $driverData['driving_license_expiry']): ?>
                                        <p class="expiry-date">Λήξη: <?php echo date('d/m/Y', strtotime($driverData['driving_license_expiry'])); ?></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="profile-empty">Δεν έχει καταχωρηθεί</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="qualification-item">
                            <div class="qualification-icon">
                                <img src="<?php echo BASE_URL; ?>img/adr_icon.png" alt="Πιστοποιητικό ADR">
                            </div>
                            <div class="qualification-info">
                                <h3>Πιστοποιητικό ADR</h3>
                                <?php if ($driverData['adr_certificate']): ?>
                                    <p>Ναι</p>
                                    <?php if (isset($driverData['adr_certificate_expiry']) && $driverData['adr_certificate_expiry']): ?>
                                        <p class="expiry-date">Λήξη: <?php echo date('d/m/Y', strtotime($driverData['adr_certificate_expiry'])); ?></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p>Όχι</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="qualification-item">
                            <div class="qualification-icon">
                                <img src="<?php echo BASE_URL; ?>img/operator_icon.png" alt="Άδεια Χειριστή Μηχανημάτων">
                            </div>
                            <div class="qualification-info">
                                <h3>Άδεια Χειριστή Μηχανημάτων</h3>
                                <?php if ($driverData['operator_license']): ?>
                                    <p>Ναι</p>
                                    <?php if (isset($driverData['operator_license_expiry']) && $driverData['operator_license_expiry']): ?>
                                        <p class="expiry-date">Λήξη: <?php echo date('d/m/Y', strtotime($driverData['operator_license_expiry'])); ?></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p>Όχι</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="qualification-item">
                            <div class="qualification-icon">
                                <img src="<?php echo BASE_URL; ?>img/training_icon.png" alt="Σεμινάρια Κατάρτισης">
                            </div>
                            <div class="qualification-info">
                                <h3>Σεμινάρια Κατάρτισης</h3>
                                <?php if ($driverData['training_seminars']): ?>
                                    <p>Ναι</p>
                                    <?php if (isset($driverData['training_details']) && $driverData['training_details']): ?>
                                        <div class="training-details">
                                            <?php echo nl2br(htmlspecialchars($driverData['training_details'])); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p>Όχι</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Καρτέλα Ταιριασμάτων Εργασίας -->
                <div class="tab-pane" id="job-matches">
                    <h2>Προτεινόμενες Αγγελίες Εργασίας</h2>
                    
                    <div class="job-matches-container">
                        <div class="job-matches-map">
                            <h3>Αγγελίες Εργασίας στην περιοχή σας</h3>
                            <!-- Αλλαγή στο div του χάρτη για να προσθέσουμε data attributes -->
<div class="map-container">
<div id="jobMatchesMap" style="width: 100%; height: 400px;" 
    data-lat="<?php echo isset($driverLocation) && isset($driverLocation['lat']) ? $driverLocation['lat'] : 40.6401; ?>" 
    data-lng="<?php echo isset($driverLocation) && isset($driverLocation['lng']) ? $driverLocation['lng'] : 22.9444; ?>">
</div>
</div>
                            <div class="map-options">
                                <label for="searchRadius">Ακτίνα αναζήτησης:</label>
                                <select id="searchRadius" name="searchRadius">
                                    <option value="5">5 χλμ</option>
                                    <option value="10" selected>10 χλμ</option>
                                    <option value="20">20 χλμ</option>
                                    <option value="50">50 χλμ</option>
                                    <option value="100">100 χλμ</option>
                                </select>
                                
                                <button id="refreshJobMatches" class="btn-primary">Ανανέωση</button>
                            </div>
                        </div>
                        
                        <div class="job-matches-list">
                            <h3>Προτεινόμενες θέσεις εργασίας</h3>
                            <div id="matchedJobsList">
                                <p class="loading-message">Φόρτωση προτεινόμενων θέσεων εργασίας...</p>
                                <!-- Τα ταιριάσματα θα φορτωθούν εδώ με JavaScript -->
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Καρτέλα Αυτοαξιολόγησης -->
                <div class="tab-pane" id="self-assessment">
                    <h2>Αυτοαξιολόγηση Οδηγού</h2>
                    
                    <div class="assessment-container">
                        <div class="assessment-intro">
                            <p>Αξιολογήστε τις οδηγικές σας ικανότητες και δείτε την συνολική βαθμολογία σας ως επαγγελματίας οδηγός. Οι εργοδότες μπορούν να δουν αυτή την αξιολόγηση.</p>
                        </div>
                        
                        <div class="driver-score-summary">
                            <div class="score-circle">
                                <svg viewBox="0 0 100 100">
                                    <circle class="score-background" cx="50" cy="50" r="45"></circle>
                                    <circle class="score-progress" cx="50" cy="50" r="45" style="stroke-dashoffset: calc(283.5 - (283.5 * <?php echo isset($driverAssessment) ? $driverAssessment['total_score'] / 100 : 0; ?>) / 100)"></circle>
                                </svg>
                                <div class="score-text">
                                    <span class="score-value"><?php echo isset($driverAssessment) ? $driverAssessment['total_score'] : 0; ?></span>
                                    <span class="score-label">Βαθμολογία</span>
                                </div>
                            </div>
                            
                            <div class="score-breakdown">
                                <h3>Ανάλυση Βαθμολογίας</h3>
                                <div class="score-categories">
                                    <div class="score-category">
                                        <h4>Οδηγικές Ικανότητες</h4>
                                        <div class="progress-bar">
                                            <div class="progress" style="width: <?php echo isset($driverAssessment) ? $driverAssessment['driving_skills'] : 0; ?>%"></div>
                                        </div>
                                        <span class="category-score"><?php echo isset($driverAssessment) ? $driverAssessment['driving_skills'] : 0; ?>%</span>
                                    </div>
                                    
                                    <div class="score-category">
                                        <h4>Ασφάλεια & Συμμόρφωση</h4>
                                        <div class="progress-bar">
                                            <div class="progress" style="width: <?php echo isset($driverAssessment) ? $driverAssessment['safety_compliance'] : 0; ?>%"></div>
                                        </div>
                                        <span class="category-score"><?php echo isset($driverAssessment) ? $driverAssessment['safety_compliance'] : 0; ?>%</span>
                                    </div>
                                    
                                    <div class="score-category">
                                        <h4>Επαγγελματισμός</h4>
                                        <div class="progress-bar">
                                            <div class="progress" style="width: <?php echo isset($driverAssessment) ? $driverAssessment['professionalism'] : 0; ?>%"></div>
                                        </div>
                                        <span class="category-score"><?php echo isset($driverAssessment) ? $driverAssessment['professionalism'] : 0; ?>%</span>
                                    </div>
                                    
                                    <div class="score-category">
                                        <h4>Τεχνικές Γνώσεις</h4>
                                        <div class="progress-bar">
                                            <div class="progress" style="width: <?php echo isset($driverAssessment) ? $driverAssessment['technical_knowledge'] : 0; ?>%"></div>
                                        </div>
                                        <span class="category-score"><?php echo isset($driverAssessment) ? $driverAssessment['technical_knowledge'] : 0; ?>%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="self-assessment-form">
                            <h3>Ενημέρωση Αυτοαξιολόγησης</h3>
                            <p>Συμπληρώστε την παρακάτω φόρμα για να ενημερώσετε την αυτοαξιολόγησή σας.</p>
                            
                            <form id="assessmentForm" action="<?php echo BASE_URL; ?>drivers/update-assessment" method="POST">
                                <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
                                
                                <div class="assessment-section">
                                    <h4>Οδηγικές Ικανότητες</h4>
                                    
                                    <div class="assessment-question">
                                        <label>Πόσα χρόνια οδηγείτε επαγγελματικά;</label>
                                        <select name="driving_experience" required>
                                            <option value="">Επιλέξτε</option>
                                            <option value="1">Λιγότερο από 1 έτος</option>
                                            <option value="2">1-3 έτη</option>
                                            <option value="3">3-5 έτη</option>
                                            <option value="4">5-10 έτη</option>
                                            <option value="5">Περισσότερο από 10 έτη</option>
                                        </select>
                                    </div>
                                    
                                    <div class="assessment-question">
                                        <label>Πόσα χιλιόμετρα οδηγείτε ετησίως;</label>
                                        <select name="annual_kilometers" required>
                                            <option value="">Επιλέξτε</option>
                                            <option value="1">Λιγότερα από 10.000 χλμ</option>
                                            <option value="2">10.000-30.000 χλμ</option>
                                            <option value="3">30.000-50.000 χλμ</option>
                                            <option value="4">50.000-100.000 χλμ</option>
                                            <option value="5">Περισσότερα από 100.000 χλμ</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Περισσότερες ερωτήσεις για οδηγικές ικανότητες -->
                                </div>
                                
                                <div class="assessment-section">
                                    <h4>Ασφάλεια & Συμμόρφωση</h4>
                                    
                                    <div class="assessment-question">
                                        <label>Πόσα ατυχήματα είχατε τα τελευταία 3 χρόνια;</label>
                                        <select name="accidents" required>
                                            <option value="">Επιλέξτε</option>
                                            <option value="5">Κανένα</option>
                                            <option value="4">1 μικρό ατύχημα</option>
                                            <option value="3">1-2 ατυχήματα</option>
                                            <option value="2">3-4 ατυχήματα</option>
                                            <option value="1">Περισσότερα από 4 ατυχήματα</option>
                                        </select>
                                    </div>
                                    
                                    <div class="assessment-question">
                                        <label>Πόσες παραβάσεις του Κ.Ο.Κ. είχατε τα τελευταία 3 χρόνια;</label>
                                        <select name="traffic_violations" required>
                                            <option value="">Επιλέξτε</option>
                                            <option value="5">Καμία</option>
                                            <option value="4">1-2 μικρές παραβάσεις</option>
                                            <option value="3">3-5 παραβάσεις</option>
                                            <option value="2">6-10 παραβάσεις</option>
                                            <option value="1">Περισσότερες από 10 παραβάσεις</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Περισσότερες ερωτήσεις για ασφάλεια -->
                                </div>
                                
                                <div class="form-actions">
                                    <button type="submit" class="btn-primary">Ενημέρωση Αξιολόγησης</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Καρτέλα Αγγελιών -->
                <div class="tab-pane" id="my-listings">
                    <h2>Οι Αγγελίες μου</h2>
                    
                    <?php if (count($listings['results']) > 0): ?>
                        <div class="profile-listings">
                            <?php foreach ($listings['results'] as $listing): ?>
                                <div class="listing-item">
                                    <h3><a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></h3>
                                    <div class="listing-meta">
                                        <span class="listing-type"><?php echo $listing['listing_type'] === 'job_offer' ? 'Προσφορά Εργασίας' : 'Αναζήτηση Εργασίας'; ?></span>
                                        <span class="listing-date">Δημοσιεύτηκε: <?php echo date('d/m/Y', strtotime($listing['created_at'])); ?></span>
                                    </div>
                                    <p class="listing-status <?php echo $listing['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $listing['is_active'] ? 'Ενεργή' : 'Ανενεργή'; ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="profile-section-footer">
                            <a href="<?php echo BASE_URL; ?>my-listings" class="btn-secondary">Όλες οι αγγελίες μου</a>
                            <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn-primary">Νέα αγγελία</a>
                        </div>
                    <?php else: ?>
                        <p class="profile-empty">Δεν έχετε δημιουργήσει ακόμα αγγελίες. <a href="<?php echo BASE_URL; ?>job-listings/create">Δημιουργήστε την πρώτη σας αγγελία!</a></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<script src="<?php echo BASE_URL; ?>js/driver_profile.js"></script>
</main>

<?php 
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php'; 
?>
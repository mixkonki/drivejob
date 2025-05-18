<?php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/job-listing.css">

<main>
    <div class="container">
        <div class="job-listing-detail-page">
            <!-- Επικεφαλίδα αγγελίας -->
            <div class="job-listing-header">
                <h1><?php echo htmlspecialchars($listing['title']); ?></h1>
                
                <div class="job-listing-meta">
                    <span class="job-type <?php echo $listing['job_type']; ?>">
                        <?php
                        switch ($listing['job_type']) {
                            case 'full_time':
                                echo 'Πλήρης Απασχόληση';
                                break;
                            case 'part_time':
                                echo 'Μερική Απασχόληση';
                                break;
                            case 'contract':
                                echo 'Σύμβαση Έργου';
                                break;
                            case 'temporary':
                                echo 'Προσωρινή Απασχόληση';
                                break;
                        }
                        ?>
                    </span>
                    
                    <span class="listing-type <?php echo $listing['listing_type']; ?>">
                        <?php echo $listing['listing_type'] === 'job_offer' ? 'Προσφορά Εργασίας' : 'Αναζήτηση Εργασίας'; ?>
                    </span>
                </div>
            </div>
            
            <?php if ($listing['driver_id'] && isset($driverRating) && isset($listing['show_rating']) && $listing['show_rating']) : ?>
            <!-- Βαθμολογία Οδηγού -->
            <div class="driver-rating-badge">
                <div class="rating-stars">
                    <?php
                    $rating = isset($driverRating['total_score']) ? floatval($driverRating['total_score']) / 20 : 0; // Μετατροπή από 0-100 σε 0-5
                    $fullStars = floor($rating);
                    $halfStar = $rating - $fullStars >= 0.5;
                    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);

                    for ($i = 0; $i < $fullStars; $i++) : ?>
                        <i class="star full">★</i>
                    <?php endfor; ?>
                    
                    <?php if ($halfStar) : ?>
                        <i class="star half">★</i>
                    <?php endif; ?>
                    
                    <?php for ($i = 0; $i < $emptyStars; $i++) : ?>
                        <i class="star empty">☆</i>
                    <?php endfor; ?>
                </div>
                <span class="rating-value"><?php echo number_format($rating, 1); ?>/5</span>
                <span class="rating-label">Βαθμολογία Οδηγού</span>
            </div>
            <?php endif; ?>
            
            <!-- Κύριο μέρος αγγελίας -->
            <div class="job-listing-content">
                <div class="job-listing-main">
                    <!-- Βασικές πληροφορίες -->
                    <section class="job-section">
                        <h2>Περιγραφή</h2>
                        <div class="job-description">
                            <?php echo nl2br(htmlspecialchars($listing['description'])); ?>
                        </div>
                    </section>
                    
                    <!-- Τύποι Οχημάτων -->
                    <section class="job-section">
                        <h2>Τύποι Οχημάτων</h2>
                        <div class="vehicle-types">
                            <?php
                            // Μετατροπή των κωδικών τύπων οχημάτων σε αναγνώσιμες ετικέτες
                            $vehicleTypeLabels = [
                                'car' => 'Αυτοκίνητο',
                                'van' => 'Βαν',
                                'truck' => 'Φορτηγό',
                                'bus' => 'Λεωφορείο',
                                'machinery' => 'Μηχάνημα Έργου'
                            ];

                            // Αν έχουμε πίνακα vehicle_types, τους εμφανίζουμε
                            if (isset($vehicleTypes) && !empty($vehicleTypes)) :
                                foreach ($vehicleTypes as $type) :
                                    $label = isset($vehicleTypeLabels[$type]) ? $vehicleTypeLabels[$type] : $type;
                                    ?>
                                <div class="vehicle-type-badge <?php echo $type; ?>">
                                    <div class="vehicle-icon"></div>
                                    <?php echo $label; ?>
                                </div>
                                    <?php
                                endforeach;
                            // Αλλιώς, εμφανίζουμε το μοναδικό τύπο οχήματος από το πεδίο vehicle_type
                            elseif (isset($listing['vehicle_type']) && $listing['vehicle_type']) :
                                $type = $listing['vehicle_type'];
                                $label = isset($vehicleTypeLabels[$type]) ? $vehicleTypeLabels[$type] : $type;
                                ?>
                                <div class="vehicle-type-badge <?php echo $type; ?>">
                                    <div class="vehicle-icon"></div>
                                    <?php echo $label; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                    
                    <!-- Απαιτήσεις -->
                    <section class="job-section">
                        <h2>Απαιτήσεις</h2>
                        <ul class="job-requirements">
                            <li>
                                <strong>Απαιτούμενη άδεια:</strong> 
                                <?php echo htmlspecialchars($listing['required_license']); ?>
                            </li>
                            <?php if ($listing['experience_years']) : ?>
                            <li>
                                <strong>Έτη εμπειρίας:</strong> 
                                <?php echo $listing['experience_years']; ?> έτη
                            </li>
                            <?php endif; ?>
                            <?php if ($listing['adr_certificate']) : ?>
                            <li>
                                <strong>Πιστοποιητικό ADR:</strong> Απαιτείται
                            </li>
                            <?php endif; ?>
                            <?php if ($listing['operator_license']) : ?>
                            <li>
                                <strong>Άδεια χειριστή μηχανημάτων:</strong> Απαιτείται
                            </li>
                            <?php endif; ?>
                            <?php if ($listing['required_training']) : ?>
                            <li>
                                <strong>Απαιτούμενη εκπαίδευση:</strong> 
                                <?php echo nl2br(htmlspecialchars($listing['required_training'])); ?>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </section>
                    
                    <!-- Εμφάνιση Ωραρίου και Μέγιστης Διάρκειας Απουσίας (για αγγελίες οδηγών) -->
                    <?php if ($listing['driver_id'] && (!empty($preferredSchedules) || isset($listing['max_days_away']))) : ?>
                    <section class="job-section">
                        <h2>Διαθεσιμότητα & Προτιμήσεις</h2>
                        <div class="driver-availability">
                            <?php if (!empty($preferredSchedules)) : ?>
                            <div class="preferred-schedule">
                                <h3>Προτιμώμενο Ωράριο</h3>
                                <ul class="schedule-list">
                                    <?php
                                    $scheduleLabels = [
                                        'morning' => 'Πρωινό (06:00-14:00)',
                                        'afternoon' => 'Απογευματινό (14:00-22:00)',
                                        'night' => 'Βραδινό (22:00-06:00)',
                                        'shifts' => 'Εναλλασσόμενες Βάρδιες',
                                        'weekend' => 'Σαββατοκύριακα',
                                        'flexible' => 'Ευέλικτο Ωράριο'
                                    ];

                                    foreach ($preferredSchedules as $schedule) :
                                        $label = isset($scheduleLabels[$schedule]) ? $scheduleLabels[$schedule] : $schedule;
                                        ?>
                                    <li><?php echo $label; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($listing['max_days_away'])) : ?>
                            <div class="max-days-away">
                                <h3>Μέγιστη Διάρκεια Απουσίας</h3>
                                <p>
                                    <?php
                                    $maxDays = (int)$listing['max_days_away'];

                                    if ($maxDays === 0) {
                                        echo 'Χωρίς διανυκτέρευση';
                                    } elseif ($maxDays === 1) {
                                        echo '1 ημέρα';
                                    } elseif ($maxDays <= 6) {
                                        echo $maxDays . ' ημέρες';
                                    } elseif ($maxDays === 7) {
                                        echo '1 εβδομάδα';
                                    } elseif ($maxDays === 14) {
                                        echo '2 εβδομάδες';
                                    } elseif ($maxDays === 30) {
                                        echo '1 μήνα';
                                    } elseif ($maxDays === 90) {
                                        echo '3 μήνες';
                                    } elseif ($maxDays === 999) {
                                        echo 'Απεριόριστο';
                                    } else {
                                        echo $maxDays . ' ημέρες';
                                    }
                                    ?>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <!-- Εμφάνιση Πιστοποιήσεων και Αδειών για Οδηγούς -->
                    <?php if (
                    $listing['driver_id'] &&
                             ((isset($listing['show_adr']) && $listing['show_adr'] && isset($driverData['adr_certificate']) && $driverData['adr_certificate']) ||
                              (isset($listing['show_operator_license']) && $listing['show_operator_license'] && isset($driverData['operator_license']) && $driverData['operator_license']) ||
                              (isset($listing['show_tachograph']) && $listing['show_tachograph'] && isset($driverData['tachograph_card']) && $driverData['tachograph_card']))
) : ?>
                    <section class="job-section">
                        <h2>Πιστοποιήσεις & Άδειες</h2>
                        <div class="driver-certifications">
                            <ul class="certification-list">
                                <?php if (isset($listing['show_adr']) && $listing['show_adr'] && isset($driverData['adr_certificate']) && $driverData['adr_certificate']) : ?>
                                <li class="certification-item">
                                    <span class="certification-icon adr-icon"></span>
                                    <div class="certification-details">
                                        <h4>Πιστοποιητικό ADR</h4>
                                        <?php if (isset($driverData['adr_classes']) && $driverData['adr_classes']) : ?>
                                        <p>Κατηγορία: <?php echo htmlspecialchars($driverData['adr_classes']); ?></p>
                                        <?php endif; ?>
                                        <?php if (isset($driverData['adr_certificate_expiry']) && $driverData['adr_certificate_expiry']) : ?>
                                        <p>Λήξη: <?php echo date('d/m/Y', strtotime($driverData['adr_certificate_expiry'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </li>
                                <?php endif; ?>
                                
                                <?php if (isset($listing['show_operator_license']) && $listing['show_operator_license'] && isset($driverData['operator_license']) && $driverData['operator_license']) : ?>
                                <li class="certification-item">
                                    <span class="certification-icon operator-icon"></span>
                                    <div class="certification-details">
                                        <h4>Άδεια Χειριστή Μηχανημάτων</h4>
                                        <?php if (isset($driverData['operator_license_type']) && $driverData['operator_license_type']) : ?>
                                        <p>Τύπος: <?php echo htmlspecialchars($driverData['operator_license_type']); ?></p>
                                        <?php endif; ?>
                                        <?php if (isset($driverData['operator_license_expiry']) && $driverData['operator_license_expiry']) : ?>
                                        <p>Λήξη: <?php echo date('d/m/Y', strtotime($driverData['operator_license_expiry'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </li>
                                <?php endif; ?>
                                
                                <?php if (isset($listing['show_tachograph']) && $listing['show_tachograph'] && isset($driverData['tachograph_card']) && $driverData['tachograph_card']) : ?>
                                <li class="certification-item">
                                    <span class="certification-icon tachograph-icon"></span>
                                    <div class="certification-details">
                                        <h4>Κάρτα Ταχογράφου</h4>
                                        <?php if (isset($driverData['tachograph_card_expiry']) && $driverData['tachograph_card_expiry']) : ?>
                                        <p>Λήξη: <?php echo date('d/m/Y', strtotime($driverData['tachograph_card_expiry'])); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <?php if ($listing['benefits']) : ?>
                    <section class="job-section">
                        <h2>Παροχές</h2>
                        <div class="job-benefits">
                            <?php echo nl2br(htmlspecialchars($listing['benefits'])); ?>
                        </div>
                    </section>
                    <?php endif; ?>
                    
                    <!-- Tags -->
                    <?php if (!empty($tags)) : ?>
                    <section class="job-section">
                        <h2>Ετικέτες</h2>
                        <div class="job-tags">
                            <?php foreach ($tags as $tag) : ?>
                                <span class="job-tag"><?php echo htmlspecialchars($tag['name']); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </section>
                    <?php endif; ?>
                </div>
                
                <div class="job-listing-sidebar">
                    <!-- Πληροφορίες χρήστη/εταιρείας -->
                    <div class="job-sidebar-section">
                        <h3>
                            <?php echo $listing['company_id'] ? 'Στοιχεία Εταιρείας' : 'Στοιχεία Οδηγού'; ?>
                        </h3>
                        <div class="user-info">
                            <?php if ($listing['company_id']) : ?>
                                <h4><?php echo htmlspecialchars($author['company_name']); ?></h4>
                            <?php else : ?>
                                <h4><?php echo htmlspecialchars($author['first_name'] . ' ' . $author['last_name']); ?></h4>
                                <?php if ($listing['driver_id']) : ?>
                                <a href="<?php echo BASE_URL; ?>drivers/profile/<?php echo $listing['driver_id']; ?>" class="view-driver-profile">
                                    Προβολή Πλήρους Προφίλ Οδηγού
                                </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($listing['contact_email']) : ?>
                                <div class="contact-info">
                                    <img src="<?php echo BASE_URL; ?>img/email_icon.png" alt="Email">
                                    <span><?php echo htmlspecialchars($listing['contact_email']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($listing['contact_phone']) : ?>
                                <div class="contact-info">
                                    <img src="<?php echo BASE_URL; ?>img/phone_icon.png" alt="Τηλέφωνο">
                                    <span><?php echo htmlspecialchars($listing['contact_phone']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="contact-actions">
                                <?php if ($listing['listing_type'] === 'job_offer' && isset($_SESSION['role']) && $_SESSION['role'] === 'driver') : ?>
                                    <?php if (!$hasApplied) : ?>
                                    <form action="<?php echo BASE_URL; ?>job-applications/apply/<?php echo $listing['id']; ?>" method="POST">
                                        <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
                                        <button type="submit" class="btn-primary">Υποβολή Αίτησης</button>
                                    </form>
                                    <?php else : ?>
                                    <div class="already-applied">
                                        <img src="<?php echo BASE_URL; ?>img/check_icon.png" alt="Έχετε υποβάλει αίτηση">
                                        <span>Έχετε ήδη υποβάλει αίτηση</span>
                                    </div>
                                    <?php endif; ?>
                                <?php elseif ($listing['listing_type'] === 'job_search' && isset($_SESSION['role']) && $_SESSION['role'] === 'company') : ?>
                                    <a href="<?php echo BASE_URL; ?>messages/create/<?php echo $listing['driver_id']; ?>" class="btn-primary">
                                        <img src="<?php echo BASE_URL; ?>img/message_icon.png" alt="Μήνυμα">
                                        Επικοινωνία με τον Οδηγό
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Βασικές πληροφορίες -->
                    <div class="job-sidebar-section">
                        <h3>Βασικές Πληροφορίες</h3>
                        <ul class="job-details-list">
                            <li>
                                <img src="<?php echo BASE_URL; ?>img/location_icon.png" alt="Τοποθεσία">
                                <span><?php echo htmlspecialchars($listing['location']); ?></span>
                            </li>
                            
                            <?php if ($listing['radius']) : ?>
                            <li>
                                <img src="<?php echo BASE_URL; ?>img/radius_icon.png" alt="Ακτίνα">
                                <span>Ακτίνα: <?php echo $listing['radius']; ?> χλμ</span>
                            </li>
                            <?php endif; ?>
                            
                            <?php if ($listing['salary_min'] || $listing['salary_max']) : ?>
                            <li>
                                <img src="<?php echo BASE_URL; ?>img/salary_icon.png" alt="Αμοιβή">
                                <span>
                                    <?php
                                    if ($listing['salary_min'] && $listing['salary_max']) {
                                        echo number_format($listing['salary_min']) . '€ - ' . number_format($listing['salary_max']) . '€';
                                    } elseif ($listing['salary_min']) {
                                        echo 'Από ' . number_format($listing['salary_min']) . '€';
                                    } elseif ($listing['salary_max']) {
                                        echo 'Έως ' . number_format($listing['salary_max']) . '€';
                                    }

                                    if ($listing['salary_type']) {
                                        echo ' / ';
                                        switch ($listing['salary_type']) {
                                            case 'hourly':
                                                echo 'ώρα';
                                                break;
                                            case 'daily':
                                                echo 'ημέρα';
                                                break;
                                            case 'monthly':
                                                echo 'μήνα';
                                                break;
                                            case 'yearly':
                                                echo 'έτος';
                                                break;
                                        }
                                    }
                                    ?>
                                </span>
                            </li>
                            <?php endif; ?>
                            
                            <li>
                                <img src="<?php echo BASE_URL; ?>img/date_icon.png" alt="Ημερομηνία">
                                <span>Δημοσιεύτηκε: <?php echo date('d/m/Y', strtotime($listing['created_at'])); ?></span>
                            </li>
                            
                            <?php if ($listing['expires_at']) : ?>
                            <li>
                                <img src="<?php echo BASE_URL; ?>img/expiry_icon.png" alt="Λήξη">
                                <span>Λήγει: <?php echo date('d/m/Y', strtotime($listing['expires_at'])); ?></span>
                            </li>
                            <?php endif; ?>
                            
                            <?php if ($listing['remote_possible']) : ?>
                            <li>
                                <img src="<?php echo BASE_URL; ?>img/remote_icon.png" alt="Απομακρυσμένη Εργασία">
                                <span><?php echo $listing['driver_id'] ? 'Διαθέσιμος/η για εργασία εξ αποστάσεως' : 'Δυνατότητα εργασίας από απόσταση'; ?></span>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <!-- Χάρτης τοποθεσίας -->
                    <?php if ($listing['latitude'] && $listing['longitude']) : ?>
                    <div class="job-sidebar-section">
                        <h3>Τοποθεσία</h3>
                        <div class="job-map">
                            <div id="location-map" style="width:100%; height:250px;"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Επιλογές χρήστη -->
            <?php if (
            isset($_SESSION['user_id']) && (
                  ($_SESSION['role'] === 'company' && $listing['company_id'] == $_SESSION['user_id']) ||
                  ($_SESSION['role'] === 'driver' && $listing['driver_id'] == $_SESSION['user_id'])
                )
) : ?>
                <div class="job-listing-actions">
                    <a href="<?php echo BASE_URL; ?>job-listings/edit/<?php echo $listing['id']; ?>" class="btn-secondary">Επεξεργασία</a>
                    <a href="<?php echo BASE_URL; ?>job-listings/delete/<?php echo $listing['id']; ?>" class="btn-danger" onclick="return confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή την αγγελία;')">Διαγραφή</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Παρόμοιες Αγγελίες -->
    <?php if (!empty($similarListings['results'])) : ?>
    <section class="similar-listings">
        <div class="container">
            <h3>Παρόμοιες Αγγελίες</h3>
            <div class="similar-listings-grid">
                <?php foreach ($similarListings['results'] as $similarListing) : ?>
                    <div class="similar-listing-card">
                        <h4><a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $similarListing['id']; ?>"><?php echo htmlspecialchars($similarListing['title']); ?></a></h4>
                        <div class="listing-meta">
                            <span class="job-type-badge <?php echo $similarListing['job_type']; ?>">
                                <?php
                                switch ($similarListing['job_type']) {
                                    case 'full_time':
                                        echo 'Πλήρης Απασχόληση';
                                        break;
                                    case 'part_time':
                                        echo 'Μερική Απασχόληση';
                                        break;
                                    case 'contract':
                                        echo 'Σύμβαση Έργου';
                                        break;
                                    case 'temporary':
                                        echo 'Προσωρινή Απασχόληση';
                                        break;
                                }
                                ?>
                            </span>
                            <span class="location">
                                <img src="<?php echo BASE_URL; ?>img/location_icon.png" alt="Τοποθεσία">
                                <?php echo htmlspecialchars($similarListing['location']); ?>
                            </span>
                        </div>
                        <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $similarListing['id']; ?>" class="btn-secondary btn-sm">Περισσότερα</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>
</main>

<!-- JavaScript για την εμφάνιση του χάρτη -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCgZpJWVYyrY0U8U1jBGelEWryur3vIrzc&callback=initMap" async defer></script>
<script>
    function initMap() {
        <?php if ($listing['latitude'] && $listing['longitude']) : ?>
        // Δημιουργία του χάρτη
        const center = {
            lat: <?php echo $listing['latitude']; ?>, 
            lng: <?php echo $listing['longitude']; ?>
        };
        
        const map = new google.maps.Map(document.getElementById('location-map'), {
            zoom: 12,
            center: center,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false
        });
        
        // Προσθήκη δείκτη για την τοποθεσία
        const marker = new google.maps.Marker({
            position: center,
            map: map,
            title: '<?php echo addslashes(htmlspecialchars($listing['location'])); ?>'
        });
        
        // Προσθήκη κύκλου για την ακτίνα αναζήτησης
            <?php if ($listing['radius']) : ?>
        const radiusCircle = new google.maps.Circle({
            map: map,
            center: center,
            radius: <?php echo $listing['radius']; ?> * 1000, // Μετατροπή σε μέτρα
            fillColor: '#4285F4',
            fillOpacity: 0.2,
            strokeColor: '#4285F4',
            strokeOpacity: 0.5,
            strokeWeight: 1
        });
        
        // Προσαρμογή του zoom ώστε να φαίνεται όλος ο κύκλος
        const bounds = radiusCircle.getBounds();
        map.fitBounds(bounds);
            <?php endif; ?>
        <?php endif; ?>
    }
</script>

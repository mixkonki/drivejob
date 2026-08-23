<?php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<?= \Drivejob\Helpers\Asset::css('css/driver-profile.css') ?>

<main>
    <div class="container">
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

        <!-- Κάρτα Προφίλ Οδηγού -->
        <div class="driver-profile-header">
            <div class="driver-info">
                <div class="driver-photo-container">
                    <?php if (isset($driverData['profile_image']) && $driverData['profile_image']) : ?>
                        <img src="<?php echo BASE_URL . htmlspecialchars($driverData['profile_image']); ?>" alt="Φωτογραφία προφίλ" class="driver-photo">
                    <?php else : ?>
                        <img src="<?= \Drivejob\Helpers\Asset::url('img/default_profile.png') ?>" alt="Προεπιλεγμένη φωτογραφία" class="driver-photo">
                    <?php endif; ?>
                </div>
                <div class="driver-details">
                    <h1><?php echo htmlspecialchars($driverData['first_name'] . ' ' . $driverData['last_name']); ?></h1>

                    <div class="driver-meta">
                        <?php if (isset($driverData['city']) && $driverData['city']) : ?>
                            <div class="driver-location">
                                <img src="<?= \Drivejob\Helpers\Asset::url('img/location_icon.png') ?>" alt="Τοποθεσία">
                                <span><?php echo htmlspecialchars($driverData['city'] . ', ' . $driverData['country']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($driverData['experience_years']) && $driverData['experience_years']) : ?>
                            <div class="driver-experience">
                                <img src="<?= \Drivejob\Helpers\Asset::url('img/experience_icon.png') ?>" alt="Εμπειρία">
                                <span><?php echo $driverData['experience_years']; ?> έτη εμπειρίας</span>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($averageRating) && $averageRating > 0) : ?>
                            <div class="driver-rating">
                                <img src="<?= \Drivejob\Helpers\Asset::url('img/rating_icon.png') ?>" alt="Αξιολόγηση">
                                <div class="stars">
                                    <?php
                                    $rating = round($averageRating * 2) / 2; // Στρογγυλοποίηση στο πλησιέστερο 0.5
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $rating) {
                                            echo '★'; // Πλήρες αστέρι
                                        } elseif ($i - 0.5 == $rating) {
                                            echo '⯨'; // Μισό αστέρι
                                        } else {
                                            echo '☆'; // Κενό αστέρι
                                        }
                                    }
                                    ?>
                                </div>
                                <span><?php echo number_format($averageRating, 1); ?>/5</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($driverData['available_for_work'])) : ?>
                        <div class="driver-availability <?php echo $driverData['available_for_work'] ? 'available' : 'not-available'; ?>">
                            <?php echo $driverData['available_for_work'] ? 'Διαθέσιμος/η για εργασία' : 'Μη διαθέσιμος/η για εργασία'; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="driver-actions">
                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'company') : ?>
                    <a href="<?php echo BASE_URL; ?>messages/create/<?php echo $driverData['id']; ?>" class="btn-primary">
                        <img src="<?= \Drivejob\Helpers\Asset::url('img/message_icon.png') ?>" alt="Μήνυμα">
                        Επικοινωνία
                    </a>

                    <?php if (isset($driverData['cv_file']) && $driverData['cv_file']) : ?>
                        <a href="<?php echo BASE_URL . htmlspecialchars($driverData['cv_file']); ?>" class="btn-secondary" target="_blank">
                            <img src="<?= \Drivejob\Helpers\Asset::url('img/download_icon.png') ?>" alt="Κατέβασμα">
                            Κατέβασμα Βιογραφικού
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Περιεχόμενο Προφίλ -->
        <div class="profile-content">
            <!-- Αριστερή Στήλη -->
            <div class="profile-main">
                <!-- Περίληψη / Σχετικά με εμένα -->
                <?php if (isset($driverData['about_me']) && $driverData['about_me']) : ?>
                    <section class="profile-section">
                        <h2>Σχετικά με εμένα</h2>
                        <div class="about-me">
                            <?php echo nl2br(htmlspecialchars($driverData['about_me'])); ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Δεξιότητες Οδηγού - διορθωμένη έκδοση -->
                <?php if (isset($driverSkills) && is_array($driverSkills) && !empty($driverSkills)) : ?>
                    <section class="profile-section">
                        <h2>Δεξιότητες</h2>
                        <div class="driver-skills">
                            <?php
                            // Ορίζουμε τις ετικέτες για κάθε κλειδί δεξιότητας
                            $skillLabels = [
                                'defensive_driving' => 'Αμυντική Οδήγηση',
                                'eco_driving' => 'Οικολογική Οδήγηση',
                                'night_driving' => 'Νυχτερινή Οδήγηση',
                                'mountain_driving' => 'Οδήγηση σε Ορεινές Περιοχές',
                                'extreme_conditions' => 'Οδήγηση σε Ακραίες Συνθήκες',
                                'loading_securing' => 'Φόρτωση & Ασφάλιση Φορτίου',
                                'emergency_response' => 'Αντιμετώπιση Έκτακτων Καταστάσεων',
                                'first_aid' => 'Πρώτες Βοήθειες',
                                'dangerous_goods' => 'Διαχείριση Επικίνδυνων Εμπορευμάτων',
                                'tacograph_compliance' => 'Συμμόρφωση με Ταχογράφο',
                                'customer_service' => 'Εξυπηρέτηση Πελατών',
                                'time_management' => 'Διαχείριση Χρόνου',
                                'route_planning' => 'Σχεδιασμός Διαδρομής',
                                'conflict_resolution' => 'Επίλυση Συγκρούσεων',
                                'multilingual' => 'Πολύγλωσσος',
                                'vehicle_maintenance' => 'Συντήρηση Οχήματος',
                                'troubleshooting' => 'Αντιμετώπιση Βλαβών',
                                'digital_tachograph' => 'Ψηφιακός Ταχογράφος',
                                'gps_systems' => 'Συστήματα GPS',
                                'logistics_software' => 'Λογισμικό Logistics'
                            ];

                            $foundSkills = false; // Ένα flag για να ξέρουμε αν βρέθηκε τουλάχιστον μία δεξιότητα

                            // Ελέγχουμε κάθε κλειδί δεξιότητας από τον πίνακα $skillLabels
                            foreach ($skillLabels as $skillKey => $skillLabel) :
                                // Αν το κλειδί υπάρχει στο $driverSkills και η τιμή του είναι 1 (δηλαδή ο οδηγός την έχει)
                                if (isset($driverSkills[$skillKey]) && $driverSkills[$skillKey] == 1) :
                                    $foundSkills = true; // Βρήκαμε τουλάχιστον μία δεξιότητα
                            ?>
                                    <div class="skill-tag">
                                        <span class="skill-name"><?php echo htmlspecialchars($skillLabel); ?></span>
                                    </div>
                                <?php
                                endif;
                            endforeach;

                            // Αν δεν βρέθηκε καμία δεξιότητα, εμφανίζουμε μήνυμα
                            if (!$foundSkills) :
                                ?>
                                <p class="no-skills">Δεν έχουν καταχωρηθεί δεξιότητες.</p>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($driverData['additional_skills'])) : ?>
                            <div class="additional-skills">
                                <h4>Επιπλέον Δεξιότητες</h4>
                                <p><?php echo nl2br(htmlspecialchars($driverData['additional_skills'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php else : ?>
                    <section class="profile-section">
                        <h2>Δεξιότητες</h2>
                        <p class="no-skills">Δεν έχουν καταχωρηθεί δεξιότητες.</p>
                        <?php if (!empty($driverData['additional_skills'])) : ?>
                            <div class="additional-skills">
                                <h4>Επιπλέον Δεξιότητες</h4>
                                <p><?php echo nl2br(htmlspecialchars($driverData['additional_skills'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

                <!-- Εμπειρία Εργασίας -->
                <?php if (isset($driverData['work_experience']) && $driverData['work_experience']) : ?>
                    <section class="profile-section">
                        <h2>Εμπειρία Εργασίας</h2>
                        <div class="work-experience">
                            <?php echo nl2br(htmlspecialchars($driverData['work_experience'])); ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Αξιολογήσεις -->
                <?php if (isset($driverReviews) && !empty($driverReviews)) : ?>
                    <section class="profile-section">
                        <h2>Αξιολογήσεις</h2>
                        <div class="driver-reviews">
                            <?php foreach ($driverReviews as $review) : ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <div class="reviewer-info">
                                            <h4><?php echo htmlspecialchars($review['company_name'] ?? 'Ανώνυμος'); ?></h4>
                                            <span class="review-date"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></span>
                                        </div>
                                        <div class="review-rating">
                                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                                <span class="star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>">★</span>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="review-content">
                                        <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>

            <!-- Δεξιά Στήλη -->
            <div class="profile-sidebar">
                <!-- Άδειες Οδήγησης -->
                <?php if (isset($driverLicenses) && !empty($driverLicenses)) : ?>
                    <section class="profile-card">
                        <h3>Άδειες Οδήγησης</h3>
                        <div class="license-badges">
                            <?php foreach ($driverLicenseTypes as $licenseType) : ?>
                                <span class="license-badge"><?php echo htmlspecialchars($licenseType); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Ειδικές Πιστοποιήσεις -->
                <?php
                $hasCertifications =
                    (isset($driverData['adr_certificate']) && $driverData['adr_certificate']) ||
                    (isset($driverData['operator_license']) && $driverData['operator_license']) ||
                    (isset($driverData['tachograph_card']) && $driverData['tachograph_card']);

                if ($hasCertifications) :
                ?>
                    <section class="profile-card">
                        <h3>Πιστοποιήσεις</h3>
                        <div class="certifications">
                            <ul class="certification-list">
                                <?php if (isset($driverData['adr_certificate']) && $driverData['adr_certificate']) : ?>
                                    <li class="certification-item">
                                        <img src="<?= \Drivejob\Helpers\Asset::url('img/adr_icon.png') ?>" alt="ADR">
                                        <div class="certification-info">
                                            <h4>Πιστοποιητικό ADR</h4>
                                            <?php if (isset($driverData['adr_classes']) && $driverData['adr_classes']) : ?>
                                                <p>Κατηγορίες: <?php echo htmlspecialchars($driverData['adr_classes']); ?></p>
                                            <?php endif; ?>
                                            <?php if (isset($driverData['adr_certificate_expiry']) && $driverData['adr_certificate_expiry']) : ?>
                                                <p>Λήξη: <?php echo date('d/m/Y', strtotime($driverData['adr_certificate_expiry'])); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endif; ?>

                                <?php if (isset($driverData['operator_license']) && $driverData['operator_license']) : ?>
                                    <li class="certification-item">
                                        <img src="<?= \Drivejob\Helpers\Asset::url('img/operator_icon.png') ?>" alt="Χειριστής">
                                        <div class="certification-info">
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

                                <?php if (isset($driverData['tachograph_card']) && $driverData['tachograph_card']) : ?>
                                    <li class="certification-item">
                                        <img src="<?= \Drivejob\Helpers\Asset::url('img/tachograph_icon.png') ?>" alt="Ταχογράφος">
                                        <div class="certification-info">
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

                <!-- Προτιμώμενα Οχήματα -->
                <?php if (isset($driverData['preferred_vehicle_type']) && $driverData['preferred_vehicle_type']) : ?>
                    <section class="profile-card">
                        <h3>Προτιμώμενα Οχήματα</h3>
                        <div class="preferred-vehicles">
                            <?php
                            $vehicleType = $driverData['preferred_vehicle_type'];
                            $vehicleText = '';

                            switch ($vehicleType) {
                                case 'car':
                                    $vehicleText = 'Αυτοκίνητο';
                                    break;
                                case 'van':
                                    $vehicleText = 'Βαν';
                                    break;
                                case 'truck':
                                    $vehicleText = 'Φορτηγό';
                                    break;
                                case 'bus':
                                    $vehicleText = 'Λεωφορείο';
                                    break;
                                case 'machinery':
                                    $vehicleText = 'Μηχάνημα Έργου';
                                    break;
                            }
                            ?>
                            <div class="vehicle-badge <?php echo $vehicleType; ?>">
                                <img src="<?php echo BASE_URL; ?>img/vehicle_<?php echo $vehicleType; ?>_icon.png" alt="<?php echo $vehicleText; ?>">
                                <?php echo $vehicleText; ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Προτιμώμενο Ωράριο -->
                <?php if (isset($driverData['preferred_schedule']) && $driverData['preferred_schedule']) : ?>
                    <section class="profile-card">
                        <h3>Προτιμώμενο Ωράριο</h3>
                        <div class="preferred-schedule">
                            <?php
                            $scheduleArray = is_array($driverData['preferred_schedule']) ? $driverData['preferred_schedule'] : explode(',', $driverData['preferred_schedule']);

                            foreach ($scheduleArray as $schedule) :
                                $scheduleText = '';
                                $scheduleIcon = '';

                                switch (trim($schedule)) {
                                    case 'morning':
                                        $scheduleText = 'Πρωινό (06:00-14:00)';
                                        $scheduleIcon = 'morning';
                                        break;
                                    case 'afternoon':
                                        $scheduleText = 'Απογευματινό (14:00-22:00)';
                                        $scheduleIcon = 'afternoon';
                                        break;
                                    case 'night':
                                        $scheduleText = 'Βραδινό (22:00-06:00)';
                                        $scheduleIcon = 'night';
                                        break;
                                    case 'shifts':
                                        $scheduleText = 'Εναλλασσόμενες Βάρδιες';
                                        $scheduleIcon = 'shifts';
                                        break;
                                    case 'weekend':
                                        $scheduleText = 'Σαββατοκύριακα';
                                        $scheduleIcon = 'weekend';
                                        break;
                                    case 'flexible':
                                        $scheduleText = 'Ευέλικτο Ωράριο';
                                        $scheduleIcon = 'flexible';
                                        break;
                                }

                                if (!empty($scheduleText)) :
                            ?>
                                    <div class="schedule-item">
                                        <img src="<?php echo BASE_URL; ?>img/schedule_<?php echo $scheduleIcon; ?>_icon.png" alt="<?php echo $scheduleText; ?>">
                                        <span><?php echo $scheduleText; ?></span>
                                    </div>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Στοιχεία Επικοινωνίας (ορατά μόνο σε εταιρείες) -->
                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'company') : ?>
                    <section class="profile-card contact-info">
                        <h3>Στοιχεία Επικοινωνίας</h3>

                        <?php if (isset($driverData['email']) && $driverData['email']) : ?>
                            <div class="contact-item">
                                <img src="<?= \Drivejob\Helpers\Asset::url('img/email_icon.png') ?>" alt="Email">
                                <a href="mailto:<?php echo htmlspecialchars($driverData['email']); ?>"><?php echo htmlspecialchars($driverData['email']); ?></a>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($driverData['phone']) && $driverData['phone']) : ?>
                            <div class="contact-item">
                                <img src="<?= \Drivejob\Helpers\Asset::url('img/phone_icon.png') ?>" alt="Τηλέφωνο">
                                <a href="tel:<?php echo htmlspecialchars($driverData['phone']); ?>"><?php echo htmlspecialchars($driverData['phone']); ?></a>
                            </div>
                        <?php endif; ?>

                        <div class="contact-action">
                            <a href="<?php echo BASE_URL; ?>messages/create/<?php echo $driverData['id']; ?>" class="btn-primary btn-block">
                                <img src="<?= \Drivejob\Helpers\Asset::url('img/message_icon.png') ?>" alt="Μήνυμα">
                                Αποστολή Μηνύματος
                            </a>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </div>

        <!-- Αγγελίες Οδηγού -->
        <?php if (isset($listings) && count($listings['results']) > 0) : ?>
            <section class="driver-listings">
                <h2>Αγγελίες Οδηγού</h2>

                <div class="job-listings">
                    <?php foreach ($listings['results'] as $listing) : ?>
                        <div class="job-listing-card">
                            <div class="job-listing-header">
                                <h3><a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></h3>
                                <div>
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

                            <div class="job-listing-details">
                                <div class="job-listing-detail">
                                    <img src="<?= \Drivejob\Helpers\Asset::url('img/location_icon.png') ?>" alt="Τοποθεσία">
                                    <span><?php echo htmlspecialchars($listing['location']); ?></span>
                                </div>

                                <div class="job-listing-detail">
                                    <img src="<?= \Drivejob\Helpers\Asset::url('img/vehicle_icon.png') ?>" alt="Όχημα">
                                    <span>
                                        <?php
                                        $vehicleTypes = [];
                                        if (isset($listing['vehicle_types'])) {
                                            $vehicleTypes = is_array($listing['vehicle_types']) ? $listing['vehicle_types'] : explode(',', $listing['vehicle_types']);
                                        }
                                        if (!empty($vehicleTypes)) {
                                            $vehicleLabels = [];
                                            foreach ($vehicleTypes as $type) {
                                                switch (trim($type)) {
                                                    case 'car':
                                                        $vehicleLabels[] = 'Αυτοκίνητο';
                                                        break;
                                                    case 'van':
                                                        $vehicleLabels[] = 'Βαν';
                                                        break;
                                                    case 'truck':
                                                        $vehicleLabels[] = 'Φορτηγό';
                                                        break;
                                                    case 'bus':
                                                        $vehicleLabels[] = 'Λεωφορείο';
                                                        break;
                                                    case 'machinery':
                                                        $vehicleLabels[] = 'Μηχάνημα Έργου';
                                                        break;
                                                }
                                            }
                                            echo implode(', ', $vehicleLabels);
                                        } else {
                                            switch ($listing['vehicle_type'] ?? '') {
                                                case 'car':
                                                    echo 'Αυτοκίνητο';
                                                    break;
                                                case 'van':
                                                    echo 'Βαν';
                                                    break;
                                                case 'truck':
                                                    echo 'Φορτηγό';
                                                    break;
                                                case 'bus':
                                                    echo 'Λεωφορείο';
                                                    break;
                                                case 'machinery':
                                                    echo 'Μηχάνημα Έργου';
                                                    break;
                                                default:
                                                    echo 'Δεν καθορίστηκε';
                                            }
                                        }
                                        ?>
                                    </span>
                                </div>

                                <?php if ($listing['salary_min'] || $listing['salary_max']) : ?>
                                    <div class="job-listing-detail">
                                        <img src="<?= \Drivejob\Helpers\Asset::url('img/salary_icon.png') ?>" alt="Αμοιβή">
                                        <span>
                                            <?php
                                            if ($listing['salary_min'] && $listing['salary_max']) {
                                                echo number_format($listing['salary_min']) . '€ - ' . number_format($listing['salary_max']) . '€';
                                            } elseif ($listing['salary_min']) {
                                                echo 'Από ' . number_format($listing['salary_min']) . '€';
                                            } elseif ($listing['salary_max']) {
                                                echo 'Έως ' . number_format($listing['salary_max']) . '€';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="job-listing-description">
                                <?php echo nl2br(htmlspecialchars(mb_substr($listing['description'], 0, 150, 'UTF-8') . (mb_strlen($listing['description'], 'UTF-8') > 150 ? '...' : ''))); ?>
                            </div>

                            <div class="job-listing-footer">
                                <span class="job-listing-date">Δημοσιεύτηκε: <?php echo date('d/m/Y', strtotime($listing['created_at'])); ?></span>
                                <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>" class="btn-primary">Περισσότερα</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($listings['results']) >= 5) : ?>
                    <div class="view-all-listings">
                        <a href="<?php echo BASE_URL; ?>job-listings/driver/<?php echo $driverData['id']; ?>" class="btn-secondary">
                            Προβολή Όλων των Αγγελιών
                        </a>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</main>

<style>
    /* Βασικό στυλ για το προφίλ οδηγού */
    .driver-profile-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        background-color: #f9f9f9;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .driver-info {
        display: flex;
        align-items: center;
        gap: 25px;
    }

    .driver-photo-container {
        flex-shrink: 0;
    }

    .driver-photo {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #fff;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }

    .driver-details h1 {
        margin: 0 0 15px 0;
        font-size: 28px;
        color: #333;
    }

    .driver-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 15px;
    }

    .driver-location,
    .driver-experience,
    .driver-rating {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #666;
        font-size: 15px;
    }

    .driver-location img,
    .driver-experience img,
    .driver-rating img {
        width: 18px;
        height: 18px;
    }

    .driver-rating .stars {
        color: #f8b739;
        letter-spacing: 2px;
    }

    .driver-availability {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 14px;
    }

    .driver-availability.available {
        background-color: #e8f5e9;
        color: #2e7d32;
    }

    .driver-availability.not-available {
        background-color: #ffebee;
        color: #c62828;
    }

    .driver-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    /* Διάταξη περιεχομένου σε δύο στήλες */
    .profile-content {
        display: flex;
        gap: 30px;
        margin-bottom: 40px;
    }

    .profile-main {
        flex: 2;
    }

    .profile-sidebar {
        flex: 1;
    }

    /* Ενότητες προφίλ */
    .profile-section,
    .profile-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        padding: 20px;
        margin-bottom: 25px;
    }

    .profile-section h2,
    .profile-card h3 {
        margin-top: 0;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
        color: #333;
    }

    /* About Me / Περιγραφή */
    .about-me,
    .work-experience {
        color: #444;
        line-height: 1.6;
    }

    /* Δεξιότητες */
    .driver-skills {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
    }

    .skill-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .skill-name {
        font-weight: 500;
        color: #333;
    }

    .skill-level {
        display: flex;
        gap: 3px;
    }

    .skill-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #e0e0e0;
    }

    .skill-dot.active {
        background-color: #0277bd;
    }

    /* Αξιολογήσεις */
    .driver-reviews {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .review-item {
        padding: 15px;
        border-radius: 8px;
        background-color: #f9f9f9;
    }

    .review-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .reviewer-info h4 {
        margin: 0 0 5px 0;
        font-size: 16px;
    }

    .review-date {
        font-size: 14px;
        color: #777;
    }

    .review-rating .star {
        color: #e0e0e0;
    }

    .review-rating .star.filled {
        color: #f8b739;
    }

    .review-content {
        line-height: 1.5;
        color: #444;
    }

    /* Άδειες & Πιστοποιήσεις */
    .license-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .license-badge {
        padding: 6px 12px;
        border-radius: 4px;
        background-color: #f1f8fe;
        color: #0277bd;
        font-weight: 500;
        font-size: 14px;
    }

    .certification-list {
        list-style-type: none;
        padding: 0;
        margin: 0;
    }

    .certification-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .certification-item:last-child {
        border-bottom: none;
    }

    .certification-item img {
        width: 24px;
        height: 24px;
        margin-top: 3px;
    }

    .certification-info h4 {
        margin: 0 0 5px 0;
        font-size: 16px;
    }

    .certification-info p {
        margin: 0 0 5px 0;
        font-size: 14px;
        color: #666;
    }

    /* Προτιμώμενα Οχήματα */
    .preferred-vehicles {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .vehicle-badge {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 4px;
        background-color: #f5f5f5;
        color: #333;
        font-size: 14px;
    }

    .vehicle-badge img {
        width: 20px;
        height: 20px;
    }

    /* Προτιμώμενο Ωράριο */
    .preferred-schedule {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .schedule-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .schedule-item:last-child {
        border-bottom: none;
    }

    .schedule-item img {
        width: 20px;
        height: 20px;
    }

    /* Στοιχεία Επικοινωνίας */
    .contact-info {
        background-color: #f9f9f9;
    }

    .contact-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .contact-item:last-child {
        border-bottom: none;
    }

    .contact-item img {
        width: 18px;
        height: 18px;
    }

    .contact-item a {
        color: #0277bd;
        text-decoration: none;
    }

    .contact-item a:hover {
        text-decoration: underline;
    }

    .contact-action {
        margin-top: 15px;
    }

    /* Αγγελίες Οδηγού */
    .driver-listings {
        margin-top: 40px;
    }

    .driver-listings h2 {
        margin-bottom: 20px;
    }

    .job-listings {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }

    .job-listing-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        padding: 15px;
        transition: all 0.3s ease;
    }

    .job-listing-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .job-listing-header {
        margin-bottom: 15px;
    }

    .job-listing-header h3 {
        margin: 0 0 10px 0;
        font-size: 18px;
    }

    .job-listing-header h3 a {
        color: #333;
        text-decoration: none;
    }

    .job-listing-header h3 a:hover {
        color: #0277bd;
    }

    .job-type,
    .listing-type {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
        margin-right: 5px;
    }

    .job-type.full_time {
        background-color: #e8f5e9;
        color: #2e7d32;
    }

    .job-type.part_time {
        background-color: #e3f2fd;
        color: #0277bd;
    }

    .job-type.contract {
        background-color: #fff3e0;
        color: #ef6c00;
    }

    .job-type.temporary {
        background-color: #f3e5f5;
        color: #8e24aa;
    }

    .listing-type.job_offer {
        background-color: #e8eaf6;
        color: #3f51b5;
    }

    .listing-type.job_search {
        background-color: #fce4ec;
        color: #c2185b;
    }

    .job-listing-details {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 15px;
    }

    .job-listing-detail {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: #666;
    }

    .job-listing-detail img {
        width: 16px;
        height: 16px;
    }

    .job-listing-description {
        margin-bottom: 15px;
        color: #555;
        font-size: 14px;
        line-height: 1.5;
    }

    .job-listing-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #f0f0f0;
        padding-top: 15px;
    }

    .job-listing-date {
        font-size: 13px;
        color: #777;
    }

    .view-all-listings {
        text-align: center;
        margin-top: 20px;
    }

    /* Κουμπιά */
    .btn-primary,
    .btn-secondary,
    .btn-block {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 4px;
        font-weight: bold;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .btn-primary {
        background-color: #0277bd;
        color: white;
        border: none;
    }

    .btn-secondary {
        background-color: #f5f5f5;
        color: #333;
        border: 1px solid #ddd;
    }

    .btn-block {
        display: flex;
        justify-content: center;
        width: 100%;
    }

    .btn-primary:hover,
    .btn-secondary:hover {
        opacity: 0.9;
        transform: translateY(-2px);
    }

    .btn-primary img,
    .btn-secondary img {
        width: 18px;
        height: 18px;
    }

    /* Προσαρμογή για μικρότερες οθόνες */
    @media (max-width: 768px) {
        .driver-profile-header {
            flex-direction: column;
            gap: 20px;
        }

        .driver-info {
            flex-direction: column;
            align-items: center;
            text-align: center;
            width: 100%;
        }

        .driver-meta {
            justify-content: center;
        }

        .driver-actions {
            width: 100%;
        }

        .profile-content {
            flex-direction: column;
        }

        .job-listings {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>
<?php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>
<!-- Προσθήκη του Font Awesome για τα εικονίδια -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

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
                    <?php if (isset($driver['profile_image']) && $driver['profile_image']) : ?>
                        <img src="<?php echo BASE_URL . htmlspecialchars($driver['profile_image']); ?>" alt="Φωτογραφία προφίλ" class="driver-photo">
                    <?php else : ?>
                        <img src="<?php echo BASE_URL; ?>img/default_profile.png" alt="Προεπιλεγμένη φωτογραφία" class="driver-photo">
                    <?php endif; ?>
                </div>
                <div class="driver-details">
                    <h1>
                        <?php
                        $firstName = isset($driver['first_name']) ? $driver['first_name'] : '';
                        $lastName = isset($driver['last_name']) ? $driver['last_name'] : '';
                        echo htmlspecialchars($firstName . ' ' . $lastName);
                        ?>
                    </h1>

                    <div class="driver-meta">
                        <?php if (isset($driver['city']) && $driver['city']) : ?>
                            <div class="driver-location">
                                <img src="<?php echo BASE_URL; ?>img/location_icon.png" alt="Τοποθεσία">
                                <span>
                                    <?php
                                    $city = isset($driver['city']) ? $driver['city'] : '';
                                    $country = isset($driver['country']) ? $driver['country'] : '';
                                    echo htmlspecialchars($city . ($city && $country ? ', ' : '') . $country);
                                    ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($driver['experience_years']) && $driver['experience_years']) : ?>
                            <div class="driver-experience">
                                <img src="<?php echo BASE_URL; ?>img/experience_icon.png" alt="Εμπειρία">
                                <span><?php echo intval($driver['experience_years']); ?> έτη εμπειρίας</span>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($averageRating) && $averageRating > 0) : ?>
                            <div class="driver-rating">
                                <img src="<?php echo BASE_URL; ?>img/rating_icon.png" alt="Αξιολόγηση">
                                <div class="stars">
                                    <?php
                                    $rating = is_numeric($averageRating) ? round($averageRating * 2) / 2 : 0; // Στρογγυλοποίηση στο πλησιέστερο 0.5
                                    for ($i = 1; $i <= 5; $i++) :
                                        if ($i <= $rating) : // Πλήρες αστέρι
                                    ?>
                                            <span class="star filled">★</span>
                                        <?php elseif ($i - 0.5 == $rating) : // Μισό αστέρι 
                                        ?>
                                            <span class="star half">★</span>
                                        <?php else : // Κενό αστέρι 
                                        ?>
                                            <span class="star">★</span>
                                    <?php endif;
                                    endfor; ?>
                                </div>
                                <span><?php echo number_format((float)$averageRating, 1); ?>/5</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($driver['available_for_work'])) : ?>
                        <div class="driver-availability <?php echo $driver['available_for_work'] ? 'available' : 'not-available'; ?>">
                            <?php echo $driver['available_for_work'] ? 'Διαθέσιμος/η για εργασία' : 'Μη διαθέσιμος/η για εργασία'; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="driver-actions">
                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'company' && isset($driver['id'])) : ?>
                    <a href="<?php echo BASE_URL; ?>messages/create/<?php echo $driver['id']; ?>" class="btn-primary">
                        <img src="<?php echo BASE_URL; ?>img/message_icon.png" alt="Μήνυμα">
                        Επικοινωνία
                    </a>

                    <a href="<?php echo BASE_URL; ?>drivers/download-resume/<?php echo $driver['id']; ?>" class="btn-secondary">
                        <img src="<?php echo BASE_URL; ?>img/download_icon.png" alt="Κατέβασμα">
                        Βιογραφικό
                    </a>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'driver' && $_SESSION['user_id'] == $driver['id']) : ?>
                    <a href="<?php echo BASE_URL; ?>drivers/edit-profile" class="btn-secondary">
                        <img src="<?php echo BASE_URL; ?>img/edit_icon.png" alt="Επεξεργασία">
                        Επεξεργασία Προφίλ
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Περιεχόμενο Προφίλ -->
        <div class="profile-content">
            <!-- Αριστερή Στήλη -->
            <div class="profile-main">
                <!-- Οπτικό Βιογραφικό για όλους τους χρήστες -->
                <?php include ROOT_DIR . '/src/Views/components/driver-visual-resume.php'; ?>

                <!-- Βαθμολογία και Αξιολογήσεις (αν υπάρχουν) -->
                <?php
                // Ανάκτηση των αναλυτικών βαθμολογιών του οδηγού
                $driverRatings = null;
                if (isset($ratingModel) && isset($driver) && is_array($driver) && isset($driver['id'])) {
                    $driverRatings = $ratingModel->getDriverRatingDetails($driver['id']);
                }
                include ROOT_DIR . '/src/Views/components/driver-ratings-display.php';
                ?>

                <!-- Περίληψη / Σχετικά με εμένα -->
                <?php if (isset($driver['about_me']) && $driver['about_me']) : ?>
                    <section class="profile-section">
                        <h2>Σχετικά με εμένα</h2>
                        <div class="about-me">
                            <?php echo nl2br(htmlspecialchars($driver['about_me'])); ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Δεξιότητες Οδηγού (μεταφέρονται στο οπτικό βιογραφικό) -->

                <!-- Εμπειρία Εργασίας (μεταφέρεται στο οπτικό βιογραφικό) -->
            </div>

            <!-- Δεξιά Στήλη -->
            <div class="profile-sidebar">
                <!-- Τυπικά Προσόντα (αντικαθιστά το "Άδειες Οδήγησης") -->
                <?php include ROOT_DIR . '/src/Views/components/driver-profile-qualifications.php'; ?>

                <!-- Προτιμώμενα Οχήματα -->
                <?php if (isset($driver['preferred_vehicle_type']) && $driver['preferred_vehicle_type']) : ?>
                    <section class="profile-card">
                        <h3>Προτιμώμενα Οχήματα</h3>
                        <div class="preferred-vehicles">
                            <?php
                            $vehicleType = $driver['preferred_vehicle_type'];
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
                                default:
                                    $vehicleText = $vehicleType;
                            }
                            ?>
                            <div class="vehicle-badge <?php echo htmlspecialchars($vehicleType); ?>">
                                <?php if (file_exists(ROOT_DIR . '/public/img/vehicle_' . $vehicleType . '_icon.png')) : ?>
                                    <img src="<?php echo BASE_URL; ?>img/vehicle_<?php echo htmlspecialchars($vehicleType); ?>_icon.png" alt="<?php echo htmlspecialchars($vehicleText); ?>">
                                <?php else : ?>
                                    <img src="<?php echo BASE_URL; ?>img/vehicle_icon.png" alt="Όχημα">
                                <?php endif; ?>
                                <?php echo htmlspecialchars($vehicleText); ?>
                            </div>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Προτιμώμενο Ωράριο -->
                <?php if (isset($driver['preferred_schedule']) && $driver['preferred_schedule']) : ?>
                    <section class="profile-card">
                        <h3>Προτιμώμενο Ωράριο</h3>
                        <div class="preferred-schedule">
                            <?php
                            $scheduleArray = is_array($driver['preferred_schedule']) ? $driver['preferred_schedule'] : explode(',', $driver['preferred_schedule']);

                            foreach ($scheduleArray as $schedule) :
                                $schedule = trim($schedule);
                                if (empty($schedule)) {
                                    continue;
                                }

                                $scheduleText = '';
                                $scheduleIcon = '';

                                switch ($schedule) {
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
                                    default:
                                        $scheduleText = $schedule;
                                        $scheduleIcon = 'flexible';
                                }

                                if (!empty($scheduleText)) :
                            ?>
                                    <div class="schedule-item">
                                        <?php if (file_exists(ROOT_DIR . '/public/img/schedule_' . $scheduleIcon . '_icon.png')) : ?>
                                            <img src="<?php echo BASE_URL; ?>img/schedule_<?php echo htmlspecialchars($scheduleIcon); ?>_icon.png" alt="<?php echo htmlspecialchars($scheduleText); ?>">
                                        <?php else : ?>
                                            <img src="<?php echo BASE_URL; ?>img/schedule_icon.png" alt="Ωράριο">
                                        <?php endif; ?>
                                        <span><?php echo htmlspecialchars($scheduleText); ?></span>
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

                        <?php if (isset($driver['email']) && $driver['email']) : ?>
                            <div class="contact-item">
                                <img src="<?php echo BASE_URL; ?>img/email_icon.png" alt="Email">
                                <a href="mailto:<?php echo htmlspecialchars($driver['email']); ?>"><?php echo htmlspecialchars($driver['email']); ?></a>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($driver['phone']) && $driver['phone']) : ?>
                            <div class="contact-item">
                                <img src="<?php echo BASE_URL; ?>img/phone_icon.png" alt="Τηλέφωνο">
                                <a href="tel:<?php echo htmlspecialchars($driver['phone']); ?>"><?php echo htmlspecialchars($driver['phone']); ?></a>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($driver['id'])) : ?>
                            <div class="contact-action">
                                <a href="<?php echo BASE_URL; ?>messages/create/<?php echo $driver['id']; ?>" class="btn-primary btn-block">
                                    <img src="<?php echo BASE_URL; ?>img/message_icon.png" alt="Μήνυμα">
                                    Αποστολή Μηνύματος
                                </a>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>
            </div>
        </div>

        <!-- Αγγελίες Οδηγού -->
        <?php if (isset($listings) && isset($listings['results']) && !empty($listings['results'])) : ?>
            <section class="driver-listings">
                <h2>Αγγελίες Οδηγού</h2>

                <div class="job-listings">
                    <?php foreach ($listings['results'] as $listing) : ?>
                        <div class="job-listing-card">
                            <div class="job-listing-header">
                                <h3>
                                    <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo htmlspecialchars($listing['id']); ?>">
                                        <?php echo htmlspecialchars($listing['title'] ?? 'Χωρίς τίτλο'); ?>
                                    </a>
                                </h3>
                                <div>
                                    <?php if (isset($listing['job_type'])) : ?>
                                        <span class="job-type <?php echo htmlspecialchars($listing['job_type']); ?>">
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
                                                default:
                                                    echo htmlspecialchars($listing['job_type']);
                                            }
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (isset($listing['listing_type'])) : ?>
                                        <span class="listing-type <?php echo htmlspecialchars($listing['listing_type']); ?>">
                                            <?php echo $listing['listing_type'] === 'job_offer' ? 'Προσφορά Εργασίας' : 'Αναζήτηση Εργασίας'; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="job-listing-details">
                                <?php if (isset($listing['location']) && $listing['location']) : ?>
                                    <div class="job-listing-detail">
                                        <img src="<?php echo BASE_URL; ?>img/location_icon.png" alt="Τοποθεσία">
                                        <span><?php echo htmlspecialchars($listing['location']); ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="job-listing-detail">
                                    <img src="<?php echo BASE_URL; ?>img/vehicle_icon.png" alt="Όχημα">
                                    <span>
                                        <?php
                                        if (isset($listing['vehicle_types']) && !empty($listing['vehicle_types'])) {
                                            $vehicleTypes = is_array($listing['vehicle_types']) ? $listing['vehicle_types'] : explode(',', $listing['vehicle_types']);
                                            if (!empty($vehicleTypes)) {
                                                $vehicleLabels = [];
                                                foreach ($vehicleTypes as $type) {
                                                    $type = trim($type);
                                                    switch ($type) {
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
                                                        default:
                                                            $vehicleLabels[] = $type;
                                                    }
                                                }
                                                echo implode(', ', $vehicleLabels);
                                            }
                                        } elseif (isset($listing['vehicle_type'])) {
                                            switch ($listing['vehicle_type']) {
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
                                                    echo isset($listing['vehicle_type']) ? htmlspecialchars($listing['vehicle_type']) : 'Δεν καθορίστηκε';
                                            }
                                        } else {
                                            echo 'Δεν καθορίστηκε';
                                        }
                                        ?>
                                    </span>
                                </div>

                                <?php if ((isset($listing['salary_min']) && $listing['salary_min']) || (isset($listing['salary_max']) && $listing['salary_max'])) : ?>
                                    <div class="job-listing-detail">
                                        <img src="<?php echo BASE_URL; ?>img/salary_icon.png" alt="Αμοιβή">
                                        <span>
                                            <?php
                                            if (isset($listing['salary_min']) && $listing['salary_min'] && isset($listing['salary_max']) && $listing['salary_max']) {
                                                echo number_format((float)$listing['salary_min']) . '€ - ' . number_format((float)$listing['salary_max']) . '€';
                                            } elseif (isset($listing['salary_min']) && $listing['salary_min']) {
                                                echo 'Από ' . number_format((float)$listing['salary_min']) . '€';
                                            } elseif (isset($listing['salary_max']) && $listing['salary_max']) {
                                                echo 'Έως ' . number_format((float)$listing['salary_max']) . '€';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (isset($listing['description'])) : ?>
                                <div class="job-listing-description">
                                    <?php
                                    $description = $listing['description'];
                                    $shortDesc = strlen($description) > 150 ? substr($description, 0, 150) . '...' : $description;
                                    echo nl2br(htmlspecialchars($shortDesc));
                                    ?>
                                </div>
                            <?php endif; ?>

                            <div class="job-listing-footer">
                                <?php if (isset($listing['created_at'])) : ?>
                                    <span class="job-listing-date">Δημοσιεύτηκε: <?php echo date('d/m/Y', strtotime($listing['created_at'])); ?></span>
                                <?php endif; ?>
                                <?php if (isset($listing['id'])) : ?>
                                    <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo htmlspecialchars($listing['id']); ?>" class="btn-primary">Περισσότερα</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (isset($listings['pagination']) && isset($listings['pagination']['pages']) && $listings['pagination']['pages'] > 1 && isset($driver['id'])) : ?>
                    <div class="view-all-listings">
                        <a href="<?php echo BASE_URL; ?>job-listings/driver/<?php echo htmlspecialchars($driver['id']); ?>" class="btn-secondary">
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
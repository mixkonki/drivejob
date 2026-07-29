<?php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';

// Εισαγωγή της κλάσης CSRF
use Drivejob\Core\CSRF;
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/company-profile.css">

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

        <!-- Κάρτα Προφίλ Εταιρείας -->
        <div class="company-profile-header">
            <div class="company-info">
                <div class="company-logo-container">
                    <?php if (isset($companyData['company_logo']) && $companyData['company_logo']) : ?>
                        <img src="<?php echo BASE_URL . htmlspecialchars($companyData['company_logo']); ?>" alt="Λογότυπο εταιρείας" class="company-logo">
                    <?php else : ?>
                        <img src="<?php echo BASE_URL; ?>img/default_company_logo.png" alt="Προεπιλεγμένο λογότυπο" class="company-logo">
                    <?php endif; ?>
                </div>
                <div class="company-details">
                    <h1><?php echo htmlspecialchars($companyData['company_name']); ?></h1>

                    <div class="company-meta">
                        <?php if (isset($companyData['city']) && $companyData['city']) : ?>
                            <div class="company-location">
                                <img src="<?php echo BASE_URL; ?>img/location_icon.png" alt="Τοποθεσία">
                                <span><?php echo htmlspecialchars($companyData['city'] . ', ' . $companyData['country']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($companyData['industry']) && $companyData['industry']) : ?>
                            <div class="company-industry">
                                <img src="<?php echo BASE_URL; ?>img/industry_icon.png" alt="Κλάδος">
                                <span><?php echo htmlspecialchars($companyData['industry']); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($averageRating) && $averageRating > 0) : ?>
                            <div class="company-rating">
                                <img src="<?php echo BASE_URL; ?>img/rating_icon.png" alt="Αξιολόγηση">
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
                </div>
            </div>

            <div class="company-actions">
                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'driver') : ?>
                    <a href="<?php echo BASE_URL; ?>messages/create/<?php echo $companyData['id']; ?>" class="btn-primary">
                        <img src="<?php echo BASE_URL; ?>img/message_icon.png" alt="Μήνυμα">
                        Επικοινωνία
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Περιεχόμενο Προφίλ -->
        <div class="profile-content">
            <!-- Αριστερή Στήλη -->
            <div class="profile-main">
                <!-- Περιγραφή Εταιρείας -->
                <?php if (isset($companyData['description']) && $companyData['description']) : ?>
                    <section class="profile-section">
                        <h2>Σχετικά με την Εταιρεία</h2>
                        <div class="company-description">
                            <?php echo nl2br(htmlspecialchars($companyData['description'])); ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Πληροφορίες Εταιρείας -->
                <section class="profile-section">
                    <h2>Πληροφορίες Εταιρείας</h2>
                    <div class="company-info-grid">
                        <?php if (isset($companyData['company_size']) && $companyData['company_size']) : ?>
                            <div class="info-item">
                                <div class="info-label">Μέγεθος Εταιρείας</div>
                                <div class="info-value"><?php echo htmlspecialchars($companyData['company_size']); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($companyData['foundation_year']) && $companyData['foundation_year']) : ?>
                            <div class="info-item">
                                <div class="info-label">Έτος Ίδρυσης</div>
                                <div class="info-value"><?php echo htmlspecialchars($companyData['foundation_year']); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($companyData['fleet_size']) && $companyData['fleet_size']) : ?>
                            <div class="info-item">
                                <div class="info-label">Μέγεθος Στόλου</div>
                                <div class="info-value"><?php echo htmlspecialchars($companyData['fleet_size']); ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($companyData['transport_types']) && $companyData['transport_types']) : ?>
                            <div class="info-item">
                                <div class="info-label">Τύποι Μεταφορών</div>
                                <div class="info-value">
                                    <?php
                                    $transportTypes = is_array($companyData['transport_types']) ? $companyData['transport_types'] : explode(',', $companyData['transport_types']);
                                    $transportLabels = [];
                                    foreach ($transportTypes as $type) {
                                        switch (trim($type)) {
                                            case 'freight':
                                                $transportLabels[] = 'Μεταφορά Εμπορευμάτων';
                                                break;
                                            case 'passenger':
                                                $transportLabels[] = 'Μεταφορά Επιβατών';
                                                break;
                                            case 'machinery':
                                                $transportLabels[] = 'Χειρισμός Μηχανημάτων';
                                                break;
                                            default:
                                                $transportLabels[] = trim($type);
                                        }
                                    }
                                    echo implode(', ', $transportLabels);
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Αξιολογήσεις -->
                <section class="profile-section">
                    <h2>Αξιολογήσεις</h2>

                    <?php if (isset($companyReviews) && !empty($companyReviews)) : ?>
                        <div class="company-reviews">
                            <?php foreach ($companyReviews as $review) : ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <div class="reviewer-info">
                                            <h4><?php echo htmlspecialchars($review['driver_name'] ?? 'Ανώνυμος'); ?></h4>
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
                    <?php else : ?>
                        <div class="no-reviews">
                            <p>Δεν υπάρχουν ακόμα αξιολογήσεις για αυτή την εταιρεία.</p>
                        </div>
                    <?php endif; ?>

                    <!-- Φόρμα Αξιολόγησης για Οδηγούς -->
                    <?php if (isset($canReview) && $canReview) : ?>
                        <div class="review-form-container">
                            <h3>Αξιολογήστε την εταιρεία</h3>

                            <?php if (isset($_SESSION['errors']) && isset($_SESSION['errors']['rating'])) : ?>
                                <div class="error-message">
                                    <?php echo $_SESSION['errors']['rating']; ?>
                                    <?php unset($_SESSION['errors']['rating']); ?>
                                </div>
                            <?php endif; ?>

                            <form action="<?php echo BASE_URL; ?>companies/add-review/<?php echo $companyData['id']; ?>" method="post" class="review-form">
                                <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">

                                <div class="form-group">
                                    <label for="rating">Συνολική Βαθμολογία:</label>
                                    <div class="rating-input">
                                        <?php for ($i = 5; $i >= 1; $i--) : ?>
                                            <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo (isset($_SESSION['old_input']['rating']) && $_SESSION['old_input']['rating'] == $i) ? 'checked' : ''; ?>>
                                            <label for="star<?php echo $i; ?>">★</label>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <div class="detailed-ratings">
                                    <h4>Λεπτομερής Αξιολόγηση</h4>

                                    <div class="form-group">
                                        <label for="reliability_rating">Αξιοπιστία:</label>
                                        <div class="rating-input">
                                            <?php for ($i = 5; $i >= 1; $i--) : ?>
                                                <input type="radio" id="reliability_star<?php echo $i; ?>" name="detailed_ratings[reliability_rating]" value="<?php echo $i; ?>" <?php echo (isset($_SESSION['old_input']['detailed_ratings']['reliability_rating']) && $_SESSION['old_input']['detailed_ratings']['reliability_rating'] == $i) ? 'checked' : ''; ?>>
                                                <label for="reliability_star<?php echo $i; ?>">★</label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="communication_rating">Επικοινωνία:</label>
                                        <div class="rating-input">
                                            <?php for ($i = 5; $i >= 1; $i--) : ?>
                                                <input type="radio" id="communication_star<?php echo $i; ?>" name="detailed_ratings[communication_rating]" value="<?php echo $i; ?>" <?php echo (isset($_SESSION['old_input']['detailed_ratings']['communication_rating']) && $_SESSION['old_input']['detailed_ratings']['communication_rating'] == $i) ? 'checked' : ''; ?>>
                                                <label for="communication_star<?php echo $i; ?>">★</label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="payment_rating">Πληρωμή:</label>
                                        <div class="rating-input">
                                            <?php for ($i = 5; $i >= 1; $i--) : ?>
                                                <input type="radio" id="payment_star<?php echo $i; ?>" name="detailed_ratings[payment_rating]" value="<?php echo $i; ?>" <?php echo (isset($_SESSION['old_input']['detailed_ratings']['payment_rating']) && $_SESSION['old_input']['detailed_ratings']['payment_rating'] == $i) ? 'checked' : ''; ?>>
                                                <label for="payment_star<?php echo $i; ?>">★</label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="working_conditions_rating">Συνθήκες Εργασίας:</label>
                                        <div class="rating-input">
                                            <?php for ($i = 5; $i >= 1; $i--) : ?>
                                                <input type="radio" id="working_conditions_star<?php echo $i; ?>" name="detailed_ratings[working_conditions_rating]" value="<?php echo $i; ?>" <?php echo (isset($_SESSION['old_input']['detailed_ratings']['working_conditions_rating']) && $_SESSION['old_input']['detailed_ratings']['working_conditions_rating'] == $i) ? 'checked' : ''; ?>>
                                                <label for="working_conditions_star<?php echo $i; ?>">★</label>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="comment">Σχόλιο:</label>
                                    <textarea name="comment" id="comment" rows="4" placeholder="Γράψτε την εμπειρία σας με την εταιρεία..."><?php echo isset($_SESSION['old_input']['comment']) ? htmlspecialchars($_SESSION['old_input']['comment']) : ''; ?></textarea>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn-primary">Υποβολή Αξιολόγησης</button>
                                </div>
                            </form>
                        </div>
                    <?php elseif (isset($hasReviewed) && $hasReviewed) : ?>
                        <div class="already-reviewed">
                            <p>Έχετε ήδη αξιολογήσει αυτή την εταιρεία.</p>
                        </div>
                    <?php elseif (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'driver') : ?>
                        <div class="review-info">
                            <p>Για να αξιολογήσετε αυτή την εταιρεία, πρέπει να έχετε συνεργαστεί μαζί της.</p>
                        </div>
                    <?php elseif (!isset($_SESSION['user_id'])) : ?>
                        <div class="review-login-prompt">
                            <p>Για να αξιολογήσετε αυτή την εταιρεία, πρέπει να <a href="<?php echo BASE_URL; ?>login">συνδεθείτε</a> ως οδηγός.</p>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            <!-- Δεξιά Στήλη -->
            <div class="profile-sidebar">
                <!-- Στοιχεία Επικοινωνίας -->
                <section class="profile-card contact-info">
                    <h3>Στοιχεία Επικοινωνίας</h3>
                    <ul class="contact-list">
                        <?php if (isset($companyData['email']) && $companyData['email']) : ?>
                            <li>
                                <img src="<?php echo BASE_URL; ?>img/email_icon.png" alt="Email">
                                <a href="mailto:<?php echo htmlspecialchars($companyData['email']); ?>"><?php echo htmlspecialchars($companyData['email']); ?></a>
                            </li>
                        <?php endif; ?>

                        <?php if (isset($companyData['phone']) && $companyData['phone']) : ?>
                            <li>
                                <img src="<?php echo BASE_URL; ?>img/phone_icon.png" alt="Τηλέφωνο">
                                <a href="tel:<?php echo htmlspecialchars($companyData['phone']); ?>"><?php echo htmlspecialchars($companyData['phone']); ?></a>
                            </li>
                        <?php endif; ?>

                        <?php if (isset($companyData['website']) && $companyData['website']) : ?>
                            <li>
                                <img src="<?php echo BASE_URL; ?>img/website_icon.png" alt="Ιστοσελίδα">
                                <a href="<?php echo htmlspecialchars($companyData['website']); ?>" target="_blank"><?php echo htmlspecialchars($companyData['website']); ?></a>
                            </li>
                        <?php endif; ?>

                        <?php if (isset($companyData['social_linkedin']) && $companyData['social_linkedin']) : ?>
                            <li>
                                <img src="<?php echo BASE_URL; ?>img/linkedin_icon.png" alt="LinkedIn">
                                <a href="<?php echo htmlspecialchars($companyData['social_linkedin']); ?>" target="_blank">LinkedIn</a>
                            </li>
                        <?php endif; ?>

                        <?php if (isset($companyData['social_facebook']) && $companyData['social_facebook']) : ?>
                            <li>
                                <img src="<?php echo BASE_URL; ?>img/facebook_icon.png" alt="Facebook">
                                <a href="<?php echo htmlspecialchars($companyData['social_facebook']); ?>" target="_blank">Facebook</a>
                            </li>
                        <?php endif; ?>

                        <?php if (isset($companyData['social_twitter']) && $companyData['social_twitter']) : ?>
                            <li>
                                <img src="<?php echo BASE_URL; ?>img/twitter_icon.png" alt="Twitter">
                                <a href="<?php echo htmlspecialchars($companyData['social_twitter']); ?>" target="_blank">Twitter</a>
                            </li>
                        <?php endif; ?>
                    </ul>

                    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'driver') : ?>
                        <div class="contact-action">
                            <a href="<?php echo BASE_URL; ?>messages/create/<?php echo $companyData['id']; ?>" class="btn-primary btn-block">
                                <img src="<?php echo BASE_URL; ?>img/message_icon.png" alt="Μήνυμα">
                                Αποστολή Μηνύματος
                            </a>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Υπεύθυνος Επικοινωνίας -->
                <?php if (isset($companyData['contact_person']) && $companyData['contact_person']) : ?>
                    <section class="profile-card">
                        <h3>Υπεύθυνος Επικοινωνίας</h3>
                        <div class="contact-person">
                            <h4><?php echo htmlspecialchars($companyData['contact_person']); ?></h4>
                            <?php if (isset($companyData['position']) && $companyData['position']) : ?>
                                <p class="contact-position"><?php echo htmlspecialchars($companyData['position']); ?></p>
                            <?php endif; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Τοποθεσία -->
                <?php if (isset($companyData['address']) && $companyData['address'] && isset($companyData['city']) && $companyData['city']) : ?>
                    <section class="profile-card">
                        <h3>Τοποθεσία</h3>
                        <div class="company-map">
                            <iframe
                                width="100%"
                                height="200"
                                frameborder="0"
                                scrolling="no"
                                marginheight="0"
                                marginwidth="0"
                                src="https://maps.google.com/maps?q=<?php echo urlencode($companyData['address'] . ', ' . $companyData['city'] . ', ' . $companyData['country']); ?>&output=embed"></iframe>
                        </div>
                    </section>
                <?php endif; ?>
            </div>
        </div>

        <!-- Αγγελίες Εταιρείας -->
        <?php if (isset($listings) && count($listings['results']) > 0) : ?>
            <section class="company-listings">
                <h2>Αγγελίες Εταιρείας</h2>

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
                                    <img src="<?php echo BASE_URL; ?>img/location_icon.png" alt="Τοποθεσία">
                                    <span><?php echo htmlspecialchars($listing['location']); ?></span>
                                </div>

                                <div class="job-listing-detail">
                                    <img src="<?php echo BASE_URL; ?>img/vehicle_icon.png" alt="Όχημα">
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
                                            ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="job-listing-description">
                                <?php echo nl2br(htmlspecialchars(substr($listing['description'], 0, 150) . (strlen($listing['description']) > 150 ? '...' : ''))); ?>
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
                        <a href="<?php echo BASE_URL; ?>job-listings/company/<?php echo $companyData['id']; ?>" class="btn-secondary">
                            Προβολή Όλων των Αγγελιών
                        </a>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</main>

<style>
    /* Βασικό στυλ για το προφίλ εταιρείας */
    .company-profile-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        background-color: #f9f9f9;
        border-radius: 8px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .company-info {
        display: flex;
        align-items: center;
        gap: 25px;
    }

    .company-logo-container {
        flex-shrink: 0;
    }

    .company-logo {
        width: 120px;
        height: 120px;
        border-radius: 8px;
        object-fit: contain;
        border: 3px solid #fff;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        background-color: #fff;
    }

    .company-details h1 {
        margin: 0 0 15px 0;
        font-size: 28px;
        color: #333;
    }

    .company-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 15px;
    }

    .company-location,
    .company-industry,
    .company-rating {
        display: flex;
        align-items: center;
        gap: 8px;
        color: #666;
        font-size: 15px;
    }

    .company-location img,
    .company-industry img,
    .company-rating img {
        width: 18px;
        height: 18px;
    }

    .company-rating .stars {
        color: #f8b739;
        letter-spacing: 2px;
    }

    .company-actions {
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

    /* Περιγραφή Εταιρείας */
    .company-description {
        color: #444;
        line-height: 1.6;
    }

    /* Πληροφορίες Εταιρείας */
    .company-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }

    .info-item {
        margin-bottom: 15px;
    }

    .info-label {
        font-weight: bold;
        color: #555;
        margin-bottom: 5px;
    }

    .info-value {
        color: #333;
    }

    /* Αξιολογήσεις */
    .company-reviews {
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

    /* Στοιχεία Επικοινωνίας */
    .contact-list {
        list-style-type: none;
        padding: 0;
        margin: 0;
    }

    .contact-list li {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .contact-list li:last-child {
        border-bottom: none;
    }

    .contact-list li img {
        width: 18px;
        height: 18px;
    }

    .contact-list li a {
        color: #0277bd;
        text-decoration: none;
    }

    .contact-list li a:hover {
        text-decoration: underline;
    }

    .contact-action {
        margin-top: 15px;
    }

    /* Υπεύθυνος Επικοινωνίας */
    .contact-person h4 {
        margin: 0 0 5px 0;
        font-size: 16px;
        color: #333;
    }

    .contact-position {
        margin: 0;
        color: #666;
        font-size: 14px;
    }

    /* Αγγελίες Εταιρείας */
    .company-listings {
        margin-top: 40px;
    }

    .company-listings h2 {
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
        color: #1565c0;
    }

    .job-type.contract {
        background-color: #fff3e0;
        color: #e65100;
    }

    .job-type.temporary {
        background-color: #f3e5f5;
        color: #7b1fa2;
    }

    .listing-type {
        background-color: #f5f5f5;
        color: #616161;
    }

    .listing-type.job_offer {
        background-color: #e8f5e9;
        color: #2e7d32;
    }

    .listing-type.job_search {
        background-color: #e3f2fd;
        color: #1565c0;
    }

    /* Φόρμα Αξιολόγησης */
    .review-form-container {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }

    .review-form-container h3 {
        margin-top: 0;
        margin-bottom: 15px;
    }

    .review-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: #555;
    }

    .rating-input {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
    }

    .rating-input input {
        display: none;
    }

    .rating-input label {
        cursor: pointer;
        font-size: 30px;
        color: #ddd;
        margin-right: 5px;
    }

    .rating-input label:hover,
    .rating-input label:hover~label,
    .rating-input input:checked~label {
        color: #f8b739;
    }

    .review-form textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        resize: vertical;
    }

    .no-reviews,
    .already-reviewed,
    .review-info,
    .review-login-prompt {
        padding: 15px;
        background-color: #f9f9f9;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .no-reviews p,
    .already-reviewed p,
    .review-info p,
    .review-login-prompt p {
        margin: 0;
        color: #666;
    }

    .review-login-prompt a {
        color: #0277bd;
        text-decoration: none;
    }

    .review-login-prompt a:hover {
        text-decoration: underline;
    }

    /* Λεπτομερείς Αξιολογήσεις */
    .detailed-ratings {
        background-color: #f9f9f9;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .detailed-ratings h4 {
        margin-top: 0;
        margin-bottom: 15px;
        color: #333;
        font-size: 16px;
    }

    .detailed-ratings .form-group {
        margin-bottom: 10px;
    }

    .detailed-ratings .rating-input label {
        font-size: 24px;
    }
<?php
// Συμπερίληψη του header
require_once ROOT_DIR . '/src/Views/partials/header.php';
?>

<link rel="stylesheet" href="<?php echo BASE_URL; ?>css/job-listings.css">

<main>
    <div class="container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <div class="job-listing-detail">
            <div class="job-listing-header">
                <h1><?php echo htmlspecialchars($listing['title']); ?></h1>
                <div class="job-listing-meta">
                    <?php if (isset($listing['job_type'])): ?>
                        <span class="job-type <?php echo htmlspecialchars($listing['job_type']); ?>">
                            <?php
                            $jobTypeLabels = [
                                'full_time' => 'Πλήρης Απασχόληση',
                                'part_time' => 'Μερική Απασχόληση',
                                'contract' => 'Σύμβαση Έργου',
                                'temporary' => 'Προσωρινή Απασχόληση'
                            ];
                            echo isset($jobTypeLabels[$listing['job_type']]) ? $jobTypeLabels[$listing['job_type']] : $listing['job_type'];
                            ?>
                        </span>
                    <?php endif; ?>
                    <?php if (isset($listing['listing_type'])): ?>
                        <span class="listing-type <?php echo htmlspecialchars($listing['listing_type']); ?>">
                            <?php
                            $listingTypeLabels = [
                                'job_offer' => 'Προσφορά Εργασίας',
                                'job_search' => 'Αναζήτηση Εργασίας'
                            ];
                            echo isset($listingTypeLabels[$listing['listing_type']]) ? $listingTypeLabels[$listing['listing_type']] : $listing['listing_type'];
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="job-listing-info">
                <div class="job-listing-main">
                    <div class="job-listing-section">
                        <h2>Περιγραφή</h2>
                        <div class="job-description">
                            <?php echo nl2br(htmlspecialchars($listing['description'])); ?>
                        </div>
                    </div>

                    <?php if (!empty($listing['benefits'])): ?>
                        <div class="job-listing-section">
                            <h2>Παροχές</h2>
                            <div class="job-benefits">
                                <?php echo nl2br(htmlspecialchars($listing['benefits'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($listing['additional_info'])): ?>
                        <div class="job-listing-section">
                            <h2>Επιπλέον Πληροφορίες</h2>
                            <div class="job-additional-info">
                                <?php echo nl2br(htmlspecialchars($listing['additional_info'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="job-listing-sidebar">
                    <div class="job-listing-section">
                        <h2>Στοιχεία Αγγελίας</h2>
                        <ul class="job-details-list">
                            <?php if (!empty($listing['location'])): ?>
                                <li>
                                    <strong>Τοποθεσία:</strong>
                                    <?php echo htmlspecialchars($listing['location']); ?>
                                </li>
                            <?php endif; ?>

                            <?php if (!empty($listing['job_type'])): ?>
                                <li>
                                    <strong>Τύπος Απασχόλησης:</strong>
                                    <?php
                                    $jobTypeLabels = [
                                        'full_time' => 'Πλήρης Απασχόληση',
                                        'part_time' => 'Μερική Απασχόληση',
                                        'contract' => 'Σύμβαση Έργου',
                                        'temporary' => 'Προσωρινή Απασχόληση'
                                    ];
                                    echo isset($jobTypeLabels[$listing['job_type']]) ? $jobTypeLabels[$listing['job_type']] : $listing['job_type'];
                                    ?>
                                </li>
                            <?php endif; ?>

                            <?php if (!empty($listing['transport_type'])): ?>
                                <li>
                                    <strong>Τύπος Μεταφοράς:</strong>
                                    <?php
                                    $transportTypeLabels = [
                                        'freight' => 'Μεταφορά Εμπορευμάτων',
                                        'passenger' => 'Μεταφορά Επιβατών',
                                        'machinery' => 'Χειρισμός Μηχανημάτων'
                                    ];
                                    echo isset($transportTypeLabels[$listing['transport_type']]) ? $transportTypeLabels[$listing['transport_type']] : $listing['transport_type'];
                                    ?>
                                </li>
                            <?php endif; ?>

                            <?php if (!empty($listing['vehicle_type'])): ?>
                                <li>
                                    <strong>Τύπος Οχήματος:</strong>
                                    <?php
                                    $vehicleTypeLabels = [
                                        'car' => 'Αυτοκίνητο',
                                        'van' => 'Βαν',
                                        'truck' => 'Φορτηγό',
                                        'bus' => 'Λεωφορείο',
                                        'machinery' => 'Μηχάνημα Έργου'
                                    ];

                                    if (is_string($listing['vehicle_type'])) {
                                        $types = explode(',', $listing['vehicle_type']);
                                        $typeLabels = [];
                                        foreach ($types as $type) {
                                            $type = trim($type);
                                            $typeLabels[] = isset($vehicleTypeLabels[$type]) ? $vehicleTypeLabels[$type] : $type;
                                        }
                                        echo implode(', ', $typeLabels);
                                    } else {
                                        echo isset($vehicleTypeLabels[$listing['vehicle_type']]) ? $vehicleTypeLabels[$listing['vehicle_type']] : $listing['vehicle_type'];
                                    }
                                    ?>
                                </li>
                            <?php endif; ?>

                            <?php if (!empty($listing['salary_min']) || !empty($listing['salary_max'])): ?>
                                <li>
                                    <strong>Μισθός:</strong>
                                    <?php
                                    if (!empty($listing['salary_min']) && !empty($listing['salary_max'])) {
                                        echo number_format($listing['salary_min']) . '€ - ' . number_format($listing['salary_max']) . '€';
                                    } elseif (!empty($listing['salary_min'])) {
                                        echo 'Από ' . number_format($listing['salary_min']) . '€';
                                    } elseif (!empty($listing['salary_max'])) {
                                        echo 'Έως ' . number_format($listing['salary_max']) . '€';
                                    }

                                    if (!empty($listing['salary_type'])) {
                                        switch ($listing['salary_type']) {
                                            case 'hourly':
                                                echo ' / ώρα';
                                                break;
                                            case 'daily':
                                                echo ' / ημέρα';
                                                break;
                                            case 'monthly':
                                                echo ' / μήνα';
                                                break;
                                            case 'yearly':
                                                echo ' / έτος';
                                                break;
                                        }
                                    }
                                    ?>
                                </li>
                            <?php endif; ?>

                            <?php if (!empty($listing['experience_years'])): ?>
                                <li>
                                    <strong>Απαιτούμενη Εμπειρία:</strong>
                                    <?php echo $listing['experience_years']; ?> έτη
                                </li>
                            <?php endif; ?>

                            <?php if (!empty($listing['required_license'])): ?>
                                <li>
                                    <strong>Απαιτούμενη Άδεια Οδήγησης:</strong>
                                    <?php echo htmlspecialchars($listing['required_license']); ?>
                                </li>
                            <?php endif; ?>

                            <?php if (isset($listing['requires_pei']) && $listing['requires_pei']): ?>
                                <li>
                                    <strong>Απαιτείται ΠΕΙ:</strong> Ναι
                                </li>
                            <?php endif; ?>

                            <?php if (isset($listing['adr_certificate']) && $listing['adr_certificate']): ?>
                                <li>
                                    <strong>Απαιτείται ADR:</strong> Ναι
                                </li>
                            <?php endif; ?>

                            <?php if (isset($listing['requires_tachograph']) && $listing['requires_tachograph']): ?>
                                <li>
                                    <strong>Απαιτείται Κάρτα Ταχογράφου:</strong> Ναι
                                </li>
                            <?php endif; ?>

                            <li>
                                <strong>Ημερομηνία Δημοσίευσης:</strong>
                                <?php echo date('d/m/Y', strtotime($listing['created_at'])); ?>
                            </li>

                            <?php if (!empty($listing['expires_at'])): ?>
                                <li>
                                    <strong>Ημερομηνία Λήξης:</strong>
                                    <?php echo date('d/m/Y', strtotime($listing['expires_at'])); ?>
                                </li>
                            <?php endif; ?>

                            <li>
                                <strong>Προβολές:</strong>
                                <?php echo isset($listing['views_count']) ? $listing['views_count'] : 0; ?>
                            </li>
                        </ul>
                    </div>

                    <?php if (isset($company) && !empty($company)): ?>
                        <div class="job-listing-section">
                            <h2>Στοιχεία Εταιρείας</h2>
                            <div class="author-info">
                                <h3><?php echo htmlspecialchars($company['company_name'] ?? $company['name'] ?? ''); ?></h3>
                                <?php if (!empty($company['logo'])): ?>
                                    <img src="<?php echo BASE_URL . htmlspecialchars($company['logo']); ?>" alt="<?php echo htmlspecialchars($company['company_name'] ?? $company['name'] ?? ''); ?>" class="company-logo">
                                <?php endif; ?>
                                <?php if (!empty($company['description'])): ?>
                                    <p><?php echo nl2br(htmlspecialchars($company['description'])); ?></p>
                                <?php endif; ?>
                                <a href="<?php echo BASE_URL; ?>companies/profile/<?php echo $company['id']; ?>" class="btn-secondary">Προφίλ Εταιρείας</a>
                            </div>
                        </div>
                    <?php elseif (isset($driver) && !empty($driver)): ?>
                        <div class="job-listing-section">
                            <h2>Στοιχεία Οδηγού</h2>
                            <div class="author-info">
                                <h3><?php echo htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']); ?></h3>
                                <?php if (!empty($driver['profile_image'])): ?>
                                    <img src="<?php echo BASE_URL . htmlspecialchars($driver['profile_image']); ?>" alt="<?php echo htmlspecialchars($driver['first_name'] . ' ' . $driver['last_name']); ?>" class="driver-image">
                                <?php endif; ?>
                                <?php if (!empty($driver['bio'])): ?>
                                    <p><?php echo nl2br(htmlspecialchars($driver['bio'])); ?></p>
                                <?php endif; ?>
                                <a href="<?php echo BASE_URL; ?>drivers/profile/<?php echo $driver['id']; ?>" class="btn-secondary">Προφίλ Οδηγού</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="job-listing-actions">
                        <?php if (isset($isLoggedIn) && $isLoggedIn): ?>
                            <?php if (isset($isOwner) && $isOwner): ?>
                                <a href="<?php echo BASE_URL; ?>job-listings/edit/<?php echo $listing['id']; ?>" class="btn-primary">Επεξεργασία</a>
                                <a href="<?php echo BASE_URL; ?>job-listings/delete/<?php echo $listing['id']; ?>" class="btn-danger">Διαγραφή</a>
                            <?php elseif (isset($userRole) && $userRole === 'driver' && isset($listing['listing_type']) && $listing['listing_type'] === 'job_offer'): ?>
                                <?php if (isset($hasApplied) && $hasApplied): ?>
                                    <button class="btn-secondary" disabled>Έχετε ήδη υποβάλει αίτηση</button>
                                <?php else: ?>
                                    <a href="<?php echo BASE_URL; ?>job-applications/create/<?php echo $listing['id']; ?>" class="btn-primary">Υποβολή Αίτησης</a>
                                <?php endif; ?>
                            <?php elseif (isset($userRole) && $userRole === 'company' && isset($listing['listing_type']) && $listing['listing_type'] === 'job_search'): ?>
                                <a href="<?php echo BASE_URL; ?>job-offers/create/<?php echo $listing['id']; ?>" class="btn-primary">Αποστολή Προσφοράς</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="<?php echo BASE_URL; ?>auth/login" class="btn-primary">Συνδεθείτε για να υποβάλετε αίτηση</a>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>job-listings" class="btn-secondary">Επιστροφή στις Αγγελίες</a>
                    </div>
                </div>
            </div>

            <?php if (isset($similarListings) && !empty($similarListings['results'])): ?>
                <div class="job-listing-section similar-listings">
                    <h2>Παρόμοιες Αγγελίες</h2>
                    <div class="job-listings">
                        <?php foreach ($similarListings['results'] as $similarListing): ?>
                            <div class="job-listing-card">
                                <div class="job-listing-header">
                                    <h3><a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $similarListing['id']; ?>"><?php echo htmlspecialchars($similarListing['title']); ?></a></h3>
                                    <div>
                                        <?php if (isset($similarListing['job_type'])): ?>
                                            <span class="job-type <?php echo htmlspecialchars($similarListing['job_type']); ?>">
                                                <?php
                                                $jobTypeLabels = [
                                                    'full_time' => 'Πλήρης Απασχόληση',
                                                    'part_time' => 'Μερική Απασχόληση',
                                                    'contract' => 'Σύμβαση Έργου',
                                                    'temporary' => 'Προσωρινή Απασχόληση'
                                                ];
                                                echo isset($jobTypeLabels[$similarListing['job_type']]) ? $jobTypeLabels[$similarListing['job_type']] : $similarListing['job_type'];
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (isset($similarListing['listing_type'])): ?>
                                            <span class="listing-type <?php echo htmlspecialchars($similarListing['listing_type']); ?>">
                                                <?php
                                                $listingTypeLabels = [
                                                    'job_offer' => 'Προσφορά Εργασίας',
                                                    'job_search' => 'Αναζήτηση Εργασίας'
                                                ];
                                                echo isset($listingTypeLabels[$similarListing['listing_type']]) ? $listingTypeLabels[$similarListing['listing_type']] : $similarListing['listing_type'];
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="job-listing-details">
                                    <?php if (!empty($similarListing['location'])): ?>
                                        <div class="job-listing-detail">
                                            <img src="<?php echo BASE_URL; ?>img/location_icon.png" alt="Τοποθεσία">
                                            <span><?php echo htmlspecialchars($similarListing['location']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($similarListing['salary_min']) || !empty($similarListing['salary_max'])): ?>
                                        <div class="job-listing-detail">
                                            <img src="<?php echo BASE_URL; ?>img/salary_icon.png" alt="Μισθός">
                                            <span>
                                                <?php
                                                if (!empty($similarListing['salary_min']) && !empty($similarListing['salary_max'])) {
                                                    echo number_format($similarListing['salary_min']) . '€ - ' . number_format($similarListing['salary_max']) . '€';
                                                } elseif (!empty($similarListing['salary_min'])) {
                                                    echo 'Από ' . number_format($similarListing['salary_min']) . '€';
                                                } elseif (!empty($similarListing['salary_max'])) {
                                                    echo 'Έως ' . number_format($similarListing['salary_max']) . '€';
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="job-listing-footer">
                                    <span class="job-listing-date">Δημοσιεύτηκε: <?php echo date('d/m/Y', strtotime($similarListing['created_at'])); ?></span>
                                    <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $similarListing['id']; ?>" class="btn-primary">Περισσότερα</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
// Συμπερίληψη του footer
require_once ROOT_DIR . '/src/Views/partials/footer.php';
?>
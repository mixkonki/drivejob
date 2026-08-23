<?php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<?= \Drivejob\Helpers\Asset::css('css/job-listings.css') ?>

<main>
    <div class="container">
        <h1>Προτεινόμενες Αγγελίες για Εσάς</h1>

        <div class="matches-header">
            <div class="matches-info">
                <p><strong><?php echo count($matchedListings); ?></strong> αγγελίες ταιριάζουν με το προφίλ σας.</p>
            </div>
            <div class="matches-actions">
                <a href="<?php echo BASE_URL; ?>job-listings" class="btn-secondary">Όλες οι Αγγελίες</a>
                <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn-primary">Νέα Αγγελία</a>
            </div>
        </div>

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

        <!-- Προφίλ Αναζήτησης -->
        <div class="search-profile">
            <h3>Το Προφίλ Αναζήτησής σας</h3>
            <div class="search-profile-items">
                <?php if (!empty($driverProfile['preferred_job_type'])) : ?>
                    <div class="search-profile-item">
                        <img src="<?= \Drivejob\Helpers\Asset::url('img/job_type_icon.png') ?>" alt="Τύπος Εργασίας">
                        <span>
                            <?php
                            switch ($driverProfile['preferred_job_type']) {
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
                                    echo htmlspecialchars($driverProfile['preferred_job_type']);
                            }
                            ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($driverProfile['preferred_vehicle_type'])) : ?>
                    <div class="search-profile-item">
                        <img src="<?= \Drivejob\Helpers\Asset::url('img/vehicle_icon.png') ?>" alt="Τύπος Οχήματος">
                        <span>
                            <?php
                            switch ($driverProfile['preferred_vehicle_type']) {
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
                                    echo htmlspecialchars($driverProfile['preferred_vehicle_type']);
                            }
                            ?>
                        </span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($driverProfile['preferred_location'])) : ?>
                    <div class="search-profile-item">
                        <img src="<?= \Drivejob\Helpers\Asset::url('img/location_icon.png') ?>" alt="Τοποθεσία">
                        <span><?php echo htmlspecialchars($driverProfile['preferred_location']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($driverProfile['preferred_radius'])) : ?>
                    <div class="search-profile-item">
                        <img src="<?= \Drivejob\Helpers\Asset::url('img/radius_icon.png') ?>" alt="Ακτίνα">
                        <span>Ακτίνα: <?php echo htmlspecialchars($driverProfile['preferred_radius']); ?> χλμ</span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="search-profile-update">
                <a href="<?php echo BASE_URL; ?>drivers/edit-profile">Ενημέρωση Προτιμήσεων</a>
            </div>
        </div>

        <!-- Λίστα Αγγελιών -->
        <?php if (isset($matchedListings) && count($matchedListings) > 0) : ?>
            <div class="job-listings">
                <?php foreach ($matchedListings as $listing) : ?>
                    <div class="job-listing-card">
                        <!-- Ποσοστό ταιριάσματος -->
                        <div class="match-percentage-container">
                            <div class="match-percentage <?php echo $listing['match_percentage'] >= 80 ? 'high' : ($listing['match_percentage'] >= 50 ? 'medium' : 'low'); ?>">
                                <?php echo $listing['match_percentage']; ?>% ταίριασμα
                            </div>
                        </div>

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
                                </div>
                            <?php endif; ?>

                            <!-- Ειδικές Απαιτήσεις -->
                            <div class="job-listing-requirements">
                                <?php if ($listing['experience_years']) : ?>
                                    <span class="requirement" title="Έτη Εμπειρίας">
                                        <img src="<?= \Drivejob\Helpers\Asset::url('img/experience_icon.png') ?>" alt="Εμπειρία">
                                        <?php echo $listing['experience_years']; ?> έτη
                                    </span>
                                <?php endif; ?>

                                <?php if ($listing['adr_certificate']) : ?>
                                    <span class="requirement" title="Πιστοποιητικό ADR">
                                        <img src="<?= \Drivejob\Helpers\Asset::url('img/adr_icon.png') ?>" alt="ADR">
                                        ADR
                                    </span>
                                <?php endif; ?>

                                <?php if ($listing['operator_license']) : ?>
                                    <span class="requirement" title="Άδεια Χειριστή">
                                        <img src="<?= \Drivejob\Helpers\Asset::url('img/operator_icon.png') ?>" alt="Χειριστής">
                                        Άδεια Χειριστή
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="job-listing-description">
                            <?php echo nl2br(htmlspecialchars(mb_substr($listing['description'], 0, 150, 'UTF-8') . (mb_strlen($listing['description'], 'UTF-8') > 150 ? '...' : ''))); ?>
                        </div>

                        <div class="job-listing-footer">
                            <span class="job-listing-date">Δημοσιεύτηκε: <?php echo date('d/m/Y', strtotime($listing['created_at'])); ?></span>
                            <div class="job-listing-actions">
                                <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>" class="btn-primary">Περισσότερα</a>
                                <?php if ($listing['listing_type'] === 'job_offer') : ?>
                                    <form action="<?php echo BASE_URL; ?>job-applications/apply/<?php echo $listing['id']; ?>" method="POST" class="inline-form">
                                        <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
                                        <button type="submit" class="btn-action">Υποβολή Αίτησης</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else : ?>
            <div class="no-results">
                <p>Δεν βρέθηκαν αγγελίες που να ταιριάζουν με το προφίλ σας.</p>
                <p>Δοκιμάστε να ενημερώσετε τις προτιμήσεις σας ή να αναζητήσετε όλες τις διαθέσιμες αγγελίες.</p>
                <div class="no-results-actions">
                    <a href="<?php echo BASE_URL; ?>drivers/edit-profile" class="btn-secondary">Ενημέρωση Προτιμήσεων</a>
                    <a href="<?php echo BASE_URL; ?>job-listings" class="btn-primary">Όλες οι Αγγελίες</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<style>
    .matches-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .matches-info {
        font-size: 16px;
    }

    .matches-actions {
        display: flex;
        gap: 10px;
    }

    .search-profile {
        background-color: #f9f9f9;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
    }

    .search-profile h3 {
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 18px;
    }

    .search-profile-items {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }

    .search-profile-item {
        display: flex;
        align-items: center;
        background-color: #fff;
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 14px;
    }

    .search-profile-item img {
        width: 16px;
        height: 16px;
        margin-right: 5px;
    }

    .search-profile-update {
        margin-top: 15px;
        text-align: right;
    }

    .search-profile-update a {
        color: #aa3636;
        text-decoration: none;
        font-size: 14px;
    }

    .search-profile-update a:hover {
        text-decoration: underline;
    }

    .match-percentage-container {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 10px;
    }

    .match-percentage {
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 14px;
        font-weight: bold;
    }

    .match-percentage.high {
        background-color: #d4edda;
        color: #155724;
    }

    .match-percentage.medium {
        background-color: #fff3cd;
        color: #856404;
    }

    .match-percentage.low {
        background-color: #f8d7da;
        color: #721c24;
    }

    .job-listing-requirements {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }

    .requirement {
        display: flex;
        align-items: center;
        background-color: #f0f0f0;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 12px;
    }

    .requirement img {
        width: 14px;
        height: 14px;
        margin-right: 5px;
    }

    .inline-form {
        display: inline;
    }

    .job-listing-actions {
        display: flex;
        gap: 10px;
    }

    .btn-action {
        display: inline-block;
        padding: 8px 15px;
        background-color: #28a745;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        font-weight: bold;
        font-size: 14px;
    }

    .btn-action:hover {
        background-color: #218838;
    }

    .no-results {
        text-align: center;
        padding: 40px 0;
    }

    .no-results p {
        margin-bottom: 20px;
        color: #666;
    }

    .no-results-actions {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 20px;
    }
</style>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>
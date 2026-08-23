<?php

/**
 * Οι αγγελίες του συνδεδεμένου χρήστη (κοινό view για εταιρεία και οδηγό).
 *
 * Δύο controllers στέλνουν εδώ διαφορετικό σχήμα: άλλοι δίνουν $listings ως
 * ολόκληρο το αποτέλεσμα (με results και pagination μέσα), άλλοι δίνουν τα
 * αποτελέσματα σκέτα και τη σελιδοποίηση χωριστά. Κανονικοποιούμε εδώ αντί να
 * σπάσουμε τον έναν από τους δύο.
 */

$rows = $rows ?? (is_array($listings ?? null) ? $listings : []);
$pager = $pager ?? ($pagination ?? []);

$currentPage = (int) ($pager['page'] ?? $pager['current_page'] ?? 1);
$totalPages = (int) ($pager['pages'] ?? $pager['total_pages'] ?? 1);

// Ο ρόλος μπορεί να λείπει από τη συνεδρία σε παλιές συνδέσεις
$viewerRole = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<?= \Drivejob\Helpers\Asset::css('css/job-listings.css') ?>

<main>
    <div class="container">
        <h1>Οι Αγγελίες μου</h1>

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

        <!-- Επικεφαλίδα αγγελιών -->
        <div class="job-listings-header">
            <h2><?php echo $viewerRole === 'company' ? 'Αγγελίες της Εταιρείας μου' : 'Αγγελίες μου ως Οδηγός'; ?></h2>
            <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn-primary">Νέα Αγγελία</a>
        </div>

        <!-- Λίστα Αγγελιών -->
        <?php if (count($rows) > 0) : ?>
            <div class="job-listings">
                <?php foreach ($rows as $listing) : ?>
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
                                        ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <div class="job-listing-detail">
                                <img src="<?= \Drivejob\Helpers\Asset::url('img/status_icon.png') ?>" alt="Κατάσταση">
                                <span class="<?php echo $listing['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $listing['is_active'] ? 'Ενεργή' : 'Ανενεργή'; ?>
                                </span>
                            </div>
                        </div>

                        <div class="job-listing-description">
                            <?php echo nl2br(htmlspecialchars(mb_substr($listing['description'], 0, 150, 'UTF-8') . (mb_strlen($listing['description'], 'UTF-8') > 150 ? '...' : ''))); ?>
                        </div>

                        <div class="job-listing-footer">
                            <span class="job-listing-date">Δημοσιεύτηκε: <?php echo date('d/m/Y', strtotime($listing['created_at'])); ?></span>

                            <div class="job-listing-actions">
                                <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>" class="btn-primary">Προβολή</a>
                                <a href="<?php echo BASE_URL; ?>job-listings/edit/<?php echo $listing['id']; ?>" class="btn-secondary">Επεξεργασία</a>
                                <a href="<?php echo BASE_URL; ?>job-listings/delete/<?php echo $listing['id']; ?>" class="btn-danger" onclick="return confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή την αγγελία;')">Διαγραφή</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Σελιδοποίηση -->
            <?php if ($totalPages > 1) : ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                        <a href="?page=<?php echo $i; ?>" class="pagination-btn <?php echo $i === $currentPage ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>

        <?php else : ?>
            <div class="no-results">
                <p>Δεν έχετε δημιουργήσει ακόμα αγγελίες.</p>
                <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn-primary">Δημιουργήστε την πρώτη σας αγγελία</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>
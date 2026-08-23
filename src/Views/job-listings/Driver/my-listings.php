<?php

use Drivejob\Helpers\VehicleTypes;

/**
 * Κανονικοποίηση του σχήματος που στέλνουν οι controllers — άλλοι δίνουν
 * $listings ως ολόκληρο αποτέλεσμα, άλλοι σκέτα τα αποτελέσματα.
 */
$rows = $listings['results'] ?? (is_array($listings ?? null) ? $listings : []);
$pager = $listings['pagination'] ?? ($pagination ?? []);
$currentPage = (int) ($pager['page'] ?? $pager['current_page'] ?? 1);
$totalPages = (int) ($pager['pages'] ?? $pager['total_pages'] ?? 1);
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

        <div class="listings-actions">
            <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn-primary">Νέα Αγγελία</a>
        </div>

        <?php if (count($rows) > 0) : ?>
            <div class="listings-container">
                <?php foreach ($rows as $listing) : ?>
                    <div class="listing-card">
                        <div class="listing-header">
                            <h2><a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>"><?php echo htmlspecialchars($listing['title']); ?></a></h2>
                            <span class="listing-type <?php echo $listing['listing_type']; ?>">
                                <?php echo $listing['listing_type'] === 'job_offer' ? 'Προσφορά Εργασίας' : 'Αναζήτηση Εργασίας'; ?>
                            </span>
                        </div>

                        <div class="listing-details">
                            <div class="listing-meta">
                                <div class="meta-item">
                                    <img src="<?php echo BASE_URL; ?>img/location_icon.png" alt="Τοποθεσία">
                                    <span><?php echo htmlspecialchars($listing['location']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <img src="<?php echo BASE_URL; ?>img/date_icon.png" alt="Ημερομηνία">
                                    <span>Δημοσιεύτηκε: <?php echo date('d/m/Y', strtotime($listing['created_at'])); ?></span>
                                </div>
                                <div class="meta-item">
                                    <img src="<?php echo BASE_URL; ?>img/car_icon.png" alt="Τύπος Οχήματος">
                                    <span>
                                        <?php
                                        echo htmlspecialchars(VehicleTypes::label($listing['vehicle_type'] ?? null));
                                        ?>
                                    </span>
                                </div>
                            </div>

                            <div class="listing-description">
                                <?php echo htmlspecialchars(mb_substr($listing['description'], 0, 200, 'UTF-8')) . '...'; ?>
                            </div>

                            <div class="listing-status">
                                <span class="status-label">Κατάσταση:</span>
                                <span class="status-value <?php echo $listing['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $listing['is_active'] ? 'Ενεργή' : 'Ανενεργή'; ?>
                                </span>
                            </div>

                            <div class="listing-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Προβολές:</span>
                                    <span class="stat-value"><?php echo $listing['views_count']; ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">Αιτήσεις:</span>
                                    <span class="stat-value"><?php echo $listing['applications']; ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="listing-actions">
                            <a href="<?php echo BASE_URL; ?>job-listings/show/<?php echo $listing['id']; ?>" class="btn-secondary">Προβολή</a>
                            <a href="<?php echo BASE_URL; ?>job-listings/edit/<?php echo $listing['id']; ?>" class="btn-secondary">Επεξεργασία</a>
                            <form action="<?php echo BASE_URL; ?>job-listings/delete/<?php echo $listing['id']; ?>" method="post" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo \Drivejob\Core\CSRF::generateToken(); ?>">
                                <button type="submit" class="btn-danger" onclick="return confirm('Είστε σίγουροι ότι θέλετε να διαγράψετε αυτή την αγγελία;')">Διαγραφή</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1) : ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++) : ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo $i === $currentPage ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <div class="no-listings">
                <p>Δεν έχετε δημιουργήσει ακόμα αγγελίες.</p>
                <a href="<?php echo BASE_URL; ?>job-listings/create" class="btn-primary">Δημιουργία Αγγελίας</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>
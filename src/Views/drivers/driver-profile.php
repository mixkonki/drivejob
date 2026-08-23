<?php
// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/partials/header.php';
?>
<!-- Σύνδεση με το CSS αρχείο του προφίλ οδηγού -->
<?= \Drivejob\Helpers\Asset::css('css/driver-profile.css') ?>
<?= \Drivejob\Helpers\Asset::css('css/driver-skills.css') ?>
<?= \Drivejob\Helpers\Asset::css('css/expiring-licenses.css') ?>
<?= \Drivejob\Helpers\Asset::css('css/driver-rating-profile.css') ?>
<?= \Drivejob\Helpers\Asset::css('css/toggle-switch.css') ?>


<script>
    // Ορισμός των βασικών μεταβλητών
    const BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<?= \Drivejob\Helpers\Asset::js('js/driver-profile.js', false) ?>
<!-- Μετά το link του CSS και πριν το </head> -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCgZpJWVYyrY0U8U1jBGelEWryur3vIrzc&libraries=places"></script>

<main>
    <div class="container">
        <!-- Επικεφαλίδα προφίλ με βασικές πληροφορίες και στατιστικά -->
        <div class="profile-header">
            <div class="profile-image-wrapper">
                <div class="profile-image">
                    <?php if (isset($driverData['profile_image']) && $driverData['profile_image']) : ?>
                        <img src="<?php echo BASE_URL . htmlspecialchars($driverData['profile_image']); ?>" alt="Εικόνα προφίλ">
                    <?php else : ?>
                        <img src="<?php echo BASE_URL; ?>img/default_profile.png" alt="Προεπιλεγμένη εικόνα προφίλ">
                    <?php endif; ?>
                </div>

            </div>

            <div class="profile-info">
                <h1><?php echo htmlspecialchars($driverData['first_name'] . ' ' . $driverData['last_name']); ?></h1>

                <?php if (isset($driverData['city']) && $driverData['city']) : ?>
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

                        for ($i = 0; $i < $fullStars; $i++) : ?>
                            <i class="star full"></i>
                        <?php endfor; ?>

                        <?php if ($halfStar) : ?>
                            <i class="star half"></i>
                        <?php endif; ?>

                        <?php for ($i = 0; $i < $emptyStars; $i++) : ?>
                            <i class="star empty"></i>
                        <?php endfor; ?>

                        <span class="rating-value"><?php echo number_format($rating, 1); ?></span>
                    </div>
                    <span class="rating-count">(<?php echo $driverData['rating_count'] ?? 0; ?> αξιολογήσεις)</span>
                </div>

                <?php if (isset($driverData['experience_years']) && $driverData['experience_years']) : ?>
                    <div class="experience-badge">
                        <img src="<?php echo BASE_URL; ?>img/experience_icon.png" alt="Εμπειρία">
                        <span><?php echo $driverData['experience_years']; ?> έτη εμπειρίας</span>
                    </div>
                <?php endif; ?>


            </div>

            <!-- Ενότητα Στατιστικών Προφίλ μεταφέρθηκε εδώ -->
            <div class="profile-stats-header">
                <h3>Στατιστικά Προφίλ</h3>
                <ul class="profile-stats">
                    <li>
                        <div class="stat-icon">
                            <img src="<?php echo BASE_URL; ?>img/view_icon.png" alt="Προβολές">
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo isset($driverStats['profile_views']) ? $driverStats['profile_views'] : '0'; ?></span>
                            <span class="stat-label">Προβολές Προφίλ</span>
                        </div>
                    </li>
                    <li>
                        <div class="stat-icon">
                            <img src="<?php echo BASE_URL; ?>img/application_icon.png" alt="Αιτήσεις">
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo isset($driverStats['applications']) ? $driverStats['applications'] : '0'; ?></span>
                            <span class="stat-label">Αιτήσεις για Θέσεις</span>
                        </div>
                    </li>
                    <li>
                        <div class="stat-icon">
                            <img src="<?php echo BASE_URL; ?>img/match_icon.png" alt="Ταιριάσματα">
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo isset($driverStats['matches']) ? $driverStats['matches'] : '0'; ?></span>
                            <span class="stat-label">Ταιριάσματα Εργασίας</span>
                        </div>
                    </li>
                </ul>
                <div class="profile-image-actions">
                    <a href="<?php echo BASE_URL; ?>drivers/edit-profile" class="btn-primary">Επεξεργασία Προφίλ</a>

                    <?php if (isset($driverData['resume_file']) && $driverData['resume_file']) : ?>
                        <a href="<?php echo BASE_URL . htmlspecialchars($driverData['resume_file']); ?>" class="btn-secondary" target="_blank">Προβολή Βιογραφικού</a>
                    <?php endif; ?>

                    <a href="<?php echo BASE_URL; ?>drivers/edit-resume" class="btn-secondary">Ενημέρωση Βιογραφικού</a>
                </div>
            </div>


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

    <!-- Καρτέλες (tabs) με περιεχόμενο προφίλ -->
    <div class="profile-tabs">
        <nav class="tabs-nav">
            <button class="tab-btn active" data-tab="overview">Επισκόπηση</button>
            <button class="tab-btn" data-tab="qualifications">Προσόντα & Πιστοποιήσεις</button>
            <button class="tab-btn" data-tab="self-assessment">Αξιολόγηση Οδηγού</button>
            <button class="tab-btn" data-tab="job-matches">Ταιριάσματα Εργασίας</button>
            <button class="tab-btn" data-tab="my-listings">Αγγελίες</button>
        </nav>

        <div class="tab-content">
            <?php include __DIR__ . '/partials/profile-tabs/overview.php'; ?>
            <?php include __DIR__ . '/partials/profile-tabs/qualifications.php'; ?>
            <?php include __DIR__ . '/partials/profile-tabs/self-assessment.php'; ?>
            <?php include __DIR__ . '/partials/profile-tabs/job-matches.php'; ?>
            <?php include __DIR__ . '/partials/profile-tabs/my-listings.php'; ?>
        </div>
    </div>
    </div>
    <?= \Drivejob\Helpers\Asset::js('js/driver-profile.js', false) ?>
</main>
<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/partials/footer.php';
?>
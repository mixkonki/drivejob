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
<?php /* Τελευταίο: υπερισχύει των παλιών κανόνων του driver-profile.css
   για τον πίνακα προσόντων που αντικαταστάθηκε από ομάδες (30/08). */ ?>
<?= \Drivejob\Helpers\Asset::css('css/driver-overview.css') ?>
<?php /* ΧΑΡΤΗΣ: Leaflet + OpenStreetMap, ΤΟΠΙΚΑ (31/08).
   Αντικατέστησε το Google Maps, που έβγαζε «For development purposes only»
   και popup σφάλματος επειδή το κλειδί δεν είχε ενεργή χρέωση. Δωρεάν,
   χωρίς κλειδί, χωρίς εξάρτηση από CDN — άρα και χωρίς αλλαγή στο CSP. */ ?>
<link rel="stylesheet" href="<?= \Drivejob\Helpers\Asset::url('vendor/leaflet/leaflet.css') ?>">
<script src="<?= \Drivejob\Helpers\Asset::url('vendor/leaflet/leaflet.js') ?>"></script>



<script>
    // Ορισμός των βασικών μεταβλητών
    const BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<?= \Drivejob\Helpers\Asset::js('js/driver-profile.js', false) ?>
<?php /* Deep links: το #skills-tab στη διεύθυνση ανοίγει την καρτέλα
   «Προσόντα», το #driving-licenses~pei_c_number ανοίγει την καρτέλα ΚΑΙ
   φωτίζει το πεδίο. Πριν, το hash δεν σήμαινε τίποτα: οι καρτέλες είναι
   JavaScript και η σελίδα άνοιγε πάντα στην πρώτη. (31/08) */ ?>
<?= \Drivejob\Helpers\Asset::js('js/tab-deeplink.js', true) ?>


<main>
    <div class="container">
        <!-- Επικεφαλίδα προφίλ με βασικές πληροφορίες και στατιστικά -->
        <div class="profile-header">
            <div class="profile-image-wrapper">
                <div class="profile-image">
                    <?php if (isset($driverData['profile_image']) && $driverData['profile_image']) : ?>
                        <img src="<?php echo BASE_URL . htmlspecialchars($driverData['profile_image']); ?>" alt="Εικόνα προφίλ">
                    <?php else : ?>
                        <img src="<?= \Drivejob\Helpers\Asset::url('img/default_profile.png') ?>" alt="Προεπιλεγμένη εικόνα προφίλ">
                    <?php endif; ?>
                </div>

            </div>

            <div class="profile-info">
                <h1><?php echo htmlspecialchars($driverData['first_name'] . ' ' . $driverData['last_name']); ?></h1>

                <?php if (isset($driverData['city']) && $driverData['city']) : ?>
                    <p class="profile-location">
                        <img src="<?= \Drivejob\Helpers\Asset::url('img/location_icon.png') ?>" alt="Τοποθεσία">
                        <?php echo htmlspecialchars($driverData['city'] . ', ' . $driverData['country']); ?>
                    </p>
                <?php endif; ?>

                <?php
                /*
                 * ΑΞΙΟΛΟΓΗΣΕΙΣ — 30/08.
                 *
                 * Πριν: πέντε άδεια αστέρια και «0.0 (0 αξιολογήσεις)». Ίδιο
                 * πρόβλημα με τα παλιά στατιστικά: ένας δείκτης στο μηδέν
                 * διαβάζεται ως ΚΑΚΗ βαθμολογία, όχι ως απουσία βαθμολογίας.
                 * Ο νέος οδηγός έμπαινε στο προφίλ του και έβλεπε μηδενικό.
                 */
                $rating = isset($driverData['rating']) ? (float) $driverData['rating'] : 0;
                $ratingCount = (int) ($driverData['rating_count'] ?? 0);
                ?>
                <?php if ($ratingCount > 0) : ?>
                    <div class="driver-rating">
                        <div class="rating-stars">
                            <?php
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
                        <span class="rating-count">(<?php echo $ratingCount; ?> <?php echo $ratingCount === 1 ? 'αξιολόγηση' : 'αξιολογήσεις'; ?>)</span>
                    </div>
                <?php else : ?>
                    <p class="rating-none">Χωρίς αξιολογήσεις ακόμη</p>
                <?php endif; ?>

                <?php /* ΔΙΑΘΕΣΙΜΟΤΗΤΑ — μετακόμισε εδώ από την πλαϊνή στήλη (30/08).
                   Ήταν κάρτα με τίτλο «Κατάσταση Διαθεσιμότητας», εικονίδιο και
                   μια πρόταση οδηγιών· τρία στοιχεία για μία λέξη. Ως σήμα
                   δίπλα στο όνομα το βλέπει και ο ίδιος και ο εργοδότης. */ ?>
                <p class="avail-pill <?php echo !empty($driverData['available_for_work']) ? 'is-on' : 'is-off'; ?>">
                    <span class="avail-dot" aria-hidden="true"></span>
                    <?php echo !empty($driverData['available_for_work']) ? 'Διαθέσιμος για εργασία' : 'Μη διαθέσιμος'; ?>
                </p>

                <?php
                /*
                 * ΕΤΗ ΕΜΠΕΙΡΙΑΣ — ΜΙΑ ΠΗΓΗ (31/08).
                 *
                 * Πριν: η κεφαλίδα έδειχνε τη στήλη `experience_years`
                 * («8 έτη») και η ενότητα Προϋπηρεσία το άθροισμα των
                 * εγγραφών («7 έτη 8 μήνες») — δύο διαφορετικά νούμερα
                 * για το ίδιο πράγμα, στην ίδια οθόνη. Η στήλη έμεινε
                 * ως κρυφό πεδίο όταν αφαιρέθηκε από τη φόρμα (25/08)
                 * και δεν συγχρονίζεται ποτέ.
                 *
                 * Τώρα δείχνεται ΤΟ ΑΘΡΟΙΣΜΑ ΤΗΣ ΠΡΟΫΠΗΡΕΣΙΑΣ — αυτό που
                 * ο οδηγός μπορεί να τεκμηριώσει και αυτό που μπαίνει
                 * στο βιογραφικό. Η στήλη μένει μόνο ως εφεδρεία για
                 * παλιά προφίλ χωρίς καταχωρημένη προϋπηρεσία.
                 */
                $expMonths = (int) ($cv['experience']['total_months'] ?? 0);
                $expText = $expMonths > 0
                    ? ($cv['experience']['total_label'] ?? null)
                    : (!empty($driverData['experience_years']) ? $driverData['experience_years'] . ' έτη' : null);
                ?>
                <?php if ($expText) : ?>
                    <div class="experience-badge">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15.5 14"/></svg>
                        <span><?php echo htmlspecialchars($expText, ENT_QUOTES, 'UTF-8'); ?> προϋπηρεσία</span>
                    </div>
                <?php endif; ?>


            </div>

            <?php
            /*
             * Στατιστικά κεφαλίδας — ΞΑΝΑΓΡΑΦΤΗΚΑΝ 30/08.
             *
             * Έδειχναν πάντα «0 Προβολές · 0 Αιτήσεις · 0 Ταιριάσματα»
             * επειδή η $driverStats δεν οριζόταν πουθενά. Τώρα έρχεται από
             * τον DriverStatsService με πραγματικά νούμερα, και η θέση των
             * «Προβολών» (που δεν καταγράφονται πουθενά) πήρε τον δείκτη
             * πληρότητας προφίλ — ο ίδιος που δείχνει και πόσο έτοιμο
             * είναι το αυτόματο βιογραφικό.
             */
            $pc = $driverStats['completeness'] ?? null;
            $pcPercent = $pc['percent'] ?? 0;
            $pcClass = $pcPercent >= 85 ? 'is-strong' : ($pcPercent >= 55 ? 'is-mid' : 'is-weak');
            ?>
            <div class="profile-stats-header">
                <h3>Στατιστικά Προφίλ</h3>
                <ul class="profile-stats">
                    <li class="stat-completeness <?php echo $pcClass; ?>">
                        <div class="stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo (int) $pcPercent; ?>%</span>
                            <span class="stat-label">Πληρότητα Προφίλ</span>
                            <div class="stat-bar"><span style="width:<?php echo (int) $pcPercent; ?>%"></span></div>
                        </div>
                    </li>
                    <li>
                        <div class="stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo (int) ($driverStats['applications'] ?? 0); ?></span>
                            <span class="stat-label">Αιτήσεις που έκανα</span>
                        </div>
                    </li>
                    <li>
                        <div class="stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8v13H3V8"/><rect x="1" y="3" width="22" height="5"/><line x1="12" y1="22" x2="12" y2="3"/></svg>
                        </div>
                        <div class="stat-info">
                            <span class="stat-value"><?php echo (int) ($driverStats['offers'] ?? 0); ?></span>
                            <span class="stat-label">Προσφορές που έλαβα</span>
                            <?php if (!empty($driverStats['pending_offers'])) : ?>
                                <span class="stat-sub"><?php echo (int) $driverStats['pending_offers']; ?> σε αναμονή απάντησης</span>
                            <?php endif; ?>
                        </div>
                    </li>
                </ul>

                <?php /* Τι λείπει: το ποσοστό χωρίς «τι να κάνω» είναι απλώς μια κρίση. */ ?>
                <?php if (!empty($pc['missing'])) : ?>
                    <div class="profile-missing">
                        <span class="profile-missing-title">Για πληρέστερο προφίλ &amp; βιογραφικό:</span>
                        <ul>
                            <?php foreach (array_slice($pc['missing'], 0, 3) as $miss) : ?>
                                <li>
                                    <a href="<?php echo BASE_URL . htmlspecialchars($miss['link'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($miss['label'], ENT_QUOTES, 'UTF-8'); ?></a>
                                    <?php if (!empty($miss['why'])) : ?><small><?php echo htmlspecialchars($miss['why'], ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                            <?php if (count($pc['missing']) > 3) : ?>
                                <li class="profile-missing-more">…και <?php echo count($pc['missing']) - 3; ?> ακόμη</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <?php
                /*
                 * ΚΟΥΜΠΙΑ — 30/08.
                 *
                 * Πριν υπήρχαν «Προβολή Βιογραφικού» και «Ενημέρωση
                 * Βιογραφικού»: κουμπιά του ΠΑΛΙΟΥ μοντέλου, όπου το CV ήταν
                 * ένα αρχείο που ανέβαζε ο οδηγός. Αυτό το πεδίο αφαιρέθηκε
                 * από την επεξεργασία στις 25/08, με τη λογική «το προφίλ
                 * ΕΙΝΑΙ το βιογραφικό» — αλλά τα κουμπιά έμειναν, στέλνοντας
                 * σε σελίδες ενός μοντέλου που δεν ισχύει πια.
                 *
                 * Τώρα ένα κουμπί: το βιογραφικό ΠΑΡΑΓΕΤΑΙ από τα δεδομένα
                 * του προφίλ, δεν ανεβαίνει.
                 */
                ?>
                <div class="profile-image-actions">
                    <a href="<?php echo BASE_URL; ?>drivers/edit-profile" class="btn-primary">Επεξεργασία Προφίλ</a>
                    <a href="<?php echo BASE_URL; ?>drivers/cv" class="btn-secondary">Το βιογραφικό μου</a>
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
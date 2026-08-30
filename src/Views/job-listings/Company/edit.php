<?php

/**
 * Επεξεργασία αγγελίας εταιρείας. (01/09/2026)
 *
 * Λεπτό περιτύλιγμα γύρω από το κοινό _listing-form.php — την ίδια φόρμα
 * που χρησιμοποιεί και η δημιουργία. Η προηγούμενη εκδοχή αυτού του
 * αρχείου είχε τη φόρμα ΕΔΩ μέσα, ενώ η δημιουργία είχε άλλη, δική της
 * (αντιγραμμένη από τη φόρμα οδηγού, με τα γνωστά χάλια). Δύο φόρμες για
 * το ίδιο πράγμα αποκλίνουν πάντα· τώρα υπάρχει μία.
 *
 * Αναμένει: $listing, προαιρετικά $listingVehicleTypes.
 */

$pageTitle = 'Επεξεργασία Αγγελίας';
include ROOT_DIR . '/src/Views/partials/header.php';
?>

<?= \Drivejob\Helpers\Asset::css('css/job-listings.css') ?>

<main>
    <div class="container listing-form-page">

        <div class="page-head">
            <h1>Επεξεργασία αγγελίας</h1>
            <p class="muted">Οι αλλαγές γίνονται ορατές αμέσως μόλις αποθηκεύσεις.</p>
        </div>

        <?php
        $formAction = BASE_URL . 'job-listings/update/' . (int) $listing['id'];
        $formSubmitLabel = 'Αποθήκευση αλλαγών';
        include __DIR__ . '/_listing-form.php';
        ?>
    </div>
</main>

<?php
include ROOT_DIR . '/src/Views/partials/footer.php';

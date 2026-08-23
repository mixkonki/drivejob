<?php

/**
 * Λίστα αγγελιών — GET /job-listings
 *
 * Μεταβλητές από τον UnifiedJobListingController::index():
 *   $listings      — καθαρισμένες από το Visibility (χωρίς contact_*)
 *   $pagination    — total, page, limit, pages
 *   $activeFilters — ό,τι ζήτησε ο χρήστης, μόνο έγκυρες τιμές
 */

use Drivejob\Core\Session;
use Drivejob\Helpers\Asset;
use Drivejob\Helpers\VehicleTypes;

include ROOT_DIR . '/src/Views/partials/header.php';

$listings      = $listings ?? [];
$pagination    = $pagination ?? [];
$activeFilters = $activeFilters ?? [];

$total = (int) ($pagination['total'] ?? count($listings));

/**
 * Ελληνική ετικέτα τύπου απασχόλησης.
 *
 * Ήταν switch με τέσσερις περιπτώσεις και ΧΩΡΙΣ default: μια αγγελία με
 * `seasonal` έβγαζε κενό σήμα — ένα άδειο χρωματιστό πλαίσιο δίπλα στον
 * τίτλο, που έμοιαζε με σφάλμα εμφάνισης αντί για τύπο απασχόλησης.
 */
$jobTypeLabel = static function (?string $type): string {
    $map = [
        'full_time'  => 'Πλήρης απασχόληση',
        'part_time'  => 'Μερική απασχόληση',
        'contract'   => 'Σύμβαση έργου',
        'temporary'  => 'Προσωρινή',
        'seasonal'   => 'Εποχική',
        'freelance'  => 'Ελεύθερος επαγγελματίας',
        'internship' => 'Πρακτική άσκηση',
    ];

    if (empty($type)) {
        return '—';
    }

    return $map[$type] ?? ucfirst(str_replace('_', ' ', $type));
};

/**
 * Η αμοιβή με την περίοδό της.
 *
 * «1.450€ - 1.800€» χωρίς περίοδο διαβάζεται μηνιαίο από τον έναν και
 * ημερήσιο από τον άλλον. Η στήλη salary_type υπήρχε και δεν εμφανιζόταν.
 */
$salaryText = static function (array $listing): string {
    $min = (float) ($listing['salary_min'] ?? 0);
    $max = (float) ($listing['salary_max'] ?? 0);

    if ($min <= 0 && $max <= 0) {
        return '';
    }

    $periods = [
        'hourly'  => 'ανά ώρα',
        'daily'   => 'ημερησίως',
        'monthly' => 'μηνιαίως',
        'yearly'  => 'ετησίως',
    ];

    $fmt = static fn(float $v): string => number_format($v, 0, ',', '.') . '€';

    if ($min > 0 && $max > 0 && $min != $max) {
        $amount = $fmt($min) . ' – ' . $fmt($max);
    } else {
        $amount = $fmt($min > 0 ? $min : $max);
    }

    $period = $periods[$listing['salary_type'] ?? ''] ?? '';

    return $period !== '' ? "$amount $period" : $amount;
};

/*
 * Περίληψη των ενεργών φίλτρων σε ανθρώπινη γλώσσα.
 *
 * Χωρίς αυτήν, η μόνη ένδειξη ότι κάτι φιλτράρεται ήταν η κατάσταση των
 * κουτιών — που χανόταν με κάθε πλοήγηση.
 */
$filterSummary = [];

if (!empty($activeFilters['listing_type'])) {
    $filterSummary[] = $activeFilters['listing_type'] === 'job_search'
        ? 'οδηγοί που ζητούν εργασία'
        : 'εταιρείες που ζητούν οδηγό';
}
if (!empty($activeFilters['job_type'])) {
    $filterSummary[] = mb_strtolower($jobTypeLabel($activeFilters['job_type']), 'UTF-8');
}
if (!empty($activeFilters['vehicle_type'])) {
    $filterSummary[] = mb_strtolower(VehicleTypes::label($activeFilters['vehicle_type']), 'UTF-8');
}
if (!empty($activeFilters['location'])) {
    $filterSummary[] = 'περιοχή «' . $activeFilters['location'] . '»';
}
if (!empty($activeFilters['adr_certificate'])) {
    $filterSummary[] = 'με ADR';
}
if (!empty($activeFilters['operator_license'])) {
    $filterSummary[] = 'με άδεια χειριστή';
}
?>
<?= Asset::css('css/job-listings.css') ?>

<main>
    <div class="container">
        <h1>Αγγελίες εργασίας</h1>

        <?php if (Session::has('error_message')) : ?>
            <?php /* Escaped: το μήνυμα τυπωνόταν ωμό — XSS μέσω session. */ ?>
            <div class="error-message">
                <?= htmlspecialchars((string) Session::get('error_message'), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php Session::remove('error_message'); ?>
        <?php endif; ?>

        <?php include __DIR__ . '/partials/filters.php'; ?>

        <div class="job-listings-header">
            <h2>
                <?php if ($total === 0) : ?>
                    Κανένα αποτέλεσμα
                <?php elseif ($total === 1) : ?>
                    1 αγγελία
                <?php else : ?>
                    <?= number_format($total, 0, ',', '.') ?> αγγελίες
                <?php endif; ?>
            </h2>

            <?php if (!empty($filterSummary)) : ?>
                <p class="job-listings-filter-summary">
                    Φίλτρα: <?= htmlspecialchars(implode(' · ', $filterSummary), ENT_QUOTES, 'UTF-8') ?>
                    — <a href="<?= BASE_URL ?>job-listings">καθαρισμός</a>
                </p>
            <?php endif; ?>

            <?php if (!Session::has('user_id')) : ?>
                <a href="<?= BASE_URL ?>auth/login" class="btn-primary">Συνδεθείτε για να δημιουργήσετε αγγελία</a>
            <?php endif; ?>
        </div>

        <?php if (!empty($listings)) : ?>
            <div class="job-listings">
                <?php foreach ($listings as $listing) : ?>
                    <?php
                    $vType = $listing['vehicle_type']
                        ?? (is_array($listing['vehicle_types'] ?? null)
                            ? ($listing['vehicle_types'][0] ?? '')
                            : ($listing['vehicle_types'] ?? ''));

                    $place = trim((string) ($listing['location'] ?? ''));
                    $money = $salaryText($listing);

                    /*
                     * ΠΟΙΟΣ ΔΗΜΟΣΙΕΥΣΕ.
                     *
                     * Το όνομα έχει ήδη περάσει από το Visibility: ο ανώνυμος
                     * επισκέπτης παίρνει «Εταιρεία μεταφορών», ο συνδεδεμένος
                     * την πραγματική επωνυμία. Το view ΔΕΝ αποφασίζει.
                     *
                     * Στις αγγελίες οδηγών (`job_search`) δεν υπάρχει εταιρεία
                     * — εκεί δείχνουμε τον οδηγό, πάντα ανώνυμα.
                     */
                    $isDriverListing = ($listing['listing_type'] ?? '') === 'job_search';
                    $companyLabel = trim((string) ($listing['company_name'] ?? ''));
                    $identityHidden = !empty($listing['company_identity_hidden']);
                    ?>
                    <div class="job-listing-card">
                        <div class="job-listing-header">
                            <h3>
                                <a href="<?= BASE_URL ?>job-listings/show/<?= (int) $listing['id'] ?>">
                                    <?= htmlspecialchars((string) $listing['title'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </h3>

                            <?php if ($isDriverListing) : ?>
                                <p class="job-listing-company">
                                    <span class="job-listing-company-masked"
                                          title="Η ταυτότητα του οδηγού αποκαλύπτεται όταν δεχθεί προσφορά">
                                        Οδηγός #<?= (int) ($listing['driver_id'] ?? 0) ?>
                                    </span>
                                </p>
                            <?php elseif ($companyLabel !== '') : ?>
                                <p class="job-listing-company">
                                    <?php if ($identityHidden) : ?>
                                        <span class="job-listing-company-masked"
                                              title="Συνδέσου για να δεις ποια εταιρεία δημοσίευσε την αγγελία">
                                            <?= htmlspecialchars($companyLabel, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php elseif (!empty($listing['company_id'])) : ?>
                                        <a href="<?= BASE_URL ?>companies/profile/<?= (int) $listing['company_id'] ?>">
                                            <?= htmlspecialchars($companyLabel, ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    <?php else : ?>
                                        <?= htmlspecialchars($companyLabel, ENT_QUOTES, 'UTF-8') ?>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>

                            <div>
                                <?php if (!empty($listing['is_urgent'])) : ?>
                                    <span class="listing-urgent">Επείγον</span>
                                <?php endif; ?>

                                <span class="job-type <?= htmlspecialchars((string) ($listing['job_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($jobTypeLabel($listing['job_type'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                </span>

                                <span class="listing-type <?= htmlspecialchars((string) ($listing['listing_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= $isDriverListing ? 'Αναζήτηση εργασίας' : 'Προσφορά εργασίας' ?>
                                </span>
                            </div>
                        </div>

                        <div class="job-listing-details">
                            <div class="job-listing-detail">
                                <?php
                                $vehicleIcon = (string) $vType;
                                $vehicleIconSize = 20;
                                include ROOT_DIR . '/src/Views/partials/vehicle-icon.php';
                                ?>
                                <span><?= htmlspecialchars(VehicleTypes::label((string) $vType), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>

                            <?php if ($place !== '' && $place !== 'Δεν καθορίστηκε') : ?>
                                <div class="job-listing-detail">
                                    <svg class="dj-place-icon" viewBox="0 0 24 24" width="20" height="20"
                                         fill="none" stroke="currentColor" stroke-width="1.6"
                                         stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="Τοποθεσία">
                                        <path d="M12 21s-6.5-5.4-6.5-10a6.5 6.5 0 1 1 13 0c0 4.6-6.5 10-6.5 10Z"/>
                                        <circle cx="12" cy="10.6" r="2.4"/>
                                    </svg>
                                    <span><?= htmlspecialchars($place, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if ($money !== '') : ?>
                                <div class="job-listing-detail">
                                    <img src="<?= Asset::url('img/salary_icon.png') ?>" alt="" aria-hidden="true">
                                    <span><?= htmlspecialchars($money, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($listing['adr_certificate'])) : ?>
                                <div class="job-listing-detail"><span class="listing-req">ADR</span></div>
                            <?php endif; ?>

                            <?php if (!empty($listing['operator_license'])) : ?>
                                <div class="job-listing-detail"><span class="listing-req">Άδεια χειριστή</span></div>
                            <?php endif; ?>
                        </div>

                        <div class="job-listing-description">
                            <?php
                            $desc = (string) ($listing['description'] ?? '');
                            $short = mb_substr($desc, 0, 150, 'UTF-8');
                            echo nl2br(htmlspecialchars($short . (mb_strlen($desc, 'UTF-8') > 150 ? '…' : ''), ENT_QUOTES, 'UTF-8'));
                            ?>
                        </div>

                        <div class="job-listing-footer">
                            <span class="job-listing-date">
                                Δημοσιεύτηκε: <?= !empty($listing['created_at']) ? date('d/m/Y', strtotime((string) $listing['created_at'])) : '—' ?>
                            </span>
                            <a href="<?= BASE_URL ?>job-listings/show/<?= (int) $listing['id'] ?>" class="btn-primary">Περισσότερα</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php include __DIR__ . '/partials/pagination.php'; ?>

        <?php else : ?>
            <div class="no-results">
                <?php if (!empty($activeFilters)) : ?>
                    <?php /* Αδιέξοδο με φίλτρα: η έξοδος πρέπει να είναι ορατή. */ ?>
                    <p>Καμία αγγελία δεν ταιριάζει με αυτά τα κριτήρια.</p>
                    <p><a href="<?= BASE_URL ?>job-listings">Δες όλες τις αγγελίες →</a></p>
                <?php else : ?>
                    <p>Δεν υπάρχουν ενεργές αγγελίες αυτή τη στιγμή.</p>
                <?php endif; ?>

                <?php if (Session::has('user_id')) : ?>
                    <p><a href="<?= BASE_URL ?>job-listings/create" class="btn-primary">Δημιούργησε αγγελία</a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>

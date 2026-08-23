<?php

/**
 * Οι προσφορές μου — GET /job-offers/my-offers
 *
 * Η ίδια σελίδα εξυπηρετεί δύο ρόλους, γιατί είναι το ίδιο πράγμα ιδωμένο
 * από τις δύο άκρες:
 *
 *   εταιρεία → «οι προσφορές που έστειλα»
 *   οδηγός   → «οι προσφορές που έλαβα»
 *
 * Μεταβλητές από τον Driver\JobOfferController::myOffers():
 *   $offers     — company: o.* + d.first_name, d.last_name, d.profile_image, d.rating
 *                 driver:  o.* + c.company_name, c.company_logo
 *   $pagination
 */

use Drivejob\Core\CSRF;
use Drivejob\Core\Session;

include ROOT_DIR . '/src/Views/partials/header.php';
require_once __DIR__ . '/partials/status.php';

$offers = $offers ?? [];
$role   = Session::get('user_role');
$isCompany = $role === 'company';

/*
 * Η ΤΑΥΤΟΤΗΤΑ ΤΟΥ ΟΔΗΓΟΥ ΞΕΚΛΕΙΔΩΝΕΙ ΜΕ ΤΗΝ ΑΠΟΔΟΧΗ — ή αν υπάρχει ήδη
 * σχέση (ο controller το έχει ήδη κρίνει μέσω Visibility).
 *
 * Το ερώτημα φέρνει first_name/last_name γιατί τα χρειάζεται η στιγμή της
 * αποδοχής. Μέχρι τότε η εταιρεία βλέπει αριθμό — αλλιώς θα αρκούσε να
 * στέλνει προσφορές μαζικά για να μαζέψει ονόματα.
 */
$revealedDriverIds = $revealedDriverIds ?? [];

$driverLabel = function (array $offer) use ($revealedDriverIds): string {
    $did = (int) ($offer['driver_id'] ?? 0);
    $reveal = ($offer['status'] ?? '') === 'accepted' || isset($revealedDriverIds[$did]);

    if ($reveal) {
        $name = trim(($offer['first_name'] ?? '') . ' ' . ($offer['last_name'] ?? ''));
        if ($name !== '') {
            return $name;
        }
    }

    return 'Οδηγός #' . $did;
};

// Η findByDriver/findByCompany επιστρέφει last_page· το κοινό partial
// σελιδοποίησης περιμένει total_pages.
if (isset($pagination['last_page']) && !isset($pagination['total_pages'])) {
    $pagination['total_pages'] = (int) $pagination['last_page'];
}
$paginationBase = BASE_URL . 'job-offers/my-offers';
?>

<?php include __DIR__ . '/partials/styles.php'; ?>

<main class="app-page">
    <h1><?= $isCompany ? 'Προσφορές που έστειλα' : 'Προσφορές που έλαβα' ?></h1>
    <p class="app-lead">
        <?= $isCompany
            ? 'Οι προσφορές εργασίας που έχεις στείλει σε οδηγούς, από τη νεότερη προς την παλαιότερη.'
            : 'Εταιρείες που είδαν την αγγελία σου και σου προτείνουν θέση. Απαντάς εσύ.' ?>
    </p>

    <?php include ROOT_DIR . '/src/Views/job-applications/partials/messages.php'; ?>

    <?php if (empty($offers)): ?>
        <div class="app-empty">
            <?php if ($isCompany): ?>
                <p>Δεν έχεις στείλει καμία προσφορά ακόμη.</p>
                <p><a href="<?= BASE_URL ?>job-listings?listing_type=job_search">Δες ποιοι οδηγοί ψάχνουν εργασία →</a></p>
            <?php else: ?>
                <p>Δεν έχεις λάβει καμία προσφορά ακόμη.</p>
                <p><a href="<?= BASE_URL ?>job-listings/create">Δημοσίευσε ότι ψάχνεις εργασία →</a></p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <table class="app-table">
            <thead>
                <tr>
                    <th><?= $isCompany ? 'Οδηγός' : 'Εταιρεία' ?></th>
                    <th>Θέση</th>
                    <th>Τοποθεσία</th>
                    <th>Αμοιβή</th>
                    <th>Κατάσταση</th>
                    <th>Ημερομηνία</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($offers as $offer): ?>
                <tr>
                    <td data-label="<?= $isCompany ? 'Οδηγός' : 'Εταιρεία' ?>">
                        <?php if ($isCompany): ?>
                            <?= htmlspecialchars($driverLabel($offer), ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($offer['rating'])): ?>
                                <div class="muted">★ <?= number_format((float) $offer['rating'], 1, ',', '') ?></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <?= htmlspecialchars((string) ($offer['company_name'] ?? 'Εταιρεία'), ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </td>

                    <td data-label="Θέση">
                        <?= htmlspecialchars((string) ($offer['title'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                        <div class="muted"><?= htmlspecialchars(offerJobType($offer['job_type'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
                    </td>

                    <td data-label="Τοποθεσία">
                        <?= htmlspecialchars((string) ($offer['location'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                    </td>

                    <td data-label="Αμοιβή">
                        <?= htmlspecialchars(offerSalary(
                            $offer['salary_min'] ?? null,
                            $offer['salary_max'] ?? null,
                            $offer['salary_period'] ?? null
                        ), ENT_QUOTES, 'UTF-8') ?>
                    </td>

                    <td data-label="Κατάσταση"><?= offerStatusBadge($offer['status'] ?? null) ?></td>

                    <td data-label="Ημερομηνία"><?= offerDate($offer['created_at'] ?? null) ?></td>

                    <td data-label="Ενέργειες">
                        <div class="app-actions">
                            <a class="app-btn app-btn-view"
                               href="<?= BASE_URL ?>job-offers/view/<?= (int) $offer['id'] ?>">Προβολή</a>

                            <?php if (!$isCompany && in_array($offer['status'] ?? '', ['pending', 'viewed'], true)): ?>
                                <form method="POST" action="<?= BASE_URL ?>job-offers/accept/<?= (int) $offer['id'] ?>" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= CSRF::token() ?>">
                                    <button type="submit" class="app-btn app-btn-ok">Αποδοχή</button>
                                </form>
                                <form method="POST" action="<?= BASE_URL ?>job-offers/reject/<?= (int) $offer['id'] ?>" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= CSRF::token() ?>">
                                    <button type="submit" class="app-btn app-btn-no">Απόρριψη</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php include ROOT_DIR . '/src/Views/job-applications/partials/pagination.php'; ?>
    <?php endif; ?>
</main>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>

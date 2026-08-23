<?php

/**
 * Αιτήσεις μιας συγκεκριμένης αγγελίας — GET /job-applications/listing/{id}
 *
 * Μεταβλητές από τον Company\JobApplicationController::listingApplications():
 *   $listing      — η αγγελία (έχει ήδη ελεγχθεί ότι ανήκει στην εταιρεία)
 *   $applications — ja.* + d.first_name, d.last_name, d.email, d.phone, d.city
 *   $pagination
 */

use Drivejob\Core\CSRF;

include ROOT_DIR . '/src/Views/partials/header.php';
require_once __DIR__ . '/partials/status.php';

$applications = $applications ?? [];
$listing = $listing ?? [];
$listingId = (int) ($listing['id'] ?? 0);
?>

<?php include __DIR__ . '/partials/styles.php'; ?>

<main class="app-page">
    <h1>Αιτήσεις για: <?= htmlspecialchars($listing['title'] ?? 'Αγγελία') ?></h1>
    <p class="app-lead">
        <?php if (!empty($listing['location'])): ?>
            <?= htmlspecialchars($listing['location']) ?> ·
        <?php endif; ?>
        <a href="<?= BASE_URL ?>job-listings/show/<?= $listingId ?>">Προβολή αγγελίας</a> ·
        <a href="<?= BASE_URL ?>job-applications/company-applications">Όλες οι αιτήσεις</a>
    </p>

    <?php include __DIR__ . '/partials/messages.php'; ?>

    <?php if (empty($applications)): ?>
        <div class="app-empty">
            <p>Κανείς δεν έχει κάνει αίτηση σε αυτή την αγγελία ακόμη.</p>
        </div>
    <?php else: ?>
        <table class="app-table">
            <thead>
                <tr>
                    <th>Υποψήφιος</th>
                    <th>Επικοινωνία</th>
                    <th>Μήνυμα</th>
                    <th>Υποβλήθηκε</th>
                    <th>Κατάσταση</th>
                    <th>Ενέργειες</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($applications as $app):
                $name = trim(($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));
                $isPending = ($app['status'] ?? '') === 'pending';
                $message = trim((string) ($app['message'] ?? ''));
            ?>
                <tr>
                    <td data-label="Υποψήφιος">
                        <a href="<?= BASE_URL ?>drivers/profile/<?= (int) $app['driver_id'] ?>">
                            <?= htmlspecialchars($name !== '' ? $name : 'Οδηγός #' . $app['driver_id']) ?>
                        </a>
                        <?php if (!empty($app['city'])): ?>
                            <div class="muted"><?= htmlspecialchars($app['city']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td data-label="Επικοινωνία">
                        <?php if (!empty($app['email'])): ?>
                            <div><?= htmlspecialchars($app['email']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($app['phone'])): ?>
                            <div class="muted"><?= htmlspecialchars($app['phone']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td data-label="Μήνυμα">
                        <?php if ($message !== ''): ?>
                            <span class="muted"><?= htmlspecialchars(mb_strimwidth($message, 0, 90, '…')) ?></span>
                        <?php else: ?>
                            <span class="muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Υποβλήθηκε"><?= applicationDate($app['created_at'] ?? null) ?></td>
                    <td data-label="Κατάσταση"><?= applicationStatusBadge($app['status'] ?? null) ?></td>
                    <td data-label="Ενέργειες">
                        <div class="app-actions">
                            <a class="app-btn app-btn-view"
                               href="<?= BASE_URL ?>job-applications/view/<?= (int) $app['id'] ?>">Προβολή</a>

                            <?php if ($isPending): ?>
                                <form method="post"
                                      action="<?= BASE_URL ?>job-applications/accept/<?= (int) $app['id'] ?>"
                                      onsubmit="return confirm('Να γίνει αποδεκτή η αίτηση;');">
                                    <input type="hidden" name="csrf_token" value="<?= CSRF::token() ?>">
                                    <button type="submit" class="app-btn app-btn-ok">Αποδοχή</button>
                                </form>
                                <form method="post"
                                      action="<?= BASE_URL ?>job-applications/reject/<?= (int) $app['id'] ?>"
                                      onsubmit="return confirm('Να απορριφθεί η αίτηση;');">
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

        <?php
        $paginationBase = BASE_URL . 'job-applications/listing/' . $listingId;
        include __DIR__ . '/partials/pagination.php';
        ?>
    <?php endif; ?>
</main>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>

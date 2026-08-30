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
                $status = (string) ($app['status'] ?? '');
                $isPending = in_array($status, ['pending', 'viewed'], true);
                $isShortlisted = $status === 'shortlisted';
                $message = trim((string) ($app['message'] ?? ''));

                /*
                 * ΜΑΣΚΑΡΙΣΜΑ (01/09): η στήλη έδειχνε email και τηλέφωνο
                 * ΚΑΘΕ αιτούντος — δηλαδή μια απλή αίτηση χάριζε στην
                 * εταιρεία τα πλήρη στοιχεία, παρακάμπτοντας το μοντέλο
                 * «πλήρη στοιχεία μετά την προεπιλογή» που ήδη τηρεί το
                 * προφίλ. Ίδιος κανόνας, ίδιες σταθερές (ENGAGED_STATUSES).
                 */
                $engaged = in_array($status, ['shortlisted', 'hired'], true);
                $shownEmail = $engaged ? ($app['email'] ?? '') : \Drivejob\Services\Visibility::maskEmail($app['email'] ?? null);
                $shownPhone = $engaged ? ($app['phone'] ?? '') : \Drivejob\Services\Visibility::maskPhone($app['phone'] ?? null);
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
                        <?php if (!empty($shownEmail)): ?>
                            <div><?= htmlspecialchars($shownEmail) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($shownPhone)): ?>
                            <div class="muted"><?= htmlspecialchars($shownPhone) ?></div>
                        <?php endif; ?>
                        <?php if (!$engaged): ?>
                            <div class="muted" style="font-size:.75rem;">πλήρη στοιχεία μετά την προεπιλογή</div>
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

                            <?php /* Η ΠΡΟΕΠΙΛΟΓΗ είναι το ενδιάμεσο βήμα που έλειπε
                               (01/09): το route υπήρχε, κουμπί όχι — η εταιρεία
                               πήγαινε από «νέα» κατευθείαν σε «πρόσληψη». Η
                               προεπιλογή είναι και το κλειδί της ιδιωτικότητας:
                               ΑΥΤΗ ξεκλειδώνει τα πλήρη στοιχεία επικοινωνίας. */ ?>
                            <?php if ($isPending): ?>
                                <form method="post"
                                      action="<?= BASE_URL ?>job-applications/shortlist/<?= (int) $app['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= CSRF::token() ?>">
                                    <button type="submit" class="app-btn app-btn-ok">Προεπιλογή</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($isShortlisted): ?>
                                <form method="post"
                                      action="<?= BASE_URL ?>job-applications/accept/<?= (int) $app['id'] ?>"
                                      onsubmit="return confirm('Να προσληφθεί ο οδηγός;');">
                                    <input type="hidden" name="csrf_token" value="<?= CSRF::token() ?>">
                                    <button type="submit" class="app-btn app-btn-ok">Πρόσληψη</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($isPending || $isShortlisted): ?>
                                <form method="post"
                                      action="<?= BASE_URL ?>job-applications/reject/<?= (int) $app['id'] ?>"
                                      onsubmit="return confirm('Να απορριφθεί η αίτηση;');">
                                    <input type="hidden" name="csrf_token" value="<?= CSRF::token() ?>">
                                    <button type="submit" class="app-btn app-btn-no">Απόρριψη</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?= BASE_URL ?>companies/message-driver">
                                <input type="hidden" name="csrf_token" value="<?= CSRF::token() ?>">
                                <input type="hidden" name="driver_id" value="<?= (int) $app['driver_id'] ?>">
                                <button type="submit" class="app-btn app-btn-view">Μήνυμα</button>
                            </form>
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

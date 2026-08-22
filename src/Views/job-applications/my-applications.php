<?php

/**
 * Οι αιτήσεις του οδηγού — GET /job-applications/my-applications
 *
 * Μεταβλητές από τον Driver\JobApplicationController::myApplications():
 *   $applications — ja.* + jl.title, jl.location, jl.job_type, c.company_name
 *   $pagination
 */

use Drivejob\Core\CSRF;

include ROOT_DIR . '/src/Views/partials/header.php';
require_once __DIR__ . '/partials/status.php';

$applications = $applications ?? [];
?>

<?php include __DIR__ . '/partials/styles.php'; ?>

<main class="app-page">
    <h1>Οι αιτήσεις μου</h1>
    <p class="app-lead">Οι αγγελίες στις οποίες έχεις κάνει αίτηση και σε ποιο στάδιο βρίσκεται η καθεμία.</p>

    <?php include __DIR__ . '/partials/messages.php'; ?>

    <?php if (empty($applications)): ?>
        <div class="app-empty">
            <p>Δεν έχεις κάνει καμία αίτηση ακόμη.</p>
            <p><a href="<?= BASE_URL ?>job-listings">Δες τις διαθέσιμες αγγελίες →</a></p>
        </div>
    <?php else: ?>
        <table class="app-table">
            <thead>
                <tr>
                    <th>Αγγελία</th>
                    <th>Εταιρεία</th>
                    <th>Τοποθεσία</th>
                    <th>Υποβλήθηκε</th>
                    <th>Κατάσταση</th>
                    <th>Ενέργειες</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($applications as $app): ?>
                <tr>
                    <td data-label="Αγγελία">
                        <a href="<?= BASE_URL ?>job-listings/show/<?= (int) $app['job_listing_id'] ?>">
                            <?= htmlspecialchars($app['title'] ?? 'Αγγελία #' . $app['job_listing_id']) ?>
                        </a>
                        <?php if (!empty($app['job_type'])): ?>
                            <div class="muted"><?= htmlspecialchars(applicationJobType($app['job_type'])) ?></div>
                        <?php endif; ?>
                    </td>
                    <td data-label="Εταιρεία"><?= htmlspecialchars($app['company_name'] ?? '—') ?></td>
                    <td data-label="Τοποθεσία"><?= htmlspecialchars($app['location'] ?? '—') ?></td>
                    <td data-label="Υποβλήθηκε"><?= applicationDate($app['created_at'] ?? null) ?></td>
                    <td data-label="Κατάσταση"><?= applicationStatusBadge($app['status'] ?? null) ?></td>
                    <td data-label="Ενέργειες">
                        <div class="app-actions">
                            <a class="app-btn app-btn-view"
                               href="<?= BASE_URL ?>job-applications/view/<?= (int) $app['id'] ?>">Προβολή</a>

                            <?php if (($app['status'] ?? '') === 'pending'): ?>
                                <form method="post"
                                      action="<?= BASE_URL ?>job-applications/withdraw/<?= (int) $app['id'] ?>"
                                      onsubmit="return confirm('Να αποσυρθεί η αίτηση; Η ενέργεια δεν αναιρείται.');">
                                    <input type="hidden" name="csrf_token" value="<?= CSRF::generateToken() ?>">
                                    <button type="submit" class="app-btn app-btn-no">Απόσυρση</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        $paginationBase = BASE_URL . 'job-applications/my-applications';
        include __DIR__ . '/partials/pagination.php';
        ?>
    <?php endif; ?>
</main>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>

<?php

/**
 * Αιτήσεις προς την εταιρεία — GET /job-applications/company-applications
 *
 * Μεταβλητές από τον Company\JobApplicationController::myApplications():
 *   $applications — ja.* + jl.title, jl.location, jl.job_type
 *                        + d.first_name, d.last_name, d.email, d.phone, d.city
 *   $pagination
 */

include ROOT_DIR . '/src/Views/partials/header.php';
require_once __DIR__ . '/partials/status.php';

$applications = $applications ?? [];
?>

<?php include __DIR__ . '/partials/styles.php'; ?>

<main class="app-page">
    <h1>Αιτήσεις υποψηφίων</h1>
    <p class="app-lead">Όλες οι αιτήσεις που έχουν υποβληθεί στις αγγελίες σου, από τη νεότερη προς την παλαιότερη.</p>

    <?php include __DIR__ . '/partials/messages.php'; ?>

    <?php if (empty($applications)): ?>
        <div class="app-empty">
            <p>Δεν έχει υποβληθεί καμία αίτηση ακόμη.</p>
            <p><a href="<?= BASE_URL ?>job-listings/create">Δημιούργησε νέα αγγελία →</a></p>
        </div>
    <?php else: ?>
        <table class="app-table">
            <thead>
                <tr>
                    <th>Υποψήφιος</th>
                    <th>Αγγελία</th>
                    <th>Επικοινωνία</th>
                    <th>Υποβλήθηκε</th>
                    <th>Κατάσταση</th>
                    <th>Ενέργειες</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($applications as $app):
                $name = trim(($app['first_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));
                // Ίδιο μασκάρισμα με τη σελίδα ανά αγγελία (01/09):
                // πλήρη στοιχεία μόνο μετά την προεπιλογή.
                $engaged = in_array((string) ($app['status'] ?? ''), ['shortlisted', 'hired'], true);
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
                    <td data-label="Αγγελία">
                        <a href="<?= BASE_URL ?>job-listings/show/<?= (int) $app['job_listing_id'] ?>">
                            <?= htmlspecialchars($app['title'] ?? 'Αγγελία #' . $app['job_listing_id']) ?>
                        </a>
                        <?php if (!empty($app['location'])): ?>
                            <div class="muted"><?= htmlspecialchars($app['location']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td data-label="Επικοινωνία">
                        <?php if (!empty($shownEmail)): ?>
                            <div><?= htmlspecialchars($shownEmail) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($shownPhone)): ?>
                            <div class="muted"><?= htmlspecialchars($shownPhone) ?></div>
                        <?php endif; ?>
                    </td>
                    <td data-label="Υποβλήθηκε"><?= applicationDate($app['created_at'] ?? null) ?></td>
                    <td data-label="Κατάσταση"><?= applicationStatusBadge($app['status'] ?? null) ?></td>
                    <td data-label="Ενέργειες">
                        <div class="app-actions">
                            <a class="app-btn app-btn-view"
                               href="<?= BASE_URL ?>job-applications/view/<?= (int) $app['id'] ?>">Προβολή</a>
                            <a class="app-btn app-btn-quiet"
                               href="<?= BASE_URL ?>job-applications/listing/<?= (int) $app['job_listing_id'] ?>">Όλες της αγγελίας</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        $paginationBase = BASE_URL . 'job-applications/company-applications';
        include __DIR__ . '/partials/pagination.php';
        ?>
    <?php endif; ?>
</main>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>

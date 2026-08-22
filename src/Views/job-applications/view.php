<?php

/**
 * Προβολή μίας αίτησης — GET /job-applications/view/{id}
 *
 * Την βλέπουν και οι δύο πλευρές· η πρόσβαση έχει ήδη ελεγχθεί στον
 * Driver\JobApplicationController::viewApplication().
 *
 * Μεταβλητές: $application, $listing, $driver, $company
 */

use Drivejob\Core\CSRF;
use Drivejob\Core\Session;

include ROOT_DIR . '/src/Views/partials/header.php';
require_once __DIR__ . '/partials/status.php';

$application = $application ?? [];
$listing = $listing ?? [];
$driver = $driver ?? [];
$company = $company ?? [];

$viewerRole = Session::get('user_role');
$isDriver = $viewerRole === 'driver';
$isCompany = $viewerRole === 'company';
$isPending = ($application['status'] ?? '') === 'pending';
$applicationId = (int) ($application['id'] ?? 0);
$driverName = trim(($driver['first_name'] ?? '') . ' ' . ($driver['last_name'] ?? ''));
$message = trim((string) ($application['message'] ?? ''));
?>

<?php include __DIR__ . '/partials/styles.php'; ?>

<main class="app-page">
    <h1>Αίτηση #<?= $applicationId ?></h1>
    <p class="app-lead">
        <?= applicationStatusBadge($application['status'] ?? null) ?>
        &nbsp;Υποβλήθηκε <?= applicationDate($application['created_at'] ?? null) ?>
        <?php if (!empty($application['updated_at']) && $application['updated_at'] !== ($application['created_at'] ?? null)): ?>
            · Ενημερώθηκε <?= applicationDate($application['updated_at']) ?>
        <?php endif; ?>
    </p>

    <?php include __DIR__ . '/partials/messages.php'; ?>

    <div class="app-cards">
        <section class="app-card">
            <h2>Η αγγελία</h2>
            <?php if (!empty($listing)): ?>
                <dl>
                    <dt>Τίτλος</dt>
                    <dd>
                        <a href="<?= BASE_URL ?>job-listings/show/<?= (int) ($listing['id'] ?? 0) ?>">
                            <?= htmlspecialchars($listing['title'] ?? '—') ?>
                        </a>
                    </dd>
                    <dt>Τοποθεσία</dt><dd><?= htmlspecialchars($listing['location'] ?? '—') ?></dd>
                    <dt>Τύπος</dt><dd><?= htmlspecialchars(applicationJobType($listing['job_type'] ?? null)) ?></dd>
                    <?php if (!empty($company['company_name'])): ?>
                        <dt>Εταιρεία</dt><dd><?= htmlspecialchars($company['company_name']) ?></dd>
                    <?php endif; ?>
                </dl>
            <?php else: ?>
                <p class="muted">Η αγγελία δεν είναι πλέον διαθέσιμη.</p>
            <?php endif; ?>
        </section>

        <section class="app-card">
            <h2>Ο υποψήφιος</h2>
            <?php if (!empty($driver)): ?>
                <dl>
                    <dt>Όνομα</dt>
                    <dd>
                        <a href="<?= BASE_URL ?>drivers/profile/<?= (int) ($driver['id'] ?? 0) ?>">
                            <?= htmlspecialchars($driverName !== '' ? $driverName : '—') ?>
                        </a>
                    </dd>
                    <?php if ($isCompany): ?>
                        <dt>Email</dt><dd><?= htmlspecialchars($driver['email'] ?? '—') ?></dd>
                        <dt>Τηλέφωνο</dt><dd><?= htmlspecialchars($driver['phone'] ?? '—') ?></dd>
                    <?php endif; ?>
                    <dt>Πόλη</dt><dd><?= htmlspecialchars($driver['city'] ?? '—') ?></dd>
                    <?php if (isset($driver['experience_years']) && $driver['experience_years'] !== null): ?>
                        <dt>Εμπειρία</dt><dd><?= (int) $driver['experience_years'] ?> έτη</dd>
                    <?php endif; ?>
                </dl>
            <?php else: ?>
                <p class="muted">Ο λογαριασμός του οδηγού δεν είναι πλέον διαθέσιμος.</p>
            <?php endif; ?>
        </section>
    </div>

    <section class="app-card" style="margin-top:1.5rem;">
        <h2>Μήνυμα υποψηφίου</h2>
        <?php if ($message !== ''): ?>
            <div class="app-message"><?= htmlspecialchars($message) ?></div>
        <?php else: ?>
            <p class="muted">Δεν συνοδεύτηκε από μήνυμα.</p>
        <?php endif; ?>
    </section>

    <div class="app-actions" style="margin-top:1.5rem;">
        <?php if ($isCompany && $isPending): ?>
            <form method="post" action="<?= BASE_URL ?>job-applications/accept/<?= $applicationId ?>"
                  onsubmit="return confirm('Να γίνει αποδεκτή η αίτηση;');">
                <input type="hidden" name="csrf_token" value="<?= CSRF::generateToken() ?>">
                <button type="submit" class="app-btn app-btn-ok">Αποδοχή αίτησης</button>
            </form>
            <form method="post" action="<?= BASE_URL ?>job-applications/reject/<?= $applicationId ?>"
                  onsubmit="return confirm('Να απορριφθεί η αίτηση;');">
                <input type="hidden" name="csrf_token" value="<?= CSRF::generateToken() ?>">
                <button type="submit" class="app-btn app-btn-no">Απόρριψη</button>
            </form>
        <?php endif; ?>

        <?php if ($isDriver && $isPending): ?>
            <form method="post" action="<?= BASE_URL ?>job-applications/withdraw/<?= $applicationId ?>"
                  onsubmit="return confirm('Να αποσυρθεί η αίτηση; Η ενέργεια δεν αναιρείται.');">
                <input type="hidden" name="csrf_token" value="<?= CSRF::generateToken() ?>">
                <button type="submit" class="app-btn app-btn-no">Απόσυρση αίτησης</button>
            </form>
        <?php endif; ?>

        <a class="app-btn app-btn-quiet"
           href="<?= BASE_URL ?><?= $isCompany ? 'job-applications/company-applications' : 'job-applications/my-applications' ?>">
            ← Επιστροφή στη λίστα
        </a>
    </div>
</main>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>

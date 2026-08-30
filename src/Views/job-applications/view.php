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
$status = (string) ($application['status'] ?? '');
$isPending = $status === 'pending';

/*
 * ΠΟΙΕΣ ΕΝΕΡΓΕΙΕΣ ΕΙΝΑΙ ΑΚΟΜΗ ΔΥΝΑΤΕΣ.
 *
 * Τα κουμπιά εμφανίζονταν μόνο όταν η αίτηση ήταν `pending`. Μόλις η
 * εταιρεία άνοιγε τη σελίδα και η αίτηση περνούσε σε άλλη κατάσταση, ΟΛΑ
 * τα κουμπιά εξαφανίζονταν — η σελίδα γινόταν αδιέξοδο χωρίς εξήγηση.
 *
 * Η διαδικασία έχει τρία στάδια που μπορούν να συνεχίσουν: μια αίτηση σε
 * αναμονή, μια που είδε η εταιρεία, και μια σε προεπιλογή. Οι δύο τελικές
 * καταστάσεις — πρόσληψη και απόρριψη — δεν έχουν επόμενο βήμα.
 */
$openStatuses = ['pending', 'viewed', 'shortlisted'];
$isOpen = in_array($status, $openStatuses, true);
$canShortlist = $isCompany && in_array($status, ['pending', 'viewed'], true);
$canDecide = $isCompany && $isOpen;
$canWithdraw = $isDriver && $isOpen;
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
                        <?php
                        // Πλήρη στοιχεία μόνο μετά την προεπιλογή (01/09) —
                        // ίδιος κανόνας με προφίλ και λίστες αιτήσεων.
                        $engaged = in_array((string) ($application['status'] ?? ''), ['shortlisted', 'hired'], true);
                        $shownEmail = $engaged ? ($driver['email'] ?? '—') : \Drivejob\Services\Visibility::maskEmail($driver['email'] ?? null);
                        $shownPhone = $engaged ? ($driver['phone'] ?? '—') : \Drivejob\Services\Visibility::maskPhone($driver['phone'] ?? null);
                        ?>
                        <dt>Email</dt><dd><?= htmlspecialchars($shownEmail ?: '—') ?></dd>
                        <dt>Τηλέφωνο</dt><dd><?= htmlspecialchars($shownPhone ?: '—') ?></dd>
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
        <?php if ($canShortlist): ?>
            <form method="post" action="<?= BASE_URL ?>job-applications/shortlist/<?= $applicationId ?>"
                  onsubmit="return confirm('Να μπει ο υποψήφιος σε προεπιλογή; Τα στοιχεία επικοινωνίας θα γίνουν αμοιβαία ορατά.');">
                <input type="hidden" name="csrf_token" value="<?= CSRF::token() ?>">
                <button type="submit" class="app-btn app-btn-mid">Προεπιλογή &amp; επικοινωνία</button>
            </form>
        <?php endif; ?>

        <?php if ($canDecide): ?>
            <form method="post" action="<?= BASE_URL ?>job-applications/accept/<?= $applicationId ?>"
                  onsubmit="return confirm('Να γίνει αποδεκτή η αίτηση;');">
                <input type="hidden" name="csrf_token" value="<?= CSRF::token() ?>">
                <button type="submit" class="app-btn app-btn-ok">Πρόσληψη</button>
            </form>
            <form method="post" action="<?= BASE_URL ?>job-applications/reject/<?= $applicationId ?>"
                  onsubmit="return confirm('Να απορριφθεί η αίτηση;');">
                <input type="hidden" name="csrf_token" value="<?= CSRF::token() ?>">
                <button type="submit" class="app-btn app-btn-no">Απόρριψη</button>
            </form>
        <?php endif; ?>

        <?php if ($isCompany && !$isOpen): ?>
            <p class="muted" style="margin:0 0 .5rem">
                <?= $status === 'hired'
                    ? 'Ο υποψήφιος έχει προσληφθεί. Η διαδικασία ολοκληρώθηκε.'
                    : ($status === 'withdrawn'
                        ? 'Ο οδηγός απέσυρε την αίτησή του.'
                        : 'Η αίτηση έχει απορριφθεί.') ?>
            </p>
        <?php endif; ?>

        <?php if ($canWithdraw): ?>
            <form method="post" action="<?= BASE_URL ?>job-applications/withdraw/<?= $applicationId ?>"
                  onsubmit="return confirm('Να αποσυρθεί η αίτηση; Η ενέργεια δεν αναιρείται.');">
                <input type="hidden" name="csrf_token" value="<?= CSRF::token() ?>">
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

<?php

/**
 * Μία προσφορά — GET /job-offers/view/{id}
 *
 * Μεταβλητές από τον Driver\JobOfferController::viewOffer():
 *   $offer   — η εγγραφή job_offers (η πρόσβαση έχει ήδη ελεγχθεί)
 *   $driver  — ο παραλήπτης
 *   $company — ο αποστολέας
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  ΤΙ ΦΑΙΝΕΤΑΙ ΚΑΙ ΠΟΤΕ
 * ══════════════════════════════════════════════════════════════════════════
 *
 * Ο οδηγός βλέπει την εταιρεία με το όνομά της από την πρώτη στιγμή: αυτή
 * ξεκίνησε την επαφή και δεν έχει τίποτα να κρύψει — άλλωστε το ζητούμενο
 * είναι να πειστεί ο οδηγός.
 *
 * Η εταιρεία βλέπει «Οδηγός #84» μέχρι την αποδοχή. Η ασυμμετρία είναι
 * σκόπιμη: αλλιώς η αποστολή προσφορών γίνεται φθηνός τρόπος συλλογής
 * στοιχείων επικοινωνίας.
 */

use Drivejob\Core\CSRF;
use Drivejob\Core\Session;

include ROOT_DIR . '/src/Views/partials/header.php';
require_once __DIR__ . '/partials/status.php';

$offer   = $offer ?? [];
$driver  = $driver ?? [];
$company = $company ?? [];

$role      = Session::get('user_role');
$isCompany = $role === 'company';
$isDriver  = $role === 'driver';
$status    = (string) ($offer['status'] ?? '');
$accepted  = $status === 'accepted';

// Ο controller έχει ήδη κρίνει (αποδοχή Ή προϋπάρχουσα σχέση μέσω Visibility).
$canRevealDriver = $canRevealDriver ?? $accepted;

$driverName = trim(($driver['first_name'] ?? '') . ' ' . ($driver['last_name'] ?? ''));
$driverLabel = ($canRevealDriver && $driverName !== '')
    ? $driverName
    : 'Οδηγός #' . (int) ($offer['driver_id'] ?? 0);

$attachments = [
    'document_path'          => 'Έγγραφο προσφοράς',
    'contract_template_path' => 'Σχέδιο σύμβασης',
    'job_description_path'   => 'Περιγραφή θέσης',
    'company_brochure_path'  => 'Εταιρικό έντυπο',
];

$canAnswer = $isDriver && in_array($status, ['pending', 'viewed'], true);
?>

<?php include __DIR__ . '/partials/styles.php'; ?>

<main class="app-page">
    <h1><?= htmlspecialchars((string) ($offer['title'] ?? 'Προσφορά εργασίας'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="app-lead">
        <?= offerStatusBadge($status) ?>
        &nbsp;Στάλθηκε <?= offerDate($offer['created_at'] ?? null) ?>
    </p>

    <?php include ROOT_DIR . '/src/Views/job-applications/partials/messages.php'; ?>

    <div class="app-cards">
        <div class="app-card">
            <h2>Η θέση</h2>
            <dl>
                <dt>Τύπος εργασίας</dt>
                <dd><?= htmlspecialchars(offerJobType($offer['job_type'] ?? null), ENT_QUOTES, 'UTF-8') ?></dd>

                <dt>Όχημα</dt>
                <dd><?= htmlspecialchars((string) ($offer['vehicle_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?: '—' ?></dd>

                <dt>Τοποθεσία</dt>
                <dd><?= htmlspecialchars((string) ($offer['location'] ?? ''), ENT_QUOTES, 'UTF-8') ?: '—' ?></dd>

                <dt>Αμοιβή</dt>
                <dd><?= htmlspecialchars(offerSalary(
                        $offer['salary_min'] ?? null,
                        $offer['salary_max'] ?? null,
                        $offer['salary_period'] ?? null
                    ), ENT_QUOTES, 'UTF-8') ?></dd>

                <dt>Έναρξη</dt>
                <dd><?= offerDate($offer['start_date'] ?? null, 'd/m/Y') ?></dd>
            </dl>
        </div>

        <div class="app-card">
            <h2><?= $isCompany ? 'Παραλήπτης' : 'Η εταιρεία' ?></h2>
            <?php if ($isCompany): ?>
                <dl>
                    <dt>Οδηγός</dt>
                    <dd><?= htmlspecialchars($driverLabel, ENT_QUOTES, 'UTF-8') ?></dd>

                    <dt>Εμπειρία</dt>
                    <dd><?= (int) ($driver['experience_years'] ?? 0) ?> χρόνια</dd>

                    <?php if ($canRevealDriver): ?>
                        <dt>Email</dt>
                        <dd><?= htmlspecialchars((string) ($driver['email'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>

                        <dt>Τηλέφωνο</dt>
                        <dd><?= htmlspecialchars((string) ($driver['phone'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>
                    <?php endif; ?>
                </dl>
                <?php if (!$canRevealDriver): ?>
                    <p class="muted" style="margin-top:.9rem; color:#6b7280; font-size:.85rem;">
                        Τα στοιχεία επικοινωνίας εμφανίζονται μόλις ο οδηγός δεχθεί την προσφορά.
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <dl>
                    <dt>Επωνυμία</dt>
                    <dd><?= htmlspecialchars((string) ($company['company_name'] ?? 'Εταιρεία'), ENT_QUOTES, 'UTF-8') ?></dd>

                    <dt>Πόλη</dt>
                    <dd><?= htmlspecialchars((string) ($company['city'] ?? ''), ENT_QUOTES, 'UTF-8') ?: '—' ?></dd>

                    <?php if (!empty($company['website'])): ?>
                        <dt>Ιστοσελίδα</dt>
                        <dd><a href="<?= htmlspecialchars((string) $company['website'], ENT_QUOTES, 'UTF-8') ?>"
                               target="_blank" rel="noopener noreferrer nofollow">Άνοιγμα</a></dd>
                    <?php endif; ?>

                    <?php if ($accepted): ?>
                        <dt>Email</dt>
                        <dd><?= htmlspecialchars((string) ($company['email'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>

                        <dt>Τηλέφωνο</dt>
                        <dd><?= htmlspecialchars((string) ($company['phone'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></dd>
                    <?php endif; ?>
                </dl>
            <?php endif; ?>
        </div>
    </div>

    <div class="app-card" style="margin-top:1.5rem;">
        <h2>Περιγραφή</h2>
        <div class="app-message"><?= nl2br(htmlspecialchars((string) ($offer['description'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></div>
    </div>

    <?php if (!empty($offer['benefits'])): ?>
        <div class="app-card" style="margin-top:1.5rem;">
            <h2>Παροχές</h2>
            <div class="app-message"><?= nl2br(htmlspecialchars((string) $offer['benefits'], ENT_QUOTES, 'UTF-8')) ?></div>
        </div>
    <?php endif; ?>

    <?php
    $hasFiles = false;
    foreach ($attachments as $col => $label) {
        if (!empty($offer[$col])) {
            $hasFiles = true;
            break;
        }
    }
    ?>
    <?php if ($hasFiles): ?>
        <div class="app-card" style="margin-top:1.5rem;">
            <h2>Συνημμένα</h2>
            <div class="app-files">
                <?php foreach ($attachments as $col => $label): ?>
                    <?php if (!empty($offer[$col])): ?>
                        <a href="<?= BASE_URL ?>uploads/<?= htmlspecialchars(ltrim((string) $offer[$col], '/'), ENT_QUOTES, 'UTF-8') ?>"
                           target="_blank" rel="noopener">↓ <?= $label ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="app-actions" style="margin-top:1.5rem;">
        <a class="app-btn app-btn-quiet" href="<?= BASE_URL ?>job-offers/my-offers"
           style="padding:.6rem 1.1rem;">← Πίσω στις προσφορές</a>

        <?php if ($canAnswer): ?>
            <form method="POST" action="<?= BASE_URL ?>job-offers/accept/<?= (int) ($offer['id'] ?? 0) ?>" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= CSRF::token() ?>">
                <button type="submit" class="app-btn app-btn-ok" style="padding:.6rem 1.1rem;">Αποδοχή προσφοράς</button>
            </form>
            <form method="POST" action="<?= BASE_URL ?>job-offers/reject/<?= (int) ($offer['id'] ?? 0) ?>" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= CSRF::token() ?>">
                <button type="submit" class="app-btn app-btn-no" style="padding:.6rem 1.1rem;">Απόρριψη</button>
            </form>
        <?php endif; ?>
    </div>
</main>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>

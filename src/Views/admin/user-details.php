<?php

/**
 * Στοιχεία χρήστη — GET /admin/user-details/{userId}/{userType}
 *
 * Μεταβλητές από τον AdminController::userDetails():
 *   $user     — η πλήρης εγγραφή (drivers ή companies)
 *   $activity — μετρήσεις δραστηριότητας
 *   $userType — 'driver' | 'company'
 *
 * Ο διαχειριστής βλέπει τα πλήρη στοιχεία — αυτή είναι η δουλειά του. Αλλά
 * ΔΕΝ εμφανίζεται ποτέ το password hash ή τα tokens, ακόμη κι εδώ: δεν τα
 * χρειάζεται καμία διαχειριστική απόφαση.
 */

use Drivejob\Core\CSRF;

$breadcrumb = [
    ['title' => 'Χρήστες', 'url' => BASE_URL . 'admin/users'],
    ['title' => 'Στοιχεία'],
];
include ROOT_DIR . '/src/Views/partials/admin-header.php';

$user = $user ?? [];
$activity = $activity ?? [];
$isDriver = ($userType ?? 'driver') === 'driver';

$name = $isDriver
    ? trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))
    : (string) ($user['company_name'] ?? '');

$show = fn($v) => htmlspecialchars((string) ($v ?? '—'), ENT_QUOTES, 'UTF-8') ?: '—';

$rows = $isDriver
    ? [
        'Email' => $user['email'] ?? null,
        'Τηλέφωνο' => $user['phone'] ?? null,
        'Πόλη' => $user['city'] ?? null,
        'Χρόνια εμπειρίας' => $user['experience_years'] ?? null,
        'Διαθέσιμος για εργασία' => !empty($user['available_for_work']) ? 'Ναι' : 'Όχι',
        'Άδεια οδήγησης' => $user['driving_license'] ?? null,
        'Λήξη διπλώματος' => $user['driving_license_expiry'] ?? null,
        'ADR' => !empty($user['adr_certificate']) ? 'Ναι' : 'Όχι',
        'Εγγραφή' => $user['created_at'] ?? null,
        'Τελευταία σύνδεση' => $user['last_login'] ?? null,
    ]
    : [
        'Email' => $user['email'] ?? null,
        'Τηλέφωνο' => $user['phone'] ?? null,
        'ΑΦΜ' => $user['vat_number'] ?? null,
        'Πόλη' => $user['city'] ?? null,
        'Υπεύθυνος επικοινωνίας' => $user['contact_person'] ?? null,
        'Ιστοσελίδα' => $user['website'] ?? null,
        'Μέγεθος' => $user['company_size'] ?? null,
        'Κλάδος' => $user['industry'] ?? null,
        'Εγγραφή' => $user['created_at'] ?? null,
        'Τελευταία σύνδεση' => $user['last_login'] ?? null,
    ];
?>

<style>
    .dj-detail { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; }
    .dj-panel { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 18px 20px; }
    .dj-panel h2 { font-size: 1rem; margin: 0 0 12px; padding-bottom: 8px; border-bottom: 1px solid #f1f2f4; }
    .dj-panel dl { display: grid; grid-template-columns: auto 1fr; gap: 8px 16px; margin: 0; font-size: .9rem; }
    .dj-panel dt { color: #6b7280; }
    .dj-panel dd { margin: 0; }
    .dj-act { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .dj-act .box { background: #f9fafb; border-radius: 8px; padding: 10px 12px; text-align: center; }
    .dj-act .box .v { font-size: 1.4rem; font-weight: 700; }
    .dj-act .box .l { color: #6b7280; font-size: .78rem; }
    @media (max-width: 800px) { .dj-detail { grid-template-columns: 1fr; } }
</style>

<div class="admin-container">
    <div class="admin-header">
        <h1><?= htmlspecialchars($name !== '' ? $name : ($user['email'] ?? 'Χρήστης'), ENT_QUOTES, 'UTF-8') ?></h1>
        <div class="admin-actions">
            <span class="badge badge-<?= $isDriver ? 'blue' : 'green' ?>"><?= $isDriver ? 'Οδηγός' : 'Εταιρεία' ?></span>
            <?php if (!empty($user['is_active'])) : ?>
                <span class="status-badge status-active">Ενεργός</span>
            <?php else : ?>
                <span class="status-badge status-inactive">Ανενεργός</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])) : ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <div class="dj-detail">
        <div class="dj-panel">
            <h2>Στοιχεία</h2>
            <dl>
                <?php foreach ($rows as $label => $value) : ?>
                    <dt><?= $label ?></dt>
                    <dd><?= $show($value) ?></dd>
                <?php endforeach; ?>
            </dl>
        </div>

        <div>
            <div class="dj-panel" style="margin-bottom:16px;">
                <h2>Δραστηριότητα</h2>
                <div class="dj-act">
                    <div class="box"><div class="v"><?= (int) ($activity['listings'] ?? 0) ?></div><div class="l">Αγγελίες</div></div>
                    <div class="box"><div class="v"><?= (int) ($activity['applications'] ?? 0) ?></div><div class="l">Αιτήσεις</div></div>
                    <div class="box"><div class="v"><?= (int) ($activity['hired'] ?? 0) ?></div><div class="l">Προσλήψεις</div></div>
                    <div class="box"><div class="v"><?= (int) ($activity['offers'] ?? 0) ?></div><div class="l">Προσφορές</div></div>
                </div>
            </div>

            <div class="dj-panel">
                <h2>Ενέργειες</h2>
                <form method="POST" action="<?= BASE_URL ?>admin/toggle-user-status/<?= (int) ($user['id'] ?? 0) ?>/<?= $isDriver ? 'driver' : 'company' ?>"
                      onsubmit="return confirm('Σίγουρα;');">
                    <input type="hidden" name="csrf_token" value="<?= CSRF::token() ?>">
                    <button type="submit" class="btn btn-warning" style="width:100%;">
                        <?= !empty($user['is_active']) ? 'Απενεργοποίηση λογαριασμού' : 'Ενεργοποίηση λογαριασμού' ?>
                    </button>
                </form>
                <p style="color:#6b7280; font-size:.8rem; margin:10px 0 0;">
                    Ο απενεργοποιημένος λογαριασμός δεν μπορεί να συνδεθεί. Τα δεδομένα του διατηρούνται.
                </p>
            </div>
        </div>
    </div>
</div>

<?php include ROOT_DIR . '/src/Views/partials/admin-footer.php'; ?>

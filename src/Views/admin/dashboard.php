<?php

/**
 * Admin dashboard — GET /admin/dashboard
 *
 * Μεταβλητές από τον AdminController::dashboard():
 *   $stats          — όλοι οι αριθμοί της πλατφόρμας (AdminRepository::stats)
 *   $recentUsers    — τελευταίες εγγραφές, οδηγοί και εταιρείες μαζί
 *   $recentActivity — τελευταίες αιτήσεις και προσφορές
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΞΑΝΑΓΡΑΦΤΗΚΕ
 * ══════════════════════════════════════════════════════════════════════════
 *
 * Το προηγούμενο dashboard ήταν αυτόνομη σκοτεινή σελίδα εκτός του admin
 * layout, που ζητούσε μετρήσεις από legacy APIs (/api/admin/metrics) με
 * JavaScript. Όταν τα APIs δεν απαντούσαν, ο διαχειριστής έβλεπε κενά
 * κουτιά. Τώρα οι αριθμοί έρχονται από τον server, μέσα στην ίδια σελίδα —
 * αν φορτώσει η σελίδα, φορτώνουν και οι αριθμοί.
 */

$breadcrumb = [['title' => 'Πίνακας ελέγχου']];
include ROOT_DIR . '/src/Views/partials/admin-header.php';

$stats = $stats ?? [];
$recentUsers = $recentUsers ?? [];
$recentActivity = $recentActivity ?? [];

$n = fn(string $key): string => number_format((int) ($stats[$key] ?? 0), 0, ',', '.');

$statusLabel = static function (?string $status): string {
    $map = [
        'pending' => 'Σε αναμονή', 'viewed' => 'Εξετάστηκε',
        'shortlisted' => 'Προεπιλογή', 'hired' => 'Πρόσληψη',
        'accepted' => 'Αποδοχή', 'rejected' => 'Απόρριψη',
        'withdrawn' => 'Απόσυρση', 'expired' => 'Έληξε',
    ];

    return $map[$status] ?? (string) $status;
};
?>

<style>
    .dj-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
               gap: 16px; margin-bottom: 24px; }
    .dj-kpi { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px;
              padding: 16px 18px; }
    .dj-kpi .v { font-size: 1.9rem; font-weight: 700; color: #111827; line-height: 1.1; }
    .dj-kpi .l { color: #6b7280; font-size: .85rem; margin-top: 4px; }
    .dj-kpi .s { color: #16a34a; font-size: .78rem; margin-top: 6px; }
    .dj-kpi.hot { border-left: 3px solid #b3261e; }

    .dj-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .dj-panel { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px 18px; }
    .dj-panel h2 { font-size: 1rem; margin: 0 0 12px; padding-bottom: 8px;
                   border-bottom: 1px solid #f1f2f4; }
    .dj-panel table { width: 100%; border-collapse: collapse; font-size: .88rem; }
    .dj-panel td { padding: 7px 4px; border-bottom: 1px solid #f7f7f8; vertical-align: top; }
    .dj-panel tr:last-child td { border-bottom: 0; }
    .dj-panel .muted { color: #6b7280; font-size: .8rem; }
    .dj-chip { display: inline-block; padding: 1px 8px; border-radius: 999px;
               font-size: .75rem; font-weight: 600; }
    .dj-chip.driver  { background: #dbeafe; color: #1d4ed8; }
    .dj-chip.company { background: #dcfce7; color: #15803d; }
    .dj-chip.application { background: #fef3c7; color: #92400e; }
    .dj-chip.offer   { background: #f3e8ff; color: #7c3aed; }

    @media (max-width: 900px) { .dj-cols { grid-template-columns: 1fr; } }
</style>

<div class="admin-container">
    <div class="admin-header">
        <h1>Πίνακας ελέγχου</h1>
    </div>

    <?php if (isset($_SESSION['error_message'])) : ?>
        <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error_message']) ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['success_message'])) : ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <!-- Χρήστες -->
    <div class="dj-kpis">
        <div class="dj-kpi">
            <div class="v"><?= $n('drivers_active') ?></div>
            <div class="l">Ενεργοί οδηγοί <span class="muted">/ <?= $n('drivers_total') ?> σύνολο</span></div>
            <?php if (($stats['drivers_new_7d'] ?? 0) > 0) : ?>
                <div class="s">+<?= $n('drivers_new_7d') ?> την τελευταία εβδομάδα</div>
            <?php endif; ?>
        </div>
        <div class="dj-kpi">
            <div class="v"><?= $n('drivers_available') ?></div>
            <div class="l">Διαθέσιμοι για εργασία</div>
        </div>
        <div class="dj-kpi">
            <div class="v"><?= $n('companies_active') ?></div>
            <div class="l">Ενεργές εταιρείες <span class="muted">/ <?= $n('companies_total') ?> σύνολο</span></div>
            <?php if (($stats['companies_new_7d'] ?? 0) > 0) : ?>
                <div class="s">+<?= $n('companies_new_7d') ?> την τελευταία εβδομάδα</div>
            <?php endif; ?>
        </div>
        <div class="dj-kpi">
            <div class="v"><?= $n('listings_active') ?></div>
            <div class="l"><?= $n('listings_offers') ?> από εταιρείες · <?= $n('listings_searches') ?> από οδηγούς</div>
            <?php if (($stats['listings_new_7d'] ?? 0) > 0) : ?>
                <div class="s">+<?= $n('listings_new_7d') ?> την τελευταία εβδομάδα</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Η κίνηση: το πραγματικό προϊόν ενός marketplace -->
    <div class="dj-kpis">
        <div class="dj-kpi hot">
            <div class="v"><?= $n('applications_pending') ?></div>
            <div class="l">Αιτήσεις σε εκκρεμότητα <span class="muted">/ <?= $n('applications_total') ?> σύνολο</span></div>
        </div>
        <div class="dj-kpi hot">
            <div class="v"><?= $n('offers_pending') ?></div>
            <div class="l">Προσφορές σε εκκρεμότητα <span class="muted">/ <?= $n('offers_total') ?> σύνολο</span></div>
        </div>
        <div class="dj-kpi">
            <div class="v"><?= $n('applications_hired') ?></div>
            <div class="l">Προσλήψεις μέσω αιτήσεων</div>
        </div>
        <div class="dj-kpi">
            <div class="v"><?= $n('offers_accepted') ?></div>
            <div class="l">Αποδεκτές προσφορές</div>
        </div>
    </div>

    <div class="dj-cols">
        <div class="dj-panel">
            <h2>Τελευταίες εγγραφές</h2>
            <?php if (empty($recentUsers)) : ?>
                <p class="muted">Καμία εγγραφή ακόμη.</p>
            <?php else : ?>
                <table>
                    <?php foreach ($recentUsers as $u) : ?>
                        <tr>
                            <td><span class="dj-chip <?= $u['type'] === 'driver' ? 'driver' : 'company' ?>">
                                <?= $u['type'] === 'driver' ? 'Οδηγός' : 'Εταιρεία' ?></span></td>
                            <td>
                                <a href="<?= BASE_URL ?>admin/user-details/<?= (int) $u['id'] ?>/<?= $u['type'] ?>">
                                    <?= htmlspecialchars($u['name'] !== '' ? $u['name'] : $u['email'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                                <div class="muted"><?= htmlspecialchars((string) $u['email'], ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td class="muted"><?= !empty($u['created_at']) ? date('d/m H:i', strtotime((string) $u['created_at'])) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <div class="dj-panel">
            <h2>Πρόσφατη δραστηριότητα</h2>
            <?php if (empty($recentActivity)) : ?>
                <p class="muted">Καμία αίτηση ή προσφορά ακόμη.</p>
            <?php else : ?>
                <table>
                    <?php foreach ($recentActivity as $a) : ?>
                        <tr>
                            <td><span class="dj-chip <?= $a['kind'] === 'offer' ? 'offer' : 'application' ?>">
                                <?= $a['kind'] === 'offer' ? 'Προσφορά' : 'Αίτηση' ?></span></td>
                            <td>
                                <?= htmlspecialchars((string) ($a['subject'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                                <div class="muted"><?= htmlspecialchars($statusLabel($a['status'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td class="muted"><?= !empty($a['created_at']) ? date('d/m H:i', strtotime((string) $a['created_at'])) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include ROOT_DIR . '/src/Views/partials/admin-footer.php'; ?>

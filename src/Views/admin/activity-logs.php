<?php

/**
 * Ημερολόγιο ενεργειών διαχειριστών — GET /admin/activity-logs
 *
 * Μεταβλητές: $logs, $pagination.
 *
 * Κάθε ενεργοποίηση/απενεργοποίηση χρήστη ή αγγελίας γράφεται εδώ. Όταν
 * κάποιος ρωτήσει «γιατί δεν μπαίνω στον λογαριασμό μου;», η απάντηση
 * είναι σε αυτή τη σελίδα — ποιος, τι, πότε, από ποια IP.
 */

$breadcrumb = [['title' => 'Ημερολόγιο ενεργειών']];
include ROOT_DIR . '/src/Views/partials/admin-header.php';

$logs = $logs ?? [];
$pagination = $pagination ?? [];

$actionLabel = static function (?string $action): string {
    $map = [
        'user_activated'      => 'Ενεργοποίηση χρήστη',
        'user_deactivated'    => 'Απενεργοποίηση χρήστη',
        'listing_activated'   => 'Ενεργοποίηση αγγελίας',
        'listing_deactivated' => 'Απενεργοποίηση αγγελίας',
    ];

    return $map[$action] ?? (string) $action;
};
?>

<?= \Drivejob\Helpers\Asset::css('css/admin-users.css') ?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Ημερολόγιο ενεργειών</h1>
    </div>

    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Πότε</th>
                    <th>Διαχειριστής</th>
                    <th>Ενέργεια</th>
                    <th>Αφορά</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)) : ?>
                    <?php foreach ($logs as $log) : ?>
                        <tr>
                            <td><?= !empty($log['created_at']) ? date('d/m/Y H:i', strtotime((string) $log['created_at'])) : '—' ?></td>
                            <td><?= htmlspecialchars((string) ($log['admin_email'] ?? ('#' . ($log['admin_id'] ?? '?'))), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($actionLabel($log['action'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if (!empty($log['resource_type'])) : ?>
                                    <?= htmlspecialchars((string) $log['resource_type'], ENT_QUOTES, 'UTF-8') ?>
                                    #<?= (int) ($log['resource_id'] ?? 0) ?>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string) ($log['ip_address'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="5" class="text-center">
                        Καμία καταγεγραμμένη ενέργεια ακόμη. Οι ενεργοποιήσεις και
                        απενεργοποιήσεις χρηστών και αγγελιών θα εμφανίζονται εδώ.
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($pagination['pages']) && $pagination['pages'] > 1) : ?>
        <div class="pagination">
            <?php if ($pagination['page'] > 1) : ?>
                <a href="?page=<?= $pagination['page'] - 1 ?>" class="pagination-link">← Προηγούμενη</a>
            <?php endif; ?>
            <span class="pagination-info">Σελίδα <?= $pagination['page'] ?> από <?= $pagination['pages'] ?></span>
            <?php if ($pagination['page'] < $pagination['pages']) : ?>
                <a href="?page=<?= $pagination['page'] + 1 ?>" class="pagination-link">Επόμενη →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include ROOT_DIR . '/src/Views/partials/admin-footer.php'; ?>

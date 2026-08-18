<?php
/**
 * Στατιστικά χρήσης
 *
 * Δημιουργήθηκε στο Πακέτο 9: ο SystemMonitoringController καλούσε αυτό το
 * view αλλά το αρχείο δεν υπήρχε ποτέ — η σελίδα έβγαζε «Το view δεν βρέθηκε».
 * Ο πίνακας παράγεται δυναμικά από τα κλειδιά των δεδομένων, ώστε να μη σπάει
 * αν αλλάξει το σχήμα του query.
 */

include ROOT_DIR . '/src/Views/partials/admin-header.php';

$rows = $usageData ?? [];
if (!is_array($rows)) {
    $rows = [];
}
// Κάποια queries επιστρέφουν ['data' => [...]] αντί για επίπεδη λίστα
if (isset($rows['data']) && is_array($rows['data'])) {
    $rows = $rows['data'];
}
$periods = ['1h' => 'Τελευταία ώρα', '24h' => '24 ώρες', '7d' => '7 ημέρες', '30d' => '30 ημέρες'];
$current = $period ?? '24h';
?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Στατιστικά χρήσης</h1>
        <div class="admin-actions">
            <a class="btn btn-secondary" href="<?= BASE_URL ?>admin/monitoring/dashboard">← Πίσω στο Monitoring</a>
        </div>
    </div>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error_message']) ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <form method="get" class="monitoring-filters">
        <label for="period">Περίοδος:</label>
        <select id="period" name="period" onchange="this.form.submit()">
            <?php foreach ($periods as $value => $label): ?>
                <option value="<?= $value ?>" <?= $current === $value ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($search)): ?>
            <input type="search" name="search" value="<?= htmlspecialchars((string) $search) ?>" placeholder="Αναζήτηση…">
            <button type="submit" class="btn btn-secondary">Αναζήτηση</button>
        <?php endif; ?>
    </form>

    <?php if (empty($rows)): ?>
        <p class="empty-state">Δεν υπάρχουν δεδομένα για την επιλεγμένη περίοδο.</p>
    <?php else: ?>
        <?php
        $first = reset($rows);
        $columns = is_array($first) ? array_keys($first) : ['τιμή'];
        ?>
        <p class="result-count"><?= count($rows) ?> εγγραφές</p>
        <table class="admin-table">
            <thead>
                <tr><?php foreach ($columns as $c): ?><th><?= htmlspecialchars((string) $c) ?></th><?php endforeach; ?></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $key => $row): ?>
                <tr>
                <?php if (is_array($row)): ?>
                    <?php foreach ($columns as $c): ?>
                        <?php $v = $row[$c] ?? ''; ?>
                        <td><?= htmlspecialchars(is_scalar($v) || $v === null ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE)) ?></td>
                    <?php endforeach; ?>
                <?php else: ?>
                    <td><?= htmlspecialchars((string) $row) ?></td>
                <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
    .monitoring-filters { margin: 1rem 0; display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }
    .admin-table { width: 100%; border-collapse: collapse; font-size: .9rem; }
    .admin-table th, .admin-table td { padding: .5rem .75rem; border-bottom: 1px solid #e0e0e0; text-align: left; vertical-align: top; }
    .admin-table th { background: #f5f5f5; font-weight: 600; white-space: nowrap; }
    .admin-table td { max-width: 40ch; overflow-wrap: anywhere; }
    .empty-state { padding: 2rem; text-align: center; color: #777; }
    .result-count { color: #666; font-size: .85rem; }
</style>

<?php
include ROOT_DIR . '/src/Views/partials/admin-footer.php';

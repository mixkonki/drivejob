<?php

/**
 * Διαχείριση αγγελιών — GET /admin/job-listings
 *
 * Μεταβλητές από τον AdminController::jobListings():
 *   $listings — data + pagination (AdminRepository::listings)
 *   $status, $search
 *
 * Η μόνη διοικητική ενέργεια είναι η απενεργοποίηση/ενεργοποίηση.
 * Διαγραφή από εδώ ΔΕΝ υπάρχει: η αγγελία έχει αιτήσεις και προσφορές
 * κρεμασμένες πάνω της — η απενεργοποίηση την κρύβει χωρίς να σπάει τίποτα.
 */

use Drivejob\Core\CSRF;

$breadcrumb = [['title' => 'Αγγελίες']];
include ROOT_DIR . '/src/Views/partials/admin-header.php';

$listings = $listings ?? ['data' => [], 'pagination' => []];
$status = $status ?? 'all';
$search = $search ?? '';
?>

<?= \Drivejob\Helpers\Asset::css('css/admin-users.css') ?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Διαχείριση αγγελιών</h1>
    </div>

    <div class="admin-filters">
        <form method="GET" action="<?= BASE_URL ?>admin/job-listings" class="filters-form">
            <div class="filter-group">
                <label>Κατάσταση:</label>
                <select name="status" onchange="this.form.submit()">
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Όλες</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Ενεργές</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Ανενεργές</option>
                </select>
            </div>

            <div class="filter-group search-group">
                <label>Αναζήτηση:</label>
                <input type="text" name="search" placeholder="Τίτλος, τοποθεσία, εταιρεία…"
                       value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-search">Αναζήτηση</button>
            </div>
        </form>
    </div>

    <?php if (isset($_SESSION['success_message'])) : ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success_message']) ?></div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])) : ?>
        <div class="alert alert-error"><?= htmlspecialchars($_SESSION['error_message']) ?></div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Τίτλος</th>
                    <th>Τύπος</th>
                    <th>Δημοσίευσε</th>
                    <th>Τοποθεσία</th>
                    <th>Προβολές</th>
                    <th>Αιτήσεις</th>
                    <th>Κατάσταση</th>
                    <th>Ενέργειες</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($listings['data'])) : ?>
                    <?php foreach ($listings['data'] as $l) : ?>
                        <tr>
                            <td>#<?= (int) $l['id'] ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>job-listings/show/<?= (int) $l['id'] ?>" target="_blank" rel="noopener">
                                    <?= htmlspecialchars((string) $l['title'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-<?= ($l['listing_type'] ?? '') === 'job_search' ? 'blue' : 'green' ?>">
                                    <?= ($l['listing_type'] ?? '') === 'job_search' ? 'Οδηγός ζητά' : 'Εταιρεία ζητά' ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($l['company_name'])) : ?>
                                    <?= htmlspecialchars((string) $l['company_name'], ENT_QUOTES, 'UTF-8') ?>
                                <?php elseif (!empty($l['driver_id'])) : ?>
                                    Οδηγός #<?= (int) $l['driver_id'] ?>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string) ($l['location'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) ($l['views_count'] ?? 0) ?></td>
                            <td><?= (int) ($l['applications'] ?? 0) ?></td>
                            <td>
                                <?php if (!empty($l['is_active'])) : ?>
                                    <span class="status-badge status-active">Ενεργή</span>
                                <?php else : ?>
                                    <span class="status-badge status-inactive">Ανενεργή</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="<?= BASE_URL ?>admin/toggle-listing/<?= (int) $l['id'] ?>"
                                      onsubmit="return confirm('Σίγουρα;');" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= CSRF::token() ?>">
                                    <button type="submit" class="btn btn-sm btn-warning">
                                        <?= !empty($l['is_active']) ? 'Απενεργοποίηση' : 'Ενεργοποίηση' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="9" class="text-center">Δεν βρέθηκαν αγγελίες</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $p = $listings['pagination'] ?? []; ?>
    <?php if (!empty($p['pages']) && $p['pages'] > 1) : ?>
        <div class="pagination">
            <?php if ($p['page'] > 1) : ?>
                <a href="?page=<?= $p['page'] - 1 ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>" class="pagination-link">← Προηγούμενη</a>
            <?php endif; ?>
            <span class="pagination-info">Σελίδα <?= $p['page'] ?> από <?= $p['pages'] ?> (<?= $p['total'] ?> αγγελίες)</span>
            <?php if ($p['page'] < $p['pages']) : ?>
                <a href="?page=<?= $p['page'] + 1 ?>&status=<?= $status ?>&search=<?= urlencode($search) ?>" class="pagination-link">Επόμενη →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include ROOT_DIR . '/src/Views/partials/admin-footer.php'; ?>

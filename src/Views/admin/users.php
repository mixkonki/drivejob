<?php
// Admin Users Management Page
include ROOT_DIR . '/src/Views/partials/admin-header.php';
?>

<?= \Drivejob\Helpers\Asset::css('css/admin-users.css') ?>

<div class="admin-container">
    <div class="admin-header">
        <h1>Διαχείριση Χρηστών</h1>
        <div class="admin-actions">
            <button class="btn btn-primary" onclick="exportUsers()">
                <i class="icon-export"></i> Εξαγωγή
            </button>
        </div>
    </div>

    <!-- Φίλτρα και Αναζήτηση -->
    <div class="admin-filters">
        <form method="GET" action="<?php echo BASE_URL; ?>admin/users" class="filters-form">
            <div class="filter-group">
                <label>Τύπος Χρήστη:</label>
                <select name="type" onchange="this.form.submit()">
                    <option value="all" <?php echo ($type ?? 'all') === 'all' ? 'selected' : ''; ?>>Όλοι</option>
                    <option value="driver" <?php echo ($type ?? '') === 'driver' ? 'selected' : ''; ?>>Οδηγοί</option>
                    <option value="company" <?php echo ($type ?? '') === 'company' ? 'selected' : ''; ?>>Εταιρείες</option>
                </select>
            </div>

            <div class="filter-group">
                <label>Κατάσταση:</label>
                <select name="status" onchange="this.form.submit()">
                    <option value="all" <?php echo ($status ?? 'all') === 'all' ? 'selected' : ''; ?>>Όλες</option>
                    <option value="active" <?php echo ($status ?? '') === 'active' ? 'selected' : ''; ?>>Ενεργοί</option>
                    <option value="inactive" <?php echo ($status ?? '') === 'inactive' ? 'selected' : ''; ?>>Ανενεργοί</option>
                    <option value="verified" <?php echo ($status ?? '') === 'verified' ? 'selected' : ''; ?>>Επαληθευμένοι</option>
                    <option value="unverified" <?php echo ($status ?? '') === 'unverified' ? 'selected' : ''; ?>>Μη Επαληθευμένοι</option>
                </select>
            </div>

            <div class="filter-group search-group">
                <label>Αναζήτηση:</label>
                <input type="text" name="search" placeholder="Όνομα, Email, Τηλέφωνο..."
                    value="<?php echo htmlspecialchars($search ?? ''); ?>">
                <button type="submit" class="btn btn-search">
                    <i class="icon-search"></i> Αναζήτηση
                </button>
            </div>
        </form>
    </div>

    <!-- Μηνύματα -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="icon-check"></i>
            <?php echo htmlspecialchars($_SESSION['success_message']); ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error">
            <i class="icon-error"></i>
            <?php echo htmlspecialchars($_SESSION['error_message']); ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- Πίνακας Χρηστών -->
    <div class="admin-table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Τύπος</th>
                    <th>Όνομα</th>
                    <th>Email</th>
                    <th>Τηλέφωνο</th>
                    <th>Εγγραφή</th>
                    <th>Κατάσταση</th>
                    <th>Επαλήθευση</th>
                    <th>Ενέργειες</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users['data'])): ?>
                    <?php foreach ($users['data'] as $user): ?>
                        <tr>
                            <td>#<?php echo $user['id']; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['type'] === 'driver' ? 'blue' : 'green'; ?>">
                                    <?php echo $user['type'] === 'driver' ? 'Οδηγός' : 'Εταιρεία'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="user-info">
                                    <?php if (!empty($user['profile_image']) || !empty($user['logo'])): ?>
                                        <img src="<?php echo BASE_URL . ($user['profile_image'] ?? $user['logo']); ?>"
                                            alt="Profile" class="user-avatar">
                                    <?php else: ?>
                                        <div class="user-avatar-placeholder">
                                            <?php echo mb_strtoupper(mb_substr($user['name'] ?? 'U', 0, 1, 'UTF-8'), 'UTF-8'); ?>
                                        </div>
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($user['name'] ?? 'Χωρίς όνομα'); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <span class="status-badge status-active">Ενεργός</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">Ανενεργός</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['is_verified']): ?>
                                    <span class="status-badge status-verified">
                                        <i class="icon-check"></i> Επαληθευμένος
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-unverified">
                                        <i class="icon-x"></i> Μη επαληθευμένος
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="<?php echo BASE_URL; ?>admin/user-details/<?php echo $user['id']; ?>/<?php echo $user['type']; ?>"
                                        class="btn btn-sm btn-info" title="Προβολή">
                                        <i class="icon-eye"></i>
                                    </a>
                                    <button onclick="toggleUserStatus(<?php echo $user['id']; ?>, '<?php echo $user['type']; ?>')"
                                        class="btn btn-sm btn-warning"
                                        title="<?php echo $user['is_active'] ? 'Απενεργοποίηση' : 'Ενεργοποίηση'; ?>">
                                        <i class="icon-<?php echo $user['is_active'] ? 'lock' : 'unlock'; ?>"></i>
                                    </button>
                                    <button onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo $user['type']; ?>')"
                                        class="btn btn-sm btn-danger" title="Διαγραφή">
                                        <i class="icon-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">Δεν βρέθηκαν χρήστες</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Σελιδοποίηση -->
    <?php if (!empty($users['pagination'])): ?>
        <div class="pagination">
            <?php if ($users['pagination']['page'] > 1): ?>
                <a href="?page=<?php echo $users['pagination']['page'] - 1; ?>&type=<?php echo $type; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>"
                    class="pagination-link">
                    <i class="icon-chevron-left"></i> Προηγούμενη
                </a>
            <?php endif; ?>

            <span class="pagination-info">
                Σελίδα <?php echo $users['pagination']['page']; ?> από <?php echo $users['pagination']['pages']; ?>
                (<?php echo $users['pagination']['total']; ?> χρήστες)
            </span>

            <?php if ($users['pagination']['page'] < $users['pagination']['pages']): ?>
                <a href="?page=<?php echo $users['pagination']['page'] + 1; ?>&type=<?php echo $type; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>"
                    class="pagination-link">
                    Επόμενη <i class="icon-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    function toggleUserStatus(userId, userType) {
        if (confirm('Είστε σίγουροι ότι θέλετε να αλλάξετε την κατάσταση αυτού του χρήστη;')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `<?php echo BASE_URL; ?>admin/toggle-user-status/${userId}/${userType}`;

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = 'csrf_token';
            csrfToken.value = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';

            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        }
    }

    function deleteUser(userId, userType) {
        if (confirm('ΠΡΟΣΟΧΗ: Αυτή η ενέργεια είναι μη αναστρέψιμη. Είστε σίγουροι ότι θέλετε να διαγράψετε αυτόν τον χρήστη;')) {
            // Implement delete functionality
            alert('Η λειτουργία διαγραφής δεν έχει υλοποιηθεί ακόμα για λόγους ασφαλείας.');
        }
    }

    function exportUsers() {
        // Implement export functionality
        alert('Η λειτουργία εξαγωγής θα υλοποιηθεί σύντομα.');
    }
</script>

<?php
include ROOT_DIR . '/src/Views/partials/admin-footer.php';
?>
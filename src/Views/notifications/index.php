<?php

/**
 * Οι ειδοποιήσεις μου — GET /notifications
 *
 * Μεταβλητές από τον NotificationController::index():
 *   $notifications — εγγραφές του πίνακα, νεότερες πρώτες
 *   $pagination    — total, page, limit, pages
 *   $unread        — πλήθος αδιάβαστων
 *
 * Κάθε ειδοποίηση κουβαλά τον σύνδεσμό της μέσα στο πεδίο `data` (JSON) —
 * το κλικ πάει τον χρήστη κατευθείαν στην αίτηση ή την προσφορά που τον
 * αφορά, όχι σε μια γενική σελίδα.
 */

use Drivejob\Core\CSRF;

include ROOT_DIR . '/src/Views/partials/header.php';

$notifications = $notifications ?? [];
$unread = (int) ($unread ?? 0);

$linkOf = static function (array $n): ?string {
    if (empty($n['data'])) {
        return null;
    }
    $data = json_decode((string) $n['data'], true);

    return is_array($data) && !empty($data['link']) ? (string) $data['link'] : null;
};

$when = static function (?string $value): string {
    if (empty($value)) {
        return '';
    }
    $ts = strtotime($value);
    if (!$ts) {
        return '';
    }

    /*
     * Σχετικός χρόνος για τα πρόσφατα, ημερομηνία για τα παλιά.
     * «Πριν 5 λεπτά» σημαίνει κάτι· «23/08/2026 21:14» για κάτι που έγινε
     * μόλις τώρα μοιάζει με αρχείο.
     */
    $diff = time() - $ts;
    if ($diff < 60) {
        return 'μόλις τώρα';
    }
    if ($diff < 3600) {
        return 'πριν ' . intdiv($diff, 60) . '′';
    }
    if ($diff < 86400) {
        return 'πριν ' . intdiv($diff, 3600) . ' ώρες';
    }

    return date('d/m/Y H:i', $ts);
};
?>

<?php include ROOT_DIR . '/src/Views/partials/app-styles.php'; ?>
<style>
    .ntf-item { display: flex; gap: 12px; align-items: flex-start; padding: 14px 16px;
                background: #fff; border: 1px solid #e5e7eb; border-radius: 8px;
                margin-bottom: 10px; text-decoration: none; color: inherit; }
    .ntf-item:hover { border-color: #d1d5db; }
    .ntf-item.unread { border-left: 3px solid #b3261e; background: #fffbfa; }
    .ntf-dot { flex: 0 0 8px; width: 8px; height: 8px; border-radius: 50%;
               background: #b3261e; margin-top: 7px; }
    .ntf-item:not(.unread) .ntf-dot { background: transparent; }
    .ntf-body h3 { margin: 0 0 3px; font-size: .98rem; }
    .ntf-body p { margin: 0; color: #4b5563; font-size: .9rem; }
    .ntf-time { margin-left: auto; flex: 0 0 auto; color: #9ca3af; font-size: .8rem;
                white-space: nowrap; }
    .ntf-head { display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
                margin-bottom: 1.2rem; }
    .ntf-head h1 { margin: 0; }
    .ntf-head form { margin-left: auto; }
</style>

<main class="app-page">
    <div class="ntf-head">
        <h1>Ειδοποιήσεις</h1>
        <?php if ($unread > 0) : ?>
            <span class="app-status" style="background:#b3261e1a; color:#b3261e; border:1px solid #b3261e55;">
                <?= $unread ?> αδιάβαστ<?= $unread === 1 ? 'η' : 'ες' ?>
            </span>
            <form method="POST" action="<?= BASE_URL ?>notifications/read-all">
                <input type="hidden" name="csrf_token" value="<?= CSRF::token() ?>">
                <button type="submit" class="app-btn app-btn-quiet">Όλες ως αναγνωσμένες</button>
            </form>
        <?php endif; ?>
    </div>

    <?php include ROOT_DIR . '/src/Views/job-applications/partials/messages.php'; ?>

    <?php if (empty($notifications)) : ?>
        <div class="app-empty">
            <p>Καμία ειδοποίηση ακόμη.</p>
            <p>Όταν κάτι συμβεί — νέα αίτηση, προσφορά, αποδοχή — θα το δεις εδώ.</p>
        </div>
    <?php else : ?>
        <?php foreach ($notifications as $n) : ?>
            <?php
            $link = $linkOf($n);
            $tag = $link !== null ? 'a' : 'div';
            $unreadClass = empty($n['is_read']) ? ' unread' : '';
            ?>
            <<?= $tag ?><?= $link !== null ? ' href="' . BASE_URL . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '"' : '' ?>
                class="ntf-item<?= $unreadClass ?>">
                <span class="ntf-dot"></span>
                <span class="ntf-body">
                    <h3><?= htmlspecialchars((string) ($n['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars((string) ($n['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                </span>
                <span class="ntf-time"><?= $when($n['created_at'] ?? null) ?></span>
            </<?= $tag ?>>
        <?php endforeach; ?>

        <?php
        // Το κοινό partial περιμένει total_pages/current_page.
        if (isset($pagination['pages']) && !isset($pagination['total_pages'])) {
            $pagination['total_pages'] = (int) $pagination['pages'];
            $pagination['current_page'] = (int) ($pagination['page'] ?? 1);
        }
        $paginationBase = BASE_URL . 'notifications';
        include ROOT_DIR . '/src/Views/job-applications/partials/pagination.php';
        ?>
    <?php endif; ?>
</main>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>

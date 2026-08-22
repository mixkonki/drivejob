<?php

/**
 * Σελιδοποίηση. Περιμένει $pagination (total, per_page, current_page,
 * total_pages, has_more) και $paginationBase — τη διαδρομή χωρίς παράμετρο page.
 */

$pagination = $pagination ?? [];
$totalPages = (int) ($pagination['total_pages'] ?? 0);
$current = (int) ($pagination['current_page'] ?? 1);
$base = $paginationBase ?? '';

if ($totalPages > 1):
    $separator = str_contains($base, '?') ? '&' : '?';
?>
<nav class="app-pagination" aria-label="Σελιδοποίηση">
    <?php if ($current > 1): ?>
        <a href="<?= htmlspecialchars($base . $separator . 'page=' . ($current - 1)) ?>">← Προηγούμενη</a>
    <?php endif; ?>

    <span>Σελίδα <?= $current ?> από <?= $totalPages ?></span>

    <?php if ($current < $totalPages): ?>
        <a href="<?= htmlspecialchars($base . $separator . 'page=' . ($current + 1)) ?>">Επόμενη →</a>
    <?php endif; ?>
</nav>
<?php endif; ?>

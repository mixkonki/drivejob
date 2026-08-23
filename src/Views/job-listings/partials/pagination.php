<?php

/**
 * Σελιδοποίηση που ΔΕΝ χάνει τα φίλτρα.
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  ΤΙ ΕΚΑΝΕ ΠΡΙΝ
 * ══════════════════════════════════════════════════════════════════════════
 *
 * Ο σύνδεσμος χτιζόταν με το χέρι και κουβαλούσε ΤΡΕΙΣ παραμέτρους:
 * listing_type, job_type, vehicle_type. Ό,τι άλλο είχε επιλέξει ο χρήστης —
 * τοποθεσία, ADR, άδεια χειριστή — εξαφανιζόταν στη σελίδα 2.
 *
 * Δηλαδή: έψαχνες «οδηγός στη Θεσσαλονίκη», έβλεπες 12 αποτελέσματα,
 * πατούσες «2» και έπαιρνες όλη την Ελλάδα — χωρίς καμία ένδειξη ότι το
 * φίλτρο έπεσε. Το κουτί της τοποθεσίας εμφανιζόταν άδειο και έμοιαζε με
 * δικό σου λάθος.
 *
 * Τώρα το URL χτίζεται από ΟΛΕΣ τις ενεργές παραμέτρους. Κάθε νέο φίλτρο
 * που θα προστεθεί στο μέλλον ακολουθεί αυτόματα — δεν χρειάζεται να το
 * θυμηθεί κανείς εδώ.
 *
 * Μεταβλητές: $pagination (total, page, limit, pages) · $activeFilters
 */

$pagination = $pagination ?? [];
$totalPages = (int) ($pagination['pages'] ?? 0);
$current    = (int) ($pagination['page'] ?? 1);

if ($totalPages <= 1) {
    return;
}

$activeFilters = $activeFilters ?? [];

$urlFor = static function (int $page) use ($activeFilters): string {
    $params = $activeFilters;
    $params['page'] = $page;

    return BASE_URL . 'job-listings?' . http_build_query($params);
};

/*
 * Παράθυρο σελίδων.
 *
 * Ο βρόχος τύπωνε ΚΑΘΕ σελίδα. Με 30 αγγελίες φαίνονται τρεις αριθμοί και
 * είναι μια χαρά· με 800 αγγελίες η γραμμή σελιδοποίησης γίνεται μεγαλύτερη
 * από τα αποτελέσματα. Δείχνουμε δύο σελίδες γύρω από την τρέχουσα, συν την
 * πρώτη και την τελευταία.
 */
$window = 2;
$pages  = [1, $totalPages];

for ($i = $current - $window; $i <= $current + $window; $i++) {
    if ($i >= 1 && $i <= $totalPages) {
        $pages[] = $i;
    }
}

$pages = array_values(array_unique($pages));
sort($pages);
?>

<nav class="pagination" aria-label="Σελιδοποίηση αποτελεσμάτων">
    <?php if ($current > 1) : ?>
        <a href="<?= htmlspecialchars($urlFor($current - 1), ENT_QUOTES, 'UTF-8') ?>"
           class="pagination-btn" rel="prev">← Προηγούμενη</a>
    <?php endif; ?>

    <?php $previous = 0; ?>
    <?php foreach ($pages as $p) : ?>
        <?php if ($previous && $p > $previous + 1) : ?>
            <span class="pagination-gap">…</span>
        <?php endif; ?>

        <?php if ($p === $current) : ?>
            <span class="pagination-btn active" aria-current="page"><?= $p ?></span>
        <?php else : ?>
            <a href="<?= htmlspecialchars($urlFor($p), ENT_QUOTES, 'UTF-8') ?>"
               class="pagination-btn"><?= $p ?></a>
        <?php endif; ?>

        <?php $previous = $p; ?>
    <?php endforeach; ?>

    <?php if ($current < $totalPages) : ?>
        <a href="<?= htmlspecialchars($urlFor($current + 1), ENT_QUOTES, 'UTF-8') ?>"
           class="pagination-btn" rel="next">Επόμενη →</a>
    <?php endif; ?>
</nav>

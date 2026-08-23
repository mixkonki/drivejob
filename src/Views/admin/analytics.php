<?php

/**
 * Στατιστικά — GET /admin/analytics
 *
 * Μεταβλητές: $monthly (σειρές ανά μήνα), $stats.
 *
 * Οι μπάρες είναι καθαρό HTML/CSS — κανένα chart library. Για τέσσερις
 * σειρές έξι μηνών, μια βιβλιοθήκη 200KB είναι υπερβολή· ένα div με
 * πλάτος-ποσοστό λέει το ίδιο πράγμα και δεν σπάει ποτέ.
 */

$breadcrumb = [['title' => 'Στατιστικά']];
include ROOT_DIR . '/src/Views/partials/admin-header.php';

$monthly = $monthly ?? [];
$stats = $stats ?? [];

/*
 * Οι τελευταίοι 6 μήνες, πάντα και οι έξι — και οι κενοί.
 * Χωρίς αυτούς, ένας μήνας χωρίς εγγραφές εξαφανίζεται από τον άξονα και
 * η καμπύλη δείχνει ψευδώς συνεχή άνοδο.
 */
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $months[] = date('Y-m', strtotime("-$i months"));
}

$monthLabel = static function (string $ym): string {
    $names = [1 => 'Ιαν', 'Φεβ', 'Μάρ', 'Απρ', 'Μάι', 'Ιούν', 'Ιούλ', 'Αύγ', 'Σεπ', 'Οκτ', 'Νοέ', 'Δεκ'];
    [$y, $m] = explode('-', $ym);

    return ($names[(int) $m] ?? $m) . ' ' . substr($y, 2);
};

$seriesMeta = [
    'drivers'      => ['Εγγραφές οδηγών', '#1d4ed8'],
    'companies'    => ['Εγγραφές εταιριών', '#15803d'],
    'listings'     => ['Νέες αγγελίες', '#b3261e'],
    'applications' => ['Αιτήσεις', '#7c3aed'],
];
?>

<style>
    .dj-charts { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .dj-chart { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px 18px; }
    .dj-chart h2 { font-size: .95rem; margin: 0 0 14px; }
    .dj-bar-row { display: grid; grid-template-columns: 60px 1fr 40px; gap: 8px;
                  align-items: center; margin-bottom: 8px; font-size: .82rem; }
    .dj-bar-row .m { color: #6b7280; }
    .dj-bar-row .n { text-align: right; font-weight: 600; }
    .dj-bar { height: 16px; border-radius: 4px; min-width: 2px; }
    .dj-funnel { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px;
                 padding: 16px 18px; margin-bottom: 16px; }
    .dj-funnel h2 { font-size: .95rem; margin: 0 0 14px; }
    .dj-funnel .step { display: grid; grid-template-columns: 180px 1fr 50px; gap: 10px;
                       align-items: center; margin-bottom: 8px; font-size: .85rem; }
    @media (max-width: 900px) { .dj-charts { grid-template-columns: 1fr; } }
</style>

<div class="admin-container">
    <div class="admin-header">
        <h1>Στατιστικά</h1>
    </div>

    <?php
    /*
     * Η χοάνη: πόση από την κίνηση καταλήγει σε πρόσληψη.
     * Αυτός είναι Ο αριθμός της πλατφόρμας — όλα τα άλλα είναι πλαίσιο.
     */
    $funnel = [
        'Ενεργές αγγελίες' => (int) ($stats['listings_active'] ?? 0),
        'Αιτήσεις συνολικά' => (int) ($stats['applications_total'] ?? 0),
        'Προσλήψεις' => (int) ($stats['applications_hired'] ?? 0),
        'Προσφορές συνολικά' => (int) ($stats['offers_total'] ?? 0),
        'Αποδεκτές προσφορές' => (int) ($stats['offers_accepted'] ?? 0),
    ];
    $funnelMax = max(1, max($funnel));
    ?>
    <div class="dj-funnel">
        <h2>Η χοάνη της πλατφόρμας</h2>
        <?php foreach ($funnel as $label => $value) : ?>
            <div class="step">
                <span><?= $label ?></span>
                <div><div class="dj-bar" style="background:#b3261e; width: <?= max(1, round($value / $funnelMax * 100)) ?>%;"></div></div>
                <strong><?= number_format($value, 0, ',', '.') ?></strong>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="dj-charts">
        <?php foreach ($seriesMeta as $key => [$title, $color]) : ?>
            <?php
            $data = $monthly[$key] ?? [];
            $max = max(1, ...array_map(fn($m) => (int) ($data[$m] ?? 0), $months));
            ?>
            <div class="dj-chart">
                <h2><?= $title ?></h2>
                <?php foreach ($months as $m) : ?>
                    <?php $v = (int) ($data[$m] ?? 0); ?>
                    <div class="dj-bar-row">
                        <span class="m"><?= $monthLabel($m) ?></span>
                        <div><div class="dj-bar" style="background: <?= $color ?>; width: <?= max(1, round($v / $max * 100)) ?>%; opacity: <?= $v > 0 ? '1' : '.15' ?>;"></div></div>
                        <span class="n"><?= $v ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include ROOT_DIR . '/src/Views/partials/admin-footer.php'; ?>

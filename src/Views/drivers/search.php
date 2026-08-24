<?php

/**
 * Αναζήτηση οδηγών — GET /drivers/search (μόνο εταιρείες/admin)
 *
 * Μεταβλητές από τον DriversController::search():
 *   $drivers — ανώνυμες κάρτες: id, city, region, experience_years,
 *              available_for_work, rating, licenses (συνενωμένες), adr
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  Η ΚΑΡΤΑ ΕΙΝΑΙ ΑΝΩΝΥΜΗ ΣΚΟΠΙΜΑ
 * ══════════════════════════════════════════════════════════════════════════
 *
 * Το παλιό view τύπωνε ονοματεπώνυμο κάθε οδηγού σε μια σελίδα-κατάλογο.
 * Η λίστα αναζήτησης είναι το εύκολο σημείο μαζικής συλλογής: μία σελίδα,
 * τριάντα ονόματα. Η κάρτα δείχνει ό,τι χρειάζεται η ΑΠΟΦΑΣΗ της εταιρείας
 * — άδειες, εμπειρία, περιοχή, διαθεσιμότητα — και η ταυτότητα ξεκλειδώνει
 * μέσα στο προφίλ, με τους κανόνες του Visibility (αίτηση → προφίλ,
 * shortlist/αποδοχή → επικοινωνία).
 */

include ROOT_DIR . '/src/Views/partials/header.php';

$drivers = $drivers ?? [];
?>

<?php include ROOT_DIR . '/src/Views/partials/app-styles.php'; ?>
<style>
    .drv-filters { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;
                   padding: 1rem; margin-bottom: 1.5rem; display: flex; flex-wrap: wrap;
                   gap: .8rem; align-items: end; }
    .drv-filters .f { display: flex; flex-direction: column; gap: .25rem; }
    .drv-filters label { font-size: .8rem; font-weight: 600; color: #374151; }
    .drv-filters input, .drv-filters select {
        padding: .5rem .65rem; border: 1px solid #d1d5db; border-radius: 6px;
        font-family: inherit; box-sizing: border-box; }
    .drv-filters .chk { flex-direction: row; align-items: center; gap: .4rem; padding-bottom: .5rem; }
    .drv-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 1rem; }
    .drv-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 1.1rem 1.2rem; }
    .drv-card h3 { margin: 0 0 .5rem; font-size: 1rem; color: #111827; }
    .drv-card .row { display: flex; gap: .5rem; align-items: baseline; font-size: .87rem;
                     color: #4b5563; margin-bottom: .3rem; }
    .drv-card .row .k { color: #9ca3af; min-width: 78px; font-size: .78rem; }
    .drv-badges { margin: .5rem 0 .8rem; display: flex; flex-wrap: wrap; gap: .35rem; }
    .drv-b { font-size: .74rem; font-weight: 600; padding: 2px 8px; border-radius: 999px; }
    .drv-b.ok  { background: #dcfce7; color: #166534; }
    .drv-b.adr { background: #fef3c7; color: #92400e; }
    .drv-b.lic { background: #eef2ff; color: #3730a3; }
</style>

<main class="app-page">
    <h1>Αναζήτηση οδηγών</h1>
    <p class="app-lead">Οι κάρτες είναι ανώνυμες — τα στοιχεία κάθε οδηγού ξεκλειδώνουν
        μέσα από το προφίλ του, όταν υπάρξει αίτηση ή αποδεκτή προσφορά.</p>

    <form method="GET" action="<?= BASE_URL ?>drivers/search" class="drv-filters">
        <div class="f">
            <label for="city">Περιοχή</label>
            <input type="text" id="city" name="city" placeholder="Πόλη ή νομός"
                   value="<?= htmlspecialchars((string) ($_GET['city'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="f">
            <label for="license_type">Κατηγορία άδειας</label>
            <select id="license_type" name="license_type">
                <option value="">Όλες</option>
                <?php
                $sel = strtoupper((string) ($_GET['license_type'] ?? ''));
                foreach (['B' => 'B — Επιβατικά', 'C1' => 'C1 — Ελαφρά φορτηγά', 'C' => 'C — Φορτηγά',
                          'CE' => 'CE — Συρμοί / νταλίκες', 'D1' => 'D1 — Μίνι λεωφορεία',
                          'D' => 'D — Λεωφορεία', 'DE' => 'DE — Αρθρωτά λεωφορεία'] as $code => $label) : ?>
                    <option value="<?= $code ?>" <?= $sel === $code ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="f">
            <label for="experience_years">Εμπειρία τουλάχιστον</label>
            <input type="number" id="experience_years" name="experience_years" min="0" max="50"
                   value="<?= htmlspecialchars((string) ($_GET['experience_years'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="έτη">
        </div>
        <div class="f chk">
            <input type="checkbox" id="available_for_work" name="available_for_work" value="1"
                   <?= isset($_GET['available_for_work']) ? 'checked' : '' ?>>
            <label for="available_for_work">Μόνο διαθέσιμοι</label>
        </div>
        <div class="f">
            <button type="submit" class="app-submit" style="background:#b3261e;color:#fff;border:0;border-radius:6px;padding:.55rem 1.2rem;font-weight:600;cursor:pointer;">Αναζήτηση</button>
        </div>
    </form>

    <?php if (empty($drivers)) : ?>
        <div class="app-empty">
            <p>Κανένας οδηγός δεν ταιριάζει με αυτά τα κριτήρια.</p>
            <p><a href="<?= BASE_URL ?>drivers/search">Καθάρισε τα φίλτρα →</a></p>
        </div>
    <?php else : ?>
        <p style="color:#6b7280; margin-bottom:1rem;"><?= count($drivers) ?> οδηγο<?= count($drivers) === 1 ? 'ς' : 'ί' ?></p>
        <div class="drv-grid">
            <?php foreach ($drivers as $d) : ?>
                <div class="drv-card">
                    <h3>Οδηγός #<?= (int) $d['id'] ?></h3>

                    <div class="drv-badges">
                        <?php if (!empty($d['available_for_work'])) : ?>
                            <span class="drv-b ok">Διαθέσιμος</span>
                        <?php endif; ?>
                        <?php if (!empty($d['adr_certificate'])) : ?>
                            <span class="drv-b adr">ADR</span>
                        <?php endif; ?>
                        <?php if (!empty($d['licenses'])) : ?>
                            <span class="drv-b lic"><?= htmlspecialchars((string) $d['licenses'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="row"><span class="k">Περιοχή</span>
                        <span><?= htmlspecialchars((string) ($d['city'] ?: $d['region'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></span></div>
                    <div class="row"><span class="k">Εμπειρία</span>
                        <span><?= (int) ($d['experience_years'] ?? 0) ?> χρόνια</span></div>
                    <?php if (!empty($d['rating'])) : ?>
                        <div class="row"><span class="k">Αξιολόγηση</span>
                            <span>★ <?= number_format((float) $d['rating'], 1, ',', '') ?>
                                <?= (int) ($d['rating_count'] ?? 0) > 0 ? '(' . (int) $d['rating_count'] . ')' : '' ?></span></div>
                    <?php endif; ?>
                    <?php if (!empty($d['willing_to_relocate'])) : ?>
                        <div class="row"><span class="k">Μετακίνηση</span><span>Δεκτή μετεγκατάσταση</span></div>
                    <?php endif; ?>

                    <div style="margin-top:.8rem;">
                        <a href="<?= BASE_URL ?>drivers/profile/<?= (int) $d['id'] ?>" class="app-btn app-btn-view">Προβολή προφίλ</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>

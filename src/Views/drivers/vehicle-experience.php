<?php

/**
 * Προϋπηρεσία σε Οχήματα — GET /drivers/vehicle-experience
 *
 * Μεταβλητές από τον DriversController::vehicleExperience():
 *   $rows   — εγγραφές από τη βάση (με vehicle_type_name)
 *   $totals — ['freight' => ..., 'passenger' => ..., 'all' => ...]
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  ΚΑΘΕ ΕΓΓΡΑΦΗ ΑΠΟΘΗΚΕΥΕΤΑΙ ΤΗ ΣΤΙΓΜΗ ΤΗΣ ΠΡΟΣΘΗΚΗΣ
 * ══════════════════════════════════════════════════════════════════════════
 *
 * Το παλιό σχέδιο («μάζεψε σε κρυφά πεδία → πάτα Αποθήκευση Αλλαγών»)
 * απαιτούσε δύο βήματα και όποιος ξεχνούσε το δεύτερο έχανε σιωπηλά τη
 * δουλειά του — γι' αυτό χρειαζόταν το κίτρινο προειδοποιητικό σημείωμα.
 * Τώρα: «Προσθήκη» = σώθηκε στη βάση, ο πίνακας δείχνει ΠΑΝΤΑ ό,τι
 * πραγματικά υπάρχει, η διαγραφή είναι άμεση. Κανένα κουμπί αποθήκευσης,
 * καμία αλλαγή σελίδας.
 *
 * Ο πίνακας ζωγραφίζεται server-side από τη βάση. Το JS
 * (vehicle-experience-page.js) κάνει μόνο: φιλτράρισμα τύπων ανά είδος
 * μεταφοράς, fetch για προσθήκη/διαγραφή, ενημέρωση πίνακα/συνόλων.
 */

use Drivejob\Helpers\VehicleExperienceTypes;

$rows = $rows ?? [];
$totals = $totals ?? ['freight' => '—', 'passenger' => '—', 'all' => '—'];

$fmtPeriod = static function (array $row): string {
    if (empty($row['start_date'])) {
        return '—';
    }
    $end = !empty($row['end_date']) ? date('d/m/Y', strtotime($row['end_date'])) : 'σήμερα';

    return date('d/m/Y', strtotime($row['start_date'])) . ' — ' . $end;
};
?>

<style>
    .vxp-wrap { max-width: 1100px; margin: 1.5rem auto; padding: 0 1rem; }
    .vxp-grid { display: grid; grid-template-columns: minmax(300px, 420px) 1fr; gap: 1.5rem; align-items: start; }
    .vxp-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 1.2rem 1.4rem; }
    .vxp-card h2 { font-size: 1.05rem; margin: 0 0 1rem; }
    .vxp-f { margin-bottom: .9rem; }
    .vxp-f label { display: block; font-size: .85rem; font-weight: 600; color: #374151; margin-bottom: .3rem; }
    .vxp-f select, .vxp-f input, .vxp-f textarea {
        width: 100%; padding: .5rem .65rem; border: 1px solid #d1d5db; border-radius: 6px;
        font-family: inherit; font-size: .92rem; box-sizing: border-box; background: #fff; }
    .vxp-dates { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; }
    .vxp-dates input { flex: 1; min-width: 125px; }
    .vxp-cal { border: 1px solid #d1d5db; background: #f9fafb; border-radius: 6px;
               padding: .4rem .55rem; cursor: pointer; font-size: .95rem; }
    .vxp-hint { font-size: .78rem; color: #6b7280; margin-top: .25rem; }
    .vxp-add { background: #b3261e; color: #fff; border: 0; border-radius: 6px;
               padding: .6rem 1.3rem; font-weight: 600; cursor: pointer; font-size: .95rem; }
    .vxp-add:disabled { opacity: .6; cursor: wait; }
    .vxp-msg { margin-top: .8rem; padding: .55rem .8rem; border-radius: 6px; font-size: .88rem; display: none; }
    .vxp-msg.ok { background: #dcfce7; color: #166534; display: block; }
    .vxp-msg.err { background: #fee2e2; color: #991b1b; display: block; }
    .vxp-table { width: 100%; border-collapse: collapse; font-size: .9rem; }
    .vxp-table th { text-align: left; padding: .5rem .6rem; border-bottom: 2px solid #e5e7eb;
                    font-size: .8rem; color: #6b7280; }
    .vxp-table td { padding: .55rem .6rem; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
    .vxp-table .cat { color: #9ca3af; font-size: .78rem; }
    .vxp-del { border: 1px solid #fca5a5; background: #fff; color: #b91c1c; border-radius: 6px;
               padding: .25rem .6rem; cursor: pointer; font-size: .8rem; }
    .vxp-totals td { border-bottom: 0; border-top: 2px solid #e5e7eb; font-size: .85rem; }
    .vxp-empty { color: #6b7280; padding: 1rem 0; }
    .vxp-back { margin-top: 1.2rem; }
    /* minmax(0, 1fr) και ΟΧΙ σκέτο 1fr: το «1fr» δεν συρρικνώνεται κάτω
       από το min-content των παιδιών του, οπότε στο κινητό η κάρτα έβγαινε
       471px σε οθόνη 390px και η σελίδα κυλούσε οριζόντια. (30/08) */
    /* Ο πίνακας κυλά ΜΕΣΑ στο δικό του πλαίσιο· η σελίδα δεν κυλά ποτέ
       οριζόντια. Πέντε στήλες δεν στριμώχνονται σε 390px χωρίς να γίνουν
       αδιάβαστες, οπότε η κύλιση είναι η τίμια λύση. */
    .vxp-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }

    @media (max-width: 860px) {
        .vxp-grid { grid-template-columns: minmax(0, 1fr); }
        .vxp-card { min-width: 0; padding: 1rem; }
        .vxp-table { min-width: 420px; }
    }
</style>

<main class="vxp-wrap">
    <h1 style="font-size:1.3rem;">Προϋπηρεσία σε Οχήματα</h1>
    <p style="color:#6b7280;">Κάθε καταχώρηση αποθηκεύεται αμέσως με το «Προσθήκη» — δεν χρειάζεται άλλο βήμα.</p>

    <div class="vxp-grid">
        <div class="vxp-card">
            <h2>Προσθήκη Προϋπηρεσίας</h2>

            <div class="vxp-f">
                <label for="vxp_transport">Είδος Μεταφοράς</label>
                <select id="vxp_transport">
                    <option value="">Επιλέξτε είδος μεταφοράς...</option>
                    <?php foreach (VehicleExperienceTypes::TRANSPORT_LABELS as $tCode => $tLabel) : ?>
                        <option value="<?= $tCode ?>"><?= $tLabel ?> Μεταφορές</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="vxp-f">
                <label for="vxp_type">Τύπος Οχήματος</label>
                <select id="vxp_type" disabled>
                    <option value="">Επιλέξτε πρώτα είδος μεταφοράς...</option>
                    <?php /* ΟΛΟΙ οι τύποι, server-rendered από τη μία πηγή αλήθειας.
                             Το JS απλώς δείχνει/κρύβει τα optgroups ανά είδος μεταφοράς. */ ?>
                    <?php foreach (VehicleExperienceTypes::TAXONOMY as $tCode => $categories) : ?>
                        <?php foreach ($categories as $catCode => $types) : ?>
                            <optgroup data-transport="<?= $tCode ?>" data-category="<?= $catCode ?>"
                                      label="<?= htmlspecialchars(VehicleExperienceTypes::categoryLabel($catCode), ENT_QUOTES, 'UTF-8') ?>">
                                <?php foreach ($types as $typeCode => $typeLabel) : ?>
                                    <option value="<?= $catCode ?>|<?= $typeCode ?>"><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="vxp-f">
                <label for="vxp_employment">Σχέση Εργασίας</label>
                <select id="vxp_employment">
                    <?php foreach (VehicleExperienceTypes::EMPLOYMENT_LABELS as $eCode => $eLabel) : ?>
                        <option value="<?= $eCode ?>"><?= $eLabel ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="vxp-f">
                <label>Περίοδος</label>
                <div class="vxp-dates">
                    <input type="date" id="vxp_start" max="<?= date('Y-m-d') ?>">
                    <button type="button" class="vxp-cal" data-for="vxp_start" title="Άνοιγμα ημερολογίου">📅</button>
                    <span>έως</span>
                    <input type="date" id="vxp_end">
                    <button type="button" class="vxp-cal" data-for="vxp_end" title="Άνοιγμα ημερολογίου">📅</button>
                </div>
                <div class="vxp-hint">Γράψε ηη/μμ/εεεε ή πάτα 📅. Άφησε κενό το «έως» αν εργάζεσαι ακόμη εκεί — η διάρκεια υπολογίζεται αυτόματα.</div>
            </div>

            <div class="vxp-f">
                <label for="vxp_description">Περιγραφή Καθηκόντων <span style="font-weight:400;color:#9ca3af;">(προαιρετικό)</span></label>
                <textarea id="vxp_description" rows="3"></textarea>
            </div>

            <button type="button" id="vxp-add-btn" class="vxp-add">Προσθήκη</button>
            <div id="vxp-msg" class="vxp-msg"></div>
        </div>

        <div class="vxp-card">
            <h2>Καταχωρημένη Προϋπηρεσία</h2>

            <div class="vxp-scroll">
            <table class="vxp-table" id="vxp-table">
                <thead>
                    <tr>
                        <th>Όχημα</th>
                        <th>Μεταφορές</th>
                        <th>Περίοδος</th>
                        <th>Διάρκεια</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="vxp-tbody">
                    <?php foreach ($rows as $row) : ?>
                        <tr data-id="<?= (int) $row['id'] ?>">
                            <td>
                                <?= htmlspecialchars((string) ($row['vehicle_type_name'] ?? $row['vehicle_type']), ENT_QUOTES, 'UTF-8') ?>
                                <div class="cat"><?= htmlspecialchars(VehicleExperienceTypes::categoryLabel((string) $row['vehicle_category']), ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td><?= htmlspecialchars(VehicleExperienceTypes::transportLabel($row['transport_type'] ?? 'freight'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= $fmtPeriod($row) ?></td>
                            <td><?= (int) ($row['years'] ?? 0) ?> έτη, <?= (int) ($row['months'] ?? 0) ?> μήνες, <?= (int) ($row['days'] ?? 0) ?> ημέρες</td>
                            <td><button type="button" class="vxp-del" data-id="<?= (int) $row['id'] ?>">Διαγραφή</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="vxp-totals"><td colspan="3">Μερικό Σύνολο (Εμπορευματικές)</td><td id="vxp-total-freight" colspan="2"><?= htmlspecialchars($totals['freight'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr class="vxp-totals"><td colspan="3">Μερικό Σύνολο (Επιβατικές)</td><td id="vxp-total-passenger" colspan="2"><?= htmlspecialchars($totals['passenger'], ENT_QUOTES, 'UTF-8') ?></td></tr>
                    <tr class="vxp-totals"><td colspan="3"><strong>Συνολική Προϋπηρεσία</strong></td><td id="vxp-total-all" colspan="2"><strong><?= htmlspecialchars($totals['all'], ENT_QUOTES, 'UTF-8') ?></strong></td></tr>
                </tfoot>
            </table>
            </div><!-- /.vxp-scroll -->

            <p class="vxp-empty" id="vxp-empty" <?= !empty($rows) ? 'style="display:none;"' : '' ?>>
                Δεν έχεις καταχωρήσει ακόμη προϋπηρεσία — πρόσθεσε την πρώτη από τη φόρμα.
            </p>
        </div>
    </div>

    <?php /* Πίσω ΕΚΕΙ ΑΠ' ΟΠΟΥ ΗΡΘΕ (31/08): ο σύνδεσμος ήταν καρφωμένος
       στην επεξεργασία προφίλ, οπότε όποιος ερχόταν από το προφίλ ή το
       βιογραφικό έχανε και τη σελίδα του και την καρτέλα του — και η
       ετικέτα έλεγε «στο προφίλ» ενώ πήγαινε αλλού. */ ?>
    <div class="vxp-back">
        <a href="<?= \Drivejob\Helpers\ReturnTo::url() ?>" class="btn-secondary" style="text-decoration:none;">← <?= htmlspecialchars(\Drivejob\Helpers\ReturnTo::label(), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
</main>

<?= \Drivejob\Helpers\Asset::js('js/vehicle-experience-page.js', false) ?>

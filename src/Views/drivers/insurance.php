<?php
/**
 * Ασφαλιστικό ιστορικό — η οθόνη του οδηγού. (01/09/2026)
 *
 * Περιμένει στο scope: $groups, $totals (από InsuranceController::index)
 */

$hasData = !empty($groups);
$kindLabels = ['employee' => 'Μισθωτός', 'self_employed' => 'Αυτοαπασχολούμενος'];

$fmtMonths = static function (float $m): string {
    $y = intdiv((int) round($m), 12);
    $rest = (int) round($m) % 12;
    if ($y > 0) {
        return $y . ' έτ' . ($y === 1 ? 'ος' : 'η') . ($rest ? ' ' . $rest . ' μ.' : '');
    }
    return number_format($m, 1, ',', '.') . ' μήνες';
};
?>
<?= \Drivejob\Helpers\Asset::css('css/driver-overview.css') ?>
<?= \Drivejob\Helpers\Asset::css('css/driver-score.css') ?>
<?= \Drivejob\Helpers\Asset::css('css/driver-references.css') ?>

<main>
    <div class="container refs-container">

        <h1 class="refs-title">Ασφαλιστικό Ιστορικό</h1>
        <p class="refs-lead">
            Ο <strong>Αναλυτικός Λογαριασμός Ασφάλισης</strong> από το gov.gr αποδεικνύει την
            επαγγελματική σου διαδρομή — <strong>και τη μισθωτή και την αυτοαπασχόληση</strong>
            (ΟΑΕΕ/ΤΕΒΕ/ΤΣΑ). Ανεβάζεις το .xlsx, κρατάμε μόνο τη σύνοψη των περιόδων
            (ποτέ αποδοχές ή εισφορές, ποτέ το ίδιο το αρχείο), και η προϋπηρεσία σου
            αποκτά επίσημο τεκμήριο.
        </p>

        <?php /* ═══ ΑΝΕΒΑΣΜΑ ════════════════════════════════════════════ */ ?>
        <section class="qgroup qgroup--invite">
            <header class="qgroup-head">
                <h3>Ανέβασμα αρχείου</h3>
            </header>
            <div class="qgroup-body">
                <p class="ins-how">
                    Θα το βρεις στο <strong>gov.gr → Εργασία και ασφάλιση → Ασφάλιση →
                    Ασφαλιστικό βιογραφικό / Λογαριασμός ασφάλισης e-ΕΦΚΑ</strong> —
                    κατέβασέ το ως Excel (.xlsx). Αν έχεις χρόνια σε διαφορετικά ταμεία
                    (ΙΚΑ, ΟΑΕΕ, ΤΕΒΕ), ανέβασε ένα αρχείο για το καθένα — προστίθενται.
                </p>
                <form id="ins-form" class="refs-form" enctype="multipart/form-data">
                    <div class="ins-upload-row">
                        <input type="file" name="insurance_file" id="ins-file" accept=".xlsx" required>
                        <button type="submit" class="btn-primary" id="ins-submit">Ανέβασμα &amp; ανάγνωση</button>
                    </div>
                    <div id="ins-msg" class="refs-msg" hidden></div>
                </form>
            </div>
        </section>

        <?php /* ═══ ΣΥΝΟΨΗ ══════════════════════════════════════════════ */ ?>
        <section class="qgroup qgroup--done">
            <header class="qgroup-head">
                <h3>Η διαδρομή σου</h3>
                <?php if ($hasData) : ?>
                    <span class="qgroup-meta">
                        σύνολο <strong><?php echo $fmtMonths($totals['employee'] + $totals['self_employed']); ?></strong>
                    </span>
                <?php endif; ?>
            </header>
            <div class="qgroup-body">
                <?php if (!$hasData) : ?>
                    <p class="qrow-empty">Δεν έχει ανέβει ακόμη ασφαλιστικό ιστορικό.</p>
                <?php else : ?>

                    <div class="pd-grid ins-totals">
                        <div class="pd-item">
                            <span class="pd-key">Μισθωτή εργασία</span>
                            <span class="pd-val"><?php echo $fmtMonths($totals['employee']); ?></span>
                        </div>
                        <div class="pd-item">
                            <span class="pd-key">Αυτοαπασχόληση</span>
                            <span class="pd-val"><?php echo $fmtMonths($totals['self_employed']); ?></span>
                        </div>
                    </div>

                    <table class="pd-table ins-table">
                        <thead>
                            <tr>
                                <th>Περίοδος</th>
                                <th>Ιδιότητα</th>
                                <th>Εργοδότης</th>
                                <th class="ins-num">Χρόνος</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groups as $g) : ?>
                                <tr>
                                    <td><?php echo date('m/Y', strtotime($g['date_from'])) . ' – ' . date('m/Y', strtotime($g['date_to'])); ?></td>
                                    <td><?php echo $kindLabels[$g['fund_kind']] ?? $g['fund_kind']; ?></td>
                                    <td>
                                        <?php echo $g['employer_name'] !== ''
                                            ? htmlspecialchars($g['employer_name'], ENT_QUOTES, 'UTF-8')
                                            : '<span class="pd-val--empty">' . ($g['fund_kind'] === 'self_employed' ? 'δική του δραστηριότητα' : '—') . '</span>'; ?>
                                    </td>
                                    <td class="ins-num"><?php echo $fmtMonths((float) $g['months']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <p class="refs-fineprint">
                        Οι περίοδοι μετράνε στη βαθμολογία μειωμένες μέχρι να επαληθευτούν
                        με Βεβαίωση που φέρει κωδικό ελέγχου gov.gr.
                        Μπορείς να διαγράψεις το ιστορικό όποτε θέλεις —
                        <button type="button" id="ins-delete" class="ins-delete-link">διαγραφή όλων</button>.
                    </p>

                <?php endif; ?>
            </div>
        </section>

        <div class="vxp-back">
            <a href="<?= BASE_URL ?>drivers/profile#self-assessment" class="btn-secondary" style="text-decoration:none;">← Επιστροφή στην αξιολόγηση</a>
        </div>
    </div>
</main>

<style>
/* Μικρά τοπικά στυλ — δεν αξίζουν δικό τους αρχείο. */
.ins-how { font-size: .84rem; line-height: 1.55; color: #6b7280; margin: .5rem 0 .8rem; }
.ins-upload-row { display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; }
.ins-upload-row input[type=file] { flex: 1; min-width: min(260px, 100%); font-size: .84rem; }
.ins-totals { margin-bottom: .6rem; }
.ins-table thead th { font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; color: #94a3b8; border-top: 0; }
.ins-num { text-align: right !important; white-space: nowrap; }
.ins-delete-link { background: none; border: none; padding: 0; color: #b3261e; cursor: pointer; font-size: inherit; text-decoration: underline; }
</style>

<?= \Drivejob\Helpers\Asset::js('js/driver-insurance.js', false) ?>

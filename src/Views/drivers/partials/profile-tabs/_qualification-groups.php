<?php
/**
 * Τυπικά προσόντα — ΜΟΝΟ ΕΜΦΑΝΙΣΗ. (ξαναγράφτηκε 30/08/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΑΛΛΑΞΕ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Η ΛΟΓΙΚΗ ΕΦΥΓΕ. Πρώτη γραφή είχε εδώ την ομαδοποίηση, τους ελέγχους
 * λήξης, τη μετάφραση κωδικών — και το PDF, που δεν μπορούσε να δει
 * τίποτα από αυτά, έβγαζε ισοπεδωμένη λίστα «ετικέτα: τιμή». Ακριβώς η
 * απόκλιση που ο DriverCvService υπάρχει για να αποτρέπει.
 *
 * Τώρα ο service δίνει έτοιμες ομάδες ($cv['qualifications']) και το
 * αρχείο αυτό τις ζωγραφίζει. Ό,τι φαίνεται εδώ φαίνεται και στο PDF.
 *
 * ΟΙ ΟΜΑΔΕΣ (οδηγία 30/08):
 *   1. Άδεια Οδήγησης — ο τίτλος της ομάδας ΕΙΝΑΙ το προσόν, η πρώτη
 *      γραμμή δεν τον επαναλαμβάνει
 *   2. Πιστοποιητικά & Ειδικές Άδειες — ADR και ταχογράφος πρώτα
 *   3. Άδεια Χειριστή Μηχανημάτων Έργου
 *
 * Οι υπότιτλοι ομάδας («Τι έχει δικαίωμα να οδηγεί», «ΕΔΧ, ζώντα ζώα
 * και λοιπά») αφαιρέθηκαν: εξηγούσαν το προφανές και έκαναν τη σελίδα
 * φλύαρη.
 *
 * Περιμένει στο scope: $cv
 */

use Drivejob\Helpers\QualIcons;

$groups = $cv['qualifications'] ?? [];

/** Το εικονίδιο από τον κωδικό που έδωσε ο service. */
$icon = static function (string $code): string {
    if (str_starts_with($code, 'op:')) {
        return QualIcons::operator(substr($code, 3));
    }
    if (str_starts_with($code, 'special:')) {
        return QualIcons::special(substr($code, 8));
    }
    return QualIcons::svg($code);
};
?>

<?php foreach ($groups as $group) : ?>
    <section class="qgroup qgroup--<?php echo htmlspecialchars($group['key'], ENT_QUOTES, 'UTF-8'); ?>">
        <header class="qgroup-head">
            <h3><?php echo htmlspecialchars($group['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <?php if (!empty($group['meta'])) : ?>
                <span class="qgroup-meta">
                    <?php echo htmlspecialchars($group['meta']['key'], ENT_QUOTES, 'UTF-8'); ?>
                    <strong><?php echo htmlspecialchars($group['meta']['value'], ENT_QUOTES, 'UTF-8'); ?></strong>
                </span>
            <?php endif; ?>
        </header>

        <div class="qgroup-body">
            <?php foreach ($group['items'] as $item) : ?>
                <article class="qrow<?php echo !empty($item['absent']) ? ' qrow--absent' : ''; ?>">
                    <div class="qrow-icon"><?php echo $icon($item['icon']); ?></div>

                    <div class="qrow-main">
                        <?php if ($item['title'] !== '') : ?>
                            <h4>
                                <?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php if (!empty($item['tag'])) : ?>
                                    <span class="qtag"><?php echo htmlspecialchars($item['tag'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </h4>
                        <?php endif; ?>

                        <?php if (!empty($item['subtitle'])) : ?>
                            <p class="qrow-sub"><?php echo htmlspecialchars($item['subtitle'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($item['cats'])) : ?>
                            <p class="qrow-cats">
                                <?php foreach ($item['cats'] as $cat) : ?>
                                    <span class="qcat"><?php echo htmlspecialchars((string) $cat, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endforeach; ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($item['lines'])) : ?>
                            <p class="qrow-lines">
                                <?php foreach ($item['lines'] as $line) : ?>
                                    <span class="qrow-line">
                                        <?php if ($line['key'] !== '') : ?>
                                            <span class="qrow-key"><?php echo htmlspecialchars($line['key'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($line['value'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                <?php endforeach; ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($item['covers_all'])) : ?>
                            <p class="qrow-all">Σύνολο μηχανημάτων της ειδικότητας</p>
                        <?php elseif (!empty($item['subs'])) : ?>
                            <ul class="qsubs">
                                <?php foreach ($item['subs'] as $sub) : ?>
                                    <li>
                                        <span class="qsub-code"><?php echo htmlspecialchars($sub['code'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="qsub-name"><?php echo htmlspecialchars($sub['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php if ($sub['group'] !== '') : ?>
                                            <span class="qsub-group"><?php echo htmlspecialchars($sub['group'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if (!empty($item['absent']) && !empty($item['empty_text'])) : ?>
                            <p class="qrow-empty"><?php echo htmlspecialchars($item['empty_text'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                    </div>

                    <?php /* Οι λήξεις ΣΕ ΣΕΙΡΑ, με το τι είναι η καθεμία (οδηγία
                       30/08): πριν ήταν στοιβαγμένες και ο υπότιτλος «έντυπο /
                       κατηγορίες» διαβαζόταν σαν συνέχεια της ημερομηνίας. */ ?>
                    <div class="qrow-expiry">
                        <?php foreach ($item['expiries'] as $exp) : ?>
                            <span class="qexp">
                                <span class="qexp-label"><?php echo htmlspecialchars($exp['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php echo htmlspecialchars($exp['date'], ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <?php /* Το «Σε ισχύ» ΔΕΝ εμφανίζεται: είναι το αναμενόμενο και
                       επαναλαμβανόταν σε κάθε γραμμή. Ένδειξη μπαίνει μόνο όταν
                       κάτι χρειάζεται προσοχή ή λείπει. */ ?>
                    <div class="qrow-status">
                        <?php if (!in_array($item['status']['cls'], ['valid', 'open'], true)) : ?>
                            <span class="qbadge qbadge--<?php echo $item['status']['cls']; ?>"><?php echo htmlspecialchars($item['status']['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>

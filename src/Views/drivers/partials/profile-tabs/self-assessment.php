<?php
/**
 * Καρτέλα «Αξιολόγηση Οδηγού». (ξαναγράφτηκε 01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΕΔΕΙΧΝΕ ΠΡΙΝ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Έναν κύκλο με «Συνολική Βαθμολογία» και τέσσερις μπάρες. Μετρημένο
 * στη βάση (31/08): και οι πέντε σχετικοί πίνακες είχαν ΜΗΔΕΝ γραμμές.
 * Ο αριθμός δεν έβγαινε από δεδομένα — έβγαινε από προεπιλογές:
 *
 *   • Επαγγελματισμός 25% + Τεχνικές 20% = 45%, από αυτοβαθμολόγηση σε
 *     πεδία που δεν εμφανίζονταν σε ΚΑΜΙΑ φόρμα → σταθερά 50.
 *   • Ασφάλεια 30%: ξεκινούσε από 100 και αφαιρούσε ποινές ανά συμβάν —
 *     αλλά κανείς δεν καταχωρεί συμβάντα, άρα σταθερά 100.
 *
 * Δηλαδή 52,5 μονάδες προκαταβολικά, ίδιες για όλους.
 *
 * ΚΑΙ ΤΑ ΨΕΥΤΙΚΑ: «Συμβουλές Βελτίωσης» και «Προτάσεις Εκπαίδευσης»
 * («Αμυντική Οδήγηση», «Διαχείριση Οδηγικού Στρες») ήταν γραμμένες μέσα
 * στο view. Εμφανίζονταν σε κάθε οδηγό, ό,τι κι αν είχε, παρουσιασμένες
 * ως προσωποποιημένες. Αφαιρέθηκαν.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΔΕΙΧΝΕΙ ΤΩΡΑ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Τρία μεγέθη που δεν συγχέονται, και το «γιατί» καθενός:
 *
 *   ΠΡΟΣΟΝΤΑ  τι χαρτιά κρατά, με ισχύ — το ελέγχει ο οδηγός
 *   ΦΗΜΗ      τι λένε οι άλλοι — ΔΕΝ το ελέγχει, γι' αυτό πείθει
 *   ΣΥΝΟΛΟ    εμφανίζεται ΜΟΝΟ όταν υπάρχει φήμη
 *
 * Ο κανόνας «χωρίς μαρτυρία τρίτου δεν υπάρχει συνολικός αριθμός»
 * επιβάλλεται στο μοντέλο (DriverScoreService), όχι εδώ — ώστε καμία
 * μελλοντική σελίδα να μην μπορέσει να τον παρακάμψει.
 *
 * Περιμένει στο scope: $driverScore (πίνακας από DriverScore::toArray())
 */

use Drivejob\Services\Score\ScoreSource;

$score = $driverScore ?? null;

/** Ομαδοποίηση της ανάλυσης ανά πηγή, με τη σειρά του μητρώου. */
$bySource = [];
foreach (($score['contributions'] ?? []) as $c) {
    $bySource[$c['source']][] = $c;
}

$evidenceShort = ScoreSource::EVIDENCE_SHORT;
$evidenceFull = ScoreSource::EVIDENCE_LABELS;
?>
            <!-- Καρτέλα Αξιολόγησης Οδηγού -->
            <div class="tab-pane" id="self-assessment">

            <?php if (!$score) : ?>
                <p class="qrow-empty">Η βαθμολογία δεν είναι διαθέσιμη αυτή τη στιγμή.</p>
            <?php else : ?>

                <?php /* ═══ ΟΙ ΑΡΙΘΜΟΙ ═══════════════════════════════════ */ ?>
                <div class="score-top">

                    <?php /* Ο συνολικός: ΜΟΝΟ αν στηρίζεται σε μαρτυρία τρίτου. */ ?>
                    <div class="score-hero<?php echo $score['total'] === null ? ' score-hero--empty' : ''; ?>">
                        <?php if ($score['total'] !== null) : ?>
                            <?php
                            // Ο κύκλος: 283.5 = 2πr για r=45.
                            $dash = 283.5 - (283.5 * $score['total'] / 100);
                            ?>
                            <div class="score-ring">
                                <svg viewBox="0 0 100 100" aria-hidden="true">
                                    <circle class="ring-bg" cx="50" cy="50" r="45" fill="none"></circle>
                                    <circle class="ring-fg" cx="50" cy="50" r="45" fill="none"
                                            style="stroke-dashoffset: <?php echo round($dash, 1); ?>"></circle>
                                </svg>
                                <div class="score-ring-text">
                                    <strong><?php echo round($score['total']); ?></strong>
                                    <span>στα 100</span>
                                </div>
                            </div>
                            <div class="score-hero-side">
                                <h3><?php echo htmlspecialchars($score['label'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p>Χαρτιά 40% · Φήμη 60%. Τα χαρτιά σε φέρνουν στη συνέντευξη·
                                   η συμπεριφορά σε κρατά στη δουλειά.</p>
                                <p class="score-conf">
                                    Στηρίζεται σε <strong><?php echo round($score['confidence']); ?>%</strong>
                                    των διαθέσιμων πηγών.
                                </p>
                            </div>
                        <?php else : ?>
                            <?php /* Η ΤΙΜΙΑ ΕΚΔΟΧΗ ΤΟΥ ΚΕΝΟΥ. Το παλιό «52» έδειχνε
                               βεβαιότητα εκεί που δεν υπήρχε ούτε ένα δεδομένο. */ ?>
                            <div class="score-hero-side">
                                <h3>Χωρίς αξιολόγηση ακόμη</h3>
                                <p>Ο συνολικός βαθμός εμφανίζεται μόλις υπάρξει η πρώτη μαρτυρία από
                                   τρίτο πρόσωπο — αξιολόγηση εργοδότη ή μετρημένη οδήγηση.</p>
                                <p><strong>Δεν δείχνουμε αριθμό που βγαίνει μόνο από όσα δήλωσες ο ίδιος.</strong>
                                   Θα φαινόταν αξιολόγηση ενώ θα ήταν δήλωση — και ο πρώτος εργοδότης που
                                   το καταλάβαινε δεν θα ξανακοίταζε κανέναν αριθμό σου.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php /* Τα δύο μεγέθη, πάντα ορατά. */ ?>
                    <div class="score-pair">
                        <div class="score-metric">
                            <span class="score-metric-key">Προσόντα &amp; χαρτιά</span>
                            <span class="score-metric-val"><?php echo round($score['credentials']); ?><i>/100</i></span>
                            <div class="score-bar"><div style="width: <?php echo round($score['credentials']); ?>%"></div></div>
                            <span class="score-metric-note">Άδειες, ΠΕΙ, πιστοποιητικά, σεμινάρια — όλα με έγγραφο.</span>
                        </div>

                        <div class="score-metric<?php echo $score['reputation'] === null ? ' score-metric--empty' : ''; ?>">
                            <span class="score-metric-key">Φήμη</span>
                            <?php if ($score['reputation'] !== null) : ?>
                                <span class="score-metric-val"><?php echo round($score['reputation']); ?><i>/100</i></span>
                                <div class="score-bar"><div style="width: <?php echo round($score['reputation']); ?>%"></div></div>
                                <span class="score-metric-note">Από αξιολογήσεις εργοδοτών και μετρήσεις — όχι από δηλώσεις.</span>
                            <?php else : ?>
                                <span class="score-metric-val score-metric-val--none">—</span>
                                <div class="score-bar"><div style="width: 0"></div></div>
                                <span class="score-metric-note">Καμία αξιολόγηση ακόμη. Είναι το μόνο μέγεθος που δεν
                                    ελέγχεις ο ίδιος — γι' αυτό και το μόνο που πείθει τον εργοδότη.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php /* ═══ ΤΙ ΛΕΙΠΕΙ ═══════════════════════════════════ */ ?>
                <?php
                $missing = [];
                foreach (($score['sources'] ?? []) as $key => $s) {
                    if ($s['active'] && !$s['has_data'] && $s['hint'] !== '' && $s['weight'] > 0) {
                        $missing[$key] = $s;
                    }
                }
                ?>
                <?php if ($missing) : ?>
                    <section class="qgroup qgroup--todo">
                        <header class="qgroup-head">
                            <h3>Τι θα ανέβαζε τη βαθμολογία σου</h3>
                        </header>
                        <div class="qgroup-body">
                            <ul class="score-todo">
                                <?php foreach ($missing as $key => $s) : ?>
                                    <li>
                                        <strong><?php echo htmlspecialchars($s['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <span><?php echo htmlspecialchars($s['hint'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php /* Η πιο σημαντική έλλειψη έχει και δρόμο: η
                                           σελίδα προσκλήσεων υπάρχει (01/09). */ ?>
                                        <?php if ($key === 'employer_review') : ?>
                                            <a class="score-todo-cta" href="<?php echo BASE_URL; ?>drivers/references">Στείλε πρόσκληση →</a>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </section>
                <?php endif; ?>

                <?php /* ═══ ΑΠΟ ΠΟΥ ΒΓΑΙΝΕΙ ═════════════════════════════
                   Χωρίς αυτό, στο «γιατί 61;» δεν υπάρχει απάντηση — και
                   ο οδηγός δικαιούται να ξέρει τι μετρήθηκε. */ ?>
                <section class="qgroup qgroup--why">
                    <header class="qgroup-head">
                        <h3>Από πού βγαίνει</h3>
                        <span class="qgroup-meta">κάθε μονάδα, με την πηγή της</span>
                    </header>
                    <div class="qgroup-body">
                        <?php foreach (($score['sources'] ?? []) as $key => $s) : ?>
                            <?php $rows = $bySource[$key] ?? []; ?>
                            <div class="score-src<?php echo $rows ? '' : ' score-src--empty'; ?>">
                                <div class="score-src-head">
                                    <span class="score-src-name"><?php echo htmlspecialchars($s['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="ev ev--<?php echo htmlspecialchars($s['evidence'], ENT_QUOTES, 'UTF-8'); ?>"
                                          title="<?php echo htmlspecialchars($evidenceFull[$s['evidence']] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($evidenceShort[$s['evidence']] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                    <?php if (!$s['counts']) : ?>
                                        <span class="ev ev--off">δεν μετράει</span>
                                    <?php endif; ?>
                                    <?php if (!$s['active']) : ?>
                                        <span class="score-src-state">δεν έχει ενεργοποιηθεί</span>
                                    <?php elseif (!$rows) : ?>
                                        <span class="score-src-state">κενό</span>
                                    <?php elseif ($s['counts']) : ?>
                                        <span class="score-src-sum"><?php echo ($s['points'] > 0 ? '+' : '') . round($s['points'], 1); ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($rows) : ?>
                                    <ul class="score-lines">
                                        <?php foreach ($rows as $c) : ?>
                                            <li>
                                                <span class="score-line-label">
                                                    <?php echo htmlspecialchars($c['label'], ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php if ($c['detail'] !== '') : ?>
                                                        <em><?php echo htmlspecialchars($c['detail'], ENT_QUOTES, 'UTF-8'); ?></em>
                                                    <?php endif; ?>
                                                </span>
                                                <?php if ($c['counts']) : ?>
                                                    <span class="score-line-pts<?php echo $c['points'] < 0 ? ' score-line-pts--neg' : ''; ?>">
                                                        <?php echo ($c['points'] > 0 ? '+' : '') . round($c['points'], 1); ?>
                                                    </span>
                                                <?php else : ?>
                                                    <span class="score-line-pts score-line-pts--off">—</span>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php elseif (!$s['active'] && $s['hint'] !== '') : ?>
                                    <p class="score-src-hint"><?php echo htmlspecialchars($s['hint'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <p class="score-foot">
                    Η βαθμολογία υπολογίζεται κατά <strong>ισχύ τεκμηρίου</strong>, όχι κατά θέμα:
                    μετράει ποιος βεβαιώνει κάτι, όχι τι είναι.
                    Ό,τι δηλώνεις μόνος σου εμφανίζεται στο προφίλ και στο βιογραφικό σου,
                    αλλά δεν προσθέτει μονάδες.
                </p>

            <?php endif; ?>
            </div>

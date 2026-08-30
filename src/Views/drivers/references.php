<?php
/**
 * Συστάσεις εργοδοτών — η πλευρά του ΟΔΗΓΟΥ. (01/09/2026)
 *
 * Δύο πράγματα, με αυτή τη σειρά:
 *   1. Οι προσκλήσεις που έχει στείλει και η κατάστασή τους.
 *   2. Η φόρμα νέας πρόσκλησης.
 *
 * ΤΙ ΔΕΝ ΔΕΙΧΝΕΙ ΕΠΙΤΗΔΕΣ: τη βαθμολογία κάθε απαντημένης σύστασης.
 * Ο οδηγός βλέπει «Απαντήθηκε», όχι «σου έβαλε 3». Αν έβλεπε τον βαθμό
 * ανά αξιολογητή, ο κακός βαθμός θα γινόταν τηλεφώνημα στον πρώην
 * εργοδότη — και κανένας εργοδότης δεν θα ξανααπαντούσε ειλικρινά.
 * Το σύνολο φαίνεται στην καρτέλα «Αξιολόγηση Οδηγού».
 *
 * Περιμένει στο scope: $invites (από ReferenceController::index)
 */

$pending = array_filter($invites, static fn($i) => $i['rating'] === null);
$answered = array_filter($invites, static fn($i) => $i['rating'] !== null);
?>
<?= \Drivejob\Helpers\Asset::css('css/driver-overview.css') ?>
<?= \Drivejob\Helpers\Asset::css('css/driver-score.css') ?>
<?= \Drivejob\Helpers\Asset::css('css/driver-references.css') ?>

<main>
    <div class="container refs-container">

        <h1 class="refs-title">Συστάσεις Εργοδοτών</h1>
        <p class="refs-lead">
            Η σύσταση από παλιό εργοδότη είναι <strong>η πρώτη μαρτυρία τρίτου</strong> στο προφίλ σου —
            αυτή που ενεργοποιεί τη συνολική βαθμολογία και μετράει περισσότερο απ' ό,τι
            οποιοδήποτε χαρτί. Ο εργοδότης δεν χρειάζεται λογαριασμό: του στέλνεις έναν
            σύνδεσμο και απαντά σε 2 λεπτά.
        </p>

        <?php /* ═══ ΝΕΑ ΠΡΟΣΚΛΗΣΗ ═══════════════════════════════════════ */ ?>
        <section class="qgroup qgroup--invite">
            <header class="qgroup-head">
                <h3>Νέα πρόσκληση</h3>
            </header>
            <div class="qgroup-body">
                <form id="ref-form" class="refs-form" autocomplete="off">
                    <div class="refs-grid">
                        <label>
                            Ονοματεπώνυμο εργοδότη <i>*</i>
                            <input type="text" name="reviewer_name" maxlength="120" required
                                   placeholder="π.χ. Γιώργος Παπαδόπουλος">
                        </label>
                        <label>
                            Επιχείρηση <i>*</i>
                            <input type="text" name="reviewer_company" maxlength="160" required
                                   placeholder="π.χ. Μεταφορική Παπαδόπουλος ΟΕ">
                        </label>
                        <label>
                            Email εργοδότη <span class="refs-opt">(προαιρετικό)</span>
                            <input type="email" name="reviewer_email" maxlength="160"
                                   placeholder="Αν το βάλεις, στέλνουμε εμείς την πρόσκληση">
                        </label>
                        <label class="refs-period">
                            Περίοδος απασχόλησης <span class="refs-opt">(προαιρετικό)</span>
                            <span class="refs-period-inputs">
                                <input type="month" name="employment_from" aria-label="Από">
                                <span>έως</span>
                                <input type="month" name="employment_to" aria-label="Έως">
                            </span>
                        </label>
                    </div>

                    <div class="refs-actions">
                        <button type="submit" class="btn-primary" id="ref-submit">Δημιουργία πρόσκλησης</button>
                    </div>

                    <div id="ref-msg" class="refs-msg" hidden></div>

                    <?php /* Ο σύνδεσμος εμφανίζεται ΕΔΩ, πάντα — και όταν φύγει
                       email. Ο οδηγός τον στέλνει και μόνος του σε Viber/SMS:
                       στην αγορά των μεταφορών αυτό απαντιέται πιο συχνά
                       από το email. */ ?>
                    <div id="ref-link-box" class="refs-linkbox" hidden>
                        <span class="refs-linkbox-label">Ο σύνδεσμος της πρόσκλησης</span>
                        <div class="refs-linkbox-row">
                            <input type="text" id="ref-link" readonly>
                            <button type="button" class="btn-secondary" id="ref-copy">Αντιγραφή</button>
                        </div>
                        <p>Στείλ' τον στον εργοδότη με όποιον τρόπο σου είναι εύκολος — Viber, WhatsApp, SMS, email.</p>
                    </div>
                </form>
            </div>
        </section>

        <?php /* ═══ ΕΚΚΡΕΜΕΙΣ ═══════════════════════════════════════════ */ ?>
        <section class="qgroup qgroup--pend">
            <header class="qgroup-head">
                <h3>Περιμένουν απάντηση</h3>
                <?php if ($pending) : ?>
                    <span class="qgroup-meta"><strong><?php echo count($pending); ?></strong> από 3</span>
                <?php endif; ?>
            </header>
            <div class="qgroup-body">
                <?php if (!$pending) : ?>
                    <p class="qrow-empty">Καμία εκκρεμής πρόσκληση.</p>
                <?php else : ?>
                    <?php foreach ($pending as $inv) : ?>
                        <div class="refs-item" data-id="<?php echo (int) $inv['id']; ?>">
                            <div class="refs-item-main">
                                <strong><?php echo htmlspecialchars($inv['reviewer_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span><?php echo htmlspecialchars($inv['reviewer_company'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <em>στάλθηκε <?php echo date('d/m/Y', strtotime($inv['invited_at'])); ?></em>
                            </div>
                            <div class="refs-item-actions">
                                <button type="button" class="refs-copybtn btn-secondary"
                                        data-link="<?php echo htmlspecialchars(BASE_URL . 'reference/' . $inv['invite_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                    Αντιγραφή συνδέσμου
                                </button>
                                <button type="button" class="refs-cancel" title="Ακύρωση πρόσκλησης">Ακύρωση</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <?php /* ═══ ΑΠΑΝΤΗΜΕΝΕΣ ═════════════════════════════════════════ */ ?>
        <section class="qgroup qgroup--done">
            <header class="qgroup-head">
                <h3>Απαντημένες</h3>
                <?php if ($answered) : ?>
                    <span class="qgroup-meta"><strong><?php echo count($answered); ?></strong></span>
                <?php endif; ?>
            </header>
            <div class="qgroup-body">
                <?php if (!$answered) : ?>
                    <p class="qrow-empty">Καμία απαντημένη σύσταση ακόμη — μόλις απαντηθεί η πρώτη,
                        ενεργοποιείται η συνολική βαθμολογία σου.</p>
                <?php else : ?>
                    <?php foreach ($answered as $inv) : ?>
                        <div class="refs-item refs-item--done">
                            <div class="refs-item-main">
                                <strong><?php echo htmlspecialchars($inv['reviewer_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span><?php echo htmlspecialchars($inv['reviewer_company'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <em>απαντήθηκε <?php echo date('d/m/Y', strtotime($inv['updated_at'])); ?></em>
                            </div>
                            <span class="refs-done-badge">✓ Μετράει στη βαθμολογία</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <div class="vxp-back">
            <a href="<?= BASE_URL ?>drivers/profile#self-assessment" class="btn-secondary" style="text-decoration:none;">← Επιστροφή στην αξιολόγηση</a>
        </div>
    </div>
</main>

<?= \Drivejob\Helpers\Asset::js('js/driver-references.js', false) ?>

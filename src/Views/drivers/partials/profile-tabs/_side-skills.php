<?php
/**
 * Γλώσσες και Δεξιότητες — ΔΥΟ ΞΕΧΩΡΙΣΤΕΣ ομάδες, στη δεξιά στήλη.
 * (31/08/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΧΩΡΙΣΑΝ ΚΑΙ ΓΙΑΤΙ ΜΕΤΑΚΟΜΙΣΑΝ ΕΔΩ
 * ══════════════════════════════════════════════════════════════════════
 *
 * ΧΩΡΙΣΑΝ: ήταν μία ομάδα «Γλώσσες & Δεξιότητες» με δύο εσωτερικές
 * στήλες. Δύο άσχετα πράγματα κάτω από έναν τίτλο, με τις γλώσσες σε
 * στενή στήλη και τεράστιο κενό δίπλα τους. Είναι δύο διαφορετικά
 * προσόντα και διαβάζονται ανεξάρτητα.
 *
 * ΜΕΤΑΚΟΜΙΣΑΝ: μετρημένο στη ζωντανή σελίδα (31/08) — κύρια στήλη
 * 2176px, πλαϊνή 484px. Τα δύο τρίτα της δεξιάς στήλης ήταν ΚΕΝΑ ενώ η
 * αριστερή συνέχιζε. Οι γλώσσες και οι δεξιότητες είναι σύντομες λίστες
 * που στέκονται μια χαρά σε στενή στήλη, και γεμίζουν το κενό.
 *
 * Περιμένει στο scope: $cv
 */

$langs = $cv['languages'] ?? [];
$skills = $cv['skills'] ?? ['groups' => [], 'count' => 0];
?>

<?php // ═══ ΓΛΩΣΣΕΣ ═════════════════════════════════════════════════ ?>
<section class="qgroup qgroup--lang">
    <header class="qgroup-head">
        <h3>Γλώσσες</h3>
        <?php if (!empty($langs)) : ?>
            <span class="qgroup-meta"><strong><?php echo count($langs); ?></strong></span>
        <?php endif; ?>
            <?php /* Deep link: πάει στην ακριβή καρτέλα/σελίδα επεξεργασίας. */ ?>
            <a class="qgroup-edit" href="<?php echo BASE_URL; ?>drivers/edit-profile#skills-tab~dj-languages">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                Επεξεργασία
            </a>
    </header>
    <div class="qgroup-body">
        <?php if (empty($langs)) : ?>
            <p class="qrow-empty">Καμία δηλωμένη. <a href="<?php echo BASE_URL; ?>drivers/edit-profile">Προσθήκη</a></p>
        <?php else : ?>
            <ul class="lang-list">
                <?php foreach ($langs as $l) : ?>
                    <li>
                        <span class="lang-name"><?php echo htmlspecialchars($l['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php /* Ράβδος επιπέδου: το «Άριστα» δίπλα στο «Βασικά»
                           διαβάζεται σαν λίστα λέξεων· τέσσερα τετραγωνάκια
                           δείχνουν τη διαφορά χωρίς να τη διαβάσεις. */ ?>
                        <span class="lang-level lang-level--<?php echo htmlspecialchars($l['level'], ENT_QUOTES, 'UTF-8'); ?>">
                            <span class="lang-bar" aria-hidden="true">
                                <?php for ($i = 1; $i <= 4; $i++) : ?>
                                    <i class="<?php echo $i <= (int) $l['rank'] ? 'on' : ''; ?>"></i>
                                <?php endfor; ?>
                            </span>
                            <?php echo htmlspecialchars($l['level_label'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>

<?php // ═══ ΔΕΞΙΟΤΗΤΕΣ ══════════════════════════════════════════════ ?>
<section class="qgroup qgroup--skl">
    <header class="qgroup-head">
        <h3>Δεξιότητες</h3>
        <?php if ($skills['count'] > 0) : ?>
            <span class="qgroup-meta"><strong><?php echo (int) $skills['count']; ?></strong></span>
        <?php endif; ?>
            <?php /* Deep link: πάει στην ακριβή καρτέλα/σελίδα επεξεργασίας. */ ?>
            <a class="qgroup-edit" href="<?php echo BASE_URL; ?>drivers/edit-profile#skills-tab">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                Επεξεργασία
            </a>
    </header>
    <div class="qgroup-body">
        <?php if (empty($skills['groups'])) : ?>
            <p class="qrow-empty">Καμία δηλωμένη. <a href="<?php echo BASE_URL; ?>drivers/edit-profile">Προσθήκη</a></p>
        <?php else : ?>
            <?php foreach ($skills['groups'] as $g) : ?>
                <div class="skl-group">
                    <span class="skl-group-label"><?php echo htmlspecialchars($g['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <p class="skl-list">
                        <?php foreach ($g['items'] as $skillLabel) : ?>
                            <span class="qchip"><?php echo htmlspecialchars($skillLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endforeach; ?>
                    </p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

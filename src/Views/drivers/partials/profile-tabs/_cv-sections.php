<?php
/**
 * Οι ενότητες του βιογραφικού στην επισκόπηση: προϋπηρεσία, σεμινάρια,
 * γλώσσες, δεξιότητες. (30/08/2026)
 *
 * ΓΙΑΤΙ: η επισκόπηση έδειχνε ΜΟΝΟ τα τυπικά προσόντα. Η προϋπηρεσία
 * φαινόταν σαν «7 έτη εμπειρίας» δίπλα στο όνομα — χωρίς οχήματα, χωρίς
 * διαστήματα· τα σεμινάρια, οι γλώσσες και οι δεξιότητες πουθενά. Είναι
 * ακριβώς τα πεδία που ζητά το βιογραφικό, και ο οδηγός δεν είχε τρόπο
 * να δει αν είναι συμπληρωμένα.
 *
 * ΟΛΑ τα κείμενα έρχονται έτοιμα από τον DriverCvService — η όψη δεν
 * μεταφράζει κωδικούς, δεν υπολογίζει διάρκειες, δεν ταξινομεί. Αυτό
 * είναι που εγγυάται ότι το PDF θα δείχνει τα ίδια πράγματα.
 *
 * Περιμένει στο scope: $cv (η δομή του DriverCvService).
 */

$exp = $cv['experience'] ?? ['items' => [], 'count' => 0];
$certs = $cv['certifications'] ?? ['items' => [], 'count' => 0];
$langs = $cv['languages'] ?? [];
$skills = $cv['skills'] ?? ['groups' => [], 'count' => 0];
?>

<?php // ═══ ΠΡΟΫΠΗΡΕΣΙΑ ══════════════════════════════════════════════ ?>
<section class="profile-section cv-section" id="cv-experience">
    <div class="cv-head">
        <h2>Προϋπηρεσία</h2>
        <?php if ($exp['count'] > 0) : ?>
            <span class="cv-total">Σύνολο: <strong><?php echo htmlspecialchars($exp['total_label'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
        <?php endif; ?>
    </div>

    <?php if ($exp['count'] === 0) : ?>
        <p class="cv-empty">
            Δεν έχετε καταχωρήσει προϋπηρεσία.
            <a href="<?php echo BASE_URL; ?>drivers/vehicle-experience">Προσθέστε την τώρα</a> —
            είναι το πρώτο που κοιτάζει ένας εργοδότης μετά το δίπλωμα.
        </p>
    <?php else : ?>
        <ol class="cv-timeline">
            <?php foreach ($exp['items'] as $item) : ?>
                <li class="cv-exp<?php echo $item['current'] ? ' cv-exp--current' : ''; ?>">
                    <div class="cv-exp-marker" aria-hidden="true"></div>
                    <div class="cv-exp-body">
                        <h4>
                            <?php echo htmlspecialchars($item['category_label'], ENT_QUOTES, 'UTF-8'); ?>
                            <?php if ($item['current']) : ?><span class="cv-now">τρέχουσα</span><?php endif; ?>
                        </h4>
                        <?php if ($item['type_label'] !== '') : ?>
                            <p class="cv-exp-type"><?php echo htmlspecialchars($item['type_label'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                        <p class="cv-exp-meta">
                            <span class="cv-chip"><?php echo htmlspecialchars($item['duration_label'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php if ($item['period_label'] !== '') : ?>
                                <span class="cv-muted"><?php echo htmlspecialchars($item['period_label'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <?php if ($item['transport_label'] !== '') : ?>
                                <span class="cv-muted"><?php echo htmlspecialchars($item['transport_label'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <?php if ($item['employment_label'] !== '') : ?>
                                <span class="cv-muted"><?php echo htmlspecialchars($item['employment_label'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </p>
                        <?php if ($item['description'] !== '') : ?>
                            <p class="cv-exp-desc"><?php echo htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</section>

<?php // ═══ ΣΕΜΙΝΑΡΙΑ & ΠΙΣΤΟΠΟΙΗΤΙΚΑ ══════════════════════════════ ?>
<section class="profile-section cv-section" id="cv-certifications">
    <div class="cv-head">
        <h2>Σεμινάρια &amp; Πιστοποιητικά</h2>
        <?php if ($certs['count'] > 0) : ?>
            <span class="cv-total"><strong><?php echo (int) $certs['count']; ?></strong> καταχωρημένα</span>
        <?php endif; ?>
    </div>

    <?php if ($certs['count'] === 0) : ?>
        <p class="cv-empty">
            Δεν έχετε καταχωρήσει σεμινάρια.
            <a href="<?php echo BASE_URL; ?>drivers/edit-profile#qualifications">Προσθέστε τα</a> —
            μπαίνουν αυτόματα στο βιογραφικό σας.
        </p>
    <?php else : ?>
        <ul class="cv-certs">
            <?php
            /*
             * Τα 5 πιο πρόσφατα εδώ· τα υπόλοιπα στην καρτέλα Προσόντα.
             * Η επισκόπηση είναι επισκόπηση — αν κάποιος έχει 20
             * σεμινάρια δεν βοηθά να τα δει όλα σε μια στήλη.
             */
            $shown = array_slice($certs['items'], 0, 5);
            foreach ($shown as $c) :
            ?>
                <li class="cv-cert<?php echo $c['expired'] ? ' cv-cert--expired' : ''; ?>">
                    <div class="cv-cert-main">
                        <strong><?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <?php if ($c['category_label'] !== '') : ?>
                            <span class="cv-chip cv-chip--soft"><?php echo htmlspecialchars($c['category_label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="cv-cert-meta">
                        <?php if ($c['provider'] !== '') : ?><span><?php echo htmlspecialchars($c['provider'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                        <?php if ($c['date_label'] !== '') : ?><span><?php echo htmlspecialchars($c['date_label'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                        <?php if ($c['duration'] > 0) : ?><span><?php echo (int) $c['duration']; ?> ώρες</span><?php endif; ?>
                        <?php if ($c['expired']) : ?>
                            <span class="cv-expired">Έληξε <?php echo htmlspecialchars($c['expiry_label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php elseif ($c['expiry_label'] !== '') : ?>
                            <span class="cv-muted">Λήξη <?php echo htmlspecialchars($c['expiry_label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($certs['count'] > count($shown)) : ?>
            <p class="cv-more">
                <a href="<?php echo BASE_URL; ?>drivers/profile#qualifications" data-tab-link="qualifications">
                    Όλα τα <?php echo (int) $certs['count']; ?> σεμινάρια →
                </a>
            </p>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php // ═══ ΓΛΩΣΣΕΣ & ΔΕΞΙΟΤΗΤΕΣ ════════════════════════════════════ ?>
<section class="profile-section cv-section" id="cv-languages">
    <div class="cv-head"><h2>Γλώσσες &amp; Δεξιότητες</h2></div>

    <div class="cv-two">
        <div class="cv-col">
            <h4 class="cv-subhead">Γλώσσες</h4>
            <?php if (empty($langs)) : ?>
                <p class="cv-empty">
                    Καμία δηλωμένη.
                    <a href="<?php echo BASE_URL; ?>drivers/edit-profile#qualifications">Προσθέστε</a> —
                    απαραίτητο για διεθνείς μεταφορές.
                </p>
            <?php else : ?>
                <ul class="cv-langs">
                    <?php foreach ($langs as $l) : ?>
                        <li>
                            <span class="cv-lang-name"><?php echo htmlspecialchars($l['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="cv-lang-level cv-lang-level--<?php echo htmlspecialchars($l['level'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($l['level_label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="cv-col">
            <h4 class="cv-subhead">
                Επαγγελματικές δεξιότητες
                <?php if ($skills['count'] > 0) : ?><span class="cv-count"><?php echo (int) $skills['count']; ?></span><?php endif; ?>
            </h4>
            <?php if (empty($skills['groups'])) : ?>
                <p class="cv-empty">
                    Καμία δηλωμένη.
                    <a href="<?php echo BASE_URL; ?>drivers/edit-profile#qualifications">Δηλώστε τις</a>.
                </p>
            <?php else : ?>
                <?php foreach ($skills['groups'] as $g) : ?>
                    <div class="cv-skill-group">
                        <span class="cv-skill-group-label"><?php echo htmlspecialchars($g['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <p class="cv-skill-list">
                            <?php foreach ($g['items'] as $skillLabel) : ?>
                                <span class="cv-chip cv-chip--soft"><?php echo htmlspecialchars($skillLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endforeach; ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

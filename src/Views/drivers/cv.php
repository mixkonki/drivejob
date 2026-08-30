<?php
/**
 * ΒΙΟΓΡΑΦΙΚΟ — οθόνη ελέγχου με ζωντανή προεπισκόπηση (31/08/2026).
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΜΙΑ ΟΘΟΝΗ ΚΑΙ ΟΧΙ ΟΔΗΓΟΣ ΒΗΜΑΤΩΝ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Ένας wizard έχει νόημα όταν συλλέγει δεδομένα που δεν υπάρχουν. Εδώ
 * υπάρχουν όλα: το βιογραφικό παράγεται από το προφίλ. Ένας οδηγός δεν
 * συμπληρώνει επτά οθόνες στο κινητό για να ξαναπεί όσα έχει ήδη πει.
 *
 * Αυτό που λείπει πραγματικά είναι τρία πράγματα, και χωράνε σε μία
 * οθόνη δίπλα στην προεπισκόπηση:
 *
 *   1. ΤΙ ΦΕΥΓΕΙ ΠΡΟΣ ΤΑ ΕΞΩ — φωτογραφία, ηλικία, τηλέφωνο, email.
 *      Το προφίλ τα έχει· το βιογραφικό δεν είναι υποχρεωμένο να τα
 *      δείξει όλα σε κάθε παραλήπτη.
 *   2. Η ΠΑΡΟΥΣΙΑΣΗ — δύο γραμμές με δικά του λόγια. Το πεδίο έρχεται
 *      ΓΕΜΑΤΟ με αυτόματη πρόταση από τα δεδομένα: ο οδηγός που δεν
 *      θέλει να γράψει παίρνει σωστό κείμενο, αυτός που θέλει το
 *      διορθώνει αντί να ξεκινά από λευκή σελίδα.
 *   3. ΝΑ ΔΕΙ ΤΙ ΘΑ ΣΤΕΙΛΕΙ πριν το στείλει.
 *
 * ΑΜΕΣΗ ΑΠΟΘΗΚΕΥΣΗ ΑΝΑ ΠΡΑΞΗ: κάθε διακόπτης αποθηκεύεται μόνος του,
 * όπως στα πιστοποιητικά και τις γλώσσες — καμία «Αποθήκευση Αλλαγών»
 * σε οθόνη με τέσσερα πεδία.
 *
 * Η προεπισκόπηση ενημερώνεται ΤΟΠΙΚΑ (κρύβει/δείχνει στοιχεία) ώστε η
 * αλλαγή να φαίνεται ακαριαία· η ίδια η δομή έρχεται από τον
 * DriverCvService, την ίδια πηγή που τυπώνει το PDF.
 *
 * Περιμένει: $cv, $cvOptions, $cvSummarySaved, $cvSummaryAuto, $driverData
 */

include ROOT_DIR . '/src/Views/partials/header.php';

$id = $cv['identity'];
$exp = $cv['experience'];
$certs = $cv['certifications'];
$langs = $cv['languages'];
$skills = $cv['skills'];
$groups = $cv['qualifications'] ?? [];
?>

<?= \Drivejob\Helpers\Asset::css('css/driver-cv.css') ?>

<main class="cv-page">
    <div class="cv-wrap">

        <header class="cv-topbar">
            <div>
                <h1>Το βιογραφικό μου</h1>
                <p class="cv-topbar-note">Παράγεται από το προφίλ σας. Ό,τι αλλάξετε εδώ ισχύει και για το PDF.</p>
            </div>
            <div class="cv-topbar-actions">
                <a href="<?php echo BASE_URL; ?>drivers/profile" class="cv-btn cv-btn-ghost">Πίσω στο προφίλ</a>
                <a href="<?php echo BASE_URL; ?>drivers/cv/pdf" class="cv-btn cv-btn-primary" target="_blank" rel="noopener">⬇ Λήψη PDF</a>
            </div>
        </header>

        <div class="cv-layout"
             id="cvRoot"
             data-save-url="<?php echo BASE_URL; ?>drivers/cv/settings"
             data-csrf="<?php echo htmlspecialchars(\Drivejob\Core\CSRF::token(), ENT_QUOTES, 'UTF-8'); ?>">

            <?php // ═══ ΡΥΘΜΙΣΕΙΣ ═══════════════════════════════════════ ?>
            <aside class="cv-side">
                <section class="cv-card">
                    <h2>Παρουσίαση</h2>
                    <p class="cv-hint">Οι πρώτες γραμμές που διαβάζει ο εργοδότης.</p>

                    <textarea id="cvSummary" maxlength="600" rows="5"
                              placeholder="Γράψτε δυο λόγια για εσάς…"><?php echo htmlspecialchars($cvSummarySaved !== '' ? $cvSummarySaved : $cvSummaryAuto, ENT_QUOTES, 'UTF-8'); ?></textarea>

                    <div class="cv-summary-foot">
                        <span id="cvCount">0</span>/600
                        <?php /* Το κουμπί επαναφοράς έχει νόημα μόνο όταν ο οδηγός
                           έχει γράψει δικό του κείμενο — αλλιώς «επαναφέρει» σε
                           αυτό που ήδη βλέπει. */ ?>
                        <button type="button" id="cvReset" class="cv-link"
                                data-auto="<?php echo htmlspecialchars($cvSummaryAuto, ENT_QUOTES, 'UTF-8'); ?>">
                            Αυτόματη πρόταση
                        </button>
                    </div>
                </section>

                <section class="cv-card">
                    <h2>Τι περιλαμβάνεται</h2>
                    <p class="cv-hint">Ό,τι κλείσετε δεν εμφανίζεται στο PDF.</p>

                    <?php
                    /*
                     * Η φωτογραφία είναι κλειστή από προεπιλογή: σε αρκετές
                     * χώρες θεωρείται μειονέκτημα σε βιογραφικό και δεν
                     * χρειάζεται για να κριθούν προσόντα. Τα υπόλοιπα
                     * ανοιχτά — CV χωρίς τηλέφωνο δεν καλείται από κανέναν.
                     */
                    $toggles = [
                        'photo' => ['Φωτογραφία', 'cv_show_photo'],
                        'age' => ['Ηλικία', 'cv_show_age'],
                        'phone' => ['Τηλέφωνα', 'cv_show_phone'],
                        'email' => ['Email', 'cv_show_email'],
                        'rating' => ['Αξιολογήσεις', 'cv_show_rating'],
                    ];
                    foreach ($toggles as $key => [$label, $field]) :
                        $on = !empty($cvOptions[$key]);
                        // Οι αξιολογήσεις δεν έχουν νόημα ως διακόπτης όταν
                        // δεν υπάρχει καμία — αλλά ο διακόπτης μένει ορατός
                        // και ανενεργός, ώστε να ξέρει ότι θα εμφανιστούν.
                        $disabled = ($key === 'rating' && empty($driverData['rating_count']));
                    ?>
                        <label class="cv-toggle<?php echo $disabled ? ' is-muted' : ''; ?>">
                            <input type="checkbox" data-opt="<?php echo $key; ?>" name="<?php echo $field; ?>"
                                   <?php echo $on ? 'checked' : ''; ?> <?php echo $disabled ? 'disabled' : ''; ?>>
                            <span class="cv-toggle-track"><span class="cv-toggle-knob"></span></span>
                            <span class="cv-toggle-label">
                                <?php echo $label; ?>
                                <?php if ($disabled) : ?><small>δεν υπάρχουν ακόμη</small><?php endif; ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </section>

                <?php
                /*
                 * Τι λείπει από το ΒΙΟΓΡΑΦΙΚΟ (όχι από το προφίλ): μόνο οι
                 * ενότητες που θα ήταν κενές. Η λίστα «τι λείπει» του
                 * προφίλ είναι άλλο πράγμα — εδώ μετράει μόνο ό,τι
                 * αδειάζει μια ενότητα του CV.
                 */
                $gaps = [];
                if ($exp['count'] === 0) {
                    $gaps[] = ['Προϋπηρεσία', 'drivers/vehicle-experience', 'Η ενότητα που κοιτάζει πρώτη ο εργοδότης.'];
                }
                if ($certs['count'] === 0) {
                    $gaps[] = ['Σεμινάρια', 'drivers/certifications', ''];
                }
                if (empty($langs)) {
                    $gaps[] = ['Γλώσσες', 'drivers/edit-profile', ''];
                }
                if (empty($skills['groups'])) {
                    $gaps[] = ['Δεξιότητες', 'drivers/edit-profile', ''];
                }
                ?>
                <?php if ($gaps) : ?>
                    <section class="cv-card cv-card-gaps">
                        <h2>Κενές ενότητες</h2>
                        <ul>
                            <?php foreach ($gaps as [$label, $link, $why]) : ?>
                                <li>
                                    <a href="<?php echo BASE_URL . $link; ?>"><?php echo $label; ?></a>
                                    <?php if ($why !== '') : ?><small><?php echo $why; ?></small><?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <p class="cv-saved" id="cvSaved" hidden>Αποθηκεύτηκε</p>
            </aside>

            <?php // ═══ ΠΡΟΕΠΙΣΚΟΠΗΣΗ ═══════════════════════════════════ ?>
            <div class="cv-preview-wrap">
                <div class="cv-paper" id="cvPaper">

                    <header class="cvp-head">
                        <?php /* Η φωτογραφία υπάρχει πάντα στο DOM και κρύβεται με
                           κλάση: ο διακόπτης πρέπει να τη δείχνει ακαριαία, χωρίς
                           να ξαναφορτώσει η σελίδα. */ ?>
                        <div class="cvp-photo" data-part="photo" <?php echo empty($cvOptions['photo']) ? 'hidden' : ''; ?>>
                            <?php if (!empty($driverData['profile_image'])) : ?>
                                <img src="<?php echo BASE_URL . htmlspecialchars($driverData['profile_image']); ?>" alt="">
                            <?php endif; ?>
                        </div>

                        <div class="cvp-head-main">
                            <h2><?php echo htmlspecialchars($id['full_name'], ENT_QUOTES, 'UTF-8'); ?></h2>

                            <p class="cvp-line1">
                                <span data-part="age" <?php echo empty($cvOptions['age']) ? 'hidden' : ''; ?>><?php echo htmlspecialchars((string) ($id['age'] !== null ? $id['age'] . ' ετών' : ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if ($id['location'] !== '') : ?>
                                    <span><?php echo htmlspecialchars($id['location'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </p>

                            <p class="cvp-line2">
                                <span data-part="phone" <?php echo empty($cvOptions['phone']) ? 'hidden' : ''; ?>><?php echo htmlspecialchars((string) ($driverData['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <span data-part="email" <?php echo empty($cvOptions['email']) ? 'hidden' : ''; ?>><?php echo htmlspecialchars((string) ($driverData['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                            </p>

                            <?php if (!empty($id['reach']['declared'])) : ?>
                                <p class="cvp-line2"><span><?php echo htmlspecialchars($id['reach']['label'], ENT_QUOTES, 'UTF-8'); ?></span></p>
                            <?php endif; ?>

                            <p class="cvp-line2" data-part="rating" <?php echo (empty($cvOptions['rating']) || empty($driverData['rating_count'])) ? 'hidden' : ''; ?>>
                                <span>Αξιολόγηση <?php echo number_format((float) ($driverData['rating'] ?? 0), 1); ?>/5 από <?php echo (int) ($driverData['rating_count'] ?? 0); ?> εργοδότες</span>
                            </p>
                        </div>
                    </header>

                    <p class="cvp-summary" id="cvPaperSummary"><?php echo htmlspecialchars($cvSummarySaved !== '' ? $cvSummarySaved : $cvSummaryAuto, ENT_QUOTES, 'UTF-8'); ?></p>

                    <?php if ($exp['count'] > 0) : ?>
                        <section class="cvp-sec">
                            <h3>Προϋπηρεσία <small>Σύνολο <?php echo htmlspecialchars($exp['total_label'], ENT_QUOTES, 'UTF-8'); ?></small></h3>
                            <?php foreach ($exp['items'] as $item) : ?>
                                <article class="cvp-row">
                                    <div class="cvp-when"><?php echo htmlspecialchars($item['period_label'] ?: $item['duration_label'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="cvp-what">
                                        <strong>
                                            <?php echo htmlspecialchars($item['category_label'], ENT_QUOTES, 'UTF-8'); ?>
                                            <?php if ($item['type_label'] !== '') : ?> — <?php echo htmlspecialchars($item['type_label'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                                            <?php if ($item['current']) : ?><span class="cvp-now">τρέχουσα</span><?php endif; ?>
                                        </strong>
                                        <span class="cvp-meta"><?php echo htmlspecialchars(implode('  ·  ', array_filter([$item['duration_label'], $item['transport_label'], $item['employment_label']])), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php if ($item['description'] !== '') : ?>
                                            <span class="cvp-desc"><?php echo htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </section>
                    <?php endif; ?>

                    <?php
                    // Ομάδες όπου τίποτα δεν κατέχεται δεν μπαίνουν: ο εργοδότης
                    // δεν χρειάζεται λίστα με ό,τι ΔΕΝ έχει ο οδηγός.
                    $ownedGroups = [];
                    foreach ($groups as $g) {
                        $owned = array_filter($g['items'], static fn($i) => empty($i['absent']));
                        if ($owned) {
                            $ownedGroups[] = ['title' => $g['title'], 'meta' => $g['meta'] ?? null, 'items' => $owned];
                        }
                    }
                    ?>
                    <?php if ($ownedGroups) : ?>
                        <section class="cvp-sec">
                            <h3>Άδειες &amp; πιστοποιήσεις</h3>
                            <?php foreach ($ownedGroups as $g) : ?>
                                <div class="cvp-group">
                                    <h4>
                                        <?php echo htmlspecialchars($g['title'], ENT_QUOTES, 'UTF-8'); ?>
                                        <?php if ($g['meta']) : ?>
                                            <small><?php echo htmlspecialchars($g['meta']['key'] . ' ' . $g['meta']['value'], ENT_QUOTES, 'UTF-8'); ?></small>
                                        <?php endif; ?>
                                    </h4>
                                    <?php foreach ($g['items'] as $item) : ?>
                                        <div class="cvp-qual">
                                            <?php
                                            $head = trim($item['title'] . (!empty($item['tag']) ? ' (' . $item['tag'] . ')' : ''));
                                            if (!empty($item['cats'])) {
                                                $head = ($head !== '' ? $head . ': ' : '') . implode(', ', $item['cats']);
                                            }
                                            ?>
                                            <?php if ($head !== '') : ?><strong><?php echo htmlspecialchars($head, ENT_QUOTES, 'UTF-8'); ?></strong><?php endif; ?>
                                            <?php if (!empty($item['subtitle'])) : ?><span class="cvp-sub"><?php echo htmlspecialchars($item['subtitle'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                                            <?php
                                            $meta = [];
                                            foreach ($item['lines'] as $l) {
                                                $meta[] = trim(($l['key'] !== '' ? $l['key'] . ' ' : '') . $l['value']);
                                            }
                                            foreach ($item['expiries'] as $e) {
                                                $meta[] = $e['label'] . ' ' . $e['date'];
                                            }
                                            ?>
                                            <?php if ($meta) : ?><span class="cvp-meta"><?php echo htmlspecialchars(implode('  ·  ', $meta), ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                                            <?php if (!empty($item['covers_all'])) : ?>
                                                <span class="cvp-sub">Σύνολο μηχανημάτων της ειδικότητας</span>
                                            <?php elseif (!empty($item['subs'])) : ?>
                                                <span class="cvp-sub"><?php
                                                    $parts = [];
                                                    foreach ($item['subs'] as $sub) {
                                                        $parts[] = $sub['code'] . ' ' . $sub['name'] . ($sub['group'] !== '' ? ' (' . $sub['group'] . ')' : '');
                                                    }
                                                    echo htmlspecialchars(implode(' · ', $parts), ENT_QUOTES, 'UTF-8');
                                                ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </section>
                    <?php endif; ?>

                    <?php if ($certs['count'] > 0) : ?>
                        <section class="cvp-sec">
                            <h3>Επιμόρφωση &amp; σεμινάρια</h3>
                            <?php foreach ($certs['items'] as $c) : ?>
                                <article class="cvp-row">
                                    <div class="cvp-when"><?php echo htmlspecialchars($c['date_label'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="cvp-what">
                                        <strong><?php echo htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <span class="cvp-meta"><?php echo htmlspecialchars(implode('  ·  ', array_filter([
                                            $c['provider'],
                                            $c['category_label'],
                                            $c['duration'] > 0 ? $c['duration'] . ' ώρες' : '',
                                            $c['expiry_label'] !== '' ? ($c['expired'] ? 'έληξε ' : 'λήξη ') . $c['expiry_label'] : '',
                                        ])), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($langs) || !empty($skills['groups'])) : ?>
                        <section class="cvp-sec">
                            <h3>Γλώσσες &amp; δεξιότητες</h3>
                            <?php if ($langs) : ?>
                                <article class="cvp-row">
                                    <div class="cvp-when">Γλώσσες</div>
                                    <div class="cvp-what"><span class="cvp-plain"><?php
                                        $parts = [];
                                        foreach ($langs as $l) {
                                            $parts[] = $l['name'] . ' (' . $l['level_label'] . ')';
                                        }
                                        echo htmlspecialchars(implode(', ', $parts), ENT_QUOTES, 'UTF-8');
                                    ?></span></div>
                                </article>
                            <?php endif; ?>
                            <?php foreach ($skills['groups'] as $g) : ?>
                                <article class="cvp-row">
                                    <div class="cvp-when"><?php echo htmlspecialchars($g['label'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="cvp-what"><span class="cvp-plain"><?php echo htmlspecialchars(implode(', ', $g['items']), ENT_QUOTES, 'UTF-8'); ?></span></div>
                                </article>
                            <?php endforeach; ?>
                        </section>
                    <?php endif; ?>

                    <p class="cvp-foot">Βιογραφικό από το DriveJob · <?php echo date('d/m/Y'); ?> · drivejob.gr</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?= \Drivejob\Helpers\Asset::js('js/driver-cv.js', true) ?>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>

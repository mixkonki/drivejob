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
                    $gaps[] = ['Προϋπηρεσία', 'drivers/vehicle-experience?from=cv', 'Η ενότητα που κοιτάζει πρώτη ο εργοδότης.'];
                }
                if ($certs['count'] === 0) {
                    $gaps[] = ['Σεμινάρια', 'drivers/certifications?from=cv', ''];
                }
                if (empty($langs)) {
                    $gaps[] = ['Γλώσσες', 'drivers/edit-profile#skills-tab~dj-languages', ''];
                }
                if (empty($skills['groups'])) {
                    $gaps[] = ['Δεξιότητες', 'drivers/edit-profile#skills-tab~dj-skills', ''];
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
                <?php include ROOT_DIR . '/src/Views/drivers/partials/_cv-paper.php'; ?>
            </div>
        </div>
    </div>
</main>

<?= \Drivejob\Helpers\Asset::js('js/driver-cv.js', true) ?>

<?php include ROOT_DIR . '/src/Views/partials/footer.php'; ?>

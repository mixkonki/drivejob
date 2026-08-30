<?php
/**
 * Δημόσιο προφίλ οδηγού — ό,τι βλέπει η ΕΤΑΙΡΕΙΑ. (ξαναγράφτηκε 01/09/2026 — Φάση Α)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΑΝΤΙΚΑΤΕΣΤΗΣΕ
 * ══════════════════════════════════════════════════════════════════════
 *
 * 1.139 γραμμές παλιάς όψης που δεν ήξεραν τίποτα από όσα χτίστηκαν:
 * ούτε βαθμολογία, ούτε επίπεδα τεκμηρίου, ούτε τους διακόπτες
 * ιδιωτικότητας του βιογραφικού. Η εταιρεία που άνοιγε υποψήφιο έβλεπε
 * την προ-βαθμολογίας εποχή — όλη η δουλειά του συστήματος αξιολόγησης
 * δεν έφτανε ποτέ στο μοναδικό της ακροατήριο.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΔΕΙΧΝΕΙ ΤΩΡΑ — ΚΑΙ ΜΕ ΠΟΙΟΥΣ ΚΑΝΟΝΕΣ
 * ══════════════════════════════════════════════════════════════════════
 *
 *   1. Κεφαλίδα με διαθεσιμότητα και περιοχή εργασίας.
 *   2. Βαθμολογία: ΜΟΝΟ αν ο οδηγός έχει ανοιχτό τον διακόπτη
 *      «αξιολόγηση» του βιογραφικού (cv_show_rating). Ο κανόνας του
 *      σχεδιασμού ιδιωτικότητας: ο οδηγός βλέπει τον βαθμό του πριν
 *      αποφασίσει αν δημοσιεύεται — ο διακόπτης είναι η απόφασή του.
 *   3. Το ΙΔΙΟ «χαρτί» βιογραφικού που βλέπει ο οδηγός στην
 *      προεπισκόπησή του (_cv-paper.php) — με τις δικές του επιλογές
 *      για φωτογραφία/ηλικία/τηλέφωνο/email.
 *   4. Για εταιρεία-θεατή: η κατάσταση της αίτησης του οδηγού και
 *      κουμπί «Μήνυμα».
 *
 * Η επικοινωνία (email/τηλέφωνο) έρχεται ΗΔΗ μασκαρισμένη από τον
 * controller όταν η εταιρεία δεν έχει δικαίωμα πλήρων στοιχείων — το
 * view δεν έχει τρόπο να δείξει ό,τι δεν πρέπει.
 *
 * Περιμένει στο scope: $driverData, $cv, $cvOptions, $cvSummarySaved,
 * $cvSummaryAuto, $viewerApplications (προαιρετικά), $canViewContact
 */

$id = $cv['identity'];
$exp = $cv['experience'];
$certs = $cv['certifications'];
$langs = $cv['languages'];
$skills = $cv['skills'];
$groups = $cv['qualifications'] ?? [];

$score = $driverData['score'] ?? null;
$showScore = !empty($driverData['cv_show_rating']) && $score;

$isCompanyViewer = \Drivejob\Core\Session::get('user_role') === 'company';
?>

<?= \Drivejob\Helpers\Asset::css('css/driver-cv.css') ?>
<?= \Drivejob\Helpers\Asset::css('css/driver-overview.css') ?>
<?= \Drivejob\Helpers\Asset::css('css/driver-score.css') ?>

<main class="cv-page">
    <div class="cv-wrap pubp-wrap">

        <?php /* ═══ ΚΕΦΑΛΙΔΑ ════════════════════════════════════════════ */ ?>
        <header class="pubp-head">
            <div class="pubp-id">
                <h1><?php echo htmlspecialchars($id['full_name'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="pubp-meta">
                    <?php if ($id['location'] !== '') : ?>
                        <span><?php echo htmlspecialchars($id['location'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($id['reach']['declared'])) : ?>
                        <span><?php echo htmlspecialchars($id['reach']['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </p>
                <p class="avail-pill <?php echo !empty($driverData['available_for_work']) ? 'is-on' : 'is-off'; ?>">
                    <span class="avail-dot" aria-hidden="true"></span>
                    <?php echo !empty($driverData['available_for_work']) ? 'Διαθέσιμος για εργασία' : 'Μη διαθέσιμος'; ?>
                </p>
            </div>

            <?php /* Ενέργειες εταιρείας: η αίτηση που τους έφερε εδώ + μήνυμα. */ ?>
            <?php if ($isCompanyViewer) : ?>
                <div class="pubp-actions">
                    <?php if (!empty($viewerApplications)) : ?>
                        <?php $app = $viewerApplications[0]; ?>
                        <p class="pubp-app">
                            Αίτηση για: <strong><?php echo htmlspecialchars($app['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <em>(<?php echo date('d/m/Y', strtotime($app['created_at'])); ?>)</em>
                        </p>
                        <div class="pubp-btns">
                            <a class="btn-primary" href="<?php echo BASE_URL; ?>job-applications/listing/<?php echo (int) $app['job_listing_id']; ?>">Διαχείριση αίτησης</a>
                            <form method="post" action="<?php echo BASE_URL; ?>companies/message-driver" class="pubp-msgform">
                                <input type="hidden" name="csrf_token" value="<?php echo \Drivejob\Core\CSRF::token(); ?>">
                                <input type="hidden" name="driver_id" value="<?php echo (int) $driverData['id']; ?>">
                                <button type="submit" class="btn-secondary">Μήνυμα στον οδηγό</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </header>

        <?php /* ═══ ΒΑΘΜΟΛΟΓΙΑ — μόνο με τη συγκατάθεση του οδηγού ═════ */ ?>
        <?php if ($showScore) : ?>
            <section class="qgroup qgroup--why pubp-score">
                <header class="qgroup-head">
                    <h3>Αξιολόγηση DriveJob</h3>
                    <span class="qgroup-meta">κατά ισχύ τεκμηρίου</span>
                </header>
                <div class="qgroup-body">
                    <div class="score-pair pubp-score-pair">
                        <div class="score-metric">
                            <span class="score-metric-key">Προσόντα &amp; χαρτιά</span>
                            <span class="score-metric-val"><?php echo round((float) $score['credentials']); ?><i>/100</i></span>
                            <div class="score-bar"><div style="width: <?php echo round((float) $score['credentials']); ?>%"></div></div>
                            <span class="score-metric-note">Άδειες, ΠΕΙ, πιστοποιητικά, ένσημα — επαληθεύσιμα έγγραφα.</span>
                        </div>
                        <div class="score-metric<?php echo $score['reputation'] === null ? ' score-metric--empty' : ''; ?>">
                            <span class="score-metric-key">Φήμη</span>
                            <?php if ($score['reputation'] !== null) : ?>
                                <span class="score-metric-val"><?php echo round((float) $score['reputation']); ?><i>/100</i></span>
                                <div class="score-bar"><div style="width: <?php echo round((float) $score['reputation']); ?>%"></div></div>
                                <span class="score-metric-note">Από επώνυμες συστάσεις εργοδοτών — όχι από δηλώσεις.</span>
                            <?php else : ?>
                                <span class="score-metric-val score-metric-val--none">—</span>
                                <span class="score-metric-note">Χωρίς αξιολογήσεις τρίτων ακόμη.</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($score['total'] !== null) : ?>
                            <div class="score-metric">
                                <span class="score-metric-key">Συνολικά</span>
                                <span class="score-metric-val"><?php echo round((float) $score['total']); ?><i>/100</i></span>
                                <div class="score-bar"><div style="width: <?php echo round((float) $score['total']); ?>%"></div></div>
                                <span class="score-metric-note"><?php echo htmlspecialchars($score['label'], ENT_QUOTES, 'UTF-8'); ?> ·
                                    στηρίζεται στο <?php echo round((float) $score['confidence']); ?>% των πηγών.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php /* ═══ ΤΟ ΒΙΟΓΡΑΦΙΚΟ — το ίδιο χαρτί με την προεπισκόπηση ══ */ ?>
        <div class="cv-preview-wrap pubp-paper">
            <?php include __DIR__ . '/partials/_cv-paper.php'; ?>
        </div>
    </div>
</main>

<style>
    /* Μικρά στυλ της δημόσιας όψης — δανείζεται cv/score/overview CSS. */
    .pubp-wrap { max-width: 900px; margin: 0 auto; padding: 1.2rem 1rem 2rem; }
    .pubp-head {
        display: flex; flex-wrap: wrap; align-items: flex-start;
        justify-content: space-between; gap: .8rem 1.5rem; margin-bottom: 1rem;
    }
    .pubp-id h1 { margin: 0 0 .25rem; font-size: 1.45rem; color: #1f2937; }
    .pubp-meta { display: flex; flex-wrap: wrap; gap: .2rem .8rem; margin: 0 0 .4rem; font-size: .86rem; color: #6b7280; }
    .pubp-actions { min-width: min(280px, 100%); }
    .pubp-app { margin: 0 0 .5rem; font-size: .84rem; color: #475569; }
    .pubp-app em { font-style: normal; color: #94a3b8; }
    .pubp-btns { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
    .pubp-msgform { margin: 0; display: inline; }
    .pubp-score { margin-bottom: 1rem; }
    .pubp-score-pair { grid-template-columns: repeat(auto-fit, minmax(min(200px, 100%), 1fr)); padding: .4rem 0 .3rem; }
    .pubp-paper .cv-paper { margin: 0 auto; }
</style>

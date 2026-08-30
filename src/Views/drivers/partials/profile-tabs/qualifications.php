<?php
/**
 * Καρτέλα «Προσόντα & Πιστοποιήσεις». (ξαναγράφτηκε 31/08/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΑΛΛΑΞΕ ΚΑΙ ΓΙΑΤΙ
 * ══════════════════════════════════════════════════════════════════════
 *
 * 1. ΙΔΙΑ ΓΛΩΣΣΑ ΜΕ ΤΗΝ ΕΠΙΣΚΟΠΗΣΗ. Η καρτέλα ήταν το τελευταίο σημείο
 *    με τα παλιά γκρι κουτιά «skills-category» και τα ροζέ «skill-tag»,
 *    ενώ δίπλα της η Επισκόπηση είχε ήδη τις κάρτες `.qgroup` με
 *    χρωματική ταυτότητα ανά ομάδα. Δύο σχεδιαστικές γλώσσες σε δύο
 *    καρτέλες της ίδιας σελίδας.
 *
 * 2. ΤΑ ΟΝΟΜΑΤΑ ΤΩΝ ΔΕΞΙΟΤΗΤΩΝ ΕΦΥΓΑΝ ΑΠΟ ΕΔΩ. Ήταν γραμμένα ξανά —
 *    και με άλλα κεφαλαία («Αμυντική Οδήγηση» εδώ, «Αμυντική οδήγηση»
 *    στον helper). Τώρα διαβάζονται από το `DriverSkills`, όπως τα
 *    διαβάζουν το βιογραφικό και το PDF. −170 γραμμές.
 *
 * 3. ΔΙΑΤΑΞΗ (οδηγία 31/08):
 *      Προσωπικά Στοιχεία  → συμπαγή, μία σειρά που αναδιπλώνεται
 *      Γλωσσικές Ικανότητες → αμέσως από κάτω
 *      Δεξιότητες Οδηγού    → τέσσερις ομάδες, ΙΔΙΑ διάταξη σε όλες
 *      Προϋπηρεσία | Πιστοποιήσεις → δύο στήλες στην ίδια γραμμή
 *
 *    Η ασυνέπεια «οι Οδηγικές σε δύο στήλες, οι άλλες σε μία» ερχόταν
 *    από τα `inline-block` ροζέ: όποιες ετικέτες ήταν κοντές χωρούσαν
 *    δύο-δύο, οι μακριές μία-μία. Τώρα κάθε δεξιότητα είναι μία γραμμή
 *    με ✓ σε όλες τις ομάδες — η στοίχιση δεν εξαρτάται από το μήκος
 *    της λέξης.
 *
 * 4. ΣΥΝΔΕΣΜΟΣ ΕΠΕΞΕΡΓΑΣΙΑΣ ΣΕ ΚΑΘΕ ΟΜΑΔΑ, όχι ένα κουμπί στο τέλος
 *    που άφηνε τον οδηγό να ψάχνει σε ποια από τις επτά καρτέλες ζει
 *    το πεδίο.
 *
 * Περιμένει στο scope: $driverData, $driverSkills, $driverLanguages,
 *                      $driverCertifications, $driverVehicleExperience
 */

use Drivejob\Helpers\DriverSkills;

$editUrl = BASE_URL . 'drivers/edit-profile';

/** Το μολύβι του συνδέσμου «Επεξεργασία» — μία φορά, όχι επτά. */
$pen = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>';

/** Σύνδεσμος επεξεργασίας — ίδιο μοτίβο με την Επισκόπηση. */
$editLink = static function (string $href, string $label = 'Επεξεργασία') use ($pen): string {
    return '<a class="qgroup-edit" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">'
        . $pen . ' ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
};

/* ══════════════════════════════════════════════════════════════════════
   ΠΡΟΣΩΠΙΚΑ ΣΤΟΙΧΕΙΑ — κωδικοί σε ελληνικά, μία φορά
   ══════════════════════════════════════════════════════════════════════ */

$maritalStatus = [
    'single' => 'Άγαμος/η',
    'married' => 'Έγγαμος/η',
    'divorced' => 'Διαζευγμένος/η',
    'separated' => 'Σε διάσταση',
    'widowed' => 'Χήρος/α',
    'civil_partnership' => 'Σύμφωνο συμβίωσης',
    'no_answer' => 'Δεν απαντώ',
];

$educationLevels = [
    'primary' => 'Δημοτικό',
    'secondary_low' => 'Γυμνάσιο',
    'secondary_high' => 'Λύκειο',
    'vocational_low' => 'Επαγγελματική εκπαίδευση (Γυμνάσιο)',
    'vocational' => 'Επαγγελματική εκπαίδευση (Λύκειο)',
    'iek' => 'ΙΕΚ',
    'tei' => 'ΑΤΕΙ',
    'university' => 'ΑΕΙ',
    'postgraduate' => 'Μεταπτυχιακό',
    'doctorate' => 'Διδακτορικό',
    'no_answer' => 'Δεν απαντώ',
];

$militaryStatus = [
    'completed' => 'Εκπληρωμένες',
    'exempt' => 'Απαλλαγή',
    'postponed' => 'Αναβολή',
    'unfulfilled' => 'Μη εκπληρωμένες',
    'not_applicable' => 'Δεν απαιτείται',
    'no_answer' => 'Δεν απαντώ',
];

/** Ηλικία, όχι ημερομηνία γέννησης: αυτό ρωτά ο εργοδότης. */
$age = null;
if (!empty($driverData['birth_date'])) {
    try {
        $age = (new DateTime($driverData['birth_date']))->diff(new DateTime())->y;
    } catch (Exception $e) {
        $age = null;
    }
}

/** @var array<int, array{0:string, 1:?string}> ετικέτα → τιμή (null = κενό) */
$personal = [
    ['Ηλικία', $age !== null ? $age . ' ετών' : null],
    ['Οικογενειακή κατάσταση', $maritalStatus[$driverData['marital_status'] ?? ''] ?? ($driverData['marital_status'] ?: null)],
    ['Εκπαίδευση', $educationLevels[$driverData['education_level'] ?? ''] ?? ($driverData['education_level'] ?: null)],
    ['Στρατιωτικές υποχρεώσεις', $militaryStatus[$driverData['military_service'] ?? ''] ?? ($driverData['military_service'] ?: null)],
    ['Ποινικό μητρώο', !empty($driverData['legal_status'])
        ? ($driverData['legal_status'] === 'yes' ? 'Υπεύθυνη δήλωση λευκού μητρώου' : 'Όχι')
        : null],
];

/* ══════════════════════════════════════════════════════════════════════
   ΔΕΞΙΟΤΗΤΕΣ — εικονίδιο ανά ομάδα
   ══════════════════════════════════════════════════════════════════════
   Τα PNG (driving_skills_icon.png κ.λπ.) αντικαταστάθηκαν από inline SVG
   στο χρώμα της ομάδας: ίδιο μέγεθος παντού, καθαρά σε κάθε ανάλυση,
   μία λιγότερη αίτηση δικτύου το καθένα. */
$skillIcons = [
    'Οδηγικές ικανότητες' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/><path d="M12 2v7M4.9 19.1l4.3-4.3M19.1 19.1l-4.3-4.3"/></svg>',
    'Ασφάλεια & συμμόρφωση' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>',
    'Επαγγελματισμός' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>',
    'Τεχνικές γνώσεις' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.7 6.3a4 4 0 0 0 5 5l-9.4 9.4a2.1 2.1 0 0 1-3-3z"/><path d="M14.7 6.3 17.5 3.5"/></svg>',
];

/* Πόσες δεξιότητες έχει δηλώσει συνολικά — μπαίνει στον τίτλο ώστε ο
   οδηγός να βλέπει με μια ματιά αν η ενότητα είναι γεμάτη ή άδεια. */
$skillsTotal = 0;
foreach (DriverSkills::LABELS as $code => $_) {
    if (!empty($driverSkills[$code])) {
        $skillsTotal++;
    }
}
?>
            <!-- Καρτέλα Προσόντων & Πιστοποιήσεων -->
            <div class="tab-pane" id="qualifications">

                <?php /* ═══ ΠΡΟΣΩΠΙΚΑ ΣΤΟΙΧΕΙΑ ══════════════════════════
                   Ήταν πέντε κάρτες με εικονίδιο 40px η καθεμία, ~400px
                   ύψος για πέντε λέξεις. Τώρα μία σειρά που αναδιπλώνεται
                   μόνη της: ίδια πληροφορία, ~70px. */ ?>
                <section class="qgroup qgroup--pers">
                    <header class="qgroup-head">
                        <h3>Προσωπικά Στοιχεία</h3>
                        <?php echo $editLink($editUrl . '#personal-info~birth_date'); ?>
                    </header>
                    <div class="qgroup-body">
                        <div class="pd-grid">
                            <?php foreach ($personal as [$label, $value]) : ?>
                                <div class="pd-item">
                                    <span class="pd-key"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if ($value !== null && $value !== '') : ?>
                                        <span class="pd-val"><?php echo htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php else : ?>
                                        <span class="pd-val pd-val--empty">Δεν έχει καταχωρηθεί</span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>

                <?php /* ═══ ΓΛΩΣΣΙΚΕΣ ΙΚΑΝΟΤΗΤΕΣ ════════════════════════
                   Μετακόμισαν εδώ (31/08): ήταν δίπλα στην Προϋπηρεσία,
                   που ζητήθηκε να πάει με τις Πιστοποιήσεις. */ ?>
                <?php
                $languageLevelLabels = [
                    'native' => 'Μητρική Γλώσσα',
                    'fluent' => 'Άριστα',
                    'good' => 'Καλά',
                    'basic' => 'Βασικά',
                ];
                ?>
                <section class="qgroup qgroup--lang">
                    <header class="qgroup-head">
                        <h3>Γλωσσικές Ικανότητες</h3>
                        <?php if (!empty($driverLanguages)) : ?>
                            <span class="qgroup-meta"><strong><?php echo count($driverLanguages); ?></strong></span>
                        <?php endif; ?>
                        <?php echo $editLink($editUrl . '#skills-tab~dj-languages'); ?>
                    </header>
                    <div class="qgroup-body">
                        <?php if (empty($driverLanguages)) : ?>
                            <p class="qrow-empty">Δεν έχουν καταχωρηθεί γλώσσες.</p>
                        <?php else : ?>
                            <div class="languages-list">
                                <?php foreach ($driverLanguages as $lang) : ?>
                                    <div class="language-item">
                                        <div class="language-name"><?php echo htmlspecialchars($lang['language_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="language-level <?php echo htmlspecialchars($lang['level'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($languageLevelLabels[$lang['level']] ?? $lang['level'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <?php /* ═══ ΔΕΞΙΟΤΗΤΕΣ ΟΔΗΓΟΥ ═══════════════════════════
                   Οι ομάδες και τα ονόματα έρχονται από τον DriverSkills:
                   ό,τι βλέπει εδώ ο οδηγός είναι ό,τι τσέκαρε στη φόρμα
                   και ό,τι τυπώνεται στο βιογραφικό. */ ?>
                <section class="qgroup qgroup--skl">
                    <header class="qgroup-head">
                        <h3>Δεξιότητες Οδηγού</h3>
                        <?php if ($skillsTotal > 0) : ?>
                            <span class="qgroup-meta"><strong><?php echo $skillsTotal; ?></strong></span>
                        <?php endif; ?>
                        <?php echo $editLink($editUrl . '#skills-tab~dj-skills'); ?>
                    </header>
                    <div class="qgroup-body">
                        <div class="skl-cats">
                            <?php foreach (DriverSkills::GROUPS as $groupTitle => $codes) : ?>
                                <?php
                                $owned = [];
                                foreach ($codes as $code) {
                                    if (!empty($driverSkills[$code])) {
                                        $owned[] = DriverSkills::label($code);
                                    }
                                }
                                ?>
                                <div class="skl-cat<?php echo $owned ? '' : ' skl-cat--empty'; ?>">
                                    <div class="skl-cat-head">
                                        <span class="skl-cat-icon"><?php echo $skillIcons[$groupTitle] ?? ''; ?></span>
                                        <h4><?php echo htmlspecialchars($groupTitle, ENT_QUOTES, 'UTF-8'); ?></h4>
                                        <?php if ($owned) : ?>
                                            <span class="skl-cat-count"><?php echo count($owned); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($owned) : ?>
                                        <?php /* Μία δεξιότητα ανά γραμμή σε ΟΛΕΣ τις ομάδες:
                                           η στοίχιση δεν εξαρτάται από το μήκος της λέξης. */ ?>
                                        <ul class="skl-cat-list">
                                            <?php foreach ($owned as $label) : ?>
                                                <li><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else : ?>
                                        <p class="qrow-empty">Καμία δηλωμένη</p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (!empty($driverData['additional_skills'])) : ?>
                            <div class="skl-extra">
                                <span class="skl-cat-count-label">Επιπλέον δεξιότητες</span>
                                <p><?php echo nl2br(htmlspecialchars($driverData['additional_skills'], ENT_QUOTES, 'UTF-8')); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <?php /* ═══ ΠΡΟΫΠΗΡΕΣΙΑ | ΠΙΣΤΟΠΟΙΗΣΕΙΣ ════════════════
                   Δύο στήλες στην ίδια γραμμή (οδηγία 31/08). Και οι δύο
                   ενότητες εμφανίζονται ΠΑΝΤΑ, ακόμη και άδειες: πριν
                   κρύβονταν όταν δεν υπήρχε εγγραφή, οπότε ο οδηγός δεν
                   είχε από πού να καταλάβει ότι λείπουν. */ ?>
                <div class="profile-two-col">

                    <section class="qgroup qgroup--exp">
                        <header class="qgroup-head">
                            <h3>Προϋπηρεσία σε Οχήματα</h3>
                            <?php if (!empty($driverVehicleExperience)) : ?>
                                <span class="qgroup-meta"><strong><?php echo count($driverVehicleExperience); ?></strong></span>
                            <?php endif; ?>
                            <?php echo $editLink(BASE_URL . 'drivers/vehicle-experience', 'Διαχείριση'); ?>
                        </header>
                        <div class="qgroup-body">
                            <?php if (empty($driverVehicleExperience)) : ?>
                                <p class="qrow-empty">Δεν έχει καταχωρηθεί προϋπηρεσία.</p>
                            <?php else : ?>
                                <?php
                                $vehicleCategories = [
                                    'lcv' => 'Ελαφρά Επαγγελματικά Οχήματα',
                                    'rigid_truck' => 'Μεσαία & Βαρέα Φορτηγά',
                                    'articulated' => 'Αρθρωτά/Συρόμενα Οχήματα',
                                    'taxi' => 'Ταξί',
                                    'minibus' => 'Μικρό Λεωφορείο',
                                    'bus' => 'Λεωφορεία & Πούλμαν',
                                    'utility' => 'Οχήματα Δημοτικά/Κοινής Ωφέλειας',
                                    'construction' => 'Οχήματα Έργων/Κατασκευών',
                                    'emergency' => 'Οχήματα Έκτακτης Ανάγκης',
                                    'specialized' => 'Εξειδικευμένα Οχήματα',
                                ];
                                ?>
                                <div class="vehicle-experience-list">
                                    <?php foreach ($driverVehicleExperience as $exp) : ?>
                                        <div class="vehicle-experience-item">
                                            <div class="vehicle-experience-header">
                                                <div class="vehicle-title">
                                                    <?php
                                                    echo htmlspecialchars(
                                                        $vehicleCategories[$exp['vehicle_category']] ?? (string) $exp['vehicle_category'],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    );
                                                    $sub = $exp['vehicle_type_name'] ?? ($exp['vehicle_type'] ?? '');
                                                    if ($sub !== '' && $sub !== null) {
                                                        echo ' — ' . htmlspecialchars((string) $sub, ENT_QUOTES, 'UTF-8');
                                                    }
                                                    ?>
                                                </div>
                                                <div class="vehicle-years"><?php echo (int) $exp['years']; ?> <?php echo (int) $exp['years'] === 1 ? 'έτος' : 'έτη'; ?></div>
                                            </div>

                                            <?php if (!empty($exp['start_date']) || !empty($exp['end_date'])) : ?>
                                                <div class="vehicle-period">
                                                    <?php
                                                    echo !empty($exp['start_date']) ? date('m/Y', strtotime($exp['start_date'])) : '—';
                                                    echo ' έως ';
                                                    echo !empty($exp['end_date']) ? date('m/Y', strtotime($exp['end_date'])) : 'σήμερα';
                                                    ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($exp['description'])) : ?>
                                                <div class="vehicle-description"><?php echo nl2br(htmlspecialchars($exp['description'], ENT_QUOTES, 'UTF-8')); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="qgroup qgroup--sem">
                        <header class="qgroup-head">
                            <h3>Πιστοποιήσεις &amp; Σεμινάρια</h3>
                            <?php if (!empty($driverCertifications)) : ?>
                                <span class="qgroup-meta"><strong><?php echo count($driverCertifications); ?></strong></span>
                            <?php endif; ?>
                            <?php echo $editLink(BASE_URL . 'drivers/certifications', 'Διαχείριση'); ?>
                        </header>
                        <div class="qgroup-body">
                            <?php if (empty($driverCertifications)) : ?>
                                <p class="qrow-empty">Δεν έχουν καταχωρηθεί πιστοποιήσεις ή σεμινάρια.</p>
                            <?php else : ?>
                                <div class="certifications-list">
                                    <?php foreach ($driverCertifications as $cert) : ?>
                                        <div class="certification-item">
                                            <div class="certification-header">
                                                <h4><?php echo htmlspecialchars($cert['title'], ENT_QUOTES, 'UTF-8'); ?></h4>
                                                <?php if (!empty($cert['expiry'])) :
                                                    $expiryTs = strtotime($cert['expiry']);
                                                    $isExpired = $expiryTs < time();
                                                    $soon = !$isExpired && ($expiryTs - time()) < 60 * 60 * 24 * 90;
                                                    ?>
                                                    <?php /* «Σε ισχύ» ΔΕΝ εμφανίζεται — είναι το αναμενόμενο
                                                       και επαναλαμβανόταν σε κάθε γραμμή. Ένδειξη μπαίνει
                                                       μόνο όταν κάτι χρειάζεται προσοχή. (ίδιος κανόνας
                                                       με την Επισκόπηση) */ ?>
                                                    <?php if ($isExpired || $soon) : ?>
                                                        <div class="certification-status <?php echo $isExpired ? 'expired' : 'expiring-soon'; ?>">
                                                            <?php echo $isExpired ? 'Έχει λήξει' : 'Λήγει σύντομα'; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="certification-details">
                                                <?php if (!empty($cert['provider'])) : ?>
                                                    <div class="certification-provider"><?php echo htmlspecialchars($cert['provider'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php endif; ?>

                                                <?php if (!empty($cert['date']) || !empty($cert['expiry'])) : ?>
                                                    <div class="certification-dates">
                                                        <?php if (!empty($cert['date'])) : ?>
                                                            <span><strong>Απόκτηση</strong> <?php echo date('d/m/Y', strtotime($cert['date'])); ?></span>
                                                        <?php endif; ?>
                                                        <?php if (!empty($cert['expiry'])) : ?>
                                                            <span><strong>Λήξη</strong> <?php echo date('d/m/Y', strtotime($cert['expiry'])); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($cert['description'])) : ?>
                                                    <div class="certification-description"><?php echo nl2br(htmlspecialchars($cert['description'], ENT_QUOTES, 'UTF-8')); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php /* Το ελεύθερο κείμενο «Εκπαιδευτικά Σεμινάρια» ήταν δική
                               του ενότητα στο τέλος της σελίδας, μακριά από τις
                               πιστοποιήσεις που περιγράφει. Μπαίνει εδώ. */ ?>
                            <?php if (!empty($driverData['training_seminars']) && !empty($driverData['training_details'])) : ?>
                                <div class="skl-extra">
                                    <span class="skl-cat-count-label">Εκπαιδευτικά σεμινάρια</span>
                                    <p><?php echo nl2br(htmlspecialchars($driverData['training_details'], ENT_QUOTES, 'UTF-8')); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                </div><!-- /.profile-two-col -->
            </div>

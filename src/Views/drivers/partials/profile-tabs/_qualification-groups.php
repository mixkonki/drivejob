<?php
/**
 * Τυπικά προσόντα σε ΟΠΤΙΚΕΣ ΟΜΑΔΕΣ — αντικαθιστά τον ενιαίο πίνακα.
 * (30/08/2026, αίτημα του Κώστα.)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΑΛΛΑΞΕ ΚΑΙ ΓΙΑΤΙ
 * ══════════════════════════════════════════════════════════════════════
 *
 * ΠΡΙΝ: ένας πίνακας 4 στηλών με 6+ γραμμές στη σειρά — δίπλωμα, ΠΕΙ,
 * ADR, ταχογράφος, χειριστής και ειδικές άδειες όλα με την ίδια οπτική
 * βαρύτητα. Δεν φαινόταν ότι το ΠΕΙ ανήκει στο δίπλωμα, ούτε ότι οι
 * ειδικότητες μηχανημάτων είναι άλλος κόσμος από την οδήγηση.
 *
 * ΤΩΡΑ: τέσσερις ομάδες με δικό της χρώμα και παχιά αριστερή γραμμή η
 * καθεμία:
 *
 *   1. Οδήγηση            — δίπλωμα + τα δύο ΠΕΙ (τι οδηγεί)
 *   2. Πιστοποιήσεις      — ADR + ψηφιακός ταχογράφος (τι μεταφέρει,
 *                            με τι κανόνες)
 *   3. Μηχανήματα έργου   — ΜΙΑ ΚΑΡΤΑ ΑΝΑ ΕΙΔΙΚΟΤΗΤΑ, όχι όλες
 *                            στριμωγμένες σε ένα κελί
 *   4. Ειδικές άδειες     — ΕΔΧ, ζώντα ζώα, ΠΕΕ κ.λπ.
 *
 * Κάθε προσόν έχει δικό του εικονίδιο (QualIcons — inline SVG).
 *
 * ΓΙΑΤΙ ΟΧΙ ΠΙΝΑΚΑΣ: τα δεδομένα δεν είναι ομοιογενής μήτρα — μια
 * ειδικότητα χειριστή έχει λίστα υποειδικοτήτων, ένα δίπλωμα έχει
 * κατηγορίες, μια ειδική άδεια έχει αριθμό. Ο πίνακας τα ισοπέδωνε και
 * στο κινητό γινόταν οριζόντια κύλιση. Οι ομάδες με grid στοιβάζονται
 * καθαρά κάτω από τα 720px.
 *
 * Περιμένει στο scope: $driverData, $driverLicenses, $driverLicenseTypes,
 * $hasPeiC/$hasPeiD, $peiCExpiryDate/$peiDExpiryDate, $driverADR,
 * $driverTachograph, $driverOperatorLicenses, $driverOperator,
 * $driverSpecialLicenses.
 */

use Drivejob\Helpers\QualIcons;
use Drivejob\Helpers\OperatorSpecialities;
use Drivejob\Helpers\SpecialLicenseTypes;

/**
 * Κατάσταση από ημερομηνία λήξης. Ένα σημείο απόφασης για ΟΛΑ τα
 * προσόντα — πριν ο ίδιος υπολογισμός ήταν γραμμένος έξι φορές, με
 * μικροδιαφορές στα κείμενα («Έγκυρη» / «Έγκυρο» / «Μη έγκυρη»).
 *
 * @return array{0:string,1:string} [κλάση, ετικέτα]
 */
$qualStatus = static function (?string $date, bool $exists = true): array {
    if (!$exists) {
        return ['none', 'Δεν διαθέτει'];
    }
    if (empty($date)) {
        return ['unknown', 'Χωρίς λήξη'];
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return ['unknown', 'Άγνωστη λήξη'];
    }
    if ($ts < time()) {
        return ['expired', 'Έληξε'];
    }
    if (($ts - time()) < 60 * 60 * 24 * 90) {
        return ['soon', 'Λήγει σύντομα'];
    }
    return ['valid', 'Σε ισχύ'];
};

/** Ημερομηνία σε ελληνική μορφή ή παύλα. */
$qualDate = static function (?string $date): string {
    if (empty($date)) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date('d/m/Y', $ts) : '—';
};

// ── Λήξεις διπλώματος: εντύπου και κατηγοριών ───────────────────────────
$earliestExpiry = null;
foreach (($driverLicenses ?? []) as $lic) {
    if (!empty($lic['expiry_date'])) {
        if ($earliestExpiry === null || strtotime($lic['expiry_date']) < strtotime($earliestExpiry)) {
            $earliestExpiry = $lic['expiry_date'];
        }
    }
}
$documentExpiry = !empty($driverData['license_document_expiry']) ? $driverData['license_document_expiry'] : null;
$effectiveExpiry = $earliestExpiry;
if ($documentExpiry && (!$effectiveExpiry || strtotime($documentExpiry) < strtotime($effectiveExpiry))) {
    $effectiveExpiry = $documentExpiry;
}

$hasLicense = !empty($driverData['license_number']) || !empty($driverLicenseTypes);
$opList = $driverOperatorLicenses ?? [];
// Με τη σειρά του βιβλιαρίου (1η, 2η, 3η...), όχι με τη σειρά καταχώρησης:
// ο χειριστής ψάχνει την ειδικότητα με τον αριθμό της.
usort($opList, static fn($a, $b) => ((int) ($a['speciality'] ?? 0)) <=> ((int) ($b['speciality'] ?? 0)));
$slList = $driverSpecialLicenses ?? [];
?>

<section class="qual-groups">

    <?php // ═══ ΟΜΑΔΑ 1: ΟΔΗΓΗΣΗ ═══════════════════════════════════════ ?>
    <div class="qgroup qgroup--driving">
        <header class="qgroup-head">
            <h3>Οδήγηση</h3>
            <span class="qgroup-note">Τι έχει δικαίωμα να οδηγεί</span>
        </header>
        <div class="qgroup-body">

            <?php list($stCls, $stTxt) = $qualStatus($effectiveExpiry, $hasLicense); ?>
            <article class="qrow">
                <div class="qrow-icon"><?php echo QualIcons::svg('license'); ?></div>
                <div class="qrow-main">
                    <h4>Άδεια Οδήγησης</h4>
                    <?php if ($hasLicense) : ?>
                        <?php if (!empty($driverData['license_number'])) : ?>
                            <p class="qrow-line"><span class="qrow-key">Αριθμός</span> <?php echo htmlspecialchars($driverData['license_number'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($driverLicenseTypes)) : ?>
                            <p class="qrow-cats">
                                <?php foreach ($driverLicenseTypes as $cat) : ?>
                                    <span class="qcat"><?php echo htmlspecialchars((string) $cat, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endforeach; ?>
                            </p>
                        <?php else : ?>
                            <p class="qrow-line qrow-empty">Δεν έχουν καταχωρηθεί κατηγορίες</p>
                        <?php endif; ?>
                    <?php else : ?>
                        <p class="qrow-line qrow-empty">Δεν έχει καταχωρηθεί</p>
                    <?php endif; ?>
                </div>
                <div class="qrow-expiry">
                    <?php if ($documentExpiry) : ?>
                        <span class="qexp"><?php echo $qualDate($documentExpiry); ?> <small>έντυπο</small></span>
                    <?php endif; ?>
                    <?php if ($earliestExpiry) : ?>
                        <span class="qexp"><?php echo $qualDate($earliestExpiry); ?> <small>κατηγορίες</small></span>
                    <?php endif; ?>
                    <?php if (!$documentExpiry && !$earliestExpiry) : ?>
                        <span class="qexp qexp-empty">—</span>
                    <?php endif; ?>
                </div>
                <div class="qrow-status"><span class="qbadge qbadge--<?php echo $stCls; ?>"><?php echo $stTxt; ?></span></div>
            </article>

            <?php list($stCls, $stTxt) = $qualStatus($peiCExpiryDate ?? null, !empty($hasPeiC)); ?>
            <article class="qrow<?php echo empty($hasPeiC) ? ' qrow--absent' : ''; ?>">
                <div class="qrow-icon"><?php echo QualIcons::svg('pei_freight'); ?></div>
                <div class="qrow-main">
                    <h4>ΠΕΙ Εμπορευμάτων</h4>
                    <?php if (!empty($hasPeiC)) : ?>
                        <p class="qrow-line"><span class="qrow-key">Κωδικός</span> <?php echo htmlspecialchars((string) ($driverData['pei_c_number'] ?? '95/'), ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php else : ?>
                        <p class="qrow-line qrow-empty">Δεν διαθέτει</p>
                    <?php endif; ?>
                </div>
                <div class="qrow-expiry"><span class="qexp<?php echo empty($peiCExpiryDate) ? ' qexp-empty' : ''; ?>"><?php echo $qualDate($peiCExpiryDate ?? null); ?></span></div>
                <div class="qrow-status"><span class="qbadge qbadge--<?php echo $stCls; ?>"><?php echo $stTxt; ?></span></div>
            </article>

            <?php list($stCls, $stTxt) = $qualStatus($peiDExpiryDate ?? null, !empty($hasPeiD)); ?>
            <article class="qrow<?php echo empty($hasPeiD) ? ' qrow--absent' : ''; ?>">
                <div class="qrow-icon"><?php echo QualIcons::svg('pei_passenger'); ?></div>
                <div class="qrow-main">
                    <h4>ΠΕΙ Επιβατών</h4>
                    <?php if (!empty($hasPeiD)) : ?>
                        <p class="qrow-line"><span class="qrow-key">Κωδικός</span> <?php echo htmlspecialchars((string) ($driverData['pei_d_number'] ?? '95/'), ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php else : ?>
                        <p class="qrow-line qrow-empty">Δεν διαθέτει</p>
                    <?php endif; ?>
                </div>
                <div class="qrow-expiry"><span class="qexp<?php echo empty($peiDExpiryDate) ? ' qexp-empty' : ''; ?>"><?php echo $qualDate($peiDExpiryDate ?? null); ?></span></div>
                <div class="qrow-status"><span class="qbadge qbadge--<?php echo $stCls; ?>"><?php echo $stTxt; ?></span></div>
            </article>
        </div>
    </div>

    <?php // ═══ ΟΜΑΔΑ 2: ΠΙΣΤΟΠΟΙΗΣΕΙΣ ΜΕΤΑΦΟΡΑΣ ═══════════════════════ ?>
    <div class="qgroup qgroup--certs">
        <header class="qgroup-head">
            <h3>Πιστοποιήσεις Μεταφοράς</h3>
            <span class="qgroup-note">Τι μεταφέρει και με ποιους κανόνες</span>
        </header>
        <div class="qgroup-body">

            <?php
            $adrExpiry = $driverADR['expiry_date'] ?? null;
            list($stCls, $stTxt) = $qualStatus($adrExpiry, !empty($driverADR));
            ?>
            <article class="qrow<?php echo empty($driverADR) ? ' qrow--absent' : ''; ?>">
                <div class="qrow-icon"><?php echo QualIcons::svg('adr'); ?></div>
                <div class="qrow-main">
                    <h4>Πιστοποιητικό ADR</h4>
                    <?php if (!empty($driverADR)) : ?>
                        <p class="qrow-line"><span class="qrow-key">Αριθμός</span> <?php echo htmlspecialchars((string) ($driverADR['certificate_number'] ?? 'Εγγεγραμμένο'), ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php if (!empty($driverADR['adr_type'])) : ?>
                            <p class="qrow-line"><span class="qrow-key">Κατηγορία</span> <?php echo htmlspecialchars((string) $driverADR['adr_type'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                    <?php else : ?>
                        <p class="qrow-line qrow-empty">Δεν διαθέτει</p>
                    <?php endif; ?>
                </div>
                <div class="qrow-expiry"><span class="qexp<?php echo empty($adrExpiry) ? ' qexp-empty' : ''; ?>"><?php echo $qualDate($adrExpiry); ?></span></div>
                <div class="qrow-status"><span class="qbadge qbadge--<?php echo $stCls; ?>"><?php echo $stTxt; ?></span></div>
            </article>

            <?php
            $tachoExpiry = $driverTachograph['expiry_date'] ?? null;
            list($stCls, $stTxt) = $qualStatus($tachoExpiry, !empty($driverTachograph));
            ?>
            <article class="qrow<?php echo empty($driverTachograph) ? ' qrow--absent' : ''; ?>">
                <div class="qrow-icon"><?php echo QualIcons::svg('tachograph'); ?></div>
                <div class="qrow-main">
                    <h4>Κάρτα Ψηφιακού Ταχογράφου</h4>
                    <?php if (!empty($driverTachograph)) : ?>
                        <p class="qrow-line"><span class="qrow-key">Αριθμός</span> <?php echo htmlspecialchars((string) ($driverTachograph['card_number'] ?? 'Εγγεγραμμένη'), ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php else : ?>
                        <p class="qrow-line qrow-empty">Δεν διαθέτει</p>
                    <?php endif; ?>
                </div>
                <div class="qrow-expiry"><span class="qexp<?php echo empty($tachoExpiry) ? ' qexp-empty' : ''; ?>"><?php echo $qualDate($tachoExpiry); ?></span></div>
                <div class="qrow-status"><span class="qbadge qbadge--<?php echo $stCls; ?>"><?php echo $stTxt; ?></span></div>
            </article>
        </div>
    </div>

    <?php // ═══ ΟΜΑΔΑ 3: ΜΗΧΑΝΗΜΑΤΑ ΕΡΓΟΥ ══════════════════════════════ ?>
    <div class="qgroup qgroup--operator">
        <header class="qgroup-head">
            <h3>Μηχανήματα Έργου</h3>
            <span class="qgroup-note">
                <?php if (!empty($opList)) : ?>
                    <?php echo count($opList); ?> <?php echo count($opList) === 1 ? 'ειδικότητα' : 'ειδικότητες'; ?> στο βιβλιάριο χειριστή
                <?php else : ?>
                    Άδεια χειριστή μηχανημάτων έργου
                <?php endif; ?>
            </span>
        </header>
        <div class="qgroup-body">

            <?php if (!empty($driverData['operator_registry_number']) || !empty($driverOperator['registry_number'])) : ?>
                <p class="qgroup-meta">
                    <span class="qrow-key">Αριθμός μητρώου</span>
                    <?php echo htmlspecialchars((string) ($driverData['operator_registry_number'] ?? $driverOperator['registry_number']), ENT_QUOTES, 'UTF-8'); ?>
                </p>
            <?php endif; ?>

            <?php if (empty($opList)) : ?>
                <article class="qrow qrow--absent">
                    <div class="qrow-icon"><?php echo QualIcons::svg('op_9'); ?></div>
                    <div class="qrow-main">
                        <h4>Άδεια Χειριστή</h4>
                        <p class="qrow-line qrow-empty">Δεν έχει καταχωρηθεί άδεια χειριστή</p>
                    </div>
                    <div class="qrow-expiry"><span class="qexp qexp-empty">—</span></div>
                    <div class="qrow-status"><span class="qbadge qbadge--none">Δεν διαθέτει</span></div>
                </article>
            <?php else : ?>
                <?php
                /*
                 * ΜΙΑ ΚΑΡΤΑ ΑΝΑ ΕΙΔΙΚΟΤΗΤΑ (αίτημα «χώρισέ το στις ειδικότητες»).
                 * Πριν ήταν όλες σε ένα κελί του πίνακα: με τρεις ειδικότητες
                 * και δέκα υποειδικότητες το κελί γινόταν κατεβατό.
                 *
                 * Η ΘΕΩΡΗΣΗ είναι ΚΟΙΝΗ για όλο το βιβλιάριο (μία ημερομηνία,
                 * $driverOperator['expiry_date']) — γι' αυτό εμφανίζεται στην
                 * ίδια στήλη σε κάθε κάρτα: το βιβλιάριο ισχύει ή δεν ισχύει
                 * συνολικά, δεν λήγει ανά ειδικότητα.
                 */
                $bookExpiry = $driverOperator['expiry_date'] ?? null;
                list($stCls, $stTxt) = $qualStatus($bookExpiry, true);
                foreach ($opList as $opLic) :
                    $opSpec = (string) ($opLic['speciality'] ?? '');
                    $opGroup = strtoupper((string) ($opLic['group_type'] ?? 'A'));
                    $opName = OperatorSpecialities::SPECIALITIES[$opSpec] ?? ('Ειδικότητα ' . $opSpec);
                    $opSubs = $opLic['sub_specialities'] ?? [];
                    $groupLabel = $opGroup === 'M' ? 'μικτή ομάδα' : 'Ομάδα ' . $opGroup . '΄';
                ?>
                    <article class="qrow qrow--operator">
                        <div class="qrow-icon"><?php echo QualIcons::operator($opSpec); ?></div>
                        <div class="qrow-main">
                            <h4>
                                <?php echo htmlspecialchars($opSpec . 'η ειδικότητα', ENT_QUOTES, 'UTF-8'); ?>
                                <span class="qgroup-tag"><?php echo htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                            </h4>
                            <p class="qrow-line qrow-spec"><?php echo htmlspecialchars($opName, ENT_QUOTES, 'UTF-8'); ?></p>

                            <?php if (!empty($opLic['number'])) : ?>
                                <p class="qrow-line"><span class="qrow-key">Αριθμός άδειας</span> <?php echo htmlspecialchars((string) $opLic['number'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($opLic['covers_all'])) : ?>
                                <p class="qrow-all">Καλύπτει το σύνολο των μηχανημάτων της ειδικότητας</p>
                            <?php elseif (!empty($opSubs)) : ?>
                                <ul class="qsubs">
                                    <?php foreach ($opSubs as $subSpec) :
                                        $subCode = is_array($subSpec) ? ($subSpec['sub_speciality'] ?? '') : (string) $subSpec;
                                        $subGroup = is_array($subSpec) ? strtoupper($subSpec['group_type'] ?? $opGroup) : $opGroup;
                                        $subName = OperatorSpecialities::subName($subCode);
                                    ?>
                                        <li>
                                            <span class="qsub-code"><?php echo htmlspecialchars($subCode, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <span class="qsub-name"><?php echo htmlspecialchars($subName ?: ('Υποειδικότητα ' . $subCode), ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php if ($opGroup === 'M') : ?>
                                                <span class="qsub-group">Ομάδα <?php echo htmlspecialchars($subGroup, ENT_QUOTES, 'UTF-8'); ?>΄</span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <p class="qrow-line qrow-empty">Δεν έχουν επιλεγεί μηχανήματα</p>
                            <?php endif; ?>
                        </div>
                        <div class="qrow-expiry">
                            <span class="qexp<?php echo empty($bookExpiry) ? ' qexp-empty' : ''; ?>"><?php echo $qualDate($bookExpiry); ?>
                                <?php if ($bookExpiry) : ?><small>θεώρηση</small><?php endif; ?>
                            </span>
                        </div>
                        <div class="qrow-status"><span class="qbadge qbadge--<?php echo $stCls; ?>"><?php echo $stTxt; ?></span></div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php // ═══ ΟΜΑΔΑ 4: ΕΙΔΙΚΕΣ ΑΔΕΙΕΣ ═════════════════════════════════ ?>
    <div class="qgroup qgroup--special">
        <header class="qgroup-head">
            <h3>Ειδικές Άδειες &amp; Πιστοποιητικά</h3>
            <span class="qgroup-note">ΕΔΧ, ζώντα ζώα, ΠΕΕ και λοιπά</span>
        </header>
        <div class="qgroup-body">
            <?php if (empty($slList)) : ?>
                <article class="qrow qrow--absent">
                    <div class="qrow-icon"><?php echo QualIcons::svg('special_other'); ?></div>
                    <div class="qrow-main">
                        <h4>Ειδικές Άδειες</h4>
                        <p class="qrow-line qrow-empty">Δεν έχουν καταχωρηθεί</p>
                    </div>
                    <div class="qrow-expiry"><span class="qexp qexp-empty">—</span></div>
                    <div class="qrow-status"><span class="qbadge qbadge--none">Δεν διαθέτει</span></div>
                </article>
            <?php else : ?>
                <?php foreach ($slList as $sl) :
                    $slCode = (string) ($sl['license_type'] ?? '');
                    $slExpiry = $sl['expiry_date'] ?? null;
                    /*
                     * Κενή ημερομηνία σε ειδική άδεια σημαίνει «Αορίστου» —
                     * ρητή επιλογή του οδηγού («χωρίς λήξη»), όχι παράλειψη.
                     */
                    list($stCls, $stTxt) = $slExpiry ? $qualStatus($slExpiry, true) : ['valid', 'Σε ισχύ'];
                ?>
                    <article class="qrow">
                        <div class="qrow-icon"><?php echo QualIcons::special($slCode); ?></div>
                        <div class="qrow-main">
                            <h4><?php echo htmlspecialchars(SpecialLicenseTypes::label($slCode), ENT_QUOTES, 'UTF-8'); ?></h4>
                            <?php if (!empty($sl['license_number'])) : ?>
                                <p class="qrow-line"><span class="qrow-key">Αριθμός</span> <?php echo htmlspecialchars((string) $sl['license_number'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($sl['details'])) : ?>
                                <p class="qrow-line qrow-details"><?php echo htmlspecialchars((string) $sl['details'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="qrow-expiry">
                            <span class="qexp<?php echo $slExpiry ? '' : ' qexp-open'; ?>"><?php echo $slExpiry ? $qualDate($slExpiry) : 'Αορίστου'; ?></span>
                        </div>
                        <div class="qrow-status"><span class="qbadge qbadge--<?php echo $stCls; ?>"><?php echo $stTxt; ?></span></div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</section>

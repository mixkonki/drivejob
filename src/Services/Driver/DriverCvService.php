<?php

namespace Drivejob\Services\Driver;

use Drivejob\Helpers\OperatorSpecialities;
use Drivejob\Helpers\SpecialLicenseTypes;
use Drivejob\Helpers\CertificationCategories;
use Drivejob\Helpers\VehicleExperienceTypes;
use Drivejob\Helpers\DriverSkills;

/**
 * ΤΟ ΒΙΟΓΡΑΦΙΚΟ ΤΟΥ ΟΔΗΓΟΥ — μία δομή, πολλές όψεις (30/08/2026).
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΥΠΑΡΧΕΙ ΑΥΤΗ Η ΚΛΑΣΗ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Η καρτέλα Επισκόπηση και το PDF βιογραφικό δείχνουν ΤΑ ΙΔΙΑ πράγματα.
 * Αν γραφτούν χωριστά, αποκλίνουν μέσα σε δύο μήνες: προστίθεται πεδίο
 * στο ένα και ξεχνιέται στο άλλο, και ο οδηγός βλέπει στην οθόνη κάτι
 * διαφορετικό από αυτό που στέλνει στον εργοδότη.
 *
 * Είναι ακριβώς το λάθος που είχαμε με τις ταξινομίες σε PHP σταθερές,
 * πριν τον πίνακα lookup_values: η ίδια πληροφορία σε δύο μέρη.
 *
 * ΕΔΩ γίνεται ΟΛΗ η μετάφραση κωδικών σε ανθρώπινο κείμενο (freight →
 * «Εμπορευματικές», own_business → «Ελεύθερος επαγγελματίας», 1.3 →
 * «Σύνθετα εκσκαπτικά-φορτωτικά»). Οι όψεις δέχονται έτοιμα κείμενα και
 * απλώς τα τυπώνουν — μια view δεν κάνει ποτέ λογική.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  Η ΔΟΜΗ (η σειρά ΕΙΝΑΙ η σειρά του βιογραφικού)
 * ══════════════════════════════════════════════════════════════════════
 *
 *   identity      → ταυτότητα, επικοινωνία, διαθεσιμότητα, ακτίνα
 *   alerts        → τι λήγει σύντομα (μόνο υπό συνθήκη)
 *   qualifications→ τυπικά προσόντα (τις ομάδες τις χτίζει η όψη)
 *   experience    → προϋπηρεσία, ταξινομημένη, με σύνολα
 *   certifications→ σεμινάρια & πιστοποιητικά
 *   languages     → γλώσσες με επίπεδο
 *   skills        → δεξιότητες ανά ομάδα
 *
 * ΟΨΕΙΣ: η ιδιωτική (ο ίδιος ο οδηγός) βλέπει και τα «τι λείπει»· η
 * δημόσια/PDF όχι. Γι' αυτό το `forDriver()` δέχεται $includePrivate.
 */
class DriverCvService
{
    /** Πόσο μπροστά κοιτάμε για λήξεις στην επισκόπηση. */
    public const ALERT_WINDOW_DAYS = 90;

    /**
     * ΤΙ ΜΠΑΙΝΕΙ ΣΤΟ ΒΙΟΓΡΑΦΙΚΟ — προεπιλογές (31/08).
     *
     * Το βιογραφικό παράγεται από το προφίλ, αλλά δεν δείχνει υποχρεωτικά
     * ό,τι έχει το προφίλ: ο οδηγός αποφασίζει τι φεύγει προς τα έξω.
     *
     * Η φωτογραφία ξεκινά ΚΛΕΙΣΤΗ — σε αρκετές χώρες θεωρείται μειονέκτημα
     * σε βιογραφικό και είναι δεδομένο που δεν χρειάζεται ο εργοδότης για
     * να κρίνει προσόντα. Τα υπόλοιπα ανοιχτά: CV χωρίς τηλέφωνο δεν
     * καλείται από κανέναν.
     */
    public const DEFAULT_OPTIONS = [
        'photo' => false,
        'age' => true,
        'phone' => true,
        'email' => true,
        'rating' => true,
    ];

    /** Οι προτιμήσεις του οδηγού από τις στήλες cv_show_*. */
    public static function optionsFromProfile(array $p): array
    {
        return [
            'photo' => !empty($p['cv_show_photo']),
            'age' => !isset($p['cv_show_age']) || !empty($p['cv_show_age']),
            'phone' => !isset($p['cv_show_phone']) || !empty($p['cv_show_phone']),
            'email' => !isset($p['cv_show_email']) || !empty($p['cv_show_email']),
            'rating' => !isset($p['cv_show_rating']) || !empty($p['cv_show_rating']),
        ];
    }

    private ProfileCompletenessService $completeness;

    public function __construct(?ProfileCompletenessService $completeness = null)
    {
        $this->completeness = $completeness ?? new ProfileCompletenessService();
    }

    /**
     * @param array $profile Το πλήρες προφίλ (DriverProfileService::getDriverProfile)
     * @param bool  $includePrivate Ιδιωτική όψη: προσθέτει «τι λείπει»
     */
    public function build(array $profile, bool $includePrivate = true, ?array $options = null): array
    {
        // Οι προτιμήσεις εφαρμόζονται ΜΟΝΟ στη δημόσια όψη: στη δική του
        // επισκόπηση ο οδηγός βλέπει πάντα τα πάντα — αλλιώς θα νόμιζε
        // ότι κάτι χάθηκε από το προφίλ του.
        $opts = $includePrivate
            ? array_map(static fn() => true, self::DEFAULT_OPTIONS)
            : ($options ?? self::DEFAULT_OPTIONS);

        $cv = [
            'options' => $opts,
            'identity' => $this->identity($profile, $opts),
            'alerts' => $this->alerts($profile),
            'qualifications' => $this->qualifications($profile),
            'experience' => $this->experience($profile['vehicle_experience'] ?? []),
            'certifications' => $this->certifications($profile['certifications'] ?? []),
            'languages' => $this->languages($profile['languages_list'] ?? []),
            'skills' => $this->skills($profile['skills'] ?? []),
        ];

        // Χτίζεται ΤΕΛΕΥΤΑΙΑ: διαβάζει ό,τι υπολογίστηκε παραπάνω.
        $cv['summary'] = $this->summary($profile, $cv['experience'], $cv['qualifications']);

        if ($includePrivate) {
            $cv['completeness'] = $this->completeness->calculate($profile);
        }

        return $cv;
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΤΑΥΤΟΤΗΤΑ
    // ══════════════════════════════════════════════════════════════════

    private function identity(array $p, array $opts = self::DEFAULT_OPTIONS): array
    {
        $city = trim((string) ($p['city'] ?? ''));
        $country = trim((string) ($p['country'] ?? ''));

        /*
         * ΗΛΙΚΙΑ — έλειπε εντελώς από το βιογραφικό (30/08).
         *
         * Σε αγγελίες οδηγών η ηλικία μετράει πρακτικά: κάποιες θέσεις
         * έχουν κατώτατο όριο (ΕΔΧ, διεθνείς), τα ασφάλιστρα στόλου
         * εξαρτώνται από αυτήν. Δείχνουμε ΗΛΙΚΙΑ και όχι ημερομηνία
         * γέννησης — αρκεί για την κρίση και αποκαλύπτει λιγότερα.
         */
        $birth = $p['birth_date'] ?? $p['date_of_birth'] ?? null;
        $age = null;
        if (!empty($birth)) {
            try {
                $age = (new \DateTime($birth))->diff(new \DateTime())->y;
                if ($age < 16 || $age > 100) {
                    $age = null;   // προφανώς λάθος καταχώρηση
                }
            } catch (\Throwable $e) {
                $age = null;
            }
        }

        return [
            'full_name' => trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')),
            'photo' => !empty($opts['photo']) ? ($p['profile_image'] ?? null) : null,
            'age' => !empty($opts['age']) ? $age : null,
            'age_label' => (!empty($opts['age']) && $age !== null) ? $age . ' ετών' : '',
            'city' => $city,
            'location' => $city !== '' ? trim($city . ($country !== '' ? ', ' . $country : '')) : '',
            'email' => !empty($opts['email']) ? ($p['email'] ?? null) : null,
            'phone' => !empty($opts['phone']) ? ($p['phone'] ?? null) : null,
            'landline' => !empty($opts['phone']) ? ($p['landline'] ?? null) : null,
            'linkedin' => $p['social_linkedin'] ?? null,
            'available' => !empty($p['available_for_work']),
            'reach' => $this->reach($p),
            'rating' => [
                'value' => (float) ($p['rating'] ?? 0),
                'count' => !empty($opts['rating']) ? (int) ($p['rating_count'] ?? 0) : 0,
            ],
        ];
    }

    /**
     * ΠΟΥ δέχεται να εργαστεί ο οδηγός.
     *
     * Το πεδίο `preferred_radius` υπάρχει στη βάση από την αρχή και το
     * διαβάζει ΤΟ ΤΑΙΡΙΑΣΜΑ (MatchingModel) — αλλά δεν το συμπλήρωνε
     * ποτέ κανείς, γιατί δεν υπήρχε πουθενά στη φόρμα. Έμενε 0, το
     * ταίριασμα έπεφτε σε προεπιλογή 50 χλμ, και ο οδηγός Θεσσαλονίκης
     * έβλεπε αγγελίες Αθηνών. Τώρα δηλώνεται και φαίνεται.
     */
    private function reach(array $p): array
    {
        $radius = (int) ($p['preferred_radius'] ?? 0);
        $relocate = !empty($p['willing_to_relocate']);
        $travel = !empty($p['willing_to_travel']);

        if ($relocate) {
            $label = 'Όλη την Ελλάδα — δέχομαι μετεγκατάσταση';
        } elseif ($radius > 0) {
            $label = 'Έως ' . $radius . ' χλμ από ' . ($p['city'] ?: 'την έδρα μου');
        } else {
            $label = '';   // αδήλωτο: το λέει η όψη, δεν το μαντεύουμε εδώ
        }

        return [
            'radius' => $radius,
            'relocate' => $relocate,
            'travel' => $travel,
            'label' => $label,
            'declared' => $radius > 0 || $relocate,
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΣΥΝΟΨΗ ΠΡΟΦΙΛ — Η ΠΡΩΤΗ ΠΑΡΑΓΡΑΦΟΣ ΤΟΥ ΒΙΟΓΡΑΦΙΚΟΥ
    // ══════════════════════════════════════════════════════════════════

    /**
     * Δύο-τρεις γραμμές που λένε ΠΟΙΟΣ είναι ο οδηγός.
     *
     * ΓΙΑΤΙ: το πρώτο PDF ήταν λίστα από πεδία — μια σύνοψη της
     * επισκόπησης, όχι βιογραφικό. Κάθε βιογραφικό ξεκινά με μια
     * παράγραφο που τοποθετεί τον άνθρωπο· χωρίς αυτήν ο αναγνώστης
     * πρέπει να συνθέσει μόνος του την εικόνα από κατάλογο αδειών.
     *
     * Συντίθεται ΑΠΟ ΤΑ ΔΕΔΟΜΕΝΑ, δεν ζητά γράψιμο: ο οδηγός που δεν
     * κάθεται να συμπληρώσει «λίγα λόγια για εμένα» παίρνει ούτως ή
     * άλλως σωστή παρουσίαση. Αν έχει γράψει δικό του κείμενο
     * (about_me), αυτό υπερισχύει — η δική του φωνή είναι καλύτερη από
     * κάθε αυτόματη πρόταση.
     */
    public function autoSummary(array $p): string
    {
        // Η προτεινόμενη σύνοψη ΧΩΡΙΣ το γραμμένο κείμενο: η οθόνη του
        // βιογραφικού τη δείχνει ως αφετηρία στο πεδίο επεξεργασίας.
        $clean = $p;
        unset($clean['cv_summary'], $clean['about_me']);
        return $this->summary($clean, $this->experience($p['vehicle_experience'] ?? []), []);
    }

    private function summary(array $p, array $experience, array $qualifications): string
    {
        /*
         * Το δικό του κείμενο υπερισχύει — ΑΝ είναι κείμενο.
         *
         * Το πεδίο about_me έμεινε χρόνια στη φόρμα χωρίς έλεγχο και
         * γέμισε με δοκιμές πληκτρολογίου («εςταςετDSGSDGSλλ»). Αυτό
         * τυπώθηκε ως «προφίλ» στην πρώτη γραμμή του βιογραφικού.
         *
         * Κριτήρια για να θεωρηθεί πραγματικό κείμενο: τουλάχιστον 30
         * χαρακτήρες, τουλάχιστον πέντε λέξεις, και κενά — δηλαδή
         * προτάσεις, όχι τυχαίοι χαρακτήρες. Ό,τι δεν περνά αγνοείται
         * σιωπηλά και συντίθεται η αυτόματη σύνοψη.
         */
        // Το cv_summary γράφεται ΕΙΔΙΚΑ για το βιογραφικό — έχει
        // προτεραιότητα πάνω από το παλιό about_me του προφίλ.
        $cvOwn = trim((string) ($p['cv_summary'] ?? ''));
        if ($cvOwn !== '') {
            return $cvOwn;
        }

        $own = trim((string) ($p['about_me'] ?? ''));
        if ($own !== '' && mb_strlen($own) >= 30 && count(preg_split('/\s+/', $own)) >= 5) {
            return $own;
        }

        $bits = [];

        // Ποιος: επάγγελμα + χρόνια
        $years = intdiv($experience['total_months'], 12);
        if ($years >= 1) {
            $bits[] = 'Επαγγελματίας οδηγός με ' . $years . ($years === 1 ? ' έτος' : ' έτη') . ' προϋπηρεσίας';
        } else {
            $bits[] = 'Επαγγελματίας οδηγός';
        }

        // Σε τι: τα οχήματα με τη μεγαλύτερη εμπειρία (έως δύο)
        $vehicles = [];
        foreach ($experience['items'] as $item) {
            if (!in_array($item['category_label'], $vehicles, true)) {
                $vehicles[] = $item['category_label'];
            }
            if (count($vehicles) === 2) {
                break;
            }
        }
        if ($vehicles) {
            // Πεζό μόνο το πρώτο γράμμα κάθε κατηγορίας: «Ελαφρά
            // Επαγγελματικά Οχήματα» → «ελαφρά Επαγγελματικά Οχήματα»
            // θα ήταν χειρότερο από το να μείνει όπως είναι.
            $bits[] = 'σε ' . implode(' και ', $vehicles);
        }

        $text = implode(' ', $bits) . '.';

        // Τι κατέχει: οι άδειες που ξεχωρίζουν, όχι όλες
        // ΓΕΝΙΚΗ πτώση: «Κάτοχος κατηγοριών…», όχι «Κάτοχος κατηγορίες…».
        // Το κείμενο διαβάζεται από εργοδότη — τα ελληνικά μετράνε.
        $highlights = [];
        $cats = array_column($p['licenses'] ?? [], 'license_type');
        if ($cats) {
            $highlights[] = 'κατηγοριών ' . implode(', ', $cats);
        }
        if (!empty($p['adr_certificates'][0])) {
            $highlights[] = 'πιστοποιητικού ADR';
        }
        if (!empty($p['tachograph_cards'][0])) {
            $highlights[] = 'κάρτας ψηφιακού ταχογράφου';
        }
        $opCount = count($p['operator_licenses'] ?? []);
        if ($opCount > 0) {
            $highlights[] = 'άδειας χειριστή μηχανημάτων έργου σε ' . $opCount
                . ($opCount === 1 ? ' ειδικότητα' : ' ειδικότητες');
        }
        if ($highlights) {
            $text .= ' Κάτοχος ' . implode(', ', $highlights) . '.';
        }

        // Πού: η ακτίνα είναι πρακτική πληροφορία για τον εργοδότη
        $reach = $this->reach($p);
        if (!empty($reach['declared'])) {
            // ΧΩΡΙΣ mb_strtolower: έκανε πεζό και το όνομα της πόλης
            // («από θεσσαλονίκη»). Μόνο η πρώτη λέξη χρειάζεται αλλαγή.
            $reachText = preg_replace('/^Έως/u', 'έως', $reach['label']);
            $text .= ' ' . ($reach['relocate']
                ? 'Διαθέσιμος για εργασία σε όλη την Ελλάδα.'
                : 'Διαθέσιμος για εργασία ' . $reachText . '.');
        }

        return $text;
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΤΥΠΙΚΑ ΠΡΟΣΟΝΤΑ — ΟΜΑΔΟΠΟΙΗΜΕΝΑ
    // ══════════════════════════════════════════════════════════════════

    /**
     * Οι τρεις ομάδες προσόντων, έτοιμες προς εμφάνιση.
     *
     * ΜΕΤΑΚΟΜΙΣΑΝ ΕΔΩ ΑΠΟ ΤΗΝ ΟΨΗ (30/08). Πριν, η ομαδοποίηση ζούσε
     * μόνο στο _qualification-groups.php: η οθόνη έδειχνε τέσσερις
     * καθαρές ομάδες και το PDF μια ισοπεδωμένη λίστα «ετικέτα: τιμή».
     * Ήταν ακριβώς η απόκλιση που ο ίδιος ο DriverCvService υπάρχει για
     * να αποτρέπει — και φάνηκε στο πρώτο κιόλας βιογραφικό.
     *
     * ΟΙ ΟΜΑΔΕΣ (σειρά και ονόματα κατά την οδηγία 30/08):
     *
     *   1. Άδεια Οδήγησης       — το έντυπο, οι κατηγορίες, τα δύο ΠΕΙ
     *   2. Πιστοποιητικά &      — ADR και ταχογράφος ΠΡΩΤΑ (τα έχει
     *      Ειδικές Άδειες         σχεδόν κάθε επαγγελματίας), μετά ΕΔΧ,
     *                             ζώντα ζώα, ΠΕΕ κ.λπ.
     *   3. Άδεια Χειριστή       — μία καταχώρηση ανά ειδικότητα
     *      Μηχανημάτων Έργου
     *
     * Κάθε στοιχείο έχει σταθερό σχήμα ώστε η όψη και το PDF να το
     * διαβάζουν με τον ίδιο τρόπο:
     *
     *   icon      → κωδικός εικονιδίου (QualIcons)
     *   title     → τι είναι
     *   lines     → [['key' => 'Αριθμός', 'value' => '...'], ...]
     *   cats      → πλακίδια κατηγοριών (μόνο το δίπλωμα)
     *   expiries  → [['label' => 'Λήξη εντύπου', 'date' => '20/05/2028'], ...]
     *   status    → ['cls' => 'valid', 'label' => 'Σε ισχύ']
     *   absent    → δεν το διαθέτει (εμφανίζεται ξεθωριασμένο)
     *   subs      → υποειδικότητες χειριστή
     */
    public function qualifications(array $p): array
    {
        return [
            $this->groupLicense($p),
            $this->groupCertificates($p),
            $this->groupOperator($p),
        ];
    }

    /** Κατάσταση από ημερομηνία λήξης — ΕΝΑ σημείο απόφασης για όλα. */
    private function status(?string $date, bool $exists = true): array
    {
        if (!$exists) {
            return ['cls' => 'none', 'label' => 'Δεν διαθέτει'];
        }
        if (empty($date)) {
            return ['cls' => 'open', 'label' => 'Χωρίς λήξη'];
        }
        $ts = strtotime($date);
        if ($ts === false) {
            return ['cls' => 'unknown', 'label' => 'Άγνωστη λήξη'];
        }
        if ($ts < time()) {
            return ['cls' => 'expired', 'label' => 'Έληξε'];
        }
        if (($ts - time()) < 90 * 86400) {
            return ['cls' => 'soon', 'label' => 'Λήγει σύντομα'];
        }
        return ['cls' => 'valid', 'label' => 'Σε ισχύ'];
    }

    private function fmt(?string $date): string
    {
        if (empty($date)) {
            return '';
        }
        $ts = strtotime($date);
        return $ts ? date('d/m/Y', $ts) : '';
    }

    // ── Ομάδα 1: Άδεια οδήγησης ────────────────────────────────────────

    private function groupLicense(array $p): array
    {
        $cats = array_column($p['licenses'] ?? [], 'license_type');
        $has = !empty($p['license_number']) || !empty($cats);

        // Δύο ΞΕΧΩΡΙΣΤΕΣ λήξεις: το πλαστικό έντυπο (πεδίο 4β) και οι
        // κατηγορίες (πεδίο 11). Συχνά διαφέρουν κατά χρόνια.
        $docExpiry = $p['license_document_expiry'] ?? null;
        $catExpiry = null;
        foreach (($p['licenses'] ?? []) as $l) {
            if (!empty($l['expiry_date']) && ($catExpiry === null || strtotime($l['expiry_date']) < strtotime($catExpiry))) {
                $catExpiry = $l['expiry_date'];
            }
        }
        // Για την κατάσταση μετράει ό,τι λήγει ΠΡΩΤΟ.
        $effective = $catExpiry;
        if ($docExpiry && (!$effective || strtotime($docExpiry) < strtotime($effective))) {
            $effective = $docExpiry;
        }

        $expiries = [];
        if ($docExpiry) {
            $expiries[] = ['label' => 'Λήξη εντύπου', 'date' => $this->fmt($docExpiry)];
        }
        if ($catExpiry) {
            $expiries[] = ['label' => 'Λήξη άδειας', 'date' => $this->fmt($catExpiry)];
        }

        $lines = [];
        if (!empty($p['license_number'])) {
            $lines[] = ['key' => 'Αριθμός', 'value' => (string) $p['license_number']];
        }

        $items = [[
            'icon' => 'license',
            // Ο τίτλος της ΟΜΑΔΑΣ είναι ήδη «Άδεια Οδήγησης» — η γραμμή
            // δεν τον επαναλαμβάνει (οδηγία 30/08).
            'title' => '',
            'lines' => $lines,
            'cats' => $cats,
            'expiries' => $expiries,
            'status' => $this->status($effective, $has),
            'absent' => !$has,
            'empty_text' => 'Δεν έχει καταχωρηθεί',
        ]];

        // ΠΕΙ: οι στήλες επαναλαμβάνονται σε κάθε γραμμή κατηγορίας —
        // μαζεύονται μία φορά η καθεμία.
        $peiC = null;
        $peiD = null;
        foreach (($p['licenses'] ?? []) as $l) {
            $peiC = $peiC ?: ($l['pei_expiry_c'] ?: null);
            $peiD = $peiD ?: ($l['pei_expiry_d'] ?: null);
        }

        foreach ([['pei_freight', 'ΠΕΙ Εμπορευμάτων', $peiC, $p['pei_c_number'] ?? null],
                  ['pei_passenger', 'ΠΕΙ Επιβατών', $peiD, $p['pei_d_number'] ?? null]] as $pei) {
            [$icon, $title, $exp, $num] = $pei;
            $hasPei = !empty($exp);
            $items[] = [
                'icon' => $icon,
                'title' => $title,
                'lines' => $hasPei && $num ? [['key' => 'Κωδικός', 'value' => (string) $num]] : [],
                'cats' => [],
                'expiries' => $hasPei ? [['label' => 'Λήξη', 'date' => $this->fmt($exp)]] : [],
                'status' => $this->status($exp, $hasPei),
                'absent' => !$hasPei,
                'empty_text' => 'Δεν διαθέτει',
            ];
        }

        return [
            'key' => 'license',
            'title' => 'Άδεια Οδήγησης',
            // Πού επεξεργάζεται αυτή η ομάδα: ο σύνδεσμος ανοίγει την
            // ακριβή καρτέλα της φόρμας (tab-deeplink.js).
            'edit' => 'driving-licenses',
            'items' => $items,
        ];
    }

    // ── Ομάδα 2: Πιστοποιητικά & ειδικές άδειες ────────────────────────

    private function groupCertificates(array $p): array
    {
        $items = [];

        // ADR και ταχογράφος ΠΡΩΤΑ: είναι τα δύο που ζητούνται πιο συχνά
        // σε αγγελίες επαγγελματιών οδηγών.
        $adr = $p['adr_certificates'][0] ?? null;
        $adrLines = [];
        if ($adr) {
            if (!empty($adr['certificate_number'])) {
                $adrLines[] = ['key' => 'Αριθμός', 'value' => (string) $adr['certificate_number']];
            }
            if (!empty($adr['adr_type'])) {
                $adrLines[] = ['key' => 'Κατηγορία', 'value' => (string) $adr['adr_type']];
            }
        }
        $items[] = [
            'icon' => 'adr',
            'title' => 'Πιστοποιητικό ADR Οδηγού',
            'lines' => $adrLines,
            'cats' => [],
            'expiries' => $adr && !empty($adr['expiry_date']) ? [['label' => 'Λήξη', 'date' => $this->fmt($adr['expiry_date'])]] : [],
            'status' => $this->status($adr['expiry_date'] ?? null, (bool) $adr),
            'absent' => !$adr,
            'empty_text' => 'Δεν διαθέτει',
        ];

        $tacho = $p['tachograph_cards'][0] ?? null;
        $items[] = [
            'icon' => 'tachograph',
            'title' => 'Κάρτα Ψηφιακού Ταχογράφου Οδηγού',
            'lines' => $tacho && !empty($tacho['card_number'])
                ? [['key' => 'Αριθμός', 'value' => (string) $tacho['card_number']]] : [],
            'cats' => [],
            'expiries' => $tacho && !empty($tacho['expiry_date']) ? [['label' => 'Λήξη', 'date' => $this->fmt($tacho['expiry_date'])]] : [],
            'status' => $this->status($tacho['expiry_date'] ?? null, (bool) $tacho),
            'absent' => !$tacho,
            'empty_text' => 'Δεν διαθέτει',
        ];

        foreach (($p['special_licenses'] ?? []) as $sl) {
            $code = (string) ($sl['license_type'] ?? '');
            $exp = $sl['expiry_date'] ?? null;
            $lines = [];
            if (!empty($sl['license_number'])) {
                $lines[] = ['key' => 'Αριθμός', 'value' => (string) $sl['license_number']];
            }
            if (!empty($sl['details'])) {
                $lines[] = ['key' => '', 'value' => (string) $sl['details']];
            }

            $items[] = [
                'icon' => 'special:' . $code,
                'title' => SpecialLicenseTypes::label($code),
                'lines' => $lines,
                'cats' => [],
                // Κενή ημερομηνία = ρητή επιλογή «χωρίς λήξη», όχι παράλειψη.
                'expiries' => [['label' => 'Λήξη', 'date' => $exp ? $this->fmt($exp) : 'Αορίστου']],
                'status' => $exp ? $this->status($exp, true) : ['cls' => 'valid', 'label' => 'Σε ισχύ'],
                'absent' => false,
                'empty_text' => '',
            ];
        }

        // Τα ADR/ταχογράφος έχουν δική τους καρτέλα, οι ειδικές άδειες
        // άλλη — στέλνουμε στις ειδικές άδειες, που είναι και οι
        // περισσότερες εγγραφές.
        return [
            'key' => 'certs',
            'title' => 'Πιστοποιητικά & Ειδικές Άδειες',
            'edit' => 'special-licenses',
            'items' => $items,
        ];
    }

    // ── Ομάδα 3: Άδεια χειριστή μηχανημάτων έργου ──────────────────────

    private function groupOperator(array $p): array
    {
        $ops = $p['operator_licenses'] ?? [];
        // Με τη σειρά του βιβλιαρίου: ο χειριστής ψάχνει την ειδικότητα
        // με τον αριθμό της, όχι με τη σειρά που την καταχώρησε.
        usort($ops, static fn($a, $b) => ((int) ($a['speciality'] ?? 0)) <=> ((int) ($b['speciality'] ?? 0)));

        // Η ΘΕΩΡΗΣΗ είναι κοινή για όλο το βιβλιάριο — μία ημερομηνία,
        // όχι μία ανά ειδικότητα.
        $bookExpiry = $p['operator_licenses'][0]['expiry_date'] ?? null;

        $items = [];
        foreach ($ops as $op) {
            $spec = (string) ($op['speciality'] ?? '');
            $group = strtoupper((string) ($op['group_type'] ?? 'A'));
            $lines = [];
            if (!empty($op['number'])) {
                $lines[] = ['key' => 'Αριθμός', 'value' => (string) $op['number']];
            }

            $subs = [];
            if (empty($op['covers_all'])) {
                foreach (($op['sub_specialities'] ?? []) as $sub) {
                    $code = is_array($sub) ? ($sub['sub_speciality'] ?? '') : (string) $sub;
                    $subGroup = is_array($sub) ? strtoupper($sub['group_type'] ?? $group) : $group;
                    $subs[] = [
                        'code' => $code,
                        'name' => OperatorSpecialities::subName($code) ?: ('Υποειδικότητα ' . $code),
                        // Η ομάδα ανά υποειδικότητα φαίνεται ΜΟΝΟ σε μικτή
                        // άδεια — αλλού θα ήταν επανάληψη του τίτλου.
                        'group' => $group === 'M' ? 'Ομάδα ' . $subGroup . '΄' : '',
                    ];
                }
            }

            $items[] = [
                'icon' => 'op:' . $spec,
                'title' => $spec . 'η ειδικότητα',
                'tag' => $group === 'M' ? 'μικτή ομάδα' : 'Ομάδα ' . $group . '΄',
                'subtitle' => OperatorSpecialities::SPECIALITIES[$spec] ?? ('Ειδικότητα ' . $spec),
                'lines' => $lines,
                'cats' => [],
                'expiries' => $bookExpiry ? [['label' => 'Θεώρηση', 'date' => $this->fmt($bookExpiry)]] : [],
                'status' => $this->status($bookExpiry, true),
                'absent' => false,
                'covers_all' => !empty($op['covers_all']),
                'subs' => $subs,
                'empty_text' => '',
            ];
        }

        if (!$items) {
            $items[] = [
                'icon' => 'op:9',
                'title' => 'Άδεια χειριστή',
                'lines' => [],
                'cats' => [],
                'expiries' => [],
                'status' => ['cls' => 'none', 'label' => 'Δεν διαθέτει'],
                'absent' => true,
                'empty_text' => 'Δεν έχει καταχωρηθεί',
                'subs' => [],
            ];
        }

        $registry = $p['operator_registry_number'] ?? ($p['operator_licenses'][0]['registry_number'] ?? null);

        return [
            'key' => 'operator',
            'title' => 'Άδεια Χειριστή Μηχανημάτων Έργου',
            'edit' => 'operator-licenses',
            'meta' => $registry ? ['key' => 'Αριθμός μητρώου', 'value' => (string) $registry] : null,
            'items' => $items,
        ];
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΕΙΔΟΠΟΙΗΣΕΙΣ ΛΗΞΕΩΝ
    // ══════════════════════════════════════════════════════════════════

    /**
     * Ό,τι λήγει μέσα στο παράθυρο, ταξινομημένο κατά ΕΠΕΙΓΟΝ.
     *
     * Ό,τι έχει ΗΔΗ λήξει μπαίνει πρώτο: δεν είναι υπενθύμιση, είναι
     * πρόβλημα που ήδη κοστίζει δουλειές.
     */
    private function alerts(array $p): array
    {
        $items = [];
        $now = time();
        $limit = $now + self::ALERT_WINDOW_DAYS * 86400;

        $add = function (?string $date, string $label, string $action = '', string $url = '') use (&$items, $now, $limit) {
            if (empty($date)) {
                return;
            }
            $ts = strtotime($date);
            if ($ts === false || $ts > $limit) {
                return;
            }
            $items[] = [
                'label' => $label,
                'date' => date('d/m/Y', $ts),
                'ts' => $ts,
                'expired' => $ts < $now,
                'days' => (int) floor(($ts - $now) / 86400),
                'action' => $action,
                'url' => $url,
            ];
        };

        $city = $p['city'] ?? null;

        // Δίπλωμα: έντυπο και κατηγορίες λήγουν χωριστά
        $add(
            $p['license_document_expiry'] ?? null,
            'Άδεια οδήγησης (έντυπο)',
            'Ανανέωση από gov.gr',
            'https://www.gov.gr/ipiresies/polites-kai-kathemerinoteta/diploma-odegeses/ananeose-adeias-odegeses'
        );

        foreach (($p['licenses'] ?? []) as $lic) {
            $type = $lic['license_type'] ?? '';
            $add(
                $lic['expiry_date'] ?? null,
                'Κατηγορία ' . $type,
                'Ανανέωση από gov.gr',
                'https://www.gov.gr/ipiresies/polites-kai-kathemerinoteta/diploma-odegeses/ananeose-adeias-odegeses'
            );

            /*
             * Το ΠΕΙ ΔΕΝ μπαίνει εδώ: οι στήλες pei_expiry_c/d
             * επαναλαμβάνονται σε ΚΑΘΕ γραμμή κατηγορίας του διπλώματος.
             * Ένας οδηγός με C, CE, D, DE έπαιρνε τέσσερις φορές την ίδια
             * ειδοποίηση. Μαζεύονται παρακάτω, μία φορά η καθεμία.
             */
            if (!empty($lic['pei_expiry_c'])) {
                $peiC = $peiC ?? $lic['pei_expiry_c'];
            }
            if (!empty($lic['pei_expiry_d'])) {
                $peiD = $peiD ?? $lic['pei_expiry_d'];
            }
        }

        $add($peiC ?? null, 'ΠΕΙ Εμπορευμάτων', 'Σχολές ΠΕΙ', $this->schoolSearch('ΠΕΙ', $city));
        $add($peiD ?? null, 'ΠΕΙ Επιβατών', 'Σχολές ΠΕΙ', $this->schoolSearch('ΠΕΙ', $city));

        $adr = $p['adr_certificates'][0] ?? null;
        if ($adr) {
            $add($adr['expiry_date'] ?? null, 'Πιστοποιητικό ADR', 'Σχολές ADR', $this->schoolSearch('ADR', $city));
        }

        $tacho = $p['tachograph_cards'][0] ?? null;
        if ($tacho) {
            $add(
                $tacho['expiry_date'] ?? null,
                'Κάρτα ψηφιακού ταχογράφου',
                'Ανανέωση από gov.gr',
                'https://www.gov.gr/ipiresies/polites-kai-kathemerinoteta/metakineseis/karta-psephiakou-takhographou'
            );
        }

        $op = $p['operator_licenses'][0] ?? null;
        if ($op) {
            $add($op['expiry_date'] ?? null, 'Θεώρηση άδειας χειριστή', '', '');
        }

        foreach (($p['special_licenses'] ?? []) as $sl) {
            // Κενή ημερομηνία = «χωρίς λήξη», ρητή επιλογή — δεν ειδοποιούμε.
            $add($sl['expiry_date'] ?? null, SpecialLicenseTypes::label((string) $sl['license_type']), '', '');
        }

        foreach (($p['certifications'] ?? []) as $c) {
            $add($c['expiry'] ?? null, $c['title'] ?? 'Πιστοποιητικό', '', '');
        }

        usort($items, static fn($a, $b) => $a['ts'] <=> $b['ts']);

        return $items;
    }

    private function schoolSearch(string $what, ?string $city): string
    {
        return 'https://www.google.com/search?q=' . urlencode($what . ' σχολή ' . ($city ?: 'Ελλάδα'));
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΠΡΟΫΠΗΡΕΣΙΑ
    // ══════════════════════════════════════════════════════════════════

    /**
     * Η προϋπηρεσία ήταν ΤΟ ΜΕΓΑΛΟ ΚΕΝΟ της επισκόπησης: εμφανιζόταν
     * μόνο ως «7 έτη εμπειρίας» δίπλα στο όνομα. Ποια οχήματα; Πόσο;
     * Ένα βιογραφικό οδηγού χωρίς αυτό δεν είναι βιογραφικό.
     *
     * Ταξινόμηση: οι ΤΡΕΧΟΥΣΕΣ θέσεις πρώτα (χωρίς end_date), μετά κατά
     * φθίνουσα διάρκεια — έτσι διαβάζεται πρώτο ό,τι μετράει περισσότερο.
     */
    private function experience(array $rows): array
    {
        $items = [];
        $totalMonths = 0;

        foreach ($rows as $r) {
            $years = (int) ($r['years'] ?? 0);
            $months = (int) ($r['months'] ?? 0);
            $months += $years * 12;
            $totalMonths += $months;

            $category = (string) ($r['vehicle_category'] ?? '');
            $type = (string) ($r['vehicle_type'] ?? '');
            $current = empty($r['end_date']);

            $items[] = [
                'category' => $category,
                'category_label' => VehicleExperienceTypes::categoryLabel($category),
                'type_label' => $type !== '' ? VehicleExperienceTypes::typeLabel($category, $type) : '',
                'transport_label' => VehicleExperienceTypes::transportLabel($r['transport_type'] ?? null),
                'employment_label' => VehicleExperienceTypes::EMPLOYMENT_LABELS[$r['employment_type'] ?? ''] ?? '',
                'duration_label' => $this->durationLabel($months),
                'months' => $months,
                'period_label' => $this->periodLabel($r['start_date'] ?? null, $r['end_date'] ?? null),
                'current' => $current,
                'description' => trim((string) ($r['description'] ?? '')),
            ];
        }

        usort($items, static function ($a, $b) {
            if ($a['current'] !== $b['current']) {
                return $b['current'] <=> $a['current'];
            }
            return $b['months'] <=> $a['months'];
        });

        return [
            'items' => $items,
            'total_months' => $totalMonths,
            'total_label' => $this->durationLabel($totalMonths),
            'count' => count($items),
        ];
    }

    /** «7 έτη 5 μήνες», «11 μήνες», «—». */
    private function durationLabel(int $months): string
    {
        if ($months <= 0) {
            return '—';
        }
        $y = intdiv($months, 12);
        $m = $months % 12;

        $parts = [];
        if ($y > 0) {
            $parts[] = $y . ($y === 1 ? ' έτος' : ' έτη');
        }
        if ($m > 0) {
            $parts[] = $m . ($m === 1 ? ' μήνας' : ' μήνες');
        }

        return implode(' ', $parts);
    }

    /** «03/2019 — σήμερα» ή «03/2019 — 08/2024». */
    private function periodLabel(?string $start, ?string $end): string
    {
        if (empty($start)) {
            return '';
        }
        $s = strtotime($start);
        if ($s === false) {
            return '';
        }
        $out = date('m/Y', $s);
        if (empty($end)) {
            return $out . ' — σήμερα';
        }
        $e = strtotime($end);
        return $out . ' — ' . ($e ? date('m/Y', $e) : '');
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΣΕΜΙΝΑΡΙΑ & ΠΙΣΤΟΠΟΙΗΤΙΚΑ
    // ══════════════════════════════════════════════════════════════════

    private function certifications(array $rows): array
    {
        $items = [];
        $now = time();

        foreach ($rows as $c) {
            $expiry = $c['expiry'] ?? null;
            $ts = $expiry ? strtotime($expiry) : null;
            $date = $c['date'] ?? null;

            $items[] = [
                'title' => (string) ($c['title'] ?? ''),
                'provider' => (string) ($c['provider'] ?? ''),
                'category_label' => !empty($c['category']) ? CertificationCategories::label((string) $c['category']) : '',
                'transport_label' => CertificationCategories::transportLabel($c['transport_type'] ?? 'both'),
                'date_label' => $date && strtotime($date) ? date('m/Y', strtotime($date)) : '',
                'date_sort' => $date && strtotime($date) ? strtotime($date) : 0,
                'expiry_label' => $ts ? date('d/m/Y', $ts) : '',
                'expired' => $ts !== null && $ts < $now,
                'duration' => (int) ($c['duration'] ?? 0),
                'file' => $c['certificate_file'] ?? null,
            ];
        }

        // Τα πιο πρόσφατα πρώτα: αυτά δείχνουν τι κάνει ΤΩΡΑ ο οδηγός.
        usort($items, static fn($a, $b) => $b['date_sort'] <=> $a['date_sort']);

        return ['items' => $items, 'count' => count($items)];
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΓΛΩΣΣΕΣ
    // ══════════════════════════════════════════════════════════════════

    /*
     * ΤΑ ΤΕΣΣΕΡΑ ΕΠΙΠΕΔΑ ΤΗΣ ΦΟΡΜΑΣ — και μόνο αυτά.
     *
     * Πρώτη γραφή είχε 'advanced'/'intermediate' από μνήμη· η φόρμα
     * (skills.php) γράφει basic/good/fluent/native. Αποτέλεσμα στο PDF:
     * «Βουλγαρικά (good)» — ο κωδικός τυπωμένος ωμός. Τα επίπεδα
     * αντιγράφονται ΑΠΟ τη φόρμα, δεν εικάζονται.
     */
    private const LANGUAGE_LEVELS = [
        'native' => 'Μητρική',
        'fluent' => 'Άριστα',
        'good' => 'Καλά',
        'basic' => 'Βασικά',
    ];

    /** Σειρά ισχύος: η μητρική πρώτη, τα βασικά τελευταία. */
    private const LEVEL_RANK = ['native' => 4, 'fluent' => 3, 'good' => 2, 'basic' => 1];

    private function languages(array $rows): array
    {
        $items = [];
        foreach ($rows as $l) {
            $level = (string) ($l['level'] ?? '');
            $items[] = [
                'name' => (string) ($l['language_name'] ?? ''),
                'level' => $level,
                'level_label' => self::LANGUAGE_LEVELS[$level] ?? $level,
                'rank' => self::LEVEL_RANK[$level] ?? 0,
            ];
        }

        usort($items, static fn($a, $b) => $b['rank'] <=> $a['rank']);

        return $items;
    }

    // ══════════════════════════════════════════════════════════════════
    //  ΔΕΞΙΟΤΗΤΕΣ
    // ══════════════════════════════════════════════════════════════════

    /**
     * Μόνο όσες έχει δηλώσει, ομαδοποιημένες όπως στη φόρμα. Ένας
     * κατάλογος 30 δεξιοτήτων με τσεκ και χ δεν διαβάζεται· μια λίστα
     * με 8 πράγματα που ΞΕΡΕΙ ο οδηγός, ναι.
     */
    private function skills(array $flags): array
    {
        $groups = [];

        foreach (DriverSkills::GROUPS as $groupLabel => $codes) {
            $found = [];
            foreach ($codes as $code) {
                if (!empty($flags[$code])) {
                    $found[] = DriverSkills::LABELS[$code] ?? $code;
                }
            }
            if ($found) {
                $groups[] = ['label' => $groupLabel, 'items' => $found];
            }
        }

        $total = 0;
        foreach ($groups as $g) {
            $total += count($g['items']);
        }

        return ['groups' => $groups, 'count' => $total];
    }
}

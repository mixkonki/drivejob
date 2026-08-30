<?php

namespace Drivejob\Services\Score;

/**
 * ΜΗΤΡΩΟ ΠΗΓΩΝ ΒΑΘΜΟΛΟΓΙΑΣ. (01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΟ ΚΕΝΤΡΙΚΟ ΕΡΩΤΗΜΑ ΔΕΝ ΕΙΝΑΙ «ΤΙ», ΕΙΝΑΙ «ΠΟΙΟΣ ΤΟ ΛΕΕΙ»
 * ══════════════════════════════════════════════════════════════════════
 *
 * Η προηγούμενη βαθμολογία ομαδοποιούσε ΚΑΤΑ ΘΕΜΑ — ασφάλεια,
 * επαγγελματισμός, τεχνικά. Ο εργοδότης όμως δεν ρωτά «πόσο
 * επαγγελματίας είναι»· ρωτά «ποιος το βεβαιώνει».
 *
 * Το αποτέλεσμα εκείνης της ομαδοποίησης, μετρημένο στη βάση (31/08):
 * το 45% της βαθμολογίας ερχόταν από αυτοβαθμολόγηση 1-5 σε πεδία που
 * δεν εμφανίζονταν σε καμία φόρμα — άρα ο κώδικας επέστρεφε σταθερά 50.
 * Συν 30% «ασφάλεια» που ξεκινούσε από το 100 και δεν έπεφτε ποτέ, γιατί
 * κανείς δεν καταχωρούσε συμβάντα. 52,5 μονάδες προκαταβολικά, ίδιες
 * για όλους.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΑ ΠΕΝΤΕ ΕΠΙΠΕΔΑ ΤΕΚΜΗΡΙΟΥ
 * ══════════════════════════════════════════════════════════════════════
 *
 *   VERIFIED  κράτος ή επίσημο έγγραφο (άδεια, ΠΕΙ, ADR, χειριστή)
 *   CERTIFIED φορέας κατάρτισης (σεμινάριο με πάροχο και ημερομηνία)
 *   ATTESTED  τρίτος με όνομα (εργοδότης, καταγεγραμμένο συμβάν)
 *   MEASURED  αισθητήρας (ταχογράφος, τηλεματική)
 *   DECLARED  ο ίδιος ο οδηγός
 *
 * **Το DECLARED δεν μπαίνει ΠΟΤΕ στη βαθμολογία.** Αριθμός που ο
 * αξιολογούμενος ανεβάζει μόνος του δεν είναι αξιολόγηση, είναι δήλωση —
 * και ο πρώτος εργοδότης που το καταλαβαίνει σταματά να κοιτάζει τον
 * αριθμό για πάντα. Εμφανίζεται, εξηγείται, και δείχνει τον δρόμο για να
 * γίνει επαληθευμένο (π.χ. βεβαίωση εργοδότη για την προϋπηρεσία).
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΜΗΤΡΩΟ ΚΑΙ ΟΧΙ ΑΠΛΩΣ ΚΛΑΣΕΙΣ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Ο Κώστας ζήτησε ρητά «να προβλέψουμε ΟΛΕΣ τις πιθανές πηγές και να
 * δημιουργήσουμε την υποδομή». Το μητρώο δηλώνει και τις πηγές που
 * ΔΕΝ έχουν υλοποιηθεί ακόμη (`active => false`): ο ταχογράφος και η
 * τηλεματική υπάρχουν εδώ, φαίνονται στην οθόνη ως «δεν έχει
 * ενεργοποιηθεί», και όταν έρθει η ώρα τους γράφεται ΜΟΝΟ ο collector.
 * Καμία αλλαγή σε service, view ή βάση.
 *
 * Έτσι η οθόνη δεν λέει ποτέ ψέματα για το τι μετράει και τι όχι.
 */
final class ScoreSource
{
    // ── Επίπεδα τεκμηρίου ───────────────────────────────────────────────
    public const VERIFIED = 'verified';
    public const CERTIFIED = 'certified';
    public const ATTESTED = 'attested';
    public const MEASURED = 'measured';
    public const DECLARED = 'declared';

    /** Πόσο βαραίνει το κάθε επίπεδο στην «εμπιστοσύνη» του αριθμού. */
    public const EVIDENCE_LABELS = [
        self::VERIFIED => 'Επαληθευμένο από επίσημο έγγραφο',
        self::CERTIFIED => 'Πιστοποιημένο από φορέα κατάρτισης',
        self::ATTESTED => 'Βεβαιωμένο από τρίτο πρόσωπο',
        self::MEASURED => 'Μετρημένο από αισθητήρα',
        self::DECLARED => 'Δηλωμένο από τον ίδιο',
    ];

    /** Σύντομη ετικέτα για πλακίδιο. */
    public const EVIDENCE_SHORT = [
        self::VERIFIED => 'Επαληθευμένο',
        self::CERTIFIED => 'Πιστοποιημένο',
        self::ATTESTED => 'Βεβαιωμένο',
        self::MEASURED => 'Μετρημένο',
        self::DECLARED => 'Δηλωμένο',
    ];

    // ── Ομάδες εμφάνισης ────────────────────────────────────────────────
    /** Τι κρατά στα χέρια του: χαρτιά με ισχύ. */
    public const GROUP_CREDENTIALS = 'credentials';
    /** Τι λένε οι άλλοι και τι δείχνουν οι μετρήσεις. */
    public const GROUP_REPUTATION = 'reputation';
    /** Τι λέει ο ίδιος — καθοδήγηση, όχι βαθμός. */
    public const GROUP_GUIDANCE = 'guidance';

    /**
     * ΟΛΕΣ οι πηγές, υλοποιημένες και μη.
     *
     *   evidence → επίπεδο τεκμηρίου (καθορίζει αν μετράει)
     *   group    → σε ποιον από τους δύο αριθμούς συνεισφέρει
     *   weight   → μέγιστες μονάδες που μπορεί να δώσει (αρνητικό = ποινή)
     *   active   → αν έχει γραφτεί collector
     *   collector→ η κλάση που τη διαβάζει
     *   hint     → τι πρέπει να κάνει ο οδηγός για να την ενεργοποιήσει
     */
    public const ALL = [
        // ── Χαρτιά ──────────────────────────────────────────────────────
        'license' => [
            'label' => 'Άδεια οδήγησης & ΠΕΙ',
            'evidence' => self::VERIFIED,
            'group' => self::GROUP_CREDENTIALS,
            'weight' => 45,
            'active' => true,
            'collector' => Collectors\LicenseCollector::class,
            'hint' => 'Καταχώρησε τις κατηγορίες και τις ημερομηνίες λήξης.',
        ],
        'certificate' => [
            'label' => 'Πιστοποιητικά & ειδικές άδειες',
            'evidence' => self::VERIFIED,
            'group' => self::GROUP_CREDENTIALS,
            'weight' => 35,
            'active' => true,
            'collector' => Collectors\CertificateCollector::class,
            'hint' => 'ADR, κάρτα ταχογράφου, ΕΔΧ και λοιπές ειδικές άδειες.',
        ],
        'operator' => [
            'label' => 'Άδεια χειριστή μηχανημάτων έργου',
            'evidence' => self::VERIFIED,
            'group' => self::GROUP_CREDENTIALS,
            'weight' => 30,
            'active' => true,
            'collector' => Collectors\OperatorCollector::class,
            'hint' => 'Ειδικότητες και ομάδες του βιβλιαρίου χειριστή.',
        ],
        'training' => [
            'label' => 'Σεμινάρια & κατάρτιση',
            'evidence' => self::CERTIFIED,
            'group' => self::GROUP_CREDENTIALS,
            'weight' => 25,
            'active' => true,
            'collector' => Collectors\TrainingCollector::class,
            'hint' => 'Πρόσθεσε σεμινάρια με φορέα και ημερομηνία.',
        ],

        'insurance_record' => [
            'label' => 'Ασφαλιστικό ιστορικό (ένσημα)',
            'evidence' => self::VERIFIED,
            'group' => self::GROUP_CREDENTIALS,
            'weight' => 30,
            'active' => false,   // βλ. InsuranceRecordCollector για το πλάνο
            'collector' => Collectors\InsuranceRecordCollector::class,
            'hint' => 'Θα μπορείς να ανεβάζεις τη Βεβαίωση Ασφαλιστικής Ιστορίας από το gov.gr — η προϋπηρεσία σου γίνεται επαληθευμένη.',
        ],

        // ── Φήμη ────────────────────────────────────────────────────────
        'employer_review' => [
            'label' => 'Αξιολογήσεις εργοδοτών',
            'evidence' => self::ATTESTED,
            'group' => self::GROUP_REPUTATION,
            'weight' => 100,
            'active' => true,
            'collector' => Collectors\EmployerReviewCollector::class,
            'hint' => 'Ζήτησε από παλιό εργοδότη να σε αξιολογήσει.',
        ],
        'incident' => [
            'label' => 'Καταγεγραμμένα συμβάντα',
            'evidence' => self::ATTESTED,
            'group' => self::GROUP_REPUTATION,
            'weight' => -40,   // μόνο ποινή· δεν δίνει ποτέ μονάδες
            'active' => true,
            'collector' => Collectors\IncidentCollector::class,
            'hint' => '',
        ],
        'tachograph' => [
            'label' => 'Ψηφιακός ταχογράφος',
            'evidence' => self::MEASURED,
            'group' => self::GROUP_REPUTATION,
            'weight' => 60,
            'active' => false,   // βήμα 7 του πλάνου
            'collector' => Collectors\TachographCollector::class,
            'hint' => 'Θα μπορείς να ανεβάζεις το αρχείο της κάρτας σου (.DDD).',
        ],
        'telematics' => [
            'label' => 'Τηλεματική κινητού',
            'evidence' => self::MEASURED,
            'group' => self::GROUP_REPUTATION,
            'weight' => 40,
            'active' => false,   // βήμα 8 — μόνο για όσους δεν έχουν ταχογράφο
            'collector' => Collectors\TelematicsCollector::class,
            'hint' => 'Προαιρετική εφαρμογή που μετρά την οδήγησή σου.',
        ],

        // ── Καθοδήγηση (ΔΕΝ βαθμολογείται) ─────────────────────────────
        'experience' => [
            'label' => 'Προϋπηρεσία σε οχήματα',
            'evidence' => self::DECLARED,
            'group' => self::GROUP_GUIDANCE,
            'weight' => 0,
            'active' => true,
            'collector' => Collectors\ExperienceCollector::class,
            'hint' => 'Γίνεται επαληθευμένη όταν την επιβεβαιώσει εργοδότης.',
        ],
        'self_assessment' => [
            'label' => 'Αυτοαξιολόγηση',
            'evidence' => self::DECLARED,
            'group' => self::GROUP_GUIDANCE,
            'weight' => 0,
            'active' => false,   // η φόρμα δεν υπάρχει· βλ. σχόλιο παρακάτω
            'collector' => Collectors\SelfAssessmentCollector::class,
            'hint' => 'Δείχνει πού να εστιάσεις — δεν μετράει στη βαθμολογία.',
        ],
    ];

    /** Τα επίπεδα που ΜΕΤΡΑΝΕ. Το DECLARED λείπει σκόπιμα. */
    public const SCORING_EVIDENCE = [
        self::VERIFIED,
        self::CERTIFIED,
        self::ATTESTED,
        self::MEASURED,
    ];

    public static function counts(string $evidence): bool
    {
        return in_array($evidence, self::SCORING_EVIDENCE, true);
    }

    /** @return array<string, array> οι πηγές μιας ομάδας, με τη σειρά του μητρώου */
    public static function inGroup(string $group): array
    {
        return array_filter(self::ALL, static fn($s) => $s['group'] === $group);
    }

    public static function get(string $key): ?array
    {
        return self::ALL[$key] ?? null;
    }

    public static function label(string $key): string
    {
        return self::ALL[$key]['label'] ?? $key;
    }
}

<?php

namespace Drivejob\Helpers;

/**
 * Η μοναδική πηγή αλήθειας για τους τύπους οχημάτων.
 *
 * ΓΙΑΤΙ ΥΠΑΡΧΕΙ: το λεξιλόγιο είχε αποκλίνει σε πέντε ανεξάρτητες εκδοχές —
 * τα κουτάκια της φόρμας δημιουργίας, ο έλεγχος εγκυρότητας στο
 * JobListingService, οι ετικέτες σε δύο views «Οι αγγελίες μου», η
 * αντιστοίχιση οχήματος–διπλώματος σε τρία services ταιριάσματος, και οι
 * τιμές που είχαν ήδη αποθηκευτεί στη βάση.
 *
 * Η τομή φόρμας και ελέγχου εγκυρότητας ήταν μόλις τρεις τιμές (car, van,
 * bus). Κάθε αγγελία για φορτηγό απορριπτόταν ως «μη έγκυρος τύπος οχήματος»,
 * και επειδή ο χειρισμός του σφάλματος έσπαγε κι αυτός, ο χρήστης έβλεπε 500.
 *
 * Κάθε νέος τύπος οχήματος προστίθεται ΕΔΩ και πουθενά αλλού.
 */
final class VehicleTypes
{
    /**
     * Κωδικός => ελληνική ετικέτα.
     *
     * Οι κωδικοί είναι ταυτόσημοι με τις τιμές των κουτακιών στη φόρμα
     * δημιουργίας αγγελίας και με τη στήλη
     * job_listing_vehicle_types.vehicle_type.
     */
    private const TYPES = [
        // Επιβατικά
        'car'                => 'Επιβατικό αυτοκίνητο',
        'minibus'            => 'Μίνι πούλμαν',
        'bus'                => 'Λεωφορείο',

        // Ελαφρά εμπορευματικά
        'van'                => 'Βαν',

        // Φορτηγά
        'truck_light'        => 'Ελαφρύ φορτηγό',
        'truck_medium'       => 'Μεσαίο φορτηγό',
        'truck_heavy'        => 'Βαρύ φορτηγό',
        'truck_articulated'  => 'Συρμός / νταλίκα',
        'truck_tanker'       => 'Βυτιοφόρο',
        'truck_refrigerated' => 'Ψυγείο',

        // Μηχανήματα έργου
        'machinery'          => 'Μηχάνημα έργου',
    ];

    /**
     * Ομαδοποίηση για την εμφάνιση σε φόρμες και φίλτρα.
     */
    private const GROUPS = [
        'Επιβατικές μεταφορές'    => ['car', 'minibus', 'bus'],
        'Εμπορευματικές μεταφορές' => [
            'van', 'truck_light', 'truck_medium', 'truck_heavy',
            'truck_articulated', 'truck_tanker', 'truck_refrigerated',
        ],
        'Μηχανήματα έργου'        => ['machinery'],
    ];

    /**
     * Ποιες κατηγορίες άδειας οδήγησης καλύπτουν κάθε τύπο οχήματος.
     *
     * Χρησιμοποιείται από τα services ταιριάσματος. Το «machinery» δεν
     * αντιστοιχεί σε κατηγορία διπλώματος — απαιτεί άδεια χειριστή
     * μηχανημάτων έργου, που είναι ξεχωριστό πιστοποιητικό (πίνακας
     * driver_operator_licenses).
     */
    private const LICENSES = [
        'car'                => ['B'],
        'van'                => ['B'],
        'minibus'            => ['D1', 'D'],
        'bus'                => ['D', 'DE', 'D1', 'D1E'],
        'truck_light'        => ['C1', 'C'],
        'truck_medium'       => ['C', 'C1'],
        'truck_heavy'        => ['C'],
        'truck_articulated'  => ['CE', 'C1E'],
        'truck_tanker'       => ['C', 'CE'],
        'truck_refrigerated' => ['C', 'CE'],
        'machinery'          => [],
    ];

    /**
     * Παλιές τιμές που συναντώνται σε δεδομένα προηγούμενων εκδόσεων.
     *
     * Το migration fix_job_listing_fields.php τις μετέτρεψε στη βάση, αλλά η
     * αντιστοίχιση μένει εδώ ώστε τυχόν παλιά τιμή από cache, εξαγωγή ή
     * χειροκίνητη εισαγωγή να εμφανίζεται σωστά αντί για ακατέργαστο κωδικό.
     */
    private const LEGACY = [
        'truck'         => 'truck_medium',
        'truck_semi'    => 'truck_articulated',
        'truck_trailer' => 'truck_articulated',
        'truck_2axle'   => 'truck_medium',
        'truck_3axle'   => 'truck_heavy',
        // Βρέθηκαν στη ΖΩΝΤΑΝΗ βάση (23/08): 2 αγγελίες «trailer» και 2
        // «tanker» — τιμές που δεν υπήρχαν σε κανένα λεξιλόγιο και έπεφταν
        // σιωπηλά στο εφεδρικό εικονίδιο μεσαίου φορτηγού. Ένα βυτιοφόρο
        // που δείχνει σαν καρότσα δεν είναι καλλωπιστικό λάθος: ο οδηγός
        // ADR δεν αναγνωρίζει τη θέση που τον αφορά.
        'trailer'       => 'truck_articulated',
        'tanker'        => 'truck_tanker',
        'refrigerated'  => 'truck_refrigerated',
        'semi'          => 'truck_articulated',
        'taxi'          => 'car',
        'special'       => 'machinery',
        'forklift'      => 'machinery',
        'crane'         => 'machinery',
        'excavator'     => 'machinery',
        'tractor'       => 'machinery',
    ];

    /** @return array<string, string> κωδικός => ελληνική ετικέτα */
    public static function all(): array
    {
        return self::TYPES;
    }

    /** @return array<string, string[]> ομάδα => κωδικοί */
    public static function groups(): array
    {
        return self::GROUPS;
    }

    /** @return string[] μόνο οι κωδικοί */
    public static function codes(): array
    {
        return array_keys(self::TYPES);
    }

    public static function isValid(?string $code): bool
    {
        return $code !== null && isset(self::TYPES[self::normalise($code)]);
    }

    /**
     * Μετατρέπει παλιές τιμές στην τρέχουσα ονομασία. Άγνωστες τιμές
     * επιστρέφονται ως έχουν, ώστε να φανούν αντί να εξαφανιστούν σιωπηλά.
     */
    public static function normalise(?string $code): string
    {
        $code = trim((string) $code);

        return self::LEGACY[$code] ?? $code;
    }

    /**
     * Ελληνική ετικέτα. Άγνωστος κωδικός επιστρέφεται ως έχει — καλύτερα να
     * δει ο χρήστης «truck_xyz» και να το αναφέρει, παρά να δει κενό.
     */
    public static function label(?string $code): string
    {
        if (empty($code)) {
            return '—';
        }

        return self::TYPES[self::normalise($code)] ?? $code;
    }

    /**
     * Ετικέτες για λίστα κωδικών, ενωμένες με κόμμα.
     *
     * @param string[]|string|null $codes πίνακας ή τιμές χωρισμένες με κόμμα
     */
    public static function labels($codes): string
    {
        if (empty($codes)) {
            return '—';
        }

        if (is_string($codes)) {
            $codes = array_filter(array_map('trim', explode(',', $codes)));
        }

        $labels = array_map([self::class, 'label'], (array) $codes);

        return implode(', ', array_unique($labels));
    }

    /**
     * Οι κατηγορίες διπλώματος που καλύπτουν αυτόν τον τύπο οχήματος.
     *
     * @return string[] κενός πίνακας για τα μηχανήματα έργου
     */
    public static function licensesFor(?string $code): array
    {
        return self::LICENSES[self::normalise($code)] ?? [];
    }

    /**
     * Απαιτεί άδεια χειριστή μηχανημάτων έργου αντί για δίπλωμα οδήγησης;
     */
    public static function requiresOperatorLicence(?string $code): bool
    {
        return self::normalise($code) === 'machinery';
    }

    /**
     * Όλες οι τιμές που μπορεί να έχει η στήλη για έναν τύπο οχήματος.
     *
     * ══════════════════════════════════════════════════════════════════════
     *  ΓΙΑΤΙ ΤΟ ΦΙΛΤΡΟ ΔΕΝ ΜΠΟΡΕΙ ΝΑ ΣΥΓΚΡΙΝΕΙ ΑΠΛΑ ΜΕ «=»
     * ══════════════════════════════════════════════════════════════════════
     *
     * Η `normalise()` πάει από την παλιά τιμή στη νέα. Το φίλτρο χρειάζεται
     * την ΑΝΤΙΘΕΤΗ κατεύθυνση: ο χρήστης επιλέγει «Συρμός / νταλίκα» και στη
     * βάση υπάρχουν αποθηκευμένα `truck_articulated`, `trailer`, `semi`,
     * `truck_semi`, `truck_trailer` — πέντε γραφές του ίδιου πράγματος.
     *
     * Ένα `WHERE vehicle_type = 'truck_articulated'` θα έκρυβε τις τέσσερις.
     * Και ο οδηγός δεν θα καταλάβαινε ότι κάτι λείπει: θα έβλεπε λιγότερες
     * αγγελίες, όχι σφάλμα.
     *
     * Η μετάπτωση των παλιών τιμών στη βάση θα έλυνε το πρόβλημα μια για
     * πάντα, αλλά μέχρι να γίνει, το φίλτρο πρέπει να δουλεύει με ό,τι
     * υπάρχει εκεί ήδη.
     *
     * @return string[] ο κανονικός κωδικός μαζί με κάθε παλιό συνώνυμό του
     */
    public static function storedValuesFor(?string $code): array
    {
        $canonical = self::normalise($code);

        if ($canonical === '') {
            return [];
        }

        $values = [$canonical];

        foreach (self::LEGACY as $old => $new) {
            if ($new === $canonical) {
                $values[] = $old;
            }
        }

        return array_values(array_unique($values));
    }
}

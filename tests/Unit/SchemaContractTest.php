<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PDO;

/**
 * Συμβόλαιο σχήματος βάσης (Πακέτο 6).
 *
 * ΓΙΑΤΙ ΥΠΑΡΧΕΙ: όλα σχεδόν τα σοβαρά bugs που βρέθηκαν στην εξυγίανση ήταν
 * «schema drift» — ο κώδικας ζητούσε στήλες/πίνακες που δεν υπάρχουν, και το
 * try/catch τα έκρυβε (κενές λίστες αντί για σφάλμα). Παραδείγματα:
 *   - job_applications.job_id           → σωστό: job_listing_id
 *   - drivers.is_active                 → σωστό: is_verified
 *   - drivers.years_experience          → σωστό: experience_years
 *   - driver_adr / driver_tachograph    → σωστά: *_certificates / *_cards
 *   - driver_certifications.certification_name → σωστό: title
 *
 * Αν κάποιος αλλάξει σχήμα χωρίς migration (ή γράψει λάθος όνομα), σκάει ΕΔΩ.
 */
class SchemaContractTest extends TestCase
{
    private static ?PDO $pdo = null;

    /** @var array<string, string[]> πίνακας => στήλες που ΠΡΕΠΕΙ να υπάρχουν */
    private const CONTRACT = [
        'drivers' => ['id', 'email', 'password', 'first_name', 'last_name', 'is_verified', 'experience_years', 'updated_at'],
        'companies' => ['id', 'email', 'password', 'company_name', 'is_verified'],
        'users' => ['id', 'email'],
        'job_listings' => ['id', 'title', 'company_id', 'is_active', 'is_approved', 'applications', 'created_at'],
        'job_applications' => ['id', 'driver_id', 'job_listing_id', 'message', 'status'],
        'driver_licenses' => ['id', 'driver_id', 'license_type', 'expiry_date', 'has_pei', 'pei_expiry_c', 'pei_expiry_d'],
        'driver_adr_certificates' => ['id', 'driver_id', 'adr_type', 'expiry_date'],
        'driver_tachograph_cards' => ['id', 'driver_id', 'card_number', 'expiry_date'],
        'driver_operator_licenses' => ['id', 'driver_id', 'speciality', 'expiry_date'],
        'driver_certifications' => ['id', 'driver_id', 'title'],
        'driver_operator_sub_specialities' => ['id', 'operator_license_id', 'sub_speciality', 'group_type'],
        'conversations' => ['id', 'driver_id', 'company_id', 'job_id', 'subject', 'status'],
        'messages' => ['id', 'conversation_id', 'sender_type', 'message', 'is_read'],
        'ai_usage_log' => ['id', 'used_on', 'model', 'prompt_tokens', 'completion_tokens', 'est_cost_usd'],
        'matching_metrics' => ['id', 'job_id', 'duration_ms', 'cache_hit'],
        'matching_scores' => ['id', 'driver_id'],
        'roles' => ['id', 'name'],
        'user_roles' => ['user_id', 'role_id'],
    ];

    /**
     * Στήλες που ΔΕΝ επιτρέπεται να υπάρχουν (GDPR άρθρο 10 — Πακέτο 1).
     *
     * Σημείωση: drivers.is_active / drivers.years_experience ΥΠΑΡΧΟΥΝ ακόμη ως
     * legacy στήλες με δεδομένα· ο κώδικας όμως πρέπει να διαβάζει is_verified /
     * experience_years (βλ. CONTRACT παραπάνω). Η εκκαθάρισή τους θέλει migration
     * με μεταφορά δεδομένων — δεν μπαίνει εδώ ως αποτυχία.
     */
    private const FORBIDDEN = [
        'drivers' => ['criminal_record', 'criminal_record_file'],

        /**
         * job_listings.vehicle_types: διπλή πηγή αλήθειας. Η μοναδική έγκυρη
         * πηγή είναι ο πίνακας job_listing_vehicle_types. Όσο υπήρχε η στήλη,
         * τέσσερις αγγελίες είχαν δεδομένα μόνο εκεί και κανένα query δεν τα
         * έβλεπε.
         *
         * show_*: διακόπτες εμφάνισης που καμία φόρμα δεν έθετε και κανένα
         * view δεν διάβαζε — οδηγοί είχαν επιλέξει «μην εμφανίζεις το ADR μου»
         * και η επιλογή αγνοούνταν σιωπηλά. Επιστρέφουν μόνο μαζί με την
         * αντίστοιχη εμφάνιση στο Driver/show.php.
         */
        'job_listings' => [
            'vehicle_types',
            'show_rating',
            'show_adr',
            'show_operator_license',
            'show_tachograph',
            'show_skills',
            'show_experience',
            'show_special_licenses',
        ],

        /**
         * job_applications.cover_letter: το repository έγραφε σε αυτή τη
         * στήλη ενώ ο πίνακας έχει `message`. Αποτέλεσμα: η υποβολή αίτησης,
         * η απόσυρση και η αποδοχή/απόρριψη απέτυχαν σιωπηλά για μήνες.
         *
         * job_applications.company_id: η εταιρεία προκύπτει ΜΟΝΟ μέσω
         * job_listings.company_id. Όσο ο κώδικας διάβαζε ανύπαρκτο
         * $application['company_id'], κάθε έλεγχος ιδιοκτησίας απέτυχε.
         */
        'job_applications' => [
            'cover_letter',
            'company_id',
        ],
    ];

    public static function setUpBeforeClass(): void
    {
        try {
            self::$pdo = new PDO(
                'mysql:host=' . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ';dbname=' . ($_ENV['DB_NAME'] ?? 'drivejob') . ';charset=utf8mb4',
                $_ENV['DB_USER'] ?? 'root',
                $_ENV['DB_PASS'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (\PDOException $e) {
            self::$pdo = null;
        }
    }

    protected function setUp(): void
    {
        if (!self::$pdo) {
            $this->markTestSkipped('Δεν υπάρχει διαθέσιμη βάση drivejob.');
        }
    }

    /** Κάθε πίνακας του συμβολαίου υπάρχει με ΟΛΕΣ τις απαιτούμενες στήλες. */
    public function testRequiredTablesAndColumnsExist(): void
    {
        $missing = [];

        foreach (self::CONTRACT as $table => $columns) {
            $stmt = self::$pdo->prepare(
                'SELECT column_name FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = ?'
            );
            $stmt->execute([$table]);
            $actual = array_map('strtolower', $stmt->fetchAll(PDO::FETCH_COLUMN));

            if (!$actual) {
                $missing[] = "ΠΙΝΑΚΑΣ {$table} δεν υπάρχει";
                continue;
            }
            foreach ($columns as $col) {
                if (!in_array(strtolower($col), $actual, true)) {
                    $missing[] = "{$table}.{$col}";
                }
            }
        }

        $this->assertSame([], $missing, "Λείπουν από τη βάση:\n" . implode("\n", $missing));
    }

    /** Απαγορευμένες στήλες (GDPR/παλιά ονόματα) δεν έχουν επανεμφανιστεί. */
    public function testForbiddenColumnsAreGone(): void
    {
        $found = [];

        foreach (self::FORBIDDEN as $table => $columns) {
            foreach ($columns as $col) {
                $stmt = self::$pdo->prepare(
                    'SELECT COUNT(*) FROM information_schema.columns
                     WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
                );
                $stmt->execute([$table, $col]);
                if ((int) $stmt->fetchColumn() > 0) {
                    $found[] = "{$table}.{$col}";
                }
            }
        }

        $this->assertSame([], $found, "Βρέθηκαν στήλες που έπρεπε να έχουν αφαιρεθεί:\n" . implode("\n", $found));
    }

    /**
     * Τα queries που «κρύβονται» πίσω από try/catch τρέχουν πραγματικά.
     * (Το κάθε ένα από αυτά αντιστοιχεί σε bug που είχε συμβεί στην πράξη.)
     */
    public function testCriticalQueriesExecute(): void
    {
        $queries = [
            'top matches οδηγού (job_listing_id)' =>
                'SELECT j.id FROM job_listings j WHERE j.is_active = 1
                 AND j.id NOT IN (SELECT job_listing_id FROM job_applications WHERE driver_id = 1) LIMIT 1',
            'υποψήφιοι αγγελίας' =>
                'SELECT driver_id FROM job_applications WHERE job_listing_id = 1 LIMIT 1',
            'ιδιοκτησία αρχείου (FileController)' =>
                'SELECT 1 FROM job_applications ja
                 JOIN job_listings jl ON jl.id = ja.job_listing_id WHERE ja.driver_id = 1 LIMIT 1',
            'λήξεις ΠΕΙ' =>
                'SELECT d.id FROM drivers d JOIN driver_licenses dl ON d.id = dl.driver_id
                 WHERE dl.pei_expiry_c IS NOT NULL AND dl.has_pei = 1 AND d.is_verified = 1 LIMIT 1',
            'πιστοποιήσεις οδηγού (title)' =>
                'SELECT title FROM driver_certifications LIMIT 1',
        ];

        foreach ($queries as $label => $sql) {
            try {
                self::$pdo->query($sql);
                $this->assertTrue(true);
            } catch (\PDOException $e) {
                $this->fail("Το query «{$label}» απέτυχε: " . $e->getMessage());
            }
        }
    }
}

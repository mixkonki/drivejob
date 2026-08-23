<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Drivejob\Services\Visibility;
use PDO;

/**
 * Κανόνες ορατότητας στοιχείων επικοινωνίας.
 *
 * ΓΙΑΤΙ ΥΠΑΡΧΕΙ: πριν από αυτό το πακέτο, το προφίλ κάθε οδηγού ήταν
 * προσβάσιμο σε οποιονδήποτε στο διαδίκτυο — χωρίς σύνδεση — με το email και
 * το τηλέφωνό του ως clickable mailto: και tel:. Οι κανόνες που το
 * αντικατέστησαν είναι κανόνες προστασίας προσωπικών δεδομένων: αν κάποιος
 * τους χαλαρώσει κατά λάθος, πρέπει να σκάσει εδώ και όχι στην παραγωγή.
 *
 * Το τεστ φτιάχνει δικά του δεδομένα σε transaction και κάνει rollback, ώστε
 * να μην εξαρτάται από το τι τυχαίνει να υπάρχει στη βάση.
 */
class VisibilityTest extends TestCase
{
    private static ?PDO $pdo = null;
    private Visibility $visibility;

    /** @var array<string, int> */
    private array $ids = [];

    public static function setUpBeforeClass(): void
    {
        self::$pdo = new PDO(
            'mysql:host=' . ($_ENV['DB_HOST'] ?? '127.0.0.1')
            . ';dbname=' . ($_ENV['DB_NAME'] ?? 'drivejob') . ';charset=utf8mb4',
            $_ENV['DB_USER'] ?? 'root',
            $_ENV['DB_PASS'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    protected function setUp(): void
    {
        self::$pdo->beginTransaction();
        $this->visibility = new Visibility(self::$pdo);
        $this->seed();
    }

    protected function tearDown(): void
    {
        self::$pdo->rollBack();
    }

    /**
     * Δύο εταιρείες, τρεις οδηγοί, τέσσερις αιτήσεις σε διαφορετικές
     * καταστάσεις. Κάθε οδηγός έχει ΜΙΑ μόνο σχέση με κάθε εταιρεία, ώστε
     * κάθε έλεγχος να απομονώνει ακριβώς μία κατάσταση.
     */
    private function seed(): void
    {
        $suffix = '@visibility-test.invalid';
        $now = date('Y-m-d H:i:s');

        $company = function (string $name) use ($suffix, $now): int {
            $stmt = self::$pdo->prepare(
                'INSERT INTO companies (email, password, company_name, phone, vat_number, is_active, created_at, updated_at)
                 VALUES (:e, :p, :n, :ph, :v, 1, :c, :c)'
            );
            // Το vat_number έχει μοναδικό ευρετήριο — κάθε εταιρεία του τεστ
            // χρειάζεται δικό της, αλλιώς η δεύτερη εισαγωγή σκάει.
            static $vat = 900000900;
            $stmt->execute([
                ':e' => $name . $suffix, ':p' => 'x', ':n' => $name,
                ':ph' => '2310000000', ':v' => (string) $vat++, ':c' => $now,
            ]);
            return (int) self::$pdo->lastInsertId();
        };

        $driver = function (string $name) use ($suffix, $now): int {
            $stmt = self::$pdo->prepare(
                'INSERT INTO drivers (email, password, first_name, last_name, phone, is_active, created_at, updated_at)
                 VALUES (:e, :p, :f, :l, :ph, 1, :c, :c)'
            );
            $stmt->execute([
                ':e' => $name . $suffix, ':p' => 'x', ':f' => 'Δοκιμή',
                ':l' => $name, ':ph' => '6970000000', ':c' => $now,
            ]);
            return (int) self::$pdo->lastInsertId();
        };

        $listing = function (int $companyId) use ($now): int {
            $stmt = self::$pdo->prepare(
                'INSERT INTO job_listings (title, company_id, listing_type, job_type, description, location, is_active, is_approved, created_at, updated_at)
                 VALUES (:t, :c, :lt, :jt, :d, :l, 1, 1, :n, :n)'
            );
            $stmt->execute([
                ':t' => 'Δοκιμαστική αγγελία', ':c' => $companyId, ':lt' => 'job_offer',
                ':jt' => 'full_time', ':d' => 'Δοκιμή', ':l' => 'Θεσσαλονίκη', ':n' => $now,
            ]);
            return (int) self::$pdo->lastInsertId();
        };

        $apply = function (int $driverId, int $listingId, string $status) use ($now): void {
            $stmt = self::$pdo->prepare(
                'INSERT INTO job_applications (driver_id, job_listing_id, message, status, created_at, updated_at)
                 VALUES (:d, :l, :m, :s, :n, :n)'
            );
            $stmt->execute([
                ':d' => $driverId, ':l' => $listingId, ':m' => 'Δοκιμή',
                ':s' => $status, ':n' => $now,
            ]);
        };

        $this->ids['companyA'] = $company('AlphaTest');
        $this->ids['companyB'] = $company('BetaTest');
        $this->ids['listingA'] = $listing($this->ids['companyA']);
        $this->ids['listingA2'] = $listing($this->ids['companyA']);

        $this->ids['pendingDriver'] = $driver('Pending');
        $this->ids['shortlistedDriver'] = $driver('Shortlisted');
        $this->ids['hiredDriver'] = $driver('Hired');
        $this->ids['withdrawnDriver'] = $driver('Withdrawn');
        $this->ids['strangerDriver'] = $driver('Stranger');

        $apply($this->ids['pendingDriver'], $this->ids['listingA'], 'pending');
        $apply($this->ids['shortlistedDriver'], $this->ids['listingA'], 'shortlisted');
        $apply($this->ids['hiredDriver'], $this->ids['listingA2'], 'hired');
        $apply($this->ids['withdrawnDriver'], $this->ids['listingA'], 'withdrawn');
    }

    // ───────────────────────────────────── Προφίλ οδηγού

    public function testGuestCannotSeeDriverProfile(): void
    {
        $this->assertFalse(
            $this->visibility->canViewDriverProfile(null, null, $this->ids['pendingDriver']),
            'Τα προφίλ οδηγών ΔΕΝ επιτρέπεται να είναι δημόσια — είναι προσωπικά δεδομένα.'
        );
    }

    public function testUnrelatedCompanyCannotSeeDriverProfile(): void
    {
        $this->assertFalse(
            $this->visibility->canViewDriverProfile('company', $this->ids['companyB'], $this->ids['pendingDriver']),
            'Εταιρεία που δεν έλαβε αίτηση δεν βλέπει τον οδηγό.'
        );
    }

    public function testCompanyWithApplicationSeesDriverProfile(): void
    {
        $this->assertTrue(
            $this->visibility->canViewDriverProfile('company', $this->ids['companyA'], $this->ids['pendingDriver']),
            'Ο οδηγός έκανε αίτηση — έδωσε ο ίδιος τη συναίνεσή του.'
        );
    }

    public function testWithdrawnApplicationRevokesAccess(): void
    {
        $this->assertFalse(
            $this->visibility->canViewDriverProfile('company', $this->ids['companyA'], $this->ids['withdrawnDriver']),
            'Η απόσυρση της αίτησης ανακαλεί και τη συναίνεση.'
        );
    }

    public function testDriverSeesOwnProfile(): void
    {
        $this->assertTrue(
            $this->visibility->canViewDriverProfile('driver', $this->ids['pendingDriver'], $this->ids['pendingDriver'])
        );
    }

    public function testDriverCannotSeeAnotherDriver(): void
    {
        $this->assertFalse(
            $this->visibility->canViewDriverProfile('driver', $this->ids['strangerDriver'], $this->ids['pendingDriver'])
        );
    }

    // ───────────────────────────────────── Προφίλ εταιρείας

    public function testGuestCannotSeeCompanyProfile(): void
    {
        $this->assertFalse(
            $this->visibility->canViewCompanyProfile(null, null, $this->ids['companyA'])
        );
    }

    public function testLoggedInDriverSeesCompanyProfile(): void
    {
        $this->assertTrue(
            $this->visibility->canViewCompanyProfile('driver', $this->ids['strangerDriver'], $this->ids['companyA']),
            'Το προφίλ εταιρείας είναι εμπορική πληροφορία — ανοιχτό σε συνδεδεμένους.'
        );
    }

    // ───────────────────────────────────── Επικοινωνία εταιρείας

    public function testGuestCannotSeeCompanyContact(): void
    {
        $this->assertFalse(
            $this->visibility->canViewCompanyContact(null, null, $this->ids['companyA'])
        );
    }

    public function testPendingApplicationDoesNotUnlockCompanyContact(): void
    {
        $this->assertFalse(
            $this->visibility->canViewCompanyContact('driver', $this->ids['pendingDriver'], $this->ids['companyA']),
            'Σκέτη αίτηση δεν αρκεί: αλλιώς κάποιος κάνει 20 αιτήσεις και μαζεύει 20 τηλέφωνα.'
        );
    }

    public function testShortlistUnlocksCompanyContact(): void
    {
        $this->assertTrue(
            $this->visibility->canViewCompanyContact('driver', $this->ids['shortlistedDriver'], $this->ids['companyA']),
            'Η προεπιλογή σημαίνει ότι η εταιρεία ενδιαφέρεται πραγματικά.'
        );
    }

    public function testHireUnlocksCompanyContact(): void
    {
        $this->assertTrue(
            $this->visibility->canViewCompanyContact('driver', $this->ids['hiredDriver'], $this->ids['companyA'])
        );
    }

    public function testCompanySeesOwnContact(): void
    {
        $this->assertTrue(
            $this->visibility->canViewCompanyContact('company', $this->ids['companyA'], $this->ids['companyA'])
        );
    }

    public function testEngagementIsPerCompany(): void
    {
        $this->assertFalse(
            $this->visibility->canViewCompanyContact('driver', $this->ids['hiredDriver'], $this->ids['companyB']),
            'Πρόσληψη σε μία εταιρεία δεν ξεκλειδώνει τα στοιχεία άλλης.'
        );
    }

    // ───────────────────────────────────── Απόκρυψη

    public function testMaskingHidesEnoughToBeUseless(): void
    {
        $masked = Visibility::maskEmail('kostas.michailidis@hotmail.gr');
        $this->assertStringContainsString('@hotmail.gr', $masked);
        $this->assertStringNotContainsString('kostas.michailidis', $masked);

        $phone = Visibility::maskPhone('6972964602');
        $this->assertStringNotContainsString('6972964602', $phone);
        $this->assertStringStartsWith('697', $phone);
    }

    public function testMaskingSurvivesEmptyValues(): void
    {
        $this->assertSame('•••', Visibility::maskEmail(null));
        $this->assertSame('•••', Visibility::maskEmail(''));
        $this->assertSame('•••', Visibility::maskPhone(null));
        $this->assertSame('•••', Visibility::maskPhone('12'));
    }
}

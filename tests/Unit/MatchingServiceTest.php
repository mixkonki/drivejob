<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Drivejob\Services\MatchingService;
use PDO;

/**
 * Δοκιμές του rule-based MatchingService (Πακέτο 6 — ξαναγράφτηκε
 * πάνω στο ΠΡΑΓΜΑΤΙΚΟ API· το παλιό αρχείο στόχευε ανύπαρκτη κλάση).
 *
 * Απαιτεί βάση drivejob (τοπικά: DBngin / CI: MariaDB service).
 */
class MatchingServiceTest extends TestCase
{
    private static ?PDO $pdo = null;
    private MatchingService $service;

    public static function setUpBeforeClass(): void
    {
        try {
            self::$pdo = new PDO(
                'mysql:host=' . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ';dbname=' . ($_ENV['DB_NAME'] ?? 'drivejob') . ';charset=utf8mb4',
                $_ENV['DB_USER'] ?? 'root',
                $_ENV['DB_PASS'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
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
        $this->service = new MatchingService(self::$pdo);
    }

    /** Το σκορ με πλήρη δεδομένα οδηγού/αγγελίας είναι πάντα στο 0-100. */
    public function testCalculateMatchScoreWithinBounds(): void
    {
        $driver = self::$pdo->query('SELECT * FROM drivers LIMIT 1')->fetch();
        $job = self::$pdo->query('SELECT * FROM job_listings LIMIT 1')->fetch();
        $this->assertNotEmpty($driver);
        $this->assertNotEmpty($job);

        $score = $this->service->calculateMatchScore($driver, $job);

        $this->assertIsNumeric($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /** Ίδια είσοδος ⇒ ίδιο σκορ (ντετερμινιστικό rule-based). */
    public function testCalculateMatchScoreIsDeterministic(): void
    {
        $driver = self::$pdo->query('SELECT * FROM drivers LIMIT 1')->fetch();
        $job = self::$pdo->query('SELECT * FROM job_listings LIMIT 1')->fetch();

        $a = $this->service->calculateMatchScore($driver, $job);
        $b = $this->service->calculateMatchScore($driver, $job);

        $this->assertEquals($a, $b);
    }

    /** Ελλιπή δεδομένα δεν ρίχνουν exception — επιστρέφεται έγκυρο σκορ. */
    public function testCalculateMatchScoreToleratesMissingFields(): void
    {
        $driver = ['id' => null, 'city' => null, 'address' => null];
        $job = ['id' => null, 'location' => null, 'job_type' => null];

        $score = $this->service->calculateMatchScore($driver, $job);

        $this->assertIsNumeric($score);
        $this->assertGreaterThanOrEqual(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    /** Η σελιδοποιημένη λίστα ταιριασμάτων οδηγού έχει το αναμενόμενο σχήμα. */
    public function testFindDriverMatchesReturnsPaginatedShape(): void
    {
        $driverId = (int) self::$pdo->query('SELECT id FROM drivers ORDER BY id LIMIT 1')->fetchColumn();

        $result = $this->service->findDriverMatches($driverId, 1, 5);

        $this->assertIsArray($result);
        foreach ($result as $match) {
            $this->assertIsArray($match);
        }
    }
}

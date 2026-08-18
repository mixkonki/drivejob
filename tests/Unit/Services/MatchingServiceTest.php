<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Drivejob\Services\AI\MatchingService;
use PDO;

/**
 * Δοκιμές του AI\MatchingService (Πακέτο 6 — ξαναγράφτηκε πάνω στο
 * ΠΡΑΓΜΑΤΙΚΟ API: constructor χωρίς ορίσματα, calculateMatch/getTopMatchesForDriver).
 *
 * Σε web/test context το AI δεν καλείται (CLI-only guard) — ελέγχεται
 * η rule-based διαδρομή. Απαιτεί βάση drivejob.
 */
class MatchingServiceTest extends TestCase
{
    private static ?PDO $pdo = null;
    private MatchingService $service;
    private static int $driverId = 0;
    private static int $jobId = 0;

    public static function setUpBeforeClass(): void
    {
        try {
            self::$pdo = new PDO(
                'mysql:host=' . ($_ENV['DB_HOST'] ?? '127.0.0.1') . ';dbname=' . ($_ENV['DB_NAME'] ?? 'drivejob') . ';charset=utf8mb4',
                $_ENV['DB_USER'] ?? 'root',
                $_ENV['DB_PASS'] ?? ''
            );
            self::$driverId = (int) self::$pdo->query('SELECT id FROM drivers ORDER BY id LIMIT 1')->fetchColumn();
            self::$jobId = (int) self::$pdo->query('SELECT id FROM job_listings ORDER BY id LIMIT 1')->fetchColumn();
        } catch (\PDOException $e) {
            self::$pdo = null;
        }
    }

    protected function setUp(): void
    {
        if (!self::$pdo || !self::$driverId || !self::$jobId) {
            $this->markTestSkipped('Δεν υπάρχει διαθέσιμη βάση drivejob με δεδομένα.');
        }
        $this->service = new MatchingService();
    }

    /** Το calculateMatch επιστρέφει πλήρη ανάλυση με σκορ 0-100. */
    public function testCalculateMatchReturnsValidScore(): void
    {
        $result = $this->service->calculateMatch(self::$driverId, self::$jobId);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_score', $result);
        $this->assertGreaterThanOrEqual(0, $result['overall_score']);
        $this->assertLessThanOrEqual(100, $result['overall_score']);
    }

    /** Τα κορυφαία ταιριάσματα οδηγού είναι πίνακας με το ζητούμενο όριο. */
    public function testGetTopMatchesForDriverReturnsArray(): void
    {
        $result = $this->service->getTopMatchesForDriver(self::$driverId, 5);

        $this->assertIsArray($result);
        $this->assertLessThanOrEqual(5, count($result));
    }

    /** Ανύπαρκτος οδηγός/αγγελία δεν ρίχνει fatal — ελεγχόμενη συμπεριφορά. */
    public function testCalculateMatchWithUnknownIdsIsHandled(): void
    {
        try {
            $result = $this->service->calculateMatch(999999, 999999);
            $this->assertIsArray($result);
        } catch (\Throwable $e) {
            // Αποδεκτό και να πετά ελεγχόμενη εξαίρεση — όχι όμως fatal/TypeError
            $this->assertNotInstanceOf(\TypeError::class, $e);
            $this->assertNotInstanceOf(\Error::class, $e);
        }
    }
}

<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Drivejob\Services\AI\AIUsageGuard;
use PDO;

/**
 * Δοκιμές του AIUsageGuard — τα ημερήσια όρια που προστατεύουν το κόστος AI
 * (Πακέτο 6). Απαιτεί βάση drivejob με πίνακες ai_configuration/ai_usage_log.
 */
class AIUsageGuardTest extends TestCase
{
    private static ?PDO $pdo = null;

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
        // Καθαρό ημερολόγιο χρήσης για σημερινή ημέρα (μόνο test εγγραφές)
        self::$pdo->exec("DELETE FROM ai_usage_log WHERE purpose = 'phpunit'");
    }

    protected function tearDown(): void
    {
        if (self::$pdo) {
            self::$pdo->exec("DELETE FROM ai_usage_log WHERE purpose = 'phpunit'");
        }
    }

    /** Με ενεργό AI και καθαρό ημερολόγιο, το allow() επιτρέπει την κλήση. */
    public function testAllowsWhenUnderLimits(): void
    {
        $guard = new AIUsageGuard(self::$pdo);
        $this->assertTrue($guard->allow());
    }

    /** Το record() γράφει στο ai_usage_log με σωστό κόστος > 0. */
    public function testRecordWritesUsageLog(): void
    {
        $guard = new AIUsageGuard(self::$pdo);
        $guard->record('claude-haiku-4-5', [
            'usage' => ['prompt_tokens' => 1000, 'completion_tokens' => 500],
        ], 'phpunit');

        $row = self::$pdo->query(
            "SELECT * FROM ai_usage_log WHERE purpose = 'phpunit' ORDER BY id DESC LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($row, 'Δεν γράφτηκε εγγραφή στο ai_usage_log');
        $this->assertEquals(1000, (int) $row['prompt_tokens']);
        $this->assertEquals(500, (int) $row['completion_tokens']);
        $this->assertGreaterThan(0, (float) $row['est_cost_usd']);
    }

    /** Η σημερινή χρήση αθροίζει τις εγγραφές της ημέρας. */
    public function testTodayUsageAggregates(): void
    {
        $guard = new AIUsageGuard(self::$pdo);
        [$requestsBefore] = $guard->todayUsage();

        $guard->record('claude-haiku-4-5', [
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5],
        ], 'phpunit');

        [$requestsAfter, $costAfter] = $guard->todayUsage();
        $this->assertEquals($requestsBefore + 1, $requestsAfter);
        $this->assertGreaterThanOrEqual(0, $costAfter);
    }
}

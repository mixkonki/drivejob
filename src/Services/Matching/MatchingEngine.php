<?php

namespace Drivejob\Services\Matching;

use Drivejob\Core\Database;
use Drivejob\Core\Logger;
use PDO;

/**
 * MatchingEngine — ΤΟ ένα σημείο εισόδου για κάθε λειτουργία matching (Πακέτο 4).
 *
 * Οι callers (views, widgets, APIs, controllers, cron) μιλούν ΜΟΝΟ σε αυτή
 * την κλάση. Εσωτερικά δρομολογεί στις υπάρχουσες υλοποιήσεις:
 *
 *   - EnhancedMatchingService  → υβριδικό scoring (rule-based + AI σε CLI)
 *   - MatchingService          → κλασικό rule-based (λίστες οδηγού)
 *   - AIMatchingService        → προτάσεις οδηγών για εταιρείες
 *   - RealTimeMatchingService  → μαζική ανανέωση σκορ
 *
 * Η σταδιακή συγχώνευση των υλοποιήσεων σε strategies θα γίνει ΠΙΣΩ από
 * αυτό το facade, χωρίς να ξανααλλάξει κανένας caller.
 *
 * Έλεγχος κόστους AI: AIUsageGuard (ημερήσια όρια) + AI μόνο σε CLI.
 */
class MatchingEngine
{
    private PDO $pdo;

    private ?\Drivejob\Services\EnhancedMatchingService $enhanced = null;
    private ?\Drivejob\Services\MatchingService $ruleBased = null;
    private ?\Drivejob\Services\AIMatchingService $companyAI = null;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance()->getConnection();
    }

    /**
     * Σκορ ταιριάσματος οδηγού-αγγελίας (0-100).
     * Σε CLI περνά από AI (με όρια/καταγραφή), σε web από rule-based.
     */
    public function scoreDriverJob(int $driverId, int $jobId): float
    {
        try {
            return (float) $this->enhanced()->calculateMatchScore($driverId, $jobId);
        } catch (\Throwable $e) {
            Logger::error('MatchingEngine::scoreDriverJob: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Κορυφαία ταιριάσματα για οδηγό (διαβάζει αποθηκευμένα σκορ).
     */
    public function topMatchesForDriver(int $driverId, int $limit = 10): array
    {
        try {
            return $this->enhanced()->getTopMatchesForDriver($driverId, $limit);
        } catch (\Throwable $e) {
            Logger::error('MatchingEngine::topMatchesForDriver: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Σελιδοποιημένη λίστα ταιριασμάτων οδηγού (rule-based, για widgets).
     */
    public function driverMatches(int $driverId, int $page = 1, int $limit = 10): array
    {
        try {
            return $this->ruleBased()->findDriverMatches($driverId, $page, $limit);
        } catch (\Throwable $e) {
            Logger::error('MatchingEngine::driverMatches: ' . $e->getMessage());
            return ['matches' => [], 'total' => 0, 'page' => $page, 'pages' => 0];
        }
    }

    /**
     * Προτεινόμενοι οδηγοί για τις αγγελίες μιας εταιρείας.
     */
    public function companyDriverMatches(int $companyId, int $page = 1, int $limit = 20): array
    {
        try {
            return $this->companyAI()->findCompanyDriverMatches($companyId, $page, $limit);
        } catch (\Throwable $e) {
            Logger::error('MatchingEngine::companyDriverMatches: ' . $e->getMessage());
            return ['matches' => [], 'total' => 0, 'page' => $page, 'pages' => 0];
        }
    }

    /**
     * Μαζική ανανέωση σκορ οδηγού (καλείται από EventHooks/cron).
     */
    public function refreshDriverMatches(int $driverId): void
    {
        try {
            $service = new \Drivejob\Services\RealTimeMatchingService($this->pdo);
            $service->updateDriverMatches($driverId);
        } catch (\Throwable $e) {
            Logger::error('MatchingEngine::refreshDriverMatches: ' . $e->getMessage());
        }
    }

    // ---- lazy internals -------------------------------------------------

    private function enhanced(): \Drivejob\Services\EnhancedMatchingService
    {
        return $this->enhanced ??= new \Drivejob\Services\EnhancedMatchingService($this->pdo);
    }

    private function ruleBased(): \Drivejob\Services\MatchingService
    {
        return $this->ruleBased ??= new \Drivejob\Services\MatchingService($this->pdo);
    }

    private function companyAI(): \Drivejob\Services\AIMatchingService
    {
        return $this->companyAI ??= new \Drivejob\Services\AIMatchingService($this->pdo);
    }
}

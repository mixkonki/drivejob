<?php

namespace Drivejob\Services\AI;

use Drivejob\Core\Database;

/**
 * Service για caching των AI matching αποτελεσμάτων
 * Βελτιώνει την απόδοση και μειώνει τους υπολογισμούς
 */
class MatchingCacheService
{
    private $pdo;
    private $cacheTable = 'matching_cache';
    private $cacheExpiry = 3600; // 1 ώρα σε δευτερόλεπτα

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
        $this->ensureCacheTableExists();
    }

    /**
     * Δημιουργία του πίνακα cache αν δεν υπάρχει
     */
    private function ensureCacheTableExists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->cacheTable} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            cache_key VARCHAR(255) UNIQUE NOT NULL,
            cache_value JSON NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NOT NULL,
            INDEX idx_cache_key (cache_key),
            INDEX idx_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->pdo->exec($sql);
    }

    /**
     * Λήψη τιμής από το cache
     */
    public function get(string $key): ?array
    {
        // Καθαρισμός ληγμένων εγγραφών
        $this->cleanExpiredCache();

        $stmt = $this->pdo->prepare("
            SELECT cache_value 
            FROM {$this->cacheTable} 
            WHERE cache_key = ? 
            AND expires_at > NOW()
        ");

        $stmt->execute([$key]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($result) {
            return json_decode($result['cache_value'], true);
        }

        return null;
    }

    /**
     * Αποθήκευση τιμής στο cache
     */
    public function set(string $key, array $value, ?int $expiry = null): bool
    {
        $expiry = $expiry ?? $this->cacheExpiry;
        $expiresAt = date('Y-m-d H:i:s', time() + $expiry);

        $stmt = $this->pdo->prepare("
            INSERT INTO {$this->cacheTable} (cache_key, cache_value, expires_at)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                cache_value = VALUES(cache_value),
                expires_at = VALUES(expires_at),
                created_at = CURRENT_TIMESTAMP
        ");

        return $stmt->execute([
            $key,
            json_encode($value),
            $expiresAt
        ]);
    }

    /**
     * Διαγραφή τιμής από το cache
     */
    public function delete(string $key): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->cacheTable} WHERE cache_key = ?");
        return $stmt->execute([$key]);
    }

    /**
     * Διαγραφή όλων των cache entries για έναν οδηγό
     */
    public function invalidateDriverCache(int $driverId): bool
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM {$this->cacheTable} 
            WHERE cache_key LIKE ?
        ");

        return $stmt->execute(["driver_{$driverId}_%"]);
    }

    /**
     * Διαγραφή όλων των cache entries για μια αγγελία
     */
    public function invalidateJobCache(int $jobId): bool
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM {$this->cacheTable} 
            WHERE cache_key LIKE ?
        ");

        return $stmt->execute(["%_job_{$jobId}"]);
    }

    /**
     * Καθαρισμός ληγμένων cache entries
     */
    public function cleanExpiredCache(): int
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM {$this->cacheTable} 
            WHERE expires_at < NOW()
        ");

        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Δημιουργία cache key για driver matches
     */
    public function getDriverMatchesKey(int $driverId, int $limit): string
    {
        return "driver_{$driverId}_matches_{$limit}";
    }

    /**
     * Δημιουργία cache key για job candidates
     */
    public function getJobCandidatesKey(int $jobId, int $limit): string
    {
        return "job_{$jobId}_candidates_{$limit}";
    }

    /**
     * Δημιουργία cache key για specific match
     */
    public function getMatchKey(int $driverId, int $jobId): string
    {
        return "match_{$driverId}_{$jobId}";
    }

    /**
     * Λήψη στατιστικών του cache
     */
    public function getCacheStats(): array
    {
        $stats = [];

        // Συνολικές εγγραφές
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM {$this->cacheTable}");
        $stats['total_entries'] = $stmt->fetch(\PDO::FETCH_ASSOC)['total'];

        // Ενεργές εγγραφές
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as active 
            FROM {$this->cacheTable} 
            WHERE expires_at > NOW()
        ");
        $stats['active_entries'] = $stmt->fetch(\PDO::FETCH_ASSOC)['active'];

        // Ληγμένες εγγραφές
        $stats['expired_entries'] = $stats['total_entries'] - $stats['active_entries'];

        // Μέγεθος cache
        $stmt = $this->pdo->query("
            SELECT 
                SUM(LENGTH(cache_value)) as total_size,
                AVG(LENGTH(cache_value)) as avg_size
            FROM {$this->cacheTable}
        ");
        $sizeData = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stats['total_size_bytes'] = $sizeData['total_size'] ?? 0;
        $stats['avg_size_bytes'] = round($sizeData['avg_size'] ?? 0);

        // Hit rate (θα χρειαστεί logging για ακριβή μέτρηση)
        $stats['estimated_hit_rate'] = $this->estimateHitRate();

        return $stats;
    }

    /**
     * Εκτίμηση του hit rate βάσει της ηλικίας των entries
     */
    private function estimateHitRate(): float
    {
        $stmt = $this->pdo->query("
            SELECT 
                COUNT(CASE WHEN created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 1 END) as recent,
                COUNT(*) as total
            FROM {$this->cacheTable}
            WHERE expires_at > NOW()
        ");

        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($data['total'] == 0) {
            return 0;
        }

        // Εκτίμηση: αν πολλές εγγραφές είναι πρόσφατες, το hit rate είναι χαμηλό
        $recentRatio = $data['recent'] / $data['total'];
        return round((1 - $recentRatio) * 100, 2);
    }

    /**
     * Προθέρμανση του cache για έναν οδηγό
     */
    public function warmUpDriverCache(int $driverId, MatchingService $matchingService): void
    {
        // Cache top 10 matches
        $matches = $matchingService->getTopMatchesForDriver($driverId, 10);
        $this->set($this->getDriverMatchesKey($driverId, 10), $matches);

        // Cache top 5 matches (για widgets)
        $topMatches = array_slice($matches, 0, 5);
        $this->set($this->getDriverMatchesKey($driverId, 5), $topMatches);
    }

    /**
     * Προθέρμανση του cache για μια αγγελία
     */
    public function warmUpJobCache(int $jobId, MatchingService $matchingService): void
    {
        // Cache top 20 candidates
        $candidates = $matchingService->getTopCandidatesForJob($jobId, 20);
        $this->set($this->getJobCandidatesKey($jobId, 20), $candidates);
    }
}

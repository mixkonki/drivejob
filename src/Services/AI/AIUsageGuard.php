<?php

namespace Drivejob\Services\AI;

use Drivejob\Core\Logger;
use PDO;

/**
 * Φύλακας χρήσης & κόστους OpenAI (Πακέτο 4).
 *
 * Ένα σημείο ελέγχου για ΚΑΘΕ κλήση προς το OpenAI:
 *  - διακόπτης on/off (ai_enabled)
 *  - ημερήσιο όριο αιτημάτων (ai_daily_request_limit)
 *  - ημερήσιο όριο κόστους σε USD (ai_daily_cost_limit_usd)
 *  - καταγραφή κάθε κλήσης στον πίνακα ai_usage_log (tokens + εκτίμηση κόστους)
 *
 * Οι ρυθμίσεις διαβάζονται από τον πίνακα ai_configuration (config_key/config_value)
 * ώστε να αλλάζουν από το admin panel χωρίς deploy. Fail-open σε σφάλμα βάσης
 * ΜΟΝΟ για τον έλεγχο (για να μη ρίξει το matching), αλλά με warning στο log.
 *
 * Όταν το όριο εξαντληθεί, το allow() επιστρέφει false → ο caller πετάει exception
 * → η EnhancedMatchingService πέφτει αυτόματα στον rule-based αλγόριθμο.
 */
class AIUsageGuard
{
    private PDO $pdo;

    /** Προεπιλογές αν δεν υπάρχουν εγγραφές στο ai_configuration */
    private const DEFAULTS = [
        'ai_enabled' => true,
        'ai_daily_request_limit' => 200,
        'ai_daily_cost_limit_usd' => 5.0,
    ];

    /** Κόστος ανά 1K tokens (πρόχειρη εκτίμηση για guard — όχι τιμολόγηση) */
    private const COST_PER_1K = [
        'default' => ['input' => 0.005, 'output' => 0.015],
        'gpt-4o' => ['input' => 0.005, 'output' => 0.015],
        'gpt-4o-mini' => ['input' => 0.00015, 'output' => 0.0006],
        'o1-preview' => ['input' => 0.015, 'output' => 0.060],
        'o1-mini' => ['input' => 0.003, 'output' => 0.012],
        'claude-haiku-4-5' => ['input' => 0.001, 'output' => 0.005],
        'claude-sonnet-4-5' => ['input' => 0.003, 'output' => 0.015],
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Επιτρέπεται νέα κλήση OpenAI τώρα;
     */
    public function allow(): bool
    {
        try {
            if (!$this->boolSetting('ai_enabled')) {
                Logger::info('AIUsageGuard: το AI matching είναι απενεργοποιημένο (ai_enabled=false)');
                return false;
            }

            [$requests, $cost] = $this->todayUsage();

            $reqLimit = (int) $this->setting('ai_daily_request_limit');
            if ($reqLimit > 0 && $requests >= $reqLimit) {
                Logger::warning('AIUsageGuard: ημερήσιο όριο αιτημάτων εξαντλήθηκε', [
                    'requests' => $requests, 'limit' => $reqLimit,
                ]);
                return false;
            }

            $costLimit = (float) $this->setting('ai_daily_cost_limit_usd');
            if ($costLimit > 0 && $cost >= $costLimit) {
                Logger::warning('AIUsageGuard: ημερήσιο όριο κόστους εξαντλήθηκε', [
                    'cost_usd' => round($cost, 4), 'limit_usd' => $costLimit,
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Logger::warning('AIUsageGuard: σφάλμα ελέγχου, επιτρέπεται η κλήση (fail-open): ' . $e->getMessage());
            return true;
        }
    }

    /**
     * Καταγραφή ολοκληρωμένης κλήσης (από το usage της απάντησης του OpenAI).
     */
    public function record(string $model, array $apiResponse, string $purpose = 'matching'): void
    {
        try {
            $usage = $apiResponse['usage'] ?? [];
            $promptTokens = (int) ($usage['prompt_tokens'] ?? 0);
            $completionTokens = (int) ($usage['completion_tokens'] ?? 0);

            $rates = self::COST_PER_1K[$model] ?? self::COST_PER_1K['default'];
            $cost = ($promptTokens / 1000) * $rates['input'] + ($completionTokens / 1000) * $rates['output'];

            $st = $this->pdo->prepare("
                INSERT INTO ai_usage_log (used_on, model, purpose, prompt_tokens, completion_tokens, est_cost_usd)
                VALUES (CURDATE(), :model, :purpose, :pt, :ct, :cost)
            ");
            $st->execute([
                'model' => $model,
                'purpose' => $purpose,
                'pt' => $promptTokens,
                'ct' => $completionTokens,
                'cost' => round($cost, 6),
            ]);
        } catch (\Throwable $e) {
            Logger::warning('AIUsageGuard: αποτυχία καταγραφής χρήσης: ' . $e->getMessage());
        }
    }

    /**
     * Σημερινή χρήση: [πλήθος αιτημάτων, συνολικό εκτιμώμενο κόστος USD].
     */
    public function todayUsage(): array
    {
        $st = $this->pdo->query("
            SELECT COUNT(*) AS requests, COALESCE(SUM(est_cost_usd), 0) AS cost
            FROM ai_usage_log WHERE used_on = CURDATE()
        ");
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: ['requests' => 0, 'cost' => 0];
        return [(int) $row['requests'], (float) $row['cost']];
    }

    /**
     * Ανάγνωση ρύθμισης από ai_configuration με fallback στις προεπιλογές.
     */
    /** Δημόσια ανάγνωση ρύθμισης (π.χ. ai_provider) */
    public function getSetting(string $key, $default = null)
    {
        return $this->setting($key) ?? $default;
    }

    private function setting(string $key)
    {
        static $cache = null;
        if ($cache === null) {
            $cache = [];
            try {
                $st = $this->pdo->query("SELECT config_key, config_value FROM ai_configuration");
                foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $decoded = json_decode($row['config_value'], true);
                    $cache[$row['config_key']] = $decoded !== null ? $decoded : $row['config_value'];
                }
            } catch (\Throwable $e) {
                // ο πίνακας ίσως δεν υπάρχει — χρησιμοποιούμε προεπιλογές
            }
        }
        return $cache[$key] ?? self::DEFAULTS[$key] ?? null;
    }

    private function boolSetting(string $key): bool
    {
        $v = $this->setting($key);
        if (is_bool($v)) {
            return $v;
        }
        return in_array(strtolower((string) $v), ['1', 'true', 'yes', 'on'], true);
    }
}

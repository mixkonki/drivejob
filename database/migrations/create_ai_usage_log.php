<?php

/**
 * Migration: Πίνακας ai_usage_log + προεπιλογές ορίων στο ai_configuration
 *
 * Μέρος του Πακέτου 4 (Ενοποίηση Matching / έλεγχος κόστους OpenAI).
 * Εκτέλεση:  php database/migrations/create_ai_usage_log.php
 * Idempotent.
 */

$pdo = require __DIR__ . '/_bootstrap.php';

echo "🤖 Migration: AI usage guard\n\n";

// 1. Πίνακας καταγραφής χρήσης
$pdo->exec("
    CREATE TABLE IF NOT EXISTS ai_usage_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        used_on DATE NOT NULL,
        model VARCHAR(64) NOT NULL,
        purpose VARCHAR(32) NOT NULL DEFAULT 'matching',
        prompt_tokens INT NOT NULL DEFAULT 0,
        completion_tokens INT NOT NULL DEFAULT 0,
        est_cost_usd DECIMAL(10,6) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_used_on (used_on)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "  ✅ Πίνακας ai_usage_log έτοιμος.\n";

// 1β. Πίνακας μετρικών matching (τον περιμένουν τα admin KPIs & ο worker)
$pdo->exec("
    CREATE TABLE IF NOT EXISTS matching_metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        job_id INT NOT NULL,
        duration_ms INT NOT NULL DEFAULT 0,
        cache_hit TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "  ✅ Πίνακας matching_metrics έτοιμος.\n";

// 2. Προεπιλογές ορίων στο ai_configuration (αν δεν υπάρχουν ήδη)
$defaults = [
    'ai_enabled' => 'true',
    'ai_daily_request_limit' => '200',
    'ai_daily_cost_limit_usd' => '5.0',
    'ai_provider' => '"openai"',
    'anthropic_model' => '"claude-haiku-4-5"',
];

// Έλεγχος ότι υπάρχει ο πίνακας ai_configuration
$exists = $pdo->query("
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = 'drivejob' AND table_name = 'ai_configuration'
")->fetchColumn();

if ($exists) {
    $types = [
        'ai_enabled' => 'feature_flag',
        'ai_daily_request_limit' => 'rate_limit',
        'ai_daily_cost_limit_usd' => 'cost_limit',
        'ai_provider' => 'model_config',
        'anthropic_model' => 'model_config',
    ];
    foreach ($defaults as $key => $value) {
        $st = $pdo->prepare("SELECT COUNT(*) FROM ai_configuration WHERE config_key = ?");
        $st->execute([$key]);
        if ($st->fetchColumn() == 0) {
            $ins = $pdo->prepare("INSERT INTO ai_configuration (config_key, config_value, config_type) VALUES (?, ?, ?)");
            $ins->execute([$key, json_encode(json_decode($value) ?? $value), $types[$key] ?? 'feature_flag']);
            echo "  ✅ Ρύθμιση {$key} = {$value}\n";
        } else {
            echo "  ⏭️  Η ρύθμιση {$key} υπάρχει ήδη.\n";
        }
    }
} else {
    echo "  ⚠️  Ο πίνακας ai_configuration δεν βρέθηκε — ο guard θα χρησιμοποιεί τις ενσωματωμένες προεπιλογές (200 αιτήματα, \$5/ημέρα).\n";
}

echo "\n🟢 Ολοκληρώθηκε.\n";

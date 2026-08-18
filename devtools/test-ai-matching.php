<?php

/**
 * DevTool: Δοκιμή AI matching end-to-end (χωρίς browser)
 *
 * Χρήση:  php devtools/test-ai-matching.php [driver_id]
 *
 * 1. Δείχνει τις ενεργές ρυθμίσεις AI
 * 2. Καλεί απευθείας το matching για τον οδηγό (πυροδοτεί τον provider)
 * 3. Δείχνει τις εγγραφές του ai_usage_log
 */

if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

require_once __DIR__ . '/../src/bootstrap.php';

$driverId = (int) ($argv[1] ?? 26);
$pdo = new PDO('mysql:host=127.0.0.1;dbname=drivejob;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

echo "═══ 1. Ρυθμίσεις AI ═══\n";
try {
    $rows = $pdo->query("SELECT config_key, config_value FROM ai_configuration
                         WHERE config_key IN ('ai_enabled','ai_provider','anthropic_model','ai_daily_request_limit','ai_daily_cost_limit_usd')")->fetchAll();
    foreach ($rows as $r) {
        echo "  {$r['config_key']} = {$r['config_value']}\n";
    }
    if (!$rows) echo "  (καμία — χρησιμοποιούνται προεπιλογές)\n";
} catch (Throwable $e) {
    echo "  σφάλμα: {$e->getMessage()}\n";
}
echo "  ANTHROPIC_API_KEY: " . (empty($_ENV['ANTHROPIC_API_KEY']) ? '❌ ΚΕΝΟ' : '✅ ορισμένο (' . substr($_ENV['ANTHROPIC_API_KEY'], 0, 10) . '...)') . "\n\n";

echo "═══ 2. ΦΡΕΣΚΟΣ υπολογισμός matching για οδηγό #{$driverId} ═══\n";
try {
    // Παίρνουμε 2 ενεργές αγγελίες και υπολογίζουμε σκορ ΑΠΟ ΤΟ ΜΗΔΕΝ (πυροδοτεί το AI)
    $jobs = $pdo->query("SELECT id, title FROM job_listings WHERE is_active = 1 ORDER BY created_at DESC LIMIT 2")->fetchAll();
    $service = new \Drivejob\Services\EnhancedMatchingService();
    foreach ($jobs as $job) {
        $t0 = microtime(true);
        $score = $service->calculateMatchScore($driverId, (int) $job['id']);
        $ms = round((microtime(true) - $t0) * 1000);
        echo "  • {$job['title']} → score {$score} ({$ms}ms" . ($ms > 800 ? " — πέρασε από AI ✓" : " — τοπικός υπολογισμός") . ")\n";
    }
} catch (Throwable $e) {
    echo "  ❌ Σφάλμα: {$e->getMessage()}\n";
    echo "  " . str_replace("\n", "\n  ", $e->getTraceAsString()) . "\n";
}

echo "\n═══ 3. Καταγραφή ai_usage_log (τελευταίες 5) ═══\n";
try {
    $rows = $pdo->query("SELECT used_on, model, purpose, prompt_tokens, completion_tokens, est_cost_usd
                         FROM ai_usage_log ORDER BY id DESC LIMIT 5")->fetchAll();
    if (!$rows) {
        echo "  (κενό — καμία κλήση AI δεν ολοκληρώθηκε)\n";
    }
    foreach ($rows as $r) {
        echo "  {$r['used_on']} | {$r['model']} | in:{$r['prompt_tokens']} out:{$r['completion_tokens']} | \${$r['est_cost_usd']}\n";
    }
} catch (Throwable $e) {
    echo "  σφάλμα: {$e->getMessage()}\n";
}

echo "\n═══ 4. Τελευταία σχετικά logs ═══\n";
$log = ROOT_DIR . '/logs/app.log';
if (is_file($log)) {
    $lines = array_slice(file($log), -200);
    $relevant = array_values(array_filter($lines, fn($l) =>
        stripos($l, 'anthropic') !== false || stripos($l, 'openai') !== false
        || stripos($l, 'falling back') !== false || stripos($l, 'usage limit') !== false));
    foreach (array_slice($relevant, -5) as $l) {
        echo "  " . substr(trim($l), 0, 160) . "\n";
    }
    if (!$relevant) echo "  (κανένα σχετικό μήνυμα στις τελευταίες 200 γραμμές)\n";
}
echo "\n🏁 Τέλος.\n";

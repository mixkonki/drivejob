<?php

/**
 * Προέλεγχος διακομιστή πριν το deploy (Πακέτο 9).
 *
 * Χρήση στον server:  php devtools/preflight-check.php
 *
 * Ελέγχει ΟΛΕΣ τις προϋποθέσεις του DriveJob και λέει ακριβώς τι λείπει.
 * Δεν αλλάζει τίποτα — μόνο διαβάζει.
 */

$ok = 0;
$warn = 0;
$fail = 0;

function line(string $status, string $label, string $detail = ''): void
{
    global $ok, $warn, $fail;
    $icon = ['ok' => '✅', 'warn' => '⚠️ ', 'fail' => '❌'][$status];
    ${$status === 'ok' ? 'ok' : ($status === 'warn' ? 'warn' : 'fail')}++;
    printf("  %s %-34s %s\n", $icon, $label, $detail);
}

echo "\n🔍 DriveJob — Προέλεγχος διακομιστή\n";
echo str_repeat('─', 72), "\n\n";

// ── 1. PHP ─────────────────────────────────────────────────────────────
echo "PHP\n";
$v = PHP_VERSION;
version_compare($v, '8.3.0', '>=')
    ? line('ok', 'Έκδοση PHP', $v)
    : line('fail', 'Έκδοση PHP', "$v — απαιτείται 8.3+");

line(PHP_SAPI === 'cli' ? 'ok' : 'warn', 'SAPI', PHP_SAPI);

$required = ['pdo_mysql' => 'σύνδεση βάσης', 'mbstring' => 'ελληνικά', 'curl' => 'κλήσεις AI',
             'openssl' => 'HTTPS/email', 'json' => 'API', 'fileinfo' => 'έλεγχος αρχείων'];
$optional = ['gd' => 'PDF βιογραφικά', 'zip' => 'συμπίεση', 'intl' => 'τοπικοποίηση',
             'opcache' => 'ταχύτητα'];

foreach ($required as $ext => $why) {
    extension_loaded($ext)
        ? line('ok', "Επέκταση $ext", $why)
        : line('fail', "Επέκταση $ext", "ΛΕΙΠΕΙ — χρειάζεται για: $why");
}
foreach ($optional as $ext => $why) {
    extension_loaded($ext)
        ? line('ok', "Επέκταση $ext", $why)
        : line('warn', "Επέκταση $ext", "λείπει — επηρεάζει: $why");
}

// ── 2. Ρυθμίσεις PHP ───────────────────────────────────────────────────
echo "\nΡυθμίσεις PHP\n";
$toBytes = static function (string $s): int {
    $s = trim($s);
    if ($s === '' || $s === '-1') return PHP_INT_MAX;
    $unit = strtolower(substr($s, -1));
    $n = (int) $s;
    return match ($unit) { 'g' => $n * 1024 ** 3, 'm' => $n * 1024 ** 2, 'k' => $n * 1024, default => $n };
};
$mem = ini_get('memory_limit');
$toBytes($mem) >= 256 * 1024 ** 2
    ? line('ok', 'memory_limit', $mem)
    : line('warn', 'memory_limit', "$mem — συνιστάται ≥256M (composer)");

$up = ini_get('upload_max_filesize');
$toBytes($up) >= 8 * 1024 ** 2
    ? line('ok', 'upload_max_filesize', $up)
    : line('warn', 'upload_max_filesize', "$up — συνιστάται ≥10M (φωτό εγγράφων)");

$met = (int) ini_get('max_execution_time');
($met === 0 || $met >= 60)
    ? line('ok', 'max_execution_time', $met === 0 ? 'χωρίς όριο' : "{$met}s")
    : line('warn', 'max_execution_time', "{$met}s — συνιστάται ≥60");

// ── 3. Αρχεία & δικαιώματα ─────────────────────────────────────────────
echo "\nΑρχεία & δικαιώματα\n";
$root = dirname(__DIR__);
is_file($root . '/vendor/autoload.php')
    ? line('ok', 'vendor/ (composer install)', 'υπάρχει')
    : line('fail', 'vendor/', 'ΛΕΙΠΕΙ — τρέξε: composer install --no-dev');

if (is_file($root . '/.env')) {
    $perm = substr(sprintf('%o', fileperms($root . '/.env')), -3);
    $perm <= '640'
        ? line('ok', '.env δικαιώματα', $perm)
        : line('warn', '.env δικαιώματα', "$perm — συνιστάται 600");
} else {
    line('fail', '.env', 'ΛΕΙΠΕΙ — αντέγραψε από .env.example');
}

foreach (['storage/uploads', 'storage/backups', 'storage/queue/matching', 'logs'] as $dir) {
    $p = $root . '/' . $dir;
    if (!is_dir($p)) {
        line('fail', $dir, 'δεν υπάρχει — mkdir -p ' . $dir);
    } else {
        is_writable($p) ? line('ok', $dir, 'εγγράψιμο')
                        : line('fail', $dir, 'ΔΕΝ είναι εγγράψιμο — chmod 775');
    }
}

// ── 4. Ρυθμίσεις εφαρμογής ─────────────────────────────────────────────
echo "\nΡυθμίσεις εφαρμογής\n";
if (is_file($root . '/vendor/autoload.php')) {
    require_once $root . '/vendor/autoload.php';
    if (class_exists(\Dotenv\Dotenv::class) && is_file($root . '/.env')) {
        \Dotenv\Dotenv::createImmutable($root)->safeLoad();
    }

    $env = $_ENV['APP_ENV'] ?? '(δεν ορίστηκε)';
    $env === 'production'
        ? line('ok', 'APP_ENV', 'production')
        : line('warn', 'APP_ENV', "$env — σε παραγωγή πρέπει: production");

    $url = $_ENV['APP_URL'] ?? '';
    if ($url === '') {
        line('warn', 'APP_URL', 'δεν ορίστηκε — τα emails/cron θα βγάζουν λάθος links');
    } elseif (!str_starts_with($url, 'https://')) {
        line('warn', 'APP_URL', "$url — συνιστάται https://");
    } else {
        line('ok', 'APP_URL', $url);
    }

    empty($_ENV['ANTHROPIC_API_KEY'])
        ? line('warn', 'ANTHROPIC_API_KEY', 'κενό — το AI matching δεν θα λειτουργεί')
        : line('ok', 'ANTHROPIC_API_KEY', 'ορισμένο');

    empty($_ENV['SMTP_HOST'])
        ? line('warn', 'SMTP', 'κενό — δεν θα φεύγουν emails επαλήθευσης')
        : line('ok', 'SMTP', $_ENV['SMTP_HOST']);

    // ── 5. Βάση ────────────────────────────────────────────────────────
    echo "\nΒάση δεδομένων\n";
    try {
        $cfg = require $root . '/config/db.php';
        $dsn = "mysql:host={$cfg['host']};port=" . ($cfg['port'] ?? 3306) . ";dbname={$cfg['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        line('ok', 'Σύνδεση', "{$cfg['username']}@{$cfg['host']}/{$cfg['database']}");

        $ver = $pdo->query('SELECT VERSION()')->fetchColumn();
        line('ok', 'Έκδοση MySQL/MariaDB', $ver);

        $tables = (int) $pdo->query(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()"
        )->fetchColumn();
        $tables >= 60
            ? line('ok', 'Πίνακες', (string) $tables)
            : line('fail', 'Πίνακες', "$tables — αναμένονται ~68· έγινε import;");

        // Κρίσιμες στήλες (schema drift που μας πόνεσε)
        foreach ([['job_applications', 'job_listing_id'], ['drivers', 'terms_accepted_at'],
                  ['drivers', 'updated_at'], ['ai_usage_log', 'est_cost_usd']] as [$t, $c]) {
            $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns
                                 WHERE table_schema=DATABASE() AND table_name=? AND column_name=?");
            $st->execute([$t, $c]);
            $st->fetchColumn() > 0
                ? line('ok', "$t.$c", 'υπάρχει')
                : line('fail', "$t.$c", 'ΛΕΙΠΕΙ — τρέξε τα migrations');
        }
    } catch (Throwable $e) {
        line('fail', 'Σύνδεση βάσης', $e->getMessage());
    }
} else {
    echo "  (παραλείπονται — λείπει το vendor/)\n";
}

// ── Σύνοψη ─────────────────────────────────────────────────────────────
echo "\n", str_repeat('─', 72), "\n";
printf("Αποτέλεσμα:  ✅ %d εντάξει   ⚠️  %d προειδοποιήσεις   ❌ %d σφάλματα\n\n", $ok, $warn, $fail);

if ($fail > 0) {
    echo "🔴 ΜΗΝ κάνεις deploy πριν λυθούν τα ❌.\n\n";
    exit(1);
}
echo $warn > 0
    ? "🟡 Μπορείς να προχωρήσεις, αλλά δες τις προειδοποιήσεις.\n\n"
    : "🟢 Όλα έτοιμα για deploy.\n\n";
exit(0);

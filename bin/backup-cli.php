<?php

/**
 * Backup & επαναφορά βάσης — ΧΩΡΙΣ εξάρτηση από mysqldump. (05/10/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΚΑΘΑΡΟ PHP
 * ══════════════════════════════════════════════════════════════════════
 * Σε shared hosting (StackCP) το exec() είναι συνήθως απενεργοποιημένο
 * και το mysqldump δεν είναι εγγυημένο στο PATH — το scripts/backup-
 * database.sh δεν τρέχει εκεί. Αυτό το CLI διαβάζει τη βάση μέσω PDO και
 * γράφει κανονικό SQL (CREATE TABLE / INSERT / VIEW / TRIGGER / ROUTINE),
 * συμβατό με MySQL 8 και MariaDB. Τρέχει παντού όπου τρέχει η εφαρμογή.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΧΡΗΣΗ
 * ══════════════════════════════════════════════════════════════════════
 *   php bin/backup-cli.php                      → πλήρες backup (.sql.gz)
 *   php bin/backup-cli.php --schema-only        → μόνο δομή
 *   php bin/backup-cli.php --verify             → backup + δοκιμαστική
 *                                                 επαναφορά σε προσωρινή
 *                                                 βάση + σύγκριση πλήθους
 *                                                 πινάκων/γραμμών
 *   php bin/backup-cli.php --keep=14            → κρατά τα 14 πιο πρόσφατα
 *   php bin/backup-cli.php --out=/path/x.sql.gz → συγκεκριμένο αρχείο
 *   php bin/backup-cli.php --restore=FILE --into=DBNAME --yes
 *                                               → ΠΡΑΓΜΑΤΙΚΗ επαναφορά
 *                                                 (ποτέ στην τρέχουσα
 *                                                 βάση χωρίς --yes)
 *
 * Προεπιλογές: storage/backups/<db>_YYYYmmdd_HHMMSS.sql.gz, --keep=14.
 *
 * Ένα backup που δεν έχει επαναφερθεί ποτέ ΔΕΝ είναι backup — είναι
 * ελπίδα. Γι' αυτό υπάρχει το --verify: δημιουργεί <db>_verify_<ts>,
 * φορτώνει το αρχείο, μετρά, και τη διαγράφει. Αν ο χρήστης της βάσης
 * δεν έχει δικαίωμα CREATE DATABASE (συνηθισμένο σε shared hosting),
 * δώστε --verify-into=<υπάρχουσα άδεια βάση>.
 *
 * Έξοδος: 0 = επιτυχία, 1 = αποτυχία.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once __DIR__ . '/../src/bootstrap.php';

/*
 * Το bootstrap εγκαθιστά τον web ExceptionHandler, που σε CLI τυπώνει
 * ολόκληρη σελίδα 500 (και μετά σκάει δεύτερη φορά με «Headers already
 * sent»). Επαναφέρουμε τους default handlers ΚΑΙ αντικαθιστούμε τον
 * shutdown handler του, ώστε κάθε σφάλμα να βγαίνει ως μία καθαρή γραμμή.
 */
restore_exception_handler();
restore_error_handler();
set_exception_handler(static function (Throwable $e): void {
    $msg = $e->getMessage();
    if (str_contains($msg, 'Connection refused') || str_contains($msg, '2002')) {
        $cfg = require ROOT_DIR . '/config/db.php';
        $msg .= sprintf(
            "\n  Η βάση δεν απαντά στο %s:%s. Τρέχει η MySQL/MariaDB; (Herd/DBngin στο Mac: "
            . "ξεκίνησε την υπηρεσία ή διόρθωσε DB_HOST/DB_PORT στο .env)",
            $cfg['host'],
            $cfg['port']
        );
    }
    fwrite(STDERR, "ΣΦΑΛΜΑ: {$msg}\n");
    exit(1);
});
// Ο ExceptionHandler έχει register_shutdown_function που render-άρει HTML
// σε fatal· τον ακυρώνουμε: σε CLI αρκεί το μήνυμα της PHP.
register_shutdown_function(static function (): void {
    $err = error_get_last();
    if ($err !== null && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_clear_last();
    }
});

$opts = getopt('', [
    'schema-only', 'verify', 'verify-into:', 'keep:', 'out:',
    'restore:', 'into:', 'yes', 'quiet', 'help',
]);

if (isset($opts['help'])) {
    echo file_get_contents(__FILE__, false, null, 0, 2200), "\n";
    exit(0);
}

$quiet = isset($opts['quiet']);
$say = static function (string $m) use ($quiet): void {
    if (!$quiet) {
        fwrite(STDOUT, $m . "\n");
    }
};
$fail = static function (string $m): never {
    fwrite(STDERR, "ΣΦΑΛΜΑ: {$m}\n");
    exit(1);
};

$cfg = require ROOT_DIR . '/config/db.php';
$dbName = (string) $cfg['database'];

$connect = static function (?string $db) use ($cfg): PDO {
    $dsn = sprintf('mysql:host=%s;port=%s;charset=%s', $cfg['host'], $cfg['port'], $cfg['charset']);
    if ($db !== null) {
        $dsn .= ';dbname=' . $db;
    }
    $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
    ]);
    $pdo->exec("SET NAMES utf8mb4");
    $pdo->exec("SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");
    return $pdo;
};

// ──────────────────────────────────────────────────────────────────────
//  ΕΠΑΝΑΦΟΡΑ (--restore)
// ──────────────────────────────────────────────────────────────────────
if (isset($opts['restore'])) {
    $file = (string) $opts['restore'];
    $into = (string) ($opts['into'] ?? '');
    if ($into === '') {
        $fail('Δώστε --into=<βάση> (ποτέ δεν μαντεύουμε πού επαναφέρουμε).');
    }
    if (!is_file($file)) {
        $fail("Δεν βρέθηκε το αρχείο: {$file}");
    }
    if (!isset($opts['yes'])) {
        $fail("Η επαναφορά ΣΒΗΝΕΙ τους πίνακες της «{$into}». Επιβεβαιώστε με --yes.");
    }
    $pdo = $connect(null);
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $into) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $stats = dj_restore($pdo, $into, $file);
    $say(sprintf('Επαναφορά στη «%s»: %d εντολές, %d πίνακες.', $into, $stats['statements'], $stats['tables']));
    exit(0);
}

// ──────────────────────────────────────────────────────────────────────
//  BACKUP
// ──────────────────────────────────────────────────────────────────────
$schemaOnly = isset($opts['schema-only']);
$keep = max(1, (int) ($opts['keep'] ?? 14));
$dir = ROOT_DIR . '/storage/backups';
if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
    $fail("Δεν μπορώ να δημιουργήσω τον φάκελο {$dir}");
}
// Ο φάκελος δεν πρέπει ΠΟΤΕ να σερβίρεται από τον web server. Είναι έξω
// από το public/, αλλά βάζουμε και .htaccess για διπλή ασφάλεια.
if (!is_file($dir . '/.htaccess')) {
    file_put_contents($dir . '/.htaccess', "Require all denied\n");
}

$stamp = date('Ymd_His');
$out = (string) ($opts['out'] ?? "{$dir}/{$dbName}_{$stamp}" . ($schemaOnly ? '_schema' : '') . '.sql.gz');

$pdo = $connect($dbName);
$t0 = microtime(true);
$counts = dj_dump($pdo, $dbName, $out, $schemaOnly);
$size = filesize($out);
$say(sprintf(
    'Backup «%s»: %d πίνακες, %d γραμμές, %d views, %d triggers, %d routines → %s (%s, %.1fs)',
    $dbName,
    count($counts['tables']),
    array_sum($counts['tables']),
    $counts['views'],
    $counts['triggers'],
    $counts['routines'],
    basename($out),
    dj_human($size),
    microtime(true) - $t0
));

// ── Rotation ─────────────────────────────────────────────────────────
if (!isset($opts['out'])) {
    $all = glob("{$dir}/{$dbName}_*.sql.gz") ?: [];
    rsort($all);
    foreach (array_slice($all, $keep) as $old) {
        @unlink($old);
        $say('  διαγράφηκε παλιό: ' . basename($old));
    }
}

// ── Verify ───────────────────────────────────────────────────────────
if (isset($opts['verify']) || isset($opts['verify-into'])) {
    $tmp = (string) ($opts['verify-into'] ?? "{$dbName}_verify_{$stamp}");
    $created = false;
    try {
        $root = $connect(null);
        if (!isset($opts['verify-into'])) {
            $root->exec('CREATE DATABASE `' . $tmp . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $created = true;
        }
        $stats = dj_restore($root, $tmp, $out);
        $vpdo = $connect($tmp);
        $vcounts = dj_row_counts($vpdo);
        $diff = [];
        foreach ($counts['tables'] as $t => $n) {
            $vn = $vcounts[$t] ?? null;
            if ($vn === null) {
                $diff[] = "{$t}: λείπει";
            } elseif (!$schemaOnly && $vn !== $n) {
                $diff[] = "{$t}: {$n} → {$vn}";
            }
        }
        if ($diff) {
            fwrite(STDERR, "ΑΠΟΤΥΧΙΑ ΕΠΑΛΗΘΕΥΣΗΣ:\n  " . implode("\n  ", $diff) . "\n");
            $exit = 1;
        } else {
            $say(sprintf('Επαλήθευση OK: επαναφορά σε «%s», %d πίνακες, ίδιο πλήθος γραμμών.', $tmp, count($vcounts)));
            $exit = 0;
        }
    } catch (Throwable $e) {
        fwrite(STDERR, 'ΑΠΟΤΥΧΙΑ ΕΠΑΛΗΘΕΥΣΗΣ: ' . $e->getMessage() . "\n");
        if (str_contains($e->getMessage(), 'denied')) {
            fwrite(STDERR, "  Υπόδειξη: χωρίς δικαίωμα CREATE DATABASE δώστε --verify-into=<υπάρχουσα άδεια βάση>.\n");
        }
        $exit = 1;
    } finally {
        if ($created && isset($root)) {
            try {
                $root->exec('DROP DATABASE IF EXISTS `' . $tmp . '`');
            } catch (Throwable) {
            }
        } elseif (isset($opts['verify-into']) && isset($root)) {
            // Αδειάζουμε την παραχωρημένη βάση για να μη μείνουν δεδομένα.
            try {
                dj_drop_all($root, $tmp);
            } catch (Throwable) {
            }
        }
    }
    exit($exit);
}

exit(0);

// ══════════════════════════════════════════════════════════════════════
//  Συναρτήσεις
// ══════════════════════════════════════════════════════════════════════

/**
 * @return array{tables: array<string,int>, views:int, triggers:int, routines:int}
 */
function dj_dump(PDO $pdo, string $db, string $out, bool $schemaOnly): array
{
    $gz = gzopen($out, 'wb6');
    if ($gz === false) {
        throw new RuntimeException("Δεν μπορώ να γράψω το {$out}");
    }
    $w = static function (string $s) use ($gz): void {
        gzwrite($gz, $s);
    };

    $w("-- DriveJob backup\n-- Βάση: {$db}\n-- Ημερομηνία: " . date('c') . "\n-- Server: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n\n");
    $w("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET UNIQUE_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\nSET time_zone='+00:00';\n\n");

    $tables = [];
    $views = [];
    foreach ($pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as [$name, $type]) {
        if ($type === 'VIEW') {
            $views[] = $name;
        } else {
            $tables[] = $name;
        }
    }
    sort($tables);
    sort($views);

    $counts = ['tables' => [], 'views' => 0, 'triggers' => 0, 'routines' => 0];

    foreach ($tables as $t) {
        $create = $pdo->query("SHOW CREATE TABLE `{$t}`")->fetch(PDO::FETCH_NUM)[1];
        // Οι collations του MySQL 8 (utf8mb4_0900_*) δεν υπάρχουν στη MariaDB·
        // το backup πρέπει να επαναφέρεται και στα δύο.
        $create = preg_replace('/utf8mb4_0900_ai_ci/', 'utf8mb4_unicode_ci', $create);
        $create = preg_replace('/utf8mb4_0900_bin/', 'utf8mb4_bin', $create);
        $w("--\n-- Πίνακας {$t}\n--\nDROP TABLE IF EXISTS `{$t}`;\n{$create};\n\n");

        $n = 0;
        if (!$schemaOnly) {
            $cols = $pdo->query("SHOW COLUMNS FROM `{$t}`")->fetchAll();
            $colList = '`' . implode('`, `', array_column($cols, 'Field')) . '`';
            $binary = [];
            foreach ($cols as $c) {
                $binary[$c['Field']] = (bool) preg_match('/blob|binary|bit\(/i', $c['Type']);
            }
            $stmt = $pdo->query("SELECT * FROM `{$t}`");
            $buf = [];
            $bufLen = 0;
            while ($row = $stmt->fetch()) {
                $vals = [];
                foreach ($row as $k => $v) {
                    if ($v === null) {
                        $vals[] = 'NULL';
                    } elseif ($binary[$k]) {
                        $vals[] = '0x' . bin2hex((string) $v);
                    } else {
                        $vals[] = $pdo->quote((string) $v);
                    }
                }
                $tuple = '(' . implode(',', $vals) . ')';
                $buf[] = $tuple;
                $bufLen += strlen($tuple);
                $n++;
                if ($bufLen > 512 * 1024 || count($buf) >= 500) {
                    $w("INSERT INTO `{$t}` ({$colList}) VALUES\n" . implode(",\n", $buf) . ";\n");
                    $buf = [];
                    $bufLen = 0;
                }
            }
            if ($buf) {
                $w("INSERT INTO `{$t}` ({$colList}) VALUES\n" . implode(",\n", $buf) . ";\n");
            }
            $stmt->closeCursor();
            $w("\n");
        }
        $counts['tables'][$t] = $n;
    }

    // Views — μετά τους πίνακες, γιατί τους αναφέρουν. Αφαιρούμε το DEFINER
    // ώστε να επαναφέρονται με όποιον χρήστη κάνει την επαναφορά.
    foreach ($views as $v) {
        $create = $pdo->query("SHOW CREATE VIEW `{$v}`")->fetch(PDO::FETCH_NUM)[1];
        $create = preg_replace('/\sDEFINER=`[^`]*`@`[^`]*`/', '', $create);
        $w("--\n-- View {$v}\n--\nDROP VIEW IF EXISTS `{$v}`;\n{$create};\n\n");
        $counts['views']++;
    }

    // Triggers
    foreach ($pdo->query('SHOW TRIGGERS')->fetchAll() as $trg) {
        $create = $pdo->query("SHOW CREATE TRIGGER `{$trg['Trigger']}`")->fetch();
        $sql = preg_replace('/\sDEFINER=`[^`]*`@`[^`]*`/', '', (string) $create['SQL Original Statement']);
        $w("--\n-- Trigger {$trg['Trigger']}\n--\nDROP TRIGGER IF EXISTS `{$trg['Trigger']}`;\nDELIMITER ;;\n{$sql};;\nDELIMITER ;\n\n");
        $counts['triggers']++;
    }

    // Procedures & functions
    foreach (['PROCEDURE', 'FUNCTION'] as $kind) {
        $rows = $pdo->query("SHOW {$kind} STATUS WHERE Db = " . $pdo->quote($db))->fetchAll();
        foreach ($rows as $r) {
            $create = $pdo->query("SHOW CREATE {$kind} `{$r['Name']}`")->fetch();
            $body = $create['Create ' . ucfirst(strtolower($kind))] ?? null;
            if (!$body) {
                continue;
            }
            $body = preg_replace('/\sDEFINER=`[^`]*`@`[^`]*`/', '', $body);
            $w("--\n-- {$kind} {$r['Name']}\n--\nDROP {$kind} IF EXISTS `{$r['Name']}`;\nDELIMITER ;;\n{$body};;\nDELIMITER ;\n\n");
            $counts['routines']++;
        }
    }

    $w("SET FOREIGN_KEY_CHECKS=1;\nSET UNIQUE_CHECKS=1;\n-- Τέλος backup\n");
    gzclose($gz);
    return $counts;
}

/**
 * Φορτώνει ένα .sql ή .sql.gz σε βάση, εντολή-εντολή (με υποστήριξη
 * DELIMITER για triggers/routines).
 *
 * @return array{statements:int, tables:int}
 */
function dj_restore(PDO $pdo, string $db, string $file): array
{
    $pdo->exec('USE `' . str_replace('`', '', $db) . '`');
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    $fh = str_ends_with($file, '.gz') ? gzopen($file, 'rb') : fopen($file, 'rb');
    if ($fh === false) {
        throw new RuntimeException("Δεν ανοίγει το {$file}");
    }
    $isGz = str_ends_with($file, '.gz');
    $read = static fn() => $isGz ? gzgets($fh) : fgets($fh);

    $delimiter = ';';
    $buffer = '';
    $statements = 0;
    while (($line = $read()) !== false) {
        $trim = trim($line);
        if ($buffer === '' && ($trim === '' || str_starts_with($trim, '--'))) {
            continue;
        }
        if (preg_match('/^DELIMITER\s+(\S+)\s*$/i', $trim, $m)) {
            $delimiter = $m[1];
            continue;
        }
        $buffer .= $line;
        if (str_ends_with(rtrim($buffer), $delimiter)) {
            $sql = substr(rtrim($buffer), 0, -strlen($delimiter));
            $buffer = '';
            if (trim($sql) === '') {
                continue;
            }
            $pdo->exec($sql);
            $statements++;
        }
    }
    if (trim($buffer) !== '') {
        $pdo->exec($buffer);
        $statements++;
    }
    $isGz ? gzclose($fh) : fclose($fh);
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

    $tables = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = " . $pdo->quote($db) . " AND table_type = 'BASE TABLE'")->fetchColumn();
    return ['statements' => $statements, 'tables' => $tables];
}

/** @return array<string,int> */
function dj_row_counts(PDO $pdo): array
{
    $out = [];
    foreach ($pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM) as [$t]) {
        $out[$t] = (int) $pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
    }
    ksort($out);
    return $out;
}

function dj_drop_all(PDO $pdo, string $db): void
{
    $pdo->exec('USE `' . str_replace('`', '', $db) . '`');
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach ($pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as [$name, $type]) {
        $pdo->exec(($type === 'VIEW' ? 'DROP VIEW' : 'DROP TABLE') . " IF EXISTS `{$name}`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

function dj_human(int|false $bytes): string
{
    $bytes = (int) $bytes;
    foreach (['B', 'KB', 'MB', 'GB'] as $u) {
        if ($bytes < 1024) {
            return sprintf('%.1f %s', $bytes, $u);
        }
        $bytes /= 1024;
    }
    return sprintf('%.1f TB', $bytes);
}

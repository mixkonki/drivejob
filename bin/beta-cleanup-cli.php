<?php

/**
 * Εκκαθάριση δοκιμαστικών λογαριασμών πριν τη beta — Φάση Δ. (05/10/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ CLI ΚΑΙ ΟΧΙ AUTO MIGRATION
 * ══════════════════════════════════════════════════════════════════════
 * Ένα migration τρέχει στο επόμενο deploy, όποτε κι αν γίνει — και θα
 * έσβηνε τα δεδομένα με τα οποία ο Κώστας δοκιμάζει ακόμη. Η εκκαθάριση
 * είναι ΑΠΟΦΑΣΗ, όχι σχήμα: τρέχει μία φορά, όταν δοθεί το σύνθημα, από
 * άνθρωπο, με dry-run πρώτα και φρέσκο backup δίπλα.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΣΒΗΝΕΙ
 * ══════════════════════════════════════════════════════════════════════
 * Στόχοι (drivers + companies):
 *   • demo/seed:    email LIKE 'info+%@thessdrive.gr'          (πάντα)
 *   • σκουπίδια:    %@example.com, %@test.com, test@%, test-%@%, test_%@%,
 *                   temp_%@%, %fk_tester%, κενό email            (--include-garbage)
 *   • συγκεκριμένα: --ids=driver:28,company:3
 *   • ποτέ:         --keep=info@thessdrive.gr,kostas@…  (προστατεύονται ρητά)
 *
 * Cascade — ΣΧΗΜΑ-ΑΓΝΩΣΤΙΚΟ: διαβάζει το information_schema και σβήνει από
 * κάθε πίνακα που έχει στήλη driver_id / company_id / job_listing_id /
 * application_id / conversation_id / offer_id, συν τις ειδικές περιπτώσεις
 * (notifications με user_type, messages με sender/receiver, users, sessions).
 * Έτσι δουλεύει ίδια σε τοπική βάση και στην παραγωγή, ακόμη κι αν η μία
 * έχει πίνακες που η άλλη δεν έχει (job_offers, driver_references…).
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΧΡΗΣΗ
 * ══════════════════════════════════════════════════════════════════════
 *   php bin/beta-cleanup-cli.php                       → dry-run (τίποτα δεν σβήνει)
 *   php bin/beta-cleanup-cli.php --include-garbage     → dry-run + σκουπίδια
 *   php bin/beta-cleanup-cli.php --apply [--include-garbage]
 *       → ΠΡΑΓΜΑΤΙΚΗ διαγραφή. Απαιτεί backup της τελευταίας ώρας στο
 *         storage/backups (bin/backup-cli.php), αλλιώς αρνείται.
 *   --keep=a@b.gr,c@d.gr   --ids=driver:1,company:2   --skip-backup-check
 *
 * Όλα σε ΜΙΑ συναλλαγή: αν κάτι αποτύχει, δεν αλλάζει τίποτα.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once __DIR__ . '/../src/bootstrap.php';

restore_exception_handler();
restore_error_handler();
set_exception_handler(static function (Throwable $e): void {
    fwrite(STDERR, 'ΣΦΑΛΜΑ: ' . $e->getMessage() . "\n");
    exit(1);
});
register_shutdown_function(static function (): void {
    $err = error_get_last();
    if ($err !== null && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_clear_last();
    }
});

$opts = getopt('', ['apply', 'include-garbage', 'keep:', 'ids:', 'skip-backup-check', 'help']);
if (isset($opts['help'])) {
    echo file_get_contents(__FILE__, false, null, 0, 2600), "\n";
    exit(0);
}
$apply = isset($opts['apply']);
$garbage = isset($opts['include-garbage']);
$keep = array_filter(array_map('trim', explode(',', (string) ($opts['keep'] ?? ''))));

$cfg = require ROOT_DIR . '/config/db.php';
$db = (string) $cfg['database'];
$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$say = static fn(string $s) => fwrite(STDOUT, $s . "\n");
$col = static fn(string $sql, array $p = []) => (function () use ($pdo, $sql, $p) {
    $st = $pdo->prepare($sql);
    $st->execute($p);
    return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
})();
$in = static fn(array $ids) => implode(',', array_map('intval', $ids)) ?: '0';

// ── Σχήμα ────────────────────────────────────────────────────────────
$columns = [];
$st = $pdo->prepare("SELECT t.table_name, c.column_name FROM information_schema.tables t
                     JOIN information_schema.columns c ON c.table_schema = t.table_schema AND c.table_name = t.table_name
                     WHERE t.table_schema = ? AND t.table_type = 'BASE TABLE'");
$st->execute([$db]);
foreach ($st->fetchAll(PDO::FETCH_NUM) as [$t, $c]) {
    $columns[$t][] = $c;
}
$has = static fn(string $t, string $c) => isset($columns[$t]) && in_array($c, $columns[$t], true);

// ── 1. Στόχοι ────────────────────────────────────────────────────────
$patterns = ["info+%@thessdrive.gr"];
$garbagePatterns = ['%@example.com', '%@test.com', 'test@%', 'test-%@%', 'test\_%@%', 'temp\_%@%', '%fk\_tester%'];
if ($garbage) {
    $patterns = array_merge($patterns, $garbagePatterns);
}

$targets = ['driver' => [], 'company' => []];
foreach (['driver' => 'drivers', 'company' => 'companies'] as $kind => $table) {
    $where = implode(' OR ', array_fill(0, count($patterns), 'email LIKE ?'));
    $params = $patterns;
    if ($garbage) {
        $where .= " OR email IS NULL OR email = ''";
    }
    $sql = "SELECT id FROM {$table} WHERE ({$where})";
    if ($keep) {
        $sql .= ' AND email NOT IN (' . implode(',', array_fill(0, count($keep), '?')) . ')';
        $params = array_merge($params, $keep);
    }
    $targets[$kind] = $col($sql, $params);
}
foreach (array_filter(array_map('trim', explode(',', (string) ($opts['ids'] ?? '')))) as $spec) {
    [$kind, $id] = array_pad(explode(':', $spec, 2), 2, null);
    if (isset($targets[$kind]) && ctype_digit((string) $id)) {
        $targets[$kind][] = (int) $id;
    }
}
$D = array_values(array_unique($targets['driver']));
$C = array_values(array_unique($targets['company']));

if (!$D && !$C) {
    $say('Δεν βρέθηκαν δοκιμαστικοί λογαριασμοί. Τίποτα προς διαγραφή.');
    exit(0);
}

// Παράγωγα σύνολα
$U = array_merge(
    $has('drivers', 'user_id') ? $col('SELECT user_id FROM drivers WHERE user_id IS NOT NULL AND id IN (' . $in($D) . ')') : [],
    $has('companies', 'user_id') ? $col('SELECT user_id FROM companies WHERE user_id IS NOT NULL AND id IN (' . $in($C) . ')') : []
);
// Ορφανοί users δοκιμών (π.χ. ci_rbac_tester) — χωρίς driver/company και χωρίς ρόλο admin.
if ($garbage && isset($columns['users'])) {
    $gw = implode(' OR ', array_fill(0, count($garbagePatterns), 'u.email LIKE ?')) . " OR u.email LIKE '%ci\\_rbac%'";
    $sql = "SELECT u.id FROM users u WHERE ({$gw})
            AND u.id NOT IN (SELECT ur.user_id FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE r.name IN ('admin','super_admin'))";
    if ($has('drivers', 'user_id')) {
        $sql .= ' AND u.id NOT IN (SELECT user_id FROM drivers WHERE user_id IS NOT NULL AND id NOT IN (' . $in($D) . '))';
    }
    if ($has('companies', 'user_id')) {
        $sql .= ' AND u.id NOT IN (SELECT user_id FROM companies WHERE user_id IS NOT NULL AND id NOT IN (' . $in($C) . '))';
    }
    $U = array_values(array_unique(array_merge($U, $col($sql, $garbagePatterns))));
}
$L = $col('SELECT id FROM job_listings WHERE company_id IN (' . $in($C) . ')'
    . ($has('job_listings', 'driver_id') ? ' OR driver_id IN (' . $in($D) . ')' : ''));
$A = isset($columns['job_applications'])
    ? $col('SELECT id FROM job_applications WHERE driver_id IN (' . $in($D) . ') OR job_listing_id IN (' . $in($L) . ')')
    : [];
$O = isset($columns['job_offers'])
    ? $col('SELECT id FROM job_offers WHERE 1=0'
        . ($has('job_offers', 'driver_id') ? ' OR driver_id IN (' . $in($D) . ')' : '')
        . ($has('job_offers', 'company_id') ? ' OR company_id IN (' . $in($C) . ')' : '')
        . ($has('job_offers', 'job_listing_id') ? ' OR job_listing_id IN (' . $in($L) . ')' : ''))
    : [];
/*
 * Συνομιλίες: ΜΟΝΟ από τις στήλες company_id/driver_id. Τα participant*_type
 * σε παλιές γραμμές είναι λάθος (π.χ. οδηγός #26 καταγραμμένος ως 'company')
 * και θα έδιναν ψευδείς στόχους όταν συμπίπτουν ids οδηγού/εταιρίας.
 */
$CV = isset($columns['conversations'])
    ? $col('SELECT id FROM conversations WHERE driver_id IN (' . $in($D) . ') OR company_id IN (' . $in($C) . ')')
    : [];

$say(sprintf(
    "%s — βάση «%s»\nΣτόχοι: %d οδηγοί, %d εταιρίες, %d users, %d αγγελίες, %d αιτήσεις, %d προσφορές, %d συνομιλίες",
    $apply ? 'ΔΙΑΓΡΑΦΗ' : 'DRY-RUN (τίποτα δεν σβήνει)',
    $db,
    count($D),
    count($C),
    count($U),
    count($L),
    count($A),
    count($O),
    count($CV)
));
if ($garbage) {
    $say('  (περιλαμβάνονται σκουπίδια: ' . implode(', ', $garbagePatterns) . ', κενό email)');
}

// Λίστα ονομάτων για να ξέρει ο άνθρωπος τι σβήνει
foreach (['drivers' => [$D, "CONCAT(first_name,' ',last_name)"], 'companies' => [$C, 'company_name']] as $t => [$ids, $nameExpr]) {
    if (!$ids) {
        continue;
    }
    $rows = $pdo->query("SELECT id, {$nameExpr} AS n, email FROM {$t} WHERE id IN (" . $in($ids) . ') ORDER BY id')->fetchAll();
    $say("  {$t}:");
    foreach ($rows as $r) {
        $say(sprintf('    #%-4d %-32s %s', $r['id'], mb_strimwidth((string) $r['n'], 0, 32, '…'), $r['email'] ?: '(κενό email)'));
    }
}

// ── 2. Πλάνο διαγραφών (πίνακας → WHERE) ─────────────────────────────
$plan = [];
$main = ['drivers', 'companies', 'users', 'job_listings', 'job_applications', 'job_offers', 'conversations', 'messages', 'notifications'];
foreach ($columns as $t => $cols) {
    if (in_array($t, $main, true) || str_starts_with($t, 'dj_') || str_starts_with($t, 'lookup')) {
        continue;
    }
    $w = [];
    if (in_array('driver_id', $cols, true) && $D) {
        $w[] = 'driver_id IN (' . $in($D) . ')';
    }
    if (in_array('company_id', $cols, true) && $C) {
        $w[] = 'company_id IN (' . $in($C) . ')';
    }
    foreach (['job_listing_id', 'listing_id'] as $lc) {
        if (in_array($lc, $cols, true) && $L) {
            $w[] = "{$lc} IN (" . $in($L) . ')';
        }
    }
    if (in_array('application_id', $cols, true) && $A) {
        $w[] = 'application_id IN (' . $in($A) . ')';
    }
    if (in_array('offer_id', $cols, true) && $O) {
        $w[] = 'offer_id IN (' . $in($O) . ')';
    }
    if (in_array('conversation_id', $cols, true) && $CV) {
        $w[] = 'conversation_id IN (' . $in($CV) . ')';
    }
    // user_id: αν ο πίνακας έχει user_type, αναφέρεται σε drivers/companies· αλλιώς στον users.
    if (in_array('user_id', $cols, true)) {
        if (in_array('user_type', $cols, true)) {
            $w[] = "(user_type='driver' AND user_id IN (" . $in($D) . ")) OR (user_type='company' AND user_id IN (" . $in($C) . '))';
        } elseif ($U && !in_array($t, ['admins', 'admin_sessions', 'admin_activity_logs'], true)) {
            $w[] = 'user_id IN (' . $in($U) . ')';
        }
    }
    if ($w) {
        $plan[$t] = implode(' OR ', $w);
    }
}
// Ειδικές περιπτώσεις με σαφή σειρά (παιδιά πριν τους γονείς)
if (isset($columns['messages'])) {
    // Τα μηνύματα ανήκουν σε συνομιλία — αρκεί αυτή (τα sender/receiver_type είναι αναξιόπιστα σε παλιές γραμμές).
    $plan['messages'] = 'conversation_id IN (' . $in($CV) . ')';
}
if (isset($columns['notifications'])) {
    $plan['notifications'] = "(user_type='driver' AND user_id IN (" . $in($D) . ")) OR (user_type='company' AND user_id IN (" . $in($C) . '))';
}
if (isset($columns['conversations'])) {
    $plan['conversations'] = 'id IN (' . $in($CV) . ')';
}
if (isset($columns['job_offers'])) {
    $plan['job_offers'] = 'id IN (' . $in($O) . ')';
}
if (isset($columns['job_applications'])) {
    $plan['job_applications'] = 'id IN (' . $in($A) . ')';
}
$plan['job_listings'] = 'id IN (' . $in($L) . ')';
$plan['drivers'] = 'id IN (' . $in($D) . ')';
$plan['companies'] = 'id IN (' . $in($C) . ')';
if (isset($columns['users'])) {
    $plan['users'] = 'id IN (' . $in($U) . ')';
}

// Μέτρηση
$say("\nΠλάνο (πίνακας → γραμμές):");
$total = 0;
$counts = [];
foreach ($plan as $t => $where) {
    $n = (int) $pdo->query("SELECT COUNT(*) FROM `{$t}` WHERE {$where}")->fetchColumn();
    $counts[$t] = $n;
    if ($n > 0) {
        $say(sprintf('  %-40s %6d', $t, $n));
    }
    $total += $n;
}
$say(sprintf('  %-40s %6d', 'ΣΥΝΟΛΟ', $total));

// Αρχεία (avatar/logo) που θα μείνουν ορφανά
$files = [];
foreach ([['drivers', 'profile_image', $D], ['companies', 'company_logo', $C]] as [$t, $c, $ids]) {
    if ($has($t, $c) && $ids) {
        $st = $pdo->query("SELECT {$c} FROM {$t} WHERE {$c} IS NOT NULL AND {$c} <> '' AND id IN (" . $in($ids) . ')');
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $rel) {
            foreach ([ROOT_DIR . '/public/' . ltrim((string) $rel, '/'), ROOT_DIR . '/storage/' . ltrim((string) $rel, '/')] as $abs) {
                if (is_file($abs)) {
                    $files[] = $abs;
                }
            }
        }
    }
}
if ($files) {
    $say(sprintf("Αρχεία προς διαγραφή (avatar/logo): %d", count($files)));
}

if (!$apply) {
    $say("\nDry-run ολοκληρώθηκε. Για πραγματική διαγραφή: πρώτα `php bin/backup-cli.php --verify`, μετά ξανά με --apply.");
    exit(0);
}

// ── 3. Δικλείδα: φρέσκο backup ───────────────────────────────────────
if (!isset($opts['skip-backup-check'])) {
    $recent = array_filter(glob(ROOT_DIR . "/storage/backups/{$db}_*.sql.gz") ?: [], static fn($f) => filemtime($f) > time() - 3600);
    if (!$recent) {
        fwrite(STDERR, "ΑΡΝΗΣΗ: δεν βρέθηκε backup της τελευταίας ώρας στο storage/backups/.\n  Τρέξε πρώτα: php bin/backup-cli.php --verify\n  (ή --skip-backup-check αν ξέρεις τι κάνεις)\n");
        exit(1);
    }
    $say('Backup βρέθηκε: ' . basename((string) max($recent)));
}

// ── 4. Εκτέλεση σε συναλλαγή ─────────────────────────────────────────
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
$pdo->beginTransaction();
try {
    $deleted = 0;
    foreach ($plan as $t => $where) {
        if (($counts[$t] ?? 0) === 0) {
            continue;
        }
        $deleted += $pdo->exec("DELETE FROM `{$t}` WHERE {$where}");
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    throw $e;
}
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

$removed = 0;
foreach ($files as $f) {
    if (@unlink($f)) {
        $removed++;
    }
}

$say(sprintf("\nΟΛΟΚΛΗΡΩΘΗΚΕ: %d γραμμές διαγράφηκαν, %d αρχεία.", $deleted, $removed));
$say('Υπόλοιπο: ' . (int) $pdo->query('SELECT COUNT(*) FROM drivers')->fetchColumn() . ' οδηγοί, '
    . (int) $pdo->query('SELECT COUNT(*) FROM companies')->fetchColumn() . ' εταιρίες, '
    . (int) $pdo->query('SELECT COUNT(*) FROM job_listings')->fetchColumn() . ' αγγελίες.');
exit(0);

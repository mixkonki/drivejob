<?php

/**
 * Διαχείριση λογαριασμών διαχειριστή (admin) από το terminal. (05/10/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΠΩΣ ΣΥΝΔΕΕΤΑΙ ΕΝΑΣ ADMIN — ΓΙΑ ΝΑ ΜΗΝ ΤΟ ΞΑΝΑΨΑΞΟΥΜΕ
 * ══════════════════════════════════════════════════════════════════════
 * Ο admin ΔΕΝ ζει στον πίνακα `admins` (παλιό, αχρησιμοποίητο κατάλοιπο).
 * Ζει στον πίνακα `users` με ρόλο `admin` στο RBAC (`user_roles` → `roles`).
 * Συνδέεται από το κοινό /auth/login· ο UserAuthenticator ρωτά ΠΡΩΤΑ το
 * RBAC και στέλνει τον ρόλο 'admin' στο session → /admin/dashboard.
 *
 * Μέχρι σήμερα ο μόνος τρόπος να φτιαχτεί admin ήταν χειροκίνητο SQL ή τα
 * scripts create_admin_user.php / reset_admin_password.php με σκληρο-
 * κωδικοποιημένο συνθηματικό. Αυτό το CLI τα αντικαθιστά.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΧΡΗΣΗ
 * ══════════════════════════════════════════════════════════════════════
 *   php bin/admin-cli.php list
 *   php bin/admin-cli.php create --email=kostas@thessdrive.gr [--password=…]
 *   php bin/admin-cli.php reset-password --email=… [--password=…]
 *   php bin/admin-cli.php disable --email=…
 *   php bin/admin-cli.php enable  --email=…
 *   php bin/admin-cli.php demote  --email=…      (αφαιρεί ΜΟΝΟ τον ρόλο admin)
 *
 * Χωρίς --password δημιουργείται τυχαίο ισχυρό συνθηματικό (16 χαρακτήρες)
 * και τυπώνεται ΜΙΑ φορά. Δεν αποθηκεύεται πουθενά σε καθαρή μορφή.
 * Ένα --password κάτω από 12 χαρακτήρες απορρίπτεται.
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

// Το getopt() σταματά στο πρώτο μη-option όρισμα («create»), οπότε τα
// --email/--password μετά την εντολή θα χάνονταν. Αναλύουμε μόνοι μας.
$command = 'list';
$opts = [];
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/s', $arg, $m)) {
        $opts[$m[1]] = $m[2] ?? true;
    } else {
        $command = $arg;
    }
}

if (isset($opts['help']) || $command === 'help') {
    echo file_get_contents(__FILE__, false, null, 0, 1900), "\n";
    exit(0);
}

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$say = static fn(string $s) => fwrite(STDOUT, $s . "\n");
$fail = static function (string $m): never {
    fwrite(STDERR, "ΣΦΑΛΜΑ: {$m}\n");
    exit(1);
};

// Ο ρόλος admin στο RBAC — πρέπει να υπάρχει (τον φτιάχνει το RBAC bootstrap).
$adminRoleId = (int) $pdo->query("SELECT id FROM roles WHERE name = 'admin' LIMIT 1")->fetchColumn();
if ($adminRoleId === 0) {
    $fail("Δεν υπάρχει ρόλος 'admin' στον πίνακα roles. Τρέξε πρώτα τα RBAC migrations (sql/migrations).");
}

$email = strtolower(trim((string) ($opts['email'] ?? '')));
$needEmail = static function () use ($email, $fail): void {
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fail('Δώσε έγκυρο --email=…');
    }
};

$findUser = static function (string $email) use ($pdo): array|false {
    $st = $pdo->prepare('SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1');
    $st->execute([$email, $email]);
    return $st->fetch(PDO::FETCH_ASSOC);
};

$passwordFor = static function () use ($opts, $fail): array {
    if (isset($opts['password'])) {
        $p = (string) $opts['password'];
        if (mb_strlen($p) < 12) {
            $fail('Το συνθηματικό πρέπει να έχει τουλάχιστον 12 χαρακτήρες.');
        }
        return [$p, false];
    }
    // Τυχαίο, χωρίς διφορούμενους χαρακτήρες (0/O, 1/l/I).
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#$%&*';
    $p = '';
    for ($i = 0; $i < 16; $i++) {
        $p .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
    return [$p, true];
};

switch ($command) {
    case 'list':
        $rows = $pdo->query(
            "SELECT u.id, u.email, u.is_active, u.last_login, u.created_at,
                    GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ',') AS roles
             FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id
             WHERE u.id IN (SELECT user_id FROM user_roles WHERE role_id = {$adminRoleId})
             GROUP BY u.id, u.email, u.is_active, u.last_login, u.created_at
             ORDER BY u.id"
        )->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            $say('Κανένας λογαριασμός admin.');
            break;
        }
        $say(sprintf('%-4s %-36s %-8s %-19s %s', 'id', 'email', 'ενεργός', 'τελευταία σύνδεση', 'ρόλοι'));
        foreach ($rows as $r) {
            $say(sprintf(
                '%-4d %-36s %-8s %-19s %s',
                $r['id'],
                $r['email'],
                $r['is_active'] ? 'ναι' : 'ΟΧΙ',
                $r['last_login'] ?: '—',
                $r['roles']
            ));
        }
        break;

    case 'create':
        $needEmail();
        if ($findUser($email)) {
            $fail("Υπάρχει ήδη χρήστης με email {$email}. Για ρόλο admin σε υπάρχοντα: reset-password ή promote χειροκίνητα.");
        }
        [$plain, $generated] = $passwordFor();
        $hash = password_hash($plain, PASSWORD_DEFAULT);

        // Μόνο στήλες που υπάρχουν σε κάθε γνωστό σχήμα του users.
        $cols = array_column($pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_ASSOC), 'Field');
        $data = ['username' => $email, 'email' => $email, 'password' => $hash];
        foreach (['is_verified' => 1, 'is_active' => 1, 'login_attempts' => 0] as $c => $v) {
            if (in_array($c, $cols, true)) {
                $data[$c] = $v;
            }
        }
        if (in_array('user_type', $cols, true)) {
            $data['user_type'] = 'admin';
        }
        $pdo->beginTransaction();
        $st = $pdo->prepare(
            'INSERT INTO users (`' . implode('`,`', array_keys($data)) . '`) VALUES (' . rtrim(str_repeat('?,', count($data)), ',') . ')'
        );
        $st->execute(array_values($data));
        $userId = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO user_roles (user_id, role_id, is_primary) VALUES (?, ?, 1)')->execute([$userId, $adminRoleId]);
        $pdo->commit();

        $say("Δημιουργήθηκε admin #{$userId}: {$email}");
        if ($generated) {
            $say("Συνθηματικό (τυπώνεται ΜΙΑ φορά — αποθήκευσέ το σε password manager):\n\n    {$plain}\n");
        }
        $say('Σύνδεση: ' . BASE_URL . 'auth/login → μετά /admin/dashboard');
        break;

    case 'reset-password':
        $needEmail();
        $u = $findUser($email) ?: $fail("Δεν βρέθηκε χρήστης {$email}.");
        [$plain, $generated] = $passwordFor();
        $sets = 'password = ?';
        $cols = array_column($pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_ASSOC), 'Field');
        foreach (['login_attempts' => '0', 'locked_until' => 'NULL'] as $c => $v) {
            if (in_array($c, $cols, true)) {
                $sets .= ", {$c} = {$v}";
            }
        }
        $pdo->prepare("UPDATE users SET {$sets} WHERE id = ?")->execute([password_hash($plain, PASSWORD_DEFAULT), $u['id']]);
        $say("Νέο συνθηματικό για {$email} (#{$u['id']}).");
        if ($generated) {
            $say("\n    {$plain}\n");
        }
        break;

    case 'disable':
    case 'enable':
        $needEmail();
        $u = $findUser($email) ?: $fail("Δεν βρέθηκε χρήστης {$email}.");
        $pdo->prepare('UPDATE users SET is_active = ? WHERE id = ?')->execute([$command === 'enable' ? 1 : 0, $u['id']]);
        $say(($command === 'enable' ? 'Ενεργοποιήθηκε' : 'Απενεργοποιήθηκε') . " ο {$email}.");
        break;

    case 'demote':
        $needEmail();
        $u = $findUser($email) ?: $fail("Δεν βρέθηκε χρήστης {$email}.");
        $remaining = (int) $pdo->query("SELECT COUNT(*) FROM user_roles WHERE role_id = {$adminRoleId}")->fetchColumn();
        if ($remaining <= 1) {
            $fail('Δεν αφαιρώ τον τελευταίο admin — θα κλειδωνόσουν απ\' έξω. Φτιάξε πρώτα άλλον.');
        }
        $pdo->prepare('DELETE FROM user_roles WHERE user_id = ? AND role_id = ?')->execute([$u['id'], $adminRoleId]);
        $say("Αφαιρέθηκε ο ρόλος admin από τον {$email}. Ο λογαριασμός παραμένει.");
        break;

    default:
        $fail("Άγνωστη εντολή «{$command}». Δες: php bin/admin-cli.php --help");
}

exit(0);

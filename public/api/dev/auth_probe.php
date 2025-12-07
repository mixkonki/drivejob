<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../config/dev.php';
if (!defined('DEV_MODE') || DEV_MODE !== true) {
    http_response_code(404);
    exit;
}
/**
 * GET params:
 *   e=email (default admin@drivejob.gr)
 *   p=password (default admin123)
 * Επιστρέφει JSON με:
 *   - ποια στήλη password χρησιμοποιείται
 *   - flags (active/email_verified/is_active αν υπάρχουν)
 *   - άθικτο result password_verify
 *   - query/trace για να συγκρίνουμε με UI login
 *
 * ΣΚΟΠΟΣ: Να αποδείξει ότι η DB/συνάρτηση είναι οκ και αν το UI χρησιμοποιεί άλλο κώδικα/στήλη/DB.
 */
@ini_set('display_errors', '0');
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../../../src/RBAC/DB.php";

use DriveJob\RBAC\DB;

$pdo = DB::pdo();
$email = isset($_GET['e']) ? (string)$_GET['e'] : 'admin@drivejob.gr';
$pass  = isset($_GET['p']) ? (string)$_GET['p'] : 'admin123';

$out = ['email' => $email];

try {
    // Επιλογή σωστής στήλης κωδικού
    $col = 'password';
    $row = $pdo->query("SHOW COLUMNS FROM users LIKE 'password'")->fetch();
    if (!$row) {
        $row = $pdo->query("SHOW COLUMNS FROM users LIKE 'pass'")->fetch();
        if ($row) $col = 'pass';
    }
    $out['password_column'] = $col;

    // Μαζεύουμε πιθανές σημαίες
    $cols = ["id", "email", "username", "{$col} AS password"];
    foreach (["active", "email_verified", "is_active"] as $maybe) {
        $chk = $pdo->query("SHOW COLUMNS FROM users LIKE '{$maybe}'")->fetch();
        if ($chk) $cols[] = $maybe;
    }
    $sql = "SELECT " . implode(",", $cols) . " FROM users WHERE email=:e LIMIT 1";
    $st  = $pdo->prepare($sql);
    $st->execute([":e" => $email]);
    $u   = $st->fetch(PDO::FETCH_ASSOC);

    if (!$u) {
        echo json_encode(["ok" => false, "reason" => "no_user", "probe" => $out], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $out["user_id"] = (int)$u["id"];
    $out["flags"]   = [
        "active"         => array_key_exists("active", $u) ? (int)$u["active"] : null,
        "email_verified" => array_key_exists("email_verified", $u) ? (int)$u["email_verified"] : null,
        "is_active"      => array_key_exists("is_active", $u) ? (int)$u["is_active"] : null,
    ];

    $hash = (string)$u["password"];
    $out["hash_info"] = password_get_info($hash);
    $out["verify"]    = password_verify($pass, $hash);

    echo json_encode(["ok" => true, "auth_probe" => $out, "sql_used" => $sql], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage(), "trace" => $out], JSON_UNESCAPED_UNICODE);
}

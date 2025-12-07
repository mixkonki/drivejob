<?php

declare(strict_types=1);

require_once __DIR__ . "/../../src/RBAC/DB.php";

use DriveJob\RBAC\DB;

header("Content-Type: application/json; charset=utf-8");

$pdo   = DB::pdo();
$email = "admin@drivejob.gr";
$pass  = "admin123";          // προσοχή: απλό space, ΟΧΙ non-breaking

$out = ["email" => $email];

try {
    // Διάλεξε σωστή στήλη κωδικού (password ή pass)
    $col = "password";
    $row = $pdo->query("SHOW COLUMNS FROM users LIKE 'password'")->fetch();
    if (!$row) {
        $row = $pdo->query("SHOW COLUMNS FROM users LIKE 'pass'")->fetch();
        if ($row) $col = "pass";
    }

    // Φέρε και πιθανά flags, αν υπάρχουν
    $cols = ["id", "email", "username", "{$col} AS password"];
    foreach (["active", "email_verified", "is_active"] as $maybe) {
        $chk = $pdo->query("SHOW COLUMNS FROM users LIKE '{$maybe}'")->fetch();
        if ($chk) $cols[] = $maybe;
    }
    $sql = "SELECT " . implode(",", $cols) . " FROM users WHERE email=:e LIMIT 1";
    $st  = $pdo->prepare($sql);
    $st->execute([":e" => $email]);
    $u   = $st->fetch(\PDO::FETCH_ASSOC);

    if (!$u) {
        echo json_encode(["ok" => false, "reason" => "no_user"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $out["user_id"] = (int)$u["id"];
    $out["column"]  = $col;
    $out["flags"]   = [
        "active"         => array_key_exists("active", $u) ? (int)$u["active"] : null,
        "email_verified" => array_key_exists("email_verified", $u) ? (int)$u["email_verified"] : null,
        "is_active"      => array_key_exists("is_active", $u) ? (int)$u["is_active"] : null,
    ];

    $hash       = (string)$u["password"];
    $out["algo"] = password_get_info($hash);       // name=bcrypt?
    $out["verify"] = password_verify($pass, $hash);

    echo json_encode(["ok" => true, "diag" => $out], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

<?php

declare(strict_types=1);

require_once __DIR__ . "/../../src/RBAC/DB.php";

use DriveJob\RBAC\DB;

header("Content-Type: application/json; charset=utf-8");

$pdo   = DB::pdo();
$email = "admin@drivejob.gr";
$new   = "admin123";

try {
    // Επιλογή σωστής στήλης
    $col = "password";
    $row = $pdo->query("SHOW COLUMNS FROM users LIKE 'password'")->fetch();
    if (!$row) {
        $row = $pdo->query("SHOW COLUMNS FROM users LIKE 'pass'")->fetch();
        if ($row) $col = "pass";
    }

    // Βρες τον χρήστη
    $st = $pdo->prepare("SELECT id, {$col} AS password FROM users WHERE email=:e LIMIT 1");
    $st->execute([":e" => $email]);
    $u = $st->fetch(\PDO::FETCH_ASSOC);
    if (!$u) {
        echo json_encode(["ok" => false, "reason" => "no_user"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Αν ήδη είναι ΟΚ ο κωδικός, μην τον αλλάξεις
    $already = password_verify($new, (string)$u["password"]);

    if ($already) {
        echo json_encode(["ok" => true, "updated" => 0, "note" => "already valid bcrypt for admin123"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Κάνε reset σε bcrypt
    $hash = password_hash($new, PASSWORD_BCRYPT);
    $upd  = $pdo->prepare("UPDATE users SET {$col}=:h WHERE id=:id LIMIT 1");
    $upd->execute([":h" => $hash, ":id" => (int)$u["id"]]);

    echo json_encode(["ok" => true, "updated" => $upd->rowCount(), "column" => $col], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    echo json_encode(["ok" => false, "error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

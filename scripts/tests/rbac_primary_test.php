<?php
require_once __DIR__ . "/../../src/RBAC/DB.php";
require_once __DIR__ . "/../../src/RBAC/RBAC.php";

use DriveJob\RBAC\DB;
use DriveJob\RBAC\RBAC;

// Start TX (θα γίνει rollback)
$pdo = DB::pdo();
$pdo->beginTransaction();

try {
    // Create temp user
    $pdo->exec("INSERT INTO users (username,email,password) VALUES ('rbac_cli_test','rbac_cli_test@drivejob.gr','x')");
    $uid = (int)$pdo->lastInsertId();

    // Role ids
    $ridDriver   = (int)$pdo->query("SELECT id FROM roles WHERE name='driver'")->fetchColumn();
    $ridEmployer = (int)$pdo->query("SELECT id FROM roles WHERE name='employer'")->fetchColumn();

    // Give both roles
    $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id, is_primary) VALUES (:u,:r,0)");
    $stmt->execute([':u' => $uid, ':r' => $ridDriver]);
    $stmt->execute([':u' => $uid, ':r' => $ridEmployer]);

    // Switch primary
    RBAC::setPrimaryRole($uid, $ridEmployer);

    // Assert
    $st = $pdo->prepare("SELECT role_id, is_primary FROM user_roles WHERE user_id=:u ORDER BY role_id");
    $st->execute([':u' => $uid]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $ok = false;
    foreach ($rows as $row) {
        if ((int)$row['role_id'] === $ridEmployer && (int)$row['is_primary'] === 1) $ok = true;
        if ((int)$row['role_id'] === $ridDriver   && (int)$row['is_primary'] === 1) $ok = false;
    }

    echo json_encode(['test' => 'primary_switch', 'ok' => $ok, 'rows' => $rows], JSON_UNESCAPED_UNICODE);
} finally {
    $pdo->rollBack();
}

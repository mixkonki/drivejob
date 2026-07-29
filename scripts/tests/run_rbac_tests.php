<?php
require_once __DIR__ . "/../../src/RBAC/DB.php";
require_once __DIR__ . "/../../src/RBAC/RBAC.php";

use DriveJob\RBAC\DB;
use DriveJob\RBAC\RBAC;

$pdo = DB::pdo();
$results = [];
$fail = 0;

function ok($cond, $name, &$results, &$fail, $ctx = [])
{
    $results[] = ["name" => $name, "ok" => (bool)$cond, "ctx" => $ctx];
    if (!$cond) $fail++;
}

try {
    // Create test user
    $pdo->exec("DELETE FROM users WHERE username='ci_rbac_tester' OR email='ci_rbac_tester@drivejob.gr'");
    $pdo->exec("INSERT INTO users (username,email,password) VALUES ('ci_rbac_tester','ci_rbac_tester@drivejob.gr','x')");
    $uid = (int)$pdo->lastInsertId();

    // Role ids
    $ridAdmin    = $pdo->query("SELECT id FROM roles WHERE name='admin'")->fetchColumn();
    $ridDriver   = $pdo->query("SELECT id FROM roles WHERE name='driver'")->fetchColumn();
    $ridEmployer = $pdo->query("SELECT id FROM roles WHERE name='employer'")->fetchColumn();

    // Give roles to tester
    $stmt = $pdo->prepare("INSERT IGNORE INTO user_roles (user_id, role_id, is_primary) VALUES (:u,:r,0)");
    $stmt->execute([":u" => $uid, ":r" => $ridDriver]);
    $stmt->execute([":u" => $uid, ":r" => $ridEmployer]);

    // 1) Admin has admin.access
    $hasAdmin = RBAC::userCan(1, "admin.access");
    ok($hasAdmin === true, "admin_has_admin.access", $results, $fail);

    // 2) Ensure procedure exists
    $hasProc = (bool)$pdo->query("SELECT COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA=DATABASE() AND ROUTINE_NAME='sp_set_primary_role'")->fetchColumn();
    ok($hasProc, "sp_set_primary_role_exists", $results, $fail);

    // 3) Switch primary to employer and verify only 1 primary
    RBAC::setPrimaryRole($uid, (int)$ridEmployer);
    $row = $pdo->query("SELECT SUM(is_primary) FROM user_roles WHERE user_id={$uid}")->fetchColumn();
    $prim = $pdo->query("SELECT role_id FROM user_roles WHERE user_id={$uid} AND is_primary=1")->fetchColumn();
    ok(((int)$row === 1) && ((int)$prim === (int)$ridEmployer), "primary_switch_to_employer", $results, $fail, ["sum" => $row, "role_id" => $prim]);

    // 4) Try duplicate pair (unique (user_id, role_id))
    $dupOk = true;
    try {
        $pdo->prepare("INSERT INTO user_roles (user_id, role_id, is_primary) VALUES (?,?,0)")->execute([$uid, $ridEmployer]);
        $dupOk = false; // should fail
    } catch (\Throwable $e) {
        $dupOk = true;
    }
    ok($dupOk, "user_roles_pair_unique", $results, $fail);

    // 5) Permissions list not empty for admin (sanity)
    $perms = RBAC::getUserPermissions(1);
    ok(is_array($perms) && count($perms) >= 30, "admin_permissions_list_count", $results, $fail, ["count" => count($perms)]);
} catch (\Throwable $e) {
    $results[] = ["name" => "fatal_exception", "ok" => false, "ctx" => ["message" => $e->getMessage()]];
    $fail++;
}

// Cleanup tester user (soft, keep if you want to inspect)
//$pdo->prepare("DELETE FROM user_roles WHERE user_id=?")->execute([$uid]);
//$pdo->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);

header("Content-Type: application/json; charset=utf-8");
echo \json_encode([
    "failed" => $fail,
    "passed" => count($results) - $fail,
    "tests" => $results
], JSON_UNESCAPED_UNICODE);

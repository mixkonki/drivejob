<?php
require_once __DIR__ . "/../../src/RBAC/DB.php";
require_once __DIR__ . "/../../src/RBAC/RBAC.php";

use DriveJob\RBAC\DB;
use DriveJob\RBAC\RBAC;

$pdo = DB::pdo();

$uid = (int)$pdo->query("SELECT id FROM users ORDER BY id LIMIT 1")->fetchColumn();
RBAC::primePermissions($uid);
$perms = RBAC::getUserPermissions($uid);

echo json_encode(['test' => 'permissions_cache', 'user_id' => $uid, 'count' => count($perms), 'sample' => array_slice($perms, 0, 10)], JSON_UNESCAPED_UNICODE);

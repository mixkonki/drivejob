<?php
require_once __DIR__ . "/_rbac_bootstrap.php";
require_once __DIR__ . "/../../src/RBAC/Perms.php";
require_once __DIR__ . "/../../src/RBAC/Middleware/Guard.php";
require_once __DIR__ . "/../../src/RBAC/DB.php";

use DriveJob\RBAC\Perms;
use DriveJob\RBAC\Middleware\Guard;
use DriveJob\RBAC\DB;
use DriveJob\RBAC\Util\Http;

$uid = (int)(currentUserId() ?? 0);
Guard::requirePermission($uid, Perms::ADMIN_ACCESS);

$perm = $_GET['perm'] ?? '';
if (!$perm) {
    Http::jsonError("Missing perm parameter");
    exit;
}

$pdo = DB::pdo();
$roles = $pdo->prepare("
  SELECT DISTINCT r.name
  FROM roles r
  JOIN role_permissions rp ON rp.role_id=r.id
  JOIN permissions p ON p.id=rp.permission_id
  WHERE p.name = :perm
  ORDER BY r.name
");
$roles->execute([":perm" => $perm]);

$users = $pdo->prepare("
  SELECT DISTINCT u.id, u.username
  FROM users u
  JOIN user_roles ur ON ur.user_id=u.id
  JOIN role_permissions rp ON rp.role_id=ur.role_id
  JOIN permissions p ON p.id=rp.permission_id
  WHERE p.name = :perm
  ORDER BY u.id
  LIMIT 100
");
$users->execute([":perm" => $perm]);

Http::json([
    "perm" => $perm,
    "roles" => $roles->fetchAll(PDO::FETCH_COLUMN),
    "users" => $users->fetchAll(PDO::FETCH_ASSOC)
]);

<?php
require_once __DIR__ . "/_rbac_bootstrap.php";
require_once __DIR__ . "/../../src/RBAC/Perms.php";
require_once __DIR__ . "/../../src/RBAC/Middleware/Guard.php";
require_once __DIR__ . "/../../src/RBAC/DB.php";

use DriveJob\RBAC\Perms;
use DriveJob\RBAC\Middleware\Guard;
use DriveJob\RBAC\DB;
use DriveJob\RBAC\Util\Http;

$uid = (int) (currentUserId() ?? 0);
Guard::requirePermission($uid, Perms::ADMIN_ACCESS);

$pdo = DB::pdo();
$roles = $pdo->query("
  SELECT r.name AS role, GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ', ') AS perms
  FROM roles r
  LEFT JOIN role_permissions rp ON rp.role_id=r.id
  LEFT JOIN permissions p ON p.id=rp.permission_id
  GROUP BY r.id, r.name
  ORDER BY r.name
")->fetchAll(PDO::FETCH_ASSOC);

$users = $pdo->query("
  SELECT u.id, u.username, r.name AS primary_role
  FROM users u
  LEFT JOIN user_roles ur ON ur.user_id=u.id AND ur.is_primary=1
  LEFT JOIN roles r       ON r.id=ur.role_id
  ORDER BY u.id
  LIMIT 200
")->fetchAll(PDO::FETCH_ASSOC);

Http::json(["roles" => $roles, "users" => $users]);

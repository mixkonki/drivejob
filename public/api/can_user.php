<?php
require_once __DIR__ . "/_rbac_bootstrap.php";
require_once __DIR__ . "/../../src/RBAC/RBAC.php";
require_once __DIR__ . "/../../src/RBAC/Middleware/Guard.php";
require_once __DIR__ . "/../../src/RBAC/Perms.php";

use DriveJob\RBAC\RBAC;
use DriveJob\RBAC\Middleware\Guard;
use DriveJob\RBAC\Perms;
use DriveJob\RBAC\Util\Http;

$uid = (int)(currentUserId() ?? 0);
Guard::requirePermission($uid, Perms::ADMIN_ACCESS);

$checkUid = (int)($_GET['uid'] ?? 0);
$perm = $_GET['perm'] ?? '';

Http::json([
    "uid" => $checkUid,
    "perm" => $perm,
    "allowed" => ($checkUid && $perm) ? RBAC::userCan($checkUid, $perm) : false
]);

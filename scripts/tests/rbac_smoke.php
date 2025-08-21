<?php
require_once __DIR__ . "/../../src/RBAC/DB.php";
require_once __DIR__ . "/../../src/RBAC/RBAC.php";

use DriveJob\RBAC\RBAC;

// Adjust to an id that exists in your DB
$adminId = 1;

$perms = RBAC::getUserPermissions($adminId);
$can   = RBAC::userCan($adminId, "admin.access");

header("Content-Type: application/json; charset=utf-8");
echo json_encode([
    "user_id" => $adminId,
    "permissions_count" => count($perms),
    "has_admin_access" => $can,
    "sample_permissions" => array_slice($perms, 0, 10),
], JSON_UNESCAPED_UNICODE);

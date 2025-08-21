<?php
require_once __DIR__ . "/_rbac_bootstrap.php";
require_once __DIR__ . "/../../src/RBAC/Perms.php";

use DriveJob\RBAC\RBAC;
use DriveJob\RBAC\Perms;

header("Content-Type: application/json; charset=utf-8");
$uid = (int) (currentUserId() ?? 0);
RBAC::requirePermission($uid, Perms::FAVORITES_USE);

// TODO: favorite/unfavorite action...
echo json_encode(["ok"=>true,"action"=>"favorites_use","by_user"=>$uid], JSON_UNESCAPED_UNICODE);

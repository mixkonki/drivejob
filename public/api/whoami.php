<?php
require_once __DIR__ . "/_rbac_bootstrap.php";
require_once __DIR__ . "/../../src/RBAC/RBAC.php";

use DriveJob\RBAC\RBAC;
use DriveJob\RBAC\Util\Http;

$uid = (int) (currentUserId() ?? 0);
$primary = RBAC::getPrimaryRole($uid);
Http::json(["user_id" => $uid, "primary_role" => $primary]);

<?php
require_once __DIR__ . "/_rbac_bootstrap.php";
require_once __DIR__ . "/../../src/RBAC/Perms.php";
require_once __DIR__ . "/../../src/RBAC/Ownership/Applications.php";

use DriveJob\RBAC\RBAC;
use DriveJob\RBAC\Perms;
use DriveJob\RBAC\Ownership\Applications;

header("Content-Type: application/json; charset=utf-8");
$uid = (int) (currentUserId() ?? 0);
$aid = isset($_GET["application_id"]) ? (int)$_GET["application_id"] : 0;

RBAC::requireOwnerOrAny(
    $uid,
    Perms::APPL_MANAGE_OWN,
    Perms::APPL_MANAGE_ANY,
    fn(int $userId) => Applications::isEmployerOfApplication($userId, $aid)
);

// TODO: εδώ θα γίνει η διαχείριση (accept/reject κ.λπ.)
echo json_encode(["ok"=>true,"action"=>"application_manage","application_id"=>$aid,"by_user"=>$uid], JSON_UNESCAPED_UNICODE);

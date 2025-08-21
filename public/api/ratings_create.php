<?php
require_once __DIR__ . "/_rbac_bootstrap.php";
require_once __DIR__ . "/../../src/RBAC/Perms.php";
require_once __DIR__ . "/../../src/RBAC/Ownership/Jobs.php";

use DriveJob\RBAC\RBAC;
use DriveJob\RBAC\Perms;
use DriveJob\RBAC\Ownership\Jobs;

header("Content-Type: application/json; charset=utf-8");
$uid = (int) (currentUserId() ?? 0);
$listingId = isset($_GET["job_listing_id"]) ? (int)$_GET["job_listing_id"] : 0;

RBAC::requirePermission($uid, Perms::RATINGS_CREATE);
RBAC::requireOwnerOrAny(
    $uid,
    Perms::RATINGS_VIEW_OWN,   // use view.own as ownership baseline
    Perms::ADMIN_ACCESS,       // admin bypass (global)
    fn(int $userId) => Jobs::isOwner($userId, $listingId)
);

// TODO: create rating...
echo json_encode(["ok"=>true,"action"=>"ratings_create","job_listing_id"=>$listingId,"by_user"=>$uid], JSON_UNESCAPED_UNICODE);

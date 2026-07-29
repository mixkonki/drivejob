<?php
require_once __DIR__ . "/_rbac_bootstrap.php";
require_once __DIR__ . "/../../src/RBAC/Perms.php";
require_once __DIR__ . "/../../src/RBAC/Ownership/Jobs.php";

use DriveJob\RBAC\RBAC;
use DriveJob\RBAC\Perms;
use DriveJob\RBAC\Ownership\Jobs;

header("Content-Type: application/json; charset=utf-8");
$uid = (int) (currentUserId() ?? 0);
$jobId = isset($_GET["job_id"]) ? (int)$_GET["job_id"] : 0;

RBAC::requireOwnerOrAny(
    $uid,
    Perms::MATCHES_VIEW_OWN,
    Perms::MATCHES_VIEW_ANY,
    fn(int $userId) => Jobs::isOwner($userId, $jobId)
);

echo json_encode(["ok"=>true,"action"=>"matches_view","job_id"=>$jobId,"by_user"=>$uid], JSON_UNESCAPED_UNICODE);

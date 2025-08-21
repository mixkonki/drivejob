<?php
require_once __DIR__ . "/_rbac_bootstrap.php";
require_once __DIR__ . "/../../src/RBAC/Ownership/Jobs.php";

use DriveJob\RBAC\RBAC;
use DriveJob\RBAC\Ownership\Jobs;

header("Content-Type: application/json; charset=utf-8");

$uid   = (int) currentUserId();
$jobId = isset($_GET["job_id"]) ? (int)$_GET["job_id"] : 0;

RBAC::requireOwnerOrAny(
    $uid,
    "jobs.edit.own",
    "jobs.edit.any",
    fn(int $userId) => Jobs::isOwner($userId, $jobId)
);

// OK: εδώ βάζεις την ενημέρωση της αγγελίας
echo json_encode(["ok" => true, "action" => "edit_job", "job_id" => $jobId, "by_user" => $uid], JSON_UNESCAPED_UNICODE);

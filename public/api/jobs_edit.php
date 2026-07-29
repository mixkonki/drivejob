<?php
require_once __DIR__ . "/_rbac_bootstrap.php";
require_once __DIR__ . "/../../src/RBAC/Ownership/Jobs.php";

use DriveJob\RBAC\Middleware\Guard;
use DriveJob\RBAC\Middleware\HttpGuard;
use DriveJob\RBAC\Ownership\Jobs;
use DriveJob\RBAC\Util\Http;

$uid   = (int) (currentUserId() ?? 0);

header("Content-Type: application/json; charset=utf-8");
HttpGuard::requireRateLimit('jobs_edit:' . $uid, 30, 60);

$jobId = isset($_GET["job_id"]) ? (int)$_GET["job_id"] : 0;

Guard::requireOwnerOrAny(
    $uid,
    "jobs.edit.own",
    "jobs.edit.any",
    fn(int $userId) => Jobs::isOwner($userId, $jobId)
);

// TODO: edit logic
Http::json(["ok" => true, "action" => "edit_job", "job_id" => $jobId, "by_user" => $uid]);

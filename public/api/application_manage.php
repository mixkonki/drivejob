<?php
require_once __DIR__ . "/_rbac_bootstrap.php";
require_once __DIR__ . "/../../src/RBAC/Perms.php";
require_once __DIR__ . "/../../src/RBAC/Ownership/Applications.php";
require_once __DIR__ . "/../../src/RBAC/Middleware/HttpGuard.php";

use DriveJob\RBAC\Middleware\HttpGuard;
use DriveJob\RBAC\Middleware\Guard;
use DriveJob\RBAC\Perms;
use DriveJob\RBAC\Ownership\Applications;
use DriveJob\RBAC\Util\Http;

$uid = (int) (currentUserId() ?? 0);

header("Content-Type: application/json; charset=utf-8");
HttpGuard::requireMethod("POST");
HttpGuard::requireCsrf();
HttpGuard::requireRateLimit('application_manage:' . $uid, 30, 60);

$aid = isset($_POST["application_id"]) ? (int)$_POST["application_id"] : 0;

Guard::requireOwnerOrAny(
    $uid,
    Perms::APPL_MANAGE_OWN,
    Perms::APPL_MANAGE_ANY,
    fn(int $userId) => Applications::isEmployerOfApplication($userId, $aid),
    ["application_id" => $aid]
);

// TODO: accept/reject...
Http::json(["ok" => true, "action" => "application_manage", "application_id" => $aid, "by_user" => $uid]);

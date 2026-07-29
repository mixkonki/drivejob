<?php
require_once __DIR__ . "/_rbac_bootstrap.php";
require_once __DIR__ . "/../../src/RBAC/Perms.php";
require_once __DIR__ . "/../../src/RBAC/Ownership/Applications.php";

use DriveJob\RBAC\Middleware\Guard;
use DriveJob\RBAC\Middleware\HttpGuard;
use DriveJob\RBAC\Perms;
use DriveJob\RBAC\Ownership\Applications;
use DriveJob\RBAC\Util\Http;

$uid = (int) (currentUserId() ?? 0);

header("Content-Type: application/json; charset=utf-8");
HttpGuard::requireRateLimit('application_view:' . $uid, 120, 60);

$aid = isset($_GET["application_id"]) ? (int)$_GET["application_id"] : 0;

Guard::requireOwnerOrAny(
    $uid,
    Perms::APPL_VIEW_OWN,
    Perms::APPL_VIEW_ANY,
    fn(int $userId) => Applications::isEmployerOfApplication($userId, $aid)
);

Http::json(["ok" => true, "action" => "application_view", "application_id" => $aid, "by_user" => $uid]);

<?php
require_once __DIR__ . "/_rbac_bootstrap.php";
require_once __DIR__ . "/../../src/RBAC/Perms.php";
require_once __DIR__ . "/../../src/RBAC/Middleware/Guard.php";

use DriveJob\RBAC\Perms;
use DriveJob\RBAC\Middleware\Guard;
use DriveJob\RBAC\Util\Http;

$uid = (int) (currentUserId() ?? 0);
Guard::requirePermission($uid, Perms::ADMIN_ACCESS);

$file = __DIR__ . "/../../storage/logs/rbac.log";
$lines = [];
if (is_file($file)) {
    $raw = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($raw)) $lines = array_slice($raw, -200);
}
Http::json(["lines" => $lines]);

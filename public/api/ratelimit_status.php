<?php
require_once __DIR__ . "/_rbac_bootstrap.php";
require_once __DIR__ . "/../../src/RBAC/Perms.php";
require_once __DIR__ . "/../../src/RBAC/Middleware/Guard.php";

use DriveJob\RBAC\Perms;
use DriveJob\RBAC\Middleware\Guard;
use DriveJob\RBAC\Util\Http;

$uid = (int) (currentUserId() ?? 0);
Guard::requirePermission($uid, Perms::ADMIN_ACCESS);

$dir = __DIR__ . "/../../storage/cache/ratelimit";
$out = [];
if (is_dir($dir)) {
    foreach (glob($dir . "/*.json") as $file) {
        $name = basename($file, ".json");
        $j = json_decode(@file_get_contents($file), true) ?: [];
        $out[] = ["bucket" => $name, "count" => $j["count"] ?? 0, "reset" => $j["reset"] ?? 0, "reset_in" => max(0, ($j["reset"] ?? 0) - time())];
    }
}
Http::json(["buckets" => $out]);

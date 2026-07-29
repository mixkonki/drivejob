<?php
require_once __DIR__ . "/../_rbac_bootstrap.php";
require_once __DIR__ . "/../../../src/RBAC/Perms.php";
require_once __DIR__ . "/../../../src/RBAC/Middleware/Guard.php";
require_once __DIR__ . "/../../../src/RBAC/HttpGuard.php";
require_once __DIR__ . "/../../../src/RBAC/Util/Http.php";
require_once __DIR__ . "/../../../src/RBAC/DB.php";

use DriveJob\RBAC\Perms;
use DriveJob\RBAC\Middleware\Guard;
use DriveJob\RBAC\HttpGuard;
use DriveJob\RBAC\Util\Http;
use DriveJob\RBAC\DB;

header("Content-Type: application/json; charset=utf-8");
$uid = (int)(currentUserId() ?? 0);
Guard::requirePermission($uid, Perms::ADMIN_ACCESS);
HttpGuard::requireMethod(["POST"]);
HttpGuard::requireCsrf();

$raw = file_get_contents("php://input");
$body = json_decode($raw, true);
if (!is_array($body)) $body = $_POST;

$entity  = $body["entity"]  ?? "";
$action  = $body["action"]  ?? "";
$id      = (int)($body["id"] ?? 0);
$userId  = isset($body["user_id"]) ? (int)$body["user_id"] : null;
$override = (int)($body["override"] ?? 0);

if (!in_array($entity, ["company", "driver"], true)) Http::json(["error" => "invalid_entity"], 400);
if (!in_array($action, ["link", "unlink"], true))   Http::json(["error" => "invalid_action"], 400);
if ($id <= 0) Http::json(["error" => "invalid_id"], 400);
if ($action === "link" && (!$userId || $userId <= 0)) Http::json(["error" => "missing_user_id"], 400);

$table = $entity === "company" ? "companies" : "drivers";

$pdo = DB::pdo();
$pdo->beginTransaction();
try {
    // Ensure target exists
    $exists = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE id=:id");
    $exists->execute([":id" => $id]);
    if (!$exists->fetchColumn()) {
        $pdo->rollBack();
        Http::json(["error" => "not_found"], 404);
    }

    if ($action === "unlink") {
        $upd = $pdo->prepare("UPDATE {$table} SET user_id=NULL WHERE id=:id");
        $upd->execute([":id" => $id]);
        $pdo->commit();
        Http::json(["ok" => true, "action" => "unlink", "entity" => $entity, "id" => $id]);
    }

    // action === link
    if ($override === 1) {
        // Free any previous link of this user in the same table (due to UNIQUE)
        $free = $pdo->prepare("UPDATE {$table} SET user_id=NULL WHERE user_id=:u AND id<>:id");
        $free->execute([":u" => $userId, ":id" => $id]);
    }

    $upd = $pdo->prepare("UPDATE {$table} SET user_id=:u WHERE id=:id");
    $upd->execute([":u" => $userId, ":id" => $id]);

    $pdo->commit();
    Http::json(["ok" => true, "action" => "link", "entity" => $entity, "id" => $id, "user_id" => $userId, "override" => $override]);
} catch (\Throwable $e) {
    $pdo->rollBack();
    // Likely UNIQUE violation if override=0 and user already linked
    Http::json(["error" => "update_failed", "message" => $e->getMessage()], 409);
}

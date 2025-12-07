<?php
require_once __DIR__ . "/_rbac_bootstrap.php";
require_once __DIR__ . "/../../src/RBAC/Perms.php";
require_once __DIR__ . "/../../src/RBAC/Middleware/Guard.php";
require_once __DIR__ . "/../../src/RBAC/DB.php";

use DriveJob\RBAC\Perms;
use DriveJob\RBAC\Middleware\Guard;
use DriveJob\RBAC\DB;
use DriveJob\RBAC\Util\Http;

$uid = (int)(currentUserId() ?? 0);
Guard::requirePermission($uid, Perms::ADMIN_ACCESS);

$entity = isset($_GET['entity']) ? preg_replace('/[^A-Za-z_]+/', '', $_GET['entity']) : null;
$limit  = max(1, min(500, (int)($_GET['limit'] ?? 100)));
$since  = isset($_GET['since']) ? (int)$_GET['since'] : null;

$sql = "SELECT id, occurred_at, actor_user_id, event, entity, entity_id, details
        FROM rbac_audit WHERE 1";
$params = [];
if ($entity) {
    $sql .= " AND entity = :e";
    $params[':e'] = $entity;
}
if ($since) {
    $sql .= " AND occurred_at >= FROM_UNIXTIME(:s)";
    $params[':s'] = $since;
}
$sql .= " ORDER BY occurred_at DESC, id DESC LIMIT :lim";

$pdo = DB::pdo();
$st = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $st->bindValue($k, $v);
}
$st->bindValue(':lim', $limit, PDO::PARAM_INT);
$st->execute();

Http::json(["items" => $st->fetchAll(PDO::FETCH_ASSOC)]);

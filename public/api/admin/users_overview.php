<?php
require_once __DIR__ . "/../_rbac_bootstrap.php";
require_once __DIR__ . "/../../../src/RBAC/Perms.php";
require_once __DIR__ . "/../../../src/RBAC/Middleware/Guard.php";
require_once __DIR__ . "/../../../src/RBAC/DB.php";

use DriveJob\RBAC\Perms;
use DriveJob\RBAC\Middleware\Guard;
use DriveJob\RBAC\DB;

header("Content-Type: application/json; charset=utf-8");
$uid = (int) (currentUserId() ?? 0);
Guard::requirePermission($uid, Perms::ADMIN_ACCESS);

$q = trim($_GET['q'] ?? '');
$limit = max(1, min(200, (int)($_GET['limit'] ?? 100)));

$sql = "SELECT * FROM v_user_overview WHERE 1";
$params = [];
if ($q !== '') {
    $sql .= " AND (username LIKE :q OR email LIKE :q)";
    $params[':q'] = "%$q%";
}
$sql .= " ORDER BY id ASC LIMIT :lim";

$pdo = DB::pdo();
$st = $pdo->prepare($sql);
foreach ($params as $k => $v) $st->bindValue($k, $v);
$st->bindValue(':lim', $limit, PDO::PARAM_INT);
$st->execute();

echo json_encode(['items' => $st->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);

<?php
require_once __DIR__ . "/../../src/RBAC/DB.php";

use DriveJob\RBAC\DB;

$pdo = DB::pdo();
$rows = $pdo->query("
    SELECT r.name AS role, GROUP_CONCAT(p.name ORDER BY p.name SEPARATOR ', ') AS perms
    FROM roles r
    LEFT JOIN role_permissions rp ON rp.role_id=r.id
    LEFT JOIN permissions p ON p.id=rp.permission_id
    GROUP BY r.id, r.name
    ORDER BY r.name
")->fetchAll(PDO::FETCH_ASSOC);

$md = "# RBAC Role → Permissions Matrix\n\n";
foreach ($rows as $r) {
    $md .= "## " . $r['role'] . "\n\n";
    $md .= ($r['perms'] ?: "_(no permissions)_") . "\n\n";
}
file_put_contents(__DIR__ . "/../../docs/RBAC_MATRIX.md", $md);
echo "docs/RBAC_MATRIX.md updated\n";

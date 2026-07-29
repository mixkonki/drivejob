<?php

declare(strict_types=1);
/** Public admin entry → loads unified dashboard with RBAC guard */
require_once __DIR__ . "/../api/_rbac_bootstrap.php";

use DriveJob\RBAC\RBAC;

$uid = (int)(currentUserId() ?? 0);
RBAC::requirePermission($uid, "admin.access");

require_once __DIR__ . "/../../src/Views/admin/dashboard.php";

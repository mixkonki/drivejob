<?php
// Minimal bootstrap for endpoints that need RBAC checks.
require_once __DIR__ . "/../../src/RBAC/DB.php";
require_once __DIR__ . "/../../src/RBAC/RBAC.php";

use DriveJob\RBAC\RBAC;

/** Replace με πραγματικό auth/session. Για dev: ?uid=1 ή υπαρκτό id. */
function currentUserId(): ?int
{
    if (isset($_GET["uid"])) return max(1, (int)$_GET["uid"]);
    return 1;
}

RBAC::primePermissions((int) currentUserId());

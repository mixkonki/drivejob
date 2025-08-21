<?php
require_once __DIR__ . "/_rbac_bootstrap.php";
use DriveJob\RBAC\RBAC;

header("Content-Type: application/json; charset=utf-8");

// Example: only users with jobs.create OR jobs.edit.own can call this
RBAC::requireAny((int)currentUserId(), ["jobs.create", "jobs.edit.own"]);

// Business logic here...
echo json_encode(["ok"=>true, "message"=>"You have access to this protected endpoint."], JSON_UNESCAPED_UNICODE);

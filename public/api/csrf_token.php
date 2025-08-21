<?php
require_once __DIR__ . "/_rbac_bootstrap.php";

use DriveJob\RBAC\Util\Http;

Http::json(["csrf_token" => $_SESSION["csrf_token"] ?? null]);

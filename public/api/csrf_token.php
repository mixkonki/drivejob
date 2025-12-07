<?php
require_once __DIR__ . "/_rbac_bootstrap.php";

use DriveJob\RBAC\Util\Http;

// Δημιουργία CSRF token αν δεν υπάρχει
if (!isset($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

Http::json(["csrf_token" => $_SESSION["csrf_token"]]);

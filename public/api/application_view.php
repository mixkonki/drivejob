<?php
require_once __DIR__ . "/_rbac_bootstrap.php";
require_once __DIR__ . "/../../src/RBAC/Ownership/Applications.php";

use DriveJob\RBAC\RBAC;
use DriveJob\RBAC\Ownership\Applications;

header("Content-Type: application/json; charset=utf-8");

$uid  = (int) (currentUserId() ?? 0);
$aid  = isset($_GET["application_id"]) ? (int)$_GET["application_id"] : 0;

// Αν έχεις global: applications.view.any -> ok
// Αλλιώς απαιτείς applications.view.own + employer ownership της αίτησης
RBAC::requireOwnerOrAny(
    $uid,
    "applications.view.own",
    "applications.view.any",
    fn(int $userId) => Applications::isEmployerOfApplication($userId, $aid)
);

// TODO: φόρτωση/επιστροφή της αίτησης
echo json_encode(["ok" => true, "action" => "view_application", "application_id" => $aid, "by_user" => $uid], JSON_UNESCAPED_UNICODE);

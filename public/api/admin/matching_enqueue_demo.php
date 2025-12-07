<?php

declare(strict_types=1);

$bootstrap = realpath(__DIR__ . "/../_rbac_bootstrap.php");
if ($bootstrap === false || !is_file($bootstrap)) {
    http_response_code(500);
    header("Content-Type: application/json");
    echo json_encode(["error" => "bootstrap_not_found"]);
    exit;
}
require_once $bootstrap;

require_once __DIR__ . "/../../../src/RBAC/DB.php";
require_once __DIR__ . "/../../../src/RBAC/RBAC.php";

use DriveJob\RBAC\DB;
use DriveJob\RBAC\RBAC;

header("Content-Type: application/json; charset=utf-8");
RBAC::requirePermission((int)currentUserId(), "admin.access");

$count = isset($_GET["n"]) ? max(1, min(100, (int)$_GET["n"])) : 10;
$base  = realpath(__DIR__ . "/../../../");
$ok = false;
$enq = 0;
$out = null;

$cli = "php";
$script = $base . "/scripts/matching/enqueue_demo.php";
if (is_file($script)) {
    $cmd = $cli . " " . escapeshellarg($script);
    $outRaw = @shell_exec($cmd . " 2>&1");
    if (is_string($outRaw)) {
        $pos = strrpos($outRaw, "{");
        if ($pos !== false) {
            $json = substr($outRaw, $pos);
            $j = json_decode($json, true);
            if (is_array($j) && !empty($j["ok"])) {
                $ok = true;
                $enq = (int)($j["enqueued"] ?? 0);
            }
        }
    }
}

if (!$ok) {
    // Fallback: write N demo jobs directly to queue
    $qdir = $base . "/storage/queue/matching";
    if (!is_dir($qdir)) {
        @mkdir($qdir, 0777, true);
    }
    for ($i = 0; $i < $count; $i++) {
        $job = [
            "job_id" => "api_demo_" . bin2hex(random_bytes(6)),
            "payload" => ["type" => "demo"],
            "enqueued_at" => date("c"),
        ];
        $fn = $qdir . "/" . microtime(true) . "_" . bin2hex(random_bytes(4)) . ".json";
        @file_put_contents($fn, json_encode($job, JSON_UNESCAPED_UNICODE));
        $enq++;
    }
    $ok = true;
}

echo json_encode(["ok" => $ok, "enqueued" => $enq], JSON_UNESCAPED_UNICODE);

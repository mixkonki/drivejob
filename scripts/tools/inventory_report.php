<?php

declare(strict_types=1);
$root = realpath(__DIR__ . "/../../") ?: getcwd();

require_once __DIR__ . "/../../src/RBAC/DB.php";

use DriveJob\RBAC\DB;

$pdo = DB::pdo();

function listFiles(string $dir, array $excludeDirs = []): array
{
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $p = str_replace("\\", "/", $f->getPathname());
        $skip = false;
        foreach ($excludeDirs as $ex) {
            if (str_contains($p, "/$ex/")) {
                $skip = true;
                break;
            }
        }
        if ($skip) continue;
        $out[] = ["p" => $p, "s" => $f->getSize(), "t" => $f->getMTime()];
    }
    return $out;
}

$files = listFiles($root, ["vendor", ".git", "node_modules", "storage/cache", "storage/queue"]);
$total = count($files);
$size  = array_sum(array_column($files, "s"));
usort($files, fn($a, $b) => $b["s"] <=> $a["s"]);
$largest = array_slice($files, 0, 12);

$since = time() - 5 * 86400;
$recent = array_values(array_filter($files, fn($x) => $x["t"] >= $since));
usort($recent, fn($a, $b) => $b["t"] <=> $a["t"]);
$recent = array_slice($recent, 0, 40);

$apis = glob($root . "/public/api/**/*.php", GLOB_BRACE) ?: [];

$legacy = [];
$patts = ["users.role_id", "users.role"];
foreach ($files as $f) {
    if (!str_ends_with($f["p"], ".php") && !str_ends_with($f["p"], ".sql")) continue;
    $txt = @file_get_contents($f["p"]);
    if ($txt === false) continue;
    foreach ($patts as $p) if (str_contains($txt, $p)) {
        $legacy[] = $f["p"];
        break;
    }
}

$rows = [];
$rows["users.role col"]   = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='role'")->fetchColumn();
$rows["users.role_id col"] = $pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='role_id'")->fetchColumn();
$rows["fk_users_role"]    = $pdo->query("SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND CONSTRAINT_NAME='fk_users_role'")->fetchColumn();
$rows["matching_metrics"] = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='matching_metrics'")->fetchColumn();
$rows["metrics_samples"]  = $pdo->query("SELECT COUNT(*) FROM matching_metrics")->fetchColumn();

$md = "# INVENTORY REPORT\n\n";
$md .= "Root: `{$root}`\n\n";
$md .= "## Summary\n- Total files: **{$total}**\n- Total size: **" . number_format($size / 1024 / 1024, 2) . " MB**\n\n";

$md .= "## Top 12 Largest Files\n";
foreach ($largest as $i => $f) {
    $md .= ($i + 1) . ". `" . str_replace($root . "/", "", $f["p"]) . "` — " . number_format($f["s"] / 1024, 1) . " KB\n";
}

$md .= "\n## Recently Modified (<=5 days)\n";
foreach ($recent as $f) {
    $md .= "- `" . str_replace($root . "/", "", $f["p"]) . "` — " . date("Y-m-d H:i:s", $f["t"]) . "\n";
}

$md .= "\n## API Endpoints\n";
foreach ($apis as $a) {
    $md .= "- `" . str_replace($root . "/", "", $a) . "`\n";
}

$md .= "\n## Legacy references (users.role / users.role_id)\n";
if ($legacy) foreach ($legacy as $p) {
    $md .= "- `" . str_replace($root . "/", "", $p) . "`\n";
}
else $md .= "_none_\n";

$md .= "\n## DB Sanity\n";
foreach ($rows as $k => $v) {
    $md .= "- {$k}: **{$v}**\n";
}

file_put_contents($root . "/docs/INVENTORY_REPORT.md", $md);
echo "docs/INVENTORY_REPORT.md written\n";

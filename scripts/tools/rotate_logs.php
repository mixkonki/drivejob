<?php

declare(strict_types=1);

$root = realpath(__DIR__ . "/../..");
$log = $root . "/storage/logs/supervisor.log";
$max = 5 * 1024 * 1024; // 5MB

if (!is_file($log)) {
    echo "No supervisor.log\n";
    exit(0);
}

$size = filesize($log);
if ($size === false) {
    exit(0);
}

if ($size <= $max) {
    echo "No rotation needed (" . $size . " bytes)\n";
    exit(0);
}

for ($i = 5; $i >= 1; $i--) {
    $old = $log . "." . $i;
    $older = $log . "." . ($i + 1);
    if (is_file($old)) {
        @rename($old, $older);
    }
}

@rename($log, $log . ".1");
@touch($log);

echo "Rotated supervisor.log\n";

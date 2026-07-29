<?php
function run($cmd)
{
    echo "\n> $cmd\n";
    passthru($cmd, $code);
    if ($code !== 0) {
        echo "Exit code: $code\n";
    }
}
$php = "php";
$base = __DIR__;

run("$php $base/rbac_primary_test.php");
run("$php $base/rbac_permissions_test.php");

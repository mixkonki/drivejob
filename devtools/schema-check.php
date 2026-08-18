<?php

/**
 * DevTool: Έλεγχος κατάστασης σχήματος για το Πακέτο 5.2 (αφαίρεση runtime SHOW TABLES)
 * Χρήση: php devtools/schema-check.php   (read-only)
 */

if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

$pdo = new PDO('mysql:host=127.0.0.1;dbname=drivejob;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

function t(PDO $pdo, string $table): bool {
    $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?");
    $st->execute([$table]);
    return (int) $st->fetchColumn() > 0;
}
function c(PDO $pdo, string $table, string $col): bool {
    $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?");
    $st->execute([$table, $col]);
    return (int) $st->fetchColumn() > 0;
}
function cnt(PDO $pdo, string $table): int {
    return (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
}

$checks = [
    ['table', 'driver_operator_sub_specialities'],
    ['column', 'driver_operator_sub_specialities', 'group_type'],
    ['table', 'driver_operator_sub_speciality_groups'],
    ['table', 'matching_scores'],
    ['table', 'notifications'],
    ['table', 'license_expiry_notifications'],
    ['column', 'driver_licenses', 'pei_expiry_c'],
    ['column', 'driver_licenses', 'pei_expiry_d'],
    ['column', 'driver_licenses', 'has_pei'],
];

foreach ($checks as $chk) {
    if ($chk[0] === 'table') {
        $exists = t($pdo, $chk[1]);
        echo ($exists ? '✅' : '❌') . " πίνακας {$chk[1]}" . ($exists ? ' (' . cnt($pdo, $chk[1]) . ' εγγραφές)' : '') . "\n";
    } else {
        echo (c($pdo, $chk[1], $chk[2]) ? '✅' : '❌') . " στήλη {$chk[1]}.{$chk[2]}\n";
    }
}

echo "\n🏁 Τέλος.\n";

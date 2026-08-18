<?php

/**
 * DevTool: Έλεγχος κυκλώματος ειδοποιήσεων λήξης αδειών (Πακέτο 5)
 *
 * Χρήση:  php devtools/test-license-expiry.php
 *
 * ΔΕΝ στέλνει email/SMS και ΔΕΝ γράφει στη βάση — μόνο διαβάζει:
 *   1. Τρέχει τα queries εύρεσης αδειών που λήγουν (180 ημέρες μπροστά)
 *   2. Κάνει render ένα δείγμα email από κάθε πρότυπο
 *   3. Επιβεβαιώνει ότι υπάρχουν όλα τα αρχεία προτύπων
 */

if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

require_once __DIR__ . '/../src/bootstrap.php';

use Drivejob\Services\Expiry\LicenseExpiryRepository;
use Drivejob\Services\Expiry\LicenseExpiryMessageComposer;

$pdo = new PDO('mysql:host=127.0.0.1;dbname=drivejob;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$repo = new LicenseExpiryRepository($pdo);
$maxDate = (new DateTime())->modify('+180 days')->format('Y-m-d');

echo "═══ 1. Queries εύρεσης λήξεων (έως {$maxDate}) ═══\n";
$finders = [
    'Άδειες οδήγησης' => fn() => $repo->findExpiringDrivingLicenses($maxDate),
    'ΠΕΙ C' => fn() => $repo->columnExists('driver_licenses', 'pei_expiry_c') ? $repo->findExpiringPei('pei_expiry_c', $maxDate) : null,
    'ΠΕΙ D' => fn() => $repo->columnExists('driver_licenses', 'pei_expiry_d') ? $repo->findExpiringPei('pei_expiry_d', $maxDate) : null,
    'ADR' => fn() => $repo->findExpiringAdrCertificates($maxDate),
    'Ταχογράφοι' => fn() => $repo->findExpiringTachographCards($maxDate),
    'Χειριστές ΜΕ' => fn() => $repo->findExpiringOperatorLicenses($maxDate),
    'Ειδικές άδειες' => fn() => $repo->findExpiringSpecialLicenses($maxDate),
];
foreach ($finders as $label => $fn) {
    try {
        $rows = $fn();
        echo $rows === null
            ? "  ⚠️  {$label}: η στήλη δεν υπάρχει — παραλείπεται\n"
            : "  ✅ {$label}: " . count($rows) . " εγγραφές που λήγουν\n";
    } catch (Throwable $e) {
        echo "  ❌ {$label}: {$e->getMessage()}\n";
    }
}

echo "\n═══ 2. Αρχεία προτύπων email ═══\n";
$templatesPath = dirname(__DIR__) . '/templates/emails/';
$expected = ['general', 'driving_license', 'pei', 'adr_certificate', 'tachograph_card', 'operator_license', 'special_license'];
foreach ($expected as $cat) {
    $file = $templatesPath . "license_expiry_{$cat}.php";
    echo (is_file($file) ? "  ✅" : "  ❌ ΛΕΙΠΕΙ") . " license_expiry_{$cat}.php\n";
}

echo "\n═══ 3. Δοκιμαστικό render email ανά κατηγορία ═══\n";
$composer = new LicenseExpiryMessageComposer($templatesPath);
$samples = [
    'driving_license'  => ['first_name' => 'Δοκιμή', 'license_type' => 'C, CE', 'expiry_date' => '2026-10-01', 'days_before_expiry' => 30],
    'pei'              => ['first_name' => 'Δοκιμή', 'pei_category' => 'C', 'expiry_date' => '2026-10-01', 'days_before_expiry' => 30],
    'adr_certificate'  => ['first_name' => 'Δοκιμή', 'adr_type' => 'Βασική', 'expiry_date' => '2026-10-01', 'days_before_expiry' => 30],
    'tachograph_card'  => ['first_name' => 'Δοκιμή', 'card_number' => 'GR123', 'expiry_date' => '2026-10-01', 'days_before_expiry' => 30],
    'operator_license' => ['first_name' => 'Δοκιμή', 'speciality' => '2', 'speciality_name' => $composer->operatorSpecialityName('2'), 'license_number' => '555', 'expiry_date' => '2026-10-01', 'days_before_expiry' => 90],
    'special_license'  => ['first_name' => 'Δοκιμή', 'license_type' => 'Ταξί', 'license_number' => '777', 'details' => '', 'expiry_date' => '2026-10-01', 'days_before_expiry' => 30],
];
foreach ($samples as $cat => $data) {
    try {
        $html = $composer->renderEmail($cat, $data);
        $ok = strlen($html) > 500 && strpos($html, 'Δοκιμή') !== false && strpos($html, 'DriveJob') !== false;
        echo ($ok ? "  ✅" : "  ⚠️ ") . " {$cat}: " . strlen($html) . " bytes, θέμα: «" . $composer->emailSubject($cat, $data) . "»\n";
    } catch (Throwable $e) {
        echo "  ❌ {$cat}: {$e->getMessage()}\n";
    }
}

echo "\n═══ 4. Δείγμα SMS ═══\n";
echo '  ' . $composer->smsMessage('adr_certificate', ['adr_type' => 'Βασική'], 7) . "\n";

echo "\n🏁 Τέλος — κανένα email/SMS δεν στάλθηκε, καμία εγγραφή δεν γράφτηκε.\n";

<?php

/**
 * Έλεγχος ότι τα φίλτρα της λίστας αγγελιών φιλτράρουν πραγματικά.
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΥΠΑΡΧΕΙ
 * ══════════════════════════════════════════════════════════════════════════
 *
 * Στις 23/08/2026 βρέθηκε ότι από τα έξι φίλτρα της σελίδας λειτουργούσαν
 * ΔΥΟ. Τα υπόλοιπα τέσσερα άλλαζαν το URL και τίποτα άλλο:
 *
 *   Τύπος αγγελίας    → το repository δεν διάβαζε ποτέ το listing_type
 *   Τύπος οχήματος    → η φόρμα έστελνε `vehicle_type`, ο controller
 *                       διάβαζε `vehicle_types` (άλλο όνομα)
 *   ADR               → η στήλη λέγεται adr_certificate, ο κώδικας ζητούσε
 *                       adr_required (ανύπαρκτη στήλη)
 *   Άδεια χειριστή    → το ίδιο
 *
 * Κανένα από αυτά δεν έβγαζε σφάλμα. Η σελίδα φόρτωνε κανονικά, τα κουτάκια
 * κρατούσαν την επιλογή, και τα αποτελέσματα ήταν απλώς ΟΛΑ. Ο χρήστης δεν
 * έχει τρόπο να το καταλάβει — νομίζει ότι είδε φιλτραρισμένη λίστα.
 *
 * Ένα φίλτρο που δεν φιλτράρει δεν φαίνεται με το μάτι. Φαίνεται μόνο αν
 * μετρήσεις: πόσα λέει η σελίδα, πόσα λέει η βάση.
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  ΧΡΗΣΗ
 * ══════════════════════════════════════════════════════════════════════════
 *
 *   php scripts/check-filters.php                        # τοπικά
 *   php scripts/check-filters.php https://drivejob.gr    # παραγωγή
 *
 * ΤΡΕΞΕ ΤΟ μετά από κάθε αλλαγή στο searchListings, στα κριτήρια του
 * controller, ή στις επιλογές των μενού.
 */

require_once __DIR__ . '/../src/bootstrap.php';

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8899', '/');

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

restore_exception_handler();
restore_error_handler();

const RED = "\033[0;31m";
const GRN = "\033[0;32m";
const DIM = "\033[2m";
const OFF = "\033[0m";

/** Η ίδια βάση με το ερώτημα του repository: ενεργές και μη ληγμένες. */
const LIVE = 'is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())';

/**
 * Πόσες αγγελίες λέει η ΣΕΛΙΔΑ.
 *
 * Διαβάζεται από την επικεφαλίδα («26 αγγελίες»), δηλαδή από αυτό που
 * βλέπει ο χρήστης — όχι από κάποιο API που μπορεί να συμφωνεί με τη βάση
 * ενώ η σελίδα δείχνει άλλα.
 */
function pageCount(string $url): ?int
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => 'drivejob-filter-check',
    ]);
    $html = curl_exec($ch);
    curl_close($ch);

    if ($html === false) {
        return null;
    }

    if (preg_match('/Κανένα αποτέλεσμα/u', $html)) {
        return 0;
    }

    if (preg_match('/<h2>\s*([\d.]+)\s+αγγελ/u', $html, $m)) {
        return (int) str_replace('.', '', $m[1]);
    }

    if (preg_match('/<h2>\s*1\s+αγγελία/u', $html)) {
        return 1;
    }

    return null;
}

$checks = [];

// ── Τύπος αγγελίας ──────────────────────────────────────────────────────
foreach (['job_offer', 'job_search'] as $v) {
    $checks[] = [
        'label' => "listing_type=$v",
        'url'   => "/job-listings?listing_type=$v",
        'sql'   => "SELECT COUNT(*) FROM job_listings WHERE " . LIVE . " AND listing_type = " . $pdo->quote($v),
    ];
}

// ── Τύπος απασχόλησης ───────────────────────────────────────────────────
$jobTypes = $pdo->query('SELECT DISTINCT job_type FROM job_listings WHERE ' . LIVE)->fetchAll(PDO::FETCH_COLUMN);
foreach (array_filter($jobTypes) as $v) {
    $checks[] = [
        'label' => "job_type=$v",
        'url'   => "/job-listings?job_type=$v",
        'sql'   => "SELECT COUNT(*) FROM job_listings WHERE " . LIVE . " AND job_type = " . $pdo->quote($v),
    ];
}

// ── Τύπος οχήματος ──────────────────────────────────────────────────────
//
// Ο έλεγχος ΠΡΕΠΕΙ να λάβει υπόψη τα παλιά συνώνυμα, αλλιώς θα κατηγορούσε
// το φίλτρο για τιμές που σωστά συμπεριλαμβάνει (`trailer` μαζί με
// `truck_articulated`).
foreach (\Drivejob\Helpers\VehicleTypes::codes() as $code) {
    $accepted = \Drivejob\Helpers\VehicleTypes::storedValuesFor($code);
    $quoted = implode(', ', array_map([$pdo, 'quote'], $accepted));

    $n = (int) $pdo->query("SELECT COUNT(*) FROM job_listings WHERE " . LIVE . " AND vehicle_type IN ($quoted)")->fetchColumn();

    if ($n === 0) {
        continue;   // δεν υπάρχουν τέτοιες αγγελίες — τίποτα να επαληθευτεί
    }

    $checks[] = [
        'label' => "vehicle_type=$code",
        'url'   => "/job-listings?vehicle_type=$code",
        'sql'   => "SELECT COUNT(*) FROM job_listings WHERE " . LIVE . " AND vehicle_type IN ($quoted)",
    ];
}

// ── Πιστοποιητικά ───────────────────────────────────────────────────────
$checks[] = [
    'label' => 'adr_certificate=1',
    'url'   => '/job-listings?adr_certificate=1',
    'sql'   => "SELECT COUNT(*) FROM job_listings WHERE " . LIVE . " AND adr_certificate = 1",
];
$checks[] = [
    'label' => 'operator_license=1',
    'url'   => '/job-listings?operator_license=1',
    'sql'   => "SELECT COUNT(*) FROM job_listings WHERE " . LIVE . " AND operator_license = 1",
];

// ── Τοποθεσία ───────────────────────────────────────────────────────────
$places = $pdo->query('SELECT DISTINCT location FROM job_listings WHERE ' . LIVE . ' AND location IS NOT NULL LIMIT 5')
              ->fetchAll(PDO::FETCH_COLUMN);

foreach ($places as $place) {
    // Η πρώτη λέξη — έτσι πληκτρολογεί ο χρήστης («Θεσσαλονίκη», όχι
    // «Θεσσαλονίκη, Ελλάδα»).
    $needle = trim(explode(',', (string) $place)[0]);
    if ($needle === '') {
        continue;
    }

    $checks[] = [
        'label' => "location=$needle",
        'url'   => '/job-listings?' . http_build_query(['location' => $needle]),
        'sql'   => "SELECT COUNT(*) FROM job_listings WHERE " . LIVE . " AND location LIKE " . $pdo->quote('%' . $needle . '%'),
    ];
}

// ── Συνδυασμοί ──────────────────────────────────────────────────────────
//
// Ένα φίλτρο μπορεί να δουλεύει μόνο του και να χάνεται όταν προστεθεί
// δεύτερο — αν οι συνθήκες ενώνονται λάθος.
$checks[] = [
    'label' => 'listing_type=job_offer + job_type=full_time',
    'url'   => '/job-listings?listing_type=job_offer&job_type=full_time',
    'sql'   => "SELECT COUNT(*) FROM job_listings WHERE " . LIVE . " AND listing_type = 'job_offer' AND job_type = 'full_time'",
];
$checks[] = [
    'label' => 'adr_certificate=1 + job_type=full_time',
    'url'   => '/job-listings?adr_certificate=1&job_type=full_time',
    'sql'   => "SELECT COUNT(*) FROM job_listings WHERE " . LIVE . " AND adr_certificate = 1 AND job_type = 'full_time'",
];

// ══════════════════════════════════════════════════════════════════════════

echo "\nΈλεγχος φίλτρων στο $base\n";
echo str_repeat('─', 72), "\n\n";

$fails = 0;
$unfiltered = (int) $pdo->query('SELECT COUNT(*) FROM job_listings WHERE ' . LIVE)->fetchColumn();

printf("Σύνολο ενεργών αγγελιών: %d\n\n", $unfiltered);

foreach ($checks as $check) {
    $expected = (int) $pdo->query($check['sql'])->fetchColumn();
    $actual = pageCount($base . $check['url']);

    if ($actual === null) {
        printf("%s✗ %-44s δεν διαβάστηκε το πλήθος%s\n", RED, $check['label'], OFF);
        $fails++;
        continue;
    }

    if ($actual !== $expected) {
        /*
         * Η ΠΙΟ ΣΥΧΝΗ ΑΠΟΤΥΧΙΑ: το φίλτρο αγνοείται.
         *
         * Όταν το πλήθος ισούται με το σύνολο, το φίλτρο δεν εφαρμόστηκε
         * καθόλου — δεν είναι απλή απόκλιση, είναι νεκρό φίλτρο.
         */
        $hint = ($actual === $unfiltered && $expected !== $unfiltered)
            ? ' ← ΤΟ ΦΙΛΤΡΟ ΑΓΝΟΕΙΤΑΙ'
            : '';

        printf("%s✗ %-44s σελίδα:%-5d βάση:%-5d%s%s\n", RED, $check['label'], $actual, $expected, $hint, OFF);
        $fails++;
        continue;
    }

    printf("%s✓%s %-44s %d\n", GRN, OFF, $check['label'], $actual);
}

echo "\n", str_repeat('─', 72), "\n";

if ($fails > 0) {
    printf("%sΑΠΕΤΥΧΑΝ %d ΑΠΟ %d ΕΛΕΓΧΟΥΣ%s\n\n", RED, $fails, count($checks), OFF);
    printf("%sΈλεγξε με αυτή τη σειρά:\n", DIM);
    printf("  1. Το όνομα της παραμέτρου στη φόρμα == αυτό που διαβάζει ο controller\n");
    printf("  2. Το κλειδί του κριτηρίου == αυτό που ελέγχει το searchListings\n");
    printf("  3. Το όνομα της στήλης στο SQL == αυτό που υπάρχει στον πίνακα%s\n\n", OFF);
    exit(1);
}

printf("%sΚαι τα %d φίλτρα φιλτράρουν%s\n\n", GRN, count($checks), OFF);

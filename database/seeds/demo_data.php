<?php

/**
 * Δοκιμαστικά δεδομένα για έλεγχο λειτουργικότητας.
 *
 * ΠΡΟΣΟΧΗ: αυτά ΔΕΝ είναι πραγματικά δεδομένα.
 *
 * Τα email είναι της μορφής info+odigos1@thessdrive.gr και
 * info+etairia1@thessdrive.gr — plus-addressing, ώστε κάθε ειδοποίηση του
 * συστήματος να καταλήγει πραγματικά στο info@thessdrive.gr και να μπορεί
 * να ελεγχθεί, ενώ ταυτόχρονα η αφαίρεση παραμένει μονοσήμαντη: σβήνονται
 * ΜΟΝΟ όσοι λογαριασμοί ταιριάζουν σε αυτά τα δύο μοτίβα. Ο πραγματικός
 * λογαριασμός info@thessdrive.gr δεν πιάνεται ποτέ.
 *
 * Χρήση:
 *   php database/seeds/demo_data.php --install   βάζει τα δεδομένα
 *   php database/seeds/demo_data.php --status    δείχνει τι υπάρχει
 *   php database/seeds/demo_data.php --remove    τα αφαιρεί όλα
 *
 * Το --install είναι idempotent: αν τρέξει δεύτερη φορά, πρώτα καθαρίζει
 * ό,τι είχε βάλει και το ξαναχτίζει από την αρχή.
 *
 * Κωδικός σύνδεσης για ΟΛΟΥΣ τους δοκιμαστικούς λογαριασμούς: Demo!2026drivejob
 */

require_once __DIR__ . '/../../src/bootstrap.php';

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Το script τρέχει από γραμμή εντολών. Ο global exception handler της
// εφαρμογής προσπαθεί να στείλει HTTP κεφαλίδες και κρύβει το πραγματικό
// σφάλμα πίσω από «headers already sent» — εδώ θέλουμε το σφάλμα καθαρό.
restore_exception_handler();
restore_error_handler();
set_exception_handler(function (Throwable $e): void {
    fwrite(STDERR, "\n❌ " . get_class($e) . ': ' . $e->getMessage() . "\n");
    fwrite(STDERR, '   ' . $e->getFile() . ':' . $e->getLine() . "\n\n");
    exit(1);
});

const DEMO_MAILBOX = 'info';
const DEMO_HOST = '@thessdrive.gr';
const DEMO_PASSWORD = 'Demo!2026drivejob';

/** Τα δύο μοτίβα που ορίζουν τι είναι δοκιμαστικό — και μόνο αυτά. */
const DEMO_PATTERNS = [
    DEMO_MAILBOX . '+odigos%' . DEMO_HOST,
    DEMO_MAILBOX . '+etairia%' . DEMO_HOST,
];

function driverEmail(int $n): string
{
    return DEMO_MAILBOX . '+odigos' . $n . DEMO_HOST;
}

function companyEmail(int $n): string
{
    return DEMO_MAILBOX . '+etairia' . $n . DEMO_HOST;
}

$mode = $argv[1] ?? '--status';

// ---------------------------------------------------------------- βοηθητικά

function insert(PDO $pdo, string $table, array $row): int
{
    $cols = array_keys($row);
    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s)',
        $table,
        implode(', ', $cols),
        implode(', ', array_map(fn($c) => ':' . $c, $cols))
    );
    $stmt = $pdo->prepare($sql);
    $stmt->execute($row);

    return (int) $pdo->lastInsertId();
}

/** Επιστρέφει τα ids των δοκιμαστικών εγγραφών ενός πίνακα. */
function demoIds(PDO $pdo, string $table): array
{
    $where = implode(' OR ', array_fill(0, count(DEMO_PATTERNS), 'email LIKE ?'));
    $stmt = $pdo->prepare("SELECT id FROM $table WHERE $where");
    $stmt->execute(DEMO_PATTERNS);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function demoStatus(PDO $pdo): array
{
    $companyIds = demoIds($pdo, 'companies');
    $driverIds = demoIds($pdo, 'drivers');

    $listings = 0;
    $apps = 0;

    if ($companyIds) {
        $in = implode(',', array_fill(0, count($companyIds), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_listings WHERE company_id IN ($in)");
        $stmt->execute($companyIds);
        $listings = (int) $stmt->fetchColumn();
    }

    if ($driverIds) {
        $in = implode(',', array_fill(0, count($driverIds), '?'));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_applications WHERE driver_id IN ($in)");
        $stmt->execute($driverIds);
        $apps = (int) $stmt->fetchColumn();
    }

    return [
        'εταιρείες' => count($companyIds),
        'οδηγοί'    => count($driverIds),
        'αγγελίες'  => $listings,
        'αιτήσεις'  => $apps,
    ];
}

function demoRemove(PDO $pdo): void
{
    $companyIds = demoIds($pdo, 'companies');
    $driverIds = demoIds($pdo, 'drivers');

    $listingIds = [];
    if ($companyIds) {
        $in = implode(',', array_fill(0, count($companyIds), '?'));
        $stmt = $pdo->prepare("SELECT id FROM job_listings WHERE company_id IN ($in)");
        $stmt->execute($companyIds);
        $listingIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $pdo->beginTransaction();
    try {
        if ($listingIds) {
            $in = implode(',', array_fill(0, count($listingIds), '?'));
            $pdo->prepare("DELETE FROM job_applications WHERE job_listing_id IN ($in)")->execute($listingIds);
            $pdo->prepare("DELETE FROM job_listing_vehicle_types WHERE job_listing_id IN ($in)")->execute($listingIds);
            $pdo->prepare("DELETE FROM job_listings WHERE id IN ($in)")->execute($listingIds);
        }

        if ($driverIds) {
            $in = implode(',', array_fill(0, count($driverIds), '?'));
            $pdo->prepare("DELETE FROM job_applications WHERE driver_id IN ($in)")->execute($driverIds);
            $pdo->prepare(
                "DELETE FROM driver_operator_sub_specialities
                 WHERE operator_license_id IN (
                     SELECT id FROM driver_operator_licenses WHERE driver_id IN ($in)
                 )"
            )->execute($driverIds);
            foreach (['driver_licenses', 'driver_adr_certificates', 'driver_tachograph_cards', 'driver_operator_licenses'] as $t) {
                $pdo->prepare("DELETE FROM $t WHERE driver_id IN ($in)")->execute($driverIds);
            }
            $pdo->prepare("DELETE FROM drivers WHERE id IN ($in)")->execute($driverIds);
        }

        if ($companyIds) {
            $in = implode(',', array_fill(0, count($companyIds), '?'));
            $pdo->prepare("DELETE FROM companies WHERE id IN ($in)")->execute($companyIds);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    // Επαναφορά των μετρητών αιτήσεων στις αγγελίες που έμειναν
    $pdo->exec(
        'UPDATE job_listings jl
         SET applications = (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_listing_id = jl.id)'
    );
}

// ---------------------------------------------------------------- δεδομένα

/**
 * Έξι εταιρείες με σαφώς διαφορετικό στόλο, ώστε το ταίριασμα να έχει κάτι
 * να διακρίνει: διεθνείς εμπορευματικές, αστικές διανομές, επιβατικές
 * υπεραστικές, μηχανήματα έργου, βυτιοφόρα καυσίμων, μεταφορές οχημάτων.
 */
function demoCompanies(): array
{
    return [
        1 => ['company_name' => 'Εταιρία 1', 'phone' => '2310555101', 'vat_number' => '900000001',
              'city' => 'Θεσσαλονίκη', 'address' => 'Θέρμη, 6ο χλμ. Θεσσαλονίκης–Μουδανιών',
              'contact_person' => 'Υπεύθυνος 1', 'position' => 'Υπεύθυνος Ανθρώπινου Δυναμικού',
              'fleet_size' => 45, 'operates_internationally' => 1, 'transport_types' => 'freight',
              'description' => 'ΔΟΚΙΜΑΣΤΙΚΗ ΕΓΓΡΑΦΗ. Διεθνείς εμπορευματικές μεταφορές, στόλος 45 συρμών, τακτικά δρομολόγια προς Βαλκάνια και Κεντρική Ευρώπη.'],

        2 => ['company_name' => 'Εταιρία 2', 'phone' => '2109887202', 'vat_number' => '900000002',
              'city' => 'Αθήνα', 'address' => 'Ελευσίνα, Λεωφ. Ν.Π.Ο. 22',
              'contact_person' => 'Υπεύθυνος 2', 'position' => 'Διευθυντής Λειτουργιών',
              'fleet_size' => 60, 'operates_internationally' => 0, 'transport_types' => 'freight',
              'description' => 'ΔΟΚΙΜΑΣΤΙΚΗ ΕΓΓΡΑΦΗ. Διανομή τελευταίου χιλιομέτρου στο λεκανοπέδιο, στόλος από βαν και ελαφρά φορτηγά, βάρδιες πρωί–απόγευμα.'],

        3 => ['company_name' => 'Εταιρία 3', 'phone' => '2651044303', 'vat_number' => '900000003',
              'city' => 'Ιωάννινα', 'address' => 'Ιωάννινα, Οδός Σταθμού 14',
              'contact_person' => 'Υπεύθυνος 3', 'position' => 'Υπεύθυνος Κίνησης',
              'fleet_size' => 28, 'operates_internationally' => 0, 'transport_types' => 'passenger',
              'description' => 'ΔΟΚΙΜΑΣΤΙΚΗ ΕΓΓΡΑΦΗ. Υπεραστικές γραμμές Ηπείρου και εκδρομικά δρομολόγια, στόλος λεωφορείων και μικρών πούλμαν.'],

        4 => ['company_name' => 'Εταιρία 4', 'phone' => '2810777404', 'vat_number' => '900000004',
              'city' => 'Ηράκλειο', 'address' => 'Ηράκλειο, ΒΙ.ΠΕ. Τμήμα Δ',
              'contact_person' => 'Υπεύθυνος 4', 'position' => 'Υπεύθυνος Έργων',
              'fleet_size' => 17, 'operates_internationally' => 0, 'transport_types' => 'machinery',
              'description' => 'ΔΟΚΙΜΑΣΤΙΚΗ ΕΓΓΡΑΦΗ. Χωματουργικά και τεχνικά έργα σε όλη την Κρήτη, ίδιος στόλος εκσκαφέων, φορτωτών και γερανοφόρων.'],

        5 => ['company_name' => 'Εταιρία 5', 'phone' => '2410666505', 'vat_number' => '900000005',
              'city' => 'Λάρισα', 'address' => 'Λάρισα, 3ο χλμ. Λαρίσης–Βόλου',
              'contact_person' => 'Υπεύθυνος 5', 'position' => 'Υπεύθυνος Ασφάλειας Μεταφορών',
              'fleet_size' => 32, 'operates_internationally' => 1, 'transport_types' => 'freight',
              'description' => 'ΔΟΚΙΜΑΣΤΙΚΗ ΕΓΓΡΑΦΗ. Διακίνηση καυσίμων και χημικών με βυτιοφόρα. Όλα τα δρομολόγια απαιτούν πιστοποίηση ADR και σύμβουλο ασφαλούς μεταφοράς.'],

        6 => ['company_name' => 'Εταιρία 6', 'phone' => '2610333606', 'vat_number' => '900000006',
              'city' => 'Πάτρα', 'address' => 'Πάτρα, Λιμάνι — Πύλη 3',
              'contact_person' => 'Υπεύθυνος 6', 'position' => 'Συντονιστής Στόλου',
              'fleet_size' => 21, 'operates_internationally' => 1, 'transport_types' => 'freight',
              'description' => 'ΔΟΚΙΜΑΣΤΙΚΗ ΕΓΓΡΑΦΗ. Μεταφορά οχημάτων με πλατφόρμες και εξυπηρέτηση λιμένα Πατρών. Συνδυασμός εθνικών και ακτοπλοϊκών δρομολογίων.'],
    ];
}

/**
 * Δεκαοκτώ οδηγοί που καλύπτουν σκόπιμα ΟΛΟ το φάσμα προσόντων:
 *
 *   Διπλώματα:      B, BE, C, CE, D, DE (με και χωρίς ΠΕΙ)
 *   ADR:            Π1 βασική, Π2 εκρηκτικά, Π3 ραδιενεργά, Π5 βυτία,
 *                   Π6 βυτία+εκρηκτικά, Π8 πλήρες
 *   Ταχογράφος:     με και χωρίς κάρτα
 *   Χειριστή:       ειδικότητες 1–8, με υποειδικότητες ομάδων Α και Β
 *
 * Τέσσερις έχουν πιστοποιητικά που λήγουν σε 10–60 ημέρες, ώστε να
 * ενεργοποιηθεί η εργασία ειδοποιήσεων λήξης (cron license-expiry) και να
 * φανεί αν φτάνουν πραγματικά τα email.
 */
function demoDrivers(): array
{
    return [
        1 => ['city' => 'Αθήνα', 'region' => 'Αττική', 'phone' => '6970000101',
              'experience_years' => 1, 'birth_date' => '2002-05-14', 'preferred_vehicle_type' => 'van',
              'preferred_job_type' => 'full_time', 'salary_min' => 900, 'salary_max' => 1100,
              'about_me' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΠΡΟΦΙΛ. Νέος οδηγός διανομών με βαν, χωρίς επαγγελματικό δίπλωμα ακόμη. Ψάχνω σταθερή θέση με προοπτική να βγάλω Γ κατηγορία.',
              'licenses' => [['B', 0, 1095]], 'adr' => [], 'tachograph' => false, 'operator' => []],

        2 => ['city' => 'Αθήνα', 'region' => 'Αττική', 'phone' => '6970000102',
              'experience_years' => 3, 'birth_date' => '1998-02-03', 'preferred_vehicle_type' => 'van',
              'preferred_job_type' => 'part_time', 'salary_min' => 950, 'salary_max' => 1200,
              'about_me' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΠΡΟΦΙΛ. Διανομές με βαν και μικρό ρυμουλκούμενο. Άνεση με φορητά τερματικά και πυκνό πρόγραμμα στάσεων.',
              'licenses' => [['B', 0, 1460], ['BE', 0, 1460]], 'adr' => [], 'tachograph' => false, 'operator' => []],

        3 => ['city' => 'Αθήνα', 'region' => 'Αττική', 'phone' => '6970000103',
              'experience_years' => 4, 'birth_date' => '1995-11-19', 'preferred_vehicle_type' => 'truck_light',
              'preferred_job_type' => 'full_time', 'salary_min' => 1100, 'salary_max' => 1400,
              'about_me' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΠΡΟΦΙΛ. Ανεφοδιασμός καταστημάτων με ελαφρύ φορτηγό. Πρωινές βάρδιες, καθημερινή επιστροφή στη βάση.',
              'licenses' => [['C', 1, 1095]], 'adr' => [], 'tachograph' => true, 'operator' => []],

        4 => ['city' => 'Θεσσαλονίκη', 'region' => 'Κεντρική Μακεδονία', 'phone' => '6970000104',
              'experience_years' => 8, 'birth_date' => '1989-09-27', 'preferred_vehicle_type' => 'truck_trailer',
              'preferred_job_type' => 'full_time', 'salary_min' => 1400, 'salary_max' => 1800,
              'about_me' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΠΡΟΦΙΛ. Οδηγός συρμού σε εθνικά και διεθνή δρομολόγια. Εμπειρία σε ψυγεία και παλετοποιημένα φορτία.',
              'licenses' => [['C', 1, 1095], ['CE', 1, 1095]], 'adr' => [], 'tachograph' => true, 'operator' => []],

        5 => ['city' => 'Θεσσαλονίκη', 'region' => 'Κεντρική Μακεδονία', 'phone' => '6970000105',
              'experience_years' => 11, 'birth_date' => '1986-04-11', 'preferred_vehicle_type' => 'truck_semi',
              'preferred_job_type' => 'full_time', 'salary_min' => 1450, 'salary_max' => 1850,
              'about_me' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΠΡΟΦΙΛ. Έντεκα χρόνια σε διεθνή δρομολόγια Βαλκανίων. Βασική πιστοποίηση ADR για συσκευασμένα επικίνδυνα φορτία.',
              'licenses' => [['C', 1, 1095], ['CE', 1, 1095]], 'adr' => [['Π1', 730]], 'tachograph' => true, 'operator' => []],

        6 => ['city' => 'Λάρισα', 'region' => 'Θεσσαλία', 'phone' => '6970000106',
              'experience_years' => 15, 'birth_date' => '1982-08-14', 'preferred_vehicle_type' => 'truck_tanker',
              'preferred_job_type' => 'full_time', 'salary_min' => 1700, 'salary_max' => 2200,
              'about_me' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΠΡΟΦΙΛ. Δεκαπέντε χρόνια σε βυτιοφόρα καυσίμων. Πιστοποίηση ADR με επέκταση δεξαμενών, τακτικά δρομολόγια διυλιστηρίων.',
              'licenses' => [['C', 1, 1460], ['CE', 1, 1460]], 'adr' => [['Π5', 730]], 'tachograph' => true, 'operator' => []],

        7 => ['city' => 'Λάρισα', 'region' => 'Θεσσαλία', 'phone' => '6970000107',
              'experience_years' => 19, 'birth_date' => '1978-01-08', 'preferred_vehicle_type' => 'truck_tanker',
              'preferred_job_type' => 'full_time', 'salary_min' => 1900, 'salary_max' => 2500,
              'about_me' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΠΡΟΦΙΛ. Ο πληρέστερα πιστοποιημένος του δείγματος: ADR Π8 (βυτία, εκρηκτικά και ραδιενεργά). Δεκαεννιά χρόνια σε φορτία υψηλού κινδύνου.',
              'licenses' => [['C', 1, 1460], ['CE', 1, 1460]], 'adr' => [['Π8', 1095]], 'tachograph' => true, 'operator' => []],

        8 => ['city' => 'Βόλος', 'region' => 'Θεσσαλία', 'phone' => '6970000108',
              'experience_years' => 13, 'birth_date' => '1984-06-30', 'preferred_vehicle_type' => 'truck_trailer',
              'preferred_job_type' => 'full_time', 'salary_min' => 1600, 'salary_max' => 2050,
              'about_me' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΠΡΟΦΙΛ. Μεταφορά εκρηκτικών υλών για λατομεία και τεχνικά έργα. Πιστοποίηση ADR κλάσης 1.',
              'licenses' => [['C', 1, 1095], ['CE', 1, 1095]], 'adr' => [['Π2', 545]], 'tachograph' => true, 'operator' => []],

        9 => ['city' => 'Χαλκίδα', 'region' => 'Στερεά Ελλάδα', 'phone' => '6970000109',
              'experience_years' => 9, 'birth_date' => '1990-03-22', 'preferred_vehicle_type' => 'truck_tanker',
              'preferred_job_type' => 'contract', 'salary_min' => 1800, 'salary_max' => 2400,
              'about_me' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΠΡΟΦΙΛ. Εξειδίκευση σε μεταφορά ραδιενεργών υλικών για νοσοκομεία και ερευνητικά κέντρα.',
              'licenses' => [['C', 1, 1095], ['CE', 1, 1095]], 'adr' => [['Π3', 400]], 'tachograph' => true, 'operator' => []],

        10 => ['city' => 'Ιωάννινα', 'region' => 'Ήπειρος', 'phone' => '6970000110',
               'experience_years' => 11, 'birth_date' => '1986-06-30', 'preferred_vehicle_type' => 'bus',
               'preferred_job_type' => 'full_time', 'salary_min' => 1300, 'salary_max' => 1650,
               'about_me' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΠΡΟΦΙΛ. Οδηγός υπεραστικού λεωφορείου. Εμπειρία σε ορεινά δρομολόγια Ηπείρου με χειμερινές συνθήκες.',
               'licenses' => [['D', 1, 1095]], 'adr' => [], 'tachograph' => true, 'operator' => []],

        11 => ['city' => 'Ιωάννινα', 'region' => 'Ήπειρος', 'phone' => '6970000111',
               'experience_years' => 20, 'birth_date' => '1976-01-08', 'preferred_vehicle_type' => 'bus',
               'preferred_job_type' => 'contract', 'salary_min' => 1500, 'salary_max' => 2000,
               'about_me' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΠΡΟΦΙΛ. Είκοσι χρόνια σε τουριστικά και εκδρομικά δρομολόγια. Άδεια D+E για πούλμαν με ρυμουλκούμενο αποσκευών.',
               'licenses' => [['D', 1, 1460], ['DE', 1, 1460]], 'adr' => [], 'tachograph' => true, 'operator' => []],

        12 => ['city' => 'Πάτρα', 'region' => 'Δυτική Ελλάδα', 'phone' => '6970000112',
               'experience_years' => 6, 'birth_date' => '1993-07-02', 'preferred_vehicle_type' => 'minibus',
               'preferred_job_type' => 'part_time', 'salary_min' => 800, 'salary_max' => 1100,
               'about_me' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΠΡΟΦΙΛ. Μεταφορά προσωπικού και σχολικά δρομολόγια με μίνι πούλμαν. Διαθέσιμη για μερική απασχόληση.',
               'licenses' => [['D', 1, 1095]], 'adr' => [], 'tachograph' => true, 'operator' => []],

        13 => ['city' => 'Ηράκλειο', 'region' => 'Κρήτη', 'phone' => '6970000113',
               'experience_years' => 9, 'birth_date' => '1991-03-22', 'preferred_vehicle_type' => 'machinery',
               'preferred_job_type' => 'full_time', 'salary_min' => 1250, 'salary_max' => 1600,
               'about_me' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΠΡΟΦΙΛ. Χειριστής εκσκαφέα και φορτωτή σε χωματουργικά έργα. Εμπειρία σε εργοτάξια με στενά περιθώρια.',
               'licenses' => [['B', 0, 1460], ['C', 1, 1460]], 'adr' => [], 'tachograph' => false,
               'operator' => [['1', 1825, ['1.1', '1.3'], 'A']]],

        14 => ['city' => 'Ηράκλειο', 'region' => 'Κρήτη', 'phone' => '6970000114',
               'experience_years' => 14, 'birth_date' => '1983-10-05', 'preferred_vehicle_type' => 'machinery',
               'preferred_job_type' => 'full_time', 'salary_min' => 1500, 'salary_max' => 1900,
               'about_me' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΠΡΟΦΙΛ. Χειριστής γερανού και καλαθοφόρου. Συνδυάζω τον χειρισμό με οδήγηση οχήματος μεταφοράς μηχανημάτων.',
               'licenses' => [['C', 1, 1460], ['CE', 1, 1460]], 'adr' => [], 'tachograph' => true,
               'operator' => [['2', 1825, ['2.1', '2.2'], 'A'], ['8', 1825, ['8.1'], 'B']]],

        15 => ['city' => 'Θεσσαλονίκη', 'region' => 'Κεντρική Μακεδονία', 'phone' => '6970000115',
               'experience_years' => 7, 'birth_date' => '1992-12-11', 'preferred_vehicle_type' => 'machinery',
               'preferred_job_type' => 'contract', 'salary_min' => 1300, 'salary_max' => 1700,
               'about_me' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΠΡΟΦΙΛ. Οδοστρωσία και ασφαλτικά. Χειρισμός οδοστρωτήρα, διαστρωτήρα και ισοπεδωτή.',
               'licenses' => [['B', 0, 1095], ['C', 1, 1095]], 'adr' => [], 'tachograph' => false,
               'operator' => [['3', 1460, ['3.1', '3.2'], 'A']]],

        16 => ['city' => 'Καβάλα', 'region' => 'Ανατολική Μακεδονία και Θράκη', 'phone' => '6970000116',
               'experience_years' => 17, 'birth_date' => '1980-09-18', 'preferred_vehicle_type' => 'machinery',
               'preferred_job_type' => 'full_time', 'salary_min' => 1600, 'salary_max' => 2100,
               'about_me' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΠΡΟΦΙΛ. Υπόγεια έργα και διάτρηση. Δύο ειδικότητες χειριστή και εμπειρία σε σήραγγες.',
               'licenses' => [['B', 0, 1460], ['C', 1, 1460]], 'adr' => [], 'tachograph' => false,
               'operator' => [['5', 1825, ['5.1'], 'A'], ['7', 1825, ['7.1', '7.2'], 'B']]],

        // --- Τέσσερις με πιστοποιητικά που λήγουν σύντομα (έλεγχος ειδοποιήσεων) ---

        17 => ['city' => 'Σέρρες', 'region' => 'Κεντρική Μακεδονία', 'phone' => '6970000117',
               'experience_years' => 10, 'birth_date' => '1987-02-25', 'preferred_vehicle_type' => 'truck_trailer',
               'preferred_job_type' => 'full_time', 'salary_min' => 1350, 'salary_max' => 1700,
               'about_me' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΠΡΟΦΙΛ. Το δίπλωμα και το ΠΕΙ λήγουν μέσα στον επόμενο μήνα — χρησιμεύει για τον έλεγχο των ειδοποιήσεων λήξης.',
               'licenses' => [['C', 1, 25], ['CE', 1, 25]], 'adr' => [], 'tachograph' => true, 'operator' => []],

        18 => ['city' => 'Κοζάνη', 'region' => 'Δυτική Μακεδονία', 'phone' => '6970000118',
               'experience_years' => 12, 'birth_date' => '1985-05-30', 'preferred_vehicle_type' => 'truck_tanker',
               'preferred_job_type' => 'full_time', 'salary_min' => 1550, 'salary_max' => 2000,
               'about_me' => 'ΔΟΚΙΜΑΣΤΙΚΟ ΠΡΟΦΙΛ. Το ADR λήγει σε δέκα ημέρες και η κάρτα ταχογράφου σε δύο μήνες — χρησιμεύει για τον έλεγχο των ειδοποιήσεων λήξης.',
               'licenses' => [['C', 1, 900], ['CE', 1, 900]], 'adr' => [['Π6', 10]], 'tachograph' => true,
               'tachograph_days' => 60, 'operator' => [['2', 45, ['2.1'], 'A']]],
    ];
}

/**
 * Δεκατέσσερις αγγελίες. Κάθε μία δηλώνει ρητά τους τύπους οχημάτων της,
 * ώστε να γεμίζει και ο πίνακας job_listing_vehicle_types (η μοναδική
 * έγκυρη πηγή αλήθειας για τα οχήματα μιας αγγελίας).
 */
function demoListings(): array
{
    return [
        ['company' => 1, 'title' => 'Οδηγός συρμού — γραμμή Θεσσαλονίκη–Σόφια',
         'transport_type' => 'freight', 'job_type' => 'full_time', 'required_license' => 'CE',
         'location' => 'Θεσσαλονίκη', 'salary_min' => 1450, 'salary_max' => 1800, 'salary_type' => 'monthly',
         'experience_years' => 3, 'adr_certificate' => 0, 'operator_license' => 0, 'is_urgent' => 0,
         'vehicles' => ['truck_trailer', 'truck_semi'],
         'description' => 'Σταθερή γραμμή με δύο δρομολόγια την εβδομάδα. Παλετοποιημένα φορτία, φόρτωση και εκφόρτωση με ευθύνη του πελάτη.',
         'benefits' => 'Σταθερό ωράριο, επιστροφή στη βάση κάθε Παρασκευή, ιδιωτική ασφάλιση.',
         'requirements' => 'Δίπλωμα CE, ΠΕΙ, κάρτα ταχογράφου, εμπειρία σε διασυνοριακά δρομολόγια.'],

        ['company' => 1, 'title' => 'Οδηγός ψυγείου — εθνικά δρομολόγια',
         'transport_type' => 'freight', 'job_type' => 'full_time', 'required_license' => 'CE',
         'location' => 'Θεσσαλονίκη', 'salary_min' => 1350, 'salary_max' => 1650, 'salary_type' => 'monthly',
         'experience_years' => 2, 'adr_certificate' => 0, 'operator_license' => 0, 'is_urgent' => 0,
         'vehicles' => ['truck_refrigerated', 'truck_trailer'],
         'description' => 'Μεταφορά νωπών προϊόντων σε Αττική και Θεσσαλία. Απαιτείται προσοχή στην ψυκτική αλυσίδα και στα παραστατικά.',
         'benefits' => 'Εκτός έδρας, μηνιαίο bonus συνέπειας.',
         'requirements' => 'Δίπλωμα CE, ΠΕΙ, κάρτα ταχογράφου.'],

        ['company' => 1, 'title' => 'Οδηγός με ADR — συσκευασμένα επικίνδυνα φορτία',
         'transport_type' => 'freight', 'job_type' => 'full_time', 'required_license' => 'CE',
         'location' => 'Θεσσαλονίκη', 'salary_min' => 1550, 'salary_max' => 1950, 'salary_type' => 'monthly',
         'experience_years' => 5, 'adr_certificate' => 1, 'operator_license' => 0, 'is_urgent' => 0,
         'vehicles' => ['truck_trailer', 'truck_semi'],
         'description' => 'Μεταφορά συσκευασμένων χημικών προς βιομηχανικούς πελάτες. Απαιτείται βασική πιστοποίηση ADR σε ισχύ.',
         'benefits' => 'Επίδομα επικινδυνότητας, ετήσια επιμόρφωση με έξοδα εταιρείας.',
         'requirements' => 'Δίπλωμα CE, ΠΕΙ, ADR βάσης, κάρτα ταχογράφου.'],

        ['company' => 2, 'title' => 'Οδηγός βαν — διανομές Αττικής',
         'transport_type' => 'freight', 'job_type' => 'full_time', 'required_license' => 'B',
         'location' => 'Αθήνα', 'salary_min' => 950, 'salary_max' => 1200, 'salary_type' => 'monthly',
         'experience_years' => 0, 'adr_certificate' => 0, 'operator_license' => 0, 'is_urgent' => 1,
         'vehicles' => ['van'],
         'description' => 'Καθημερινές διανομές σε προκαθορισμένο δρομολόγιο εντός λεκανοπεδίου. Παρέχεται εκπαίδευση στο σύστημα δρομολόγησης.',
         'benefits' => 'Επιστροφή στη βάση κάθε βράδυ, κουπόνια γεύματος.',
         'requirements' => 'Δίπλωμα Β, γνώση του λεκανοπεδίου, ευχέρεια με κινητές συσκευές.'],

        ['company' => 2, 'title' => 'Οδηγός ελαφρού φορτηγού — πρωινή βάρδια',
         'transport_type' => 'freight', 'job_type' => 'full_time', 'required_license' => 'C',
         'location' => 'Αθήνα', 'salary_min' => 1150, 'salary_max' => 1400, 'salary_type' => 'monthly',
         'experience_years' => 2, 'adr_certificate' => 0, 'operator_license' => 0, 'is_urgent' => 0,
         'vehicles' => ['truck_light', 'truck_medium'],
         'description' => 'Ανεφοδιασμός καταστημάτων από το κέντρο διανομής Ελευσίνας. Βάρδια 06:00–14:00.',
         'benefits' => 'Σταθερή βάρδια, ιδιωτική ασφάλιση μετά τον πρώτο χρόνο.',
         'requirements' => 'Δίπλωμα Γ, ΠΕΙ, κάρτα ταχογράφου.'],

        ['company' => 2, 'title' => 'Οδηγός διανομών — μερική απασχόληση Σαββατοκύριακο',
         'transport_type' => 'freight', 'job_type' => 'part_time', 'required_license' => 'B',
         'location' => 'Αθήνα', 'salary_min' => 550, 'salary_max' => 700, 'salary_type' => 'monthly',
         'experience_years' => 0, 'adr_certificate' => 0, 'operator_license' => 0, 'is_urgent' => 0,
         'vehicles' => ['van'],
         'description' => 'Δρομολόγια Σάββατο και Κυριακή, 8 ώρες ημερησίως. Κατάλληλο για συμπλήρωση εισοδήματος.',
         'benefits' => 'Ευέλικτη έναρξη βάρδιας.',
         'requirements' => 'Δίπλωμα Β, διαθεσιμότητα και τα δύο ημερήσια του Σαββατοκύριακου.'],

        ['company' => 3, 'title' => 'Οδηγός υπεραστικού λεωφορείου',
         'transport_type' => 'passenger', 'job_type' => 'full_time', 'required_license' => 'D',
         'location' => 'Ιωάννινα', 'salary_min' => 1300, 'salary_max' => 1600, 'salary_type' => 'monthly',
         'experience_years' => 3, 'adr_certificate' => 0, 'operator_license' => 0, 'is_urgent' => 0,
         'vehicles' => ['bus'],
         'description' => 'Τακτικές γραμμές Ιωάννινα–Άρτα–Πρέβεζα. Απαιτείται άνεση στην εξυπηρέτηση επιβατών και τήρηση δρομολογίου.',
         'benefits' => 'Πρόγραμμα βαρδιών ένα μήνα πριν, ασφάλιση.',
         'requirements' => 'Δίπλωμα Δ, ΠΕΙ επιβατικών, κάρτα ταχογράφου, καθαρό μητρώο οδήγησης.'],

        ['company' => 3, 'title' => 'Οδηγός πούλμαν — εκδρομικά δρομολόγια',
         'transport_type' => 'passenger', 'job_type' => 'contract', 'required_license' => 'D',
         'location' => 'Ιωάννινα', 'salary_min' => 1500, 'salary_max' => 2000, 'salary_type' => 'monthly',
         'experience_years' => 8, 'adr_certificate' => 0, 'operator_license' => 0, 'is_urgent' => 0,
         'vehicles' => ['bus', 'minibus'],
         'description' => 'Πολυήμερες εκδρομές εντός Ελλάδας με τουριστικά γραφεία. Σύμβαση έργου ανά σεζόν.',
         'benefits' => 'Διαμονή και διατροφή στα πολυήμερα, φιλοδωρήματα.',
         'requirements' => 'Δίπλωμα Δ, ΠΕΙ, εμπειρία σε τουριστικά, καλή γνώση αγγλικών.'],

        ['company' => 3, 'title' => 'Οδηγός μίνι πούλμαν — μεταφορά προσωπικού',
         'transport_type' => 'passenger', 'job_type' => 'part_time', 'required_license' => 'D',
         'location' => 'Πάτρα', 'salary_min' => 750, 'salary_max' => 1000, 'salary_type' => 'monthly',
         'experience_years' => 2, 'adr_certificate' => 0, 'operator_license' => 0, 'is_urgent' => 0,
         'vehicles' => ['minibus', 'van'],
         'description' => 'Πρωινή και απογευματινή μεταφορά προσωπικού εργοστασίου. Σταθερή διαδρομή, δύο βάρδιες την ημέρα.',
         'benefits' => 'Σύντομες βάρδιες με μεγάλο διάλειμμα, όχημα στη βάση.',
         'requirements' => 'Δίπλωμα Δ, ΠΕΙ, υπομονή στην εξυπηρέτηση επιβατών.'],

        ['company' => 4, 'title' => 'Χειριστής εκσκαφέα — έργα Ηρακλείου',
         'transport_type' => 'machinery', 'job_type' => 'full_time', 'required_license' => 'B',
         'location' => 'Ηράκλειο', 'salary_min' => 1250, 'salary_max' => 1550, 'salary_type' => 'monthly',
         'experience_years' => 5, 'adr_certificate' => 0, 'operator_license' => 1, 'is_urgent' => 1,
         'vehicles' => ['machinery'],
         'description' => 'Χωματουργικές εργασίες σε ιδιωτικά και δημόσια έργα. Εργοτάξια εντός νομού Ηρακλείου, καθημερινή επιστροφή.',
         'benefits' => 'Μεταφορά στο εργοτάξιο, υπερωρίες πληρωμένες.',
         'requirements' => 'Άδεια χειριστή μηχανημάτων έργου ειδικότητας 1, εμπειρία σε ερπυστριοφόρο εκσκαφέα.'],

        ['company' => 4, 'title' => 'Χειριστής γερανοφόρου — μεταφορά βαρέων',
         'transport_type' => 'machinery', 'job_type' => 'full_time', 'required_license' => 'CE',
         'location' => 'Ηράκλειο', 'salary_min' => 1500, 'salary_max' => 1900, 'salary_type' => 'monthly',
         'experience_years' => 7, 'adr_certificate' => 0, 'operator_license' => 1, 'is_urgent' => 0,
         'vehicles' => ['machinery', 'truck_trailer'],
         'description' => 'Συνδυασμένος ρόλος: οδήγηση οχήματος μεταφοράς και χειρισμός γερανού για φόρτωση μηχανημάτων.',
         'benefits' => 'Σταθερή απασχόληση όλο τον χρόνο, εταιρικό όχημα εκτός βάρδιας.',
         'requirements' => 'Δίπλωμα CE, άδεια χειριστή ειδικότητας 2, εμπειρία σε ασυνήθιστα φορτία.'],

        ['company' => 5, 'title' => 'Οδηγός βυτιοφόρου καυσίμων — ADR δεξαμενών',
         'transport_type' => 'freight', 'job_type' => 'full_time', 'required_license' => 'CE',
         'location' => 'Λάρισα', 'salary_min' => 1700, 'salary_max' => 2200, 'salary_type' => 'monthly',
         'experience_years' => 5, 'adr_certificate' => 1, 'operator_license' => 0, 'is_urgent' => 1,
         'vehicles' => ['truck_tanker', 'truck_semi'],
         'description' => 'Διανομή καυσίμων σε πρατήρια Θεσσαλίας και Μακεδονίας. Απαιτείται ADR με επέκταση δεξαμενών και κάρτα ταχογράφου σε ισχύ.',
         'benefits' => 'Ασφάλιση ζωής, επίδομα επικινδυνότητας, bonus παραγωγικότητας.',
         'requirements' => 'Δίπλωμα CE, ΠΕΙ, ADR βάσης και δεξαμενών, τουλάχιστον 5 έτη σε βυτιοφόρα.'],

        ['company' => 5, 'title' => 'Οδηγός ADR κλάσης 1 — μεταφορά εκρηκτικών',
         'transport_type' => 'freight', 'job_type' => 'contract', 'required_license' => 'CE',
         'location' => 'Λάρισα', 'salary_min' => 2000, 'salary_max' => 2600, 'salary_type' => 'monthly',
         'experience_years' => 10, 'adr_certificate' => 1, 'operator_license' => 0, 'is_urgent' => 0,
         'vehicles' => ['truck_semi', 'truck_trailer'],
         'description' => 'Εφοδιασμός λατομείων και τεχνικών έργων με εκρηκτικές ύλες. Αυστηρά πρωτόκολλα ασφαλείας και συνοδεία.',
         'benefits' => 'Υψηλότερη αμοιβή του κλάδου, πλήρης κάλυψη εξόδων μετακίνησης.',
         'requirements' => 'Δίπλωμα CE, ΠΕΙ, ADR κλάσης 1, λευκό ποινικό μητρώο, δεκαετής εμπειρία.'],

        ['company' => 6, 'title' => 'Οδηγός πλατφόρμας — μεταφορά οχημάτων',
         'transport_type' => 'freight', 'job_type' => 'full_time', 'required_license' => 'CE',
         'location' => 'Πάτρα', 'salary_min' => 1400, 'salary_max' => 1750, 'salary_type' => 'monthly',
         'experience_years' => 4, 'adr_certificate' => 0, 'operator_license' => 0, 'is_urgent' => 0,
         'vehicles' => ['truck_articulated', 'truck_trailer'],
         'description' => 'Μεταφορά καινούριων και μεταχειρισμένων οχημάτων από το λιμάνι Πατρών προς αντιπροσωπείες. Απαιτείται προσοχή στη φόρτωση.',
         'benefits' => 'Δρομολόγια εντός Ελλάδας, σαββατοκύριακα ελεύθερα.',
         'requirements' => 'Δίπλωμα CE, ΠΕΙ, κάρτα ταχογράφου, εμπειρία σε φόρτωση οχημάτων σε πλατφόρμα.'],
    ];
}

/**
 * Αιτήσεις σε όλες τις καταστάσεις του enum, με ρεαλιστική κατανομή:
 * κάποιες αγγελίες συγκεντρώνουν πολλές, άλλες καμία.
 */
function demoApplications(): array
{
    return [
        ['driver' => 4,  'listing' => 0,  'status' => 'pending',     'message' => 'Δουλεύω ήδη σε παρόμοια γραμμή και ψάχνω σταθερότερο πρόγραμμα με επιστροφή κάθε Παρασκευή.'],
        ['driver' => 5,  'listing' => 0,  'status' => 'viewed',      'message' => 'Έντεκα χρόνια στα Βαλκάνια, γνωρίζω καλά τα τελωνειακά σημεία της διαδρομής προς Σόφια.'],
        ['driver' => 17, 'listing' => 0,  'status' => 'rejected',    'message' => 'Ενδιαφέρομαι για τη θέση. Σημειώνω ότι ανανεώνω το ΠΕΙ μου τον επόμενο μήνα.'],

        ['driver' => 4,  'listing' => 1,  'status' => 'shortlisted', 'message' => 'Έχω πολλά χρόνια σε ψυγεία και γνωρίζω τις απαιτήσεις της ψυκτικής αλυσίδας.'],

        ['driver' => 5,  'listing' => 2,  'status' => 'pending',     'message' => 'Έχω ADR βάσης σε ισχύ μέχρι το 2028 και εμπειρία σε συσκευασμένα χημικά.'],
        ['driver' => 7,  'listing' => 2,  'status' => 'shortlisted', 'message' => 'Διαθέτω πλήρη πιστοποίηση ADR — καλύπτω και τις τρεις κατηγορίες που ζητούνται.'],

        ['driver' => 1,  'listing' => 3,  'status' => 'hired',       'message' => 'Ξεκίνησα πέρσι στις διανομές και γνωρίζω πολύ καλά τα βόρεια προάστια. Είμαι διαθέσιμος άμεσα.'],
        ['driver' => 2,  'listing' => 3,  'status' => 'rejected',    'message' => 'Ενδιαφέρομαι για τη θέση, έχω τρία χρόνια σε διανομές με βαν.'],

        ['driver' => 3,  'listing' => 4,  'status' => 'pending',     'message' => 'Η πρωινή βάρδια μου ταιριάζει απόλυτα. Έχω τέσσερα χρόνια σε ανεφοδιασμό καταστημάτων.'],

        ['driver' => 2,  'listing' => 5,  'status' => 'pending',     'message' => 'Τα Σαββατοκύριακα μού ταιριάζουν ως συμπληρωματική απασχόληση.'],

        ['driver' => 10, 'listing' => 6,  'status' => 'pending',     'message' => 'Κάνω τη γραμμή Ιωάννινα–Άρτα εννιά χρόνια. Γνωρίζω τα ορεινά τμήματα και σε χειμερινές συνθήκες.'],
        ['driver' => 11, 'listing' => 6,  'status' => 'viewed',      'message' => 'Είκοσι χρόνια στο τιμόνι λεωφορείου. Διαθέσιμος για βάρδιες όλη την εβδομάδα.'],

        ['driver' => 11, 'listing' => 7,  'status' => 'shortlisted', 'message' => 'Είκοσι χρόνια σε εκδρομικά, με D+E για ρυμουλκούμενο αποσκευών. Διαθέσιμος για όλη τη σεζόν.'],

        ['driver' => 12, 'listing' => 8,  'status' => 'pending',     'message' => 'Κάνω ήδη σχολικά δρομολόγια στην Πάτρα και οι ώρες μού ταιριάζουν.'],

        ['driver' => 13, 'listing' => 9,  'status' => 'pending',     'message' => 'Χειρίζομαι εκσκαφέα και φορτωτή εννιά χρόνια, κυρίως σε έργα εντός νομού. Άδεια σε ισχύ.'],
        ['driver' => 16, 'listing' => 9,  'status' => 'withdrawn',   'message' => 'Είχα ενδιαφερθεί αλλά δεσμεύτηκα σε άλλο έργο — αποσύρω την αίτηση.'],

        ['driver' => 14, 'listing' => 10, 'status' => 'shortlisted', 'message' => 'Έχω και τα δύο προσόντα: δίπλωμα CE και άδεια χειριστή ειδικότητας 2 για γερανό.'],

        ['driver' => 6,  'listing' => 11, 'status' => 'pending',     'message' => 'Δεκαπέντε χρόνια σε βυτιοφόρα καυσίμων, ADR δεξαμενών σε ισχύ. Γνωρίζω τα πρατήρια της Θεσσαλίας.'],
        ['driver' => 7,  'listing' => 11, 'status' => 'viewed',      'message' => 'Διαθέτω ADR Π8 και δεκαεννιά χρόνια σε φορτία υψηλού κινδύνου.'],
        ['driver' => 18, 'listing' => 11, 'status' => 'pending',     'message' => 'Έχω ADR βυτίων και εκρηκτικών. Το πιστοποιητικό μου ανανεώνεται μέσα στον μήνα.'],

        ['driver' => 8,  'listing' => 12, 'status' => 'shortlisted', 'message' => 'Μεταφέρω εκρηκτικές ύλες για λατομεία εδώ και χρόνια, με πιστοποίηση κλάσης 1 σε ισχύ.'],
        ['driver' => 7,  'listing' => 12, 'status' => 'hired',       'message' => 'Καλύπτω και τις τρεις κατηγορίες ADR και έχω τη δεκαετή εμπειρία που ζητάτε.'],

        ['driver' => 4,  'listing' => 13, 'status' => 'pending',     'message' => 'Ενδιαφέρομαι για τη μεταφορά οχημάτων — έχω εμπειρία σε φόρτωση σε πλατφόρμα.'],
    ];
}

// ---------------------------------------------------------------- εκτέλεση

function demoInstall(PDO $pdo): void
{
    $hash = password_hash(DEMO_PASSWORD, PASSWORD_DEFAULT);
    $now = date('Y-m-d H:i:s');
    $expires = date('Y-m-d H:i:s', strtotime('+90 days'));

    $companyIds = [];
    foreach (demoCompanies() as $n => $c) {
        $companyIds[$n] = insert($pdo, 'companies', [
            'email' => companyEmail($n),
            'password' => $hash,
            'company_name' => $c['company_name'],
            'phone' => $c['phone'],
            'vat_number' => $c['vat_number'],
            'address' => $c['address'],
            'city' => $c['city'],
            'country' => 'Ελλάδα',
            'description' => $c['description'],
            'contact_person' => $c['contact_person'],
            'position' => $c['position'],
            'fleet_size' => $c['fleet_size'],
            'operates_internationally' => $c['operates_internationally'],
            'transport_types' => $c['transport_types'],
            'is_verified' => 1,
            'is_active' => 1,
            'status' => 'active',
            'role_id' => 3,
            'terms_accepted_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
    printf("✅ εταιρείες: %d\n", count($companyIds));

    $driverIds = [];
    foreach (demoDrivers() as $n => $d) {
        $driverIds[$n] = $id = insert($pdo, 'drivers', [
            'email' => driverEmail($n),
            'password' => $hash,
            'first_name' => 'Οδηγός',
            'last_name' => (string) $n,
            'phone' => $d['phone'],
            'city' => $d['city'],
            'region' => $d['region'],
            'country' => 'Ελλάδα',
            'birth_date' => $d['birth_date'],
            'about_me' => $d['about_me'],
            'experience_years' => $d['experience_years'],
            'preferred_job_type' => $d['preferred_job_type'],
            'preferred_vehicle_type' => $d['preferred_vehicle_type'],
            'salary_min' => $d['salary_min'],
            'salary_max' => $d['salary_max'],
            'salary_period' => 'monthly',
            'available_for_work' => 1,
            'is_available' => 1,
            'driving_license' => 1,
            'adr_certificate' => $d['adr'] ? 1 : 0,
            'operator_license' => $d['operator'] ? 1 : 0,
            'is_verified' => 1,
            'is_active' => 1,
            'status' => 'active',
            'role_id' => 2,
            'language_greek' => 'native',
            'terms_accepted_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Κάθε πιστοποιητικό φέρει τη δική του απόσταση λήξης σε ημέρες,
        // ώστε κάποιοι οδηγοί να έχουν πιστοποιητικά που λήγουν σύντομα και
        // να ενεργοποιείται η εργασία ειδοποιήσεων λήξης.
        foreach ($d['licenses'] as [$type, $hasPei, $days]) {
            $expiry = date('Y-m-d', strtotime("+$days days"));
            insert($pdo, 'driver_licenses', [
                'driver_id' => $id,
                'license_type' => $type,
                'license_number' => 'DEMO-' . $type . '-' . $id,
                'has_pei' => $hasPei,
                'expiry_date' => $expiry,
                'license_document_expiry' => $expiry,
                'pei_expiry_c' => $hasPei && $type[0] === 'C' ? $expiry : null,
                'pei_expiry_d' => $hasPei && $type[0] === 'D' ? $expiry : null,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($d['adr'] as [$adrType, $days]) {
            insert($pdo, 'driver_adr_certificates', [
                'driver_id' => $id,
                'adr_type' => $adrType,
                'certificate_number' => 'DEMO-ADR-' . $id,
                'expiry_date' => date('Y-m-d', strtotime("+$days days")),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if ($d['tachograph']) {
            $days = $d['tachograph_days'] ?? 1460;
            insert($pdo, 'driver_tachograph_cards', [
                'driver_id' => $id,
                'card_number' => 'DEMO' . str_pad((string) $id, 6, '0', STR_PAD_LEFT),
                'expiry_date' => date('Y-m-d', strtotime("+$days days")),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($d['operator'] as [$speciality, $days, $subs, $group]) {
            $licenseId = insert($pdo, 'driver_operator_licenses', [
                'driver_id' => $id,
                'speciality' => $speciality,
                'license_number' => 'DEMO-XE-' . $id . '-' . $speciality,
                'expiry_date' => date('Y-m-d', strtotime("+$days days")),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($subs as $sub) {
                insert($pdo, 'driver_operator_sub_specialities', [
                    'operator_license_id' => $licenseId,
                    'sub_speciality' => $sub,
                    'group_type' => $group,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
    printf("✅ οδηγοί: %d (με διπλώματα, ADR, ταχογράφους, άδειες χειριστή)\n", count($driverIds));

    $companies = demoCompanies();
    $listingIds = [];
    foreach (demoListings() as $i => $l) {
        $listingIds[$i] = $id = insert($pdo, 'job_listings', [
            'title' => $l['title'],
            'company_id' => $companyIds[$l['company']],
            'listing_type' => 'job_offer',
            'transport_type' => $l['transport_type'],
            'job_type' => $l['job_type'],
            'required_license' => $l['required_license'],
            'description' => $l['description'],
            'requirements' => $l['requirements'],
            'benefits' => $l['benefits'],
            'salary_min' => $l['salary_min'],
            'salary_max' => $l['salary_max'],
            'salary_type' => $l['salary_type'],
            'location' => $l['location'] . ', Ελλάδα',
            'experience_years' => $l['experience_years'],
            'min_experience' => $l['experience_years'],
            'adr_certificate' => $l['adr_certificate'],
            'operator_license' => $l['operator_license'],
            'contact_email' => companyEmail($l['company']),
            'contact_phone' => $companies[$l['company']]['phone'],
            'is_urgent' => $l['is_urgent'],
            'is_active' => 1,
            'is_approved' => 1,
            'status' => 'active',
            'employment_type' => $l['job_type'],
            'vehicle_type' => $l['vehicles'][0],
            'views_count' => 0,
            'applications' => 0,
            'created_at' => $now,
            'updated_at' => $now,
            'expires_at' => $expires,
        ]);

        foreach ($l['vehicles'] as $v) {
            insert($pdo, 'job_listing_vehicle_types', [
                'job_listing_id' => $id,
                'vehicle_type' => $v,
            ]);
        }
    }
    printf("✅ αγγελίες: %d (με τύπους οχημάτων)\n", count($listingIds));

    $count = 0;
    foreach (demoApplications() as $a) {
        insert($pdo, 'job_applications', [
            'driver_id' => $driverIds[$a['driver']],
            'job_listing_id' => $listingIds[$a['listing']],
            'message' => $a['message'],
            'status' => $a['status'],
            'created_at' => date('Y-m-d H:i:s', strtotime(sprintf('-%d days -%d hours', $count + 1, ($count * 7) % 24))),
            'updated_at' => $now,
        ]);
        $count++;
    }

    $pdo->exec(
        'UPDATE job_listings jl
         SET applications = (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_listing_id = jl.id)'
    );
    printf("✅ αιτήσεις: %d (σε όλες τις καταστάσεις)\n", $count);
}

// ---------------------------------------------------------------- κύριο

switch ($mode) {
    case '--install':
        echo "🧹 Καθαρισμός τυχόν προηγούμενων δοκιμαστικών δεδομένων...\n";
        demoRemove($pdo);
        echo "📥 Εισαγωγή δοκιμαστικών δεδομένων...\n\n";
        demoInstall($pdo);
        echo "\n🔑 Κωδικός για όλους: " . DEMO_PASSWORD . "\n";
        echo "   Εταιρείες: " . companyEmail(1) . " … " . companyEmail(count(demoCompanies())) . "\n";
        echo "   Οδηγοί:    " . driverEmail(1) . " … " . driverEmail(count(demoDrivers())) . "\n\n";
        break;

    case '--remove':
        echo "🧹 Αφαίρεση δοκιμαστικών δεδομένων...\n";
        demoRemove($pdo);
        echo "🟢 Ολοκληρώθηκε.\n\n";
        break;

    case '--status':
    default:
        echo "Δοκιμαστικά δεδομένα στη βάση\n";
        echo "Κριτήριο: " . implode(' | ', DEMO_PATTERNS) . "\n\n";
        break;
}

foreach (demoStatus($pdo) as $label => $n) {
    printf("  %-12s %d\n", $label . ':', $n);
}

if ($mode === '--status') {
    echo "\nΕντολές: --install  |  --remove\n";
}

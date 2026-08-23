<?php

/**
 * Εξυγίανση των πεδίων του πίνακα job_listings.
 *
 * ΓΙΑΤΙ: η δημιουργία και η διαχείριση αγγελιών ήταν σπασμένες από έξι
 * ανεξάρτητα σημεία. Αυτό το migration καλύπτει τα δύο που αφορούν το σχήμα:
 *
 *   1. Τέσσερα πεδία που η φόρμα και ο controller στέλνουν αλλά δεν υπάρχουν
 *      ως στήλες: requires_pei, requires_tachograph, job_category,
 *      additional_info. Όσο έλειπαν, τα views έσκαγαν στη μέση της σελίδας
 *      (η «Οι αγγελίες μου» έδειχνε 1 από 3 αγγελίες, χωρίς μήνυμα λάθους).
 *
 *   2. Το λεξιλόγιο τύπων οχημάτων είχε αποκλίνει σε τρεις εκδοχές — φόρμα,
 *      έλεγχος εγκυρότητας, βάση. Η τομή φόρμας και ελέγχου ήταν μόλις τρεις
 *      τιμές (car, van, bus), οπότε κάθε αγγελία για φορτηγό απορριπτόταν.
 *      Κανονικό λεξιλόγιο είναι πλέον αυτό της φόρμας, συν το machinery.
 *
 * ΔΕΝ μετονομάζει τις adr_certificate και operator_license: ο κώδικας τις
 * χρησιμοποιεί με αυτά τα ονόματα σε 100 σημεία, ενώ τα εναλλακτικά
 * (requires_adr, operator_license_required) εμφανίζονται μόλις σε 7.
 * Διορθώνονται τα 7, όχι τα 100.
 *
 * Το script είναι idempotent — τρέχει με ασφάλεια όσες φορές θέλεις.
 */

require_once __DIR__ . '/../../src/bootstrap.php';

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

restore_exception_handler();
restore_error_handler();
set_exception_handler(function (Throwable $e): void {
    fwrite(STDERR, "\n❌ " . get_class($e) . ': ' . $e->getMessage() . "\n");
    fwrite(STDERR, '   ' . $e->getFile() . ':' . $e->getLine() . "\n\n");
    exit(1);
});

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c'
    );
    $stmt->execute([':t' => $table, ':c' => $column]);

    return (int) $stmt->fetchColumn() > 0;
}

$changes = 0;

// ------------------------------------------------------ 1. Στήλες που λείπουν

/**
 * requires_pei / requires_tachograph: η φόρμα έχει τα κουτάκια, ο controller
 * τα γράφει στο $data, αλλά δεν υπήρχε πού να αποθηκευτούν.
 *
 * job_category: η φόρμα το στέλνει και καθορίζει ποια πεδία εμφανίζονται
 * (cargo_transport / passenger_transport / machinery_operator /
 * machinery_assistant). Δεν ταυτίζεται με το transport_type — το transport_type
 * έχει τρεις τιμές, το job_category τέσσερις, και ξεχωρίζει τον χειριστή από
 * τον βοηθό χειριστή.
 *
 * additional_info: ελεύθερο κείμενο της φόρμας.
 */
$newColumns = [
    'requires_pei' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER adr_certificate",
    'requires_tachograph' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER requires_pei",
    'job_category' => "VARCHAR(50) NULL AFTER transport_type",
    'additional_info' => "TEXT NULL AFTER benefits",
];

foreach ($newColumns as $column => $definition) {
    if (columnExists($pdo, 'job_listings', $column)) {
        echo "• $column: υπάρχει ήδη\n";
        continue;
    }
    $pdo->exec("ALTER TABLE job_listings ADD COLUMN $column $definition");
    echo "✅ $column: προστέθηκε\n";
    $changes++;
}

// --------------------------------- 1β. Χαλάρωση του required_license

/**
 * Η στήλη ήταν NOT NULL χωρίς προεπιλογή, ενώ η φόρμα δημιουργίας δεν ζητά
 * καθόλου απαιτούμενο δίπλωμα. Αποτέλεσμα: κάθε νέα αγγελία έσκαγε με
 * «Column 'required_license' cannot be null».
 *
 * Μια αγγελία για βαν ή για χειριστή μηχανήματος μπορεί κάλλιστα να μην
 * ορίζει κατηγορία διπλώματος — το πεδίο είναι προαιρετικό εξ ορισμού.
 */
$nullable = $pdo->query(
    "SELECT IS_NULLABLE FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'job_listings'
       AND COLUMN_NAME = 'required_license'"
)->fetchColumn();

if ($nullable === 'NO') {
    $pdo->exec("ALTER TABLE job_listings MODIFY required_license VARCHAR(100) NULL DEFAULT NULL");
    echo "✅ required_license: έγινε προαιρετικό\n";
    $changes++;
} else {
    echo "• required_license: ήδη προαιρετικό\n";
}

// ------------------------------- 2. Συμπλήρωση job_category από transport_type

/**
 * Οι υπάρχουσες αγγελίες δεν έχουν job_category. Το συμπεραίνουμε από το
 * transport_type, που είναι η πλησιέστερη υπάρχουσα πληροφορία. Ο διαχωρισμός
 * χειριστή/βοηθού δεν προκύπτει από τα δεδομένα — όλες οι αγγελίες
 * μηχανημάτων θεωρούνται χειριστή.
 */
$filled = $pdo->exec(
    "UPDATE job_listings SET job_category = CASE transport_type
        WHEN 'freight'   THEN 'cargo_transport'
        WHEN 'passenger' THEN 'passenger_transport'
        WHEN 'machinery' THEN 'machinery_operator'
     END
     WHERE job_category IS NULL AND transport_type IS NOT NULL"
);

if ($filled > 0) {
    echo "✅ job_category: συμπληρώθηκε σε $filled αγγελίες από το transport_type\n";
    $changes++;
}

// ------------------------------------ 3. Κανονικοποίηση τύπων οχημάτων

/**
 * Κανονικό λεξιλόγιο (της φόρμας + machinery):
 *
 *   Επιβατικά:  car, minibus, bus
 *   Ελαφρά:     van
 *   Φορτηγά:    truck_light, truck_medium, truck_heavy, truck_articulated,
 *               truck_tanker, truck_refrigerated
 *   Μηχανήματα: machinery
 *
 * Οι παλιές τιμές αντιστοιχίζονται στην πλησιέστερη νέα. Το truck_semi και το
 * truck_trailer περιγράφουν και τα δύο συρμό — γίνονται truck_articulated.
 * Τα truck_2axle / truck_3axle περιγράφουν μέγεθος, όχι κατηγορία.
 */
$vehicleMap = [
    'truck'         => 'truck_medium',
    'truck_semi'    => 'truck_articulated',
    'truck_trailer' => 'truck_articulated',
    'truck_2axle'   => 'truck_medium',
    'truck_3axle'   => 'truck_heavy',
];

$canonical = [
    'car', 'van', 'minibus', 'bus',
    'truck_light', 'truck_medium', 'truck_heavy',
    'truck_articulated', 'truck_tanker', 'truck_refrigerated',
    'machinery',
];

$remapped = 0;
foreach ($vehicleMap as $old => $new) {
    // Αν η αγγελία έχει ήδη τη νέα τιμή, η παλιά διαγράφεται αντί να
    // δημιουργήσει διπλοεγγραφή.
    $pdo->prepare(
        'DELETE FROM job_listing_vehicle_types
         WHERE vehicle_type = :old
           AND job_listing_id IN (
               SELECT job_listing_id FROM (
                   SELECT job_listing_id FROM job_listing_vehicle_types WHERE vehicle_type = :new
               ) AS existing
           )'
    )->execute([':old' => $old, ':new' => $new]);

    $stmt = $pdo->prepare('UPDATE job_listing_vehicle_types SET vehicle_type = :new WHERE vehicle_type = :old');
    $stmt->execute([':new' => $new, ':old' => $old]);
    $n = $stmt->rowCount();
    if ($n > 0) {
        echo "✅ οχήματα: $old → $new ($n εγγραφές)\n";
        $remapped += $n;
        $changes++;
    }
}

// Ενημέρωση και της στήλης job_listings.vehicle_type (κύριος τύπος)
foreach ($vehicleMap as $old => $new) {
    $stmt = $pdo->prepare('UPDATE job_listings SET vehicle_type = :new WHERE vehicle_type = :old');
    $stmt->execute([':new' => $new, ':old' => $old]);
    if ($stmt->rowCount() > 0) {
        echo "✅ job_listings.vehicle_type: $old → $new ({$stmt->rowCount()} αγγελίες)\n";
        $changes++;
    }
}

// ------------------------------------------------------------ 4. Απολογισμός

$leftovers = $pdo->query(
    "SELECT vehicle_type, COUNT(*) AS n FROM job_listing_vehicle_types
     WHERE vehicle_type NOT IN ('" . implode("','", $canonical) . "')
     GROUP BY vehicle_type"
)->fetchAll(PDO::FETCH_ASSOC);

echo "\n";

if ($leftovers) {
    echo "⚠️  Τιμές οχημάτων εκτός λεξιλογίου (χρειάζονται απόφαση):\n";
    foreach ($leftovers as $row) {
        printf("     %-22s %d εγγραφές\n", $row['vehicle_type'], $row['n']);
    }
} else {
    echo "🟢 Όλες οι τιμές οχημάτων ανήκουν στο κανονικό λεξιλόγιο.\n";
}

$stats = $pdo->query(
    'SELECT COUNT(*) total,
            SUM(job_category IS NOT NULL) categorised,
            SUM(requires_pei = 1) pei,
            SUM(requires_tachograph = 1) tacho,
            SUM(adr_certificate = 1) adr
     FROM job_listings'
)->fetch(PDO::FETCH_ASSOC);

printf(
    "\nΑγγελίες: %d | με κατηγορία: %d | απαιτούν ΠΕΙ: %d | ταχογράφο: %d | ADR: %d\nΑλλαγές: %d\n",
    $stats['total'], $stats['categorised'], $stats['pei'], $stats['tacho'], $stats['adr'], $changes
);

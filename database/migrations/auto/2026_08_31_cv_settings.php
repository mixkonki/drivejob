<?php

/**
 * Προτιμήσεις βιογραφικού (31/08/2026).
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Το βιογραφικό παράγεται από το προφίλ, αλλά ΔΕΝ πρέπει να δείχνει
 * υποχρεωτικά ό,τι έχει το προφίλ. Ο οδηγός που στέλνει CV σε εταιρεία
 * μεταφορών θέλει άλλα πράγματα ορατά από αυτόν που το ανεβάζει σε
 * δημόσιο site αγγελιών: την ηλικία, το κινητό, τη φωτογραφία μπορεί να
 * τα θέλει έξω.
 *
 * ΞΕΧΩΡΙΣΤΕΣ ΣΤΗΛΕΣ ΚΑΙ ΟΧΙ JSON: είναι πέντε σημαίες που θα θελήσουμε
 * να ρωτήσουμε από SQL («πόσοι κρύβουν το τηλέφωνο;») και να δείξουμε σε
 * admin. Ένα JSON blob τις κάνει αόρατες σε κάθε ερώτημα.
 *
 * ΠΡΟΕΠΙΛΟΓΗ 1 ΠΑΝΤΟΥ ΕΚΤΟΣ ΦΩΤΟΓΡΑΦΙΑΣ: το βιογραφικό είναι εργαλείο
 * πρόσληψης — τα στοιχεία επικοινωνίας πρέπει να υπάρχουν, αλλιώς δεν
 * τον καλεί κανείς. Η φωτογραφία είναι το μόνο που σε αρκετές χώρες
 * θεωρείται μειονέκτημα σε CV, γι' αυτό ξεκινά κλειστή.
 *
 * Το cv_summary είναι η προσωπική παρουσίαση. Κενό = χρησιμοποιείται η
 * αυτόματη που συνθέτει ο DriverCvService από τα δεδομένα.
 *
 * Idempotent: κάθε στήλη ελέγχεται πριν προστεθεί.
 */

require_once __DIR__ . '/../../../src/bootstrap.php';

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// CLI: ο web ExceptionHandler τυπώνει σελίδα 500 σε σφάλμα — τον βγάζουμε
// ώστε το πραγματικό σφάλμα να φανεί ΩΜΟ στο log του deploy.
restore_exception_handler();
restore_error_handler();

/** Υπάρχει ήδη η στήλη; */
$hasColumn = static function (PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
};

$columns = [
    'cv_summary'      => 'TEXT NULL',
    'cv_show_photo'   => 'TINYINT(1) NOT NULL DEFAULT 0',
    'cv_show_age'     => 'TINYINT(1) NOT NULL DEFAULT 1',
    'cv_show_phone'   => 'TINYINT(1) NOT NULL DEFAULT 1',
    'cv_show_email'   => 'TINYINT(1) NOT NULL DEFAULT 1',
    'cv_show_rating'  => 'TINYINT(1) NOT NULL DEFAULT 1',
];

$added = 0;
foreach ($columns as $name => $definition) {
    if ($hasColumn($pdo, 'drivers', $name)) {
        continue;
    }
    $pdo->exec("ALTER TABLE drivers ADD COLUMN {$name} {$definition}");
    $added++;
}

echo "OK: προτιμήσεις βιογραφικού — {$added} νέες στήλες.\n";

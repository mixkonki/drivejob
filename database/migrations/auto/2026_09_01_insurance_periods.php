<?php

/**
 * Ασφαλιστικό ιστορικό + σχέση αξιολογητή. (01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  1. driver_insurance_periods
 * ══════════════════════════════════════════════════════════════════════
 *
 * Συγκεντρωτικές περίοδοι από τον «Αναλυτικό Λογαριασμό Ασφάλισης»
 * e-ΕΦΚΑ που ανεβάζει ο οδηγός (.xlsx από gov.gr). Δοκιμασμένο πάνω στα
 * πραγματικά αρχεία του Κώστα (3 σχήματα: ΙΚΑ μισθωτού με επωνυμία
 * εργοδότη, ΤΕΒΕ/ΤΣΑ και ΟΑΕΕ αυτοαπασχολούμενου).
 *
 * ΕΛΑΧΙΣΤΟΠΟΙΗΣΗ: αποθηκεύουμε φορέα, είδος, εργοδότη, περίοδο, μήνες.
 * ΟΧΙ αποδοχές, ΟΧΙ εισφορές, ΟΧΙ το ίδιο το αρχείο — περιέχουν
 * μισθολογικό ιστορικό που δεν χρειάζεται σε πλατφόρμα ευρέσεως
 * εργασίας και δεν θέλουμε την ευθύνη του.
 *
 * `verified`: το xlsx ΔΕΝ φέρει υπογραφή (επεξεργάζεται με Excel), άρα
 * οι περίοδοι μπαίνουν ανεπιβεβαίωτες και μετράνε ΜΕΙΩΜΕΝΕΣ στη
 * βαθμολογία. Όταν ελεγχθεί Βεβαίωση με κωδικό docs.gov.gr, γυρίζει 1.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  2. driver_reviews.reviewer_relation
 * ══════════════════════════════════════════════════════════════════════
 *
 * Απάντηση στο «τι κάνουμε με τους αυτοαπασχολούμενους»: ο
 * αυτοαπασχολούμενος δεν έχει εργοδότη να τον συστήσει — έχει ΠΕΛΑΤΕΣ.
 * Η μεταφορική που του έδινε δρομολόγια επί πέντε χρόνια ξέρει για τη
 * δουλειά του ό,τι θα ήξερε ένας εργοδότης. Η πρόσκληση αποκτά σχέση:
 * employer | client | supervisor — και η δημόσια φόρμα προσαρμόζει το
 * λεκτικό της.
 *
 * Idempotent: όλα ελέγχουν πριν πράξουν.
 */

require_once __DIR__ . '/../../../src/bootstrap.php';

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
restore_exception_handler();
restore_error_handler();

$hasTable = static function (PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
};

$hasColumn = static function (PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
};

// ── 1. Περίοδοι ασφάλισης ───────────────────────────────────────────────
if (!$hasTable($pdo, 'driver_insurance_periods')) {
    $pdo->exec(
        'CREATE TABLE driver_insurance_periods (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            driver_id INT(11) NOT NULL,
            fund VARCHAR(10) NOT NULL,
            fund_kind ENUM("employee", "self_employed") NOT NULL,
            employer_name VARCHAR(190) DEFAULT NULL,
            date_from DATE NOT NULL,
            date_to DATE NOT NULL,
            months DECIMAL(6,2) NOT NULL DEFAULT 0,
            verified TINYINT(1) NOT NULL DEFAULT 0,
            uploaded_at DATETIME NOT NULL,
            KEY idx_insurance_driver (driver_id),
            UNIQUE KEY uq_insurance_period (driver_id, fund, employer_name, date_from, date_to)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    echo "OK: δημιουργήθηκε ο driver_insurance_periods.\n";
} else {
    echo "OK: ο driver_insurance_periods υπήρχε ήδη.\n";
}

// ── 1β. ΔΙΟΡΘΩΣΗ ΠΑΡΑΓΩΓΗΣ: rating πρέπει να δέχεται NULL ──────────────
/*
 * ΤΟ ΜΑΘΗΜΑ (01/09, πιάστηκε ΖΩΝΤΑΝΑ από τον Κώστα): το
 * `MODIFY rating NULL` είχε προστεθεί στο 2026_09_01_score_infrastructure
 * ΑΦΟΥ εκείνο είχε ήδη τρέξει στην παραγωγή. Ο runner το είδε
 * καταγεγραμμένο και δεν το ξανάτρεξε — άρα εκεί η στήλη έμεινε
 * NOT NULL, και το INSERT της πρόσκλησης (rating NULL = εκκρεμής)
 * έσκαγε με 500: «Κάτι πήγε στραβά. Δοκίμασε ξανά.»
 *
 * ΚΑΝΟΝΑΣ (μπαίνει στους κανόνες migrations): migration που έχει
 * τρέξει ΔΕΝ τροποποιείται ποτέ — κάθε νέα αλλαγή, νέο αρχείο.
 * Το statement είναι idempotent, οπότε αδιάφορο για φρέσκες βάσεις
 * που το πήραν ήδη από το προηγούμενο αρχείο.
 */
$pdo->exec('ALTER TABLE driver_reviews MODIFY COLUMN rating INT(11) DEFAULT NULL');
echo "OK: driver_reviews.rating δέχεται NULL (εκκρεμείς προσκλήσεις).\n";

// ── 2. Σχέση αξιολογητή ─────────────────────────────────────────────────
if ($hasTable($pdo, 'driver_reviews') && !$hasColumn($pdo, 'driver_reviews', 'reviewer_relation')) {
    $pdo->exec(
        'ALTER TABLE driver_reviews
         ADD COLUMN reviewer_relation ENUM("employer", "client", "supervisor")
             NOT NULL DEFAULT "employer"'
    );
    echo "OK: driver_reviews — προστέθηκε reviewer_relation.\n";
} else {
    echo "OK: reviewer_relation υπήρχε ήδη.\n";
}

echo "OK: ασφαλιστικό ιστορικό έτοιμο.\n";

<?php

/**
 * Υποδομή βαθμολογίας οδηγού. (01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΒΡΗΚΑΜΕ ΠΡΙΝ ΓΡΑΦΤΕΙ ΑΥΤΟ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Ο μηχανισμός βαθμολογίας υπήρχε ήδη: 1.500 γραμμές σε τρία αρχεία και
 * οκτώ πίνακες. Μετρημένο στη βάση παραγωγής (31/08):
 *
 *     driver_telemetry     0 γραμμές
 *     driver_incidents     0
 *     driver_assessments   0
 *     driver_reviews       0
 *     driver_ratings       0
 *
 * Δεν έλειπε αλγόριθμος. Έλειπε **είσοδος**. Και δύο από τους πίνακες
 * ήταν διπλοί (`driver_assessment` / `driver_assessments`,
 * `driver_rating` / `driver_ratings` / `driver_rating_details`) — μπερδεμένο
 * σχήμα που θα γινόταν αδύνατο να καθαριστεί μόλις έμπαινε το πρώτο
 * πραγματικό δεδομένο.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΚΑΝΕΙ
 * ══════════════════════════════════════════════════════════════════════
 *
 *   1. Νέες στήλες στο `driver_ratings` για το νέο σχήμα (χαρτιά,
 *      φήμη, εμπιστοσύνη) — και μοναδικό κλειδί στο driver_id, που
 *      έλειπε και επέτρεπε διπλές γραμμές ανά οδηγό.
 *   2. `driver_score_breakdown`: η ανάλυση κάθε υπολογισμού. Χωρίς αυτό,
 *      στο «γιατί 61;» δεν υπάρχει απάντηση πουθενά.
 *   3. Στήλες στο `driver_reviews` για τη δομημένη αξιολόγηση εργοδότη
 *      (βήμα 6): περίοδος απασχόλησης, «θα τον ξαναπροσλάμβανες;»,
 *      πρόσκληση με token, επαλήθευση.
 *   4. Καθαρισμός των διπλών πινάκων — **ΜΟΝΟ αν είναι άδειοι**.
 *
 * Idempotent: κάθε βήμα ελέγχει πρώτα αν χρειάζεται. Η δεύτερη εκτέλεση
 * δεν βρίσκει τίποτα να κάνει.
 *
 * ΓΙΑΤΙ ΟΛΑ ΣΕ ΕΝΑ ΑΡΧΕΙΟ: κανόνας #4 των migrations — ό,τι εξαρτάται
 * μεταξύ του μπαίνει μαζί. Η σειρά μέσα σε ένα script είναι ρητή· η
 * σειρά μεταξύ αρχείων είναι αλφαβητική σύμβαση που έχει ήδη σπάσει μία
 * φορά (30/08, lookup_values).
 */

require_once __DIR__ . '/../../../src/bootstrap.php';

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
restore_exception_handler();
restore_error_handler();

/** Υπάρχει η στήλη; (ποτέ τυφλό ALTER) */
$hasColumn = static function (PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
};

$hasTable = static function (PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
};

$hasIndex = static function (PDO $pdo, string $table, string $index): bool {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    $stmt->execute([$table, $index]);
    return (int) $stmt->fetchColumn() > 0;
};

// ── 1. driver_ratings: νέο σχήμα ────────────────────────────────────────
if (!$hasTable($pdo, 'driver_ratings')) {
    $pdo->exec(
        'CREATE TABLE driver_ratings (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            driver_id INT(11) NOT NULL,
            credentials_score DECIMAL(5,2) DEFAULT NULL,
            reputation_score DECIMAL(5,2) DEFAULT NULL,
            confidence DECIMAL(5,2) DEFAULT NULL,
            has_third_party TINYINT(1) NOT NULL DEFAULT 0,
            total_score DECIMAL(5,2) DEFAULT NULL,
            last_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_driver_ratings_driver (driver_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    echo "OK: δημιουργήθηκε ο driver_ratings.\n";
} else {
    /*
     * Οι νέες στήλες δέχονται NULL ΕΠΙΤΗΔΕΣ. Το `total_score` είναι NULL
     * όσο δεν υπάρχει μαρτυρία τρίτου — και αυτό είναι η ουσία της
     * αλλαγής: το παλιό σχήμα με DEFAULT 0 δεν μπορούσε να ξεχωρίσει
     * «μηδέν» από «δεν ξέρουμε».
     */
    $newColumns = [
        'credentials_score' => 'DECIMAL(5,2) DEFAULT NULL',
        'reputation_score' => 'DECIMAL(5,2) DEFAULT NULL',
        'confidence' => 'DECIMAL(5,2) DEFAULT NULL',
        'has_third_party' => 'TINYINT(1) NOT NULL DEFAULT 0',
    ];
    $added = [];
    foreach ($newColumns as $col => $def) {
        if (!$hasColumn($pdo, 'driver_ratings', $col)) {
            $pdo->exec("ALTER TABLE driver_ratings ADD COLUMN `$col` $def");
            $added[] = $col;
        }
    }

    // Το total_score ήταν NOT NULL/DEFAULT 0 — πρέπει να δέχεται NULL.
    $pdo->exec('ALTER TABLE driver_ratings MODIFY COLUMN total_score DECIMAL(5,2) DEFAULT NULL');

    // Χωρίς μοναδικό κλειδί, το ON DUPLICATE KEY UPDATE δεν λειτουργεί
    // και κάθε ανανέωση θα πρόσθετε νέα γραμμή.
    if (!$hasIndex($pdo, 'driver_ratings', 'uq_driver_ratings_driver')) {
        // Πρώτα καθαρίζουμε τυχόν διπλές γραμμές, αλλιώς το ALTER σκάει.
        $pdo->exec(
            'DELETE r1 FROM driver_ratings r1
             INNER JOIN driver_ratings r2
             WHERE r1.driver_id = r2.driver_id AND r1.id < r2.id'
        );
        $pdo->exec('ALTER TABLE driver_ratings ADD UNIQUE KEY uq_driver_ratings_driver (driver_id)');
        $added[] = 'uq_driver_ratings_driver';
    }

    echo $added
        ? 'OK: driver_ratings — προστέθηκαν ' . implode(', ', $added) . ".\n"
        : "OK: driver_ratings — ήταν ήδη ενημερωμένος.\n";
}

// ── 2. driver_score_breakdown: η ανάλυση ────────────────────────────────
if (!$hasTable($pdo, 'driver_score_breakdown')) {
    $pdo->exec(
        'CREATE TABLE driver_score_breakdown (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            driver_id INT(11) NOT NULL,
            source VARCHAR(40) NOT NULL,
            evidence VARCHAR(20) NOT NULL,
            score_group VARCHAR(20) NOT NULL,
            label VARCHAR(190) NOT NULL,
            detail VARCHAR(250) DEFAULT NULL,
            points DECIMAL(6,2) NOT NULL DEFAULT 0,
            max_points DECIMAL(6,2) NOT NULL DEFAULT 0,
            counts_toward_score TINYINT(1) NOT NULL DEFAULT 1,
            occurred_at DATE DEFAULT NULL,
            expires_at DATE DEFAULT NULL,
            computed_at DATETIME NOT NULL,
            KEY idx_breakdown_driver (driver_id),
            KEY idx_breakdown_source (source)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    echo "OK: δημιουργήθηκε ο driver_score_breakdown.\n";
} else {
    echo "OK: ο driver_score_breakdown υπήρχε ήδη.\n";
}

// ── 3. driver_reviews: δομημένη αξιολόγηση εργοδότη ─────────────────────
if ($hasTable($pdo, 'driver_reviews')) {
    /*
     * `would_rehire`: το ισχυρότερο ερώτημα σε κάθε έρευνα συστάσεων.
     *   Απαντιέται πιο ειλικρινά από τα αστέρια γιατί είναι δεσμευτικό.
     * `employment_from/to`: επιβεβαιώνουν την προϋπηρεσία, που σήμερα
     *   είναι δηλωμένη και άρα δεν μετράει στη βαθμολογία.
     * `invite_token`: ο εργοδότης αξιολογεί από σύνδεσμο, χωρίς
     *   λογαριασμό — αλλιώς δεν θα αξιολογήσει ποτέ κανείς.
     * `verified_at`: πότε επιβεβαιώθηκε ότι ο αξιολογητής είναι όντως
     *   αυτός που λέει.
     */
    $reviewColumns = [
        'would_rehire' => 'TINYINT(1) DEFAULT NULL',
        'employment_from' => 'DATE DEFAULT NULL',
        'employment_to' => 'DATE DEFAULT NULL',
        'reviewer_name' => 'VARCHAR(120) DEFAULT NULL',
        'reviewer_company' => 'VARCHAR(160) DEFAULT NULL',
        'reviewer_email' => 'VARCHAR(160) DEFAULT NULL',
        'invite_token' => 'VARCHAR(64) DEFAULT NULL',
        'invited_at' => 'DATETIME DEFAULT NULL',
        'verified_at' => 'DATETIME DEFAULT NULL',
    ];
    $added = [];
    foreach ($reviewColumns as $col => $def) {
        if (!$hasColumn($pdo, 'driver_reviews', $col)) {
            $pdo->exec("ALTER TABLE driver_reviews ADD COLUMN `$col` $def");
            $added[] = $col;
        }
    }
    if (!$hasIndex($pdo, 'driver_reviews', 'uq_driver_reviews_token')) {
        $pdo->exec('ALTER TABLE driver_reviews ADD UNIQUE KEY uq_driver_reviews_token (invite_token)');
        $added[] = 'uq_driver_reviews_token';
    }

    // Ο company_id ήταν υποχρεωτικός: αξιολόγηση μόνο από εγγεγραμμένη
    // εταιρεία. Ο παλιός εργοδότης όμως συνήθως ΔΕΝ έχει λογαριασμό —
    // γι' αυτό δεν υπήρχε ούτε μία αξιολόγηση στη βάση.
    if ($hasColumn($pdo, 'driver_reviews', 'company_id')) {
        $pdo->exec('ALTER TABLE driver_reviews MODIFY COLUMN company_id INT(11) DEFAULT NULL');
    }

    // Το rating πρέπει να δέχεται NULL: η ΕΚΚΡΕΜΗΣ πρόσκληση είναι
    // γραμμή με rating NULL (ώστε ο EmployerReviewCollector, που μετρά
    // μόνο rating > 0, να την προσπερνά μέχρι να απαντηθεί).
    $pdo->exec('ALTER TABLE driver_reviews MODIFY COLUMN rating INT(11) DEFAULT NULL');

    echo $added
        ? 'OK: driver_reviews — προστέθηκαν ' . implode(', ', $added) . ".\n"
        : "OK: driver_reviews — ήταν ήδη ενημερωμένος.\n";
}

// ── 4. Καθαρισμός διπλών πινάκων — ΜΟΝΟ αν είναι άδειοι ─────────────────
/*
 * ΓΙΑΤΙ Ο ΕΛΕΓΧΟΣ ΚΑΙ ΟΧΙ ΣΚΕΤΟ DROP: τοπικά είναι άδειοι, αλλά αυτό το
 * migration θα τρέξει και σε βάση που δεν έχω δει. Ένα `DROP TABLE` που
 * παίρνει μαζί του δεδομένα πελάτη δεν ξεγίνεται. Αν βρεθεί γραμμή, ο
 * πίνακας μένει και το λέει στο log — το κρατάμε για χειροκίνητη κρίση.
 */
$legacy = [
    'driver_assessment' => 'αντικαταστάθηκε από driver_assessments',
    'driver_rating' => 'αντικαταστάθηκε από driver_ratings',
    'driver_rating_details' => 'αντικαταστάθηκε από driver_score_breakdown',
];

foreach ($legacy as $table => $why) {
    if (!$hasTable($pdo, $table)) {
        continue;
    }
    $rows = (int) $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
    if ($rows === 0) {
        $pdo->exec("DROP TABLE `$table`");
        echo "OK: καταργήθηκε ο άδειος `$table` ($why).\n";
    } else {
        echo "ΠΡΟΣΟΧΗ: ο `$table` έχει $rows γραμμές — ΔΕΝ καταργήθηκε ($why). Χρειάζεται χειροκίνητη μεταφορά.\n";
    }
}

echo "OK: υποδομή βαθμολογίας έτοιμη.\n";

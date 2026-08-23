<?php

/**
 * Ο πίνακας job_offers — η ΑΛΛΗ κατεύθυνση της πλατφόρμας.
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  ΤΙ ΕΛΕΙΠΕ
 * ══════════════════════════════════════════════════════════════════════════
 *
 * Το DriveJob έχει δύο κατευθύνσεις, και μόνο η μία λειτουργούσε:
 *
 *   ✔ Η εταιρεία δημοσιεύει αγγελία → ο οδηγός κάνει αίτηση → πρόσληψη
 *   ✘ Ο οδηγός δημοσιεύει «ζητώ εργασία» → η εταιρεία κάνει προσφορά → …
 *
 * Στη βάση υπάρχουν ήδη 5 αγγελίες οδηγών (listing_type = 'job_search').
 * Η σελίδα τους δείχνει κουμπί «Αποστολή Προσφοράς». Ολόκληρος ο
 * JobOfferController υπάρχει — send, myOffers, viewOffer, accept, reject —
 * με 600 γραμμές κώδικα, χειρισμό αρχείων και ελέγχους.
 *
 * Και ο πίνακας που γράφει δεν υπήρχε ποτέ. Κάθε κλήση κατέληγε σε:
 *
 *     Table 'drivejob.job_offers' doesn't exist
 *
 * Πρακτικά: ένας οδηγός μπορούσε να δημοσιεύσει ότι ψάχνει δουλειά, μια
 * εταιρεία μπορούσε να τον βρει — και δεν υπήρχε κανένας τρόπος να του
 * μιλήσει. Η μισή πλατφόρμα σταματούσε σε αδιέξοδο.
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  ΑΠΟΦΑΣΕΙΣ ΣΧΗΜΑΤΟΣ
 * ══════════════════════════════════════════════════════════════════════════
 *
 * Οι στήλες προκύπτουν από το $fillable του JobOfferRepository και από όσα
 * γράφει πραγματικά ο controller — όχι από φαντασία. Ό,τι δεν χρησιμοποιεί
 * ο κώδικας, δεν μπαίνει.
 *
 * Οι καταστάσεις καθρεφτίζουν τις αιτήσεις (job_applications), ώστε οι δύο
 * κατευθύνσεις να μιλούν την ίδια γλώσσα:
 *
 *     pending → viewed → accepted / rejected / withdrawn / expired
 *
 * Το μοναδικό ευρετήριο (company_id, driver_id, status) αποτρέπει την ίδια
 * εταιρεία από το να στέλνει δεκάδες εκκρεμείς προσφορές στον ίδιο οδηγό —
 * ο controller το ελέγχει ήδη στον κώδικα, αλλά ένας έλεγχος που ζει μόνο
 * στην PHP παρακάμπτεται από ταυτόχρονα αιτήματα.
 *
 * Idempotent: τρέχει με ασφάλεια όσες φορές θέλεις.
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

echo "Δημιουργία πίνακα job_offers\n";
echo str_repeat('─', 60), "\n\n";

$exists = $pdo->query("SHOW TABLES LIKE 'job_offers'")->fetchColumn();

if ($exists) {
    echo "Ο πίνακας υπάρχει ήδη — δεν χρειάζεται καμία αλλαγή.\n\n";
    $n = $pdo->query('SELECT COUNT(*) FROM job_offers')->fetchColumn();
    echo "Εγγραφές: $n\n";
    exit(0);
}

$pdo->exec("
    CREATE TABLE job_offers (
        id INT AUTO_INCREMENT PRIMARY KEY,

        company_id INT NOT NULL,
        driver_id  INT NOT NULL,

        title       VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        location    VARCHAR(255) DEFAULT NULL,

        job_type     VARCHAR(50)  DEFAULT NULL,
        vehicle_type VARCHAR(50)  DEFAULT NULL,

        salary_min    DECIMAL(10,2) DEFAULT NULL,
        salary_max    DECIMAL(10,2) DEFAULT NULL,
        salary_period VARCHAR(20)   DEFAULT NULL,

        benefits   TEXT DEFAULT NULL,
        start_date DATE DEFAULT NULL,

        -- Συνημμένα: σύμβαση, περιγραφή θέσης, εταιρικό έντυπο.
        -- Αποθηκεύονται εκτός public/ και σερβίρονται μέσω FileController.
        document_path          VARCHAR(255) DEFAULT NULL,
        contract_template_path VARCHAR(255) DEFAULT NULL,
        job_description_path   VARCHAR(255) DEFAULT NULL,
        company_brochure_path  VARCHAR(255) DEFAULT NULL,

        -- Ίδιο λεξιλόγιο με τις αιτήσεις, ώστε οι δύο κατευθύνσεις της
        -- πλατφόρμας να μη μιλούν διαφορετικά για το ίδιο πράγμα.
        status ENUM('pending','viewed','accepted','rejected','withdrawn','expired')
               NOT NULL DEFAULT 'pending',

        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

        INDEX idx_company (company_id),
        INDEX idx_driver  (driver_id),
        INDEX idx_status  (status),
        INDEX idx_driver_status (driver_id, status),

        CONSTRAINT fk_offer_company FOREIGN KEY (company_id)
            REFERENCES companies(id) ON DELETE CASCADE,
        CONSTRAINT fk_offer_driver FOREIGN KEY (driver_id)
            REFERENCES drivers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

echo "✓ Ο πίνακας δημιουργήθηκε.\n\n";

$cols = $pdo->query('SHOW COLUMNS FROM job_offers')->fetchAll(PDO::FETCH_ASSOC);
printf("%d στήλες:\n", count($cols));
foreach ($cols as $c) {
    printf("  %-24s %s\n", $c['Field'], $c['Type']);
}

echo "\nΕπόμενο βήμα: μια εταιρεία μπορεί πλέον να απαντήσει σε αγγελία οδηγού.\n";

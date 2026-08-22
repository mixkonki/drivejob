<?php

/**
 * Προσθέτει τις στήλες έγκρισης και μετρητή αιτήσεων στον πίνακα job_listings.
 *
 * Γιατί:
 *   - `is_approved` το διαβάζουν 12 σημεία του κώδικα (υποβολή αίτησης,
 *     MatchingRepository ×4, MachineLearningService, controllers αγγελιών).
 *     Χωρίς τη στήλη κάθε σχετικό query έσκαγε με «Unknown column», και
 *     η υποβολή αίτησης απέρριπτε κάθε αγγελία ως «μη διαθέσιμη».
 *   - `applications` το αυξάνει το JobListingRepository::incrementApplications()
 *     αμέσως μετά από κάθε επιτυχή αίτηση.
 *
 * Οι υπάρχουσες αγγελίες θεωρούνται εγκεκριμένες (ο κώδικας ήδη γράφει
 * «Αυτόματη έγκριση για τώρα»), και ο μετρητής αρχικοποιείται από τα
 * πραγματικά δεδομένα του job_applications.
 *
 * Το script είναι idempotent — τρέχει με ασφάλεια όσες φορές θέλεις.
 */

require_once __DIR__ . '/../../src/bootstrap.php';

$pdo = require ROOT_DIR . '/config/database.php';

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

if (columnExists($pdo, 'job_listings', 'is_approved')) {
    echo "• is_approved: υπάρχει ήδη\n";
} else {
    $pdo->exec("ALTER TABLE job_listings
                ADD COLUMN is_approved TINYINT(1) NOT NULL DEFAULT 1 AFTER is_active");
    $pdo->exec("ALTER TABLE job_listings ADD INDEX idx_active_approved (is_active, is_approved)");
    echo "✅ is_approved: προστέθηκε (default 1) + ευρετήριο\n";
    $changes++;
}

if (columnExists($pdo, 'job_listings', 'applications')) {
    echo "• applications: υπάρχει ήδη\n";
} else {
    $pdo->exec("ALTER TABLE job_listings
                ADD COLUMN applications INT(11) NOT NULL DEFAULT 0 AFTER views_count");
    $pdo->exec("UPDATE job_listings jl
                SET applications = (
                    SELECT COUNT(*) FROM job_applications ja
                    WHERE ja.job_listing_id = jl.id
                )");
    echo "✅ applications: προστέθηκε και αρχικοποιήθηκε από τα υπάρχοντα δεδομένα\n";
    $changes++;
}

$row = $pdo->query(
    'SELECT COUNT(*) total,
            SUM(is_approved = 1) approved,
            SUM(applications > 0) with_apps
     FROM job_listings'
)->fetch(PDO::FETCH_ASSOC);

printf(
    "\nΣύνολο αγγελιών: %d | εγκεκριμένες: %d | με αιτήσεις: %d | αλλαγές: %d\n",
    $row['total'], $row['approved'], $row['with_apps'], $changes
);

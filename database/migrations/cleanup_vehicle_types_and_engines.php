<?php

/**
 * Migration: εξυγίανση τύπων οχημάτων & μηχανών αποθήκευσης.
 *
 * ΤΙ ΔΙΟΡΘΩΝΕΙ
 *
 * 1) Διπλή πηγή αλήθειας για τους τύπους οχημάτων.
 *    Η στήλη job_listings.vehicle_types προστέθηκε κάποια στιγμή αλλά ΚΑΝΕΝΑ
 *    query δεν τη διαβάζει — όλος ο κώδικας περνά από τον πίνακα
 *    job_listing_vehicle_types. Παρ' όλα αυτά τέσσερις αγγελίες (14-17) έχουν
 *    τιμή ΜΟΝΟ εκεί, οπότε η στήλη δεν διαγράφεται πριν μεταφερθούν.
 *
 * 2) MyISAM χωρίς ξένα κλειδιά.
 *    Ο job_listing_vehicle_types είχε ήδη 5 ορφανές εγγραφές — τύποι οχημάτων
 *    αγγελιών που έχουν διαγραφεί. Μετατροπή σε InnoDB και ON DELETE CASCADE
 *    ώστε να μη ξανασυμβεί.
 *
 * Εκτέλεση:  php database/migrations/cleanup_vehicle_types_and_engines.php
 * Είναι idempotent — μπορεί να ξανατρέξει χωρίς συνέπειες.
 */

$pdo = require __DIR__ . '/_bootstrap.php';

echo "🧹 Migration: τύποι οχημάτων & μηχανές αποθήκευσης\n\n";

$columnExists = static function (PDO $pdo, string $table, string $column): bool {
    $st = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?"
    );
    $st->execute([$table, $column]);
    return $st->fetchColumn() > 0;
};

// ── 1. Μεταφορά δεδομένων από τη νεκρή στήλη ───────────────────────────
echo "1️⃣  Τύποι οχημάτων\n";

if (!$columnExists($pdo, 'job_listings', 'vehicle_types')) {
    echo "   ⏭️  Η στήλη job_listings.vehicle_types έχει ήδη αφαιρεθεί.\n";
} else {
    $rows = $pdo->query(
        "SELECT jl.id, jl.vehicle_types
         FROM job_listings jl
         LEFT JOIN job_listing_vehicle_types v ON v.job_listing_id = jl.id
         WHERE jl.vehicle_types IS NOT NULL AND jl.vehicle_types <> ''
         GROUP BY jl.id, jl.vehicle_types
         HAVING COUNT(v.job_listing_id) = 0"
    )->fetchAll(PDO::FETCH_ASSOC);

    if ($rows === []) {
        echo "   ⏭️  Καμία αγγελία δεν έχει δεδομένα μόνο στη στήλη.\n";
    } else {
        $insert = $pdo->prepare(
            "INSERT INTO job_listing_vehicle_types (job_listing_id, vehicle_type) VALUES (?, ?)"
        );
        $moved = 0;
        foreach ($rows as $row) {
            foreach (array_filter(array_map('trim', explode(',', $row['vehicle_types']))) as $type) {
                $insert->execute([$row['id'], $type]);
                $moved++;
            }
            echo "   ➡️  Αγγελία {$row['id']}: «{$row['vehicle_types']}» → πίνακας\n";
        }
        echo "   ✅ Μεταφέρθηκαν {$moved} τύποι από " . count($rows) . " αγγελίες.\n";
    }

    $pdo->exec("ALTER TABLE job_listings DROP COLUMN vehicle_types");
    echo "   ✅ Αφαιρέθηκε η στήλη job_listings.vehicle_types.\n";
}

// ── 2. Καθαρισμός ορφανών & μετατροπή σε InnoDB ────────────────────────
echo "\n2️⃣  Μηχανές αποθήκευσης & ακεραιότητα\n";

$orphans = $pdo->exec(
    "DELETE v FROM job_listing_vehicle_types v
     LEFT JOIN job_listings j ON j.id = v.job_listing_id
     WHERE j.id IS NULL"
);
echo $orphans > 0
    ? "   ✅ Διαγράφηκαν {$orphans} ορφανές εγγραφές τύπων οχημάτων.\n"
    : "   ⏭️  Καμία ορφανή εγγραφή.\n";

foreach (['job_listing_vehicle_types', 'driver_incidents', 'driver_reviews'] as $table) {
    $st = $pdo->prepare(
        "SELECT engine FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = ?"
    );
    $st->execute([$table]);
    $engine = $st->fetchColumn();

    if ($engine === 'InnoDB') {
        echo "   ⏭️  {$table}: ήδη InnoDB.\n";
        continue;
    }
    $pdo->exec("ALTER TABLE {$table} ENGINE=InnoDB");
    echo "   ✅ {$table}: {$engine} → InnoDB\n";
}

// ── 3. Ξένα κλειδιά ────────────────────────────────────────────────────
$foreignKeys = [
    ['job_listing_vehicle_types', 'fk_jlvt_listing', 'job_listing_id', 'job_listings'],
    ['driver_incidents',          'fk_incidents_driver', 'driver_id',  'drivers'],
    ['driver_reviews',            'fk_reviews_driver',   'driver_id',  'drivers'],
];

foreach ($foreignKeys as [$table, $name, $column, $parent]) {
    $st = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.table_constraints
         WHERE table_schema = DATABASE() AND table_name = ? AND constraint_name = ?"
    );
    $st->execute([$table, $name]);

    if ($st->fetchColumn() > 0) {
        echo "   ⏭️  {$name}: υπάρχει ήδη.\n";
        continue;
    }

    // Το ευρετήριο είναι προϋπόθεση για το ξένο κλειδί
    $idx = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.statistics
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? AND seq_in_index = 1"
    );
    $idx->execute([$table, $column]);
    if ($idx->fetchColumn() == 0) {
        $pdo->exec("CREATE INDEX idx_{$table}_{$column} ON {$table} ({$column})");
    }

    $pdo->exec(
        "ALTER TABLE {$table}
         ADD CONSTRAINT {$name} FOREIGN KEY ({$column})
         REFERENCES {$parent}(id) ON DELETE CASCADE"
    );
    echo "   ✅ {$name}: {$table}.{$column} → {$parent}.id (ON DELETE CASCADE)\n";
}

echo "\n🟢 Ολοκληρώθηκε.\n";

<?php

/**
 * Migration: αφαίρεση των αχρησιμοποίητων διακοπτών εμφάνισης από τις αγγελίες.
 *
 * ΓΙΑΤΙ
 *
 * Οι έξι στήλες show_rating, show_adr, show_operator_license, show_tachograph,
 * show_skills και show_experience σχεδιάστηκαν ώστε ο οδηγός να ελέγχει τι
 * εμφανίζεται στη δημόσια αγγελία του. Σάρωση ολόκληρου του έργου (php, js,
 * sql) δεν βρήκε ΚΑΜΙΑ αναφορά σε αυτές: καμία φόρμα δεν τις θέτει, κανένας
 * controller δεν τις συλλέγει, κανένα view δεν τις διαβάζει.
 *
 * Χειρότερα, τρεις αγγελίες είχαν ήδη τιμές — δηλαδή οδηγοί επέλεξαν κάποτε
 * «μην εμφανίζεις το ADR μου» και η επιλογή τους αγνοήθηκε σιωπηλά. Μια
 * ρύθμιση απορρήτου που δεν τηρείται είναι χειρότερη από ρύθμιση που δεν
 * υπάρχει.
 *
 * Η λειτουργία θα επιστρέψει όταν το src/Views/job-listings/Driver/show.php
 * εμφανίσει ξανά ενότητες πιστοποιήσεων — τότε οι διακόπτες και η εμφάνιση
 * σχεδιάζονται μαζί. Η επαναφορά μιας στήλης tinyint κοστίζει μηδέν.
 *
 * Εκτέλεση:  php database/migrations/drop_unused_show_flags.php   (idempotent)
 */

$pdo = require __DIR__ . '/_bootstrap.php';

echo "🧹 Migration: αφαίρεση αχρησιμοποίητων διακοπτών εμφάνισης\n\n";

$flags = [
    'show_rating',
    'show_adr',
    'show_operator_license',
    'show_tachograph',
    'show_skills',
    'show_experience',
];

// Καταγραφή όσων είχαν τιμή, πριν χαθούν — για το αρχείο καταγραφής
$existing = [];
foreach ($flags as $flag) {
    $st = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = 'job_listings' AND column_name = ?"
    );
    $st->execute([$flag]);
    if ($st->fetchColumn() > 0) {
        $existing[] = $flag;
    }
}

if ($existing === []) {
    echo "  ⏭️  Οι στήλες έχουν ήδη αφαιρεθεί — καμία ενέργεια.\n\n🟢 Ολοκληρώθηκε.\n";
    return;
}

$sums = implode(', ', array_map(static fn($f) => "SUM(`{$f}`) AS `{$f}`", $existing));
$row = $pdo->query("SELECT {$sums}, COUNT(*) AS synolo FROM job_listings")->fetch(PDO::FETCH_ASSOC);

echo "  Καταγραφή πριν τη διαγραφή (σε {$row['synolo']} αγγελίες):\n";
foreach ($existing as $flag) {
    printf("    %-24s %s αγγελίες με τιμή 1\n", $flag, $row[$flag] ?? 0);
}
echo "\n";

foreach ($existing as $flag) {
    $pdo->exec("ALTER TABLE job_listings DROP COLUMN `{$flag}`");
    echo "  ✅ Αφαιρέθηκε job_listings.{$flag}\n";
}

echo "\n🟢 Ολοκληρώθηκε. Η λειτουργία θα επανασχεδιαστεί μαζί με την εμφάνιση.\n";

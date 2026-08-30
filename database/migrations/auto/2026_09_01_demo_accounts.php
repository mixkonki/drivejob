<?php

/**
 * Demo λογαριασμοί: υπάρχουν ΠΑΝΤΟΥ, με ΕΝΑΝ κωδικό. (01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΟ ΠΡΟΒΛΗΜΑ
 * ══════════════════════════════════════════════════════════════════════
 *
 * «Οι κωδικοί που δημιουργήσαμε για τις demo εταιρίες δεν κάνουν είσοδο.»
 *
 * Η διάγνωση (01/09): τοπικά η είσοδος ΔΟΥΛΕΥΕ — δοκιμασμένο με πλήρες
 * POST /login → 302 στο προφίλ εταιρίας. Το πρόβλημα ήταν διπλό:
 *
 *   1. Ο seeder ζει στο database/seeds/, που ΔΕΝ τρέχει στο deploy.
 *      Στην παραγωγή οι demo εταιρίες είτε δεν υπήρχαν είτε είχαν
 *      ό,τι κωδικό είχε το χέρι που τις έφτιαξε.
 *   2. Ασυνέπεια και τοπικά: η Εταιρία 1 είχε καταλήξει με «Dokimi2026!»
 *      ενώ οι 2-6 με «Demo!2026drivejob». Δύο κωδικοί για έξι
 *      πανομοιότυπους λογαριασμούς = εγγυημένο «δεν μπαίνω».
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΚΑΝΕΙ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Για τις 6 demo εταιρίες (info+etairia1..6@thessdrive.gr):
 *   - αν λείπει η εγγραφή, τη δημιουργεί με τα ελάχιστα υποχρεωτικά
 *     πεδία (email, password, company_name, phone, vat_number)·
 *   - αν υπάρχει, ΕΠΑΝΑΦΕΡΕΙ κωδικό, is_verified=1, is_active=1.
 *
 * Ο κωδικός είναι ο DEMO_PASSWORD του seeder: **Demo!2026drivejob** —
 * μία πηγή αλήθειας, όχι δεύτερη σταθερά εδώ (γι' αυτό γίνεται require
 * του seeder μόνο για τα constants… όχι: ο seeder εκτελεί κώδικα στο
 * include. Η σταθερά επαναλαμβάνεται εδώ ΜΕ ΣΧΟΛΙΟ στον seeder — δες
 * την επικεφαλίδα του demo_data.php.)
 *
 * ΓΙΑΤΙ MIGRATION ΚΑΙ ΟΧΙ ΧΕΙΡΟΚΙΝΗΤΟ SQL: το migration τρέχει σε ΚΑΘΕ
 * βάση που θα κάνει deploy — παραγωγή, τοπικό του Κώστα, δοκιμαστικό
 * container — και καταγράφεται. Το χειροκίνητο SQL τρέχει εκεί που το
 * θυμήθηκες.
 *
 * ΑΣΦΑΛΕΙΑ: οι λογαριασμοί αυτοί είναι επώνυμα demo (plus-addressing στο
 * mailbox του Κώστα), υπάρχουν για επίδειξη πριν το beta, και ΠΡΕΠΕΙ να
 * διαγραφούν στο beta-cleanup μαζί με τον seeder. Σημειωμένο στο
 * drivejob-ekkremotites.
 *
 * Idempotent: INSERT μόνο όταν λείπει, UPDATE πάντα στα ίδια σταθερά.
 */

require_once __DIR__ . '/../../../src/bootstrap.php';

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
restore_exception_handler();
restore_error_handler();

// Ίδια τιμή με DEMO_PASSWORD στο database/seeds/demo_data.php.
$password = 'Demo!2026drivejob';
$hash = password_hash($password, PASSWORD_DEFAULT);
$now = date('Y-m-d H:i:s');

$companies = [
    1 => ['company_name' => 'Εταιρία 1', 'phone' => '2310555101', 'vat_number' => '900000001', 'city' => 'Θεσσαλονίκη'],
    2 => ['company_name' => 'Εταιρία 2', 'phone' => '2109887202', 'vat_number' => '900000002', 'city' => 'Αθήνα'],
    3 => ['company_name' => 'Εταιρία 3', 'phone' => '2651044303', 'vat_number' => '900000003', 'city' => 'Ιωάννινα'],
    4 => ['company_name' => 'Εταιρία 4', 'phone' => '2810777404', 'vat_number' => '900000004', 'city' => 'Ηράκλειο'],
    5 => ['company_name' => 'Εταιρία 5', 'phone' => '2410666505', 'vat_number' => '900000005', 'city' => 'Λάρισα'],
    6 => ['company_name' => 'Εταιρία 6', 'phone' => '2610333606', 'vat_number' => '900000006', 'city' => 'Πάτρα'],
];

$created = 0;
$reset = 0;

foreach ($companies as $n => $c) {
    $email = 'info+etairia' . $n . '@thessdrive.gr';

    $stmt = $pdo->prepare('SELECT id FROM companies WHERE email = ?');
    $stmt->execute([$email]);
    $id = $stmt->fetchColumn();

    if ($id) {
        $pdo->prepare(
            'UPDATE companies
             SET password = ?, is_verified = 1, is_active = 1, updated_at = ?
             WHERE id = ?'
        )->execute([$hash, $now, $id]);
        $reset++;
    } else {
        $pdo->prepare(
            'INSERT INTO companies
                (email, password, company_name, phone, vat_number, city, country,
                 is_verified, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1, ?, ?)'
        )->execute([
            $email, $hash, $c['company_name'], $c['phone'], $c['vat_number'],
            $c['city'], 'Ελλάδα', $now, $now,
        ]);
        $created++;
    }
}

echo "OK: demo εταιρίες — {$created} δημιουργήθηκαν, {$reset} επαναφορά κωδικού. Κωδικός: ο DEMO_PASSWORD του seeder.\n";

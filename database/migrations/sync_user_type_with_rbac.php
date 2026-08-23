<?php

/**
 * Συγχρονισμός της παλιάς στήλης `users.user_type` με το RBAC.
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  ΤΙ ΠΗΓΕ ΣΤΡΑΒΑ
 * ══════════════════════════════════════════════════════════════════════════
 *
 * Ο λογαριασμός admin@drivejob.gr ΔΕΝ ΜΠΟΡΟΥΣΕ ΝΑ ΣΥΝΔΕΘΕΙ — με σωστό
 * email, σωστό συνθηματικό, ενεργό και επαληθευμένο λογαριασμό, και σωστή
 * εγγραφή `admin` στον πίνακα `user_roles`.
 *
 * Η αιτία ήταν μία τιμή: `users.user_type = 'driver'`.
 *
 * Ο κώδικας ταυτοποίησης ρωτούσε πρώτα τις στήλες του πίνακα και μόνο αν
 * δεν έβρισκε τίποτα κατέφευγε στο RBAC. Επειδή το `user_type` είχε τιμή
 * (λάθος τιμή, αλλά τιμή), το RBAC lookup δεν εκτελούνταν ποτέ, ο ρόλος
 * έβγαινε «driver», και ο έλεγχος «είναι διαχειριστής;» απέτυπε.
 *
 * Ο ΚΩΔΙΚΑΣ διορθώθηκε ώστε να ρωτά ΠΡΩΤΑ το RBAC (UserAuthenticator).
 * Αυτό το migration διορθώνει τα ΔΕΔΟΜΕΝΑ, ώστε οι δύο πηγές να μη
 * λένε διαφορετικά πράγματα και να μην ξαναχτυπήσει το ίδιο από αλλού.
 *
 * ΑΣΦΑΛΕΙΑ: το migration ΔΕΝ αγγίζει το RBAC — αυτό είναι η πηγή αλήθειας.
 * Αντιγράφει μόνο προς τη λάθος κατεύθυνση: RBAC ➜ user_type.
 */

require_once __DIR__ . '/../../src/bootstrap.php';

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Ο χειριστής σφαλμάτων της εφαρμογής στέλνει κεφαλίδες HTTP — άχρηστο και
// θορυβώδες στη γραμμή εντολών, όπου η έξοδος έχει ήδη ξεκινήσει.
restore_exception_handler();
restore_error_handler();
set_exception_handler(function (Throwable $e): void {
    fwrite(STDERR, "\n❌ " . get_class($e) . ': ' . $e->getMessage() . "\n");
    fwrite(STDERR, '   ' . $e->getFile() . ':' . $e->getLine() . "\n\n");
    exit(1);
});

echo "Συγχρονισμός users.user_type με το RBAC\n";
echo str_repeat('─', 62), "\n\n";

/*
 * Η στήλη είναι ENUM('driver','company','admin','super_admin'), ενώ το RBAC
 * επιτρέπει ελεύθερα ονόματα ρόλων (βρέθηκε π.χ. 'employer' σε λογαριασμό
 * δοκιμών του CI). Ό,τι δεν χωράει στο ENUM το αφήνουμε ήσυχο αντί να
 * σκάσει με «Data truncated» — το RBAC παραμένει η πηγή αλήθειας.
 */
$allowed = [];
$col = $pdo->query("SHOW COLUMNS FROM users LIKE 'user_type'")->fetch(\PDO::FETCH_ASSOC);
if ($col && preg_match_all("/'([^']+)'/", $col['Type'] ?? '', $m)) {
    $allowed = $m[1];
}

/*
 * Ένας χρήστης μπορεί να έχει ΠΟΛΛΟΥΣ ρόλους στο RBAC. Η στήλη χωράει
 * έναν, οπότε κρατάμε τον ισχυρότερο — αλλιώς το αποτέλεσμα εξαρτάται από
 * τη σειρά που τυχαίνει να επιστρέψει η βάση.
 */
$priority = ['super_admin' => 4, 'admin' => 3, 'company' => 2, 'driver' => 1];

$stmt = $pdo->query(
    'SELECT u.id, u.email, u.user_type AS legacy, r.name AS rbac
     FROM users u
     JOIN user_roles ur ON ur.user_id = u.id
     JOIN roles r ON r.id = ur.role_id'
);

$best = [];
foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
    $id = $row['id'];
    $rank = $priority[$row['rbac']] ?? 0;

    if (!isset($best[$id]) || $rank > ($priority[$best[$id]['rbac']] ?? 0)) {
        $best[$id] = $row;
    }
}

$rows = [];
foreach ($best as $row) {
    if (!in_array($row['rbac'], $allowed, true)) {
        printf("· %-34s ρόλος «%s» δεν χωράει στο ENUM — παραλείπεται\n",
            mb_strimwidth($row['email'], 0, 33, '…'), $row['rbac']);
        continue;
    }
    if (($row['legacy'] ?? null) !== $row['rbac']) {
        $rows[] = $row;
    }
}

if ($rows === []) {
    echo "Καμία ασυμφωνία — δεν χρειάζεται καμία αλλαγή.\n";
    exit(0);
}

printf("%-4s %-34s %-12s %-12s\n", 'ID', 'Email', 'ΗΤΑΝ', 'ΓΙΝΕΤΑΙ');
echo str_repeat('─', 62), "\n";

$update = $pdo->prepare('UPDATE users SET user_type = ? WHERE id = ?');
$fixed = 0;

foreach ($rows as $row) {
    printf(
        "%-4s %-34s %-12s %-12s\n",
        $row['id'],
        mb_strimwidth($row['email'], 0, 33, '…'),
        $row['legacy'] ?? '(κενό)',
        $row['rbac']
    );

    $update->execute([$row['rbac'], $row['id']]);
    $fixed++;
}

echo str_repeat('─', 62), "\n";
echo "Διορθώθηκαν $fixed λογαριασμοί.\n\n";

// Επαλήθευση: μετράμε μόνο όσους ΘΑ έπρεπε να είχαν διορθωθεί — δηλαδή
// χρήστες των οποίων κανένας ρόλος RBAC δεν ταιριάζει με τη στήλη.
$check = $pdo->query(
    'SELECT COUNT(*) FROM (
        SELECT u.id
        FROM users u
        JOIN user_roles ur ON ur.user_id = u.id
        JOIN roles r ON r.id = ur.role_id
        GROUP BY u.id
        HAVING SUM(u.user_type = r.name) = 0
     ) AS mismatched'
)->fetchColumn();

echo $check == 0
    ? "Επαλήθευση: κάθε λογαριασμός συμφωνεί με έναν από τους ρόλους του ✓\n"
    : "Απομένουν $check λογαριασμοί με ρόλο εκτός ENUM (αναμενόμενο για δοκιμαστικούς).\n";

<?php

/**
 * Εξυγίανση απαιτήσεων στις υπάρχουσες αγγελίες. (01/09/2026)
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΒΡΕΘΗΚΕ ΣΤΗ ΒΑΣΗ
 * ══════════════════════════════════════════════════════════════════════
 *
 * Το required_license ήταν ελεύθερο κείμενο, γραμμένο από τρεις
 * διαφορετικές εποχές της φόρμας:
 *
 *     'C+E'                       ← δεν ισούται με το 'CE' του οδηγού
 *     'A, B, BE, C, CE, D, DE'    ← κενά μετά τα κόμματα
 *     ''                          ← 4 στις 29 χωρίς τίποτα
 *
 * Και ΔΥΟ στήλες εμπειρίας (experience_years, min_experience) με
 * διαφορετικές τιμές στην ίδια γραμμή — η #16 ζητούσε 1 έτος στη μία
 * και 3 στην άλλη. Ποιο ισχύει; Κανένα ταίριασμα δεν μπορεί να χτιστεί
 * πάνω σε αυτό.
 *
 * ══════════════════════════════════════════════════════════════════════
 *  ΤΙ ΚΑΝΕΙ
 * ══════════════════════════════════════════════════════════════════════
 *
 *   1. required_license → κανονικοποιημένο CSV κωδικών: 'C+E' → 'CE',
 *      κενά έξω, ελληνικά Γ/Δ → λατινικά C/D, άγνωστα πετιούνται.
 *   2. Εμπειρία: και οι δύο στήλες παίρνουν το ΕΛΑΧΙΣΤΟ των μη μηδενικών
 *      τιμών τους. Το ελάχιστο και όχι το μέγιστο: επί αμφιβολίας, η
 *      απαίτηση χαλαρώνει — καλύτερα ένας παραπάνω υποψήφιος που θα
 *      απορριφθεί στη συνέντευξη παρά ένας σωστός που δεν θα δει ποτέ
 *      την αγγελία.
 *
 * Από εδώ και πέρα η φόρμα (κοινό _listing-form.php) γράφει μόνο
 * κωδικούς — αυτό το migration καθαρίζει ό,τι προϋπήρχε.
 *
 * Idempotent: η κανονικοποίηση κανονικοποιημένου δίνει το ίδιο.
 */

require_once __DIR__ . '/../../../src/bootstrap.php';

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
restore_exception_handler();
restore_error_handler();

$valid = ['AM', 'A1', 'A2', 'A', 'B', 'BE', 'C1', 'C1E', 'C', 'CE', 'D1', 'D1E', 'D', 'DE'];

/** 'C+E' / 'Γ+Ε' / ' ce ' → 'CE'. Άγνωστο → null. */
$normaliseCode = static function (string $raw) use ($valid): ?string {
    $code = strtoupper(trim($raw));
    $code = str_replace(['+', ' ', '.'], '', $code);
    // Ελληνικοί χαρακτήρες των κατηγοριών → λατινικοί.
    $code = strtr($code, ['Α' => 'A', 'Β' => 'B', 'Γ' => 'C', 'Δ' => 'D', 'Ε' => 'E', 'Μ' => 'M']);
    return in_array($code, $valid, true) ? $code : null;
};

// ── 1. required_license ─────────────────────────────────────────────────
$rows = $pdo->query(
    "SELECT id, required_license FROM job_listings
     WHERE required_license IS NOT NULL AND required_license <> ''"
)->fetchAll(PDO::FETCH_ASSOC);

$update = $pdo->prepare('UPDATE job_listings SET required_license = ? WHERE id = ?');
$changed = 0;

foreach ($rows as $row) {
    $codes = [];
    foreach (explode(',', (string) $row['required_license']) as $part) {
        $code = $normaliseCode($part);
        if ($code !== null && !in_array($code, $codes, true)) {
            $codes[] = $code;
        }
    }
    $clean = implode(',', $codes);
    if ($clean !== $row['required_license']) {
        $update->execute([$clean, $row['id']]);
        $changed++;
    }
}
echo "OK: required_license — κανονικοποιήθηκαν {$changed} αγγελίες.\n";

// ── 2. Εμπειρία: μία αλήθεια στις δύο στήλες ───────────────────────────
/*
 * LEAST των μη μηδενικών: NULLIF μηδενίζει τα 0 ώστε το LEAST να μην
 * τα προτιμά, COALESCE γυρνά στο 0 όταν καμία στήλη δεν έχει τιμή.
 */
$affected = $pdo->exec(
    'UPDATE job_listings
     SET experience_years = COALESCE(LEAST(
             COALESCE(NULLIF(experience_years, 0), 999),
             COALESCE(NULLIF(min_experience, 0), 999)
         ), 0),
         min_experience = experience_years
     WHERE COALESCE(experience_years, 0) <> COALESCE(min_experience, 0)'
);
// Όσες είχαν 999 (καμία τιμή πουθενά) γυρίζουν σε 0.
$pdo->exec('UPDATE job_listings SET experience_years = 0, min_experience = 0 WHERE experience_years = 999');

echo "OK: εμπειρία — ενοποιήθηκαν {$affected} αγγελίες (ελάχιστο των δύο στηλών).\n";

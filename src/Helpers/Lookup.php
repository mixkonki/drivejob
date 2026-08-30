<?php

namespace Drivejob\Helpers;

use Drivejob\Models\LookupModel;

/**
 * Γενικός μηχανισμός καταλόγων (30/08/2026).
 *
 * Κάθε λίστα που βλέπει ο χρήστης (ειδικές άδειες, θεματολογίες
 * πιστοποιητικών, …) ζει στον πίνακα lookup_values και τη συντηρεί ο
 * διαχειριστής. Αυτή η κλάση είναι ο κοινός κώδικας ανάγνωσης, ώστε
 * κάθε νέος κατάλογος να χρειάζεται μόνο:
 *   1. μια σταθερά DOMAIN + πίνακα DEFAULTS στον δικό του helper
 *   2. μια γραμμή στο LookupModel::DOMAINS (για να φαίνεται στο admin)
 *   3. seed σε migration
 *
 * ΓΙΑΤΙ ΥΠΑΡΧΟΥΝ DEFAULTS: αν ο πίνακας λείπει (migration που άργησε) ή
 * η βάση απαντήσει με σφάλμα, οι φόρμες πρέπει να συνεχίσουν να
 * δουλεύουν με τις ενσωματωμένες τιμές — ποτέ άδειο dropdown.
 *
 * Η μνήμη είναι ανά αίτημα: ο κατάλογος διαβάζεται μία φορά, όχι σε
 * κάθε πεδίο μιας επαναλαμβανόμενης φόρμας.
 */
final class Lookup
{
    /** @var array<string, array<string,string>> domain => code=>label */
    private static array $activeCache = [];

    /** @var array<string, array<string,string>> domain => code=>label (και ανενεργές) */
    private static array $allCache = [];

    /** Ενεργές τιμές — αυτές προσφέρονται σε νέες καταχωρήσεις. */
    public static function options(string $domain, array $defaults): array
    {
        if (isset(self::$activeCache[$domain])) {
            return self::$activeCache[$domain];
        }

        $fromDb = self::read($domain, true);
        self::$activeCache[$domain] = $fromDb ?: $defaults;
        return self::$activeCache[$domain];
    }

    /** Όλες οι τιμές, ενεργές και ανενεργές — για εμφάνιση παλιών εγγραφών. */
    public static function allOptions(string $domain, array $defaults): array
    {
        if (isset(self::$allCache[$domain])) {
            return self::$allCache[$domain];
        }

        $fromDb = self::read($domain, false);
        self::$allCache[$domain] = $fromDb ?: $defaults;
        return self::$allCache[$domain];
    }

    /**
     * Έγκυρος κωδικός για αποθήκευση — δέχεται ΚΑΙ ανενεργές τιμές: αν ο
     * διαχειριστής αποσύρει μια κατηγορία, ο χρήστης που την έχει ήδη
     * πρέπει να μπορεί να αποθηκεύσει χωρίς να τη χάσει.
     */
    public static function isValid(string $domain, string $code, array $defaults): bool
    {
        return isset(self::allOptions($domain, $defaults)[$code]) || isset($defaults[$code]);
    }

    /** Ετικέτα εμφάνισης· άγνωστος/παλιός ελεύθερος κωδικός επιστρέφεται ως έχει. */
    public static function label(string $domain, string $code, array $defaults): string
    {
        return self::allOptions($domain, $defaults)[$code] ?? $defaults[$code] ?? $code;
    }

    private static function read(string $domain, bool $activeOnly): array
    {
        try {
            $pdo = require ROOT_DIR . '/config/database.php';
            return (new LookupModel($pdo))->options($domain, $activeOnly);
        } catch (\Throwable $e) {
            return []; // ο καλών πέφτει στα defaults
        }
    }
}

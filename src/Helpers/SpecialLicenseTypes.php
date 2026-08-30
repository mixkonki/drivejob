<?php

namespace Drivejob\Helpers;

use Drivejob\Models\LookupModel;

/**
 * Ειδικές άδειες & πιστοποιητικά οδηγού (30/08/2026, v2).
 *
 * Οι τιμές ζουν πλέον στον πίνακα lookup_values και τις συντηρεί ο
 * διαχειριστής από το admin (Ρυθμίσεις → Κατάλογοι) — δεν χρειάζεται
 * deploy για να προστεθεί μια νέα κατηγορία όταν αλλάξει η νομοθεσία.
 *
 * Οι σταθερές DEFAULTS παραμένουν ως: (α) seed της βάσης στο migration,
 * (β) δίχτυ ασφαλείας αν ο πίνακας δεν υπάρχει ακόμη ή η βάση απαντήσει
 * με σφάλμα — η φόρμα του οδηγού δεν μένει ποτέ με άδεια λίστα.
 *
 * ΔΕΝ μπαίνουν εδώ: ADR, ΠΕΙ, κάρτα ταχογράφου και άδεια χειριστή ΜΕ
 * (έχουν δικές τους καρτέλες), ούτε σεμινάρια (πάνε στις πιστοποιήσεις).
 */
final class SpecialLicenseTypes
{
    public const DOMAIN = 'special_license';

    /** Ενσωματωμένες τιμές — seed + fallback. */
    public const DEFAULTS = [
        'edx_taxi'      => 'Ειδική άδεια ΕΔΧ (ΤΑΞΙ)',
        'live_animals'  => 'Πιστοποιητικό Επάρκειας Οδηγών και Συνοδών Μεταφορικών Μέσων Ζώντων Ζώων',
        'rental_driver' => 'Πιστοποιητικό οδηγού για ενοικίαση οχήματος με οδηγό',
        'pee_freight'   => 'Πιστοποιητικό Επαγγελματικής Επάρκειας (ΠΕΕ) Εμπορευματικών Μεταφορών',
        'pee_passenger' => 'Πιστοποιητικό Επαγγελματικής Επάρκειας (ΠΕΕ) Επιβατικών Μεταφορών',
        'other'         => 'Άλλο πιστοποιητικό οδηγού',
    ];

    public const SHORT_DEFAULTS = [
        'edx_taxi'      => 'ΕΔΧ (ΤΑΞΙ)',
        'live_animals'  => 'Μεταφορά ζώντων ζώων',
        'rental_driver' => 'Ενοικίαση με οδηγό',
        'pee_freight'   => 'ΠΕΕ Εμπορευματικών',
        'pee_passenger' => 'ΠΕΕ Επιβατικών',
        'other'         => 'Άλλο πιστοποιητικό',
    ];

    /** Μνήμη ανά αίτημα: ο κατάλογος διαβάζεται μία φορά, όχι ανά πεδίο. */
    private static ?array $cache = null;

    /**
     * code => label, ΜΟΝΟ ενεργές τιμές — αυτό δείχνει η φόρμα του οδηγού.
     */
    public static function options(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $fromDb = [];
        try {
            $pdo = require ROOT_DIR . '/config/database.php';
            $fromDb = (new LookupModel($pdo))->options(self::DOMAIN, true);
        } catch (\Throwable $e) {
            $fromDb = []; // fallback παρακάτω
        }

        self::$cache = $fromDb ?: self::DEFAULTS;
        return self::$cache;
    }

    /**
     * Έγκυρος κωδικός για αποθήκευση. Δέχεται και ΑΝΕΝΕΡΓΕΣ τιμές: αν ο
     * διαχειριστής απενεργοποιήσει μια κατηγορία, ο οδηγός που την είχε
     * ήδη πρέπει να μπορεί να αποθηκεύσει το προφίλ του χωρίς να τη χάσει.
     */
    public static function isValid(string $code): bool
    {
        if (isset(self::options()[$code]) || isset(self::DEFAULTS[$code])) {
            return true;
        }

        try {
            $pdo = require ROOT_DIR . '/config/database.php';
            return isset((new LookupModel($pdo))->options(self::DOMAIN, false)[$code]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Ετικέτα για εμφάνιση. Ψάχνει και στις ανενεργές (ο οδηγός βλέπει
     * ό,τι έχει καταχωρήσει) και δέχεται παλιές ελεύθερες τιμές (π.χ.
     * «εδχ») — τις επιστρέφει ως έχουν αντί για κενό.
     */
    public static function label(string $code): string
    {
        $opts = self::options();
        if (isset($opts[$code])) {
            return $opts[$code];
        }

        try {
            $pdo = require ROOT_DIR . '/config/database.php';
            $all = (new LookupModel($pdo))->options(self::DOMAIN, false);
            if (isset($all[$code])) {
                return $all[$code];
            }
        } catch (\Throwable $e) {
            // πέφτουμε στα defaults
        }

        return self::DEFAULTS[$code] ?? $code;
    }

    public static function shortLabel(string $code): string
    {
        return self::SHORT_DEFAULTS[$code] ?? self::label($code);
    }
}

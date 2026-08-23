<?php

namespace Drivejob\Helpers;

/**
 * Διευθύνσεις στατικών αρχείων με αποτύπωμα έκδοσης.
 *
 * ΓΙΑΤΙ ΥΠΑΡΧΕΙ: όταν το site μπήκε σε Maintenance Mode, το edge cache του
 * παρόχου αποθήκευσε τη σελίδα συντήρησης ΣΤΗ ΘΕΣΗ κάποιων αρχείων. Μετά την
 * επαναφορά, το /css/drivers-registration.css εξακολουθούσε να επιστρέφει
 * HTML με τίτλο «This Site is Under Maintenance» — status 200, content-type
 * text/html. Η φόρμα εγγραφής οδηγού φόρτωνε χωρίς κανένα στιλ και δεν υπήρχε
 * τρόπος να το καταλάβει κανείς: το αρχείο υπήρχε στον server, ήταν σωστό, και
 * το ίδιο URL με ?v=κάτι επέστρεφε αμέσως το σωστό CSS.
 *
 * Η λύση είναι να μη ζητάμε ποτέ δύο φορές το ίδιο URL για διαφορετικό
 * περιεχόμενο. Κάθε αρχείο συνοδεύεται από το mtime του:
 *
 *     /css/styles.css?v=1755512400
 *
 * Όταν αλλάξει το αρχείο, αλλάζει και το URL — οπότε κανένα cache, ούτε του
 * browser ούτε του παρόχου, δεν μπορεί να σερβίρει παλιό ή λάθος περιεχόμενο.
 */
final class Asset
{
    /** @var array<string, string> μνήμη εντός αιτήματος */
    private static array $cache = [];

    /**
     * Πλήρης διεύθυνση στατικού αρχείου, με αποτύπωμα έκδοσης.
     *
     * @param string $path διαδρομή σχετική με το public/, π.χ. 'css/styles.css'
     */
    public static function url(string $path): string
    {
        $path = ltrim($path, '/');

        if (isset(self::$cache[$path])) {
            return self::$cache[$path];
        }

        $base = defined('BASE_URL') ? BASE_URL : '/';
        $url = rtrim($base, '/') . '/' . $path;

        $file = self::publicDir() . '/' . $path;

        // Αν το αρχείο δεν βρεθεί, επιστρέφουμε τη διεύθυνση χωρίς αποτύπωμα
        // αντί να σπάσουμε τη σελίδα — το πρόβλημα φαίνεται στο Network tab.
        if (is_file($file)) {
            $url .= '?v=' . filemtime($file);
        }

        return self::$cache[$path] = $url;
    }

    /**
     * Έτοιμη ετικέτα <link> για φύλλο στιλ.
     */
    public static function css(string $path): string
    {
        if (!str_starts_with($path, 'css/')) {
            $path = 'css/' . $path;
        }

        return sprintf('<link rel="stylesheet" href="%s">', htmlspecialchars(self::url($path), ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Έτοιμη ετικέτα <script>.
     */
    public static function js(string $path, bool $defer = true): string
    {
        if (!str_starts_with($path, 'js/')) {
            $path = 'js/' . $path;
        }

        return sprintf(
            '<script src="%s"%s></script>',
            htmlspecialchars(self::url($path), ENT_QUOTES, 'UTF-8'),
            $defer ? ' defer' : ''
        );
    }

    private static function publicDir(): string
    {
        if (defined('ROOT_DIR')) {
            return ROOT_DIR . '/public';
        }

        return dirname(__DIR__, 2) . '/public';
    }
}

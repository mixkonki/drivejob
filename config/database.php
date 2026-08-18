<?php

/**
 * Σύνδεση PDO στη βάση δεδομένων.
 *
 * Ιστορικό: μέχρι το Πακέτο 9 το αρχείο είχε σκληροκωδικοποιημένα τοπικά
 * credentials (127.0.0.1 / root / κενό), με αποτέλεσμα κάθε web αίτημα στην
 * παραγωγή να αποτυγχάνει με «Connection refused». Πλέον διαβάζει
 * αποκλειστικά το config/db.php, που παίρνει τις τιμές από το .env.
 *
 * Το αρχείο ΕΠΙΣΤΡΕΦΕΙ το PDO και ταυτόχρονα ορίζει τη μεταβλητή $pdo στο
 * scope όποιου το κάνει include — και οι δύο τρόποι χρήσης υποστηρίζονται.
 * Η σύνδεση δημιουργείται μία φορά ανά αίτημα και επαναχρησιμοποιείται.
 *
 * ΠΡΟΣΟΧΗ: χρησιμοποιήστε «require», ΟΧΙ «require_once», όταν χρειάζεστε την
 * επιστρεφόμενη τιμή — το require_once επιστρέφει true τη δεύτερη φορά.
 */

$config = require __DIR__ . '/db.php';

// Σταθερές συμβατότητας για παλαιότερο κώδικα (δεν περιέχουν πλέον μυστικά
// σκληροκωδικοποιημένα — προέρχονται από το .env).
defined('DB_HOST') or define('DB_HOST', $config['host']);
defined('DB_NAME') or define('DB_NAME', $config['database']);
defined('DB_USER') or define('DB_USER', $config['username']);
defined('DB_PASS') or define('DB_PASS', $config['password']);

if (!isset($GLOBALS['__drivejob_pdo']) || !$GLOBALS['__drivejob_pdo'] instanceof PDO) {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );

    try {
        $GLOBALS['__drivejob_pdo'] = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            $config['options']
        );
    } catch (PDOException $e) {
        // Το μήνυμα του PDO αποκαλύπτει host/χρήστη — δεν φτάνει ποτέ στον χρήστη.
        error_log('[DriveJob] Αποτυχία σύνδεσης στη βάση: ' . $e->getMessage());

        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') {
            throw new RuntimeException('Σφάλμα σύνδεσης στη βάση: ' . $e->getMessage(), 0, $e);
        }

        throw new RuntimeException(
            'Η υπηρεσία δεν είναι προσωρινά διαθέσιμη. Δοκιμάστε ξανά σε λίγο.',
            0,
            $e
        );
    }
}

$pdo = $GLOBALS['__drivejob_pdo'];

return $pdo;

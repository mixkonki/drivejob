<?php

/**
 * Διόρθωση σημαιών is_system στις ειδικές άδειες (30/08/2026).
 *
 * Το προηγούμενο migration σήμανε ΟΛΕΣ τις τιμές ως «βασικές του
 * συστήματος» — που σημαίνει ότι ο διαχειριστής δεν μπορούσε να
 * αποσύρει καμία. Αυτό ακυρώνει τον ίδιο τον λόγο ύπαρξης του
 * καταλόγου: οι νομοθετικές κατηγορίες αλλάζουν και πρέπει να
 * μπορούν να αποσυρθούν χωρίς deploy.
 *
 * Βασική παραμένει μόνο η «other»: είναι το δίχτυ ασφαλείας της
 * φόρμας — χωρίς αυτήν, ό,τι δεν χωρά σε κατηγορία δεν καταχωρείται.
 *
 * Idempotent: σκέτο UPDATE, τρέχει όσες φορές θέλει.
 */

require_once __DIR__ . '/../../../src/bootstrap.php';

$pdo = require ROOT_DIR . '/config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// CLI: ο web ExceptionHandler τυπώνει σελίδα 500 σε σφάλμα — τον βγάζουμε.
restore_exception_handler();
restore_error_handler();

$stmt = $pdo->prepare(
    "UPDATE lookup_values SET is_system = 0 WHERE domain = 'special_license' AND code <> 'other'"
);
$stmt->execute();

echo 'OK: ' . $stmt->rowCount() . " τιμές έγιναν διαχειρίσιμες (μόνο η «other» παραμένει βασική).\n";

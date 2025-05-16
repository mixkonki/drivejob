<?php
// Ορισμός της διαδρομής του φακέλου
$folderPath = __DIR__ . '/public/uploads/criminal_records';

// Έλεγχος αν ο φάκελος υπάρχει
if (is_dir($folderPath)) {
    echo "Ο φάκελος $folderPath υπάρχει ήδη.\n";
} else {
    // Δημιουργία του φακέλου με δικαιώματα 0755
    if (mkdir($folderPath, 0755, true)) {
        echo "Ο φάκελος $folderPath δημιουργήθηκε με επιτυχία.\n";
    } else {
        echo "Σφάλμα κατά τη δημιουργία του φακέλου $folderPath.\n";
    }
}

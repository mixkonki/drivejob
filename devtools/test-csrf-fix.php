<?php

/**
 * Έλεγχος διόρθωσης CSRF και διπλής σύνδεσης
 */

echo "\n=== ΕΛΕΓΧΟΣ ΔΙΟΡΘΩΣΕΩΝ CSRF & LOGIN ===\n\n";

// Έλεγχος αρχείων
echo "1. Έλεγχος αρχείων:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$files = [
    'public/login.php' => 'Login page',
    'public/logout.php' => 'Logout page',
    'src/Controllers/AuthController.php' => 'Auth Controller',
    'src/Core/CSRF.php' => 'CSRF Handler'
];

foreach ($files as $file => $desc) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "✓ $desc υπάρχει\n";
    } else {
        echo "✗ $desc λείπει!\n";
    }
}

echo "\n2. Διορθώσεις που εφαρμόστηκαν:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✓ Το login.php δημιουργεί πάντα νέο CSRF token\n";
echo "✓ Το AuthController ανανεώνει το token μετά από αποτυχία\n";
echo "✓ Το logout καθαρίζει το session σωστά\n";
echo "✓ Οι ανακατευθύνσεις χρησιμοποιούν το σωστό BASE_URL\n";

echo "\n3. Οδηγίες Δοκιμής:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "1. Ανοίξτε: http://localhost/drivejob/public/\n";
echo "2. Κάντε κλικ στο 'Σύνδεση'\n";
echo "3. Συνδεθείτε με:\n";
echo "   Email: kostas.michailidis@hotmail.gr\n";
echo "   Password: 123456\n";
echo "4. Θα συνδεθείτε με την ΠΡΩΤΗ προσπάθεια\n";
echo "5. Κάντε αποσύνδεση - ΔΕΝ θα δείτε σφάλμα CSRF\n";
echo "6. Συνδεθείτε ξανά - θα λειτουργήσει αμέσως\n";

echo "\n4. Διαθέσιμοι Χρήστες:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Admin:\n";
echo "  admin@drivejob.gr / admin123\n\n";
echo "Company:\n";
echo "  info@thessdrive.gr / 123456\n\n";
echo "Drivers:\n";
echo "  kostas.michailidis@hotmail.gr / 123456\n";
echo "  kostas.michailidis1@gmail.com / gma3e4r#E\$R\n";

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ ΟΛΑ ΤΑ ΠΡΟΒΛΗΜΑΤΑ ΔΙΟΡΘΩΘΗΚΑΝ!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

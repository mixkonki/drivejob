<?php
require 'src/bootstrap.php';

echo "<h1>Διόρθωση Προβλήματος DriverProfileService</h1>";

try {
    // Σύνδεση με τη βάση δεδομένων
    $pdo = $container->get('pdo');

    // Έλεγχος αν ο χρήστης είναι συνδεδεμένος
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
        echo "<p>Δεν είστε συνδεδεμένος ως οδηγός. Το script θα συνεχίσει χωρίς να χρησιμοποιήσει το ID σας.</p>";
        $driverId = null;
    } else {
        $driverId = $_SESSION['user_id'];
        echo "<p>Συνδεδεμένος οδηγός με ID: $driverId</p>";
    }

    // Έλεγχος του DriverProfileService
    echo "<h2>Έλεγχος DriverProfileService</h2>";

    // Ανάλυση του κώδικα του DriverProfileService
    $serviceFile = ROOT_DIR . '/src/Services/DriverProfileService.php';
    if (file_exists($serviceFile)) {
        echo "<p>Το αρχείο DriverProfileService.php βρέθηκε.</p>";

        // Ανάγνωση του περιεχομένου του αρχείου
        $serviceContent = file_get_contents($serviceFile);

        // Έλεγχος αν το αρχείο περιέχει τα πεδία legal_status και criminal_record_file
        $hasLegalStatus = strpos($serviceContent, 'legal_status') !== false;
        $hasCriminalRecordFile = strpos($serviceContent, 'criminal_record_file') !== false;

        echo "<p>Το πεδίο legal_status " . ($hasLegalStatus ? "βρέθηκε" : "δεν βρέθηκε") . " στο αρχείο DriverProfileService.php.</p>";
        echo "<p>Το πεδίο criminal_record_file " . ($hasCriminalRecordFile ? "βρέθηκε" : "δεν βρέθηκε") . " στο αρχείο DriverProfileService.php.</p>";

        // Αν λείπουν τα πεδία, προσθήκη τους στο αρχείο
        if (!$hasLegalStatus || !$hasCriminalRecordFile) {
            echo "<h3>Διόρθωση του DriverProfileService</h3>";

            // Αναζήτηση του σημείου όπου επιστρέφονται τα δεδομένα του οδηγού
            if (preg_match('/return\s+array_merge\(\s*\$driver\s*,\s*\[/s', $serviceContent, $matches, PREG_OFFSET_CAPTURE)) {
                $position = $matches[0][1] + strlen($matches[0][0]);

                // Προσθήκη των πεδίων στο array_merge
                $newFields = "";
                if (!$hasLegalStatus) {
                    $newFields .= "'legal_status' => \$driver['legal_status'] ?? null,\n            ";
                }
                if (!$hasCriminalRecordFile) {
                    $newFields .= "'criminal_record_file' => \$driver['criminal_record_file'] ?? null,\n            ";
                }

                // Ενημέρωση του περιεχομένου του αρχείου
                $newContent = substr($serviceContent, 0, $position) . $newFields . substr($serviceContent, $position);

                // Αποθήκευση του νέου περιεχομένου
                if (file_put_contents($serviceFile, $newContent)) {
                    echo "<p style='color: green;'>Το αρχείο DriverProfileService.php ενημερώθηκε με επιτυχία!</p>";
                } else {
                    echo "<p style='color: red;'>Αποτυχία ενημέρωσης του αρχείου DriverProfileService.php.</p>";
                }
            } else {
                echo "<p style='color: red;'>Δεν βρέθηκε το σημείο για την προσθήκη των πεδίων στο αρχείο DriverProfileService.php.</p>";
            }
        } else {
            echo "<p style='color: green;'>Τα πεδία υπάρχουν ήδη στο αρχείο DriverProfileService.php.</p>";
        }
    } else {
        echo "<p style='color: red;'>Το αρχείο DriverProfileService.php δεν βρέθηκε!</p>";
    }

    // Έλεγχος του πίνακα drivers
    echo "<h2>Έλεγχος Πίνακα Drivers</h2>";

    // Έλεγχος αν υπάρχουν τα πεδία legal_status και criminal_record_file στον πίνακα drivers
    $stmt = $pdo->query("SHOW COLUMNS FROM drivers LIKE 'legal_status'");
    $hasLegalStatusColumn = $stmt->fetch() !== false;

    $stmt = $pdo->query("SHOW COLUMNS FROM drivers LIKE 'criminal_record_file'");
    $hasCriminalRecordFileColumn = $stmt->fetch() !== false;

    echo "<p>Το πεδίο legal_status " . ($hasLegalStatusColumn ? "υπάρχει" : "δεν υπάρχει") . " στον πίνακα drivers.</p>";
    echo "<p>Το πεδίο criminal_record_file " . ($hasCriminalRecordFileColumn ? "υπάρχει" : "δεν υπάρχει") . " στον πίνακα drivers.</p>";

    // Αν λείπουν τα πεδία, προσθήκη τους στον πίνακα
    if (!$hasLegalStatusColumn || !$hasCriminalRecordFileColumn) {
        echo "<h3>Προσθήκη Πεδίων στον Πίνακα Drivers</h3>";

        if (!$hasLegalStatusColumn) {
            $sql = "ALTER TABLE drivers ADD COLUMN legal_status ENUM('yes', 'no') DEFAULT NULL AFTER date_of_birth";
            if ($pdo->exec($sql) !== false) {
                echo "<p style='color: green;'>Το πεδίο legal_status προστέθηκε με επιτυχία στον πίνακα drivers.</p>";
            } else {
                echo "<p style='color: red;'>Αποτυχία προσθήκης του πεδίου legal_status στον πίνακα drivers.</p>";
            }
        }

        if (!$hasCriminalRecordFileColumn) {
            $sql = "ALTER TABLE drivers ADD COLUMN criminal_record_file VARCHAR(255) DEFAULT NULL AFTER resume_file";
            if ($pdo->exec($sql) !== false) {
                echo "<p style='color: green;'>Το πεδίο criminal_record_file προστέθηκε με επιτυχία στον πίνακα drivers.</p>";
            } else {
                echo "<p style='color: red;'>Αποτυχία προσθήκης του πεδίου criminal_record_file στον πίνακα drivers.</p>";
            }
        }
    } else {
        echo "<p style='color: green;'>Τα πεδία υπάρχουν ήδη στον πίνακα drivers.</p>";
    }

    // Έλεγχος του φακέλου για τα αρχεία ποινικού μητρώου
    echo "<h2>Έλεγχος Φακέλου για Αρχεία Ποινικού Μητρώου</h2>";

    $criminalRecordsDir = ROOT_DIR . '/public/uploads/criminal_records';
    if (!is_dir($criminalRecordsDir)) {
        echo "<p style='color: red;'>Ο φάκελος $criminalRecordsDir δεν υπάρχει.</p>";

        // Δημιουργία του φακέλου
        if (mkdir($criminalRecordsDir, 0755, true)) {
            echo "<p style='color: green;'>Ο φάκελος $criminalRecordsDir δημιουργήθηκε με επιτυχία.</p>";
        } else {
            echo "<p style='color: red;'>Αποτυχία δημιουργίας του φακέλου $criminalRecordsDir.</p>";
        }
    } else {
        echo "<p style='color: green;'>Ο φάκελος $criminalRecordsDir υπάρχει.</p>";

        // Έλεγχος δικαιωμάτων
        if (!is_writable($criminalRecordsDir)) {
            echo "<p style='color: red;'>Ο φάκελος $criminalRecordsDir δεν έχει δικαιώματα εγγραφής.</p>";

            // Προσπάθεια αλλαγής δικαιωμάτων
            if (chmod($criminalRecordsDir, 0755)) {
                echo "<p style='color: green;'>Τα δικαιώματα του φακέλου $criminalRecordsDir άλλαξαν με επιτυχία.</p>";
            } else {
                echo "<p style='color: red;'>Αποτυχία αλλαγής δικαιωμάτων του φακέλου $criminalRecordsDir.</p>";
            }
        } else {
            echo "<p style='color: green;'>Ο φάκελος $criminalRecordsDir έχει δικαιώματα εγγραφής.</p>";
        }
    }

    // Αν έχουμε το ID του οδηγού, ελέγχουμε αν τα δεδομένα του εμφανίζονται σωστά
    if ($driverId) {
        echo "<h2>Έλεγχος Δεδομένων Οδηγού</h2>";

        // Ανάκτηση των δεδομένων του οδηγού
        $stmt = $pdo->prepare("SELECT * FROM drivers WHERE id = ?");
        $stmt->execute([$driverId]);
        $driver = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($driver) {
            echo "<p style='color: green;'>Τα δεδομένα του οδηγού βρέθηκαν στη βάση δεδομένων.</p>";

            // Έλεγχος για τα πεδία legal_status και criminal_record_file
            echo "<p>Κατάσταση Ποινικού Μητρώου (legal_status): " . (isset($driver['legal_status']) ? $driver['legal_status'] : 'NULL') . "</p>";
            echo "<p>Αρχείο Ποινικού Μητρώου (criminal_record_file): " . (isset($driver['criminal_record_file']) ? $driver['criminal_record_file'] : 'NULL') . "</p>";

            // Δημιουργία του DriverProfileService
            $driverProfileService = new \Drivejob\Services\DriverProfileService($pdo);

            // Ανάκτηση του προφίλ του οδηγού
            $driverProfile = $driverProfileService->getDriverProfile($driverId);

            if ($driverProfile) {
                echo "<p style='color: green;'>Το DriverProfileService επέστρεψε δεδομένα για τον οδηγό.</p>";

                // Έλεγχος για τα πεδία legal_status και criminal_record_file
                echo "<p>Κατάσταση Ποινικού Μητρώου (legal_status) από DriverProfileService: " . (isset($driverProfile['legal_status']) ? $driverProfile['legal_status'] : 'NULL') . "</p>";
                echo "<p>Αρχείο Ποινικού Μητρώου (criminal_record_file) από DriverProfileService: " . (isset($driverProfile['criminal_record_file']) ? $driverProfile['criminal_record_file'] : 'NULL') . "</p>";

                // Σύγκριση των δεδομένων
                $legalStatusMatch = (isset($driver['legal_status']) && isset($driverProfile['legal_status']) && $driver['legal_status'] === $driverProfile['legal_status']) || (!isset($driver['legal_status']) && !isset($driverProfile['legal_status']));
                $criminalRecordFileMatch = (isset($driver['criminal_record_file']) && isset($driverProfile['criminal_record_file']) && $driver['criminal_record_file'] === $driverProfile['criminal_record_file']) || (!isset($driver['criminal_record_file']) && !isset($driverProfile['criminal_record_file']));

                echo "<p>Τα δεδομένα του πεδίου legal_status " . ($legalStatusMatch ? "ταιριάζουν" : "δεν ταιριάζουν") . " μεταξύ της βάσης δεδομένων και του DriverProfileService.</p>";
                echo "<p>Τα δεδομένα του πεδίου criminal_record_file " . ($criminalRecordFileMatch ? "ταιριάζουν" : "δεν ταιριάζουν") . " μεταξύ της βάσης δεδομένων και του DriverProfileService.</p>";
            } else {
                echo "<p style='color: red;'>Το DriverProfileService δεν επέστρεψε δεδομένα για τον οδηγό!</p>";
            }
        } else {
            echo "<p style='color: red;'>Δεν βρέθηκαν δεδομένα για τον οδηγό με ID: $driverId</p>";
        }
    }

    echo "<h2>Ολοκλήρωση</h2>";
    echo "<p>Η διαδικασία διόρθωσης ολοκληρώθηκε. Παρακαλώ ελέγξτε τα αποτελέσματα παραπάνω για να δείτε αν υπάρχουν ακόμα προβλήματα.</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>Σφάλμα βάσης δεδομένων: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Σφάλμα: " . $e->getMessage() . "</p>";
}

<?php
require 'src/bootstrap.php';

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'driver') {
    echo "Πρέπει να συνδεθείτε ως οδηγός για να δείτε αυτή τη σελίδα.";
    exit;
}

$driverId = $_SESSION['user_id'];

try {
    // Σύνδεση με τη βάση δεδομένων
    $pdo = $container->get('pdo');

    // Έλεγχος αν υπάρχει το πεδίο criminal_record_file στον πίνακα drivers
    $stmt = $pdo->query("SHOW COLUMNS FROM drivers LIKE 'criminal_record_file'");
    $columnExists = $stmt->fetch();

    echo "<h2>Διαγνωστικά Ποινικού Μητρώου</h2>";

    if (!$columnExists) {
        echo "<p style='color: red;'>Το πεδίο criminal_record_file δεν υπάρχει στον πίνακα drivers!</p>";
        echo "<p>Εκτελέστε το script add_criminal_record_field.php για να προσθέσετε το πεδίο.</p>";
    } else {
        echo "<p style='color: green;'>Το πεδίο criminal_record_file υπάρχει στον πίνακα drivers.</p>";

        // Έλεγχος αν υπάρχει ο φάκελος για τα αρχεία ποινικού μητρώου
        $criminalRecordsDir = ROOT_DIR . '/public/uploads/criminal_records';
        if (!is_dir($criminalRecordsDir)) {
            echo "<p style='color: red;'>Ο φάκελος $criminalRecordsDir δεν υπάρχει!</p>";
            echo "<p>Εκτελέστε το script create_criminal_records_folder.php για να δημιουργήσετε τον φάκελο.</p>";
        } else {
            echo "<p style='color: green;'>Ο φάκελος $criminalRecordsDir υπάρχει.</p>";

            // Έλεγχος δικαιωμάτων φακέλου
            if (!is_writable($criminalRecordsDir)) {
                echo "<p style='color: red;'>Ο φάκελος $criminalRecordsDir δεν έχει δικαιώματα εγγραφής!</p>";
            } else {
                echo "<p style='color: green;'>Ο φάκελος $criminalRecordsDir έχει δικαιώματα εγγραφής.</p>";
            }
        }

        // Ανάκτηση των δεδομένων του οδηγού
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, legal_status, criminal_record_file FROM drivers WHERE id = ?");
        $stmt->execute([$driverId]);
        $driver = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($driver) {
            echo "<h3>Δεδομένα Οδηγού</h3>";
            echo "<p>ID: " . $driver['id'] . "</p>";
            echo "<p>Όνομα: " . $driver['first_name'] . " " . $driver['last_name'] . "</p>";
            echo "<p>Κατάσταση Ποινικού Μητρώου (legal_status): " . ($driver['legal_status'] ?: 'Δεν έχει οριστεί') . "</p>";
            echo "<p>Αρχείο Ποινικού Μητρώου (criminal_record_file): " . ($driver['criminal_record_file'] ?: 'Δεν έχει οριστεί') . "</p>";

            if ($driver['criminal_record_file']) {
                $filePath = ROOT_DIR . '/public/' . $driver['criminal_record_file'];
                if (file_exists($filePath)) {
                    echo "<p style='color: green;'>Το αρχείο υπάρχει στο σύστημα αρχείων.</p>";
                    echo "<p>Διαδρομή: $filePath</p>";
                    echo "<p>Μέγεθος: " . filesize($filePath) . " bytes</p>";
                    echo "<p><a href='" . BASE_URL . $driver['criminal_record_file'] . "' target='_blank'>Προβολή αρχείου</a></p>";
                } else {
                    echo "<p style='color: red;'>Το αρχείο δεν υπάρχει στο σύστημα αρχείων!</p>";
                    echo "<p>Διαδρομή που αναζητήθηκε: $filePath</p>";
                }
            }
        } else {
            echo "<p style='color: red;'>Δεν βρέθηκαν δεδομένα για τον οδηγό με ID: $driverId</p>";
        }
    }

    // Έλεγχος του κώδικα JavaScript
    echo "<h3>Έλεγχος JavaScript</h3>";
    $jsFilePath = ROOT_DIR . '/public/js/criminal-record-toggle.js';
    if (file_exists($jsFilePath)) {
        echo "<p style='color: green;'>Το αρχείο criminal-record-toggle.js υπάρχει.</p>";
        echo "<p>Περιεχόμενο:</p>";
        echo "<pre>" . htmlspecialchars(file_get_contents($jsFilePath)) . "</pre>";
    } else {
        echo "<p style='color: red;'>Το αρχείο criminal-record-toggle.js δεν υπάρχει!</p>";
    }

    // Έλεγχος αν το αρχείο JavaScript συμπεριλαμβάνεται στο edit_profile.php
    $editProfilePath = ROOT_DIR . '/src/Views/drivers/edit_profile.php';
    if (file_exists($editProfilePath)) {
        $editProfileContent = file_get_contents($editProfilePath);
        if (strpos($editProfileContent, 'criminal-record-toggle.js') !== false) {
            echo "<p style='color: green;'>Το αρχείο criminal-record-toggle.js συμπεριλαμβάνεται στο edit_profile.php.</p>";
        } else {
            echo "<p style='color: red;'>Το αρχείο criminal-record-toggle.js ΔΕΝ συμπεριλαμβάνεται στο edit_profile.php!</p>";
        }
    } else {
        echo "<p style='color: red;'>Το αρχείο edit_profile.php δεν βρέθηκε!</p>";
    }

    // Έλεγχος του κώδικα HTML για το ποινικό μητρώο
    if (file_exists($editProfilePath)) {
        $editProfileContent = file_get_contents($editProfilePath);
        if (preg_match('/<div[^>]*id="criminal_record_upload"[^>]*>.*?<\/div>/s', $editProfileContent, $matches)) {
            echo "<p style='color: green;'>Βρέθηκε το div με id='criminal_record_upload' στο edit_profile.php.</p>";
            echo "<p>Κώδικας:</p>";
            echo "<pre>" . htmlspecialchars($matches[0]) . "</pre>";
        } else {
            echo "<p style='color: red;'>Δεν βρέθηκε το div με id='criminal_record_upload' στο edit_profile.php!</p>";
        }

        if (preg_match('/<input[^>]*type="radio"[^>]*name="legal_status"[^>]*>.*?<input[^>]*type="radio"[^>]*name="legal_status"[^>]*>/s', $editProfileContent, $matches)) {
            echo "<p style='color: green;'>Βρέθηκαν τα radio buttons για το legal_status στο edit_profile.php.</p>";
            echo "<p>Κώδικας:</p>";
            echo "<pre>" . htmlspecialchars($matches[0]) . "</pre>";
        } else {
            echo "<p style='color: red;'>Δεν βρέθηκαν τα radio buttons για το legal_status στο edit_profile.php!</p>";
        }
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Σφάλμα βάσης δεδομένων: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Σφάλμα: " . $e->getMessage() . "</p>";
}

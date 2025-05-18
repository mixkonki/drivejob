<?php
// Ορισμός των παραμέτρων σύνδεσης στη βάση δεδομένων
$host = 'localhost';
$db = 'drivejob';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Περιγραφή του πίνακα job_listings
    $stmt = $pdo->query('DESCRIBE job_listings');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "=== Δομή του πίνακα job_listings ===\n";
    foreach ($columns as $column) {
        echo "Στήλη: " . $column['Field'] . "\n";
        echo "Τύπος: " . $column['Type'] . "\n";
        echo "Null: " . $column['Null'] . "\n";
        echo "Default: " . $column['Default'] . "\n";
        echo "------------------------\n";
    }

    // Έλεγχος για τη στήλη transport_type
    $hasTransportType = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'transport_type') {
            $hasTransportType = true;
            break;
        }
    }

    if (!$hasTransportType) {
        echo "ΠΡΟΣΟΧΗ: Η στήλη 'transport_type' δεν υπάρχει στον πίνακα job_listings!\n";
    }

    // Έλεγχος για τη στήλη machinery_types
    $hasMachineryTypes = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'machinery_types') {
            $hasMachineryTypes = true;
            break;
        }
    }

    if (!$hasMachineryTypes) {
        echo "ΠΡΟΣΟΧΗ: Η στήλη 'machinery_types' δεν υπάρχει στον πίνακα job_listings!\n";
    }

    // Έλεγχος για τον controller που χειρίζεται την αποθήκευση των αγγελιών
    echo "\n=== Έλεγχος του controller για την αποθήκευση αγγελιών ===\n";

    // Έλεγχος του JobListingController
    if (file_exists('src/Controllers/JobListingController.php')) {
        $controllerContent = file_get_contents('src/Controllers/JobListingController.php');
        echo "Ο controller JobListingController.php υπάρχει.\n";

        // Έλεγχος για τη μέθοδο store
        if (strpos($controllerContent, 'function store') !== false) {
            echo "Η μέθοδος store() υπάρχει στον JobListingController.\n";

            // Έλεγχος για τη χρήση του transport_type
            if (strpos($controllerContent, 'transport_type') !== false) {
                echo "Ο controller χρησιμοποιεί το πεδίο transport_type.\n";
            } else {
                echo "ΠΡΟΣΟΧΗ: Ο controller δεν χρησιμοποιεί το πεδίο transport_type!\n";
            }

            // Έλεγχος για τη χρήση του machinery_types
            if (strpos($controllerContent, 'machinery_types') !== false) {
                echo "Ο controller χρησιμοποιεί το πεδίο machinery_types.\n";
            } else {
                echo "ΠΡΟΣΟΧΗ: Ο controller δεν χρησιμοποιεί το πεδίο machinery_types!\n";
            }
        } else {
            echo "ΠΡΟΣΟΧΗ: Η μέθοδος store() δεν βρέθηκε στον JobListingController!\n";
        }
    } else {
        echo "ΠΡΟΣΟΧΗ: Ο controller JobListingController.php δεν βρέθηκε!\n";
    }

    // Έλεγχος του NewJobListingController
    if (file_exists('src/Controllers/NewJobListingController.php')) {
        $controllerContent = file_get_contents('src/Controllers/NewJobListingController.php');
        echo "\nΟ controller NewJobListingController.php υπάρχει.\n";

        // Έλεγχος για τη μέθοδο store
        if (strpos($controllerContent, 'function store') !== false) {
            echo "Η μέθοδος store() υπάρχει στον NewJobListingController.\n";

            // Έλεγχος για τη χρήση του transport_type
            if (strpos($controllerContent, 'transport_type') !== false) {
                echo "Ο controller χρησιμοποιεί το πεδίο transport_type.\n";
            } else {
                echo "ΠΡΟΣΟΧΗ: Ο controller δεν χρησιμοποιεί το πεδίο transport_type!\n";
            }

            // Έλεγχος για τη χρήση του machinery_types
            if (strpos($controllerContent, 'machinery_types') !== false) {
                echo "Ο controller χρησιμοποιεί το πεδίο machinery_types.\n";
            } else {
                echo "ΠΡΟΣΟΧΗ: Ο controller δεν χρησιμοποιεί το πεδίο machinery_types!\n";
            }
        } else {
            echo "ΠΡΟΣΟΧΗ: Η μέθοδος store() δεν βρέθηκε στον NewJobListingController!\n";
        }
    } else {
        echo "ΠΡΟΣΟΧΗ: Ο controller NewJobListingController.php δεν βρέθηκε!\n";
    }
} catch (PDOException $e) {
    echo "Σφάλμα βάσης δεδομένων: " . $e->getMessage();
}

<?php
// Αρχικοποίηση της εφαρμογής
$container = require_once __DIR__ . '/../../src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Services\DriverProfileService;
use Drivejob\Core\Logger;

// Ενεργοποίηση καταγραφής σφαλμάτων
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Αρχικοποίηση του Logger
Logger::init();
Logger::info("Εκτέλεση του update_rating.php", "UpdateRating");

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι οδηγός
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<div class="debug-error">Δεν έχετε δικαίωμα πρόσβασης σε αυτή τη σελίδα.</div>';
    exit();
}

// Έλεγχος αν η φόρμα υποβλήθηκε με τη μέθοδο POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<div class="debug-error">Η φόρμα πρέπει να υποβληθεί με τη μέθοδο POST.</div>';
    exit();
}

// Λήψη του ID του οδηγού
$driverId = $_SESSION['user_id'];

// Λήψη των δεδομένων από τη φόρμα
$freightYears = isset($_POST['freight_years']) ? intval($_POST['freight_years']) : 0;
$passengerYears = isset($_POST['passenger_years']) ? intval($_POST['passenger_years']) : 0;

// Λήψη του PDO από το container
$pdo = $container->get('pdo');

// Υπολογισμός βαθμολογίας για προϋπηρεσία εμπορευματικών μεταφορών
$freightExperiencePoints = 0;
$freightExperienceRange = "";

if ($freightYears <= 1) {
    $freightExperiencePoints = 0;
    $freightExperienceRange = "0-1 έτος";
} elseif ($freightYears <= 3) {
    $freightExperiencePoints = 10;
    $freightExperienceRange = "2-3 έτη";
} elseif ($freightYears <= 5) {
    $freightExperiencePoints = 20;
    $freightExperienceRange = "4-5 έτη";
} elseif ($freightYears <= 8) {
    $freightExperiencePoints = 30;
    $freightExperienceRange = "6-8 έτη";
} else {
    $freightExperiencePoints = 40;
    $freightExperienceRange = "9+ έτη";
}

// Υπολογισμός βαθμολογίας για προϋπηρεσία επιβατικών μεταφορών
$passengerExperiencePoints = 0;
$passengerExperienceRange = "";

if ($passengerYears <= 1) {
    $passengerExperiencePoints = 0;
    $passengerExperienceRange = "0-1 έτος";
} elseif ($passengerYears <= 3) {
    $passengerExperiencePoints = 10;
    $passengerExperienceRange = "2-3 έτη";
} elseif ($passengerYears <= 5) {
    $passengerExperiencePoints = 20;
    $passengerExperienceRange = "4-5 έτη";
} elseif ($passengerYears <= 8) {
    $passengerExperiencePoints = 30;
    $passengerExperienceRange = "6-8 έτη";
} else {
    $passengerExperiencePoints = 40;
    $passengerExperienceRange = "9+ έτη";
}

// Ενημέρωση του πεδίου experience_years στον πίνακα drivers
$query = "UPDATE drivers SET experience_years = :experience_years WHERE id = :driver_id";
$stmt = $pdo->prepare($query);
$result1 = $stmt->execute([
    'experience_years' => max($freightYears, $passengerYears),
    'driver_id' => $driverId
]);

// Έλεγχος αν υπάρχει ο πίνακας driver_rating_details
$result2 = true;
try {
    $query = "SHOW TABLES LIKE 'driver_rating_details'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $tableExists = $stmt->fetchColumn();

    if ($tableExists) {
        // Έλεγχος αν υπάρχει εγγραφή στον πίνακα driver_rating_details
        $query = "SELECT * FROM driver_rating_details WHERE driver_id = :driver_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['driver_id' => $driverId]);
        $driverRatingDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($driverRatingDetails) {
            // Ενημέρωση των πεδίων freight_experience και passenger_experience
            $query = "UPDATE driver_rating_details SET freight_experience = :freight_experience, passenger_experience = :passenger_experience WHERE driver_id = :driver_id";
            $stmt = $pdo->prepare($query);
            $result2 = $stmt->execute([
                'freight_experience' => $freightExperiencePoints,
                'passenger_experience' => $passengerExperiencePoints,
                'driver_id' => $driverId
            ]);
        } else {
            // Δημιουργία νέας εγγραφής
            $query = "INSERT INTO driver_rating_details (driver_id, freight_experience, passenger_experience) VALUES (:driver_id, :freight_experience, :passenger_experience)";
            $stmt = $pdo->prepare($query);
            $result2 = $stmt->execute([
                'driver_id' => $driverId,
                'freight_experience' => $freightExperiencePoints,
                'passenger_experience' => $passengerExperiencePoints
            ]);
        }
    } else {
        // Δημιουργία του πίνακα driver_rating_details
        $query = "CREATE TABLE driver_rating_details (
            id INT(11) NOT NULL AUTO_INCREMENT,
            driver_id INT(11) NOT NULL,
            freight_experience INT(11) NOT NULL DEFAULT 0,
            passenger_experience INT(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY driver_id (driver_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        // Δημιουργία νέας εγγραφής
        $query = "INSERT INTO driver_rating_details (driver_id, freight_experience, passenger_experience) VALUES (:driver_id, :freight_experience, :passenger_experience)";
        $stmt = $pdo->prepare($query);
        $result2 = $stmt->execute([
            'driver_id' => $driverId,
            'freight_experience' => $freightExperiencePoints,
            'passenger_experience' => $passengerExperiencePoints
        ]);

        Logger::info("Δημιουργήθηκε ο πίνακας driver_rating_details", "UpdateRating");
    }
} catch (PDOException $e) {
    Logger::error("Σφάλμα κατά τον έλεγχο ή τη δημιουργία του πίνακα driver_rating_details: " . $e->getMessage(), "UpdateRating");
    $result2 = false;
}

// Έλεγχος αν υπάρχει ο πίνακας driver_ratings
$result3 = true;
try {
    $query = "SHOW TABLES LIKE 'driver_ratings'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $tableExists = $stmt->fetchColumn();

    // Υπολογισμός της συνολικής βαθμολογίας προϋπηρεσίας
    $experienceScore = max($freightExperiencePoints, $passengerExperiencePoints);

    if ($tableExists) {
        // Έλεγχος αν υπάρχει εγγραφή στον πίνακα driver_ratings
        $query = "SELECT * FROM driver_ratings WHERE driver_id = :driver_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['driver_id' => $driverId]);
        $driverRating = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($driverRating) {
            // Ενημέρωση του πεδίου experience_score
            $query = "UPDATE driver_ratings SET experience_score = :experience_score WHERE driver_id = :driver_id";
            $stmt = $pdo->prepare($query);
            $result3 = $stmt->execute([
                'experience_score' => $experienceScore,
                'driver_id' => $driverId
            ]);
        } else {
            // Δημιουργία νέας εγγραφής
            $query = "INSERT INTO driver_ratings (driver_id, experience_score) VALUES (:driver_id, :experience_score)";
            $stmt = $pdo->prepare($query);
            $result3 = $stmt->execute([
                'driver_id' => $driverId,
                'experience_score' => $experienceScore
            ]);
        }
    } else {
        // Δημιουργία του πίνακα driver_ratings
        $query = "CREATE TABLE driver_ratings (
            id INT(11) NOT NULL AUTO_INCREMENT,
            driver_id INT(11) NOT NULL,
            rating DECIMAL(3,1) DEFAULT NULL,
            experience_score INT(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY driver_id (driver_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $stmt = $pdo->prepare($query);
        $stmt->execute();

        // Δημιουργία νέας εγγραφής
        $query = "INSERT INTO driver_ratings (driver_id, experience_score) VALUES (:driver_id, :experience_score)";
        $stmt = $pdo->prepare($query);
        $result3 = $stmt->execute([
            'driver_id' => $driverId,
            'experience_score' => $experienceScore
        ]);

        Logger::info("Δημιουργήθηκε ο πίνακας driver_ratings", "UpdateRating");
    }
} catch (PDOException $e) {
    Logger::error("Σφάλμα κατά τον έλεγχο ή τη δημιουργία του πίνακα driver_ratings: " . $e->getMessage(), "UpdateRating");
    $result3 = false;
}

// Αποθήκευση των τιμών στο localStorage
echo '<script>
    localStorage.setItem("drivejob_freight_years", "' . $freightYears . '");
    localStorage.setItem("drivejob_passenger_years", "' . $passengerYears . '");
    localStorage.setItem("drivejob_last_update", new Date().toISOString());
</script>';

// Εμφάνιση των αποτελεσμάτων
if ($result1 && $result2 && $result3) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<div class="debug-success">
        <p>Η βαθμολογία προϋπηρεσίας ενημερώθηκε επιτυχώς.</p>
        <p><strong>Εμπορευματικές μεταφορές:</strong> ' . $freightYears . ' έτη (' . $freightExperienceRange . ', ' . $freightExperiencePoints . ' βαθμοί)</p>
        <p><strong>Επιβατικές μεταφορές:</strong> ' . $passengerYears . ' έτη (' . $passengerExperienceRange . ', ' . $passengerExperiencePoints . ' βαθμοί)</p>
        <p>Οι τιμές αποθηκεύτηκαν στο localStorage και θα χρησιμοποιηθούν για την ενημέρωση της σελίδας αξιολόγησης οδηγού.</p>
    </div>';
} else {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<div class="debug-error">
        <p>Παρουσιάστηκε σφάλμα κατά την ενημέρωση της βαθμολογίας προϋπηρεσίας.</p>
        <p>Αποτέλεσμα ενημέρωσης drivers: ' . ($result1 ? 'Επιτυχία' : 'Αποτυχία') . '</p>
        <p>Αποτέλεσμα ενημέρωσης driver_rating_details: ' . ($result2 ? 'Επιτυχία' : 'Αποτυχία') . '</p>
        <p>Αποτέλεσμα ενημέρωσης driver_rating: ' . ($result3 ? 'Επιτυχία' : 'Αποτυχία') . '</p>
    </div>';
}

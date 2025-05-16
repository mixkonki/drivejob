<?php

/**
 * Script για τον έλεγχο των δεδομένων του οδηγού
 * 
 * Αυτό το script εμφανίζει τα δεδομένα του οδηγού και επιτρέπει την επεξεργασία του πεδίου legal_status
 * και του αρχείου criminal_record_file.
 */

// Αρχικοποίηση της εφαρμογής
require_once __DIR__ . '/src/bootstrap.php';

// Λήψη του PDO από το container
$pdo = $container->get('pdo');

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    echo "<h1>Δεν είστε συνδεδεμένος ως οδηγός</h1>";
    echo "<p>Παρακαλώ <a href='" . BASE_URL . "login'>συνδεθείτε</a> για να δείτε τα δεδομένα σας.</p>";
    exit;
}

// Λήψη του ID του οδηγού
$driverId = $_SESSION['user_id'];

// Δημιουργία του DriverProfileService
$driverProfileService = new \Drivejob\Services\DriverProfileService($pdo);

// Ανάκτηση του προφίλ του οδηγού
$driverProfile = $driverProfileService->getDriverProfile($driverId);

if (!$driverProfile) {
    echo "<h1>Σφάλμα</h1>";
    echo "<p>Δεν βρέθηκε το προφίλ του οδηγού.</p>";
    exit;
}

// Επεξεργασία της φόρμας
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Έλεγχος για CSRF token
    if (!isset($_POST['csrf_token']) || !\Drivejob\Core\CSRF::validateToken($_POST['csrf_token'])) {
        echo "<h1>Σφάλμα</h1>";
        echo "<p>Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.</p>";
        exit;
    }

    // Συλλογή των δεδομένων από τη φόρμα
    $data = [
        'legal_status' => $_POST['legal_status'] ?? null
    ];

    // Καταγραφή για εντοπισμό σφαλμάτων
    \Drivejob\Core\Logger::info("Επεξεργασία προφίλ οδηγού - legal_status: " . ($data['legal_status'] ?? 'null'));

    // Επεξεργασία του αρχείου ποινικού μητρώου
    if (isset($_FILES['criminal_record_file']) && $_FILES['criminal_record_file']['error'] === UPLOAD_ERR_OK) {
        // Βεβαιωνόμαστε ότι το legal_status είναι 'yes' αν ανεβάζουμε αρχείο
        if ($data['legal_status'] !== 'yes') {
            $data['legal_status'] = 'yes';
            \Drivejob\Core\Logger::info("Ορισμός legal_status σε 'yes' λόγω ανεβάσματος αρχείου");
        }

        // Δημιουργία του φακέλου αν δεν υπάρχει
        $uploadDir = ROOT_DIR . '/public/uploads/criminal_records/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Δημιουργία μοναδικού ονόματος αρχείου
        $extension = pathinfo($_FILES['criminal_record_file']['name'], PATHINFO_EXTENSION);
        $filename = 'criminal_record_' . $driverId . '_' . uniqid() . '.' . $extension;
        $destination = $uploadDir . $filename;

        // Μετακίνηση του αρχείου
        if (move_uploaded_file($_FILES['criminal_record_file']['tmp_name'], $destination)) {
            $data['criminal_record_file'] = 'uploads/criminal_records/' . $filename;
            \Drivejob\Core\Logger::info("Αρχείο ποινικού μητρώου ανέβηκε επιτυχώς: " . $data['criminal_record_file']);
        } else {
            \Drivejob\Core\Logger::error("Αποτυχία ανεβάσματος αρχείου ποινικού μητρώου");
        }
    }

    // Ενημέρωση του προφίλ
    $updateResult = $driverProfileService->updateProfile($driverId, $data);

    if ($updateResult) {
        echo "<div style='background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 4px;'>Το προφίλ ενημερώθηκε με επιτυχία.</div>";
        // Ανανέωση του προφίλ
        $driverProfile = $driverProfileService->getDriverProfile($driverId);
    } else {
        echo "<div style='background-color: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 20px; border-radius: 4px;'>Υπήρξε ένα σφάλμα κατά την ενημέρωση του προφίλ.</div>";
    }
}

// Εμφάνιση των δεδομένων του οδηγού
?>
<!DOCTYPE html>
<html lang="el">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Έλεγχος Δεδομένων Οδηγού</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/toggle-switch.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="file"] {
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
        }

        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }

        .data-section {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
        }

        .data-item {
            margin-bottom: 10px;
        }

        .data-label {
            font-weight: bold;
        }

        .current-file {
            margin-top: 10px;
            padding: 5px;
            background-color: #e9ecef;
            border-radius: 4px;
        }

        .current-file a {
            color: #007bff;
            text-decoration: none;
        }

        .current-file a:hover {
            text-decoration: underline;
        }

        .debug-info {
            margin-top: 10px;
            padding: 5px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 12px;
            color: #666;
        }
    </style>
    <script src="<?php echo BASE_URL; ?>js/criminal-record-toggle.js"></script>
</head>

<body>
    <h1>Έλεγχος Δεδομένων Οδηγού</h1>

    <div class="data-section">
        <h2>Δεδομένα Οδηγού</h2>
        <div class="data-item">
            <span class="data-label">ID:</span> <?php echo $driverProfile['id']; ?>
        </div>
        <div class="data-item">
            <span class="data-label">Όνομα:</span> <?php echo htmlspecialchars($driverProfile['first_name']); ?>
        </div>
        <div class="data-item">
            <span class="data-label">Επώνυμο:</span> <?php echo htmlspecialchars($driverProfile['last_name']); ?>
        </div>
        <div class="data-item">
            <span class="data-label">Email:</span> <?php echo htmlspecialchars($driverProfile['email']); ?>
        </div>
        <div class="data-item">
            <span class="data-label">Κατάσταση Ποινικού Μητρώου (legal_status):</span> <?php echo isset($driverProfile['legal_status']) ? $driverProfile['legal_status'] : 'Δεν έχει οριστεί'; ?>
        </div>
        <div class="data-item">
            <span class="data-label">Αρχείο Ποινικού Μητρώου (criminal_record_file):</span>
            <?php if (isset($driverProfile['criminal_record_file']) && $driverProfile['criminal_record_file']) : ?>
                <div class="current-file">
                    <a href="<?php echo BASE_URL . htmlspecialchars($driverProfile['criminal_record_file']); ?>" target="_blank">Προβολή αρχείου</a>
                </div>
            <?php else : ?>
                Δεν έχει οριστεί
            <?php endif; ?>
        </div>
    </div>

    <div class="data-section">
        <h2>Επεξεργασία Ποινικού Μητρώου</h2>
        <form method="post" enctype="multipart/form-data">
            <?php echo \Drivejob\Core\CSRF::tokenField(); ?>

            <div class="form-group">
                <label>Ποινικό Μητρώο</label>
                <div class="radio-group">
                    <!-- Τα radio buttons παραμένουν για συμβατότητα αλλά θα κρύβονται με CSS -->
                    <label class="radio-inline" style="display: none;">
                        <input type="radio" name="legal_status" value="yes" <?php echo (isset($driverProfile['legal_status']) && $driverProfile['legal_status'] == 'yes') ? 'checked' : ''; ?>> Ναι
                    </label>
                    <label class="radio-inline" style="display: none;">
                        <input type="radio" name="legal_status" value="no" <?php echo (isset($driverProfile['legal_status']) && $driverProfile['legal_status'] == 'no') ? 'checked' : ''; ?>> Όχι
                    </label>

                    <!-- Το div για το ανέβασμα αρχείου -->
                    <div id="criminal_record_upload" class="criminal-record-upload" style="<?php echo (isset($driverProfile['legal_status']) && $driverProfile['legal_status'] == 'yes') ? 'display:inline-block;' : 'display:none;'; ?> margin-left: 20px;">
                        <label for="criminal_record_file" class="file-upload-label">Ανέβασμα αρχείου:</label>
                        <input type="file" id="criminal_record_file" name="criminal_record_file" accept=".pdf,.jpg,.jpeg,.png">
                        <?php if (isset($driverProfile['criminal_record_file']) && $driverProfile['criminal_record_file']) : ?>
                            <div class="current-file">
                                <a href="<?php echo BASE_URL . htmlspecialchars($driverProfile['criminal_record_file']); ?>" target="_blank">Προβολή τρέχοντος αρχείου</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <p class="form-hint">Επιλέξτε "Ναι" για να ανεβάστε το αρχείο. Μέγιστο μέγεθος: 5MB. Επιτρεπόμενοι τύποι: PDF, JPG, JPEG, PNG</p>

                <!-- Προσθήκη διαγνωστικών πληροφοριών -->
                <div class="debug-info">
                    <p>Κατάσταση: <?php echo (isset($driverProfile['legal_status'])) ? $driverProfile['legal_status'] : 'Δεν έχει οριστεί'; ?></p>
                    <p>Αρχείο: <?php echo (isset($driverProfile['criminal_record_file']) && $driverProfile['criminal_record_file']) ? $driverProfile['criminal_record_file'] : 'Δεν έχει οριστεί'; ?></p>
                </div>
            </div>

            <button type="submit">Αποθήκευση</button>
        </form>
    </div>

    <div class="data-section">
        <h2>Αρχεία Καταγραφής (Logs)</h2>
        <div class="debug-info">
            <?php
            // Εμφάνιση των τελευταίων 10 καταγραφών
            $logFile = ROOT_DIR . '/logs/app.log';
            if (file_exists($logFile)) {
                $logs = file($logFile);
                $logs = array_reverse($logs);
                $logs = array_slice($logs, 0, 10);
                foreach ($logs as $log) {
                    echo "<p>" . htmlspecialchars($log) . "</p>";
                }
            } else {
                echo "<p>Δεν βρέθηκε αρχείο καταγραφής.</p>";
            }
            ?>
        </div>
    </div>
</body>

</html>
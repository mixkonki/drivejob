<?php

// Αρχικοποίηση της εφαρμογής
$container = require_once __DIR__ . '/../../src/bootstrap.php';

// Λήψη του PDO από το container
$pdo = $container->get('pdo');

// Έλεγχος αν ο χρήστης είναι συνδεδεμένος και είναι οδηγός
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'driver') {
    header('Location: ' . BASE_URL . 'login.php');
    exit();
}

// Λήψη του ID του οδηγού
$driverId = $_SESSION['user_id'];

// Συμπερίληψη του header
include ROOT_DIR . '/src/Views/header.php';
?>

<style>
    .debug-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .debug-section {
        margin-bottom: 30px;
        padding: 20px;
        background-color: #f9f9f9;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .debug-section h2 {
        margin-top: 0;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }

    .debug-code {
        background-color: #f5f5f5;
        padding: 15px;
        border-radius: 5px;
        font-family: monospace;
        overflow-x: auto;
        white-space: pre-wrap;
    }

    .debug-error {
        color: #d9534f;
        font-weight: bold;
    }

    .debug-success {
        color: #5cb85c;
        font-weight: bold;
    }

    .debug-warning {
        color: #f0ad4e;
        font-weight: bold;
    }

    .debug-info {
        color: #5bc0de;
        font-weight: bold;
    }

    .debug-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    .debug-table th,
    .debug-table td {
        padding: 10px;
        border: 1px solid #ddd;
        text-align: left;
    }

    .debug-table th {
        background-color: #f2f2f2;
        font-weight: bold;
    }

    .debug-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .debug-form {
        margin-top: 20px;
        padding: 20px;
        background-color: #f0f8ff;
        border-radius: 5px;
    }

    .debug-form label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }

    .debug-form input,
    .debug-form select,
    .debug-form textarea {
        width: 100%;
        padding: 8px;
        margin-bottom: 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .debug-form button {
        background-color: #4CAF50;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .debug-form button:hover {
        background-color: #45a049;
    }
</style>

<main class="debug-container">
    <h1>Διόρθωση Προβλήματος Προϋπηρεσίας Οχημάτων</h1>

    <div class="debug-section">
        <h2>Διάγνωση Προβλήματος</h2>
        <p>Εντοπίστηκε πρόβλημα στη μέθοδο <code>updateDriverVehicleExperience</code> στο αρχείο <code>SkillModel.php</code>.</p>
        <p>Το πρόβλημα είναι ότι υπάρχει ασυμφωνία μεταξύ των πεδίων που αναμένονται στο SQL ερώτημα και των πεδίων που αποστέλλονται από τη φόρμα.</p>

        <h3>Προβληματικό SQL Ερώτημα:</h3>
        <div class="debug-code">
            INSERT INTO driver_vehicle_experience (
            driver_id, vehicle_category, vehicle_type, transport_type, employment_type,
            years, months, days, start_date, end_date, description
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        </div>

        <h3>Πρόβλημα:</h3>
        <ul>
            <li>Το SQL ερώτημα αναμένει πεδία όπως <code>vehicle_category</code>, αλλά η φόρμα στέλνει <code>transport_type</code> και <code>vehicle_type</code>.</li>
            <li>Ο κώδικας ελέγχει για την ύπαρξη του πεδίου <code>vehicle_category</code>, το οποίο μπορεί να μην υπάρχει στα δεδομένα που αποστέλλονται από τη φόρμα.</li>
        </ul>
    </div>

    <div class="debug-section">
        <h2>Έλεγχος Δομής Πίνακα</h2>

        <?php
        try {
            // Έλεγχος αν υπάρχει ο πίνακας
            $tableCheckStmt = $pdo->query("SHOW TABLES LIKE 'driver_vehicle_experience'");
            $tableExists = $tableCheckStmt->rowCount() > 0;

            if ($tableExists) {
                echo "<p class='debug-success'>Ο πίνακας driver_vehicle_experience υπάρχει.</p>";

                // Έλεγχος της δομής του πίνακα
                $columnsStmt = $pdo->query("SHOW COLUMNS FROM driver_vehicle_experience");
                $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);

                echo "<h3>Δομή Πίνακα</h3>";
                echo "<table class='debug-table'>";
                echo "<thead><tr><th>Πεδίο</th><th>Τύπος</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>";
                echo "<tbody>";

                foreach ($columns as $column) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
                    echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
                    echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
                    echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
                    echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
                    echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
                    echo "</tr>";
                }

                echo "</tbody></table>";

                // Έλεγχος αν υπάρχουν τα απαραίτητα πεδία
                $requiredFields = ['transport_type', 'vehicle_type'];
                $missingFields = [];

                foreach ($requiredFields as $field) {
                    $found = false;
                    foreach ($columns as $column) {
                        if ($column['Field'] === $field) {
                            $found = true;
                            break;
                        }
                    }

                    if (!$found) {
                        $missingFields[] = $field;
                    }
                }

                if (!empty($missingFields)) {
                    echo "<p class='debug-error'>Λείπουν τα ακόλουθα πεδία από τον πίνακα: " . implode(', ', $missingFields) . "</p>";

                    // Προτεινόμενο SQL για την προσθήκη των πεδίων που λείπουν
                    echo "<h3>Προτεινόμενο SQL για την προσθήκη των πεδίων που λείπουν</h3>";
                    echo "<div class='debug-code'>";

                    foreach ($missingFields as $field) {
                        if ($field === 'transport_type') {
                            echo "ALTER TABLE driver_vehicle_experience ADD COLUMN transport_type ENUM('freight', 'passenger') NOT NULL DEFAULT 'freight' AFTER driver_id;\n";
                        } elseif ($field === 'vehicle_type') {
                            echo "ALTER TABLE driver_vehicle_experience ADD COLUMN vehicle_type VARCHAR(100) NOT NULL AFTER transport_type;\n";
                        }
                    }

                    echo "</div>";
                } else {
                    echo "<p class='debug-success'>Όλα τα απαραίτητα πεδία υπάρχουν στον πίνακα.</p>";
                }
            } else {
                echo "<p class='debug-error'>Ο πίνακας driver_vehicle_experience δεν υπάρχει!</p>";

                // Προτεινόμενο SQL για τη δημιουργία του πίνακα
                echo "<h3>Προτεινόμενο SQL για τη δημιουργία του πίνακα</h3>";
                echo "<div class='debug-code'>";
                echo "CREATE TABLE driver_vehicle_experience (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    driver_id INT(11) UNSIGNED NOT NULL,
    transport_type ENUM('freight', 'passenger') NOT NULL DEFAULT 'freight',
    vehicle_type VARCHAR(100) NOT NULL,
    years INT(11) NOT NULL DEFAULT 0,
    months INT(11) NOT NULL DEFAULT 0,
    days INT(11) NOT NULL DEFAULT 0,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (driver_id),
    INDEX (transport_type),
    INDEX (vehicle_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                echo "</div>";
            }
        } catch (PDOException $e) {
            echo "<p class='debug-error'>Σφάλμα κατά τον έλεγχο του πίνακα: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>

    <div class="debug-section">
        <h2>Διόρθωση Μεθόδου updateDriverVehicleExperience</h2>

        <p>Η μέθοδος <code>updateDriverVehicleExperience</code> στο αρχείο <code>SkillModel.php</code> πρέπει να τροποποιηθεί ώστε να χρησιμοποιεί τα σωστά πεδία.</p>

        <h3>Προτεινόμενη Διόρθωση:</h3>
        <div class="debug-code">
            public function updateDriverVehicleExperience($driverId, $vehicleExperience)
            {
            try {
            // Δημιουργία αρχείου καταγραφής για διαγνωστικούς σκοπούς
            $logFile = ROOT_DIR . '/logs/vehicle_experience_debug.log';
            file_put_contents($logFile, "=== " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);
            file_put_contents($logFile, "Driver ID: $driverId\n", FILE_APPEND);
            file_put_contents($logFile, "Vehicle experience data: " . print_r($vehicleExperience, true) . "\n", FILE_APPEND);

            // Καταγραφή των δεδομένων για διαγνωστικούς σκοπούς
            Logger::info("updateDriverVehicleExperience called for driver_id: $driverId", "VehicleExperience");
            Logger::info("Vehicle experience data: " . print_r($vehicleExperience, true), "VehicleExperience");

            // Διαγραφή προηγούμενης εμπειρίας
            $deleteResult = $this->deleteDriverVehicleExperience($driverId);
            file_put_contents($logFile, "Delete result: " . ($deleteResult ? 'success' : 'failure') . "\n", FILE_APPEND);
            Logger::info("Delete result: " . ($deleteResult ? 'success' : 'failure'), "VehicleExperience");

            // Αν δεν υπάρχει νέα εμπειρία, επιστρέφουμε επιτυχία
            if (empty($vehicleExperience)) {
            Logger::info("No vehicle experience data to insert", "VehicleExperience");
            file_put_contents($logFile, "No vehicle experience data to insert\n", FILE_APPEND);
            return true;
            }

            // Προσθήκη της νέας εμπειρίας
            $table = 'driver_vehicle_experience';
            $sql = "INSERT INTO $table (
            driver_id, transport_type, vehicle_type,
            years, months, days, description
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";

            file_put_contents($logFile, "SQL query: $sql\n", FILE_APPEND);
            Logger::info("SQL query: $sql", "VehicleExperience");

            $stmt = $this->pdo->prepare($sql);
            $insertCount = 0;
            $errorCount = 0;

            foreach ($vehicleExperience as $exp) {
            // Παραλείπουμε εγγραφές χωρίς επιλεγμένο τύπο οχήματος
            if (empty($exp['vehicle_type'])) {
            Logger::warning("Skipping entry with empty vehicle_type", "VehicleExperience");
            file_put_contents($logFile, "Skipping entry with empty vehicle_type\n", FILE_APPEND);
            continue;
            }

            // Καταγραφή των δεδομένων κάθε εγγραφής
            Logger::info("Inserting vehicle experience: " . print_r($exp, true), "VehicleExperience");
            file_put_contents($logFile, "Inserting vehicle experience: " . print_r($exp, true) . "\n", FILE_APPEND);

            // Προετοιμασία των παραμέτρων
            $params = [
            $driverId,
            $exp['transport_type'] ?? 'freight',
            $exp['vehicle_type'],
            intval($exp['years'] ?? 0),
            intval($exp['months'] ?? 0),
            intval($exp['days'] ?? 0),
            $exp['description'] ?? ''
            ];

            file_put_contents($logFile, "Parameters: " . print_r($params, true) . "\n", FILE_APPEND);
            Logger::info("Parameters: " . print_r($params, true), "VehicleExperience");

            $result = $stmt->execute($params);

            if ($result) {
            $insertCount++;
            file_put_contents($logFile, "Insert successful\n", FILE_APPEND);
            Logger::info("Insert successful", "VehicleExperience");
            } else {
            $errorCount++;
            $errorInfo = $stmt->errorInfo();
            file_put_contents($logFile, "Failed to insert vehicle experience: " . print_r($exp, true) . " Error: " . print_r($errorInfo, true) . "\n", FILE_APPEND);
            Logger::error('Failed to insert vehicle experience: ' . print_r($exp, true) . ' Error: ' . print_r($errorInfo, true), "VehicleExperience");
            }
            }

            file_put_contents($logFile, "Insert summary: $insertCount successful, $errorCount failed\n", FILE_APPEND);
            Logger::info("Insert summary: $insertCount successful, $errorCount failed", "VehicleExperience");

            // Έλεγχος των εγγραφών μετά την εισαγωγή
            $sql = "SELECT COUNT(*) FROM $table WHERE driver_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$driverId]);
            $count = $stmt->fetchColumn();

            file_put_contents($logFile, "Records after insert: $count\n", FILE_APPEND);
            Logger::info("Records after insert: $count", "VehicleExperience");

            return true;
            } catch (PDOException $e) {
            file_put_contents($logFile, "Error in updateDriverVehicleExperience: " . $e->getMessage() . "\n", FILE_APPEND);
            file_put_contents($logFile, "Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
            Logger::error('Error in updateDriverVehicleExperience: ' . $e->getMessage());
            return false;
            }
            }
        </div>

        <h3>Εφαρμογή της Διόρθωσης</h3>
        <p>Για να εφαρμόσετε τη διόρθωση, ακολουθήστε τα παρακάτω βήματα:</p>
        <ol>
            <li>Ανοίξτε το αρχείο <code>src/Models/Driver/SkillModel.php</code></li>
            <li>Εντοπίστε τη μέθοδο <code>updateDriverVehicleExperience</code></li>
            <li>Αντικαταστήστε τον κώδικα της μεθόδου με τον παραπάνω κώδικα</li>
            <li>Αποθηκεύστε το αρχείο</li>
        </ol>

        <p>Εναλλακτικά, μπορείτε να δημιουργήσετε ένα αρχείο διόρθωσης και να το εκτελέσετε:</p>

        <div class="debug-form">
            <form action="<?php echo BASE_URL; ?>drivers/apply_vehicle_experience_fix.php" method="POST">
                <?php echo \Drivejob\Core\CSRF::tokenField(); ?>
                <button type="submit">Εφαρμογή Διόρθωσης</button>
            </form>
        </div>
    </div>

    <div class="debug-section">
        <h2>Δοκιμαστική Προσθήκη Προϋπηρεσίας</h2>

        <div class="debug-form">
            <form action="<?php echo BASE_URL; ?>drivers/update_vehicle_experience.php?debug=1" method="POST">
                <?php echo \Drivejob\Core\CSRF::tokenField(); ?>

                <div style="margin-bottom: 20px;">
                    <label for="transport_type">Τύπος Μεταφοράς:</label>
                    <select name="vehicle_experience[0][transport_type]" id="transport_type" required>
                        <option value="freight">Εμπορευματικές Μεταφορές</option>
                        <option value="passenger">Επιβατικές Μεταφορές</option>
                    </select>
                </div>

                <div style="margin-bottom: 20px;">
                    <label for="vehicle_type">Τύπος Οχήματος:</label>
                    <select name="vehicle_experience[0][vehicle_type]" id="vehicle_type" required>
                        <option value="truck">Φορτηγό</option>
                        <option value="van">Βαν</option>
                        <option value="bus">Λεωφορείο</option>
                        <option value="minibus">Μικρό Λεωφορείο</option>
                        <option value="taxi">Ταξί</option>
                        <option value="other">Άλλο</option>
                    </select>
                </div>

                <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                    <div style="flex: 1;">
                        <label for="years">Έτη:</label>
                        <input type="number" name="vehicle_experience[0][years]" id="years" min="0" max="50" value="1" required>
                    </div>

                    <div style="flex: 1;">
                        <label for="months">Μήνες:</label>
                        <input type="number" name="vehicle_experience[0][months]" id="months" min="0" max="11" value="6" required>
                    </div>

                    <div style="flex: 1;">
                        <label for="days">Ημέρες:</label>
                        <input type="number" name="vehicle_experience[0][days]" id="days" min="0" max="30" value="15" required>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label for="description">Περιγραφή:</label>
                    <textarea name="vehicle_experience[0][description]" id="description" rows="3">Δοκιμαστική προσθήκη προϋπηρεσίας για διαγνωστικούς σκοπούς.</textarea>
                </div>

                <button type="submit">Δοκιμαστική Προσθήκη</button>
            </form>
        </div>
    </div>

    <div class="debug-section">
        <h2>Σύνδεσμοι</h2>
        <ul>
            <li><a href="<?php echo BASE_URL; ?>drivers/debug_vehicle_experience.php">Διαγνωστικά Προϋπηρεσίας Οχημάτων</a></li>
            <li><a href="<?php echo BASE_URL; ?>drivers/vehicle_experience">Σελίδα Διαχείρισης Προϋπηρεσίας</a></li>
            <li><a href="<?php echo BASE_URL; ?>drivers/edit_profile.php">Επιστροφή στην Επεξεργασία Προφίλ</a></li>
        </ul>
    </div>
</main>

<?php
// Συμπερίληψη του footer
include ROOT_DIR . '/src/Views/footer.php';
?>
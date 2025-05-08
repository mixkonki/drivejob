<?php
// Εργαλείο ελέγχου αλληλεπίδρασης για το drivejob - Διορθωμένη έκδοση
// Αποθηκεύστε το ως debug_flow.php στον φάκελο public

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Φορτώνουμε το config
require_once '../config/config.php';
require_once '../config/database.php';

class FlowDebugger
{
    private $db;
    private $trace = [];

    public function __construct()
    {
        global $pdo; // Χρησιμοποιούμε το global $pdo από το database.php
        $this->db = $pdo;
    }

    /**
     * Ξεκινά την ανίχνευση για συγκεκριμένο driver_id
     */
    public function traceDriverProfile($driverId = null)
    {
        if (!$driverId) {
            $driverId = $this->getFirstDriverId();
        }

        echo "<h1>🔍 Ανίχνευση Ροής Δεδομένων για Driver ID: $driverId</h1>";

        // 1. Ελέγχουμε την βάση δεδομένων
        $this->traceDatabaseStructure($driverId);

        // 2. Προσομοιώνουμε την κλήση Controller
        $this->traceControllerFlow($driverId);

        // 3. Ελέγχουμε τα Models
        $this->traceModelCalls($driverId);

        // 4. Ελέγχουμε τα Views
        $this->traceViewRequirements();

        // 5. Εμφανίζουμε την ανάλυση
        $this->showTrace();
    }

    /**
     * Βρίσκει το πρώτο driver_id για test
     */
    private function getFirstDriverId()
    {
        $stmt = $this->db->query("SELECT id FROM drivers LIMIT 1");
        $driver = $stmt->fetch(PDO::FETCH_ASSOC);
        return $driver['id'] ?? 1;
    }

    /**
     * Ελέγχει τη δομή της βάσης δεδομένων
     */
    private function traceDatabaseStructure($driverId)
    {
        echo "<h2>📊 Database Structure Check</h2>";

        $tables = [
            'drivers' => ['tachograph_card', 'available_for_work'],
            'driver_tachograph_cards' => '*',
            'driver_operator_licenses' => '*',
            'driver_special_licenses' => '*',
            'driver_certifications' => '*'
        ];

        foreach ($tables as $table => $columns) {
            $this->trace[] = "Checking table: $table";

            try {
                // Ελέγχουμε αν υπάρχει ο πίνακας
                $stmt = $this->db->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() > 0) {
                    // Βρίσκουμε τις στήλες
                    $cols = [];
                    $colStmt = $this->db->query("SHOW COLUMNS FROM $table");
                    while ($row = $colStmt->fetch(PDO::FETCH_ASSOC)) {
                        $cols[] = $row['Field'];
                    }

                    $this->trace[] = "✓ Table $table exists with columns: " . implode(', ', $cols);

                    // Ελέγχουμε για δεδομένα
                    if (in_array('driver_id', $cols)) {
                        $stmt = $this->db->prepare("SELECT * FROM $table WHERE driver_id = ? LIMIT 1");
                        $stmt->execute([$driverId]);
                    } else {
                        $stmt = $this->db->prepare("SELECT * FROM $table WHERE id = ? LIMIT 1");
                        $stmt->execute([$driverId]);
                    }

                    if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $this->trace[] = "✓ Data found for ID $driverId: " . json_encode($data);
                    } else {
                        $this->trace[] = "⚠️ No data found for ID $driverId";
                    }
                } else {
                    $this->trace[] = "❌ Table $table does not exist";
                }
            } catch (\Exception $e) {
                $this->trace[] = "❌ Error checking $table: " . $e->getMessage();
            }
        }
    }

    /**
     * Προσομοιώνει την ροή του Controller
     */
    private function traceControllerFlow($driverId)
    {
        echo "<h2>🎮 Controller Flow Check</h2>";

        $this->trace[] = "Simulating Controller call...";

        // Ελέγχουμε πώς ο Controller θα έπρεπε να φορτώνει δεδομένα
        $expectedData = [
            'DriverController::show() or profile()' => [
                '$driverData = $driverModel->getDriverProfile($driverId)',
                '$driverLicenses = $licenseModel->getDriverLicenses($driverId)',
                '$driverTachograph = $certificationModel->getDriverTachographCard($driverId)',
                '$driverOperator = $certificationModel->getDriverOperatorLicense($driverId)',
                '$driverSpecialLicenses = $certificationModel->getDriverSpecialLicenses($driverId)'
            ]
        ];

        foreach ($expectedData as $method => $queries) {
            $this->trace[] = "$method should execute:";
            foreach ($queries as $query) {
                $this->trace[] = "  - $query";
            }
        }
    }

    /**
     * Ελέγχει τις κλήσεις Model
     */
    private function traceModelCalls($driverId)
    {
        echo "<h2>🔍 Model Method Calls</h2>";

        $modelMethods = [
            'DriverProfileModel' => [
                'getDriverProfile($id)',
                'updateProfile($id, $data)'
            ],
            'CertificationModel' => [
                'getDriverTachographCard($driverId)',
                'getDriverOperatorLicense($driverId)',
                'getDriverSpecialLicenses($driverId)',
                'updateDriverTachographCard($driverId, $data)',
                'updateDriverOperatorLicense($driverId, $data)',
                'addDriverSpecialLicense($driverId, $data)'
            ],
            'LicenseModel' => [
                'getDriverLicenses($driverId)',
                'getDriverPEI($driverId)',
                'addDriverLicense($driverId, $data)'
            ]
        ];

        foreach ($modelMethods as $modelName => $methods) {
            $this->trace[] = "$modelName should have methods:";
            foreach ($methods as $method) {
                $this->trace[] = "  - $method";
            }
        }
    }

    /**
     * Ελέγχει τις απαιτήσεις των Views
     */
    private function traceViewRequirements()
    {
        echo "<h2>👁️ View Data Requirements</h2>";

        $viewRequirements = [
            'profile.php' => [
                '$driverData["available_for_work"]' => 'Boolean flag for availability',
                '$driverData["tachograph_card"]' => 'Boolean flag if has tachograph',
                '$driverData["tachograph_card_number"]' => 'Card number string',
                '$driverData["tachograph_card_expiry"]' => 'Expiry date',
                '$driverOperator' => 'Array with operator license data',
                '$driverSpecialLicenses' => 'Array of special licenses'
            ],
            'edit_profile.php' => [
                'Form fields: tachograph_card (checkbox)',
                'Form fields: operator_license (checkbox)',
                'Form fields: available_for_work (hidden)',
                'Proper field names for tachograph_card_number',
                'Proper field names for operator_license_number'
            ]
        ];

        foreach ($viewRequirements as $view => $requirements) {
            $this->trace[] = "$view requires:";
            foreach ($requirements as $requirement => $description) {
                if (is_array($description)) {
                    $this->trace[] = "  - $requirement";
                } else {
                    $this->trace[] = "  - $requirement: $description";
                }
            }
        }
    }

    /**
     * Εμφανίζει την ανάλυση
     */
    private function showTrace()
    {
        echo "<h2>📋 Flow Analysis Report</h2>";
        echo "<div style='background: #f5f5f5; padding: 20px; border-radius: 5px;'>";
        echo "<pre>";
        foreach ($this->trace as $step) {
            echo $step . "\n";
        }
        echo "</pre>";
        echo "</div>";

        // Εμφανίζουμε πιθανά προβλήματα
        $this->detectPossibleIssues();
    }

    /**
     * Ανιχνεύει πιθανά προβλήματα
     */
    private function detectPossibleIssues()
    {
        echo "<h2>🔎 Detected Issues</h2>";
        echo "<ul>";

        foreach ($this->trace as $step) {
            if (strpos($step, '❌') !== false) {
                echo "<li style='color: red;'>" . strip_tags($step) . "</li>";
            } elseif (strpos($step, '⚠️') !== false) {
                echo "<li style='color: orange;'>" . strip_tags($step) . "</li>";
            }
        }

        echo "</ul>";

        echo "<h2>💡 Suggestions</h2>";
        echo "<ul>";
        echo "<li>Check if the database tables exist and have the required columns</li>";
        echo "<li>Verify the Controller is properly loading data and passing it to views</li>";
        echo "<li>Ensure Model methods match what the Controller expects</li>";
        echo "<li>Confirm View variables match what the Controller provides</li>";
        echo "</ul>";
    }
}

// Εκτέλεση του test
if (isset($_GET['run'])) {
    try {
        $debugger = new FlowDebugger();
        $driverId = $_GET['driver_id'] ?? null;
        $debugger->traceDriverProfile($driverId);
    } catch (Exception $e) {
        echo "<div style='color: red; padding: 20px; background: #ffeeee;'>";
        echo "<h2>❌ Error</h2>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
} else {
?>
    <form method="get">
        <h1>🔍 Flow Debugger</h1>
        <p>Driver ID (προαιρετικό): <input type="number" name="driver_id" value="" /></p>
        <button type="submit" name="run" value="1">Εκτέλεση Ελέγχου</button>
    </form>
<?php
}

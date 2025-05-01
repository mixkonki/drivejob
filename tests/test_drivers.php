<?php
// test_drivers_fixed.php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_DIR . '/vendor/autoload.php';

use Drivejob\Models\Driver\ProfileModel;
use Drivejob\Models\Driver\LicenseModel;
use Drivejob\Models\Driver\CertificationModel;
use Drivejob\Models\Driver\SkillModel;
use Drivejob\Models\Driver\RatingModel;
use Drivejob\Models\Driver\IncidentModel;
use Drivejob\Services\DriverProfileService;

class DriverTest
{
    private $pdo;

    public function __construct()
    {
        try {
            // Αρχικοποίηση του $pdo από το config/database.php
            require ROOT_DIR . '/config/database.php';

            // Ελέγχουμε αν η μεταβλητή $pdo δημιουργήθηκε
            if (isset($pdo) && $pdo instanceof PDO) {
                $this->pdo = $pdo;
            } else {
                // Δημιουργούμε το δικό μας PDO αν χρειαστεί
                $this->pdo = new PDO("mysql:host=localhost;dbname=drivejob;charset=utf8mb4", 'root', '', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            }

            echo "✓ Database connection successful\n\n";
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage() . "\n");
        }
    }

    public function runTests()
    {
        echo "=== Starting Driver Tests ===\n\n";

        // Test 1: ProfileModel
        echo "Testing ProfileModel...\n";
        $profileModel = new ProfileModel($this->pdo);

        try {
            $testDriver = $profileModel->getDriverById(26);
            echo $testDriver ? "✓ getDriverById working\n" : "✗ getDriverById failed\n";

            if ($testDriver) {
                echo "  - Driver: " . $testDriver['first_name'] . " " . $testDriver['last_name'] . "\n";
            }
        } catch (Exception $e) {
            echo "✗ getDriverById error: " . $e->getMessage() . "\n";
        }

        try {
            $count = $profileModel->countDrivers();
            echo "✓ countDrivers working - $count total drivers\n";
        } catch (Exception $e) {
            echo "✗ countDrivers error: " . $e->getMessage() . "\n";
        }

        // Test 2: LicenseModel
        echo "\nTesting LicenseModel...\n";
        $licenseModel = new LicenseModel($this->pdo);

        try {
            $testLicenses = $licenseModel->getDriverLicenses(26);

            if (is_array($testLicenses)) {
                echo "✓ getDriverLicenses working - " . count($testLicenses) . " licenses found\n";

                foreach ($testLicenses as $license) {
                    echo "  - License: " . $license['license_type'] . "\n";
                }
            } else {
                echo "✗ getDriverLicenses returned invalid data\n";
            }
        } catch (Exception $e) {
            echo "✗ getDriverLicenses error: " . $e->getMessage() . "\n";
        }

        // Test 3: CertificationModel
        echo "\nTesting CertificationModel...\n";
        $certificationModel = new CertificationModel($this->pdo);

        try {
            $testCertifications = $certificationModel->getDriverCertifications(26);
            echo is_array($testCertifications) ? "✓ getDriverCertifications working\n" : "✗ getDriverCertifications failed\n";

            if (is_array($testCertifications)) {
                echo "  - Found " . count($testCertifications) . " certifications\n";
            }
        } catch (Exception $e) {
            echo "✗ getDriverCertifications error: " . $e->getMessage() . "\n";
        }

        // Test 4: SkillModel
        echo "\nTesting SkillModel...\n";
        $skillModel = new SkillModel($this->pdo);

        try {
            $testSkills = $skillModel->getDriverSkills(26);
            echo is_array($testSkills) ? "✓ getDriverSkills working\n" : "✗ getDriverSkills failed\n";
        } catch (Exception $e) {
            echo "✗ getDriverSkills error: " . $e->getMessage() . "\n";
        }

        // Test 5: RatingModel
        echo "\nTesting RatingModel...\n";
        $ratingModel = new RatingModel($this->pdo);

        try {
            $testRating = $ratingModel->getDriverRating(26);
            echo is_numeric($testRating) ? "✓ getDriverRating working - Rating: $testRating\n" : "✗ getDriverRating failed\n";
        } catch (Exception $e) {
            echo "✗ getDriverRating error: " . $e->getMessage() . "\n";
        }

        // Test 6: IncidentModel
        echo "\nTesting IncidentModel...\n";
        $incidentModel = new IncidentModel($this->pdo);

        try {
            $testIncidents = $incidentModel->getDriverIncidents(26);

            if (is_array($testIncidents)) {
                echo "✓ getDriverIncidents working - " . count($testIncidents) . " incidents found\n";
            } else {
                echo "✗ getDriverIncidents failed\n";
            }
        } catch (Exception $e) {
            echo "✗ getDriverIncidents error: " . $e->getMessage() . "\n";
        }

        // Test Services
        echo "\n=== Testing Services ===\n\n";

        // Test DriverProfileService
        echo "Testing DriverProfileService...\n";
        try {
            $profileService = new DriverProfileService($this->pdo);
            $driverProfile = $profileService->getDriverProfile(26);

            if ($driverProfile) {
                echo "✓ DriverProfileService working\n";
                echo "  - Driver: " . $driverProfile['first_name'] . " " . $driverProfile['last_name'] . "\n";
                echo "  - Licenses: " . count($driverProfile['licenses']) . "\n";
                echo "  - ADR Certifications: " . count($driverProfile['adr_certificates']) . "\n";
            } else {
                echo "✗ DriverProfileService failed\n";
            }
        } catch (Exception $e) {
            echo "✗ DriverProfileService error: " . $e->getMessage() . "\n";
        }

        echo "\n=== Tests Completed ===\n";
    }

    public function listUnusedFiles()
    {
        echo "\n=== Checking for unused files ===\n\n";

        // Λίστα παλιών αρχείων που πιθανώς δεν χρειάζονται πλέον
        $potentiallyUnusedFiles = [
            
                        ROOT_DIR . '/src/Models/DriverLicenseModel.php',
            ROOT_DIR . '/src/Controllers/DriverController.php',
            ROOT_DIR . '/public/driver.php',
            ROOT_DIR . '/public/driver_profile.php',
            ROOT_DIR . '/public/update_driver.php',
            ROOT_DIR . '/src/Views/drivers/driver.php',
            ROOT_DIR . '/src/Views/drivers/driver_profile_old.php',
            ROOT_DIR . '/src/Views/drivers/edit_profile_old.php'
        ];

        echo "Files that might be safe to remove:\n";
        $foundFiles = 0;

        foreach ($potentiallyUnusedFiles as $file) {
            if (file_exists($file)) {
                echo "- " . str_replace(ROOT_DIR, '', $file) . "\n";
                $foundFiles++;
            }
        }

        if ($foundFiles == 0) {
            echo "No unused files found!\n";
        }

        echo "\n== Recommendations ==\n";
        echo "1. Verify these files aren't used anywhere before deleting\n";
        echo "2. Use 'grep -r \"filename\" .' to search for references\n";
        echo "3. Consider using git to track these files before deletion\n";
    }
}

try {
    $tester = new DriverTest();
    $tester->runTests();
    $tester->listUnusedFiles();
} catch (Exception $e) {
    echo "Test failed: " . $e->getMessage() . "\n";
}

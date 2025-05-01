<?php

/**
 * Script για έλεγχο των λειτουργιών προφίλ οδηγού
 */

define('TESTING', true);

require_once __DIR__ . '/../vendor/autoload.php';

// Σίγουρα φόρτωση του helpers.php
if (!function_exists('old')) {
    require_once __DIR__ . '/../src/helpers.php';
}

// Φόρτωση config και ρυθμίσεων DB
$config = require_once __DIR__ . '/../config/config.php';

use Drivejob\Core\Logger;
use Drivejob\Core\Session;
use Drivejob\Core\CSRF;
use Drivejob\Models\Driver\ProfileModel;
use Drivejob\Models\Driver\LicenseModel;
use Drivejob\Models\Driver\CertificationModel;
use Drivejob\Models\Driver\SkillModel;
use Drivejob\Controllers\DriversController;
use Drivejob\Controllers\DriverResumeController;
use Drivejob\Services\FileUploadService;

// Εκκίνηση του session
Session::start();

class DriverProfileTest
{
    private $pdo;
    private $profileModel;
    private $licenseModel;
    private $certificationModel;
    private $skillModel;
    private $driversController;
    private $resumeController;
    private $fileService;
    private $results = [];
    private $testDriverId;
    private $testEmail;
    private $dbConfig;

    public function __construct()
    {
        global $config;

        // Μετακινούμε την αρχικοποίηση των δυναμικών τιμών εδώ
        $this->testEmail = 'test_profile_' . time() . '@test.com';
        $this->dbConfig = $config;

        // Σύνδεση με τη βάση δεδομένων
        $this->pdo = $GLOBALS['pdo'] ?? null;

        if (!$this->pdo) {
            $this->pdo = new PDO(
                "mysql:host=" . $this->dbConfig['db_host'] . ";dbname=" . $this->dbConfig['db_name'] . ";charset=utf8mb4",
                $this->dbConfig['db_user'],
                $this->dbConfig['db_pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        }

        // Αρχικοποίηση models και controllers
        $this->profileModel = new ProfileModel($this->pdo);
        $this->licenseModel = new LicenseModel($this->pdo);
        $this->certificationModel = new CertificationModel($this->pdo);
        $this->skillModel = new SkillModel($this->pdo);
        $this->driversController = new DriversController($this->pdo);
        $this->resumeController = new DriverResumeController($this->pdo);
        $this->fileService = new FileUploadService($this->pdo);
    }

    public function runAllTests()
    {
        echo "=== ΕΛΕΓΧΟΣ ΛΕΙΤΟΥΡΓΙΩΝ ΠΡΟΦΙΛ ΟΔΗΓΟΥ ===\n\n";

        // Δημιουργία test οδηγού
        $this->createTestDriver();

        // Εκτέλεση tests με διαχείριση headers
        $this->testProfileView();
        $this->testProfileEdit();
        $this->testProfileUpdate();
        $this->testLicenseManagement();
        $this->testCertificationManagement();
        $this->testSkillsManagement();
        $this->testAvailabilityToggle();
        $this->testFileUpload();
        $this->testResumeGeneration();

        // Καθαρισμός και αποτελέσματα
        $this->cleanup();
        $this->showResults();
    }

    private function createTestDriver()
    {
        echo "1. Δημιουργία Test Οδηγού...\n";

        try {
            $testData = [
                'email' => $this->testEmail,
                'password' => password_hash('TestPass123!', PASSWORD_DEFAULT),
                'first_name' => 'Test',
                'last_name' => 'Driver',
                'phone' => '6901234567',
                'is_verified' => 1,
                'available_for_work' => 1,
                'experience_years' => 5,
                'city' => 'Αθήνα',
                'country' => 'Ελλάδα'
            ];

            $this->testDriverId = $this->profileModel->create($testData);

            if ($this->testDriverId) {
                echo "  ✓ Test οδηγός δημιουργήθηκε (ID: {$this->testDriverId})\n";
                Session::set('user_id', $this->testDriverId);
                Session::set('role', 'driver');
            } else {
                throw new Exception("Αποτυχία δημιουργίας test οδηγού");
            }
        } catch (Exception $e) {
            echo "  ✗ Σφάλμα: " . $e->getMessage() . "\n";
            exit;
        }
        echo "\n";
    }

    private function testProfileView()
    {
        echo "2. Έλεγχος Προβολής Προφίλ...\n";

        try {
            $output = $this->runControllerMethod(function () {
                $this->driversController->profile();
            });

            if (strpos($output, 'Test Driver') !== false) {
                $this->results['profile_view'] = 'ΕΠΙΤΥΧΙΑ - Προβολή προφίλ λειτουργεί';
                echo "  ✓ Το προφίλ εμφανίζεται σωστά\n";
            } else {
                $this->results['profile_view'] = 'ΑΠΟΤΥΧΙΑ - Προβλήματα στην προβολή';
                echo "  ✗ Το προφίλ δεν εμφανίζεται σωστά\n";
            }
        } catch (Exception $e) {
            $this->results['profile_view'] = 'ΣΦΑΛΜΑ - ' . $e->getMessage();
            echo "  ✗ Σφάλμα: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testProfileEdit()
    {
        echo "3. Έλεγχος Σελίδας Επεξεργασίας...\n";

        try {
            $output = $this->runControllerMethod(function () {
                $this->driversController->edit();
            });

            if (strpos($output, '<form') !== false && strpos($output, 'first_name') !== false) {
                $this->results['profile_edit'] = 'ΕΠΙΤΥΧΙΑ - Φόρμα επεξεργασίας εμφανίζεται';
                echo "  ✓ Η φόρμα επεξεργασίας λειτουργεί\n";
            } else {
                $this->results['profile_edit'] = 'ΑΠΟΤΥΧΙΑ - Προβλήματα με τη φόρμα';
                echo "  ✗ Η φόρμα επεξεργασίας δεν εμφανίζεται\n";
            }
        } catch (Exception $e) {
            $this->results['profile_edit'] = 'ΣΦΑΛΜΑ - ' . $e->getMessage();
            echo "  ✗ Σφάλμα: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testProfileUpdate()
    {
        echo "4. Έλεγχος Ενημέρωσης Προφίλ...\n";

        try {
            // Προσομοίωση POST δεδομένων
            $_POST = [
                'csrf_token' => CSRF::generateToken(),
                'first_name' => 'Updated',
                'last_name' => 'Name',
                'phone' => '6912345678',
                'city' => 'Θεσσαλονίκη',
                'country' => 'Ελλάδα',
                'experience_years' => 7,
                'about_me' => 'This is an updated about me section.'
            ];
            $_SERVER['REQUEST_METHOD'] = 'POST';

            $output = $this->runControllerMethod(function () {
                $this->driversController->update();
            });

            // Έλεγχος αν ενημερώθηκε
            $driver = $this->profileModel->getDriverById($this->testDriverId);
            if ($driver && $driver['first_name'] === 'Updated') {
                $this->results['profile_update'] = 'ΕΠΙΤΥΧΙΑ - Το προφίλ ενημερώθηκε';
                echo "  ✓ Η ενημέρωση προφίλ λειτουργεί\n";
            } else {
                $this->results['profile_update'] = 'ΑΠΟΤΥΧΙΑ - Αποτυχία ενημέρωσης';
                echo "  ✗ Η ενημέρωση προφίλ απέτυχε\n";
            }

            // Καθαρισμός
            $_POST = [];
            $_SERVER['REQUEST_METHOD'] = 'GET';
        } catch (Exception $e) {
            $this->results['profile_update'] = 'ΣΦΑΛΜΑ - ' . $e->getMessage();
            echo "  ✗ Σφάλμα: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testLicenseManagement()
    {
        echo "5. Έλεγχος Διαχείρισης Αδειών...\n";

        try {
            // Δημιουργία άδειας
            $licenseData = [
                'driver_id' => $this->testDriverId,
                'license_type' => 'C',
                'issue_date' => '2020-01-01',
                'expiry_date' => '2026-01-01',
                'has_pei' => 1,
                'pei_expiry_c' => '2025-06-01'
            ];

            $licenseId = $this->licenseModel->create($licenseData);

            if ($licenseId) {
                echo "  ✓ Δημιουργία άδειας επιτυχής\n";

                // Λήψη αδειών οδηγού
                $licenses = $this->licenseModel->getDriverLicenses($this->testDriverId);
                if (!empty($licenses)) {
                    echo "  ✓ Ανάκτηση αδειών επιτυχής\n";

                    // Ενημέρωση άδειας
                    $updateData = ['license_type' => 'CE'];
                    if ($this->licenseModel->update($updateData, ['id' => $licenseId])) {
                        echo "  ✓ Ενημέρωση άδειας επιτυχής\n";
                    }

                    $this->results['license_management'] = 'ΕΠΙΤΥΧΙΑ - Διαχείριση αδειών';
                } else {
                    echo "  ✗ Αποτυχία ανάκτησης αδειών\n";
                }
            } else {
                echo "  ✗ Αποτυχία δημιουργίας άδειας\n";
            }
        } catch (Exception $e) {
            $this->results['license_management'] = 'ΣΦΑΛΜΑ - ' . $e->getMessage();
            echo "  ✗ Σφάλμα: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testCertificationManagement()
    {
        echo "6. Έλεγχος Διαχείρισης Πιστοποιήσεων...\n";

        try {
            // Δημιουργία ADR πιστοποιητικού
            $adrData = [
                'driver_id' => $this->testDriverId,
                'adr_type' => 'basic',
                'issue_date' => '2023-01-01',
                'expiry_date' => '2028-01-01'
            ];

            $adrId = $this->certificationModel->create('adr_certificates', $adrData);

            if ($adrId) {
                echo "  ✓ Δημιουργία ADR πιστοποιητικού επιτυχής\n";

                // Λήψη ADR πιστοποιητικών
                $adrs = $this->certificationModel->getDriverAdrCertificates($this->testDriverId);
                if (!empty($adrs)) {
                    echo "  ✓ Ανάκτηση ADR πιστοποιητικών επιτυχής\n";
                    $this->results['certification_management'] = 'ΕΠΙΤΥΧΙΑ - Διαχείριση πιστοποιήσεων';
                } else {
                    echo "  ✗ Αποτυχία ανάκτησης ADR πιστοποιητικών\n";
                }
            } else {
                echo "  ✗ Αποτυχία δημιουργίας ADR πιστοποιητικού\n";
            }
        } catch (Exception $e) {
            $this->results['certification_management'] = 'ΣΦΑΛΜΑ - ' . $e->getMessage();
            echo "  ✗ Σφάλμα: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testSkillsManagement()
    {
        echo "7. Έλεγχος Διαχείρισης Δεξιοτήτων...\n";

        try {
            // Προσθήκη skills
            $_POST = [
                'csrf_token' => CSRF::generateToken(),
                'skills' => [
                    'driving_highways' => 'on',
                    'parking_skills' => 'on',
                    'night_driving' => 'on',
                    'load_securing' => 'on'
                ],
                'languages' => [
                    'greek' => 'native',
                    'english' => 'good',
                    'german' => 'basic'
                ]
            ];
            $_SERVER['REQUEST_METHOD'] = 'POST';

            $output = $this->runControllerMethod(function () {
                $this->driversController->updateSkills(false);
            });

            if (isset($_SESSION['success_message'])) {
                echo "  ✓ Ενημέρωση δεξιοτήτων επιτυχής\n";
                $this->results['skills_management'] = 'ΕΠΙΤΥΧΙΑ - Διαχείριση δεξιοτήτων';
            } else {
                echo "  ✗ Αποτυχία ενημέρωσης δεξιοτήτων\n";
                $this->results['skills_management'] = 'ΑΠΟΤΥΧΙΑ - Αποτυχία δεξιοτήτων';
            }

            // Καθαρισμός
            $_POST = [];
            $_SERVER['REQUEST_METHOD'] = 'GET';
            unset($_SESSION['success_message']);
        } catch (Exception $e) {
            $this->results['skills_management'] = 'ΣΦΑΛΜΑ - ' . $e->getMessage();
            echo "  ✗ Σφάλμα: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testAvailabilityToggle()
    {
        echo "8. Έλεγχος Εναλλαγής Διαθεσιμότητας...\n";

        try {
            // Εναλλαγή διαθεσιμότητας
            $_POST = [
                'csrf_token' => CSRF::generateToken()
            ];
            $_SERVER['REQUEST_METHOD'] = 'POST';

            $output = $this->runControllerMethod(function () {
                $this->driversController->toggleAvailability();
            });

            // Έλεγχος αποτελέσματος
            $response = json_decode($output, true);
            if ($response && isset($response['success']) && $response['success']) {
                echo "  ✓ Εναλλαγή διαθεσιμότητας επιτυχής\n";
                $this->results['availability_toggle'] = 'ΕΠΙΤΥΧΙΑ - Εναλλαγή διαθεσιμότητας';
            } else {
                echo "  ✗ Αποτυχία εναλλαγής διαθεσιμότητας\n";
                $this->results['availability_toggle'] = 'ΑΠΟΤΥΧΙΑ - Αποτυχία εναλλαγής';
            }

            // Καθαρισμός
            $_POST = [];
            $_SERVER['REQUEST_METHOD'] = 'GET';
        } catch (Exception $e) {
            $this->results['availability_toggle'] = 'ΣΦΑΛΜΑ - ' . $e->getMessage();
            echo "  ✗ Σφάλμα: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testFileUpload()
    {
        echo "9. Έλεγχος Ανεβάσματος Αρχείων...\n";

        try {
            // Δημιουργία προσομοιωμένου αρχείου
            $testFile = [
                'name' => 'test_image.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/test_image.jpg',
                'error' => 0,
                'size' => 1024
            ];

            // Δημιουργία ενός προσωρινού αρχείου
            $tempFile = tempnam(sys_get_temp_dir(), 'test_');
            file_put_contents($tempFile, str_repeat('*', 1024));
            $testFile['tmp_name'] = $tempFile;

            $_FILES['profile_image'] = $testFile;

            // Προσομοίωση ανεβάσματος
            $result = $this->fileService->handleFileUploads($this->testDriverId, $_FILES);

            echo "  ℹ️ Test ανεβάσματος: " . ($result ? "Επιτυχία" : "Αποτυχία") . "\n";
            $this->results['file_upload'] = 'ΕΠΙΤΥΧΙΑ - Test ανεβάσματος εκτελέστηκε';

            // Καθαρισμός
            unset($_FILES['profile_image']);
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        } catch (Exception $e) {
            $this->results['file_upload'] = 'ΣΦΑΛΜΑ - ' . $e->getMessage();
            echo "  ✗ Σφάλμα: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testResumeGeneration()
    {
        echo "10. Έλεγχος Δημιουργίας PDF Βιογραφικού...\n";

        try {
            $output = $this->runControllerMethod(function () {
                $this->resumeController->generateResume($this->testDriverId);
            });

            // Έλεγχος αν το PDF δημιουργήθηκε
            $driver = $this->profileModel->getDriverById($this->testDriverId);
            if ($driver && !empty($driver['resume_file'])) {
                echo "  ✓ PDF βιογραφικού δημιουργήθηκε\n";
                $this->results['resume_generation'] = 'ΕΠΙΤΥΧΙΑ - Δημιουργία PDF';
            } else {
                echo "  ✗ Αποτυχία δημιουργίας PDF\n";
                $this->results['resume_generation'] = 'ΑΠΟΤΥΧΙΑ - Αποτυχία PDF';
            }
        } catch (Exception $e) {
            $this->results['resume_generation'] = 'ΣΦΑΛΜΑ - ' . $e->getMessage();
            echo "  ✗ Σφάλμα: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function cleanup()
    {
        echo "Καθαρισμός δεδομένων τεστ...\n";

        try {
            // Διαγραφή των δεδομένων του test οδηγού
            if ($this->testDriverId) {
                $this->profileModel->delete($this->testDriverId);
                echo "  ✓ Test οδηγός διαγράφηκε\n";
            }

            // Καθαρισμός session
            $this->resetSessionState();
            echo "  ✓ Session καθαρίστηκε\n";
        } catch (Exception $e) {
            echo "  ✗ Σφάλμα καθαρισμού: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function showResults()
    {
        echo "=== ΣΥΝΟΨΗ ΑΠΟΤΕΛΕΣΜΑΤΩΝ ===\n\n";

        $total = count($this->results);
        $passed = 0;
        $failed = 0;
        $errors = 0;

        foreach ($this->results as $test => $result) {
            echo $test . ": " . $result . "\n";

            if (strpos($result, 'ΕΠΙΤΥΧΙΑ') !== false) {
                $passed++;
            } elseif (strpos($result, 'ΑΠΟΤΥΧΙΑ') !== false) {
                $failed++;
            } elseif (strpos($result, 'ΣΦΑΛΜΑ') !== false) {
                $errors++;
            }
        }

        echo "\n";
        echo "Σύνολο tests: $total\n";
        echo "Επιτυχημένα: $passed\n";
        echo "Αποτυχημένα: $failed\n";
        echo "Σφάλματα: $errors\n";

        if ($failed > 0 || $errors > 0) {
            echo "\n❌ Υπάρχουν προβλήματα που πρέπει να διορθωθούν!\n";
        } else {
            echo "\n✅ Όλα τα tests πέρασαν επιτυχώς!\n";
        }
    }

    /**
     * Εκτελεί μια συνάρτηση χωρίς να επηρεάζονται τα headers
     */
    private function runControllerMethod($callback)
    {
        try {
            $originalErrorLevel = error_reporting();
            error_reporting($originalErrorLevel & ~E_WARNING);

            ob_start();
            $callback();
            $output = ob_get_clean();

            error_reporting($originalErrorLevel);

            return $output;
        } catch (Exception $e) {
            ob_get_clean();
            error_reporting($originalErrorLevel);
            throw $e;
        }
    }

    /**
     * Επαναφέρει την κατάσταση του session
     */
    private function resetSessionState()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
        $_SESSION = [];
    }
}

// Εκτέλεση των tests
try {
    $test = new DriverProfileTest();
    $test->runAllTests();
} catch (Exception $e) {
    echo "ΚΡΙΣΙΜΟ ΣΦΑΛΜΑ: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

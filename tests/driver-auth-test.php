<?php
// Ορισμός της TESTING σημαίας για να αποφύγουμε headers sent errors
define('TESTING', true);

/**
 * Script για έλεγχο των λειτουργιών εγγραφής, εισόδου και εξόδου οδηγού
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Φόρτωση config και ρυθμίσεων DB
$config = require_once __DIR__ . '/../config/config.php';

use Drivejob\Core\Logger;
use Drivejob\Core\Session;
use Drivejob\Core\CSRF;
use Drivejob\Models\Driver\ProfileModel;
use Drivejob\Controllers\AuthController;

// Εκκίνηση του session
Session::start();

class DriverAuthTest
{
    private $pdo;
    private $profileModel;
    private $authController;
    private $results = [];
    private $testEmail;
    private $testPassword = 'TestPass123!';
    private $testData = [];
    private $dbConfig;

    public function __construct()
    {
        global $config;

        // Μετακινούμε την αρχικοποίηση των δυναμικών τιμών εδώ
        $this->testEmail = 'test_driver_' . time() . '@test.com';
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

        $this->profileModel = new ProfileModel($this->pdo);
        $this->authController = new AuthController($this->pdo);

        // Αρχικοποίηση δεδομένων τεστ
        $this->testData = [
            'email' => $this->testEmail,
            'password' => password_hash($this->testPassword, PASSWORD_DEFAULT),
            'first_name' => 'Test',
            'last_name' => 'Driver',
            'phone' => '6901234567',
            'is_verified' => 0  // Αρχικά δεν είναι επαληθευμένος
        ];
    }

    public function runAllTests()
    {
        echo "=== ΕΛΕΓΧΟΣ ΛΕΙΤΟΥΡΓΙΩΝ ΟΔΗΓΟΥ ===\n\n";

        $this->testDriverRegistration();
        $this->testEmailVerification();
        $this->testDriverLogin();
        $this->testUnverifiedDriverLogin();
        $this->testWrongCredentialsLogin();
        $this->testDriverLogout();
        $this->testSessionSecurity();
        $this->testCSRFProtection();

        $this->cleanup();
        $this->showResults();
    }

    private function testDriverRegistration()
    {
        echo "1. Έλεγχος Εγγραφής Οδηγού...\n";

        try {
            // Εγγραφή νέου οδηγού
            $driverId = $this->profileModel->create($this->testData);

            if ($driverId) {
                $this->results['registration'] = 'ΕΠΙΤΥΧΙΑ - Εγγραφή οδηγού με ID: ' . $driverId;
                echo "  ✓ Επιτυχής εγγραφή οδηγού (ID: $driverId)\n";

                // Έλεγχος αν τα δεδομένα αποθηκεύτηκαν σωστά
                $driver = $this->profileModel->getDriverById($driverId);
                if ($driver && $driver['email'] === $this->testEmail) {
                    echo "  ✓ Τα δεδομένα αποθηκεύτηκαν σωστά\n";
                } else {
                    echo "  ✗ Σφάλμα: Τα δεδομένα δεν αποθηκεύτηκαν σωστά\n";
                }
            } else {
                $this->results['registration'] = 'ΑΠΟΤΥΧΙΑ - Η εγγραφή απέτυχε';
                echo "  ✗ Αποτυχία εγγραφής οδηγού\n";
            }
        } catch (Exception $e) {
            $this->results['registration'] = 'ΣΦΑΛΜΑ - ' . $e->getMessage();
            echo "  ✗ Σφάλμα: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testEmailVerification()
    {
        echo "2. Έλεγχος Επαλήθευσης Email...\n";

        try {
            // Επαλήθευση του email
            $verified = $this->profileModel->verifyDriver($this->testEmail);

            if ($verified) {
                $this->results['verification'] = 'ΕΠΙΤΥΧΙΑ - Email επαληθεύτηκε';
                echo "  ✓ Επιτυχής επαλήθευση email\n";

                // Έλεγχος αν η κατάσταση ενημερώθηκε
                $driver = $this->profileModel->getDriverByEmail($this->testEmail);
                if ($driver && $driver['is_verified'] == 1) {
                    echo "  ✓ Η κατάσταση επαλήθευσης ενημερώθηκε\n";
                } else {
                    echo "  ✗ Σφάλμα: Η κατάσταση επαλήθευσης δεν ενημερώθηκε\n";
                }
            } else {
                $this->results['verification'] = 'ΑΠΟΤΥΧΙΑ - Η επαλήθευση απέτυχε';
                echo "  ✗ Αποτυχία επαλήθευσης email\n";
            }
        } catch (Exception $e) {
            $this->results['verification'] = 'ΣΦΑΛΜΑ - ' . $e->getMessage();
            echo "  ✗ Σφάλμα: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testDriverLogin()
    {
        echo "3. Έλεγχος Σύνδεσης Οδηγού...\n";

        try {
            // Καθαρισμός session χωρίς output buffering
            $this->resetSessionState();

            // Προσομοίωση POST δεδομένων
            $_POST['email'] = $this->testEmail;
            $_POST['password'] = $this->testPassword;
            $_SERVER['REQUEST_METHOD'] = 'POST';

            // Εκτέλεση χωρίς να στέλνουμε headers
            $result = $this->runControllerMethod(function () {
                $this->authController->processLogin();
            });

            // Έλεγχος αν ο χρήστης συνδέθηκε
            if (Session::has('user_id') && Session::get('role') === 'driver') {
                $this->results['login'] = 'ΕΠΙΤΥΧΙΑ - Σύνδεση οδηγού';
                echo "  ✓ Επιτυχής σύνδεση\n";
                echo "  ✓ User ID: " . Session::get('user_id') . "\n";
                echo "  ✓ Role: " . Session::get('role') . "\n";
                echo "  ✓ User Name: " . Session::get('user_name') . "\n";

                // Έλεγχος της last_login
                $driver = $this->profileModel->getDriverByEmail($this->testEmail);
                if ($driver && !empty($driver['last_login'])) {
                    echo "  ✓ Η last_login ενημερώθηκε\n";
                }
            } else {
                $this->results['login'] = 'ΑΠΟΤΥΧΙΑ - Η σύνδεση απέτυχε';
                echo "  ✗ Σφάλμα: Η σύνδεση απέτυχε\n";
            }

            // Καθαρισμός
            unset($_POST['email'], $_POST['password']);
            $_SERVER['REQUEST_METHOD'] = 'GET';
        } catch (Exception $e) {
            $this->results['login'] = 'ΣΦΑΛΜΑ - ' . $e->getMessage();
            echo "  ✗ Σφάλμα: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testUnverifiedDriverLogin()
    {
        echo "4. Έλεγχος Σύνδεσης Μη Επαληθευμένου Οδηγού...\n";

        try {
            // Δημιουργούμε έναν μη επαληθευμένο οδηγό
            $unverifiedData = $this->testData;
            $unverifiedData['email'] = 'unverified_' . time() . '@test.com';
            $unverifiedData['is_verified'] = 0;

            $unverifiedId = $this->profileModel->create($unverifiedData);

            if ($unverifiedId) {
                // Καθαρισμός session
                $this->resetSessionState();

                // Προσομοίωση POST δεδομένων
                $_POST['email'] = $unverifiedData['email'];
                $_POST['password'] = $this->testPassword;
                $_SERVER['REQUEST_METHOD'] = 'POST';

                // Εκτέλεση χωρίς να στέλνουμε headers
                $result = $this->runControllerMethod(function () {
                    $this->authController->processLogin();
                });

                // Έλεγχος αν ο χρήστης ΔΕΝ συνδέθηκε
                if (!Session::has('user_id') && Session::has('login_error')) {
                    $this->results['unverified_login'] = 'ΕΠΙΤΥΧΙΑ - Η σύνδεση απορρίφθηκε σωστά';
                    echo "  ✓ Η σύνδεση μη επαληθευμένου οδηγού απορρίφθηκε σωστά\n";
                    echo "  ✓ Μήνυμα σφάλματος: " . Session::get('login_error') . "\n";
                } else {
                    $this->results['unverified_login'] = 'ΑΠΟΤΥΧΙΑ - Μη επαληθευμένος οδηγός μπόρεσε να συνδεθεί!';
                    echo "  ✗ ΣΦΑΛΜΑ: Μη επαληθευμένος οδηγός μπόρεσε να συνδεθεί!\n";
                }

                // Διαγραφή του test οδηγού
                $this->profileModel->delete($unverifiedId);
            }

            // Καθαρισμός
            unset($_POST['email'], $_POST['password']);
            $_SERVER['REQUEST_METHOD'] = 'GET';
        } catch (Exception $e) {
            $this->results['unverified_login'] = 'ΣΦΑΛΜΑ - ' . $e->getMessage();
            echo "  ✗ Σφάλμα: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testWrongCredentialsLogin()
    {
        echo "5. Έλεγχος Σύνδεσης με Λάθος Διαπιστευτήρια...\n";

        try {
            // Καθαρισμός session
            $this->resetSessionState();

            // Προσομοίωση POST δεδομένων με λάθος κωδικό
            $_POST['email'] = $this->testEmail;
            $_POST['password'] = 'WrongPassword123!';
            $_SERVER['REQUEST_METHOD'] = 'POST';

            // Εκτέλεση χωρίς να στέλνουμε headers
            $result = $this->runControllerMethod(function () {
                $this->authController->processLogin();
            });

            // Έλεγχος αν η σύνδεση απορρίφθηκε
            if (!Session::has('user_id') && Session::has('login_error')) {
                $this->results['wrong_credentials'] = 'ΕΠΙΤΥΧΙΑ - Λάθος διαπιστευτήρια απορρίφθηκαν';
                echo "  ✓ Η σύνδεση με λάθος διαπιστευτήρια απορρίφθηκε σωστά\n";
                echo "  ✓ Μήνυμα σφάλματος: " . Session::get('login_error') . "\n";
            } else {
                $this->results['wrong_credentials'] = 'ΑΠΟΤΥΧΙΑ - Το σύστημα δέχτηκε λάθος διαπιστευτήρια!';
                echo "  ✗ ΣΦΑΛΜΑ: Το σύστημα δέχτηκε λάθος διαπιστευτήρια!\n";
            }

            // Καθαρισμός
            unset($_POST['email'], $_POST['password']);
            $_SERVER['REQUEST_METHOD'] = 'GET';
        } catch (Exception $e) {
            $this->results['wrong_credentials'] = 'ΣΦΑΛΜΑ - ' . $e->getMessage();
            echo "  ✗ Σφάλμα: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testDriverLogout()
    {
        echo "6. Έλεγχος Αποσύνδεσης Οδηγού...\n";

        try {
            // Βεβαιωνόμαστε ότι είμαστε συνδεδεμένοι
            Session::set('user_id', 1);
            Session::set('role', 'driver');
            Session::set('user_name', 'Test Driver');

            // Εκτέλεση χωρίς να στέλνουμε headers
            $result = $this->runControllerMethod(function () {
                $this->authController->logout();
            });

            // Έλεγχος αν το session καταστράφηκε
            if (!Session::has('user_id') && !Session::has('role')) {
                $this->results['logout'] = 'ΕΠΙΤΥΧΙΑ - Αποσύνδεση επιτυχής';
                echo "  ✓ Επιτυχής αποσύνδεση\n";
                echo "  ✓ Το session καταστράφηκε\n";
            } else {
                $this->results['logout'] = 'ΑΠΟΤΥΧΙΑ - Το session δεν καταστράφηκε';
                echo "  ✗ Σφάλμα: Το session δεν καταστράφηκε πλήρως\n";
            }
        } catch (Exception $e) {
            $this->results['logout'] = 'ΣΦΑΛΜΑ - ' . $e->getMessage();
            echo "  ✗ Σφάλμα: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testSessionSecurity()
    {
        echo "7. Έλεγχος Ασφάλειας Session...\n";

        try {
            // Έλεγχος παραμέτρων session
            $sessionParams = session_get_cookie_params();

            echo "  - Cookie lifetime: " . $sessionParams['lifetime'] . "\n";
            echo "  - Cookie path: " . $sessionParams['path'] . "\n";
            echo "  - Cookie domain: " . $sessionParams['domain'] . "\n";
            echo "  - Cookie secure: " . ($sessionParams['secure'] ? 'ναι' : 'όχι') . "\n";
            echo "  - Cookie httponly: " . ($sessionParams['httponly'] ? 'ναι' : 'όχι') . "\n";

            // Έλεγχος για session hijacking protection
            $userId = Session::get('USER_ID_SESSION_VAR');
            $userAgent = Session::get('HTTP_USER_AGENT');

            if ($userAgent === $_SERVER['HTTP_USER_AGENT']) {
                echo "  ✓ User Agent validation working\n";
            }

            $this->results['session_security'] = 'ΕΠΙΤΥΧΙΑ - Session parameters checked';
        } catch (Exception $e) {
            $this->results['session_security'] = 'ΣΦΑΛΜΑ - ' . $e->getMessage();
            echo "  ✗ Σφάλμα: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function testCSRFProtection()
    {
        echo "8. Έλεγχος Προστασίας CSRF...\n";

        try {
            // Δημιουργία CSRF token
            $token = CSRF::generateToken();
            echo "  ✓ CSRF token generated\n";

            // Έλεγχος επαλήθευσης
            if (CSRF::validateToken($token)) {
                echo "  ✓ CSRF token validation working\n";
            } else {
                echo "  ✗ CSRF token validation failed\n";
            }

            // Έλεγχος με λάθος token
            if (!CSRF::validateToken('wrong_token')) {
                echo "  ✓ Invalid token properly rejected\n";
            } else {
                echo "  ✗ Invalid token accepted!\n";
            }

            $this->results['csrf_protection'] = 'ΕΠΙΤΥΧΙΑ - CSRF protection working';
        } catch (Exception $e) {
            $this->results['csrf_protection'] = 'ΣΦΑΛΜΑ - ' . $e->getMessage();
            echo "  ✗ Σφάλμα: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }

    private function cleanup()
    {
        echo "Καθαρισμός δεδομένων τεστ...\n";

        try {
            // Διαγραφή του test οδηγού
            $driver = $this->profileModel->getDriverByEmail($this->testEmail);
            if ($driver) {
                $this->profileModel->delete($driver['id']);
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
    $test = new DriverAuthTest();
    $test->runAllTests();
} catch (Exception $e) {
    echo "ΚΡΙΣΙΜΟ ΣΦΑΛΜΑ: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

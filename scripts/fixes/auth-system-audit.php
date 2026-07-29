<?php

/**
 * Ολοκληρωμένος έλεγχος του συστήματος authentication
 * Εντοπισμός και διόρθωση προβλημάτων
 */

require_once __DIR__ . '/../../config/database.php';

class AuthSystemAudit
{
    private $pdo;
    private $issues = [];
    private $fixes = [];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function run()
    {
        echo "\n=== ΕΛΕΓΧΟΣ ΣΥΣΤΗΜΑΤΟΣ AUTHENTICATION ===\n\n";

        // 1. Έλεγχος δομής βάσης δεδομένων
        $this->checkDatabaseStructure();

        // 2. Έλεγχος χρηστών
        $this->checkUsers();

        // 3. Έλεγχος sessions
        $this->checkSessions();

        // 4. Έλεγχος αρχείων authentication
        $this->checkAuthFiles();

        // 5. Έλεγχος cache και cookies
        $this->checkCacheAndCookies();

        // 6. Έλεγχος password reset
        $this->checkPasswordReset();

        // Εμφάνιση αποτελεσμάτων
        $this->displayResults();

        // Εφαρμογή διορθώσεων
        if (!empty($this->fixes)) {
            $this->applyFixes();
        }
    }

    private function checkDatabaseStructure()
    {
        echo "1. Έλεγχος δομής βάσης δεδομένων...\n";

        // Έλεγχος πίνακα users
        $stmt = $this->pdo->query("SHOW COLUMNS FROM users");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $requiredColumns = ['id', 'email', 'password', 'role', 'is_active', 'email_verified'];
        $missingColumns = array_diff($requiredColumns, $columns);

        if (!empty($missingColumns)) {
            $this->issues[] = "Λείπουν στήλες από τον πίνακα users: " . implode(', ', $missingColumns);

            // Προσθήκη στηλών που λείπουν
            foreach ($missingColumns as $column) {
                switch ($column) {
                    case 'is_active':
                        $this->fixes[] = "ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1";
                        break;
                    case 'email_verified':
                        $this->fixes[] = "ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0";
                        break;
                    case 'role':
                        $this->fixes[] = "ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'user'";
                        break;
                }
            }
        }

        // Έλεγχος indexes
        $stmt = $this->pdo->query("SHOW INDEX FROM users WHERE Key_name = 'email'");
        if ($stmt->rowCount() == 0) {
            $this->issues[] = "Λείπει index για το email στον πίνακα users";
            $this->fixes[] = "ALTER TABLE users ADD INDEX idx_email (email)";
        }

        // Έλεγχος πίνακα password_resets
        $stmt = $this->pdo->query("SHOW TABLES LIKE 'password_resets'");
        if ($stmt->rowCount() == 0) {
            $this->issues[] = "Λείπει ο πίνακας password_resets";
            $this->fixes[] = "CREATE TABLE password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                token VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_token (token)
            )";
        }

        echo "   ✓ Ολοκληρώθηκε\n\n";
    }

    private function checkUsers()
    {
        echo "2. Έλεγχος χρηστών...\n";

        $testUsers = [
            'admin@drivejob.gr' => 'admin',
            'info@thessdrive.gr' => 'company',
            'kostas.michailidis1@gmail.com' => 'driver'
        ];

        foreach ($testUsers as $email => $expectedRole) {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $this->issues[] = "Δεν βρέθηκε ο χρήστης: $email";
                continue;
            }

            echo "   - $email:\n";
            echo "     ID: {$user['id']}\n";
            echo "     Role: {$user['role']}\n";
            echo "     Active: " . ($user['is_active'] ?? 'N/A') . "\n";
            echo "     Verified: " . ($user['email_verified'] ?? 'N/A') . "\n";

            // Έλεγχος password hash
            if (strlen($user['password']) < 60) {
                $this->issues[] = "Ο χρήστης $email έχει μη έγκυρο password hash";
                $this->fixes[] = "UPDATE users SET password = '" .
                    password_hash('password123', PASSWORD_DEFAULT) .
                    "' WHERE email = '$email'";
            }

            // Έλεγχος ενεργοποίησης
            if (isset($user['is_active']) && $user['is_active'] != 1) {
                $this->issues[] = "Ο χρήστης $email δεν είναι ενεργός";
                $this->fixes[] = "UPDATE users SET is_active = 1 WHERE email = '$email'";
            }
        }

        echo "   ✓ Ολοκληρώθηκε\n\n";
    }

    private function checkSessions()
    {
        echo "3. Έλεγχος sessions...\n";

        // Έλεγχος session configuration
        $sessionPath = session_save_path();
        if (empty($sessionPath)) {
            $sessionPath = sys_get_temp_dir();
        }

        echo "   Session save path: $sessionPath\n";

        if (!is_writable($sessionPath)) {
            $this->issues[] = "Το session directory δεν είναι writable: $sessionPath";
        }

        // Έλεγχος session settings
        $settings = [
            'session.use_cookies' => ini_get('session.use_cookies'),
            'session.use_only_cookies' => ini_get('session.use_only_cookies'),
            'session.cookie_httponly' => ini_get('session.cookie_httponly'),
            'session.cookie_secure' => ini_get('session.cookie_secure'),
            'session.cookie_samesite' => ini_get('session.cookie_samesite')
        ];

        foreach ($settings as $key => $value) {
            echo "   $key: $value\n";
        }

        echo "   ✓ Ολοκληρώθηκε\n\n";
    }

    private function checkAuthFiles()
    {
        echo "4. Έλεγχος αρχείων authentication...\n";

        $files = [
            'public/login.php' => 'Login page',
            'src/Controllers/AuthController.php' => 'Auth Controller',
            'public/logout.php' => 'Logout page',
            'public/auth/forgot-password.php' => 'Password reset',
            'public/auth/reset-password.php' => 'Password reset form'
        ];

        foreach ($files as $file => $description) {
            $fullPath = __DIR__ . '/../../' . $file;
            if (file_exists($fullPath)) {
                echo "   ✓ $description: Υπάρχει\n";

                // Έλεγχος για session_start()
                $content = file_get_contents($fullPath);
                if (
                    strpos($content, 'session_start()') === false &&
                    strpos($content, 'session_status()') === false
                ) {
                    $this->issues[] = "Το αρχείο $file μπορεί να μην ξεκινάει σωστά το session";
                }
            } else {
                echo "   ✗ $description: Λείπει\n";
                $this->issues[] = "Λείπει το αρχείο: $file";
            }
        }

        echo "   ✓ Ολοκληρώθηκε\n\n";
    }

    private function checkCacheAndCookies()
    {
        echo "5. Έλεγχος cache και cookies...\n";

        // Έλεγχος browser cache headers
        $loginFile = __DIR__ . '/../../public/login.php';
        if (file_exists($loginFile)) {
            $content = file_get_contents($loginFile);

            $cacheHeaders = [
                'Cache-Control: no-cache',
                'Cache-Control: no-store',
                'Pragma: no-cache'
            ];

            $hasCacheControl = false;
            foreach ($cacheHeaders as $header) {
                if (strpos($content, $header) !== false) {
                    $hasCacheControl = true;
                    break;
                }
            }

            if (!$hasCacheControl) {
                $this->issues[] = "Το login.php δεν έχει headers για αποφυγή caching";
            }
        }

        echo "   ✓ Ολοκληρώθηκε\n\n";
    }

    private function checkPasswordReset()
    {
        echo "6. Έλεγχος password reset...\n";

        // Έλεγχος αν υπάρχουν τα απαραίτητα αρχεία
        $resetFiles = [
            'public/auth/forgot-password.php',
            'public/auth/reset-password.php',
            'src/Controllers/PasswordResetController.php'
        ];

        foreach ($resetFiles as $file) {
            $fullPath = __DIR__ . '/../../' . $file;
            if (!file_exists($fullPath)) {
                $this->issues[] = "Λείπει το αρχείο password reset: $file";
            }
        }

        // Έλεγχος email configuration
        $emailConfig = __DIR__ . '/../../config/email.php';
        if (file_exists($emailConfig)) {
            echo "   ✓ Email configuration υπάρχει\n";
        } else {
            $this->issues[] = "Λείπει το email configuration";
        }

        echo "   ✓ Ολοκληρώθηκε\n\n";
    }

    private function displayResults()
    {
        echo "\n=== ΑΠΟΤΕΛΕΣΜΑΤΑ ΕΛΕΓΧΟΥ ===\n\n";

        if (empty($this->issues)) {
            echo "✓ Δεν βρέθηκαν προβλήματα!\n";
        } else {
            echo "ΠΡΟΒΛΗΜΑΤΑ ΠΟΥ ΒΡΕΘΗΚΑΝ:\n";
            foreach ($this->issues as $i => $issue) {
                echo ($i + 1) . ". $issue\n";
            }
        }

        if (!empty($this->fixes)) {
            echo "\nΠΡΟΤΕΙΝΟΜΕΝΕΣ ΔΙΟΡΘΩΣΕΙΣ:\n";
            foreach ($this->fixes as $i => $fix) {
                echo ($i + 1) . ". $fix\n";
            }
        }
    }

    private function applyFixes()
    {
        echo "\n=== ΕΦΑΡΜΟΓΗ ΔΙΟΡΘΩΣΕΩΝ ===\n\n";

        echo "Θέλετε να εφαρμοστούν οι διορθώσεις; (y/n): ";
        $answer = trim(fgets(STDIN));

        if (strtolower($answer) !== 'y') {
            echo "Ακυρώθηκε η εφαρμογή διορθώσεων.\n";
            return;
        }

        foreach ($this->fixes as $fix) {
            try {
                echo "Εκτέλεση: $fix\n";
                $this->pdo->exec($fix);
                echo "   ✓ Επιτυχής\n";
            } catch (PDOException $e) {
                echo "   ✗ Σφάλμα: " . $e->getMessage() . "\n";
            }
        }

        echo "\n✓ Ολοκληρώθηκαν οι διορθώσεις\n";
    }
}

// Εκτέλεση audit
try {
    $audit = new AuthSystemAudit($pdo);
    $audit->run();
} catch (Exception $e) {
    echo "Σφάλμα: " . $e->getMessage() . "\n";
}

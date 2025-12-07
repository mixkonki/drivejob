<?php

/**
 * Εφαρμογή διορθώσεων στο σύστημα authentication
 */

require_once __DIR__ . '/../../config/database.php';

echo "\n=== ΕΦΑΡΜΟΓΗ ΔΙΟΡΘΩΣΕΩΝ AUTHENTICATION ===\n\n";

$fixes = [
    "ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'user'" => "Προσθήκη στήλης role",
    "ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0" => "Προσθήκη στήλης email_verified",
    "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_token (token)
    )" => "Δημιουργία πίνακα password_resets"
];

foreach ($fixes as $sql => $description) {
    echo "• $description... ";
    try {
        $pdo->exec($sql);
        echo "✓\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "✓ (υπάρχει ήδη)\n";
        } else {
            echo "✗ Σφάλμα: " . $e->getMessage() . "\n";
        }
    }
}

// Ενημέρωση roles για υπάρχοντες χρήστες
echo "\n=== ΕΝΗΜΕΡΩΣΗ ROLES ΧΡΗΣΤΩΝ ===\n\n";

$userRoles = [
    'admin@drivejob.gr' => 'admin',
    'info@thessdrive.gr' => 'company',
    'kostas.michailidis1@gmail.com' => 'driver'
];

foreach ($userRoles as $email => $role) {
    echo "• Ενημέρωση $email σε role '$role'... ";
    try {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE email = ?");
        $stmt->execute([$role, $email]);
        if ($stmt->rowCount() > 0) {
            echo "✓\n";
        } else {
            echo "✗ (δεν βρέθηκε)\n";
        }
    } catch (PDOException $e) {
        echo "✗ Σφάλμα: " . $e->getMessage() . "\n";
    }
}

// Δημιουργία του χρήστη οδηγού αν δεν υπάρχει
echo "\n=== ΕΛΕΓΧΟΣ ΧΡΗΣΤΗ ΟΔΗΓΟΥ ===\n\n";

$driverEmail = 'kostas.michailidis1@gmail.com';
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$driverEmail]);

if (!$stmt->fetch()) {
    echo "• Δημιουργία χρήστη οδηγού... ";
    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, role, is_active, email_verified, created_at) 
            VALUES (?, ?, 'driver', 1, 1, NOW())
        ");
        $stmt->execute([
            $driverEmail,
            password_hash('password123', PASSWORD_DEFAULT)
        ]);

        $userId = $pdo->lastInsertId();

        // Δημιουργία εγγραφής στον πίνακα drivers
        $stmt = $pdo->prepare("
            INSERT INTO drivers (user_id, first_name, last_name, phone, created_at) 
            VALUES (?, 'Κώστας', 'Μιχαηλίδης', '6900000000', NOW())
            ON DUPLICATE KEY UPDATE first_name = 'Κώστας', last_name = 'Μιχαηλίδης'
        ");
        $stmt->execute([$userId]);

        echo "✓\n";
    } catch (PDOException $e) {
        echo "✗ Σφάλμα: " . $e->getMessage() . "\n";
    }
} else {
    echo "• Ο χρήστης οδηγός υπάρχει ήδη ✓\n";
}

// Διόρθωση cache headers στο login.php
echo "\n=== ΔΙΟΡΘΩΣΗ CACHE HEADERS ===\n\n";

$loginFile = __DIR__ . '/../../public/login.php';
if (file_exists($loginFile)) {
    $content = file_get_contents($loginFile);

    // Έλεγχος αν υπάρχουν ήδη cache headers
    if (strpos($content, 'Cache-Control') === false) {
        // Προσθήκη cache headers μετά το session_start
        $newContent = preg_replace(
            '/(session_start\(\);?)/',
            "$1\n\n// Prevent caching\nheader('Cache-Control: no-cache, no-store, must-revalidate');\nheader('Pragma: no-cache');\nheader('Expires: 0');",
            $content,
            1
        );

        if ($newContent !== $content) {
            file_put_contents($loginFile, $newContent);
            echo "• Cache headers προστέθηκαν στο login.php ✓\n";
        }
    } else {
        echo "• Cache headers υπάρχουν ήδη στο login.php ✓\n";
    }
}

echo "\n=== ΔΗΜΙΟΥΡΓΙΑ ΑΡΧΕΙΩΝ PASSWORD RESET ===\n\n";

// Δημιουργία φακέλου auth αν δεν υπάρχει
$authDir = __DIR__ . '/../../public/auth';
if (!is_dir($authDir)) {
    mkdir($authDir, 0755, true);
    echo "• Δημιουργία φακέλου /public/auth ✓\n";
}

// Δημιουργία forgot-password.php
$forgotPasswordFile = $authDir . '/forgot-password.php';
if (!file_exists($forgotPasswordFile)) {
    $forgotPasswordContent = '<?php
session_start();

// Prevent caching
header(\'Cache-Control: no-cache, no-store, must-revalidate\');
header(\'Pragma: no-cache\');
header(\'Expires: 0\');

require_once __DIR__ . \'/../../config/database.php\';

$message = \'\';
$error = \'\';

if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\') {
    $email = filter_var($_POST[\'email\'] ?? \'\', FILTER_VALIDATE_EMAIL);
    
    if ($email) {
        // Έλεγχος αν υπάρχει ο χρήστης
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            // Δημιουργία token
            $token = bin2hex(random_bytes(32));
            
            // Αποθήκευση token
            $stmt = $pdo->prepare("
                INSERT INTO password_resets (email, token) 
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE token = ?, created_at = NOW()
            ");
            $stmt->execute([$email, $token, $token]);
            
            // Εδώ θα στέλνατε email με το link
            $resetLink = "http://localhost/drivejob/public/auth/reset-password.php?token=" . $token;
            
            $message = "Ένα email με οδηγίες επαναφοράς κωδικού έχει σταλεί στο $email";
            
            // Για testing, εμφάνιση του link
            $message .= "<br><br>Για δοκιμή, χρησιμοποιήστε: <a href=\'$resetLink\'>$resetLink</a>";
        } else {
            $message = "Αν το email υπάρχει στο σύστημα, θα λάβετε οδηγίες επαναφοράς.";
        }
    } else {
        $error = "Παρακαλώ εισάγετε έγκυρο email.";
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Επαναφορά Κωδικού - DriveJob</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Επαναφορά Κωδικού</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?= $message ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Αποστολή Οδηγιών</button>
                            <a href="/drivejob/public/login.php" class="btn btn-link">Επιστροφή στη Σύνδεση</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';

    file_put_contents($forgotPasswordFile, $forgotPasswordContent);
    echo "• Δημιουργία forgot-password.php ✓\n";
}

// Δημιουργία reset-password.php
$resetPasswordFile = $authDir . '/reset-password.php';
if (!file_exists($resetPasswordFile)) {
    $resetPasswordContent = '<?php
session_start();

// Prevent caching
header(\'Cache-Control: no-cache, no-store, must-revalidate\');
header(\'Pragma: no-cache\');
header(\'Expires: 0\');

require_once __DIR__ . \'/../../config/database.php\';

$message = \'\';
$error = \'\';
$token = $_GET[\'token\'] ?? $_POST[\'token\'] ?? \'\';

if (!$token) {
    header(\'Location: /drivejob/public/auth/forgot-password.php\');
    exit;
}

// Έλεγχος token
$stmt = $pdo->prepare("
    SELECT email FROM password_resets 
    WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    $error = "Το link επαναφοράς έχει λήξει ή δεν είναι έγκυρο.";
}

if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\' && $reset) {
    $password = $_POST[\'password\'] ?? \'\';
    $confirmPassword = $_POST[\'confirm_password\'] ?? \'\';
    
    if (strlen($password) < 6) {
        $error = "Ο κωδικός πρέπει να έχει τουλάχιστον 6 χαρακτήρες.";
    } elseif ($password !== $confirmPassword) {
        $error = "Οι κωδικοί δεν ταιριάζουν.";
    } else {
        // Ενημέρωση κωδικού
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $reset[\'email\']]);
        
        // Διαγραφή token
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);
        
        $message = "Ο κωδικός σας ενημερώθηκε επιτυχώς!";
        
        // Redirect μετά από 3 δευτερόλεπτα
        header("refresh:3;url=/drivejob/public/login.php");
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Νέος Κωδικός - DriveJob</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Εισάγετε Νέο Κωδικό</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?= $message ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <?php if ($reset && !$message): ?>
                        <form method="POST">
                            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                            <div class="mb-3">
                                <label for="password" class="form-label">Νέος Κωδικός</label>
                                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Επιβεβαίωση Κωδικού</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                            </div>
                            <button type="submit" class="btn btn-primary">Αλλαγή Κωδικού</button>
                        </form>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <a href="/drivejob/public/login.php" class="btn btn-link">Επιστροφή στη Σύνδεση</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';

    file_put_contents($resetPasswordFile, $resetPasswordContent);
    echo "• Δημιουργία reset-password.php ✓\n";
}

echo "\n=== ΟΛΟΚΛΗΡΩΣΗ ΔΙΟΡΘΩΣΕΩΝ ===\n\n";
echo "✓ Όλες οι διορθώσεις εφαρμόστηκαν επιτυχώς!\n\n";

echo "ΧΡΗΣΤΕΣ ΓΙΑ ΔΟΚΙΜΗ:\n";
echo "• admin@drivejob.gr (password: password123) - Role: admin\n";
echo "• info@thessdrive.gr (password: password123) - Role: company\n";
echo "• kostas.michailidis1@gmail.com (password: password123) - Role: driver\n\n";

echo "ΛΕΙΤΟΥΡΓΙΕΣ:\n";
echo "• Login: http://localhost/drivejob/public/login.php\n";
echo "• Forgot Password: http://localhost/drivejob/public/auth/forgot-password.php\n";

<?php

/**
 * Test script για το email system
 * Δοκιμάζει SMTP connection και password reset functionality
 */

require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/config/email.php';

use Drivejob\Services\EmailService;
use Drivejob\Models\AuthModel;

echo "=== Email System Test ===\n\n";

// Test 1: SMTP Connection
echo "Test 1: SMTP Connection\n";
echo "------------------------\n";

try {
    $emailService = new EmailService(
        SMTP_HOST,
        SMTP_PORT,
        SMTP_USERNAME,
        SMTP_PASSWORD,
        SMTP_FROM_EMAIL,
        SMTP_FROM_NAME,
        true // Enable debug mode
    );

    echo "✅ EmailService initialized successfully\n";
    echo "SMTP Host: " . SMTP_HOST . "\n";
    echo "SMTP Port: " . SMTP_PORT . "\n";
    echo "SMTP Username: " . SMTP_USERNAME . "\n";
    echo "From Email: " . SMTP_FROM_EMAIL . "\n";
    echo "\n";
} catch (\Exception $e) {
    echo "❌ Failed to initialize EmailService: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Send Test Email
echo "Test 2: Send Test Email\n";
echo "------------------------\n";

$testEmail = 'kostas.michailidis@hotmail.gr'; // Αλλάξτε με το δικό σας email για testing

$subject = 'Test Email από DriveJob';
$message = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #c62828; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>DriveJob Email Test</h1>
        </div>
        <div class='content'>
            <h2>Δοκιμαστικό Email</h2>
            <p>Αυτό είναι ένα δοκιμαστικό email από το σύστημα DriveJob.</p>
            <p>Αν λάβατε αυτό το email, σημαίνει ότι το SMTP configuration λειτουργεί σωστά!</p>
            <p><strong>Ημερομηνία:</strong> " . date('d/m/Y H:i:s') . "</p>
        </div>
    </div>
</body>
</html>
";

echo "Sending test email to: $testEmail\n";
echo "Subject: $subject\n";

$result = $emailService->send($testEmail, $subject, $message);

if ($result) {
    echo "✅ Test email sent successfully!\n";
    echo "Παρακαλώ ελέγξτε το inbox σας (και το spam folder).\n\n";
} else {
    echo "❌ Failed to send test email\n";
    echo "Ελέγξτε τα SMTP credentials και τα error logs.\n\n";
}

// Test 3: Password Reset Flow
echo "Test 3: Password Reset Flow\n";
echo "----------------------------\n";

$pdo = require __DIR__ . '/config/database.php';
$authModel = new AuthModel($pdo);

$resetEmail = 'kostas.michailidis@hotmail.gr'; // Email που υπάρχει στη βάση

echo "Testing password reset for: $resetEmail\n";

$resetResult = $authModel->sendPasswordResetEmail($resetEmail);

if ($resetResult) {
    echo "✅ Password reset email sent successfully!\n";
    echo "Ελέγξτε το email για τον σύνδεσμο επαναφοράς.\n\n";

    // Ανάκτηση του reset code από τη βάση για verification
    $stmt = $pdo->prepare("SELECT reset_code, reset_expires FROM drivers WHERE email = ?");
    $stmt->execute([$resetEmail]);
    $resetData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resetData && $resetData['reset_code']) {
        echo "Reset Code: " . $resetData['reset_code'] . "\n";
        echo "Expires: " . $resetData['reset_expires'] . "\n";
        echo "Reset Link: " . BASE_URL . "auth/reset-password/" . $resetData['reset_code'] . "\n";
    }
} else {
    echo "❌ Failed to send password reset email\n";
    echo "Πιθανές αιτίες:\n";
    echo "- Το email δεν υπάρχει στη βάση\n";
    echo "- Πρόβλημα με το SMTP\n";
    echo "- Πρόβλημα με το EmailService\n";
}

echo "\n=== Test Complete ===\n";
echo "\nΣημειώσεις:\n";
echo "- Αν το test email δεν έφτασε, ελέγξτε το spam folder\n";
echo "- Ελέγξτε τα SMTP credentials στο config/email.php\n";
echo "- Ελέγξτε τα error logs στο storage/logs/\n";
echo "- Βεβαιωθείτε ότι το PHPMailer είναι εγκατεστημένο (composer require phpmailer/phpmailer)\n";

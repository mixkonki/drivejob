<?php
session_start();
require_once 'config/database.php';

// Καθαρισμός session
$_SESSION = [];

echo "=== ΔΟΚΙΜΗ ΣΥΝΔΕΣΗΣ ADMIN ===\n\n";

// Δοκιμή με admin@drivejob.gr
$email = 'admin@drivejob.gr';
$password = 'admin123';

echo "Προσπάθεια σύνδεσης με:\n";
echo "Email: $email\n";
echo "Password: $password\n\n";

// Έλεγχος στη βάση (το config/database.php επιστρέφει το PDO object)
$query = "SELECT * FROM users WHERE (email = ? OR username = ?) AND role = 'admin'";
$stmt = $pdo->prepare($query);
$stmt->execute([$email, $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "✓ Βρέθηκε χρήστης admin:\n";
    echo "  ID: {$user['id']}\n";
    echo "  Username: {$user['username']}\n";
    echo "  Email: {$user['email']}\n";
    echo "  Role: {$user['role']}\n";

    // Έλεγχος password
    $passwordVerified = password_verify($password, $user['password']);
    echo "\n  Password verification: " . ($passwordVerified ? '✓ ΕΠΙΤΥΧΙΑ' : '✗ ΑΠΟΤΥΧΙΑ') . "\n";

    if (!$passwordVerified) {
        echo "\n  Stored hash: " . substr($user['password'], 0, 30) . "...\n";
        echo "  Hash length: " . strlen($user['password']) . "\n";

        // Δοκιμή με το hash που έχουμε
        $testHash = password_hash($password, PASSWORD_DEFAULT);
        echo "\n  Test hash για '$password': " . substr($testHash, 0, 30) . "...\n";
        echo "  Verification με test hash: " . (password_verify($password, $testHash) ? 'OK' : 'FAIL') . "\n";
    }
} else {
    echo "✗ Δεν βρέθηκε χρήστης admin με email/username: $email\n";

    // Έλεγχος χωρίς role filter
    $query2 = "SELECT * FROM users WHERE email = ? OR username = ?";
    $stmt2 = $pdo->prepare($query2);
    $stmt2->execute([$email, $email]);
    $user2 = $stmt2->fetch(PDO::FETCH_ASSOC);

    if ($user2) {
        echo "\nΒρέθηκε χρήστης (χωρίς role filter):\n";
        echo "  ID: {$user2['id']}\n";
        echo "  Username: {$user2['username']}\n";
        echo "  Email: {$user2['email']}\n";
        echo "  Role: {$user2['role']}\n";
    }
}

// Τώρα ας δοκιμάσουμε με το AuthModel
echo "\n\n=== ΔΟΚΙΜΗ ΜΕ AuthModel ===\n";

require_once 'src/Models/AuthModel.php';
require_once 'src/Core/Logger.php';
require_once 'src/Core/Session.php';

use Drivejob\Models\AuthModel;

$authModel = new AuthModel($pdo);
$result = $authModel->authenticate($email, $password);

if ($result) {
    echo "✓ ΕΠΙΤΥΧΗΣ ΣΥΝΔΕΣΗ!\n";
    echo "  User ID: {$result['user_id']}\n";
    echo "  Role: {$result['role']}\n";
    echo "  Email: {$result['email']}\n";
    echo "  Name: {$result['name']}\n";
} else {
    echo "✗ ΑΠΟΤΥΧΙΑ ΣΥΝΔΕΣΗΣ\n";
}

// Έλεγχος logs
echo "\n=== LOGS ===\n";
$logFile = 'logs/app_' . date('Y-m-d') . '.log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $recentLogs = array_slice($lines, -10);
    foreach ($recentLogs as $line) {
        if (strpos($line, 'AuthModel') !== false || strpos($line, 'admin') !== false) {
            echo $line . "\n";
        }
    }
}

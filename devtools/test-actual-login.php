<?php

/**
 * Test script για πραγματική δοκιμή login
 * Χρησιμοποιήστε αυτό το script για να δοκιμάσετε το login με πραγματικά credentials
 */

require_once __DIR__ . '/src/bootstrap.php';

use Drivejob\Core\Session;
use Drivejob\Core\CSRF;
use Drivejob\Models\AuthModel;

// Έναρξη session
Session::start();

echo "=== Test Login Script ===\n\n";

// Ζητήστε credentials από τον χρήστη
echo "Εισάγετε το email σας: ";
$email = trim(fgets(STDIN));

echo "Εισάγετε το password σας: ";
$password = trim(fgets(STDIN));

echo "\n=== Προσπάθεια Σύνδεσης ===\n";
echo "Email: $email\n";
echo "Password length: " . strlen($password) . "\n\n";

try {
    // Δημιουργία PDO connection
    $pdo = require __DIR__ . '/config/database.php';

    // Δημιουργία AuthModel
    $authModel = new AuthModel($pdo);

    // Προσπάθεια authentication
    echo "Καλώντας authenticate()...\n";
    $user = $authModel->authenticate($email, $password);

    if ($user) {
        echo "\n✅ ΕΠΙΤΥΧΙΑ! Ο χρήστης βρέθηκε:\n";
        echo "User ID: " . $user['user_id'] . "\n";
        echo "Role: " . $user['role'] . "\n";
        echo "Name: " . $user['name'] . "\n";
        echo "Email: " . $user['email'] . "\n\n";

        // Δοκιμή session storage
        Session::set('user_id', $user['user_id']);
        Session::set('user_role', $user['role']);
        Session::set('user_name', $user['name']);

        echo "Session Data αποθηκεύτηκε:\n";
        echo "user_id: " . Session::get('user_id') . "\n";
        echo "user_role: " . Session::get('user_role') . "\n";
        echo "user_name: " . Session::get('user_name') . "\n\n";

        // Δοκιμή CSRF token
        $token = CSRF::generateToken();
        echo "CSRF Token: $token\n";
        echo "Token validation: " . (CSRF::validateToken($token) ? "VALID" : "INVALID") . "\n\n";
    } else {
        echo "\n❌ ΑΠΟΤΥΧΙΑ! Ο χρήστης δεν βρέθηκε ή λάθος credentials\n\n";

        // Ελέγξτε αν ο χρήστης υπάρχει στη βάση
        echo "Έλεγχος αν ο χρήστης υπάρχει στη βάση...\n";

        // Έλεγχος στους drivers
        $stmt = $pdo->prepare("SELECT id, email FROM drivers WHERE email = ?");
        $stmt->execute([$email]);
        $driver = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($driver) {
            echo "✓ Ο χρήστης υπάρχει ως ΟΔΗΓΟΣ:\n";
            echo "  ID: " . $driver['id'] . "\n";
            echo "  Email: " . $driver['email'] . "\n";
            echo "  Πιθανόν το password είναι λάθος\n\n";
            return;
        }

        // Έλεγχος στις companies
        $stmt = $pdo->prepare("SELECT id, email FROM companies WHERE email = ?");
        $stmt->execute([$email]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($company) {
            echo "✓ Ο χρήστης υπάρχει ως ΕΤΑΙΡΕΙΑ:\n";
            echo "  ID: " . $company['id'] . "\n";
            echo "  Email: " . $company['email'] . "\n";
            echo "  Πιθανόν το password είναι λάθος\n\n";
            return;
        }

        // Έλεγχος στους admins
        $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            echo "✓ Ο χρήστης υπάρχει ως ADMIN:\n";
            echo "  ID: " . $admin['id'] . "\n";
            echo "  Email: " . $admin['email'] . "\n";
            echo "  Πιθανόν το password είναι λάθος\n\n";
            return;
        }

        echo "✗ Ο χρήστης ΔΕΝ υπάρχει στη βάση με αυτό το email\n\n";
    }
} catch (Exception $e) {
    echo "\n❌ ΣΦΑΛΜΑ: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
}

echo "=== Τέλος Test ===\n";

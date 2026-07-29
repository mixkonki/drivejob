<?php
require_once __DIR__ . '/../src/bootstrap.php';

header('Content-Type: text/plain');

echo "=== Creating Test Company User ===\n\n";

try {
    $pdo = \Drivejob\Core\Database::getInstance()->getConnection();

    // Test credentials
    $email = 'test@thessdrive.gr';
    $password = 'test123';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // First, check if user already exists
    $stmt = $pdo->prepare("SELECT id FROM companies WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        echo "User already exists with email: $email\n";

        // Update password
        $stmt = $pdo->prepare("UPDATE companies SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $email]);
        echo "Password updated for existing user.\n";
    } else {
        // Create new test company
        $stmt = $pdo->prepare("
            INSERT INTO companies (company_name, email, password, phone, is_active, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            'Test ThessDrive',
            $email,
            $hashedPassword,
            '2310123456',
            1
        ]);

        echo "New company user created successfully!\n";
    }

    echo "\nLogin credentials:\n";
    echo "Email: $email\n";
    echo "Password: $password\n";
    echo "\nYou can now login at: http://localhost/drivejob/public/login.php\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

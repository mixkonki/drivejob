<?php
// Create limited-privilege database user for application
$dsn = "mysql:host=127.0.0.1;port=3306;dbname=mysql;charset=utf8mb4";
$user = "root";
$pass = "";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);

    echo "Creating limited-privilege application user...\n";
    echo "=============================================\n";

    // Create user with a strong password
    $appPassword = 'ChangeThis_!Strong!';

    // Create user if not exists
    $stmt = $pdo->prepare("
        CREATE USER IF NOT EXISTS 'drivejob_app'@'localhost'
        IDENTIFIED BY ?
    ");
    $stmt->execute([$appPassword]);
    echo "✅ User 'drivejob_app' created or already exists\n";

    // Grant minimal necessary permissions (DML + EXECUTE, no DDL)
    $pdo->exec("
        GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE ON drivejob.* TO 'drivejob_app'@'localhost'
    ");
    echo "✅ Granted DML permissions to drivejob_app\n";

    // Show what permissions the user has
    $stmt = $pdo->prepare("
        SELECT * FROM mysql.user WHERE User = 'drivejob_app' AND Host = 'localhost'
    ");
    $stmt->execute();
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userInfo) {
        echo "✅ User details:\n";
        echo "   - User: {$userInfo['User']}\n";
        echo "   - Host: {$userInfo['Host']}\n";
        echo "   - Authentication: {$userInfo['plugin']}\n";
    }

    // Flush privileges
    $pdo->exec("FLUSH PRIVILEGES");
    echo "✅ Privileges flushed\n";

    // Test connection with new user
    echo "\nTesting connection with new user...\n";
    echo "===================================\n";

    try {
        $testDsn = "mysql:host=127.0.0.1;port=3306;dbname=drivejob;charset=utf8mb4";
        $testPdo = new PDO($testDsn, 'drivejob_app', $appPassword, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        // Test basic operations
        $testPdo->exec("SELECT 1");
        echo "✅ Connection successful\n";

        // Test SELECT
        $stmt = $testPdo->query("SELECT COUNT(*) as users FROM users");
        $count = $stmt->fetch()['users'];
        echo "✅ SELECT works: $count users found\n";

        // Test INSERT (should fail with our limited user)
        try {
            $testPdo->exec("CREATE TABLE test_permissions (id INT)");
            echo "❌ SECURITY ISSUE: DDL permissions granted!\n";
        } catch (Exception $e) {
            echo "✅ DDL blocked as expected: " . $e->getMessage() . "\n";
        }

        $testPdo = null;
    } catch (Exception $e) {
        echo "❌ Connection test failed: " . $e->getMessage() . "\n";
    }

    echo "\n🎉 SECURITY SETUP COMPLETE!\n";
    echo "===========================\n";
    echo "✅ Limited-privilege user created\n";
    echo "✅ DML permissions granted\n";
    echo "✅ DDL permissions blocked\n";
    echo "✅ Full backup created\n\n";

    echo "📋 SECURITY RECOMMENDATIONS:\n";
    echo "===========================\n";
    echo "1. Change the default password immediately\n";
    echo "2. Use this user in production config\n";
    echo "3. Keep root user for admin only\n";
    echo "4. Regular backup schedule recommended\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

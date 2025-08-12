<?php

/**
 * Ανάλυση του Login System - Διαγνωστικό εργαλείο
 */

require_once '../src/bootstrap.php';

echo "<h1>🔍 Ανάλυση Login System</h1>\n";

// 1. Έλεγχος βάσης δεδομένων
echo "<h2>1. 📊 Έλεγχος Βάσης Δεδομένων</h2>\n";

try {
    $container = \Drivejob\Core\Container::getInstance();
    $pdo = $container->get('pdo');
    echo "✅ Database connection: OK<br>\n";

    // Έλεγχος πίνακα users
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    echo "<h3>Users Table Structure:</h3>\n";
    echo "<pre>\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) " .
            ($column['Null'] === 'YES' ? 'NULL' : 'NOT NULL') .
            ($column['Key'] ? " KEY: {$column['Key']}" : '') . "\n";
    }
    echo "</pre>\n";

    // Έλεγχος admin users
    $stmt = $pdo->query("SELECT id, email, role, first_name, last_name, created_at FROM users WHERE role = 'admin'");
    $admins = $stmt->fetchAll();
    echo "<h3>Admin Users:</h3>\n";
    if ($admins) {
        echo "<pre>\n";
        foreach ($admins as $admin) {
            echo "ID: {$admin['id']}, Email: {$admin['email']}, Role: {$admin['role']}\n";
            echo "Name: " . ($admin['first_name'] ?? 'NULL') . " " . ($admin['last_name'] ?? 'NULL') . "\n";
            echo "Created: {$admin['created_at']}\n\n";
        }
        echo "</pre>\n";
    } else {
        echo "❌ Δεν βρέθηκαν admin users!<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>\n";
}

// 2. Έλεγχος αρχείων login
echo "<h2>2. 📁 Έλεγχος Αρχείων Login</h2>\n";

$loginFiles = [
    'public/login.php',
    'public/login-process.php',
    'public/logout.php',
    'config/config.php'
];

foreach ($loginFiles as $file) {
    if (file_exists($file)) {
        echo "✅ {$file} exists<br>\n";

        // Διάβασμα περιεχομένου
        $content = file_get_contents($file);
        if (strpos($content, 'auth/login') !== false) {
            echo "⚠️ {$file} redirects to auth/login (που δεν υπάρχει)<br>\n";
        }
        if (strpos($content, 'SESSION') !== false) {
            echo "📝 {$file} χρησιμοποιεί sessions<br>\n";
        }
    } else {
        echo "❌ {$file} missing<br>\n";
    }
}

// 3. Έλεγχος session configuration
echo "<h2>3. ⚙️ Session Configuration</h2>\n";
echo "<pre>\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "Session Cookie Lifetime: " . session_get_cookie_params()['lifetime'] . "\n";
echo "</pre>\n";

// 4. Έλεγχος working admin pages
echo "<h2>4. 🔧 Working Admin Pages</h2>\n";

$adminFiles = [
    'public/admin/ai-settings.php',
    'public/admin/test-connection.php',
    'public/admin/settings.php'
];

foreach ($adminFiles as $file) {
    if (file_exists($file)) {
        echo "✅ {$file} exists<br>\n";

        $content = file_get_contents($file);
        if (strpos($content, "SESSION['role']") !== false) {
            echo "📝 {$file} ελέγχει \$_SESSION['role']<br>\n";
        }
        if (strpos($content, "bootstrap.php") !== false) {
            echo "📝 {$file} χρησιμοποιεί bootstrap<br>\n";
        }
    }
}

// 5. Προτάσεις διόρθωσης
echo "<h2>5. 💡 Προτάσεις Διόρθωσης</h2>\n";
echo "<ul>\n";
echo "<li>Διόρθωση public/login.php - αφαίρεση redirect σε auth/login</li>\n";
echo "<li>Δημιουργία working login form</li>\n";
echo "<li>Διόρθωση session management</li>\n";
echo "<li>Έλεγχος admin user credentials</li>\n";
echo "</ul>\n";

// 6. Test admin credentials
echo "<h2>6. 🔑 Test Admin Credentials</h2>\n";
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute(['admin@drivejob.gr']);
    $admin = $stmt->fetch();

    if ($admin) {
        echo "✅ Admin user found: {$admin['email']}<br>\n";

        // Test password
        if (password_verify('admin123', $admin['password'])) {
            echo "✅ Password 'admin123' is correct<br>\n";
        } else {
            echo "❌ Password 'admin123' is incorrect<br>\n";
            echo "🔧 Trying to update password...<br>\n";

            $newHash = password_hash('admin123', PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ? AND role = 'admin'");
            $updateStmt->execute([$newHash, 'admin@drivejob.gr']);
            echo "✅ Password updated to 'admin123'<br>\n";
        }
    } else {
        echo "❌ Admin user not found<br>\n";
        echo "🔧 Creating admin user...<br>\n";

        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $insertStmt = $pdo->prepare("
            INSERT INTO users (email, password, role, first_name, last_name, created_at) 
            VALUES (?, ?, 'admin', 'Admin', 'User', NOW())
        ");
        $insertStmt->execute(['admin@drivejob.gr', $hash]);
        echo "✅ Admin user created<br>\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>\n";
}

echo "<hr>\n";
echo "<p><strong>Επόμενο βήμα:</strong> Διόρθωση των αρχείων login με βάση την ανάλυση</p>\n";

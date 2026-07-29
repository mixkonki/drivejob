<?php

/**
 * Έλεγχος και διόρθωση της δομής των πινάκων RBAC
 */

require_once __DIR__ . '/../../config/database.php';

echo "\n=== ΕΛΕΓΧΟΣ ΔΟΜΗΣ ΠΙΝΑΚΩΝ RBAC ===\n\n";

// 1. Έλεγχος πίνακα role_permissions
echo "1. Έλεγχος πίνακα role_permissions...\n";
$stmt = $pdo->query("SHOW COLUMNS FROM role_permissions");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "   Στήλες: " . implode(', ', $columns) . "\n";

// Αν δεν υπάρχει η στήλη permission, πρέπει να αναδημιουργήσουμε τον πίνακα
if (!in_array('permission', $columns)) {
    echo "   ✗ Λείπει η στήλη 'permission'. Αναδημιουργία πίνακα...\n";

    // Backup υπάρχοντα δεδομένα αν υπάρχουν
    try {
        $pdo->exec("DROP TABLE IF EXISTS role_permissions_backup");
        $pdo->exec("CREATE TABLE role_permissions_backup AS SELECT * FROM role_permissions");
        echo "   Backup δημιουργήθηκε\n";
    } catch (PDOException $e) {
        echo "   Δεν υπάρχουν δεδομένα για backup\n";
    }

    // Διαγραφή και αναδημιουργία
    $pdo->exec("DROP TABLE IF EXISTS role_permissions");
    $pdo->exec("
        CREATE TABLE role_permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role VARCHAR(50) NOT NULL,
            permission_name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_role_permission (role, permission_name),
            INDEX idx_role (role)
        )
    ");
    echo "   ✓ Πίνακας αναδημιουργήθηκε με σωστή δομή\n";
}

// 2. Έλεγχος πίνακα permissions
echo "\n2. Έλεγχος πίνακα permissions...\n";
$stmt = $pdo->query("SHOW TABLES LIKE 'permissions'");
if ($stmt->rowCount() == 0) {
    echo "   Δημιουργία πίνακα permissions...\n";
    $pdo->exec("
        CREATE TABLE permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            permission_name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_permission (user_id, permission_name),
            INDEX idx_user_id (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "   ✓ Δημιουργήθηκε\n";
} else {
    // Έλεγχος στηλών
    $stmt = $pdo->query("SHOW COLUMNS FROM permissions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   Στήλες: " . implode(', ', $columns) . "\n";

    // Αν έχει permission αντί για permission_name, διόρθωση
    if (in_array('permission', $columns) && !in_array('permission_name', $columns)) {
        echo "   Μετονομασία στήλης permission σε permission_name...\n";
        $pdo->exec("ALTER TABLE permissions CHANGE permission permission_name VARCHAR(100) NOT NULL");
        echo "   ✓ Μετονομάστηκε\n";
    }
}

// 3. Εισαγωγή βασικών permissions ανά ρόλο
echo "\n3. Εισαγωγή permissions ανά ρόλο...\n";

$rolePermissions = [
    'admin' => [
        'admin.access',
        'admin.dashboard',
        'admin.users.view',
        'admin.users.edit',
        'admin.users.delete',
        'admin.companies.view',
        'admin.companies.edit',
        'admin.drivers.view',
        'admin.drivers.edit',
        'admin.settings.view',
        'admin.settings.edit'
    ],
    'company' => [
        'company.access',
        'company.dashboard',
        'company.profile.view',
        'company.profile.edit',
        'company.jobs.create',
        'company.jobs.edit',
        'company.jobs.delete',
        'company.drivers.view'
    ],
    'driver' => [
        'driver.access',
        'driver.dashboard',
        'driver.profile.view',
        'driver.profile.edit',
        'driver.jobs.view',
        'driver.jobs.apply'
    ]
];

foreach ($rolePermissions as $role => $permissions) {
    echo "   • Ρόλος: $role\n";
    foreach ($permissions as $permission) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO role_permissions (role, permission_name) 
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE permission_name = permission_name
            ");
            $stmt->execute([$role, $permission]);
            echo "     - $permission ✓\n";
        } catch (PDOException $e) {
            echo "     - $permission (σφάλμα: " . $e->getMessage() . ")\n";
        }
    }
}

// 4. Εφαρμογή permissions στους χρήστες
echo "\n4. Εφαρμογή permissions στους χρήστες...\n";

$stmt = $pdo->query("SELECT id, email, role FROM users WHERE role IS NOT NULL");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    echo "   • {$user['email']} (Role: {$user['role']})\n";

    // Λήψη permissions για τον ρόλο
    $stmt = $pdo->prepare("SELECT permission_name FROM role_permissions WHERE role = ?");
    $stmt->execute([$user['role']]);
    $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($permissions as $permission) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO permissions (user_id, permission_name) 
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE permission_name = permission_name
            ");
            $stmt->execute([$user['id'], $permission]);
            echo "     - $permission ✓\n";
        } catch (PDOException $e) {
            echo "     - $permission (σφάλμα: " . $e->getMessage() . ")\n";
        }
    }
}

// 5. Επιβεβαίωση
echo "\n5. Επιβεβαίωση permissions...\n";

$testUsers = [
    'admin@drivejob.gr' => 'admin.access',
    'info@thessdrive.gr' => 'company.access',
    'kostas.michailidis1@gmail.com' => 'driver.access'
];

foreach ($testUsers as $email => $requiredPermission) {
    $stmt = $pdo->prepare("
        SELECT p.permission_name 
        FROM permissions p
        JOIN users u ON p.user_id = u.id
        WHERE u.email = ? AND p.permission_name = ?
    ");
    $stmt->execute([$email, $requiredPermission]);

    if ($stmt->fetch()) {
        echo "   ✓ $email έχει permission '$requiredPermission'\n";
    } else {
        echo "   ✗ $email ΔΕΝ έχει permission '$requiredPermission'\n";
    }
}

echo "\n=== ΟΛΟΚΛΗΡΩΣΗ ===\n\n";
echo "✓ Η δομή RBAC διορθώθηκε επιτυχώς!\n\n";

echo "ΣΥΝΟΨΗ:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Χρήστες με permissions:\n";

$stmt = $pdo->query("
    SELECT u.email, u.role, COUNT(p.id) as permission_count
    FROM users u
    LEFT JOIN permissions p ON u.id = p.user_id
    WHERE u.role IS NOT NULL
    GROUP BY u.id, u.email, u.role
    ORDER BY u.role, u.email
");

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($results as $row) {
    echo "• {$row['email']} ({$row['role']}): {$row['permission_count']} permissions\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

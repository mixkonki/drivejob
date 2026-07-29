<?php

/**
 * Διόρθωση RBAC permissions για όλους τους χρήστες
 */

require_once __DIR__ . '/../../config/database.php';

echo "\n=== ΔΙΟΡΘΩΣΗ RBAC PERMISSIONS ===\n\n";

// Δημιουργία πίνακα permissions αν δεν υπάρχει
echo "1. Έλεγχος πίνακα permissions...\n";
$stmt = $pdo->query("SHOW TABLES LIKE 'permissions'");
if ($stmt->rowCount() == 0) {
    echo "   Δημιουργία πίνακα permissions...\n";
    $pdo->exec("
        CREATE TABLE permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            permission VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_permission (user_id, permission),
            INDEX idx_user_id (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "   ✓ Δημιουργήθηκε\n";
} else {
    echo "   ✓ Υπάρχει ήδη\n";
}

// Δημιουργία πίνακα role_permissions αν δεν υπάρχει
echo "\n2. Έλεγχος πίνακα role_permissions...\n";
$stmt = $pdo->query("SHOW TABLES LIKE 'role_permissions'");
if ($stmt->rowCount() == 0) {
    echo "   Δημιουργία πίνακα role_permissions...\n";
    $pdo->exec("
        CREATE TABLE role_permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role VARCHAR(50) NOT NULL,
            permission VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_role_permission (role, permission),
            INDEX idx_role (role)
        )
    ");
    echo "   ✓ Δημιουργήθηκε\n";
} else {
    echo "   ✓ Υπάρχει ήδη\n";
}

// Ορισμός permissions ανά ρόλο
echo "\n3. Ορισμός permissions ανά ρόλο...\n";

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
                INSERT INTO role_permissions (role, permission) 
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE permission = permission
            ");
            $stmt->execute([$role, $permission]);
            echo "     - $permission ✓\n";
        } catch (PDOException $e) {
            echo "     - $permission (υπάρχει ήδη)\n";
        }
    }
}

// Εφαρμογή permissions στους υπάρχοντες χρήστες
echo "\n4. Εφαρμογή permissions στους χρήστες...\n";

$stmt = $pdo->query("SELECT id, email, role FROM users WHERE role IS NOT NULL");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    echo "   • {$user['email']} (Role: {$user['role']})\n";

    // Λήψη permissions για τον ρόλο
    $stmt = $pdo->prepare("SELECT permission FROM role_permissions WHERE role = ?");
    $stmt->execute([$user['role']]);
    $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($permissions as $permission) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO permissions (user_id, permission) 
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE permission = permission
            ");
            $stmt->execute([$user['id'], $permission]);
            echo "     - $permission ✓\n";
        } catch (PDOException $e) {
            echo "     - $permission (σφάλμα: " . $e->getMessage() . ")\n";
        }
    }
}

// Επιβεβαίωση permissions
echo "\n5. Επιβεβαίωση permissions...\n";

$testUsers = [
    'admin@drivejob.gr' => 'admin.access',
    'info@thessdrive.gr' => 'company.access',
    'kostas.michailidis1@gmail.com' => 'driver.access'
];

foreach ($testUsers as $email => $requiredPermission) {
    $stmt = $pdo->prepare("
        SELECT p.permission 
        FROM permissions p
        JOIN users u ON p.user_id = u.id
        WHERE u.email = ? AND p.permission = ?
    ");
    $stmt->execute([$email, $requiredPermission]);

    if ($stmt->fetch()) {
        echo "   ✓ $email έχει permission '$requiredPermission'\n";
    } else {
        echo "   ✗ $email ΔΕΝ έχει permission '$requiredPermission'\n";
    }
}

echo "\n=== ΟΛΟΚΛΗΡΩΣΗ ===\n\n";
echo "✓ Τα RBAC permissions ρυθμίστηκαν επιτυχώς!\n\n";

echo "Μπορείτε τώρα να συνδεθείτε:\n";
echo "• Admin: http://localhost:8000/login.php → /admin/dashboard\n";
echo "• Company: http://localhost:8000/login.php → /companies/profile\n";
echo "• Driver: http://localhost:8000/login.php → /drivers/profile\n";

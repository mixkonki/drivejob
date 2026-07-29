<?php

/**
 * Duplicate Data Checker
 * Ελέγχει για duplicate data πριν την εφαρμογή UNIQUE constraints
 */

$pdo = require __DIR__ . '/../../config/database.php';

echo "=== DriveJob Duplicate Data Check ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

$hasDuplicates = false;

// Check drivers.email
echo "📧 Checking drivers.email...\n";
$stmt = $pdo->query("
    SELECT email, COUNT(*) as cnt 
    FROM drivers 
    WHERE email IS NOT NULL AND email != ''
    GROUP BY email 
    HAVING cnt > 1
");
$duplicates = $stmt->fetchAll();
if (!empty($duplicates)) {
    $hasDuplicates = true;
    echo "❌ Found " . count($duplicates) . " duplicate emails:\n";
    foreach ($duplicates as $dup) {
        echo "   - '{$dup['email']}': {$dup['cnt']} occurrences\n";
    }
} else {
    echo "✅ No duplicate emails found\n";
}
echo "\n";

// Check companies.email
echo "📧 Checking companies.email...\n";
$stmt = $pdo->query("
    SELECT email, COUNT(*) as cnt 
    FROM companies 
    WHERE email IS NOT NULL AND email != ''
    GROUP BY email 
    HAVING cnt > 1
");
$duplicates = $stmt->fetchAll();
if (!empty($duplicates)) {
    $hasDuplicates = true;
    echo "❌ Found " . count($duplicates) . " duplicate emails:\n";
    foreach ($duplicates as $dup) {
        echo "   - '{$dup['email']}': {$dup['cnt']} occurrences\n";
    }
} else {
    echo "✅ No duplicate emails found\n";
}
echo "\n";

// Check users.username
echo "👤 Checking users.username...\n";
$stmt = $pdo->query("
    SELECT username, COUNT(*) as cnt 
    FROM users 
    WHERE username IS NOT NULL AND username != ''
    GROUP BY username 
    HAVING cnt > 1
");
$duplicates = $stmt->fetchAll();
if (!empty($duplicates)) {
    $hasDuplicates = true;
    echo "❌ Found " . count($duplicates) . " duplicate usernames:\n";
    foreach ($duplicates as $dup) {
        echo "   - '{$dup['username']}': {$dup['cnt']} occurrences\n";
    }
} else {
    echo "✅ No duplicate usernames found\n";
}
echo "\n";

// Check drivers.license_number
echo "🪪 Checking drivers.license_number...\n";
$stmt = $pdo->query("
    SELECT license_number, COUNT(*) as cnt 
    FROM drivers 
    WHERE license_number IS NOT NULL AND license_number != ''
    GROUP BY license_number 
    HAVING cnt > 1
");
$duplicates = $stmt->fetchAll();
if (!empty($duplicates)) {
    $hasDuplicates = true;
    echo "❌ Found " . count($duplicates) . " duplicate license numbers:\n";
    foreach ($duplicates as $dup) {
        echo "   - '{$dup['license_number']}': {$dup['cnt']} occurrences\n";
    }
} else {
    echo "✅ No duplicate license numbers found\n";
}
echo "\n";

// Check companies.vat_number
echo "🏢 Checking companies.vat_number...\n";
$stmt = $pdo->query("
    SELECT vat_number, COUNT(*) as cnt 
    FROM companies 
    WHERE vat_number IS NOT NULL AND vat_number != ''
    GROUP BY vat_number 
    HAVING cnt > 1
");
$duplicates = $stmt->fetchAll();
if (!empty($duplicates)) {
    $hasDuplicates = true;
    echo "❌ Found " . count($duplicates) . " duplicate VAT numbers:\n";
    foreach ($duplicates as $dup) {
        echo "   - '{$dup['vat_number']}': {$dup['cnt']} occurrences\n";
    }
} else {
    echo "✅ No duplicate VAT numbers found\n";
}
echo "\n";

// Check roles.name
echo "🔐 Checking roles.name...\n";
$stmt = $pdo->query("
    SELECT name, COUNT(*) as cnt 
    FROM roles 
    WHERE name IS NOT NULL AND name != ''
    GROUP BY name 
    HAVING cnt > 1
");
$duplicates = $stmt->fetchAll();
if (!empty($duplicates)) {
    $hasDuplicates = true;
    echo "❌ Found " . count($duplicates) . " duplicate role names:\n";
    foreach ($duplicates as $dup) {
        echo "   - '{$dup['name']}': {$dup['cnt']} occurrences\n";
    }
} else {
    echo "✅ No duplicate role names found\n";
}
echo "\n";

// Check permissions.name
echo "🔑 Checking permissions.name...\n";
$stmt = $pdo->query("
    SELECT name, COUNT(*) as cnt 
    FROM permissions 
    WHERE name IS NOT NULL AND name != ''
    GROUP BY name 
    HAVING cnt > 1
");
$duplicates = $stmt->fetchAll();
if (!empty($duplicates)) {
    $hasDuplicates = true;
    echo "❌ Found " . count($duplicates) . " duplicate permission names:\n";
    foreach ($duplicates as $dup) {
        echo "   - '{$dup['name']}': {$dup['cnt']} occurrences\n";
    }
} else {
    echo "✅ No duplicate permission names found\n";
}
echo "\n";

// Check user_roles composite
echo "👥 Checking user_roles (user_id, role_id)...\n";
$stmt = $pdo->query("
    SELECT user_id, role_id, COUNT(*) as cnt 
    FROM user_roles 
    GROUP BY user_id, role_id 
    HAVING cnt > 1
");
$duplicates = $stmt->fetchAll();
if (!empty($duplicates)) {
    $hasDuplicates = true;
    echo "❌ Found " . count($duplicates) . " duplicate user-role assignments:\n";
    foreach ($duplicates as $dup) {
        echo "   - User {$dup['user_id']} + Role {$dup['role_id']}: {$dup['cnt']} occurrences\n";
    }
} else {
    echo "✅ No duplicate user-role assignments found\n";
}
echo "\n";

// Check role_permissions composite
echo "🔐 Checking role_permissions (role, permission_name)...\n";
$stmt = $pdo->query("
    SELECT role, permission_name, COUNT(*) as cnt 
    FROM role_permissions 
    GROUP BY role, permission_name 
    HAVING cnt > 1
");
$duplicates = $stmt->fetchAll();
if (!empty($duplicates)) {
    $hasDuplicates = true;
    echo "❌ Found " . count($duplicates) . " duplicate role-permission assignments:\n";
    foreach ($duplicates as $dup) {
        echo "   - Role '{$dup['role']}' + Permission '{$dup['permission_name']}': {$dup['cnt']} occurrences\n";
    }
} else {
    echo "✅ No duplicate role-permission assignments found\n";
}
echo "\n";

// Summary
echo str_repeat('=', 80) . "\n";
if ($hasDuplicates) {
    echo "🔴 DUPLICATES FOUND!\n";
    echo "Action Required: Clean up duplicates before applying UNIQUE constraints.\n";
    echo "See: database/migrations/sql/2025-12-07-cleanup-duplicates.sql\n";
} else {
    echo "✅ NO DUPLICATES FOUND!\n";
    echo "Safe to proceed with UNIQUE constraints.\n";
    echo "Next: Execute database/migrations/sql/2025-12-07-p0-critical-constraints.sql\n";
}
echo str_repeat('=', 80) . "\n";

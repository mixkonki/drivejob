<?php

/**
 * DevTool: Επαναφορά κωδικού admin (ΜΟΝΟ για τοπικό περιβάλλον)
 *
 * Χρήση:
 *   php devtools/reset-admin-password.php 'ΟΝέοςΚωδικός'
 *
 * - Βρίσκει τον χρήστη με role='admin'
 * - Εντοπίζει αυτόματα τη σωστή στήλη κωδικού (password_hash ή password)
 * - Ορίζει τον νέο κωδικό με bcrypt και ξεκλειδώνει τον λογαριασμό
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

$newPassword = $argv[1] ?? null;
if (!$newPassword || strlen($newPassword) < 8) {
    exit("Δώσε νέο κωδικό (τουλάχιστον 8 χαρακτήρες):\n  php devtools/reset-admin-password.php 'ΟΝέοςΚωδικός'\n");
}

$pdo = new PDO('mysql:host=127.0.0.1;dbname=drivejob;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Εντόπισε τη στήλη ρόλου (το σχήμα διαφέρει: role ή user_type)
$colsInfo = $pdo->query("SHOW COLUMNS FROM users")->fetchAll();
$colNames = array_column($colsInfo, 'Field');
$roleCol = in_array('role', $colNames, true) ? 'role'
         : (in_array('user_type', $colNames, true) ? 'user_type' : null);

if ($roleCol) {
    $admins = $pdo->query("SELECT * FROM users WHERE {$roleCol} IN ('admin', 'super_admin')")->fetchAll();
} else {
    $admins = [];
}

// Fallback: αναζήτηση μέσω πίνακα RBAC (user_roles/roles) αν δεν βρεθεί τίποτα
if (!$admins) {
    try {
        $admins = $pdo->query("
            SELECT u.* FROM users u
            JOIN user_roles ur ON ur.user_id = u.id
            JOIN roles r ON r.id = ur.role_id
            WHERE r.name IN ('admin', 'super_admin')
        ")->fetchAll();
    } catch (PDOException $e) { /* δεν υπάρχουν αυτοί οι πίνακες */ }
}

if (!$admins) {
    echo "❌ Δεν βρέθηκε admin. Στήλες πίνακα users: " . implode(', ', $colNames) . "\n";
    echo "Πρώτοι 10 χρήστες για αναγνώριση:\n";
    $idCol = in_array('email', $colNames, true) ? 'email' : $colNames[1];
    foreach ($pdo->query("SELECT * FROM users LIMIT 10") as $u) {
        $extra = $roleCol ? " [{$roleCol}: {$u[$roleCol]}]" : '';
        echo "  id {$u['id']}: {$u[$idCol]}{$extra}\n";
    }
    exit(1);
}

// Ποια στήλη κρατά τον κωδικό;
$columns = $colNames;
$passCol = in_array('password_hash', $columns, true) ? 'password_hash'
         : (in_array('password', $columns, true) ? 'password' : null);
if (!$passCol) {
    exit("❌ Δεν βρέθηκε στήλη κωδικού (password/password_hash). Στήλες: " . implode(', ', $columns) . "\n");
}

$hash = password_hash($newPassword, PASSWORD_BCRYPT);

foreach ($admins as $admin) {
    $sets = ["$passCol = :hash"];
    if (in_array('login_attempts', $columns, true)) $sets[] = "login_attempts = 0";
    if (in_array('locked_until', $columns, true))  $sets[] = "locked_until = NULL";
    if (in_array('is_active', $columns, true))     $sets[] = "is_active = 1";
    if (in_array('is_verified', $columns, true))   $sets[] = "is_verified = 1";

    $sql = "UPDATE users SET " . implode(', ', $sets) . " WHERE id = :id";
    $pdo->prepare($sql)->execute(['hash' => $hash, 'id' => $admin['id']]);

    $label = $admin['email'] ?? $admin['username'] ?? ('id ' . $admin['id']);
    echo "✅ Νέος κωδικός ορίστηκε για: {$label}\n";
}

echo "\nΣύνδεση: ", (defined('BASE_URL') ? BASE_URL : 'http://drivejob.test/'), "auth/login με το παραπάνω email και τον κωδικό που έδωσες.\n";
echo "⚠️ Το εργαλείο είναι μόνο για τοπική χρήση — ΜΗΝ ανέβει ποτέ σε production server.\n";

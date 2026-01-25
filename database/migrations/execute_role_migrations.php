<?php

// database/migrations/execute_role_migrations.php

// Απενεργοποίηση του exception handler για αυτό το script
define('DISABLE_EXCEPTION_HANDLER', true);

// Φόρτωση των migrations
require_once __DIR__ . '/create_roles_table.php';
require_once __DIR__ . '/create_permissions_table.php';
require_once __DIR__ . '/create_role_permissions_table.php';
require_once __DIR__ . '/add_role_id_to_users_tables.php';
require_once __DIR__ . '/create_user_roles_table.php';

// Λήψη της σύνδεσης με τη βάση δεδομένων
$pdo = require_once __DIR__ . '/../../config/database.php';

// Εκτέλεση των migrations
$migrations = [
    new CreateRolesTable(),
    new CreatePermissionsTable(),
    new CreateRolePermissionsTable(),
    new AddRoleIdToUsersTables(),
    new CreateUserRolesTable()
];

// Έλεγχος για παράμετρο down
$down = isset($argv[1]) && $argv[1] === 'down';

// Εκτέλεση των migrations
if ($down) {
    // Αντιστροφή της σειράς των migrations για το down
    $migrations = array_reverse($migrations);

    echo "Αναίρεση των migrations...\n";
    foreach ($migrations as $migration) {
        $className = get_class($migration);
        echo "Αναίρεση του migration $className... ";

        try {
            $result = $migration->down($pdo);
            echo $result ? "Επιτυχία" : "Αποτυχία";
        } catch (Exception $e) {
            echo "Σφάλμα: " . $e->getMessage();
        }

        echo "\n";
    }
} else {
    echo "Εκτέλεση των migrations...\n";
    foreach ($migrations as $migration) {
        $className = get_class($migration);
        echo "Εκτέλεση του migration $className... ";

        try {
            $result = $migration->up($pdo);
            echo $result ? "Επιτυχία" : "Αποτυχία";
        } catch (Exception $e) {
            echo "Σφάλμα: " . $e->getMessage();
        }

        echo "\n";
    }
}

echo "Ολοκλήρωση των migrations.\n";

// Φόρτωση του bootstrap στο τέλος για να αποφύγουμε προβλήματα με τον exception handler
require_once __DIR__ . '/../../src/bootstrap.php';

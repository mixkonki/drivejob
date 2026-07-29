<?php

// database/migrations/create_role_permissions_table.php

/**
 * Migration για τη δημιουργία του πίνακα role_permissions
 */
class CreateRolePermissionsTable
{
    /**
     * Εκτελεί το migration
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     * @return bool Αν το migration εκτελέστηκε με επιτυχία
     */
    public function up(PDO $pdo)
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS role_permissions (
                role_id INT NOT NULL,
                permission_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (role_id, permission_id),
                FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        // Εκτέλεση του SQL
        $result = $pdo->exec($sql);

        // Εισαγωγή των προεπιλεγμένων συσχετίσεων ρόλων-δικαιωμάτων
        $this->insertDefaultRolePermissions($pdo);

        return $result !== false;
    }

    /**
     * Αναιρεί το migration
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     * @return bool Αν το migration αναιρέθηκε με επιτυχία
     */
    public function down(PDO $pdo)
    {
        $sql = "DROP TABLE IF EXISTS role_permissions;";
        $result = $pdo->exec($sql);

        return $result !== false;
    }

    /**
     * Εισάγει τις προεπιλεγμένες συσχετίσεις ρόλων-δικαιωμάτων
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     * @return void
     */
    private function insertDefaultRolePermissions(PDO $pdo)
    {
        // Ανάκτηση των ρόλων
        $rolesStmt = $pdo->query("SELECT id, name FROM roles");
        $roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);
        $roleIds = [];
        foreach ($roles as $role) {
            $roleIds[$role['name']] = $role['id'];
        }

        // Ανάκτηση των δικαιωμάτων
        $permissionsStmt = $pdo->query("SELECT id, name FROM permissions");
        $permissions = $permissionsStmt->fetchAll(PDO::FETCH_ASSOC);
        $permissionIds = [];
        foreach ($permissions as $permission) {
            $permissionIds[$permission['name']] = $permission['id'];
        }

        // Ορισμός των δικαιωμάτων για κάθε ρόλο
        $rolePermissions = [
            // Διαχειριστής: όλα τα δικαιώματα
            'admin' => array_values($permissionIds),

            // Οδηγός: δικαιώματα σχετικά με τους οδηγούς και τις αιτήσεις
            'driver' => [
                $permissionIds['drivers.view'] ?? null,
                $permissionIds['job_listings.view'] ?? null,
                $permissionIds['job_applications.view'] ?? null,
                $permissionIds['job_applications.create'] ?? null,
                $permissionIds['job_applications.edit'] ?? null,
            ],

            // Επιχείρηση: δικαιώματα σχετικά με τις επιχειρήσεις, τις αγγελίες και τις αιτήσεις
            'company' => [
                $permissionIds['companies.view'] ?? null,
                $permissionIds['job_listings.view'] ?? null,
                $permissionIds['job_listings.create'] ?? null,
                $permissionIds['job_listings.edit'] ?? null,
                $permissionIds['job_listings.delete'] ?? null,
                $permissionIds['job_applications.view'] ?? null,
            ],

            // Διαχειριστής Στόλου: δικαιώματα σχετικά με τους οδηγούς και τις αγγελίες
            'fleet_manager' => [
                $permissionIds['drivers.view'] ?? null,
                $permissionIds['drivers.create'] ?? null,
                $permissionIds['drivers.edit'] ?? null,
                $permissionIds['job_listings.view'] ?? null,
                $permissionIds['job_applications.view'] ?? null,
            ]
        ];

        // Εισαγωγή των συσχετίσεων ρόλων-δικαιωμάτων
        $stmt = $pdo->prepare("
            INSERT INTO role_permissions (role_id, permission_id)
            VALUES (:role_id, :permission_id)
            ON DUPLICATE KEY UPDATE role_id = VALUES(role_id)
        ");

        foreach ($rolePermissions as $roleName => $permissionIdList) {
            $roleId = $roleIds[$roleName] ?? null;
            if ($roleId === null) {
                continue;
            }

            foreach ($permissionIdList as $permissionId) {
                if ($permissionId === null) {
                    continue;
                }

                $stmt->execute([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId
                ]);
            }
        }
    }
}

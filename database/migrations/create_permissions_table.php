<?php

// database/migrations/create_permissions_table.php

/**
 * Migration για τη δημιουργία του πίνακα permissions
 */
class CreatePermissionsTable
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
            CREATE TABLE IF NOT EXISTS permissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                display_name VARCHAR(100) NOT NULL,
                description TEXT,
                category VARCHAR(50) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        // Εκτέλεση του SQL
        $result = $pdo->exec($sql);

        // Εισαγωγή των προεπιλεγμένων δικαιωμάτων
        $this->insertDefaultPermissions($pdo);

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
        $sql = "DROP TABLE IF EXISTS permissions;";
        $result = $pdo->exec($sql);

        return $result !== false;
    }

    /**
     * Εισάγει τα προεπιλεγμένα δικαιώματα
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     * @return void
     */
    private function insertDefaultPermissions(PDO $pdo)
    {
        $permissions = [
            // Δικαιώματα διαχείρισης χρηστών
            [
                'name' => 'users.view',
                'display_name' => 'Προβολή χρηστών',
                'description' => 'Προβολή λίστας χρηστών',
                'category' => 'users'
            ],
            [
                'name' => 'users.create',
                'display_name' => 'Δημιουργία χρηστών',
                'description' => 'Δημιουργία νέων χρηστών',
                'category' => 'users'
            ],
            [
                'name' => 'users.edit',
                'display_name' => 'Επεξεργασία χρηστών',
                'description' => 'Επεξεργασία υπαρχόντων χρηστών',
                'category' => 'users'
            ],
            [
                'name' => 'users.delete',
                'display_name' => 'Διαγραφή χρηστών',
                'description' => 'Διαγραφή χρηστών',
                'category' => 'users'
            ],

            // Δικαιώματα διαχείρισης οδηγών
            [
                'name' => 'drivers.view',
                'display_name' => 'Προβολή οδηγών',
                'description' => 'Προβολή λίστας οδηγών',
                'category' => 'drivers'
            ],
            [
                'name' => 'drivers.create',
                'display_name' => 'Δημιουργία οδηγών',
                'description' => 'Δημιουργία νέων οδηγών',
                'category' => 'drivers'
            ],
            [
                'name' => 'drivers.edit',
                'display_name' => 'Επεξεργασία οδηγών',
                'description' => 'Επεξεργασία υπαρχόντων οδηγών',
                'category' => 'drivers'
            ],
            [
                'name' => 'drivers.delete',
                'display_name' => 'Διαγραφή οδηγών',
                'description' => 'Διαγραφή οδηγών',
                'category' => 'drivers'
            ],

            // Δικαιώματα διαχείρισης επιχειρήσεων
            [
                'name' => 'companies.view',
                'display_name' => 'Προβολή επιχειρήσεων',
                'description' => 'Προβολή λίστας επιχειρήσεων',
                'category' => 'companies'
            ],
            [
                'name' => 'companies.create',
                'display_name' => 'Δημιουργία επιχειρήσεων',
                'description' => 'Δημιουργία νέων επιχειρήσεων',
                'category' => 'companies'
            ],
            [
                'name' => 'companies.edit',
                'display_name' => 'Επεξεργασία επιχειρήσεων',
                'description' => 'Επεξεργασία υπαρχόντων επιχειρήσεων',
                'category' => 'companies'
            ],
            [
                'name' => 'companies.delete',
                'display_name' => 'Διαγραφή επιχειρήσεων',
                'description' => 'Διαγραφή επιχειρήσεων',
                'category' => 'companies'
            ],

            // Δικαιώματα διαχείρισης αγγελιών
            [
                'name' => 'job_listings.view',
                'display_name' => 'Προβολή αγγελιών',
                'description' => 'Προβολή λίστας αγγελιών',
                'category' => 'job_listings'
            ],
            [
                'name' => 'job_listings.create',
                'display_name' => 'Δημιουργία αγγελιών',
                'description' => 'Δημιουργία νέων αγγελιών',
                'category' => 'job_listings'
            ],
            [
                'name' => 'job_listings.edit',
                'display_name' => 'Επεξεργασία αγγελιών',
                'description' => 'Επεξεργασία υπαρχόντων αγγελιών',
                'category' => 'job_listings'
            ],
            [
                'name' => 'job_listings.delete',
                'display_name' => 'Διαγραφή αγγελιών',
                'description' => 'Διαγραφή αγγελιών',
                'category' => 'job_listings'
            ],

            // Δικαιώματα διαχείρισης αιτήσεων
            [
                'name' => 'job_applications.view',
                'display_name' => 'Προβολή αιτήσεων',
                'description' => 'Προβολή λίστας αιτήσεων',
                'category' => 'job_applications'
            ],
            [
                'name' => 'job_applications.create',
                'display_name' => 'Δημιουργία αιτήσεων',
                'description' => 'Δημιουργία νέων αιτήσεων',
                'category' => 'job_applications'
            ],
            [
                'name' => 'job_applications.edit',
                'display_name' => 'Επεξεργασία αιτήσεων',
                'description' => 'Επεξεργασία υπαρχόντων αιτήσεων',
                'category' => 'job_applications'
            ],
            [
                'name' => 'job_applications.delete',
                'display_name' => 'Διαγραφή αιτήσεων',
                'description' => 'Διαγραφή αιτήσεων',
                'category' => 'job_applications'
            ],

            // Δικαιώματα διαχείρισης ρόλων
            [
                'name' => 'roles.view',
                'display_name' => 'Προβολή ρόλων',
                'description' => 'Προβολή λίστας ρόλων',
                'category' => 'roles'
            ],
            [
                'name' => 'roles.create',
                'display_name' => 'Δημιουργία ρόλων',
                'description' => 'Δημιουργία νέων ρόλων',
                'category' => 'roles'
            ],
            [
                'name' => 'roles.edit',
                'display_name' => 'Επεξεργασία ρόλων',
                'description' => 'Επεξεργασία υπαρχόντων ρόλων',
                'category' => 'roles'
            ],
            [
                'name' => 'roles.delete',
                'display_name' => 'Διαγραφή ρόλων',
                'description' => 'Διαγραφή ρόλων',
                'category' => 'roles'
            ],

            // Δικαιώματα διαχείρισης δικαιωμάτων
            [
                'name' => 'permissions.view',
                'display_name' => 'Προβολή δικαιωμάτων',
                'description' => 'Προβολή λίστας δικαιωμάτων',
                'category' => 'permissions'
            ],
            [
                'name' => 'permissions.assign',
                'display_name' => 'Ανάθεση δικαιωμάτων',
                'description' => 'Ανάθεση δικαιωμάτων σε ρόλους',
                'category' => 'permissions'
            ],

            // Δικαιώματα διαχείρισης συστήματος
            [
                'name' => 'system.settings',
                'display_name' => 'Ρυθμίσεις συστήματος',
                'description' => 'Διαχείριση ρυθμίσεων συστήματος',
                'category' => 'system'
            ],
            [
                'name' => 'system.logs',
                'display_name' => 'Προβολή logs',
                'description' => 'Προβολή logs συστήματος',
                'category' => 'system'
            ]
        ];

        $stmt = $pdo->prepare("
            INSERT INTO permissions (name, display_name, description, category)
            VALUES (:name, :display_name, :description, :category)
            ON DUPLICATE KEY UPDATE
            display_name = VALUES(display_name),
            description = VALUES(description),
            category = VALUES(category)
        ");

        foreach ($permissions as $permission) {
            $stmt->execute($permission);
        }
    }
}

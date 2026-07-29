<?php

// database/migrations/create_roles_table.php

/**
 * Migration για τη δημιουργία του πίνακα roles
 */
class CreateRolesTable
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
            CREATE TABLE IF NOT EXISTS roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                display_name VARCHAR(100) NOT NULL,
                description TEXT,
                is_system BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";

        // Εκτέλεση του SQL
        $result = $pdo->exec($sql);

        // Εισαγωγή των προεπιλεγμένων ρόλων
        $this->insertDefaultRoles($pdo);

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
        $sql = "DROP TABLE IF EXISTS roles;";
        $result = $pdo->exec($sql);

        return $result !== false;
    }

    /**
     * Εισάγει τους προεπιλεγμένους ρόλους
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     * @return void
     */
    private function insertDefaultRoles(PDO $pdo)
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Διαχειριστής',
                'description' => 'Διαχειριστής του συστήματος με πλήρη δικαιώματα',
                'is_system' => true
            ],
            [
                'name' => 'driver',
                'display_name' => 'Οδηγός',
                'description' => 'Οδηγός που αναζητά εργασία',
                'is_system' => true
            ],
            [
                'name' => 'company',
                'display_name' => 'Επιχείρηση',
                'description' => 'Επιχείρηση που αναζητά οδηγούς',
                'is_system' => true
            ],
            [
                'name' => 'fleet_manager',
                'display_name' => 'Διαχειριστής Στόλου',
                'description' => 'Διαχειριστής στόλου οχημάτων',
                'is_system' => false
            ]
        ];

        $stmt = $pdo->prepare("
            INSERT INTO roles (name, display_name, description, is_system)
            VALUES (:name, :display_name, :description, :is_system)
            ON DUPLICATE KEY UPDATE
            display_name = VALUES(display_name),
            description = VALUES(description),
            is_system = VALUES(is_system)
        ");

        foreach ($roles as $role) {
            $stmt->execute($role);
        }
    }
}

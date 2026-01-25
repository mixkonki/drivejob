<?php

// database/migrations/create_user_roles_table.php

/**
 * Migration για τη δημιουργία του πίνακα user_roles
 */
class CreateUserRolesTable
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
            CREATE TABLE IF NOT EXISTS user_roles (
                user_id INT NOT NULL,
                role_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (user_id, role_id),
                INDEX (user_id),
                INDEX (role_id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ";

        // Εκτέλεση του SQL
        $result = $pdo->exec($sql);

        // Μεταφορά των υπαρχόντων ρόλων από τη στήλη role_id στον πίνακα user_roles
        $this->migrateExistingRoles($pdo);

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
        $sql = "DROP TABLE IF EXISTS user_roles;";
        $result = $pdo->exec($sql);

        return $result !== false;
    }

    /**
     * Μεταφέρει τους υπάρχοντες ρόλους από τη στήλη role_id στον πίνακα user_roles
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     * @return void
     */
    private function migrateExistingRoles(PDO $pdo)
    {
        // Μεταφορά των ρόλων από τον πίνακα users
        $sql1 = "
            INSERT INTO user_roles (user_id, role_id)
            SELECT id, role_id FROM users
            WHERE role_id IS NOT NULL
            ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)
        ";
        $this->executeSafely($pdo, $sql1);

        // Μεταφορά των ρόλων από τον πίνακα drivers
        $sql2 = "
            INSERT INTO user_roles (user_id, role_id)
            SELECT id, role_id FROM drivers
            WHERE role_id IS NOT NULL
            ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)
        ";
        $this->executeSafely($pdo, $sql2);

        // Μεταφορά των ρόλων από τον πίνακα companies
        $sql3 = "
            INSERT INTO user_roles (user_id, role_id)
            SELECT id, role_id FROM companies
            WHERE role_id IS NOT NULL
            ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)
        ";
        $this->executeSafely($pdo, $sql3);
    }

    /**
     * Εκτελεί ένα SQL ερώτημα με ασφάλεια
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     * @param string $sql Το SQL ερώτημα
     * @return bool Αν το ερώτημα εκτελέστηκε με επιτυχία
     */
    private function executeSafely(PDO $pdo, $sql)
    {
        try {
            $result = $pdo->exec($sql);
            return $result !== false;
        } catch (PDOException $e) {
            // Αγνόηση σφαλμάτων που σχετίζονται με ήδη υπάρχουσες στήλες ή περιορισμούς
            if (
                strpos($e->getMessage(), 'Duplicate column') !== false ||
                strpos($e->getMessage(), 'Duplicate key') !== false ||
                strpos($e->getMessage(), 'Column not found') !== false ||
                strpos($e->getMessage(), 'Key not found') !== false
            ) {
                return true;
            }
            throw $e;
        }
    }
}

<?php

// database/migrations/add_role_id_to_users_tables.php

/**
 * Migration για την προσθήκη της στήλης role_id στους πίνακες users, drivers και companies
 */
class AddRoleIdToUsersTables
{
    /**
     * Εκτελεί το migration
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     * @return bool Αν το migration εκτελέστηκε με επιτυχία
     */
    public function up(PDO $pdo)
    {
        // Προσθήκη της στήλης role_id στον πίνακα users
        $sql1 = "
            ALTER TABLE users
            ADD COLUMN role_id INT NULL,
            ADD INDEX (role_id);
        ";

        // Προσθήκη της στήλης role_id στον πίνακα drivers
        $sql2 = "
            ALTER TABLE drivers
            ADD COLUMN role_id INT NULL,
            ADD INDEX (role_id);
        ";

        // Προσθήκη της στήλης role_id στον πίνακα companies
        $sql3 = "
            ALTER TABLE companies
            ADD COLUMN role_id INT NULL,
            ADD INDEX (role_id);
        ";

        // Εκτέλεση των SQL
        $result1 = $this->executeSafely($pdo, $sql1);
        $result2 = $this->executeSafely($pdo, $sql2);
        $result3 = $this->executeSafely($pdo, $sql3);

        // Ενημέρωση των υπαρχόντων εγγραφών
        $this->updateExistingRecords($pdo);

        return $result1 && $result2 && $result3;
    }

    /**
     * Αναιρεί το migration
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     * @return bool Αν το migration αναιρέθηκε με επιτυχία
     */
    public function down(PDO $pdo)
    {
        // Αφαίρεση του index και της στήλης role_id από τον πίνακα users
        $sql1 = "
            ALTER TABLE users
            DROP INDEX role_id,
            DROP COLUMN role_id;
        ";

        // Αφαίρεση του index και της στήλης role_id από τον πίνακα drivers
        $sql2 = "
            ALTER TABLE drivers
            DROP INDEX role_id,
            DROP COLUMN role_id;
        ";

        // Αφαίρεση του index και της στήλης role_id από τον πίνακα companies
        $sql3 = "
            ALTER TABLE companies
            DROP INDEX role_id,
            DROP COLUMN role_id;
        ";

        // Εκτέλεση των SQL
        $result1 = $this->executeSafely($pdo, $sql1);
        $result2 = $this->executeSafely($pdo, $sql2);
        $result3 = $this->executeSafely($pdo, $sql3);

        return $result1 && $result2 && $result3;
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

    /**
     * Ενημερώνει τις υπάρχουσες εγγραφές με τους σωστούς ρόλους
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     * @return void
     */
    private function updateExistingRecords(PDO $pdo)
    {
        // Ανάκτηση των ρόλων
        $rolesStmt = $pdo->query("SELECT id, name FROM roles");
        $roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);
        $roleIds = [];
        foreach ($roles as $role) {
            $roleIds[$role['name']] = $role['id'];
        }

        // Ενημέρωση των χρηστών
        if (isset($roleIds['admin'])) {
            $sql = "UPDATE users SET role_id = :role_id WHERE role = 'admin'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['role_id' => $roleIds['admin']]);
        }

        // Ενημέρωση των οδηγών
        if (isset($roleIds['driver'])) {
            $sql = "UPDATE drivers SET role_id = :role_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['role_id' => $roleIds['driver']]);
        }

        // Ενημέρωση των επιχειρήσεων
        if (isset($roleIds['company'])) {
            $sql = "UPDATE companies SET role_id = :role_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['role_id' => $roleIds['company']]);
        }
    }
}

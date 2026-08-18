<?php

namespace Drivejob\Models;

use PDO;
use PDOException;
use Drivejob\Core\Logger;

/**
 * Βασική κλάση για όλα τα μοντέλα
 * 
 * Παρέχει κοινές λειτουργίες CRUD και βοηθητικές μεθόδους
 * για όλα τα επιμέρους μοντέλα της εφαρμογής
 */
class BaseModel
{
    /**
     * Η σύνδεση PDO με τη βάση δεδομένων
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * Το όνομα του πίνακα στη βάση δεδομένων
     *
     * @var string
     */
    protected $table;

    /**
     * Constructor
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     * @param string|null $table Το όνομα του πίνακα (προαιρετικό)
     */
    public function __construct(PDO $pdo, ?string $table = null)
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    /**
     * Εκτελεί μια προετοιμασμένη SQL εντολή
     *
     * @param string $sql Το SQL ερώτημα
     * @param array $params Οι παράμετροι για το ερώτημα
     * @return \PDOStatement|false Το αντικείμενο PDOStatement ή false σε περίπτωση αποτυχίας
     */
    protected function execute(string $sql, array $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            Logger::error('Database error in ' . get_class($this) . '::execute: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Εισάγει μια νέα εγγραφή στον πίνακα
     *
     * @param array $data Τα δεδομένα προς εισαγωγή
     * @return int|bool Το ID της νέας εγγραφής ή false σε περίπτωση αποτυχίας
     */
    public function insert(array $data)
    {
        try {
            $columns = array_keys($data);
            $placeholders = array_fill(0, count($columns), '?');

            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES (%s)",
                $this->table,
                implode(', ', array_map(function ($col) {
                    return "`$col`";
                }, $columns)),
                implode(', ', $placeholders)
            );

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute(array_values($data));

            if ($result) {
                return $this->pdo->lastInsertId();
            }

            return false;
        } catch (PDOException $e) {
            Logger::error('Database error in ' . get_class($this) . '::insert: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημερώνει μια υπάρχουσα εγγραφή στον πίνακα
     *
     * @param array $data Τα δεδομένα προς ενημέρωση
     * @param array $where Οι συνθήκες για την ενημέρωση
     * @return bool Επιτυχία ή αποτυχία της ενημέρωσης
     */
    public function update(array $data, array $where)
    {
        try {
            if (empty($data) || empty($where)) {
                return false;
            }

            // Δημιουργία των SET μερών
            $setParts = [];
            $params = [];

            foreach ($data as $column => $value) {
                if ($value === null) {
                    $setParts[] = "`$column` = NULL";
                } else {
                    $setParts[] = "`$column` = ?";
                    $params[] = $value;
                }
            }

            // Δημιουργία των WHERE μερών
            $whereParts = [];
            foreach ($where as $column => $value) {
                if ($value === null) {
                    $whereParts[] = "`$column` IS NULL";
                } else {
                    $whereParts[] = "`$column` = ?";
                    $params[] = $value;
                }
            }

            $sql = sprintf(
                "UPDATE %s SET %s WHERE %s",
                $this->table,
                implode(', ', $setParts),
                implode(' AND ', $whereParts)
            );

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            Logger::error('Database error in ' . get_class($this) . '::update: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Διαγράφει εγγραφές από τον πίνακα
     *
     * @param array $where Οι συνθήκες για τη διαγραφή
     * @return bool Επιτυχία ή αποτυχία της διαγραφής
     */
    public function delete(array $where)
    {
        try {
            if (empty($where)) {
                return false;
            }

            // Δημιουργία των WHERE μερών
            $whereParts = [];
            $params = [];

            foreach ($where as $column => $value) {
                if ($value === null) {
                    $whereParts[] = "`$column` IS NULL";
                } else {
                    $whereParts[] = "`$column` = ?";
                    $params[] = $value;
                }
            }

            $sql = sprintf(
                "DELETE FROM %s WHERE %s",
                $this->table,
                implode(' AND ', $whereParts)
            );

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            Logger::error('Database error in ' . get_class($this) . '::delete: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Επιλέγει εγγραφές από τον πίνακα
     *
     * @param array $where Οι συνθήκες για την επιλογή
     * @param array|string $columns Οι στήλες προς επιλογή
     * @param array $orderBy Οι στήλες και η κατεύθυνση ταξινόμησης
     * @param int|null $limit Ο μέγιστος αριθμός αποτελεσμάτων
     * @param int|null $offset Η μετατόπιση των αποτελεσμάτων
     * @return array Οι εγγραφές που επιλέχθηκαν
     */
    public function select(array $where = [], $columns = '*', array $orderBy = [], ?int $limit = null, ?int $offset = null)
    {
        try {
            // Προετοιμασία των στηλών προς επιλογή
            if (is_array($columns)) {
                $columnsStr = implode(', ', array_map(function ($col) {
                    return "`$col`";
                }, $columns));
            } else {
                $columnsStr = $columns;
            }

            // Προετοιμασία των συνθηκών WHERE
            $whereParts = [];
            $params = [];

            foreach ($where as $column => $value) {
                if ($value === null) {
                    $whereParts[] = "`$column` IS NULL";
                } else {
                    $whereParts[] = "`$column` = ?";
                    $params[] = $value;
                }
            }

            // Προετοιμασία του SQL ερωτήματος
            $sql = sprintf("SELECT %s FROM %s", $columnsStr, $this->table);

            if (!empty($whereParts)) {
                $sql .= " WHERE " . implode(' AND ', $whereParts);
            }

            // Προσθήκη ORDER BY
            if (!empty($orderBy)) {
                $orderParts = [];
                foreach ($orderBy as $column => $direction) {
                    $orderParts[] = "`$column` $direction";
                }
                $sql .= " ORDER BY " . implode(', ', $orderParts);
            }

            // Προσθήκη LIMIT και OFFSET
            if ($limit !== null) {
                $sql .= " LIMIT $limit";
                if ($offset !== null) {
                    $sql .= " OFFSET $offset";
                }
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('Database error in ' . get_class($this) . '::select: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Επιλέγει μια εγγραφή από τον πίνακα
     *
     * @param array $where Οι συνθήκες για την επιλογή
     * @param array|string $columns Οι στήλες προς επιλογή
     * @return array|null Η εγγραφή που επιλέχθηκε ή null αν δεν βρέθηκε
     */
    public function selectOne(array $where = [], $columns = '*')
    {
        $results = $this->select($where, $columns, [], 1);
        return !empty($results) ? $results[0] : null;
    }

    /**
     * Εκτελεί ένα προσαρμοσμένο SQL ερώτημα
     *
     * @param string $sql Το SQL ερώτημα
     * @param array $params Οι παράμετροι για το ερώτημα
     * @param int $fetchMode Ο τρόπος λήψης των αποτελεσμάτων
     * @return array|bool Τα αποτελέσματα του ερωτήματος ή false σε περίπτωση αποτυχίας
     */
    public function query(string $sql, array $params = [], int $fetchMode = PDO::FETCH_ASSOC)
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);

            if (!$result) {
                return false;
            }

            if (strpos(strtoupper($sql), 'SELECT') === 0) {
                return $stmt->fetchAll($fetchMode);
            }

            return true;
        } catch (PDOException $e) {
            Logger::error('Database error in ' . get_class($this) . '::query: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Εκτελεί ένα SQL ερώτημα και επιστρέφει μια μόνο γραμμή
     *
     * @param string $sql Το SQL ερώτημα
     * @param array $params Οι παράμετροι για το ερώτημα
     * @param int $fetchMode Ο τρόπος λήψης των αποτελεσμάτων
     * @return array|bool Τα αποτελέσματα του ερωτήματος ή false σε περίπτωση αποτυχίας
     */
    public function queryOne(string $sql, array $params = [], int $fetchMode = PDO::FETCH_ASSOC)
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);

            if (!$result) {
                return false;
            }

            return $stmt->fetch($fetchMode);
        } catch (PDOException $e) {
            Logger::error('Database error in ' . get_class($this) . '::queryOne: ' . $e->getMessage());
            return false;
        }
    }

    // Πακέτο 5.2: τα checkColumnExists/checkTableExists αφαιρέθηκαν —
    // το σχήμα της βάσης είναι σταθερό και διαχειρίζεται ΜΟΝΟ από τα
    // migrations στο database/migrations/, όχι από runtime ελέγχους.

    /**
     * Ξεκινά μια συναλλαγή
     * 
     * @return bool Επιτυχία ή αποτυχία
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Επιβεβαιώνει μια συναλλαγή
     * 
     * @return bool Επιτυχία ή αποτυχία
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * Ακυρώνει μια συναλλαγή
     * 
     * @return bool Επιτυχία ή αποτυχία
     */
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }
}

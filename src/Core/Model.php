<?php

namespace Drivejob\Core;

/**
 * Βασική κλάση Model
 * 
 * Παρέχει βασικές λειτουργίες για όλα τα models
 */
class Model
{
    /**
     * Σύνδεση με τη βάση δεδομένων
     *
     * @var \PDO
     */
    protected $db;

    /**
     * Όνομα του πίνακα
     *
     * @var string
     */
    protected $table;

    /**
     * Πρωτεύον κλειδί του πίνακα
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Constructor
     *
     * @param \PDO $db Σύνδεση με τη βάση δεδομένων
     */
    public function __construct($db = null)
    {
        if ($db) {
            $this->db = $db;
        } else {
            // Αν δεν παρέχεται σύνδεση, δημιουργούμε μια νέα
            $this->db = Database::getInstance()->getConnection();
        }
    }

    /**
     * Εκτελεί ένα SQL ερώτημα
     *
     * @param string $sql Το SQL ερώτημα
     * @param array $params Παράμετροι για το ερώτημα
     * @return \PDOStatement
     */
    protected function query($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Βρίσκει όλες τις εγγραφές από τον πίνακα
     *
     * @param string $orderBy Πεδίο ταξινόμησης
     * @param string $order Κατεύθυνση ταξινόμησης (ASC ή DESC)
     * @return array
     */
    public function findAll($orderBy = null, $order = 'ASC')
    {
        $sql = "SELECT * FROM {$this->table}";

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy} {$order}";
        }

        $stmt = $this->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Βρίσκει μια εγγραφή με βάση το πρωτεύον κλειδί
     *
     * @param mixed $id Τιμή του πρωτεύοντος κλειδιού
     * @return array|false
     */
    public function findById($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->query($sql, ['id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Βρίσκει εγγραφές με βάση συγκεκριμένα κριτήρια
     *
     * @param array $criteria Κριτήρια αναζήτησης (πεδίο => τιμή)
     * @param string $orderBy Πεδίο ταξινόμησης
     * @param string $order Κατεύθυνση ταξινόμησης (ASC ή DESC)
     * @return array
     */
    public function findBy($criteria, $orderBy = null, $order = 'ASC')
    {
        $sql = "SELECT * FROM {$this->table} WHERE ";
        $conditions = [];
        $params = [];

        foreach ($criteria as $key => $value) {
            $conditions[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }

        $sql .= implode(' AND ', $conditions);

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy} {$order}";
        }

        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Εισάγει μια νέα εγγραφή στον πίνακα
     *
     * @param array $data Δεδομένα για εισαγωγή (πεδίο => τιμή)
     * @return int|false Το ID της νέας εγγραφής ή false σε περίπτωση αποτυχίας
     */
    public function insert($data)
    {
        $fields = array_keys($data);
        $placeholders = array_map(function ($field) {
            return ":{$field}";
        }, $fields);

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->query($sql, $data);

        if ($stmt) {
            return $this->db->lastInsertId();
        }

        return false;
    }

    /**
     * Ενημερώνει μια εγγραφή με βάση το πρωτεύον κλειδί
     *
     * @param mixed $id Τιμή του πρωτεύοντος κλειδιού
     * @param array $data Δεδομένα για ενημέρωση (πεδίο => τιμή)
     * @return boolean
     */
    public function update($id, $data)
    {
        $fields = array_keys($data);
        $setClause = array_map(function ($field) {
            return "{$field} = :{$field}";
        }, $fields);

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " 
                WHERE {$this->primaryKey} = :id";

        $data['id'] = $id;

        $stmt = $this->query($sql, $data);
        return $stmt->rowCount() > 0;
    }

    /**
     * Διαγράφει μια εγγραφή με βάση το πρωτεύον κλειδί
     *
     * @param mixed $id Τιμή του πρωτεύοντος κλειδιού
     * @return boolean
     */
    public function delete($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->query($sql, ['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Μετράει τις εγγραφές με βάση συγκεκριμένα κριτήρια
     *
     * @param array $criteria Κριτήρια αναζήτησης (πεδίο => τιμή)
     * @return int
     */
    public function count($criteria = [])
    {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        $params = [];

        if (!empty($criteria)) {
            $sql .= " WHERE ";
            $conditions = [];

            foreach ($criteria as $key => $value) {
                $conditions[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }

            $sql .= implode(' AND ', $conditions);
        }

        $stmt = $this->query($sql, $params);
        return (int) $stmt->fetchColumn();
    }
}

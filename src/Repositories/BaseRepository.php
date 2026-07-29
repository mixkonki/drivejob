<?php

namespace Drivejob\Repositories;

use PDO;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Βασική κλάση για όλα τα repositories
 */
abstract class BaseRepository implements RepositoryInterface
{
    /**
     * @var PDO Η σύνδεση με τη βάση δεδομένων
     */
    protected $pdo;

    /**
     * @var string Το όνομα του πίνακα
     */
    protected $table;

    /**
     * @var string Το πρωτεύον κλειδί του πίνακα
     */
    protected $primaryKey = 'id';

    /**
     * @var array Τα πεδία που μπορούν να ενημερωθούν
     */
    protected $fillable = [];

    /**
     * @var array Τα πεδία που δεν μπορούν να ενημερωθούν
     */
    protected $guarded = [];

    /**
     * Constructor
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        if (empty($this->table)) {
            // Αυτόματος ορισμός του ονόματος του πίνακα από το όνομα της κλάσης
            $className = (new \ReflectionClass($this))->getShortName();
            $className = str_replace('Repository', '', $className);
            $this->table = strtolower($className) . 's';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findAll(array $criteria = [], array $orderBy = [], $limit = null, $offset = null)
    {
        try {
            $query = "SELECT * FROM {$this->table}";
            $params = [];

            // Προσθήκη των κριτηρίων
            if (!empty($criteria)) {
                $query .= " WHERE ";
                $conditions = [];

                foreach ($criteria as $field => $value) {
                    if ($value === null) {
                        $conditions[] = "$field IS NULL";
                    } else {
                        $conditions[] = "$field = :$field";
                        $params[$field] = $value;
                    }
                }

                $query .= implode(" AND ", $conditions);
            }

            // Προσθήκη της ταξινόμησης
            if (!empty($orderBy)) {
                $query .= " ORDER BY ";
                $orders = [];

                foreach ($orderBy as $field => $direction) {
                    $orders[] = "$field $direction";
                }

                $query .= implode(", ", $orders);
            }

            // Προσθήκη του limit και offset
            if ($limit !== null) {
                $query .= " LIMIT :limit";
                $params['limit'] = $limit;

                if ($offset !== null) {
                    $query .= " OFFSET :offset";
                    $params['offset'] = $offset;
                }
            }

            $stmt = $this->pdo->prepare($query);

            // Δέσιμο των παραμέτρων
            foreach ($params as $key => $value) {
                if ($key === 'limit' || $key === 'offset') {
                    $stmt->bindValue(":$key", $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(":$key", $value);
                }
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, $params ?? []);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findOne(array $criteria = [])
    {
        $results = $this->findAll($criteria, [], 1);
        return !empty($results) ? $results[0] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        return $this->findOne([$this->primaryKey => $id]);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data)
    {
        try {
            // Φιλτράρισμα των δεδομένων
            $data = $this->filterData($data);

            if (empty($data)) {
                throw new \InvalidArgumentException("Δεν υπάρχουν έγκυρα δεδομένα για εισαγωγή");
            }

            $fields = array_keys($data);
            $placeholders = array_map(function ($field) {
                return ":$field";
            }, $fields);

            $query = "INSERT INTO {$this->table} (" . implode(", ", $fields) . ") VALUES (" . implode(", ", $placeholders) . ")";
            $stmt = $this->pdo->prepare($query);

            foreach ($data as $field => $value) {
                $stmt->bindValue(":$field", $value);
            }

            $stmt->execute();
            return $this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, $data ?? []);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update($id, array $data)
    {
        try {
            // Φιλτράρισμα των δεδομένων
            $data = $this->filterData($data);

            if (empty($data)) {
                throw new \InvalidArgumentException("Δεν υπάρχουν έγκυρα δεδομένα για ενημέρωση");
            }

            $fields = array_map(function ($field) {
                return "$field = :$field";
            }, array_keys($data));

            $query = "UPDATE {$this->table} SET " . implode(", ", $fields) . " WHERE {$this->primaryKey} = :id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(":id", $id);

            foreach ($data as $field => $value) {
                $stmt->bindValue(":$field", $value);
            }

            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, array_merge($data ?? [], ['id' => $id]));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        try {
            $query = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(":id", $id);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['id' => $id]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count(array $criteria = [])
    {
        try {
            $query = "SELECT COUNT(*) FROM {$this->table}";
            $params = [];

            // Προσθήκη των κριτηρίων
            if (!empty($criteria)) {
                $query .= " WHERE ";
                $conditions = [];

                foreach ($criteria as $field => $value) {
                    if ($value === null) {
                        $conditions[] = "$field IS NULL";
                    } else {
                        $conditions[] = "$field = :$field";
                        $params[$field] = $value;
                    }
                }

                $query .= implode(" AND ", $conditions);
            }

            $stmt = $this->pdo->prepare($query);

            // Δέσιμο των παραμέτρων
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }

            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, $params ?? []);
        }
    }

    /**
     * Φιλτράρει τα δεδομένα με βάση τα fillable και guarded πεδία
     *
     * @param array $data Τα δεδομένα προς φιλτράρισμα
     * @return array Τα φιλτραρισμένα δεδομένα
     */
    protected function filterData(array $data)
    {
        // Αν υπάρχουν fillable πεδία, κράτα μόνο αυτά
        if (!empty($this->fillable)) {
            return array_intersect_key($data, array_flip($this->fillable));
        }

        // Αν υπάρχουν guarded πεδία, αφαίρεσέ τα
        if (!empty($this->guarded)) {
            return array_diff_key($data, array_flip($this->guarded));
        }

        // Αν δεν υπάρχουν ούτε fillable ούτε guarded πεδία, επέστρεψε όλα τα δεδομένα
        return $data;
    }

    /**
     * Εκτελεί ένα προσαρμοσμένο SQL ερώτημα
     *
     * @param string $query Το SQL ερώτημα
     * @param array $params Οι παράμετροι για το ερώτημα
     * @param bool $fetchAll Αν θα επιστραφούν όλες οι εγγραφές ή μόνο η πρώτη
     * @return array|mixed Τα αποτελέσματα του ερωτήματος
     */
    protected function query($query, array $params = [], $fetchAll = true)
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);

            if ($fetchAll) {
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query, $params);
        }
    }

    /**
     * Εκτελεί ένα SQL ερώτημα και επιστρέφει μόνο την πρώτη εγγραφή
     *
     * @param string $query Το SQL ερώτημα
     * @param array $params Οι παράμετροι για το ερώτημα
     * @return mixed Η πρώτη εγγραφή ή false αν δεν βρέθηκε
     */
    protected function queryOne($query, array $params = [])
    {
        return $this->query($query, $params, false);
    }

    /**
     * Εκτελεί ένα SQL ερώτημα και επιστρέφει μόνο την πρώτη στήλη της πρώτης εγγραφής
     *
     * @param string $query Το SQL ερώτημα
     * @param array $params Οι παράμετροι για το ερώτημα
     * @return mixed Η πρώτη στήλη της πρώτης εγγραφής ή false αν δεν βρέθηκε
     */
    protected function queryScalar($query, array $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query, $params);
        }
    }

    /**
     * Εκτελεί ένα SQL ερώτημα και επιστρέφει τον αριθμό των επηρεαζόμενων εγγραφών
     *
     * @param string $query Το SQL ερώτημα
     * @param array $params Οι παράμετροι για το ερώτημα
     * @return int Ο αριθμός των επηρεαζόμενων εγγραφών
     */
    protected function execute($query, array $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query, $params);
        }
    }

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

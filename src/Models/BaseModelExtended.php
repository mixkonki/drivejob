<?php

namespace Drivejob\Models;

use PDO;
use PDOException;
use Drivejob\Core\Logger;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Επεκτεταμένη βασική κλάση για όλα τα μοντέλα
 * 
 * Επεκτείνει το BaseModel με επιπλέον λειτουργίες CRUD και βοηθητικές μεθόδους
 * για όλα τα επιμέρους μοντέλα της εφαρμογής
 */
class BaseModelExtended extends BaseModel
{
    /**
     * Τα πεδία που μπορούν να συμπληρωθούν
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * Τα πεδία που δεν μπορούν να συμπληρωθούν
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Τα πεδία που μπορούν να χρησιμοποιηθούν για αναζήτηση
     *
     * @var array
     */
    protected $searchable = [];

    /**
     * Τα πεδία που μπορούν να χρησιμοποιηθούν για φιλτράρισμα
     *
     * @var array
     */
    protected $filterable = [];

    /**
     * Τα πεδία που μπορούν να χρησιμοποιηθούν για ταξινόμηση
     *
     * @var array
     */
    protected $sortable = [];

    /**
     * Οι σχέσεις με άλλα μοντέλα
     *
     * @var array
     */
    protected $relations = [];

    /**
     * Constructor
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     * @param string|null $table Το όνομα του πίνακα (προαιρετικό)
     */
    public function __construct(PDO $pdo, ?string $table = null)
    {
        parent::__construct($pdo, $table);
    }

    /**
     * Επιλέγει εγγραφές από τον πίνακα με παραμέτρους σελιδοποίησης
     *
     * @param array $where Οι συνθήκες για την επιλογή
     * @param array|string $columns Οι στήλες προς επιλογή
     * @param array $orderBy Οι στήλες και η κατεύθυνση ταξινόμησης
     * @param int $page Ο αριθμός της σελίδας
     * @param int $perPage Ο αριθμός των εγγραφών ανά σελίδα
     * @return array Οι εγγραφές που επιλέχθηκαν και πληροφορίες σελιδοποίησης
     */
    public function paginate(array $where = [], $columns = '*', array $orderBy = [], int $page = 1, int $perPage = 10)
    {
        try {
            // Υπολογισμός του offset
            $offset = ($page - 1) * $perPage;

            // Εκτέλεση του ερωτήματος για τα αποτελέσματα
            $results = $this->select($where, $columns, $orderBy, $perPage, $offset);

            // Εκτέλεση του ερωτήματος για τον συνολικό αριθμό εγγραφών
            $total = $this->count($where);

            // Υπολογισμός του συνολικού αριθμού σελίδων
            $totalPages = ceil($total / $perPage);

            return [
                'results' => $results,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => $totalPages,
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $total)
                ]
            ];
        } catch (PDOException $e) {
            Logger::error('Database error in ' . get_class($this) . '::paginate: ' . $e->getMessage());
            throw new DatabaseException('Σφάλμα κατά την ανάκτηση δεδομένων με σελιδοποίηση', [
                'message' => $e->getMessage(),
                'where' => $where,
                'page' => $page,
                'per_page' => $perPage
            ]);
        }
    }

    /**
     * Επιλέγει εγγραφές από τον πίνακα με παραμέτρους αναζήτησης
     *
     * @param string $search Το κείμενο αναζήτησης
     * @param array $searchFields Τα πεδία στα οποία θα γίνει η αναζήτηση
     * @param array $where Οι συνθήκες για την επιλογή
     * @param array|string $columns Οι στήλες προς επιλογή
     * @param array $orderBy Οι στήλες και η κατεύθυνση ταξινόμησης
     * @param int|null $limit Ο μέγιστος αριθμός αποτελεσμάτων
     * @param int|null $offset Η μετατόπιση των αποτελεσμάτων
     * @return array Οι εγγραφές που επιλέχθηκαν
     */
    public function search(string $search, array $searchFields = [], array $where = [], $columns = '*', array $orderBy = [], ?int $limit = null, ?int $offset = null)
    {
        try {
            // Αν δεν έχουν οριστεί πεδία αναζήτησης, χρησιμοποίησε τα προκαθορισμένα
            if (empty($searchFields)) {
                $searchFields = $this->searchable;
            }

            // Αν δεν υπάρχουν πεδία αναζήτησης, επέστρεψε κανονικά αποτελέσματα
            if (empty($searchFields)) {
                return $this->select($where, $columns, $orderBy, $limit, $offset);
            }

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

            // Προετοιμασία των συνθηκών αναζήτησης
            $searchParts = [];
            foreach ($searchFields as $field) {
                $searchParts[] = "`$field` LIKE ?";
                $params[] = "%$search%";
            }

            // Προετοιμασία του SQL ερωτήματος
            $sql = sprintf("SELECT %s FROM %s", $columnsStr, $this->table);

            if (!empty($whereParts) || !empty($searchParts)) {
                $sql .= " WHERE ";
                if (!empty($whereParts)) {
                    $sql .= "(" . implode(' AND ', $whereParts) . ")";
                    if (!empty($searchParts)) {
                        $sql .= " AND ";
                    }
                }
                if (!empty($searchParts)) {
                    $sql .= "(" . implode(' OR ', $searchParts) . ")";
                }
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
            Logger::error('Database error in ' . get_class($this) . '::search: ' . $e->getMessage());
            throw new DatabaseException('Σφάλμα κατά την αναζήτηση δεδομένων', [
                'message' => $e->getMessage(),
                'search' => $search,
                'search_fields' => $searchFields,
                'where' => $where
            ]);
        }
    }

    /**
     * Επιλέγει εγγραφές από τον πίνακα με παραμέτρους αναζήτησης και σελιδοποίηση
     *
     * @param string $search Το κείμενο αναζήτησης
     * @param array $searchFields Τα πεδία στα οποία θα γίνει η αναζήτηση
     * @param array $where Οι συνθήκες για την επιλογή
     * @param array|string $columns Οι στήλες προς επιλογή
     * @param array $orderBy Οι στήλες και η κατεύθυνση ταξινόμησης
     * @param int $page Ο αριθμός της σελίδας
     * @param int $perPage Ο αριθμός των εγγραφών ανά σελίδα
     * @return array Οι εγγραφές που επιλέχθηκαν και πληροφορίες σελιδοποίησης
     */
    public function searchPaginate(string $search, array $searchFields = [], array $where = [], $columns = '*', array $orderBy = [], int $page = 1, int $perPage = 10)
    {
        try {
            // Υπολογισμός του offset
            $offset = ($page - 1) * $perPage;

            // Εκτέλεση του ερωτήματος για τα αποτελέσματα
            $results = $this->search($search, $searchFields, $where, $columns, $orderBy, $perPage, $offset);

            // Αν δεν έχουν οριστεί πεδία αναζήτησης, χρησιμοποίησε τα προκαθορισμένα
            if (empty($searchFields)) {
                $searchFields = $this->searchable;
            }

            // Αν δεν υπάρχουν πεδία αναζήτησης, χρησιμοποίησε την κανονική μέθοδο καταμέτρησης
            if (empty($searchFields)) {
                $total = $this->count($where);
            } else {
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

                // Προετοιμασία των συνθηκών αναζήτησης
                $searchParts = [];
                foreach ($searchFields as $field) {
                    $searchParts[] = "`$field` LIKE ?";
                    $params[] = "%$search%";
                }

                // Προετοιμασία του SQL ερωτήματος
                $sql = sprintf("SELECT COUNT(*) as total FROM %s", $this->table);

                if (!empty($whereParts) || !empty($searchParts)) {
                    $sql .= " WHERE ";
                    if (!empty($whereParts)) {
                        $sql .= "(" . implode(' AND ', $whereParts) . ")";
                        if (!empty($searchParts)) {
                            $sql .= " AND ";
                        }
                    }
                    if (!empty($searchParts)) {
                        $sql .= "(" . implode(' OR ', $searchParts) . ")";
                    }
                }

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $total = $result['total'];
            }

            // Υπολογισμός του συνολικού αριθμού σελίδων
            $totalPages = ceil($total / $perPage);

            return [
                'results' => $results,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => $totalPages,
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $total)
                ]
            ];
        } catch (PDOException $e) {
            Logger::error('Database error in ' . get_class($this) . '::searchPaginate: ' . $e->getMessage());
            throw new DatabaseException('Σφάλμα κατά την αναζήτηση δεδομένων με σελιδοποίηση', [
                'message' => $e->getMessage(),
                'search' => $search,
                'search_fields' => $searchFields,
                'where' => $where,
                'page' => $page,
                'per_page' => $perPage
            ]);
        }
    }

    /**
     * Ελέγχει αν υπάρχει μια εγγραφή στον πίνακα
     *
     * @param array $where Οι συνθήκες για τον έλεγχο
     * @return bool Αν υπάρχει η εγγραφή
     */
    public function exists(array $where)
    {
        try {
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
            $sql = sprintf("SELECT EXISTS(SELECT 1 FROM %s WHERE %s) as `exists`", $this->table, implode(' AND ', $whereParts));

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (bool) $result['exists'];
        } catch (PDOException $e) {
            Logger::error('Database error in ' . get_class($this) . '::exists: ' . $e->getMessage());
            throw new DatabaseException('Σφάλμα κατά τον έλεγχο ύπαρξης εγγραφής', [
                'message' => $e->getMessage(),
                'where' => $where
            ]);
        }
    }

    /**
     * Καταμετρά τις εγγραφές στον πίνακα
     *
     * @param array $where Οι συνθήκες για την καταμέτρηση
     * @return int Ο αριθμός των εγγραφών
     */
    public function count(array $where = [])
    {
        try {
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
            $sql = sprintf("SELECT COUNT(*) as total FROM %s", $this->table);

            if (!empty($whereParts)) {
                $sql .= " WHERE " . implode(' AND ', $whereParts);
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) $result['total'];
        } catch (PDOException $e) {
            Logger::error('Database error in ' . get_class($this) . '::count: ' . $e->getMessage());
            throw new DatabaseException('Σφάλμα κατά την καταμέτρηση εγγραφών', [
                'message' => $e->getMessage(),
                'where' => $where
            ]);
        }
    }

    /**
     * Εισάγει πολλαπλές εγγραφές στον πίνακα
     *
     * @param array $data Τα δεδομένα προς εισαγωγή
     * @return bool Επιτυχία ή αποτυχία της εισαγωγής
     */
    public function insertMany(array $data)
    {
        try {
            if (empty($data)) {
                return false;
            }

            // Έλεγχος αν όλες οι εγγραφές έχουν τα ίδια κλειδιά
            $keys = array_keys($data[0]);
            foreach ($data as $row) {
                if (array_keys($row) !== $keys) {
                    throw new \InvalidArgumentException('Όλες οι εγγραφές πρέπει να έχουν τα ίδια κλειδιά');
                }
            }

            // Προετοιμασία του SQL ερωτήματος
            $columns = implode(', ', array_map(function ($col) {
                return "`$col`";
            }, $keys));

            $placeholders = [];
            $values = [];

            foreach ($data as $row) {
                $rowPlaceholders = [];
                foreach ($row as $value) {
                    $rowPlaceholders[] = '?';
                    $values[] = $value;
                }
                $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
            }

            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES %s",
                $this->table,
                $columns,
                implode(', ', $placeholders)
            );

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            Logger::error('Database error in ' . get_class($this) . '::insertMany: ' . $e->getMessage());
            throw new DatabaseException('Σφάλμα κατά την εισαγωγή πολλαπλών εγγραφών', [
                'message' => $e->getMessage(),
                'data' => $data
            ]);
        }
    }

    /**
     * Ενημερώνει πολλαπλές εγγραφές στον πίνακα
     *
     * @param array $data Τα δεδομένα προς ενημέρωση
     * @param string $keyColumn Η στήλη που χρησιμοποιείται ως κλειδί
     * @return bool Επιτυχία ή αποτυχία της ενημέρωσης
     */
    public function updateMany(array $data, string $keyColumn)
    {
        try {
            if (empty($data)) {
                return false;
            }

            // Έλεγχος αν όλες οι εγγραφές έχουν τα ίδια κλειδιά
            $keys = array_keys($data[0]);
            foreach ($data as $row) {
                if (array_keys($row) !== $keys) {
                    throw new \InvalidArgumentException('Όλες οι εγγραφές πρέπει να έχουν τα ίδια κλειδιά');
                }
                if (!isset($row[$keyColumn])) {
                    throw new \InvalidArgumentException('Όλες οι εγγραφές πρέπει να έχουν το κλειδί ' . $keyColumn);
                }
            }

            // Έναρξη συναλλαγής
            $this->pdo->beginTransaction();

            try {
                foreach ($data as $row) {
                    $id = $row[$keyColumn];
                    unset($row[$keyColumn]);

                    $setParts = [];
                    $params = [];

                    foreach ($row as $column => $value) {
                        if ($value === null) {
                            $setParts[] = "`$column` = NULL";
                        } else {
                            $setParts[] = "`$column` = ?";
                            $params[] = $value;
                        }
                    }

                    $sql = sprintf(
                        "UPDATE %s SET %s WHERE `%s` = ?",
                        $this->table,
                        implode(', ', $setParts),
                        $keyColumn
                    );

                    $params[] = $id;

                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute($params);
                }

                // Επιβεβαίωση συναλλαγής
                $this->pdo->commit();
                return true;
            } catch (PDOException $e) {
                // Ακύρωση συναλλαγής
                $this->pdo->rollBack();
                throw $e;
            }
        } catch (PDOException $e) {
            Logger::error('Database error in ' . get_class($this) . '::updateMany: ' . $e->getMessage());
            throw new DatabaseException('Σφάλμα κατά την ενημέρωση πολλαπλών εγγραφών', [
                'message' => $e->getMessage(),
                'data' => $data,
                'key_column' => $keyColumn
            ]);
        }
    }

    /**
     * Διαγράφει πολλαπλές εγγραφές από τον πίνακα
     *
     * @param array $ids Τα IDs των εγγραφών προς διαγραφή
     * @param string $keyColumn Η στήλη που χρησιμοποιείται ως κλειδί
     * @return bool Επιτυχία ή αποτυχία της διαγραφής
     */
    public function deleteMany(array $ids, string $keyColumn = 'id')
    {
        try {
            if (empty($ids)) {
                return false;
            }

            // Προετοιμασία του SQL ερωτήματος
            $placeholders = implode(', ', array_fill(0, count($ids), '?'));
            $sql = sprintf("DELETE FROM %s WHERE `%s` IN (%s)", $this->table, $keyColumn, $placeholders);

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($ids);
        } catch (PDOException $e) {
            Logger::error('Database error in ' . get_class($this) . '::deleteMany: ' . $e->getMessage());
            throw new DatabaseException('Σφάλμα κατά τη διαγραφή πολλαπλών εγγραφών', [
                'message' => $e->getMessage(),
                'ids' => $ids,
                'key_column' => $keyColumn
            ]);
        }
    }

    /**
     * Επιλέγει εγγραφές από τον πίνακα με σύνδεση με άλλους πίνακες
     *
     * @param array $joins Οι συνδέσεις με άλλους πίνακες
     * @param array $where Οι συνθήκες για την επιλογή
     * @param array|string $columns Οι στήλες προς επιλογή
     * @param array $orderBy Οι στήλες και η κατεύθυνση ταξινόμησης
     * @param int|null $limit Ο μέγιστος αριθμός αποτελεσμάτων
     * @param int|null $offset Η μετατόπιση των αποτελεσμάτων
     * @return array Οι εγγραφές που επιλέχθηκαν
     */
    public function selectWithJoin(array $joins, array $where = [], $columns = '*', array $orderBy = [], ?int $limit = null, ?int $offset = null)
    {
        try {
            // Προετοιμασία των στηλών προς επιλογή
            if (is_array($columns)) {
                $columnsStr = implode(', ', array_map(function ($col) {
                    return $col;
                }, $columns));
            } else {
                $columnsStr = $columns;
            }

            // Προετοιμασία των συνθηκών WHERE
            $whereParts = [];
            $params = [];

            foreach ($where as $column => $value) {
                if ($value === null) {
                    $whereParts[] = "$column IS NULL";
                } else {
                    $whereParts[] = "$column = ?";
                    $params[] = $value;
                }
            }

            // Προετοιμασία των συνδέσεων
            $joinParts = [];
            foreach ($joins as $join) {
                $joinType = $join['type'] ?? 'INNER';
                $joinTable = $join['table'];
                $joinCondition = $join['on'];
                $joinParts[] = "$joinType JOIN $joinTable ON $joinCondition";
            }

            // Προετοιμασία του SQL ερωτήματος
            $sql = sprintf("SELECT %s FROM %s %s", $columnsStr, $this->table, implode(' ', $joinParts));

            if (!empty($whereParts)) {
                $sql .= " WHERE " . implode(' AND ', $whereParts);
            }

            // Προσθήκη ORDER BY
            if (!empty($orderBy)) {
                $orderParts = [];
                foreach ($orderBy as $column => $direction) {
                    $orderParts[] = "$column $direction";
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
            Logger::error('Database error in ' . get_class($this) . '::selectWithJoin: ' . $e->getMessage());
            throw new DatabaseException('Σφάλμα κατά την επιλογή εγγραφών με σύνδεση', [
                'message' => $e->getMessage(),
                'joins' => $joins,
                'where' => $where
            ]);
        }
    }
}

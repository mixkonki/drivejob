<?php

namespace Drivejob\Repositories;

use PDO;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Repository για τους χρήστες
 */
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * @var string Το όνομα του πίνακα
     */
    protected $table = 'users';

    /**
     * @var array Τα πεδία που μπορούν να ενημερωθούν
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'first_name',
        'last_name',
        'phone',
        'address',
        'city',
        'country',
        'postal_code',
        'is_verified',
        'is_active',
        'verification_code',
        'verification_expires',
        'reset_code',
        'reset_expires',
        'last_login',
        'created_at',
        'updated_at'
    ];

    /**
     * @var array Τα πεδία που δεν μπορούν να ενημερωθούν
     */
    protected $guarded = [
        'id',
        'created_at'
    ];

    /**
     * {@inheritdoc}
     */
    public function findByEmail($email)
    {
        return $this->findOne(['email' => $email]);
    }

    /**
     * {@inheritdoc}
     */
    public function findByUsername($username)
    {
        return $this->findOne(['username' => $username]);
    }

    /**
     * {@inheritdoc}
     */
    public function updateProfile($id, array $data)
    {
        return $this->update($id, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function updatePassword($id, $password)
    {
        return $this->update($id, [
            'password' => $password,
            'reset_code' => null,
            'reset_expires' => null
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function updateLastLogin($id)
    {
        try {
            $query = "UPDATE {$this->table} SET last_login = NOW() WHERE id = :id";
            return $this->execute($query, ['id' => $id]) > 0;
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['id' => $id]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function activate($id)
    {
        return $this->update($id, ['is_active' => 1]);
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate($id)
    {
        return $this->update($id, ['is_active' => 0]);
    }

    /**
     * {@inheritdoc}
     */
    public function verify($id)
    {
        return $this->update($id, [
            'is_verified' => 1,
            'verification_code' => null,
            'verification_expires' => null
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function findByVerificationCode($code)
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE verification_code = :code AND verification_expires > NOW()";
            return $this->queryOne($query, ['code' => $code]);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['code' => $code]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findByResetCode($code)
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE reset_code = :code AND reset_expires > NOW()";
            return $this->queryOne($query, ['code' => $code]);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['code' => $code]);
        }
    }

    /**
     * Αναζητά χρήστες με βάση διάφορα κριτήρια
     *
     * @param array $criteria Τα κριτήρια αναζήτησης
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function searchUsers(array $criteria = [], $page = 1, $limit = 10)
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE 1=1";
            $params = [];
            $conditions = [];

            // Προσθήκη των κριτηρίων
            if (isset($criteria['username']) && $criteria['username']) {
                $conditions[] = "username LIKE :username";
                $params['username'] = '%' . $criteria['username'] . '%';
            }

            if (isset($criteria['email']) && $criteria['email']) {
                $conditions[] = "email LIKE :email";
                $params['email'] = '%' . $criteria['email'] . '%';
            }

            if (isset($criteria['name']) && $criteria['name']) {
                $conditions[] = "(first_name LIKE :name OR last_name LIKE :name)";
                $params['name'] = '%' . $criteria['name'] . '%';
            }

            if (isset($criteria['is_verified']) && $criteria['is_verified'] !== null) {
                $conditions[] = "is_verified = :is_verified";
                $params['is_verified'] = $criteria['is_verified'];
            }

            if (isset($criteria['is_active']) && $criteria['is_active'] !== null) {
                $conditions[] = "is_active = :is_active";
                $params['is_active'] = $criteria['is_active'];
            }

            // Προσθήκη των συνθηκών στο ερώτημα
            if (!empty($conditions)) {
                $query .= " AND " . implode(" AND ", $conditions);
            }

            // Προσθήκη της ταξινόμησης
            if (isset($criteria['sort_by']) && $criteria['sort_by']) {
                $sortField = $criteria['sort_by'];
                $sortDirection = isset($criteria['sort_direction']) && strtoupper($criteria['sort_direction']) === 'DESC' ? 'DESC' : 'ASC';

                // Έλεγχος για έγκυρο πεδίο ταξινόμησης
                $validSortFields = ['username', 'email', 'first_name', 'last_name', 'created_at', 'last_login'];
                if (in_array($sortField, $validSortFields)) {
                    $query .= " ORDER BY $sortField $sortDirection";
                } else {
                    $query .= " ORDER BY created_at DESC";
                }
            } else {
                $query .= " ORDER BY created_at DESC";
            }

            // Μέτρηση συνολικών αποτελεσμάτων
            $countQuery = "SELECT COUNT(*) FROM ({$query}) as count_table";
            $totalResults = $this->queryScalar($countQuery, $params);

            // Προσθήκη του LIMIT και OFFSET
            $offset = ($page - 1) * $limit;
            $query .= " LIMIT :limit OFFSET :offset";
            $params['limit'] = $limit;
            $params['offset'] = $offset;

            // Εκτέλεση του ερωτήματος
            $results = $this->query($query, $params);

            return [
                'results' => $results,
                'pagination' => [
                    'total' => $totalResults,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($totalResults / $limit)
                ]
            ];
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, $params ?? []);
        }
    }

    /**
     * Επιστρέφει τους πρόσφατα εγγεγραμμένους χρήστες
     *
     * @param int $limit Ο αριθμός αποτελεσμάτων
     * @return array Οι πρόσφατα εγγεγραμμένοι χρήστες
     */
    public function getRecentlyRegisteredUsers($limit = 10)
    {
        try {
            $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT :limit";
            return $this->query($query, ['limit' => $limit]);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['limit' => $limit]);
        }
    }

    /**
     * Επιστρέφει τους πρόσφατα ενεργούς χρήστες
     *
     * @param int $limit Ο αριθμός αποτελεσμάτων
     * @return array Οι πρόσφατα ενεργοί χρήστες
     */
    public function getRecentlyActiveUsers($limit = 10)
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE last_login IS NOT NULL ORDER BY last_login DESC LIMIT :limit";
            return $this->query($query, ['limit' => $limit]);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['limit' => $limit]);
        }
    }

    /**
     * Επιστρέφει τους μη επαληθευμένους χρήστες
     *
     * @param int $limit Ο αριθμός αποτελεσμάτων
     * @return array Οι μη επαληθευμένοι χρήστες
     */
    public function getUnverifiedUsers($limit = 10)
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE is_verified = 0 ORDER BY created_at DESC LIMIT :limit";
            return $this->query($query, ['limit' => $limit]);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['limit' => $limit]);
        }
    }

    /**
     * Επιστρέφει τους ανενεργούς χρήστες
     *
     * @param int $limit Ο αριθμός αποτελεσμάτων
     * @return array Οι ανενεργοί χρήστες
     */
    public function getInactiveUsers($limit = 10)
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE is_active = 0 ORDER BY created_at DESC LIMIT :limit";
            return $this->query($query, ['limit' => $limit]);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['limit' => $limit]);
        }
    }
}

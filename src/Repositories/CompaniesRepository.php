<?php

namespace Drivejob\Repositories;

use PDO;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Repository για τις εταιρείες
 */
class CompaniesRepository extends BaseRepository
{
    /**
     * @var string Το όνομα του πίνακα
     */
    protected $table = 'companies';

    /**
     * @var array Τα πεδία που μπορούν να ενημερωθούν
     */
    protected $fillable = [
        'email',
        'password',
        'company_name',
        'contact_person',
        'phone',
        'address',
        'city',
        'country',
        'postal_code',
        'website',
        'logo',
        'description',
        'industry',
        'company_size',
        'founded_year',
        'is_verified',
        'verification_token',
        'reset_token',
        'reset_token_expires_at',
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
     * Επιστρέφει μια εταιρεία με βάση το email
     *
     * @param string $email Το email της εταιρείας
     * @return array|null Η εταιρεία ή null αν δεν βρέθηκε
     */
    public function getCompanyByEmail($email)
    {
        return $this->findOne(['email' => $email]);
    }

    /**
     * Επιστρέφει μια εταιρεία με βάση το verification token
     *
     * @param string $token Το verification token
     * @return array|null Η εταιρεία ή null αν δεν βρέθηκε
     */
    public function getCompanyByVerificationToken($token)
    {
        return $this->findOne(['verification_token' => $token]);
    }

    /**
     * Επιστρέφει μια εταιρεία με βάση το reset token
     *
     * @param string $token Το reset token
     * @return array|null Η εταιρεία ή null αν δεν βρέθηκε
     */
    public function getCompanyByResetToken($token)
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE reset_token = :token AND reset_token_expires_at > NOW()";
            return $this->queryOne($query, ['token' => $token]);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['token' => $token]);
        }
    }

    /**
     * Επαληθεύει μια εταιρεία
     *
     * @param int $id Το ID της εταιρείας
     * @return bool Επιτυχία ή αποτυχία
     */
    public function verifyCompany($id)
    {
        return $this->update($id, [
            'is_verified' => 1,
            'verification_token' => null
        ]);
    }

    /**
     * Ενημερώνει τον κωδικό πρόσβασης μιας εταιρείας
     *
     * @param int $id Το ID της εταιρείας
     * @param string $password Ο νέος κωδικός πρόσβασης (κρυπτογραφημένος)
     * @return bool Επιτυχία ή αποτυχία
     */
    public function updatePassword($id, $password)
    {
        return $this->update($id, [
            'password' => $password,
            'reset_token' => null,
            'reset_token_expires_at' => null
        ]);
    }

    /**
     * Ενημερώνει την τελευταία σύνδεση μιας εταιρείας
     *
     * @param int $id Το ID της εταιρείας
     * @return bool Επιτυχία ή αποτυχία
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
     * Αναζητά εταιρείες με βάση διάφορα κριτήρια
     *
     * @param array $criteria Τα κριτήρια αναζήτησης
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function searchCompanies(array $criteria = [], $page = 1, $limit = 10)
    {
        try {
            $query = "SELECT c.*, 
                      COALESCE(COUNT(DISTINCT j.id), 0) as job_count
                      FROM {$this->table} c
                      LEFT JOIN job_listings j ON c.id = j.company_id
                      WHERE c.is_verified = 1";
            $params = [];
            $conditions = [];

            // Προσθήκη των κριτηρίων
            if (isset($criteria['name']) && $criteria['name']) {
                $conditions[] = "c.company_name LIKE :name";
                $params['name'] = '%' . $criteria['name'] . '%';
            }

            if (isset($criteria['location']) && $criteria['location']) {
                $conditions[] = "(c.city LIKE :location OR c.country LIKE :location)";
                $params['location'] = '%' . $criteria['location'] . '%';
            }

            if (isset($criteria['industry']) && $criteria['industry']) {
                $conditions[] = "c.industry = :industry";
                $params['industry'] = $criteria['industry'];
            }

            // Προσθήκη των συνθηκών στο ερώτημα
            if (!empty($conditions)) {
                $query .= " AND " . implode(" AND ", $conditions);
            }

            // Προσθήκη του GROUP BY
            $query .= " GROUP BY c.id";

            // Προσθήκη της ταξινόμησης
            if (isset($criteria['sort_by']) && $criteria['sort_by']) {
                $sortField = $criteria['sort_by'];
                $sortDirection = isset($criteria['sort_direction']) && strtoupper($criteria['sort_direction']) === 'DESC' ? 'DESC' : 'ASC';

                // Έλεγχος για έγκυρο πεδίο ταξινόμησης
                $validSortFields = ['company_name', 'city', 'country', 'job_count', 'last_login'];
                if (in_array($sortField, $validSortFields)) {
                    $query .= " ORDER BY $sortField $sortDirection";
                } else {
                    $query .= " ORDER BY c.last_login DESC";
                }
            } else {
                $query .= " ORDER BY c.last_login DESC";
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
     * Επιστρέφει τις κορυφαίες εταιρείες με βάση τον αριθμό αγγελιών
     *
     * @param int $limit Ο αριθμός αποτελεσμάτων
     * @return array Οι κορυφαίες εταιρείες
     */
    public function getTopCompanies($limit = 10)
    {
        try {
            $query = "SELECT c.*, 
                      COUNT(DISTINCT j.id) as job_count
                      FROM {$this->table} c
                      LEFT JOIN job_listings j ON c.id = j.company_id
                      WHERE c.is_verified = 1
                      GROUP BY c.id
                      HAVING job_count > 0
                      ORDER BY job_count DESC
                      LIMIT :limit";

            return $this->query($query, ['limit' => $limit]);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['limit' => $limit]);
        }
    }

    /**
     * Επιστρέφει τις πρόσφατα ενεργές εταιρείες
     *
     * @param int $limit Ο αριθμός αποτελεσμάτων
     * @return array Οι πρόσφατα ενεργές εταιρείες
     */
    public function getRecentlyActiveCompanies($limit = 10)
    {
        try {
            $query = "SELECT c.*, 
                      COUNT(DISTINCT j.id) as job_count
                      FROM {$this->table} c
                      LEFT JOIN job_listings j ON c.id = j.company_id
                      WHERE c.is_verified = 1
                      GROUP BY c.id
                      ORDER BY c.last_login DESC
                      LIMIT :limit";

            return $this->query($query, ['limit' => $limit]);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['limit' => $limit]);
        }
    }
}

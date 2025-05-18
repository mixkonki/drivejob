<?php

namespace Drivejob\Repositories;

use PDO;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Repository για τους οδηγούς
 */
class DriversRepository extends BaseRepository implements DriversRepositoryInterface
{
    /**
     * @var string Το όνομα του πίνακα
     */
    protected $table = 'drivers';

    /**
     * @var array Τα πεδία που μπορούν να ενημερωθούν
     */
    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'phone',
        'address',
        'city',
        'country',
        'postal_code',
        'date_of_birth',
        'profile_image',
        'resume',
        'is_verified',
        'is_available',
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
     * Βρίσκει έναν οδηγό με βάση το email
     * 
     * @param string $email Το email του οδηγού
     * @return array|null Τα δεδομένα του οδηγού ή null αν δεν βρέθηκε
     */
    public function findByEmail($email)
    {
        return $this->findOne(['email' => $email]);
    }

    /**
     * Επιστρέφει έναν οδηγό με βάση το email
     *
     * @param string $email Το email του οδηγού
     * @return array|null Ο οδηγός ή null αν δεν βρέθηκε
     * @deprecated Χρησιμοποιήστε τη μέθοδο findByEmail αντί για αυτή
     */
    public function getDriverByEmail($email)
    {
        return $this->findByEmail($email);
    }

    /**
     * Επιστρέφει έναν οδηγό με βάση το verification token
     *
     * @param string $token Το verification token
     * @return array|null Ο οδηγός ή null αν δεν βρέθηκε
     */
    public function getDriverByVerificationToken($token)
    {
        return $this->findOne(['verification_token' => $token]);
    }

    /**
     * Επιστρέφει έναν οδηγό με βάση το reset token
     *
     * @param string $token Το reset token
     * @return array|null Ο οδηγός ή null αν δεν βρέθηκε
     */
    public function getDriverByResetToken($token)
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE reset_token = :token AND reset_token_expires_at > NOW()";
            return $this->queryOne($query, ['token' => $token]);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['token' => $token]);
        }
    }

    /**
     * Επαληθεύει έναν οδηγό
     *
     * @param int $id Το ID του οδηγού
     * @return bool Επιτυχία ή αποτυχία
     */
    public function verifyDriver($id)
    {
        return $this->update($id, [
            'is_verified' => 1,
            'verification_token' => null
        ]);
    }

    /**
     * Ενημερώνει τον κωδικό πρόσβασης ενός οδηγού
     *
     * @param int $id Το ID του οδηγού
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
     * Ενημερώνει την τελευταία σύνδεση ενός οδηγού
     *
     * @param int $id Το ID του οδηγού
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
     * Ενημερώνει τη διαθεσιμότητα ενός οδηγού
     *
     * @param int $id Το ID του οδηγού
     * @param bool $isAvailable Αν ο οδηγός είναι διαθέσιμος
     * @return bool Επιτυχία ή αποτυχία
     */
    public function updateAvailability($id, $isAvailable)
    {
        return $this->update($id, [
            'is_available' => $isAvailable ? 1 : 0
        ]);
    }

    /**
     * Αναζητά οδηγούς με βάση διάφορα κριτήρια
     *
     * @param array $criteria Τα κριτήρια αναζήτησης
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function searchDrivers(array $criteria = [], $page = 1, $limit = 10)
    {
        try {
            $query = "SELECT d.*, 
                      COALESCE(AVG(dr.rating), 0) as average_rating,
                      COUNT(DISTINCT dr.id) as rating_count
                      FROM {$this->table} d
                      LEFT JOIN driver_ratings dr ON d.id = dr.driver_id
                      WHERE d.is_verified = 1";
            $params = [];
            $conditions = [];

            // Προσθήκη των κριτηρίων
            if (isset($criteria['name']) && $criteria['name']) {
                $conditions[] = "(d.first_name LIKE :name OR d.last_name LIKE :name)";
                $params['name'] = '%' . $criteria['name'] . '%';
            }

            if (isset($criteria['location']) && $criteria['location']) {
                $conditions[] = "(d.city LIKE :location OR d.country LIKE :location)";
                $params['location'] = '%' . $criteria['location'] . '%';
            }

            if (isset($criteria['is_available']) && $criteria['is_available'] !== null) {
                $conditions[] = "d.is_available = :is_available";
                $params['is_available'] = $criteria['is_available'] ? 1 : 0;
            }

            if (isset($criteria['min_rating']) && $criteria['min_rating'] > 0) {
                $conditions[] = "COALESCE(AVG(dr.rating), 0) >= :min_rating";
                $params['min_rating'] = $criteria['min_rating'];
            }

            // Προσθήκη των συνθηκών στο ερώτημα
            if (!empty($conditions)) {
                $query .= " AND " . implode(" AND ", $conditions);
            }

            // Προσθήκη του GROUP BY
            $query .= " GROUP BY d.id";

            // Προσθήκη της ταξινόμησης
            if (isset($criteria['sort_by']) && $criteria['sort_by']) {
                $sortField = $criteria['sort_by'];
                $sortDirection = isset($criteria['sort_direction']) && strtoupper($criteria['sort_direction']) === 'DESC' ? 'DESC' : 'ASC';

                // Έλεγχος για έγκυρο πεδίο ταξινόμησης
                $validSortFields = ['first_name', 'last_name', 'city', 'country', 'average_rating', 'last_login'];
                if (in_array($sortField, $validSortFields)) {
                    $query .= " ORDER BY $sortField $sortDirection";
                } else {
                    $query .= " ORDER BY d.last_login DESC";
                }
            } else {
                $query .= " ORDER BY d.last_login DESC";
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
     * Επιστρέφει τους κορυφαίους οδηγούς με βάση την αξιολόγηση
     *
     * @param int $limit Ο αριθμός αποτελεσμάτων
     * @return array Οι κορυφαίοι οδηγοί
     */
    public function getTopRatedDrivers($limit = 10)
    {
        try {
            $query = "SELECT d.*, 
                      COALESCE(AVG(dr.rating), 0) as average_rating,
                      COUNT(DISTINCT dr.id) as rating_count
                      FROM {$this->table} d
                      LEFT JOIN driver_ratings dr ON d.id = dr.driver_id
                      WHERE d.is_verified = 1
                      GROUP BY d.id
                      HAVING rating_count > 0
                      ORDER BY average_rating DESC, rating_count DESC
                      LIMIT :limit";

            return $this->query($query, ['limit' => $limit]);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['limit' => $limit]);
        }
    }

    /**
     * Επιστρέφει τους πρόσφατα διαθέσιμους οδηγούς
     *
     * @param int $limit Ο αριθμός αποτελεσμάτων
     * @return array Οι πρόσφατα διαθέσιμοι οδηγοί
     */
    public function getRecentlyAvailableDrivers($limit = 10)
    {
        try {
            $query = "SELECT d.*, 
                      COALESCE(AVG(dr.rating), 0) as average_rating,
                      COUNT(DISTINCT dr.id) as rating_count
                      FROM {$this->table} d
                      LEFT JOIN driver_ratings dr ON d.id = dr.driver_id
                      WHERE d.is_verified = 1 AND d.is_available = 1
                      GROUP BY d.id
                      ORDER BY d.updated_at DESC
                      LIMIT :limit";

            return $this->query($query, ['limit' => $limit]);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['limit' => $limit]);
        }
    }

    /**
     * Ενημερώνει το προφίλ ενός οδηγού
     * 
     * @param int $id Το ID του οδηγού
     * @param array $data Τα δεδομένα του προφίλ
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateProfile($id, array $data)
    {
        return $this->update($id, $data);
    }

    /**
     * Ενημερώνει την αξιολόγηση ενός οδηγού
     * 
     * @param int $id Το ID του οδηγού
     * @param float $rating Η νέα αξιολόγηση
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateRating($id, $rating)
    {
        try {
            $query = "UPDATE {$this->table} SET rating = :rating WHERE id = :id";
            return $this->execute($query, ['id' => $id, 'rating' => $rating]) > 0;
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['id' => $id, 'rating' => $rating]);
        }
    }

    /**
     * Επιστρέφει τους οδηγούς με άδειες που λήγουν σύντομα
     * 
     * @param int $days Ο αριθμός των ημερών
     * @return array Οι οδηγοί με άδειες που λήγουν σύντομα
     */
    public function getDriversWithExpiringLicenses($days = 30)
    {
        try {
            $query = "SELECT d.* FROM {$this->table} d
                      WHERE (
                          (d.driving_license_expiry IS NOT NULL AND d.driving_license_expiry <= DATE_ADD(CURDATE(), INTERVAL :days DAY))
                          OR (d.adr_certificate_expiry IS NOT NULL AND d.adr_certificate_expiry <= DATE_ADD(CURDATE(), INTERVAL :days DAY))
                          OR (d.operator_license_expiry IS NOT NULL AND d.operator_license_expiry <= DATE_ADD(CURDATE(), INTERVAL :days DAY))
                      )
                      AND d.is_verified = 1
                      ORDER BY 
                          LEAST(
                              IFNULL(d.driving_license_expiry, '9999-12-31'),
                              IFNULL(d.adr_certificate_expiry, '9999-12-31'),
                              IFNULL(d.operator_license_expiry, '9999-12-31')
                          ) ASC";

            return $this->query($query, ['days' => $days]);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['days' => $days]);
        }
    }

    /**
     * Επιστρέφει τους οδηγούς με βάση τον τύπο άδειας
     * 
     * @param string $licenseType Ο τύπος άδειας
     * @return array Οι οδηγοί με τον συγκεκριμένο τύπο άδειας
     */
    public function getDriversByLicenseType($licenseType)
    {
        try {
            $query = "SELECT d.* FROM {$this->table} d
                      JOIN driver_licenses dl ON d.id = dl.driver_id
                      WHERE dl.license_type = :license_type
                      AND d.is_verified = 1
                      ORDER BY d.last_name, d.first_name";

            return $this->query($query, ['license_type' => $licenseType]);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['license_type' => $licenseType]);
        }
    }

    /**
     * Επιστρέφει τους οδηγούς με βάση την τοποθεσία
     * 
     * @param string $location Η τοποθεσία
     * @param int $radius Η ακτίνα σε χιλιόμετρα
     * @return array Οι οδηγοί στην συγκεκριμένη τοποθεσία
     */
    public function getDriversByLocation($location, $radius = 50)
    {
        try {
            // Απλή υλοποίηση με βάση το όνομα της πόλης ή της χώρας
            // Σε μια πραγματική εφαρμογή θα χρησιμοποιούσαμε γεωγραφικές συντεταγμένες
            $query = "SELECT d.* FROM {$this->table} d
                      WHERE (d.city LIKE :location OR d.country LIKE :location)
                      AND d.is_verified = 1
                      ORDER BY d.last_name, d.first_name";

            return $this->query($query, ['location' => '%' . $location . '%']);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['location' => '%' . $location . '%']);
        }
    }

    /**
     * Επιστρέφει τους οδηγούς με βάση την εμπειρία
     * 
     * @param int $years Τα έτη εμπειρίας
     * @return array Οι οδηγοί με την συγκεκριμένη εμπειρία
     */
    public function getDriversByExperience($years)
    {
        try {
            $query = "SELECT d.* FROM {$this->table} d
                      WHERE d.experience_years >= :years
                      AND d.is_verified = 1
                      ORDER BY d.experience_years DESC, d.last_name, d.first_name";

            return $this->query($query, ['years' => $years]);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['years' => $years]);
        }
    }

    /**
     * Επιστρέφει τους οδηγούς με βάση τη διαθεσιμότητα
     * 
     * @param bool $available Η διαθεσιμότητα
     * @return array Οι οδηγοί με την συγκεκριμένη διαθεσιμότητα
     */
    public function getDriversByAvailability($available = true)
    {
        try {
            $query = "SELECT d.* FROM {$this->table} d
                      WHERE d.is_available = :available
                      AND d.is_verified = 1
                      ORDER BY d.last_name, d.first_name";

            return $this->query($query, ['available' => $available ? 1 : 0]);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['available' => $available ? 1 : 0]);
        }
    }
}

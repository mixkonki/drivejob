<?php

namespace Drivejob\Repositories;

use PDO;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Repository για τις αγγελίες εργασίας
 */
class JobListingRepository extends BaseRepository implements JobListingRepositoryInterface
{
    /**
     * @var string Το όνομα του πίνακα
     */
    protected $table = 'job_listings';

    /**
     * @var array Τα πεδία που μπορούν να ενημερωθούν
     */
    protected $fillable = [
        'company_id',
        'title',
        'description',
        'requirements',
        'benefits',
        'location',
        'salary_min',
        'salary_max',
        'salary_period',
        'job_type',
        'vehicle_type',
        'experience_years',
        'license_required',
        'license_types',
        'pei_required',
        'adr_required',
        'tachograph_required',
        'operator_license_required',
        'is_active',
        'is_featured',
        'is_approved',
        'views',
        'applications',
        'expires_at',
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
     * Επιστρέφει τις αγγελίες μιας εταιρείας
     *
     * @param int $companyId Το ID της εταιρείας
     * @param bool $activeOnly Αν θα επιστραφούν μόνο οι ενεργές αγγελίες
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Οι αγγελίες και οι πληροφορίες σελιδοποίησης
     */
    public function getCompanyListings($companyId, $activeOnly = false, $page = 1, $limit = 10)
    {
        try {
            $query = "SELECT j.*, c.company_name
                      FROM {$this->table} j
                      LEFT JOIN companies c ON j.company_id = c.id
                      WHERE j.company_id = :company_id";

            $params = ['company_id' => $companyId];

            if ($activeOnly) {
                $query .= " AND j.is_active = 1 AND (j.expires_at IS NULL OR j.expires_at > NOW())";
            }

            $query .= " ORDER BY j.created_at DESC";

            // Μέτρηση συνολικών αποτελεσμάτων
            $countQuery = "SELECT COUNT(*) FROM ({$query}) as count_table";
            $totalResults = $this->queryScalar($countQuery, $params);

            // Προσθήκη του LIMIT και OFFSET
            $offset = ($page - 1) * $limit;
            $query .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

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
     * Επιστρέφει τις αγγελίες στις οποίες έχει κάνει αίτηση ένας οδηγός
     *
     * @param int $driverId Το ID του οδηγού
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Οι αγγελίες και οι πληροφορίες σελιδοποίησης
     */
    public function getDriverApplications($driverId, $page = 1, $limit = 10)
    {
        try {
            $query = "SELECT j.*, c.company_name, a.status, a.created_at as application_date
                      FROM job_applications a
                      LEFT JOIN {$this->table} j ON a.job_id = j.id
                      LEFT JOIN companies c ON j.company_id = c.id
                      WHERE a.driver_id = :driver_id
                      ORDER BY a.created_at DESC";

            $params = ['driver_id' => $driverId];

            // Μέτρηση συνολικών αποτελεσμάτων
            $countQuery = "SELECT COUNT(*) FROM ({$query}) as count_table";
            $totalResults = $this->queryScalar($countQuery, $params);

            // Προσθήκη του LIMIT και OFFSET
            $offset = ($page - 1) * $limit;
            $query .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

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
     * Αναζητά αγγελίες με βάση διάφορα κριτήρια
     *
     * @param array $criteria Τα κριτήρια αναζήτησης
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function searchListings(array $criteria = [], $page = 1, $limit = 10)
    {
        try {
            $query = "SELECT j.*, c.company_name
                      FROM {$this->table} j
                      LEFT JOIN companies c ON j.company_id = c.id
                      WHERE j.is_active = 1 AND (j.expires_at IS NULL OR j.expires_at > NOW())";
            $params = [];
            $conditions = [];

            // Προσθήκη των κριτηρίων
            if (isset($criteria['title']) && $criteria['title']) {
                $conditions[] = "(j.title LIKE :title OR j.description LIKE :title)";
                $params['title'] = '%' . $criteria['title'] . '%';
            }

            if (isset($criteria['location']) && $criteria['location']) {
                $conditions[] = "j.location LIKE :location";
                $params['location'] = '%' . $criteria['location'] . '%';
            }

            if (isset($criteria['job_type']) && $criteria['job_type']) {
                $conditions[] = "j.job_type = :job_type";
                $params['job_type'] = $criteria['job_type'];
            }

            if (isset($criteria['vehicle_type']) && $criteria['vehicle_type']) {
                $conditions[] = "j.vehicle_type = :vehicle_type";
                $params['vehicle_type'] = $criteria['vehicle_type'];
            }

            if (isset($criteria['experience_years']) && $criteria['experience_years'] > 0) {
                $conditions[] = "j.experience_years <= :experience_years";
                $params['experience_years'] = $criteria['experience_years'];
            }

            if (isset($criteria['license_types']) && !empty($criteria['license_types'])) {
                $licenseConditions = [];
                foreach ($criteria['license_types'] as $index => $licenseType) {
                    $licenseConditions[] = "j.license_types LIKE :license_type_$index";
                    $params["license_type_$index"] = '%' . $licenseType . '%';
                }
                $conditions[] = '(' . implode(' OR ', $licenseConditions) . ')';
            }

            if (isset($criteria['pei_required']) && $criteria['pei_required']) {
                $conditions[] = "j.pei_required = 1";
            }

            if (isset($criteria['adr_required']) && $criteria['adr_required']) {
                $conditions[] = "j.adr_required = 1";
            }

            if (isset($criteria['tachograph_required']) && $criteria['tachograph_required']) {
                $conditions[] = "j.tachograph_required = 1";
            }

            if (isset($criteria['operator_license_required']) && $criteria['operator_license_required']) {
                $conditions[] = "j.operator_license_required = 1";
            }

            if (isset($criteria['salary_min']) && $criteria['salary_min'] > 0) {
                $conditions[] = "j.salary_min >= :salary_min";
                $params['salary_min'] = $criteria['salary_min'];
            }

            if (isset($criteria['company_id']) && $criteria['company_id']) {
                $conditions[] = "j.company_id = :company_id";
                $params['company_id'] = $criteria['company_id'];
            }

            if (isset($criteria['driver_id']) && $criteria['driver_id']) {
                $conditions[] = "j.driver_id = :driver_id";
                $params['driver_id'] = $criteria['driver_id'];
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
                $validSortFields = ['title', 'location', 'salary_min', 'created_at', 'views', 'applications'];
                if (in_array($sortField, $validSortFields)) {
                    $query .= " ORDER BY j.$sortField $sortDirection";
                } else {
                    $query .= " ORDER BY j.created_at DESC";
                }
            } else {
                $query .= " ORDER BY j.created_at DESC";
            }

            // Μέτρηση συνολικών αποτελεσμάτων
            $countQuery = "SELECT COUNT(*) FROM ({$query}) as count_table";
            $totalResults = $this->queryScalar($countQuery, $params);

            // Προσθήκη του LIMIT και OFFSET
            $offset = ($page - 1) * $limit;
            $query .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

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
     * Αυξάνει τον αριθμό προβολών μιας αγγελίας
     *
     * @param int $id Το ID της αγγελίας
     * @return bool Επιτυχία ή αποτυχία
     */
    public function incrementViews($id)
    {
        try {
            $query = "UPDATE {$this->table} SET views = views + 1 WHERE id = :id";
            return $this->execute($query, ['id' => $id]) > 0;
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['id' => $id]);
        }
    }

    /**
     * Αυξάνει τον αριθμό αιτήσεων μιας αγγελίας
     *
     * @param int $id Το ID της αγγελίας
     * @return bool Επιτυχία ή αποτυχία
     */
    public function incrementApplications($id)
    {
        try {
            $query = "UPDATE {$this->table} SET applications = applications + 1 WHERE id = :id";
            return $this->execute($query, ['id' => $id]) > 0;
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['id' => $id]);
        }
    }

    /**
     * Επιστρέφει τις προτεινόμενες αγγελίες για έναν οδηγό
     *
     * @param int $driverId Το ID του οδηγού
     * @param int $limit Ο αριθμός αποτελεσμάτων
     * @return array Οι προτεινόμενες αγγελίες
     */
    public function getRecommendedListings($driverId, $limit = 10)
    {
        try {
            // Λήψη των στοιχείων του οδηγού
            $driverQuery = "SELECT d.*, GROUP_CONCAT(dl.license_type) as license_types
                           FROM drivers d
                           LEFT JOIN driver_licenses dl ON d.id = dl.driver_id
                           WHERE d.id = :driver_id
                           GROUP BY d.id";
            $driver = $this->queryOne($driverQuery, ['driver_id' => $driverId]);

            if (!$driver) {
                return [];
            }

            // Δημιουργία του ερωτήματος για τις προτεινόμενες αγγελίες
            $query = "SELECT j.*, c.company_name,
                      (
                          CASE
                              WHEN j.location LIKE :location THEN 10 ELSE 0
                          END +
                          CASE
                              WHEN j.vehicle_type = :vehicle_type THEN 5 ELSE 0
                          END +
                          CASE
                              WHEN j.experience_years <= :experience_years THEN 3 ELSE 0
                          END
                      ) as match_score
                      FROM {$this->table} j
                      LEFT JOIN companies c ON j.company_id = c.id
                      WHERE j.is_active = 1 AND (j.expires_at IS NULL OR j.expires_at > NOW())
                      AND j.id NOT IN (
                          SELECT job_id FROM job_applications WHERE driver_id = :driver_id
                      )";

            $params = [
                'driver_id' => $driverId,
                'location' => '%' . ($driver['city'] ?? '') . '%',
                'vehicle_type' => $driver['preferred_vehicle_type'] ?? '',
                'experience_years' => $driver['experience_years'] ?? 0
            ];

            // Προσθήκη συνθήκης για τις άδειες οδήγησης
            if (!empty($driver['license_types'])) {
                $licenseTypes = explode(',', $driver['license_types']);
                $licenseConditions = [];
                foreach ($licenseTypes as $index => $licenseType) {
                    $licenseConditions[] = "j.license_types LIKE :license_type_$index";
                    $params["license_type_$index"] = '%' . $licenseType . '%';
                }
                $query .= " AND (" . implode(' OR ', $licenseConditions) . ")";
            }

            // Ταξινόμηση με βάση το match_score και την ημερομηνία δημιουργίας
            $query .= " ORDER BY match_score DESC, j.created_at DESC LIMIT " . (int)$limit;

            // Εκτέλεση του ερωτήματος
            return $this->query($query, $params);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, $params ?? []);
        }
    }

    /**
     * Επιστρέφει τις πρόσφατες αγγελίες
     *
     * @param int $limit Ο αριθμός αποτελεσμάτων
     * @return array Οι πρόσφατες αγγελίες
     */
    public function getRecentListings($limit = 10)
    {
        try {
            $query = "SELECT j.*, c.company_name
                      FROM {$this->table} j
                      LEFT JOIN companies c ON j.company_id = c.id
                      WHERE j.is_active = 1 AND (j.expires_at IS NULL OR j.expires_at > NOW())
                      ORDER BY j.created_at DESC
                      LIMIT " . (int)$limit;

            return $this->query($query);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['limit' => $limit]);
        }
    }

    /**
     * Επιστρέφει τις δημοφιλείς αγγελίες
     *
     * @param int $limit Ο αριθμός αποτελεσμάτων
     * @return array Οι δημοφιλείς αγγελίες
     */
    public function getPopularListings($limit = 10)
    {
        try {
            $query = "SELECT j.*, c.company_name
                      FROM {$this->table} j
                      LEFT JOIN companies c ON j.company_id = c.id
                      WHERE j.is_active = 1 AND (j.expires_at IS NULL OR j.expires_at > NOW())
                      ORDER BY j.views DESC, j.applications DESC
                      LIMIT " . (int)$limit;

            return $this->query($query);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['limit' => $limit]);
        }
    }

    /**
     * Επιστρέφει τις προβεβλημένες αγγελίες
     *
     * @param int $limit Ο αριθμός αποτελεσμάτων
     * @return array Οι προβεβλημένες αγγελίες
     */
    public function getFeaturedListings($limit = 10)
    {
        try {
            $query = "SELECT j.*, c.company_name
                      FROM {$this->table} j
                      LEFT JOIN companies c ON j.company_id = c.id
                      WHERE j.is_active = 1 AND (j.expires_at IS NULL OR j.expires_at > NOW())
                      ORDER BY j.created_at DESC
                      LIMIT " . (int)$limit;

            return $this->query($query);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['limit' => $limit]);
        }
    }
}

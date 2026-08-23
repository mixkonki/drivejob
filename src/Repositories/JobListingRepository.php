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
    /**
     * Τα πεδία που επιτρέπεται να γραφτούν.
     *
     * Παράγεται ΑΠΟ ΤΟ ΣΧΗΜΑ, όχι από μνήμη. Η προηγούμενη λίστα είχε
     * αποκλίνει σοβαρά: εννέα ονόματα δεν αντιστοιχούσαν σε καμία στήλη
     * (salary_period, license_required, pei_required, is_featured, views…)
     * και ταυτόχρονα ΕΚΟΒΕ ΣΙΩΠΗΛΑ εικοσιπέντε υπαρκτές στήλες — ανάμεσά
     * τους contact_email, contact_phone, salary_type, required_license,
     * transport_type. Πρακτικά κάθε δημιουργία αγγελίας έχανε τα μισά
     * πεδία της φόρμας χωρίς κανένα μήνυμα.
     */
    protected $fillable = [
        'title',
        'company_id',
        'driver_id',
        'listing_type',
        'transport_type',
        'job_category',
        'job_type',
        'required_license',
        'description',
        'salary_min',
        'salary_max',
        'salary_type',
        'location',
        'latitude',
        'longitude',
        'radius',
        'remote_possible',
        'experience_years',
        'specialized_experience',
        'machinery_types',
        'adr_certificate',
        'requires_pei',
        'requires_tachograph',
        'operator_license',
        'required_training',
        'benefits',
        'additional_info',
        'contact_email',
        'contact_phone',
        'is_active',
        'is_approved',
        'updated_at',
        'expires_at',
        'views_count',
        'applications',
        'preferred_schedule',
        'max_days_away',
        'is_urgent',
        'route_type',
        'cargo_type',
        'requirements',
        'min_experience',
        'status',
        'employment_type',
        'vehicle_type',
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
            // Η στήλη λέγεται views_count — το «views» δεν υπήρξε ποτέ.
            $query = "UPDATE {$this->table} SET views_count = views_count + 1 WHERE id = :id";
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
                          SELECT job_listing_id FROM job_applications WHERE driver_id = :driver_id
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

    /**
     * Οι τύποι οχημάτων μιας αγγελίας, από τη μοναδική έγκυρη πηγή.
     *
     * Η στήλη job_listings.vehicle_types καταργήθηκε επειδή ήταν διπλή πηγή
     * αλήθειας — τέσσερις αγγελίες είχαν δεδομένα μόνο στον πίνακα και κανένα
     * query δεν τα έβλεπε. Τα views εξακολουθούσαν να διαβάζουν το πεδίο και
     * έδειχναν κενό.
     *
     * @return string[] κωδικοί τύπων οχημάτων
     */
    public function vehicleTypesFor(int $listingId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT vehicle_type FROM job_listing_vehicle_types WHERE job_listing_id = :id'
        );
        $stmt->execute([':id' => $listingId]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Γεμίζει το πεδίο vehicle_types μιας αγγελίας (ή λίστας αγγελιών).
     *
     * Δέχεται είτε μία αγγελία είτε πίνακα αγγελιών και επιστρέφει το ίδιο
     * σχήμα. Για λίστες κάνει ΕΝΑ query αντί για ένα ανά αγγελία.
     *
     * @param array $listings μία αγγελία ή πίνακας αγγελιών
     * @return array το ίδιο σχήμα, με το vehicle_types συμπληρωμένο
     */
    public function withVehicleTypes(array $listings): array
    {
        if (empty($listings)) {
            return $listings;
        }

        $single = isset($listings['id']);
        $rows = $single ? [$listings] : $listings;

        $ids = array_values(array_filter(array_map(
            static fn($l) => isset($l['id']) ? (int) $l['id'] : null,
            $rows
        )));

        if (empty($ids)) {
            return $listings;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT job_listing_id, vehicle_type
             FROM job_listing_vehicle_types
             WHERE job_listing_id IN ($placeholders)"
        );
        $stmt->execute($ids);

        $byListing = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $byListing[(int) $row['job_listing_id']][] = $row['vehicle_type'];
        }

        foreach ($rows as $i => $listing) {
            $id = isset($listing['id']) ? (int) $listing['id'] : 0;
            $rows[$i]['vehicle_types'] = $byListing[$id] ?? [];
        }

        return $single ? $rows[0] : $rows;
    }

    /**
     * Αντικαθιστά πλήρως τους τύπους οχημάτων μιας αγγελίας.
     *
     * @param string[] $types κωδικοί — οι μη έγκυροι αγνοούνται
     */
    public function setVehicleTypes(int $listingId, array $types): void
    {
        $this->pdo->prepare('DELETE FROM job_listing_vehicle_types WHERE job_listing_id = :id')
                 ->execute([':id' => $listingId]);

        $valid = array_unique(array_filter(
            array_map([\Drivejob\Helpers\VehicleTypes::class, 'normalise'], $types),
            [\Drivejob\Helpers\VehicleTypes::class, 'isValid']
        ));

        if (empty($valid)) {
            return;
        }

        $stmt = $this->pdo->prepare(
            'INSERT INTO job_listing_vehicle_types (job_listing_id, vehicle_type) VALUES (:id, :type)'
        );

        foreach ($valid as $type) {
            $stmt->execute([':id' => $listingId, ':type' => $type]);
        }
    }
}

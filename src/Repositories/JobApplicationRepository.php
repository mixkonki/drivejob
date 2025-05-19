<?php

namespace Drivejob\Repositories;

use PDO;
use Drivejob\Core\Logger;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Repository για τις αιτήσεις εργασίας
 */
class JobApplicationRepository extends BaseRepository implements JobApplicationRepositoryInterface
{
    /**
     * @var string Το όνομα του πίνακα
     */
    protected $table = 'job_applications';

    /**
     * @var array Τα πεδία που μπορούν να ενημερωθούν
     */
    protected $fillable = [
        'job_listing_id',
        'driver_id',
        'cover_letter',
        'resume_path',
        'status',
        'notes',
        'interview_date',
        'interview_location',
        'interview_notes',
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
    public function findByListingAndDriver($jobListingId, $driverId)
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE job_listing_id = :job_listing_id AND driver_id = :driver_id";
            $params = [
                'job_listing_id' => $jobListingId,
                'driver_id' => $driverId
            ];
            return $this->queryOne($query, $params);
        } catch (\PDOException $e) {
            Logger::error('Error in findByListingAndDriver', [
                'message' => $e->getMessage(),
                'job_listing_id' => $jobListingId,
                'driver_id' => $driverId
            ]);
            throw new DatabaseException('Failed to find job application by listing and driver', (int)$e->getCode(), $e, [
                'job_listing_id' => $jobListingId,
                'driver_id' => $driverId
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findByListing($jobListingId, $page = 1, $limit = 10)
    {
        try {
            // Υπολογισμός του offset
            $offset = ($page - 1) * $limit;

            // Δημιουργία του SQL ερωτήματος
            $query = "SELECT a.*, d.first_name, d.last_name, d.profile_image, d.rating, d.experience_years
                      FROM {$this->table} a
                      JOIN drivers d ON a.driver_id = d.id
                      WHERE a.job_listing_id = :job_listing_id
                      ORDER BY a.created_at DESC
                      LIMIT :limit OFFSET :offset";
            $params = [
                'job_listing_id' => $jobListingId,
                'limit' => $limit,
                'offset' => $offset
            ];

            // Εκτέλεση του ερωτήματος
            $results = $this->query($query, $params);

            // Μέτρηση του συνολικού αριθμού αιτήσεων
            $countQuery = "SELECT COUNT(*) FROM {$this->table} WHERE job_listing_id = :job_listing_id";
            $countParams = ['job_listing_id' => $jobListingId];
            $totalResults = $this->queryScalar($countQuery, $countParams);

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
            Logger::error('Error in findByListing', [
                'message' => $e->getMessage(),
                'job_listing_id' => $jobListingId,
                'page' => $page,
                'limit' => $limit
            ]);
            throw new DatabaseException('Failed to find job applications by listing', (int)$e->getCode(), $e, [
                'job_listing_id' => $jobListingId,
                'page' => $page,
                'limit' => $limit
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findByDriver($driverId, $page = 1, $limit = 10)
    {
        try {
            // Υπολογισμός του offset
            $offset = ($page - 1) * $limit;

            // Δημιουργία του SQL ερωτήματος
            $query = "SELECT a.*, jl.title, jl.location, jl.job_type, jl.vehicle_type, jl.salary_min, jl.salary_max,
                      c.company_name, c.logo as company_logo
                      FROM {$this->table} a
                      JOIN job_listings jl ON a.job_listing_id = jl.id
                      JOIN companies c ON jl.company_id = c.id
                      WHERE a.driver_id = :driver_id
                      ORDER BY a.created_at DESC
                      LIMIT :limit OFFSET :offset";
            $params = [
                'driver_id' => $driverId,
                'limit' => $limit,
                'offset' => $offset
            ];

            // Εκτέλεση του ερωτήματος
            $results = $this->query($query, $params);

            // Μέτρηση του συνολικού αριθμού αιτήσεων
            $countQuery = "SELECT COUNT(*) FROM {$this->table} WHERE driver_id = :driver_id";
            $countParams = ['driver_id' => $driverId];
            $totalResults = $this->queryScalar($countQuery, $countParams);

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
            Logger::error('Error in findByDriver', [
                'message' => $e->getMessage(),
                'driver_id' => $driverId,
                'page' => $page,
                'limit' => $limit
            ]);
            throw new DatabaseException('Failed to find job applications by driver', (int)$e->getCode(), $e, [
                'driver_id' => $driverId,
                'page' => $page,
                'limit' => $limit
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findByCompany($companyId, $page = 1, $limit = 10)
    {
        try {
            // Υπολογισμός του offset
            $offset = ($page - 1) * $limit;

            // Δημιουργία του SQL ερωτήματος
            $query = "SELECT a.*, jl.title, jl.location, jl.job_type, jl.vehicle_type,
                      d.first_name, d.last_name, d.profile_image, d.rating, d.experience_years
                      FROM {$this->table} a
                      JOIN job_listings jl ON a.job_listing_id = jl.id
                      JOIN drivers d ON a.driver_id = d.id
                      WHERE jl.company_id = :company_id
                      ORDER BY a.created_at DESC
                      LIMIT :limit OFFSET :offset";
            $params = [
                'company_id' => $companyId,
                'limit' => $limit,
                'offset' => $offset
            ];

            // Εκτέλεση του ερωτήματος
            $results = $this->query($query, $params);

            // Μέτρηση του συνολικού αριθμού αιτήσεων
            $countQuery = "SELECT COUNT(*) FROM {$this->table} a
                          JOIN job_listings jl ON a.job_listing_id = jl.id
                          WHERE jl.company_id = :company_id";
            $countParams = ['company_id' => $companyId];
            $totalResults = $this->queryScalar($countQuery, $countParams);

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
            Logger::error('Error in findByCompany', [
                'message' => $e->getMessage(),
                'company_id' => $companyId,
                'page' => $page,
                'limit' => $limit
            ]);
            throw new DatabaseException('Failed to find job applications by company', (int)$e->getCode(), $e, [
                'company_id' => $companyId,
                'page' => $page,
                'limit' => $limit
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function searchApplications(array $criteria = [], $page = 1, $limit = 10)
    {
        try {
            // Υπολογισμός του offset
            $offset = ($page - 1) * $limit;

            // Δημιουργία του βασικού SQL ερωτήματος
            $query = "SELECT a.*, jl.title, jl.location, jl.job_type, jl.vehicle_type,
                      d.first_name, d.last_name, d.profile_image, d.rating, d.experience_years,
                      c.company_name, c.logo as company_logo
                      FROM {$this->table} a
                      JOIN job_listings jl ON a.job_listing_id = jl.id
                      JOIN drivers d ON a.driver_id = d.id
                      JOIN companies c ON jl.company_id = c.id
                      WHERE 1=1";
            $params = [];
            $conditions = [];

            // Προσθήκη των κριτηρίων
            if (isset($criteria['job_listing_id']) && $criteria['job_listing_id']) {
                $conditions[] = "a.job_listing_id = :job_listing_id";
                $params['job_listing_id'] = $criteria['job_listing_id'];
            }

            if (isset($criteria['driver_id']) && $criteria['driver_id']) {
                $conditions[] = "a.driver_id = :driver_id";
                $params['driver_id'] = $criteria['driver_id'];
            }

            if (isset($criteria['company_id']) && $criteria['company_id']) {
                $conditions[] = "jl.company_id = :company_id";
                $params['company_id'] = $criteria['company_id'];
            }

            if (isset($criteria['status']) && $criteria['status']) {
                $conditions[] = "a.status = :status";
                $params['status'] = $criteria['status'];
            }

            if (isset($criteria['location']) && $criteria['location']) {
                $conditions[] = "jl.location LIKE :location";
                $params['location'] = '%' . $criteria['location'] . '%';
            }

            if (isset($criteria['job_type']) && $criteria['job_type']) {
                $conditions[] = "jl.job_type = :job_type";
                $params['job_type'] = $criteria['job_type'];
            }

            if (isset($criteria['vehicle_type']) && $criteria['vehicle_type']) {
                $conditions[] = "jl.vehicle_type = :vehicle_type";
                $params['vehicle_type'] = $criteria['vehicle_type'];
            }

            if (isset($criteria['created_from']) && $criteria['created_from']) {
                $conditions[] = "a.created_at >= :created_from";
                $params['created_from'] = $criteria['created_from'];
            }

            if (isset($criteria['created_to']) && $criteria['created_to']) {
                $conditions[] = "a.created_at <= :created_to";
                $params['created_to'] = $criteria['created_to'];
            }

            if (isset($criteria['interview_from']) && $criteria['interview_from']) {
                $conditions[] = "a.interview_date >= :interview_from";
                $params['interview_from'] = $criteria['interview_from'];
            }

            if (isset($criteria['interview_to']) && $criteria['interview_to']) {
                $conditions[] = "a.interview_date <= :interview_to";
                $params['interview_to'] = $criteria['interview_to'];
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
                $validSortFields = ['created_at', 'updated_at', 'status', 'interview_date'];
                if (in_array($sortField, $validSortFields)) {
                    $query .= " ORDER BY a.$sortField $sortDirection";
                } else {
                    $query .= " ORDER BY a.created_at DESC";
                }
            } else {
                $query .= " ORDER BY a.created_at DESC";
            }

            // Μέτρηση του συνολικού αριθμού αιτήσεων
            $countQuery = "SELECT COUNT(*) FROM ({$query}) as count_table";
            $countStmt = $this->pdo->prepare($countQuery);
            foreach ($params as $key => $value) {
                $countStmt->bindValue(":$key", $value);
            }
            $countStmt->execute();
            $totalResults = $countStmt->fetchColumn();

            // Προσθήκη του LIMIT και OFFSET
            $query .= " LIMIT :limit OFFSET :offset";
            $params['limit'] = $limit;
            $params['offset'] = $offset;

            // Εκτέλεση του ερωτήματος
            $stmt = $this->pdo->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            Logger::error('Error in searchApplications', [
                'message' => $e->getMessage(),
                'criteria' => $criteria,
                'page' => $page,
                'limit' => $limit
            ]);
            throw new DatabaseException('Failed to search job applications', (int)$e->getCode(), $e, [
                'criteria' => $criteria,
                'page' => $page,
                'limit' => $limit
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateStatus($id, $status)
    {
        try {
            $query = "UPDATE {$this->table} SET status = :status, updated_at = NOW() WHERE id = :id";
            $params = [
                'status' => $status,
                'id' => $id
            ];
            return $this->execute($query, $params) > 0;
        } catch (\PDOException $e) {
            Logger::error('Error in updateStatus', [
                'message' => $e->getMessage(),
                'id' => $id,
                'status' => $status
            ]);
            throw new DatabaseException('Failed to update job application status', (int)$e->getCode(), $e, [
                'id' => $id,
                'status' => $status
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function acceptApplication($id)
    {
        return $this->updateStatus($id, 'accepted');
    }

    /**
     * {@inheritdoc}
     */
    public function rejectApplication($id)
    {
        return $this->updateStatus($id, 'rejected');
    }

    /**
     * {@inheritdoc}
     */
    public function cancelApplication($id)
    {
        return $this->updateStatus($id, 'cancelled');
    }

    /**
     * {@inheritdoc}
     */
    public function getRecentApplications($limit = 10)
    {
        try {
            $query = "SELECT a.*, jl.title, jl.location, jl.job_type, jl.vehicle_type,
                      d.first_name, d.last_name, d.profile_image,
                      c.company_name, c.logo as company_logo
                      FROM {$this->table} a
                      JOIN job_listings jl ON a.job_listing_id = jl.id
                      JOIN drivers d ON a.driver_id = d.id
                      JOIN companies c ON jl.company_id = c.id
                      ORDER BY a.created_at DESC
                      LIMIT :limit";
            $params = ['limit' => $limit];
            return $this->query($query, $params);
        } catch (\PDOException $e) {
            Logger::error('Error in getRecentApplications', [
                'message' => $e->getMessage(),
                'limit' => $limit
            ]);
            throw new DatabaseException('Failed to get recent job applications', (int)$e->getCode(), $e, [
                'limit' => $limit
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getPendingApplications($limit = 10)
    {
        try {
            $query = "SELECT a.*, jl.title, jl.location, jl.job_type, jl.vehicle_type,
                      d.first_name, d.last_name, d.profile_image,
                      c.company_name, c.logo as company_logo
                      FROM {$this->table} a
                      JOIN job_listings jl ON a.job_listing_id = jl.id
                      JOIN drivers d ON a.driver_id = d.id
                      JOIN companies c ON jl.company_id = c.id
                      WHERE a.status = 'pending'
                      ORDER BY a.created_at DESC
                      LIMIT :limit";
            $params = ['limit' => $limit];
            return $this->query($query, $params);
        } catch (\PDOException $e) {
            Logger::error('Error in getPendingApplications', [
                'message' => $e->getMessage(),
                'limit' => $limit
            ]);
            throw new DatabaseException('Failed to get pending job applications', (int)$e->getCode(), $e, [
                'limit' => $limit
            ]);
        }
    }
}

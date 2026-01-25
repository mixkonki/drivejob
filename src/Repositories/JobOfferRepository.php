<?php

namespace Drivejob\Repositories;

use PDO;
use Drivejob\Core\Logger;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Repository για τις προσφορές εργασίας
 */
class JobOfferRepository extends BaseRepository implements JobOfferRepositoryInterface
{
    /**
     * @var string Το όνομα του πίνακα
     */
    protected $table = 'job_offers';

    /**
     * @var array Τα πεδία που μπορούν να ενημερωθούν
     */
    protected $fillable = [
        'company_id',
        'driver_id',
        'title',
        'description',
        'location',
        'job_type',
        'vehicle_type',
        'salary_min',
        'salary_max',
        'salary_period',
        'benefits',
        'start_date',
        'status',
        'document_path',
        'contract_template_path',
        'job_description_path',
        'company_brochure_path',
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
     * Δημιουργεί μια νέα προσφορά εργασίας
     * 
     * @param array $data Τα δεδομένα της προσφοράς
     * @return int|false Το ID της νέας προσφοράς ή false σε περίπτωση αποτυχίας
     */
    public function create(array $data)
    {
        try {
            // Δημιουργία του SQL ερωτήματος
            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";

            // Εκτέλεση του ερωτήματος
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute(array_values($data));

            if ($result) {
                return $this->pdo->lastInsertId();
            } else {
                Logger::error('Failed to create job offer', [
                    'data' => $data,
                    'error' => $stmt->errorInfo()
                ]);
                return false;
            }
        } catch (\PDOException $e) {
            Logger::error('PDO exception in create job offer', [
                'message' => $e->getMessage(),
                'data' => $data
            ]);
            throw new DatabaseException('Failed to create job offer', (int)$e->getCode(), $e, $data);
        }
    }

    /**
     * Ενημερώνει μια προσφορά εργασίας
     * 
     * @param int $id Το ID της προσφοράς
     * @param array $data Τα δεδομένα προς ενημέρωση
     * @return bool Επιτυχία/αποτυχία
     */
    public function update($id, array $data)
    {
        try {
            // Δημιουργία του SQL ερωτήματος
            $setClause = implode(' = ?, ', array_keys($data)) . ' = ?';
            $sql = "UPDATE {$this->table} SET $setClause WHERE id = ?";

            // Εκτέλεση του ερωτήματος
            $stmt = $this->pdo->prepare($sql);
            $values = array_values($data);
            $values[] = $id;
            $result = $stmt->execute($values);

            if (!$result) {
                Logger::error('Failed to update job offer', [
                    'id' => $id,
                    'data' => $data,
                    'error' => $stmt->errorInfo()
                ]);
            }

            return $result;
        } catch (\PDOException $e) {
            Logger::error('PDO exception in update job offer', [
                'message' => $e->getMessage(),
                'id' => $id,
                'data' => $data
            ]);
            throw new DatabaseException('Failed to update job offer', (int)$e->getCode(), $e, [
                'id' => $id,
                'data' => $data
            ]);
        }
    }

    /**
     * Διαγράφει μια προσφορά εργασίας
     * 
     * @param int $id Το ID της προσφοράς
     * @return bool Επιτυχία/αποτυχία
     */
    public function delete($id)
    {
        try {
            // Δημιουργία του SQL ερωτήματος
            $sql = "DELETE FROM {$this->table} WHERE id = ?";

            // Εκτέλεση του ερωτήματος
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);

            if (!$result) {
                Logger::error('Failed to delete job offer', [
                    'id' => $id,
                    'error' => $stmt->errorInfo()
                ]);
            }

            return $result;
        } catch (\PDOException $e) {
            Logger::error('PDO exception in delete job offer', [
                'message' => $e->getMessage(),
                'id' => $id
            ]);
            throw new DatabaseException('Failed to delete job offer', (int)$e->getCode(), $e, ['id' => $id]);
        }
    }

    /**
     * Βρίσκει μια προσφορά εργασίας με βάση το ID
     * 
     * @param int $id Το ID της προσφοράς
     * @return array|null Τα δεδομένα της προσφοράς ή null αν δεν βρέθηκε
     */
    public function find($id)
    {
        try {
            // Δημιουργία του SQL ερωτήματος
            $sql = "SELECT * FROM {$this->table} WHERE id = ?";

            // Εκτέλεση του ερωτήματος
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;
        } catch (\PDOException $e) {
            Logger::error('PDO exception in find job offer', [
                'message' => $e->getMessage(),
                'id' => $id
            ]);
            throw new DatabaseException('Failed to find job offer', (int)$e->getCode(), $e, ['id' => $id]);
        }
    }

    /**
     * Βρίσκει μια προσφορά εργασίας με βάση την εταιρεία και τον οδηγό
     * 
     * @param int $companyId Το ID της εταιρείας
     * @param int $driverId Το ID του οδηγού
     * @return array|null Τα δεδομένα της προσφοράς ή null αν δεν βρέθηκε
     */
    public function findByCompanyAndDriver($companyId, $driverId)
    {
        try {
            // Δημιουργία του SQL ερωτήματος
            $sql = "SELECT * FROM {$this->table} WHERE company_id = ? AND driver_id = ? ORDER BY created_at DESC LIMIT 1";

            // Εκτέλεση του ερωτήματος
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$companyId, $driverId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;
        } catch (\PDOException $e) {
            Logger::error('PDO exception in findByCompanyAndDriver', [
                'message' => $e->getMessage(),
                'company_id' => $companyId,
                'driver_id' => $driverId
            ]);
            throw new DatabaseException('Failed to find job offer by company and driver', (int)$e->getCode(), $e, [
                'company_id' => $companyId,
                'driver_id' => $driverId
            ]);
        }
    }

    /**
     * Βρίσκει τις προσφορές εργασίας ενός οδηγού
     * 
     * @param int $driverId Το ID του οδηγού
     * @param int $page Η σελίδα των αποτελεσμάτων
     * @param int $limit Ο αριθμός των αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findByDriver($driverId, $page = 1, $limit = 10)
    {
        try {
            // Υπολογισμός του offset
            $offset = ($page - 1) * $limit;

            // Μέτρηση του συνολικού αριθμού προσφορών
            $countSql = "SELECT COUNT(*) FROM {$this->table} WHERE driver_id = ?";
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute([$driverId]);
            $totalCount = $countStmt->fetchColumn();

            // Δημιουργία του SQL ερωτήματος για τις προσφορές
            $sql = "SELECT o.*, c.name as company_name, c.logo as company_logo
                    FROM {$this->table} o
                    JOIN companies c ON o.company_id = c.id
                    WHERE o.driver_id = ?
                    ORDER BY o.created_at DESC
                    LIMIT ? OFFSET ?";

            // Εκτέλεση του ερωτήματος
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$driverId, $limit, $offset]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Υπολογισμός του συνολικού αριθμού σελίδων
            $totalPages = ceil($totalCount / $limit);

            return [
                'results' => $results,
                'pagination' => [
                    'total' => $totalCount,
                    'per_page' => $limit,
                    'current_page' => $page,
                    'last_page' => $totalPages,
                    'from' => $offset + 1,
                    'to' => min($offset + $limit, $totalCount)
                ]
            ];
        } catch (\PDOException $e) {
            Logger::error('PDO exception in findByDriver', [
                'message' => $e->getMessage(),
                'driver_id' => $driverId,
                'page' => $page,
                'limit' => $limit
            ]);
            throw new DatabaseException('Failed to find job offers by driver', (int)$e->getCode(), $e, [
                'driver_id' => $driverId,
                'page' => $page,
                'limit' => $limit
            ]);
        }
    }

    /**
     * Βρίσκει τις προσφορές εργασίας μιας εταιρείας
     * 
     * @param int $companyId Το ID της εταιρείας
     * @param int $page Η σελίδα των αποτελεσμάτων
     * @param int $limit Ο αριθμός των αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findByCompany($companyId, $page = 1, $limit = 10)
    {
        try {
            // Υπολογισμός του offset
            $offset = ($page - 1) * $limit;

            // Μέτρηση του συνολικού αριθμού προσφορών
            $countSql = "SELECT COUNT(*) FROM {$this->table} WHERE company_id = ?";
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute([$companyId]);
            $totalCount = $countStmt->fetchColumn();

            // Δημιουργία του SQL ερωτήματος για τις προσφορές
            $sql = "SELECT o.*, d.first_name, d.last_name, d.profile_image, d.rating
                    FROM {$this->table} o
                    JOIN drivers d ON o.driver_id = d.id
                    WHERE o.company_id = ?
                    ORDER BY o.created_at DESC
                    LIMIT ? OFFSET ?";

            // Εκτέλεση του ερωτήματος
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$companyId, $limit, $offset]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Υπολογισμός του συνολικού αριθμού σελίδων
            $totalPages = ceil($totalCount / $limit);

            return [
                'results' => $results,
                'pagination' => [
                    'total' => $totalCount,
                    'per_page' => $limit,
                    'current_page' => $page,
                    'last_page' => $totalPages,
                    'from' => $offset + 1,
                    'to' => min($offset + $limit, $totalCount)
                ]
            ];
        } catch (\PDOException $e) {
            Logger::error('PDO exception in findByCompany', [
                'message' => $e->getMessage(),
                'company_id' => $companyId,
                'page' => $page,
                'limit' => $limit
            ]);
            throw new DatabaseException('Failed to find job offers by company', (int)$e->getCode(), $e, [
                'company_id' => $companyId,
                'page' => $page,
                'limit' => $limit
            ]);
        }
    }

    /**
     * Ενημερώνει την κατάσταση μιας προσφοράς
     * 
     * @param int $id Το ID της προσφοράς
     * @param string $status Η νέα κατάσταση
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateStatus($id, $status)
    {
        try {
            // Δημιουργία του SQL ερωτήματος
            $sql = "UPDATE {$this->table} SET status = ?, updated_at = ? WHERE id = ?";

            // Εκτέλεση του ερωτήματος
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$status, date('Y-m-d H:i:s'), $id]);

            if (!$result) {
                Logger::error('Failed to update job offer status', [
                    'id' => $id,
                    'status' => $status,
                    'error' => $stmt->errorInfo()
                ]);
            }

            return $result;
        } catch (\PDOException $e) {
            Logger::error('PDO exception in updateStatus', [
                'message' => $e->getMessage(),
                'id' => $id,
                'status' => $status
            ]);
            throw new DatabaseException('Failed to update job offer status', (int)$e->getCode(), $e, [
                'id' => $id,
                'status' => $status
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function searchOffers(array $criteria = [], $page = 1, $limit = 10)
    {
        try {
            // Υπολογισμός του offset
            $offset = ($page - 1) * $limit;

            // Δημιουργία του βασικού SQL ερωτήματος
            $sql = "SELECT o.*, c.company_name, c.logo as company_logo, d.first_name, d.last_name, d.profile_image
                    FROM {$this->table} o
                    LEFT JOIN companies c ON o.company_id = c.id
                    LEFT JOIN drivers d ON o.driver_id = d.id
                    WHERE 1=1";
            $params = [];
            $conditions = [];

            // Προσθήκη των κριτηρίων
            if (isset($criteria['company_id']) && $criteria['company_id']) {
                $conditions[] = "o.company_id = :company_id";
                $params['company_id'] = $criteria['company_id'];
            }

            if (isset($criteria['driver_id']) && $criteria['driver_id']) {
                $conditions[] = "o.driver_id = :driver_id";
                $params['driver_id'] = $criteria['driver_id'];
            }

            if (isset($criteria['status']) && $criteria['status']) {
                $conditions[] = "o.status = :status";
                $params['status'] = $criteria['status'];
            }

            if (isset($criteria['location']) && $criteria['location']) {
                $conditions[] = "o.location LIKE :location";
                $params['location'] = '%' . $criteria['location'] . '%';
            }

            if (isset($criteria['job_type']) && $criteria['job_type']) {
                $conditions[] = "o.job_type = :job_type";
                $params['job_type'] = $criteria['job_type'];
            }

            if (isset($criteria['vehicle_type']) && $criteria['vehicle_type']) {
                $conditions[] = "o.vehicle_type = :vehicle_type";
                $params['vehicle_type'] = $criteria['vehicle_type'];
            }

            if (isset($criteria['min_salary']) && $criteria['min_salary']) {
                $conditions[] = "o.salary_min >= :min_salary";
                $params['min_salary'] = $criteria['min_salary'];
            }

            if (isset($criteria['max_salary']) && $criteria['max_salary']) {
                $conditions[] = "o.salary_max <= :max_salary";
                $params['max_salary'] = $criteria['max_salary'];
            }

            if (isset($criteria['start_date_from']) && $criteria['start_date_from']) {
                $conditions[] = "o.start_date >= :start_date_from";
                $params['start_date_from'] = $criteria['start_date_from'];
            }

            if (isset($criteria['start_date_to']) && $criteria['start_date_to']) {
                $conditions[] = "o.start_date <= :start_date_to";
                $params['start_date_to'] = $criteria['start_date_to'];
            }

            // Προσθήκη των συνθηκών στο ερώτημα
            if (!empty($conditions)) {
                $sql .= " AND " . implode(" AND ", $conditions);
            }

            // Προσθήκη της ταξινόμησης
            if (isset($criteria['sort_by']) && $criteria['sort_by']) {
                $sortField = $criteria['sort_by'];
                $sortDirection = isset($criteria['sort_direction']) && strtoupper($criteria['sort_direction']) === 'DESC' ? 'DESC' : 'ASC';

                // Έλεγχος για έγκυρο πεδίο ταξινόμησης
                $validSortFields = ['created_at', 'updated_at', 'salary_min', 'salary_max', 'start_date'];
                if (in_array($sortField, $validSortFields)) {
                    $sql .= " ORDER BY o.$sortField $sortDirection";
                } else {
                    $sql .= " ORDER BY o.created_at DESC";
                }
            } else {
                $sql .= " ORDER BY o.created_at DESC";
            }

            // Μέτρηση του συνολικού αριθμού προσφορών
            $countSql = "SELECT COUNT(*) FROM ({$sql}) as count_table";
            $countStmt = $this->pdo->prepare($countSql);
            foreach ($params as $key => $value) {
                $countStmt->bindValue(":$key", $value);
            }
            $countStmt->execute();
            $totalCount = $countStmt->fetchColumn();

            // Προσθήκη του LIMIT και OFFSET
            $sql .= " LIMIT :limit OFFSET :offset";
            $params['limit'] = $limit;
            $params['offset'] = $offset;

            // Εκτέλεση του ερωτήματος
            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Υπολογισμός του συνολικού αριθμού σελίδων
            $totalPages = ceil($totalCount / $limit);

            return [
                'results' => $results,
                'pagination' => [
                    'total' => $totalCount,
                    'per_page' => $limit,
                    'current_page' => $page,
                    'last_page' => $totalPages,
                    'from' => $offset + 1,
                    'to' => min($offset + $limit, $totalCount)
                ]
            ];
        } catch (\PDOException $e) {
            Logger::error('PDO exception in searchOffers', [
                'message' => $e->getMessage(),
                'criteria' => $criteria,
                'page' => $page,
                'limit' => $limit
            ]);
            throw new DatabaseException('Failed to search job offers', (int)$e->getCode(), $e, [
                'criteria' => $criteria,
                'page' => $page,
                'limit' => $limit
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function acceptOffer($id)
    {
        return $this->updateStatus($id, 'accepted');
    }

    /**
     * {@inheritdoc}
     */
    public function rejectOffer($id)
    {
        return $this->updateStatus($id, 'rejected');
    }

    /**
     * {@inheritdoc}
     */
    public function cancelOffer($id)
    {
        return $this->updateStatus($id, 'cancelled');
    }

    /**
     * {@inheritdoc}
     */
    public function getExpiringOffers($days = 7)
    {
        try {
            $sql = "SELECT o.*, c.company_name, c.logo as company_logo, d.first_name, d.last_name, d.profile_image
                    FROM {$this->table} o
                    LEFT JOIN companies c ON o.company_id = c.id
                    LEFT JOIN drivers d ON o.driver_id = d.id
                    WHERE o.status = 'pending'
                    AND o.start_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :days DAY)
                    ORDER BY o.start_date ASC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':days', $days, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            Logger::error('PDO exception in getExpiringOffers', [
                'message' => $e->getMessage(),
                'days' => $days
            ]);
            throw new DatabaseException('Failed to get expiring job offers', (int)$e->getCode(), $e, ['days' => $days]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRecentOffers($limit = 10)
    {
        try {
            $sql = "SELECT o.*, c.company_name, c.logo as company_logo, d.first_name, d.last_name, d.profile_image
                    FROM {$this->table} o
                    LEFT JOIN companies c ON o.company_id = c.id
                    LEFT JOIN drivers d ON o.driver_id = d.id
                    ORDER BY o.created_at DESC
                    LIMIT :limit";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            Logger::error('PDO exception in getRecentOffers', [
                'message' => $e->getMessage(),
                'limit' => $limit
            ]);
            throw new DatabaseException('Failed to get recent job offers', (int)$e->getCode(), $e, ['limit' => $limit]);
        }
    }
}

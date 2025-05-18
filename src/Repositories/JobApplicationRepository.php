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
        'company_id',
        'message',
        'status',
        'created_at',
        'updated_at'
    ];

    /**
     * @var array Τα πεδία που δεν μπορούν να ενημερωθούν
     */
    protected $guarded = [
        'id'
    ];

    /**
     * Constructor
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }


    /**
     * Βρίσκει μια αίτηση εργασίας με βάση τον οδηγό και την αγγελία
     * 
     * @param int $driverId Το ID του οδηγού
     * @param int $listingId Το ID της αγγελίας
     * @return array|null Τα δεδομένα της αίτησης ή null αν δεν βρέθηκε
     */
    public function findByDriverAndListing($driverId, $listingId)
    {
        try {
            // Δημιουργία του SQL ερωτήματος
            $sql = "SELECT * FROM {$this->table} WHERE driver_id = ? AND job_listing_id = ?";

            // Εκτέλεση του ερωτήματος
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$driverId, $listingId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ?: null;
        } catch (\PDOException $e) {
            Logger::error('PDO exception in findByDriverAndListing', [
                'message' => $e->getMessage(),
                'driver_id' => $driverId,
                'listing_id' => $listingId
            ]);
            throw new DatabaseException('Failed to find job application by driver and listing', (int)$e->getCode(), $e, [
                'driver_id' => $driverId,
                'listing_id' => $listingId
            ]);
        }
    }

    /**
     * Βρίσκει τις αιτήσεις εργασίας ενός οδηγού
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

            // Μέτρηση του συνολικού αριθμού αιτήσεων
            $countSql = "SELECT COUNT(*) FROM {$this->table} WHERE driver_id = ?";
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute([$driverId]);
            $totalCount = $countStmt->fetchColumn();

            // Δημιουργία του SQL ερωτήματος για τις αιτήσεις
            $sql = "SELECT a.*, j.title as job_title, j.location as job_location, c.name as company_name
                    FROM {$this->table} a
                    JOIN job_listings j ON a.job_listing_id = j.id
                    JOIN companies c ON a.company_id = c.id
                    WHERE a.driver_id = ?
                    ORDER BY a.created_at DESC
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
            throw new DatabaseException('Failed to find job applications by driver', (int)$e->getCode(), $e, [
                'driver_id' => $driverId,
                'page' => $page,
                'limit' => $limit
            ]);
        }
    }

    /**
     * Βρίσκει τις αιτήσεις εργασίας μιας εταιρείας
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

            // Μέτρηση του συνολικού αριθμού αιτήσεων
            $countSql = "SELECT COUNT(*) FROM {$this->table} WHERE company_id = ?";
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute([$companyId]);
            $totalCount = $countStmt->fetchColumn();

            // Δημιουργία του SQL ερωτήματος για τις αιτήσεις
            $sql = "SELECT a.*, j.title as job_title, j.location as job_location, d.first_name, d.last_name
                    FROM {$this->table} a
                    JOIN job_listings j ON a.job_listing_id = j.id
                    JOIN drivers d ON a.driver_id = d.id
                    WHERE a.company_id = ?
                    ORDER BY a.created_at DESC
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
            throw new DatabaseException('Failed to find job applications by company', (int)$e->getCode(), $e, [
                'company_id' => $companyId,
                'page' => $page,
                'limit' => $limit
            ]);
        }
    }

    /**
     * Βρίσκει τις αιτήσεις εργασίας για μια αγγελία
     * 
     * @param int $listingId Το ID της αγγελίας
     * @param int $page Η σελίδα των αποτελεσμάτων
     * @param int $limit Ο αριθμός των αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findByListing($listingId, $page = 1, $limit = 10)
    {
        try {
            // Υπολογισμός του offset
            $offset = ($page - 1) * $limit;

            // Μέτρηση του συνολικού αριθμού αιτήσεων
            $countSql = "SELECT COUNT(*) FROM {$this->table} WHERE job_listing_id = ?";
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute([$listingId]);
            $totalCount = $countStmt->fetchColumn();

            // Δημιουργία του SQL ερωτήματος για τις αιτήσεις
            $sql = "SELECT a.*, d.first_name, d.last_name, d.email, d.phone, d.profile_image, d.rating
                    FROM {$this->table} a
                    JOIN drivers d ON a.driver_id = d.id
                    WHERE a.job_listing_id = ?
                    ORDER BY a.created_at DESC
                    LIMIT ? OFFSET ?";

            // Εκτέλεση του ερωτήματος
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$listingId, $limit, $offset]);
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
            Logger::error('PDO exception in findByListing', [
                'message' => $e->getMessage(),
                'listing_id' => $listingId,
                'page' => $page,
                'limit' => $limit
            ]);
            throw new DatabaseException('Failed to find job applications by listing', (int)$e->getCode(), $e, [
                'listing_id' => $listingId,
                'page' => $page,
                'limit' => $limit
            ]);
        }
    }

    /**
     * Ενημερώνει την κατάσταση μιας αίτησης
     * 
     * @param int $id Το ID της αίτησης
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
                Logger::error('Failed to update job application status', [
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
            throw new DatabaseException('Failed to update job application status', (int)$e->getCode(), $e, [
                'id' => $id,
                'status' => $status
            ]);
        }
    }
}

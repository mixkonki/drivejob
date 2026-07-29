<?php

namespace Drivejob\Repositories;

use PDO;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Repository για τις ειδοποιήσεις
 */
class NotificationRepository extends BaseRepository implements NotificationRepositoryInterface
{
    /**
     * @var string Το όνομα του πίνακα
     */
    protected $table = 'notifications';

    /**
     * @var array Τα πεδία που μπορούν να ενημερωθούν
     */
    protected $fillable = [
        'user_id',
        'user_type',
        'type',
        'title',
        'message',
        'link',
        'is_read',
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
    public function findByUser($userId, $userType, $unreadOnly = false, $page = 1, $limit = 10)
    {
        try {
            $query = "SELECT * FROM {$this->table} WHERE user_id = :user_id AND user_type = :user_type";
            $params = [
                'user_id' => $userId,
                'user_type' => $userType
            ];

            if ($unreadOnly) {
                $query .= " AND is_read = 0";
            }

            $query .= " ORDER BY created_at DESC";

            // Μέτρηση συνολικών αποτελεσμάτων
            $countQuery = "SELECT COUNT(*) FROM {$this->table} WHERE user_id = :user_id AND user_type = :user_type";
            if ($unreadOnly) {
                $countQuery .= " AND is_read = 0";
            }
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
     * {@inheritdoc}
     */
    public function markAsRead($id)
    {
        try {
            $query = "UPDATE {$this->table} SET is_read = 1, updated_at = NOW() WHERE id = :id";
            return $this->execute($query, ['id' => $id]) > 0;
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['id' => $id]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function markAllAsRead($userId, $userType)
    {
        try {
            $query = "UPDATE {$this->table} SET is_read = 1, updated_at = NOW() WHERE user_id = :user_id AND user_type = :user_type AND is_read = 0";
            $params = [
                'user_id' => $userId,
                'user_type' => $userType
            ];
            return $this->execute($query, $params) > 0;
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, $params ?? []);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createNotification(array $data)
    {
        // Προσθήκη των προεπιλεγμένων τιμών
        $data['is_read'] = $data['is_read'] ?? 0;
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        return $this->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function countUnread($userId, $userType)
    {
        try {
            $query = "SELECT COUNT(*) FROM {$this->table} WHERE user_id = :user_id AND user_type = :user_type AND is_read = 0";
            $params = [
                'user_id' => $userId,
                'user_type' => $userType
            ];
            return (int) $this->queryScalar($query, $params);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, $params ?? []);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteOldNotifications($days = 30)
    {
        try {
            $query = "DELETE FROM {$this->table} WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";
            return $this->execute($query, ['days' => $days]);
        } catch (\PDOException $e) {
            throw DatabaseException::fromPDOException($e, $query ?? null, ['days' => $days]);
        }
    }

    /**
     * Δημιουργεί μια ειδοποίηση για νέα αγγελία
     * 
     * @param int $jobListingId Το ID της αγγελίας
     * @param string $title Ο τίτλος της αγγελίας
     * @param string $companyName Το όνομα της εταιρείας
     * @param array $targetDrivers Τα IDs των οδηγών που θα λάβουν την ειδοποίηση
     * @return bool Επιτυχία/αποτυχία
     */
    public function notifyNewJobListing($jobListingId, $title, $companyName, array $targetDrivers)
    {
        try {
            $this->pdo->beginTransaction();

            $notificationTitle = 'Νέα αγγελία εργασίας';
            $notificationMessage = "Η εταιρεία {$companyName} δημοσίευσε μια νέα αγγελία: {$title}";
            $notificationLink = BASE_URL . "job-listings/show/{$jobListingId}";

            foreach ($targetDrivers as $driverId) {
                $data = [
                    'user_id' => $driverId,
                    'user_type' => 'driver',
                    'type' => 'new_job_listing',
                    'title' => $notificationTitle,
                    'message' => $notificationMessage,
                    'link' => $notificationLink,
                    'is_read' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $this->createNotification($data);
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Δημιουργεί μια ειδοποίηση για νέα αίτηση εργασίας
     * 
     * @param int $jobApplicationId Το ID της αίτησης
     * @param int $jobListingId Το ID της αγγελίας
     * @param string $driverName Το όνομα του οδηγού
     * @param int $companyId Το ID της εταιρείας
     * @return bool Επιτυχία/αποτυχία
     */
    public function notifyNewJobApplication($jobApplicationId, $jobListingId, $driverName, $companyId)
    {
        try {
            $notificationTitle = 'Νέα αίτηση εργασίας';
            $notificationMessage = "Ο οδηγός {$driverName} έκανε αίτηση για την αγγελία σας";
            $notificationLink = BASE_URL . "job-applications/show/{$jobApplicationId}";

            $data = [
                'user_id' => $companyId,
                'user_type' => 'company',
                'type' => 'new_job_application',
                'title' => $notificationTitle,
                'message' => $notificationMessage,
                'link' => $notificationLink,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            return $this->createNotification($data) !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Δημιουργεί μια ειδοποίηση για νέα προσφορά εργασίας
     * 
     * @param int $jobOfferId Το ID της προσφοράς
     * @param string $companyName Το όνομα της εταιρείας
     * @param int $driverId Το ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function notifyNewJobOffer($jobOfferId, $companyName, $driverId)
    {
        try {
            $notificationTitle = 'Νέα προσφορά εργασίας';
            $notificationMessage = "Η εταιρεία {$companyName} σας έστειλε μια προσφορά εργασίας";
            $notificationLink = BASE_URL . "job-offers/view/{$jobOfferId}";

            $data = [
                'user_id' => $driverId,
                'user_type' => 'driver',
                'type' => 'new_job_offer',
                'title' => $notificationTitle,
                'message' => $notificationMessage,
                'link' => $notificationLink,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            return $this->createNotification($data) !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Δημιουργεί μια ειδοποίηση για αποδοχή αίτησης εργασίας
     * 
     * @param int $jobApplicationId Το ID της αίτησης
     * @param string $companyName Το όνομα της εταιρείας
     * @param int $driverId Το ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function notifyJobApplicationAccepted($jobApplicationId, $companyName, $driverId)
    {
        try {
            $notificationTitle = 'Αποδοχή αίτησης εργασίας';
            $notificationMessage = "Η εταιρεία {$companyName} αποδέχτηκε την αίτησή σας";
            $notificationLink = BASE_URL . "job-applications/show/{$jobApplicationId}";

            $data = [
                'user_id' => $driverId,
                'user_type' => 'driver',
                'type' => 'job_application_accepted',
                'title' => $notificationTitle,
                'message' => $notificationMessage,
                'link' => $notificationLink,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            return $this->createNotification($data) !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Δημιουργεί μια ειδοποίηση για απόρριψη αίτησης εργασίας
     * 
     * @param int $jobApplicationId Το ID της αίτησης
     * @param string $companyName Το όνομα της εταιρείας
     * @param int $driverId Το ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function notifyJobApplicationRejected($jobApplicationId, $companyName, $driverId)
    {
        try {
            $notificationTitle = 'Απόρριψη αίτησης εργασίας';
            $notificationMessage = "Η εταιρεία {$companyName} απέρριψε την αίτησή σας";
            $notificationLink = BASE_URL . "job-applications/show/{$jobApplicationId}";

            $data = [
                'user_id' => $driverId,
                'user_type' => 'driver',
                'type' => 'job_application_rejected',
                'title' => $notificationTitle,
                'message' => $notificationMessage,
                'link' => $notificationLink,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            return $this->createNotification($data) !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Δημιουργεί μια ειδοποίηση για αποδοχή προσφοράς εργασίας
     * 
     * @param int $jobOfferId Το ID της προσφοράς
     * @param string $driverName Το όνομα του οδηγού
     * @param int $companyId Το ID της εταιρείας
     * @return bool Επιτυχία/αποτυχία
     */
    public function notifyJobOfferAccepted($jobOfferId, $driverName, $companyId)
    {
        try {
            $notificationTitle = 'Αποδοχή προσφοράς εργασίας';
            $notificationMessage = "Ο οδηγός {$driverName} αποδέχτηκε την προσφορά σας";
            $notificationLink = BASE_URL . "job-offers/view/{$jobOfferId}";

            $data = [
                'user_id' => $companyId,
                'user_type' => 'company',
                'type' => 'job_offer_accepted',
                'title' => $notificationTitle,
                'message' => $notificationMessage,
                'link' => $notificationLink,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            return $this->createNotification($data) !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Δημιουργεί μια ειδοποίηση για απόρριψη προσφοράς εργασίας
     * 
     * @param int $jobOfferId Το ID της προσφοράς
     * @param string $driverName Το όνομα του οδηγού
     * @param int $companyId Το ID της εταιρείας
     * @return bool Επιτυχία/αποτυχία
     */
    public function notifyJobOfferRejected($jobOfferId, $driverName, $companyId)
    {
        try {
            $notificationTitle = 'Απόρριψη προσφοράς εργασίας';
            $notificationMessage = "Ο οδηγός {$driverName} απέρριψε την προσφορά σας";
            $notificationLink = BASE_URL . "job-offers/view/{$jobOfferId}";

            $data = [
                'user_id' => $companyId,
                'user_type' => 'company',
                'type' => 'job_offer_rejected',
                'title' => $notificationTitle,
                'message' => $notificationMessage,
                'link' => $notificationLink,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            return $this->createNotification($data) !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
}

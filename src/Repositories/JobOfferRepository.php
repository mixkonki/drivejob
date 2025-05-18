<?php

namespace Drivejob\Repositories;

use PDO;
use Drivejob\Core\Logger;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Repository για τις προσφορές εργασίας
 */
class JobOfferRepository
{
    /**
     * @var PDO Η σύνδεση με τη βάση δεδομένων
     */
    private $pdo;

    /**
     * @var string Το όνομα του πίνακα
     */
    private $table = 'job_offers';

    /**
     * Constructor
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

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
}

<?php

namespace Drivejob\Repositories;

use Drivejob\Core\Database;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Repository για τις αιτήσεις εργασίας
 */
class JobApplicationRepository
{
    /**
     * @var Database Η σύνδεση με τη βάση δεδομένων
     */
    private $db;

    /**
     * Constructor
     *
     * @param Database|null $db Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct(Database $db = null)
    {
        $this->db = $db ?? new Database();
    }

    /**
     * Βρίσκει μια αίτηση εργασίας με βάση το ID της
     *
     * @param int $id Το ID της αίτησης εργασίας
     * @return array|null Τα στοιχεία της αίτησης εργασίας ή null αν δεν βρεθεί
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function find(int $id): ?array
    {
        try {
            $sql = "SELECT * FROM job_applications WHERE id = :id";
            $params = [':id' => $id];

            $result = $this->db->query($sql, $params);
            $application = $result->fetch(\PDO::FETCH_ASSOC);

            return $application ?: null;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την εύρεση της αίτησης εργασίας: " . $e->getMessage());
        }
    }

    /**
     * Βρίσκει όλες τις αιτήσεις εργασίας ενός οδηγού
     *
     * @param int $driverId Το ID του οδηγού
     * @param int $page Η σελίδα των αποτελεσμάτων
     * @param int $limit Ο αριθμός των αποτελεσμάτων ανά σελίδα
     * @return array Οι αιτήσεις εργασίας του οδηγού
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function findByDriver(int $driverId, int $page = 1, int $limit = 10): array
    {
        try {
            // Υπολογισμός του offset
            $offset = ($page - 1) * $limit;

            // Εύρεση του συνολικού αριθμού αιτήσεων
            $countSql = "SELECT COUNT(*) as total FROM job_applications WHERE driver_id = :driver_id";
            $countParams = [':driver_id' => $driverId];
            $countResult = $this->db->query($countSql, $countParams);
            $total = $countResult->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0;

            // Εύρεση των αιτήσεων
            $sql = "SELECT ja.*, jl.title, jl.location, jl.job_type, c.company_name
                    FROM job_applications ja
                    JOIN job_listings jl ON ja.job_listing_id = jl.id
                    JOIN companies c ON jl.company_id = c.id
                    WHERE ja.driver_id = :driver_id
                    ORDER BY ja.created_at DESC
                    LIMIT :limit OFFSET :offset";

            $params = [
                ':driver_id' => $driverId,
                ':limit' => $limit,
                ':offset' => $offset
            ];

            $result = $this->db->query($sql, $params);
            $applications = $result->fetchAll(\PDO::FETCH_ASSOC);

            // Υπολογισμός των σελίδων
            $totalPages = ceil($total / $limit);

            return [
                'results' => $applications,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $limit,
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'has_more' => $page < $totalPages
                ]
            ];
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την εύρεση των αιτήσεων εργασίας: " . $e->getMessage());
        }
    }

    /**
     * Βρίσκει όλες τις αιτήσεις εργασίας για μια αγγελία
     *
     * @param int $jobListingId Το ID της αγγελίας
     * @param int $page Η σελίδα των αποτελεσμάτων
     * @param int $limit Ο αριθμός των αποτελεσμάτων ανά σελίδα
     * @return array Οι αιτήσεις εργασίας για την αγγελία
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function findByJobListing(int $jobListingId, int $page = 1, int $limit = 10): array
    {
        try {
            // Υπολογισμός του offset
            $offset = ($page - 1) * $limit;

            // Εύρεση του συνολικού αριθμού αιτήσεων
            $countSql = "SELECT COUNT(*) as total FROM job_applications WHERE job_listing_id = :job_listing_id";
            $countParams = [':job_listing_id' => $jobListingId];
            $countResult = $this->db->query($countSql, $countParams);
            $total = $countResult->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0;

            // Εύρεση των αιτήσεων
            $sql = "SELECT ja.*, d.first_name, d.last_name, d.email, d.phone, d.city
                    FROM job_applications ja
                    JOIN drivers d ON ja.driver_id = d.id
                    WHERE ja.job_listing_id = :job_listing_id
                    ORDER BY ja.created_at DESC
                    LIMIT :limit OFFSET :offset";

            $params = [
                ':job_listing_id' => $jobListingId,
                ':limit' => $limit,
                ':offset' => $offset
            ];

            $result = $this->db->query($sql, $params);
            $applications = $result->fetchAll(\PDO::FETCH_ASSOC);

            // Υπολογισμός των σελίδων
            $totalPages = ceil($total / $limit);

            return [
                'results' => $applications,
                'pagination' => [
                    'total' => $total,
                    'per_page' => $limit,
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'has_more' => $page < $totalPages
                ]
            ];
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την εύρεση των αιτήσεων εργασίας: " . $e->getMessage());
        }
    }

    /**
     * Ελέγχει αν ένας οδηγός έχει ήδη υποβάλει αίτηση για μια αγγελία
     *
     * @param int $driverId Το ID του οδηγού
     * @param int $jobListingId Το ID της αγγελίας
     * @return bool Αν ο οδηγός έχει ήδη υποβάλει αίτηση
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function hasApplied(int $driverId, int $jobListingId): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM job_applications 
                    WHERE driver_id = :driver_id AND job_listing_id = :job_listing_id";
            $params = [
                ':driver_id' => $driverId,
                ':job_listing_id' => $jobListingId
            ];

            $result = $this->db->query($sql, $params);
            $count = $result->fetch(\PDO::FETCH_ASSOC)['count'] ?? 0;

            return $count > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τον έλεγχο της αίτησης εργασίας: " . $e->getMessage());
        }
    }

    /**
     * Δημιουργεί μια νέα αίτηση εργασίας
     *
     * @param array $data Τα δεδομένα της αίτησης εργασίας
     * @return int Το ID της νέας αίτησης εργασίας
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function create(array $data): int
    {
        try {
            $sql = "INSERT INTO job_applications (driver_id, job_listing_id, cover_letter, status, created_at, updated_at)
                    VALUES (:driver_id, :job_listing_id, :cover_letter, :status, NOW(), NOW())";

            $params = [
                ':driver_id' => $data['driver_id'],
                ':job_listing_id' => $data['job_listing_id'],
                ':cover_letter' => $data['cover_letter'] ?? null,
                ':status' => $data['status'] ?? 'pending'
            ];

            $this->db->query($sql, $params);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη δημιουργία της αίτησης εργασίας: " . $e->getMessage());
        }
    }

    /**
     * Ενημερώνει μια υπάρχουσα αίτηση εργασίας
     *
     * @param int $id Το ID της αίτησης εργασίας
     * @param array $data Τα νέα δεδομένα της αίτησης εργασίας
     * @return bool Αν η ενημέρωση ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function update(int $id, array $data): bool
    {
        try {
            $sql = "UPDATE job_applications SET
                    cover_letter = :cover_letter,
                    status = :status,
                    updated_at = NOW()
                    WHERE id = :id";

            $params = [
                ':id' => $id,
                ':cover_letter' => $data['cover_letter'] ?? null,
                ':status' => $data['status'] ?? 'pending'
            ];

            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την ενημέρωση της αίτησης εργασίας: " . $e->getMessage());
        }
    }

    /**
     * Διαγράφει μια αίτηση εργασίας
     *
     * @param int $id Το ID της αίτησης εργασίας
     * @return bool Αν η διαγραφή ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM job_applications WHERE id = :id";
            $params = [':id' => $id];

            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη διαγραφή της αίτησης εργασίας: " . $e->getMessage());
        }
    }

    /**
     * Διαγράφει όλες τις αιτήσεις εργασίας ενός οδηγού
     *
     * @param int $driverId Το ID του οδηγού
     * @return bool Αν η διαγραφή ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function deleteByDriver(int $driverId): bool
    {
        try {
            $sql = "DELETE FROM job_applications WHERE driver_id = :driver_id";
            $params = [':driver_id' => $driverId];

            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη διαγραφή των αιτήσεων εργασίας: " . $e->getMessage());
        }
    }

    /**
     * Διαγράφει όλες τις αιτήσεις εργασίας για μια αγγελία
     *
     * @param int $jobListingId Το ID της αγγελίας
     * @return bool Αν η διαγραφή ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function deleteByJobListing(int $jobListingId): bool
    {
        try {
            $sql = "DELETE FROM job_applications WHERE job_listing_id = :job_listing_id";
            $params = [':job_listing_id' => $jobListingId];

            $stmt = $this->db->query($sql, $params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη διαγραφή των αιτήσεων εργασίας: " . $e->getMessage());
        }
    }
}

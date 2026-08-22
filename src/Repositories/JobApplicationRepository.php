<?php

namespace Drivejob\Repositories;

use PDO;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Repository για τις αιτήσεις εργασίας
 */
class JobApplicationRepository
{
    /**
     * @var PDO Η σύνδεση με τη βάση δεδομένων
     */
    private $db;

    /**
     * Constructor
     *
     * @param PDO $db Η σύνδεση με τη βάση δεδομένων
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
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

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $application = $stmt->fetch(PDO::FETCH_ASSOC);

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
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($countParams);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

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

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($countParams);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

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

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

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
            // Η στήλη του κειμένου ονομάζεται message — το cover_letter
            // γινόταν δεκτό από τους controllers αλλά δεν υπάρχει στον πίνακα.
            $message = $data['message'] ?? $data['cover_letter'] ?? null;

            $sql = "INSERT INTO job_applications (driver_id, job_listing_id, message, status, created_at, updated_at)
                    VALUES (:driver_id, :job_listing_id, :message, :status, NOW(), NOW())";

            $params = [
                ':driver_id' => $data['driver_id'],
                ':job_listing_id' => $data['job_listing_id'],
                ':message' => $message,
                ':status' => $data['status'] ?? 'pending'
            ];

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
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
            // Ενημερώνονται μόνο τα πεδία που δόθηκαν — η παλιά έκδοση
            // έγραφε πάντα cover_letter (ανύπαρκτη στήλη) και μηδένιζε
            // το μήνυμα του υποψηφίου σε κάθε αλλαγή κατάστασης.
            $fields = [];
            $params = [':id' => $id];

            if (array_key_exists('status', $data)) {
                $fields[] = 'status = :status';
                $params[':status'] = $data['status'];
            }

            if (array_key_exists('message', $data) || array_key_exists('cover_letter', $data)) {
                $fields[] = 'message = :message';
                $params[':message'] = $data['message'] ?? $data['cover_letter'];
            }

            if (empty($fields)) {
                return false;
            }

            $fields[] = 'updated_at = NOW()';

            $sql = 'UPDATE job_applications SET ' . implode(', ', $fields) . ' WHERE id = :id';

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            // rowCount() = 0 όταν η τιμή δεν άλλαξε — δεν είναι σφάλμα.
            return $stmt->errorCode() === '00000';
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

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
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

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
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

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη διαγραφή των αιτήσεων εργασίας: " . $e->getMessage());
        }
    }

    /**
     * Βρίσκει τις αιτήσεις για όλες τις αγγελίες μιας εταιρείας.
     *
     * ΣΗΜΕΙΩΣΗ: ο πίνακας job_applications ΔΕΝ έχει στήλη company_id — η
     * εταιρεία προκύπτει μέσω της αγγελίας. Η μέθοδος καλούνταν από τον
     * Company\JobApplicationController αλλά δεν υπήρχε ποτέ, οπότε η σελίδα
     * «αιτήσεις προς την εταιρεία» δεν λειτούργησε.
     *
     * @throws DatabaseException
     */
    public function findByCompany(int $companyId, int $page = 1, int $limit = 10): array
    {
        try {
            $offset = ($page - 1) * $limit;

            $countStmt = $this->db->prepare(
                "SELECT COUNT(*) AS total
                 FROM job_applications ja
                 JOIN job_listings jl ON ja.job_listing_id = jl.id
                 WHERE jl.company_id = :company_id"
            );
            $countStmt->execute([':company_id' => $companyId]);
            $total = (int) ($countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

            $stmt = $this->db->prepare(
                "SELECT ja.*,
                        jl.title, jl.location, jl.job_type,
                        d.first_name, d.last_name, d.email, d.phone, d.city
                 FROM job_applications ja
                 JOIN job_listings jl ON ja.job_listing_id = jl.id
                 JOIN drivers d ON ja.driver_id = d.id
                 WHERE jl.company_id = :company_id
                 ORDER BY ja.created_at DESC
                 LIMIT :limit OFFSET :offset"
            );
            $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return $this->paginate($stmt->fetchAll(PDO::FETCH_ASSOC), $total, $page, $limit);
        } catch (\PDOException $e) {
            throw new DatabaseException('Σφάλμα κατά την εύρεση των αιτήσεων της εταιρείας: ' . $e->getMessage());
        }
    }

    /**
     * Συνώνυμο της findByJobListing — οι controllers την καλούν έτσι.
     *
     * @throws DatabaseException
     */
    public function findByListing(int $jobListingId, int $page = 1, int $limit = 10): array
    {
        return $this->findByJobListing($jobListingId, $page, $limit);
    }

    /**
     * Η αίτηση ενός συγκεκριμένου οδηγού για μια συγκεκριμένη αγγελία.
     *
     * Χρησιμοποιείται πριν από νέα αίτηση, ώστε να μην υποβληθεί δεύτερη.
     *
     * @throws DatabaseException
     */
    public function findByDriverAndListing(int $driverId, int $jobListingId): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM job_applications
                 WHERE driver_id = :driver_id AND job_listing_id = :job_listing_id
                 LIMIT 1"
            );
            $stmt->execute([
                ':driver_id' => $driverId,
                ':job_listing_id' => $jobListingId,
            ]);

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            throw new DatabaseException('Σφάλμα κατά την εύρεση της αίτησης: ' . $e->getMessage());
        }
    }

    /** Κοινή δομή σελιδοποίησης, ίδια με τις υπόλοιπες μεθόδους. */
    private function paginate(array $results, int $total, int $page, int $limit): array
    {
        $totalPages = $limit > 0 ? (int) ceil($total / $limit) : 0;

        return [
            'results' => $results,
            'pagination' => [
                'total' => $total,
                'per_page' => $limit,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages,
            ],
        ];
    }
}

<?php

namespace Drivejob\Repositories;

use Drivejob\Core\Database;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Repository για τα tags των αγγελιών
 */
class JobTagRepository
{
    private $db;

    /**
     * Constructor
     *
     * @param Database $db
     */
    public function __construct(Database $db = null)
    {
        $this->db = $db ?? new Database();
    }

    /**
     * Δημιουργεί ένα νέο tag
     *
     * @param array $data Τα δεδομένα του tag
     * @return int Το ID του νέου tag
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function create(array $data)
    {
        try {
            $sql = "INSERT INTO job_tags (job_listing_id, tag) VALUES (:job_listing_id, :tag)";
            $params = [
                ':job_listing_id' => $data['job_listing_id'],
                ':tag' => $data['tag']
            ];

            $this->db->query($sql, $params);
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη δημιουργία του tag: " . $e->getMessage());
        }
    }

    /**
     * Διαγράφει όλα τα tags μιας αγγελίας
     *
     * @param int $jobListingId Το ID της αγγελίας
     * @return bool Αν η διαγραφή ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function deleteByJobListing(int $jobListingId)
    {
        try {
            $sql = "DELETE FROM job_tags WHERE job_listing_id = :job_listing_id";
            $params = [':job_listing_id' => $jobListingId];

            $this->db->query($sql, $params);
            return true;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά τη διαγραφή των tags: " . $e->getMessage());
        }
    }

    /**
     * Βρίσκει όλα τα tags μιας αγγελίας
     *
     * @param int $jobListingId Το ID της αγγελίας
     * @return array Τα tags της αγγελίας
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function findByJobListing(int $jobListingId)
    {
        try {
            $sql = "SELECT * FROM job_tags WHERE job_listing_id = :job_listing_id";
            $params = [':job_listing_id' => $jobListingId];

            $result = $this->db->query($sql, $params);
            return $result->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την εύρεση των tags: " . $e->getMessage());
        }
    }
}

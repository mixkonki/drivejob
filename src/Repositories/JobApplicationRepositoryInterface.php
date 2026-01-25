<?php

namespace Drivejob\Repositories;

/**
 * Interface για το repository των αιτήσεων εργασίας
 */
interface JobApplicationRepositoryInterface extends RepositoryInterface
{
    /**
     * Βρίσκει μια αίτηση εργασίας με βάση την αγγελία και τον οδηγό
     * 
     * @param int $jobListingId Το ID της αγγελίας
     * @param int $driverId Το ID του οδηγού
     * @return array|null Τα δεδομένα της αίτησης ή null αν δεν βρέθηκε
     */
    public function findByListingAndDriver($jobListingId, $driverId);

    /**
     * Βρίσκει τις αιτήσεις εργασίας μιας αγγελίας
     * 
     * @param int $jobListingId Το ID της αγγελίας
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findByListing($jobListingId, $page = 1, $limit = 10);

    /**
     * Βρίσκει τις αιτήσεις εργασίας ενός οδηγού
     * 
     * @param int $driverId Το ID του οδηγού
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findByDriver($driverId, $page = 1, $limit = 10);

    /**
     * Βρίσκει τις αιτήσεις εργασίας μιας εταιρείας
     * 
     * @param int $companyId Το ID της εταιρείας
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findByCompany($companyId, $page = 1, $limit = 10);

    /**
     * Αναζητά αιτήσεις εργασίας με βάση διάφορα κριτήρια
     * 
     * @param array $criteria Τα κριτήρια αναζήτησης
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function searchApplications(array $criteria = [], $page = 1, $limit = 10);

    /**
     * Αποδέχεται μια αίτηση εργασίας
     * 
     * @param int $id Το ID της αίτησης
     * @return bool Επιτυχία/αποτυχία
     */
    public function acceptApplication($id);

    /**
     * Απορρίπτει μια αίτηση εργασίας
     * 
     * @param int $id Το ID της αίτησης
     * @return bool Επιτυχία/αποτυχία
     */
    public function rejectApplication($id);

    /**
     * Ακυρώνει μια αίτηση εργασίας
     * 
     * @param int $id Το ID της αίτησης
     * @return bool Επιτυχία/αποτυχία
     */
    public function cancelApplication($id);

    /**
     * Ενημερώνει την κατάσταση μιας αίτησης εργασίας
     * 
     * @param int $id Το ID της αίτησης
     * @param string $status Η νέα κατάσταση
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateStatus($id, $status);

    /**
     * Επιστρέφει τις πρόσφατες αιτήσεις εργασίας
     * 
     * @param int $limit Ο αριθμός αποτελεσμάτων
     * @return array Οι πρόσφατες αιτήσεις εργασίας
     */
    public function getRecentApplications($limit = 10);

    /**
     * Επιστρέφει τις εκκρεμείς αιτήσεις εργασίας
     * 
     * @param int $limit Ο αριθμός αποτελεσμάτων
     * @return array Οι εκκρεμείς αιτήσεις εργασίας
     */
    public function getPendingApplications($limit = 10);
}

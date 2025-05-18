<?php

namespace Drivejob\Repositories;

/**
 * Interface για το repository των αιτήσεων εργασίας
 */
interface JobApplicationRepositoryInterface extends RepositoryInterface
{
    /**
     * Βρίσκει μια αίτηση εργασίας με βάση τον οδηγό και την αγγελία
     * 
     * @param int $driverId Το ID του οδηγού
     * @param int $listingId Το ID της αγγελίας
     * @return array|null Τα δεδομένα της αίτησης ή null αν δεν βρέθηκε
     */
    public function findByDriverAndListing($driverId, $listingId);

    /**
     * Βρίσκει τις αιτήσεις εργασίας ενός οδηγού
     * 
     * @param int $driverId Το ID του οδηγού
     * @param int $page Η σελίδα των αποτελεσμάτων
     * @param int $limit Ο αριθμός των αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findByDriver($driverId, $page = 1, $limit = 10);

    /**
     * Βρίσκει τις αιτήσεις εργασίας μιας εταιρείας
     * 
     * @param int $companyId Το ID της εταιρείας
     * @param int $page Η σελίδα των αποτελεσμάτων
     * @param int $limit Ο αριθμός των αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findByCompany($companyId, $page = 1, $limit = 10);

    /**
     * Βρίσκει τις αιτήσεις εργασίας για μια αγγελία
     * 
     * @param int $listingId Το ID της αγγελίας
     * @param int $page Η σελίδα των αποτελεσμάτων
     * @param int $limit Ο αριθμός των αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findByListing($listingId, $page = 1, $limit = 10);

    /**
     * Ενημερώνει την κατάσταση μιας αίτησης
     * 
     * @param int $id Το ID της αίτησης
     * @param string $status Η νέα κατάσταση
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateStatus($id, $status);
}

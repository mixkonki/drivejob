<?php

namespace Drivejob\Repositories;

/**
 * Interface για το repository ταιριάσματος αγγελιών
 */
interface MatchingRepositoryInterface extends RepositoryInterface
{
    /**
     * Βρίσκει αγγελίες εταιρειών που ταιριάζουν με τα κριτήρια ενός οδηγού
     * 
     * @param int $driverId Το ID του οδηγού
     * @param array $criteria Επιπλέον κριτήρια αναζήτησης
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findMatchingJobsForDriver($driverId, array $criteria = [], $page = 1, $limit = 10);

    /**
     * Βρίσκει αγγελίες οδηγών που ταιριάζουν με τα κριτήρια μιας εταιρείας
     * 
     * @param int $companyId Το ID της εταιρείας
     * @param array $criteria Επιπλέον κριτήρια αναζήτησης
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findMatchingDriversForCompany($companyId, array $criteria = [], $page = 1, $limit = 10);

    /**
     * Βρίσκει αγγελίες εταιρειών που ταιριάζουν με μια αγγελία οδηγού
     * 
     * @param int $driverListingId Το ID της αγγελίας του οδηγού
     * @param array $criteria Επιπλέον κριτήρια αναζήτησης
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findMatchingJobsForDriverListing($driverListingId, array $criteria = [], $page = 1, $limit = 10);

    /**
     * Βρίσκει αγγελίες οδηγών που ταιριάζουν με μια αγγελία εταιρείας
     * 
     * @param int $companyListingId Το ID της αγγελίας της εταιρείας
     * @param array $criteria Επιπλέον κριτήρια αναζήτησης
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findMatchingDriversForCompanyListing($companyListingId, array $criteria = [], $page = 1, $limit = 10);

    /**
     * Υπολογίζει το σκορ ταιριάσματος μεταξύ ενός οδηγού και μιας αγγελίας εταιρείας
     * 
     * @param int $driverId Το ID του οδηγού
     * @param int $companyListingId Το ID της αγγελίας της εταιρείας
     * @return float Το σκορ ταιριάσματος (0-100)
     */
    public function calculateMatchScore($driverId, $companyListingId);

    /**
     * Αποθηκεύει ένα ταίριασμα μεταξύ ενός οδηγού και μιας αγγελίας εταιρείας
     * 
     * @param int $driverId Το ID του οδηγού
     * @param int $companyListingId Το ID της αγγελίας της εταιρείας
     * @param float $score Το σκορ ταιριάσματος
     * @return int|false Το ID του ταιριάσματος ή false σε περίπτωση αποτυχίας
     */
    public function saveMatch($driverId, $companyListingId, $score);

    /**
     * Βρίσκει τα ταιριάσματα ενός οδηγού
     * 
     * @param int $driverId Το ID του οδηγού
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findDriverMatches($driverId, $page = 1, $limit = 10);

    /**
     * Βρίσκει τα ταιριάσματα μιας εταιρείας
     * 
     * @param int $companyId Το ID της εταιρείας
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findCompanyMatches($companyId, $page = 1, $limit = 10);
}

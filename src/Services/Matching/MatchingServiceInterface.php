<?php

namespace Drivejob\Services\Matching;

/**
 * Interface για την υπηρεσία ταιριάσματος αγγελιών
 */
interface MatchingServiceInterface
{
    /**
     * Βρίσκει ταιριάσματα αγγελιών για έναν οδηγό
     *
     * @param int $driverId Το ID του οδηγού
     * @param array $criteria Τα κριτήρια αναζήτησης
     * @param int $page Η σελίδα των αποτελεσμάτων
     * @param int $limit Ο αριθμός των αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα της αναζήτησης
     */
    public function findMatchesForDriver(int $driverId, array $criteria = [], int $page = 1, int $limit = 10): array;

    /**
     * Βρίσκει ταιριάσματα οδηγών για μια αγγελία
     *
     * @param int $jobListingId Το ID της αγγελίας
     * @param array $criteria Τα κριτήρια αναζήτησης
     * @param int $page Η σελίδα των αποτελεσμάτων
     * @param int $limit Ο αριθμός των αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα της αναζήτησης
     */
    public function findMatchesForJobListing(int $jobListingId, array $criteria = [], int $page = 1, int $limit = 10): array;

    /**
     * Βρίσκει ταιριάσματα αγγελιών για μια εταιρεία
     *
     * @param int $companyId Το ID της εταιρείας
     * @param array $criteria Τα κριτήρια αναζήτησης
     * @param int $page Η σελίδα των αποτελεσμάτων
     * @param int $limit Ο αριθμός των αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα της αναζήτησης
     */
    public function findMatchesForCompany(int $companyId, array $criteria = [], int $page = 1, int $limit = 10): array;

    /**
     * Αποθηκεύει τις προτιμήσεις ταιριάσματος ενός χρήστη
     *
     * @param int $userId Το ID του χρήστη
     * @param string $userType Ο τύπος του χρήστη (driver ή company)
     * @param array $preferences Οι προτιμήσεις ταιριάσματος
     * @return bool Αν η αποθήκευση ήταν επιτυχής
     */
    public function saveMatchPreferences(int $userId, string $userType, array $preferences): bool;

    /**
     * Λαμβάνει τις προτιμήσεις ταιριάσματος ενός χρήστη
     *
     * @param int $userId Το ID του χρήστη
     * @param string $userType Ο τύπος του χρήστη (driver ή company)
     * @return array Οι προτιμήσεις ταιριάσματος
     */
    public function getMatchPreferences(int $userId, string $userType): array;

    /**
     * Καταγράφει μια ενέργεια ταιριάσματος
     *
     * @param int $driverId Το ID του οδηγού
     * @param int $jobListingId Το ID της αγγελίας
     * @param float $matchScore Το σκορ ταιριάσματος
     * @param string $driverAction Η ενέργεια του οδηγού (viewed, applied, rejected, no_action)
     * @param string $companyAction Η ενέργεια της εταιρείας (viewed, accepted, rejected, no_action)
     * @return bool Αν η καταγραφή ήταν επιτυχής
     */
    public function logMatchAction(int $driverId, int $jobListingId, float $matchScore, string $driverAction = 'no_action', string $companyAction = 'no_action'): bool;

    /**
     * Λαμβάνει το ιστορικό ταιριάσματος ενός οδηγού
     *
     * @param int $driverId Το ID του οδηγού
     * @param int $page Η σελίδα των αποτελεσμάτων
     * @param int $limit Ο αριθμός των αποτελεσμάτων ανά σελίδα
     * @return array Το ιστορικό ταιριάσματος
     */
    public function getDriverMatchHistory(int $driverId, int $page = 1, int $limit = 10): array;

    /**
     * Λαμβάνει το ιστορικό ταιριάσματος μιας αγγελίας
     *
     * @param int $jobListingId Το ID της αγγελίας
     * @param int $page Η σελίδα των αποτελεσμάτων
     * @param int $limit Ο αριθμός των αποτελεσμάτων ανά σελίδα
     * @return array Το ιστορικό ταιριάσματος
     */
    public function getJobListingMatchHistory(int $jobListingId, int $page = 1, int $limit = 10): array;
}

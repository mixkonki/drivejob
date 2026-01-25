<?php

namespace Drivejob\Repositories;

/**
 * Interface για το repository των αγγελιών εργασίας
 */
interface JobListingRepositoryInterface extends RepositoryInterface
{
    /**
     * Επιστρέφει τις αγγελίες μιας εταιρείας
     *
     * @param int $companyId Το ID της εταιρείας
     * @param bool $activeOnly Αν θα επιστραφούν μόνο οι ενεργές αγγελίες
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Οι αγγελίες και οι πληροφορίες σελιδοποίησης
     */
    public function getCompanyListings($companyId, $activeOnly = false, $page = 1, $limit = 10);

    /**
     * Επιστρέφει τις αγγελίες στις οποίες έχει κάνει αίτηση ένας οδηγός
     *
     * @param int $driverId Το ID του οδηγού
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Οι αγγελίες και οι πληροφορίες σελιδοποίησης
     */
    public function getDriverApplications($driverId, $page = 1, $limit = 10);

    /**
     * Αναζητά αγγελίες με βάση διάφορα κριτήρια
     *
     * @param array $criteria Τα κριτήρια αναζήτησης
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function searchListings(array $criteria = [], $page = 1, $limit = 10);

    /**
     * Αυξάνει τον αριθμό προβολών μιας αγγελίας
     *
     * @param int $id Το ID της αγγελίας
     * @return bool Επιτυχία ή αποτυχία
     */
    public function incrementViews($id);

    /**
     * Αυξάνει τον αριθμό αιτήσεων μιας αγγελίας
     *
     * @param int $id Το ID της αγγελίας
     * @return bool Επιτυχία ή αποτυχία
     */
    public function incrementApplications($id);

    /**
     * Επιστρέφει τις προτεινόμενες αγγελίες για έναν οδηγό
     *
     * @param int $driverId Το ID του οδηγού
     * @param int $limit Ο αριθμός αποτελεσμάτων
     * @return array Οι προτεινόμενες αγγελίες
     */
    public function getRecommendedListings($driverId, $limit = 10);

    /**
     * Επιστρέφει τις πρόσφατες αγγελίες
     *
     * @param int $limit Ο αριθμός αποτελεσμάτων
     * @return array Οι πρόσφατες αγγελίες
     */
    public function getRecentListings($limit = 10);

    /**
     * Επιστρέφει τις δημοφιλείς αγγελίες
     *
     * @param int $limit Ο αριθμός αποτελεσμάτων
     * @return array Οι δημοφιλείς αγγελίες
     */
    public function getPopularListings($limit = 10);

    /**
     * Επιστρέφει τις προβεβλημένες αγγελίες
     *
     * @param int $limit Ο αριθμός αποτελεσμάτων
     * @return array Οι προβεβλημένες αγγελίες
     */
    public function getFeaturedListings($limit = 10);
}

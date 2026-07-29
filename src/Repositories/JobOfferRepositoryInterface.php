<?php

namespace Drivejob\Repositories;

/**
 * Interface για το repository των προσφορών εργασίας
 */
interface JobOfferRepositoryInterface extends RepositoryInterface
{
    /**
     * Βρίσκει μια προσφορά εργασίας με βάση την εταιρεία και τον οδηγό
     * 
     * @param int $companyId Το ID της εταιρείας
     * @param int $driverId Το ID του οδηγού
     * @return array|null Τα δεδομένα της προσφοράς ή null αν δεν βρέθηκε
     */
    public function findByCompanyAndDriver($companyId, $driverId);

    /**
     * Βρίσκει τις προσφορές εργασίας μιας εταιρείας
     * 
     * @param int $companyId Το ID της εταιρείας
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findByCompany($companyId, $page = 1, $limit = 10);

    /**
     * Βρίσκει τις προσφορές εργασίας ενός οδηγού
     * 
     * @param int $driverId Το ID του οδηγού
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findByDriver($driverId, $page = 1, $limit = 10);

    /**
     * Αναζητά προσφορές εργασίας με βάση διάφορα κριτήρια
     * 
     * @param array $criteria Τα κριτήρια αναζήτησης
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function searchOffers(array $criteria = [], $page = 1, $limit = 10);

    /**
     * Αποδέχεται μια προσφορά εργασίας
     * 
     * @param int $id Το ID της προσφοράς
     * @return bool Επιτυχία/αποτυχία
     */
    public function acceptOffer($id);

    /**
     * Απορρίπτει μια προσφορά εργασίας
     * 
     * @param int $id Το ID της προσφοράς
     * @return bool Επιτυχία/αποτυχία
     */
    public function rejectOffer($id);

    /**
     * Ακυρώνει μια προσφορά εργασίας
     * 
     * @param int $id Το ID της προσφοράς
     * @return bool Επιτυχία/αποτυχία
     */
    public function cancelOffer($id);

    /**
     * Επιστρέφει τις προσφορές εργασίας που λήγουν σύντομα
     * 
     * @param int $days Ο αριθμός των ημερών
     * @return array Οι προσφορές εργασίας
     */
    public function getExpiringOffers($days = 7);

    /**
     * Επιστρέφει τις πρόσφατες προσφορές εργασίας
     * 
     * @param int $limit Ο αριθμός αποτελεσμάτων
     * @return array Οι πρόσφατες προσφορές εργασίας
     */
    public function getRecentOffers($limit = 10);
}

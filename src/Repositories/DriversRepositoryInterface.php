<?php

namespace Drivejob\Repositories;

/**
 * Interface για το repository των οδηγών
 */
interface DriversRepositoryInterface extends RepositoryInterface
{
    /**
     * Βρίσκει έναν οδηγό με βάση το email
     * 
     * @param string $email Το email του οδηγού
     * @return array|null Τα δεδομένα του οδηγού ή null αν δεν βρέθηκε
     */
    public function findByEmail($email);

    /**
     * Ενημερώνει το προφίλ ενός οδηγού
     * 
     * @param int $id Το ID του οδηγού
     * @param array $data Τα δεδομένα του προφίλ
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateProfile($id, array $data);

    /**
     * Ενημερώνει την αξιολόγηση ενός οδηγού
     * 
     * @param int $id Το ID του οδηγού
     * @param float $rating Η νέα αξιολόγηση
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateRating($id, $rating);

    /**
     * Αναζητά οδηγούς με βάση διάφορα κριτήρια
     * 
     * @param array $criteria Τα κριτήρια αναζήτησης
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function searchDrivers(array $criteria = [], $page = 1, $limit = 10);

    /**
     * Επιστρέφει τους οδηγούς με άδειες που λήγουν σύντομα
     * 
     * @param int $days Ο αριθμός των ημερών
     * @return array Οι οδηγοί με άδειες που λήγουν σύντομα
     */
    public function getDriversWithExpiringLicenses($days = 30);

    /**
     * Επιστρέφει τους οδηγούς με βάση τον τύπο άδειας
     * 
     * @param string $licenseType Ο τύπος άδειας
     * @return array Οι οδηγοί με τον συγκεκριμένο τύπο άδειας
     */
    public function getDriversByLicenseType($licenseType);

    /**
     * Επιστρέφει τους οδηγούς με βάση την τοποθεσία
     * 
     * @param string $location Η τοποθεσία
     * @param int $radius Η ακτίνα σε χιλιόμετρα
     * @return array Οι οδηγοί στην συγκεκριμένη τοποθεσία
     */
    public function getDriversByLocation($location, $radius = 50);

    /**
     * Επιστρέφει τους οδηγούς με βάση την εμπειρία
     * 
     * @param int $years Τα έτη εμπειρίας
     * @return array Οι οδηγοί με την συγκεκριμένη εμπειρία
     */
    public function getDriversByExperience($years);

    /**
     * Επιστρέφει τους οδηγούς με βάση τη διαθεσιμότητα
     * 
     * @param bool $available Η διαθεσιμότητα
     * @return array Οι οδηγοί με την συγκεκριμένη διαθεσιμότητα
     */
    public function getDriversByAvailability($available = true);
}

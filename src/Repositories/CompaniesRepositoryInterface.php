<?php

namespace Drivejob\Repositories;

/**
 * Interface για το repository των εταιρειών
 */
interface CompaniesRepositoryInterface extends RepositoryInterface
{
    /**
     * Βρίσκει μια εταιρεία με βάση το email
     * 
     * @param string $email Το email της εταιρείας
     * @return array|null Τα δεδομένα της εταιρείας ή null αν δεν βρέθηκε
     */
    public function findByEmail($email);

    /**
     * Ενημερώνει το προφίλ μιας εταιρείας
     * 
     * @param int $id Το ID της εταιρείας
     * @param array $data Τα δεδομένα του προφίλ
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateProfile($id, array $data);

    /**
     * Ενημερώνει την αξιολόγηση μιας εταιρείας
     * 
     * @param int $id Το ID της εταιρείας
     * @param float $rating Η νέα αξιολόγηση
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateRating($id, $rating);

    /**
     * Αναζητά εταιρείες με βάση διάφορα κριτήρια
     * 
     * @param array $criteria Τα κριτήρια αναζήτησης
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function searchCompanies(array $criteria = [], $page = 1, $limit = 10);

    /**
     * Επιστρέφει τις εταιρείες με βάση την τοποθεσία
     * 
     * @param string $location Η τοποθεσία
     * @param int $radius Η ακτίνα σε χιλιόμετρα
     * @return array Οι εταιρείες στην συγκεκριμένη τοποθεσία
     */
    public function getCompaniesByLocation($location, $radius = 50);

    /**
     * Επιστρέφει τις εταιρείες με βάση τον κλάδο
     * 
     * @param string $industry Ο κλάδος
     * @return array Οι εταιρείες στον συγκεκριμένο κλάδο
     */
    public function getCompaniesByIndustry($industry);

    /**
     * Επιστρέφει τις κορυφαίες εταιρείες με βάση την αξιολόγηση
     * 
     * @param int $limit Ο αριθμός αποτελεσμάτων
     * @return array Οι κορυφαίες εταιρείες
     */
    public function getTopRatedCompanies($limit = 10);

    /**
     * Επιστρέφει τις πρόσφατα εγγεγραμμένες εταιρείες
     * 
     * @param int $limit Ο αριθμός αποτελεσμάτων
     * @return array Οι πρόσφατα εγγεγραμμένες εταιρείες
     */
    public function getRecentlyRegisteredCompanies($limit = 10);
}

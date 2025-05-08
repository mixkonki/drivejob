<?php

namespace Drivejob\Repositories;

/**
 * Interface για τα repositories
 */
interface RepositoryInterface
{
    /**
     * Επιστρέφει όλες τις εγγραφές που ικανοποιούν τα κριτήρια
     *
     * @param array $criteria Τα κριτήρια αναζήτησης
     * @param array $orderBy Η ταξινόμηση
     * @param int|null $limit Ο μέγιστος αριθμός αποτελεσμάτων
     * @param int|null $offset Η μετατόπιση των αποτελεσμάτων
     * @return array Οι εγγραφές
     */
    public function findAll(array $criteria = [], array $orderBy = [], $limit = null, $offset = null);

    /**
     * Επιστρέφει μια εγγραφή που ικανοποιεί τα κριτήρια
     *
     * @param array $criteria Τα κριτήρια αναζήτησης
     * @return array|null Η εγγραφή ή null αν δεν βρέθηκε
     */
    public function findOne(array $criteria = []);

    /**
     * Επιστρέφει μια εγγραφή με βάση το ID
     *
     * @param int $id Το ID της εγγραφής
     * @return array|null Η εγγραφή ή null αν δεν βρέθηκε
     */
    public function find($id);

    /**
     * Δημιουργεί μια νέα εγγραφή
     *
     * @param array $data Τα δεδομένα της εγγραφής
     * @return int|bool Το ID της νέας εγγραφής ή false σε περίπτωση αποτυχίας
     */
    public function create(array $data);

    /**
     * Ενημερώνει μια εγγραφή
     *
     * @param int $id Το ID της εγγραφής
     * @param array $data Τα δεδομένα της εγγραφής
     * @return bool Επιτυχία ή αποτυχία της ενημέρωσης
     */
    public function update($id, array $data);

    /**
     * Διαγράφει μια εγγραφή
     *
     * @param int $id Το ID της εγγραφής
     * @return bool Επιτυχία ή αποτυχία της διαγραφής
     */
    public function delete($id);

    /**
     * Επιστρέφει τον αριθμό των εγγραφών που ικανοποιούν τα κριτήρια
     *
     * @param array $criteria Τα κριτήρια αναζήτησης
     * @return int Ο αριθμός των εγγραφών
     */
    public function count(array $criteria = []);
}

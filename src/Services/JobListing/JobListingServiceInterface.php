<?php

namespace Drivejob\Services\JobListing;

use Drivejob\Core\Exceptions\ValidationException;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Interface για την υπηρεσία διαχείρισης αγγελιών
 */
interface JobListingServiceInterface
{
    /**
     * Δημιουργεί μια νέα αγγελία
     *
     * @param array $data Τα δεδομένα της αγγελίας
     * @return int Το ID της νέας αγγελίας
     * @throws ValidationException Αν τα δεδομένα δεν είναι έγκυρα
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function createJobListing(array $data);

    /**
     * Ενημερώνει μια υπάρχουσα αγγελία
     *
     * @param int $id Το ID της αγγελίας
     * @param array $data Τα νέα δεδομένα της αγγελίας
     * @return bool Αν η ενημέρωση ήταν επιτυχής
     * @throws ValidationException Αν τα δεδομένα δεν είναι έγκυρα
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function updateJobListing(int $id, array $data);

    /**
     * Διαγράφει μια αγγελία
     *
     * @param int $id Το ID της αγγελίας
     * @return bool Αν η διαγραφή ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function deleteJobListing(int $id);

    /**
     * Βρίσκει μια αγγελία με βάση το ID της
     *
     * @param int $id Το ID της αγγελίας
     * @return array|null Τα δεδομένα της αγγελίας ή null αν δεν βρεθεί
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function findJobListing(int $id);

    /**
     * Αναζητά αγγελίες με βάση κριτήρια
     *
     * @param array $criteria Τα κριτήρια αναζήτησης
     * @param int $page Η σελίδα των αποτελεσμάτων
     * @param int $limit Ο αριθμός των αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα της αναζήτησης
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function searchJobListings(array $criteria, int $page = 1, int $limit = 10);

    /**
     * Αυξάνει τον αριθμό των προβολών μιας αγγελίας
     *
     * @param int $id Το ID της αγγελίας
     * @return bool Αν η ενημέρωση ήταν επιτυχής
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function incrementViews(int $id);

    /**
     * Ελέγχει αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας
     *
     * @param int $id Το ID της αγγελίας
     * @param int $userId Το ID του χρήστη
     * @param string $userRole Ο ρόλος του χρήστη
     * @return bool Αν ο χρήστης είναι ο ιδιοκτήτης της αγγελίας
     * @throws DatabaseException Αν υπάρξει σφάλμα στη βάση δεδομένων
     */
    public function isOwner(int $id, int $userId, string $userRole);

    /**
     * Επικυρώνει τα δεδομένα μιας αγγελίας
     *
     * @param array $data Τα δεδομένα της αγγελίας
     * @throws ValidationException Αν τα δεδομένα δεν είναι έγκυρα
     */
    public function validateJobListingData(array $data);
}

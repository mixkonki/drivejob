<?php

namespace Drivejob\Repositories;

/**
 * Interface για το repository των χρηστών
 */
interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Βρίσκει έναν χρήστη με βάση το email
     * 
     * @param string $email Το email του χρήστη
     * @return array|null Τα δεδομένα του χρήστη ή null αν δεν βρέθηκε
     */
    public function findByEmail($email);

    /**
     * Βρίσκει έναν χρήστη με βάση το username
     * 
     * @param string $username Το username του χρήστη
     * @return array|null Τα δεδομένα του χρήστη ή null αν δεν βρέθηκε
     */
    public function findByUsername($username);

    /**
     * Ενημερώνει το προφίλ ενός χρήστη
     * 
     * @param int $id Το ID του χρήστη
     * @param array $data Τα δεδομένα του προφίλ
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateProfile($id, array $data);

    /**
     * Ενημερώνει τον κωδικό πρόσβασης ενός χρήστη
     * 
     * @param int $id Το ID του χρήστη
     * @param string $password Ο νέος κωδικός πρόσβασης (κρυπτογραφημένος)
     * @return bool Επιτυχία/αποτυχία
     */
    public function updatePassword($id, $password);

    /**
     * Ενημερώνει την τελευταία σύνδεση ενός χρήστη
     * 
     * @param int $id Το ID του χρήστη
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateLastLogin($id);

    /**
     * Ενεργοποιεί έναν χρήστη
     * 
     * @param int $id Το ID του χρήστη
     * @return bool Επιτυχία/αποτυχία
     */
    public function activate($id);

    /**
     * Απενεργοποιεί έναν χρήστη
     * 
     * @param int $id Το ID του χρήστη
     * @return bool Επιτυχία/αποτυχία
     */
    public function deactivate($id);

    /**
     * Επαληθεύει έναν χρήστη
     * 
     * @param int $id Το ID του χρήστη
     * @return bool Επιτυχία/αποτυχία
     */
    public function verify($id);

    /**
     * Βρίσκει έναν χρήστη με βάση τον κωδικό επαλήθευσης
     * 
     * @param string $code Ο κωδικός επαλήθευσης
     * @return array|null Τα δεδομένα του χρήστη ή null αν δεν βρέθηκε
     */
    public function findByVerificationCode($code);

    /**
     * Βρίσκει έναν χρήστη με βάση τον κωδικό επαναφοράς
     * 
     * @param string $code Ο κωδικός επαναφοράς
     * @return array|null Τα δεδομένα του χρήστη ή null αν δεν βρέθηκε
     */
    public function findByResetCode($code);
}

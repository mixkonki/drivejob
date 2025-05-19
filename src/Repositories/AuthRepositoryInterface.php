<?php

namespace Drivejob\Repositories;

/**
 * Interface για το repository αυθεντικοποίησης
 */
interface AuthRepositoryInterface extends RepositoryInterface
{
    /**
     * Αυθεντικοποίηση χρήστη
     *
     * @param string $email Email του χρήστη
     * @param string $password Κωδικός πρόσβασης
     * @param string $role Ρόλος του χρήστη (driver ή company)
     * @return array|false Τα στοιχεία του χρήστη ή false σε περίπτωση αποτυχίας
     */
    public function authenticate($email, $password, $role = null);

    /**
     * Έλεγχος αν υπάρχει ήδη χρήστης με το ίδιο email
     *
     * @param string $email Email του χρήστη
     * @return bool Αν υπάρχει ήδη χρήστης με το ίδιο email
     */
    public function emailExists($email);

    /**
     * Εγγραφή νέου οδηγού
     *
     * @param array $data Δεδομένα οδηγού
     * @return int|false ID του νέου οδηγού ή false σε περίπτωση αποτυχίας
     */
    public function registerDriver($data);

    /**
     * Εγγραφή νέας εταιρείας
     *
     * @param array $data Δεδομένα εταιρείας
     * @return int|false ID της νέας εταιρείας ή false σε περίπτωση αποτυχίας
     */
    public function registerCompany($data);

    /**
     * Επαλήθευση λογαριασμού
     *
     * @param string $code Κωδικός επαλήθευσης
     * @return array|false Τα στοιχεία του χρήστη ή false σε περίπτωση αποτυχίας
     */
    public function verifyAccount($code);

    /**
     * Αποστολή email επαναφοράς κωδικού πρόσβασης
     *
     * @param string $email Email του χρήστη
     * @return bool Επιτυχία/αποτυχία
     */
    public function sendPasswordResetEmail($email);

    /**
     * Επαναφορά κωδικού πρόσβασης
     *
     * @param string $resetCode Κωδικός επαναφοράς
     * @param string $newPassword Νέος κωδικός πρόσβασης
     * @return bool Επιτυχία/αποτυχία
     */
    public function resetPassword($resetCode, $newPassword);

    /**
     * Αλλαγή κωδικού πρόσβασης
     *
     * @param string $role Ρόλος του χρήστη (driver, company ή admin)
     * @param int $userId ID του χρήστη
     * @param string $currentPassword Τρέχων κωδικός πρόσβασης
     * @param string $newPassword Νέος κωδικός πρόσβασης
     * @return bool Επιτυχία/αποτυχία
     */
    public function changePassword($role, $userId, $currentPassword, $newPassword);

    /**
     * Ενημέρωση της ημερομηνίας τελευταίας σύνδεσης
     *
     * @param string $table Όνομα του πίνακα
     * @param int $userId ID του χρήστη
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateLastLogin($table, $userId);
}

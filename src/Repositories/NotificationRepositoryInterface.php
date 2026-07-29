<?php

namespace Drivejob\Repositories;

/**
 * Interface για το repository των ειδοποιήσεων
 */
interface NotificationRepositoryInterface extends RepositoryInterface
{
    /**
     * Βρίσκει τις ειδοποιήσεις ενός χρήστη
     * 
     * @param int $userId Το ID του χρήστη
     * @param string $userType Ο τύπος του χρήστη (driver ή company)
     * @param bool $unreadOnly Αν θα επιστραφούν μόνο οι μη αναγνωσμένες ειδοποιήσεις
     * @param int $page Η σελίδα
     * @param int $limit Ο αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Τα αποτελέσματα και οι πληροφορίες σελιδοποίησης
     */
    public function findByUser($userId, $userType, $unreadOnly = false, $page = 1, $limit = 10);

    /**
     * Μαρκάρει μια ειδοποίηση ως αναγνωσμένη
     * 
     * @param int $id Το ID της ειδοποίησης
     * @return bool Επιτυχία/αποτυχία
     */
    public function markAsRead($id);

    /**
     * Μαρκάρει όλες τις ειδοποιήσεις ενός χρήστη ως αναγνωσμένες
     * 
     * @param int $userId Το ID του χρήστη
     * @param string $userType Ο τύπος του χρήστη (driver ή company)
     * @return bool Επιτυχία/αποτυχία
     */
    public function markAllAsRead($userId, $userType);

    /**
     * Δημιουργεί μια νέα ειδοποίηση
     * 
     * @param array $data Τα δεδομένα της ειδοποίησης
     * @return int|false Το ID της νέας ειδοποίησης ή false σε περίπτωση αποτυχίας
     */
    public function createNotification(array $data);

    /**
     * Επιστρέφει τον αριθμό των μη αναγνωσμένων ειδοποιήσεων ενός χρήστη
     * 
     * @param int $userId Το ID του χρήστη
     * @param string $userType Ο τύπος του χρήστη (driver ή company)
     * @return int Ο αριθμός των μη αναγνωσμένων ειδοποιήσεων
     */
    public function countUnread($userId, $userType);

    /**
     * Διαγράφει τις παλιές ειδοποιήσεις
     * 
     * @param int $days Ο αριθμός των ημερών μετά τις οποίες οι ειδοποιήσεις θεωρούνται παλιές
     * @return int Ο αριθμός των ειδοποιήσεων που διαγράφηκαν
     */
    public function deleteOldNotifications($days = 30);
}

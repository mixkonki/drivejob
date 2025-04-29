<?php
namespace Drivejob\Core;

class SessionLogger
{
    private $pdo;
    private $table = 'session_activity_log';
    
    /**
     * Κατασκευαστής
     * 
     * @param \PDO $pdo Αντικείμενο PDO για σύνδεση με τη βάση δεδομένων
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    /**
     * Καταγράφει μια δραστηριότητα συνεδρίας
     * 
     * @param string $activityType Τύπος δραστηριότητας (login, logout, regenerate, suspicious)
     * @param string $description Περιγραφή της δραστηριότητας
     * @param int|null $userId ID του χρήστη (προαιρετικό)
     * @return bool Επιτυχία ή αποτυχία
     */
    public function log($activityType, $description = '', $userId = null)
    {
        try {
            $sessionId = session_id() ?: 'no_session';
            
            $sql = "INSERT INTO {$this->table} (
                session_id, 
                activity_type, 
                ip_address, 
                user_agent, 
                user_id, 
                description
            ) VALUES (
                :session_id, 
                :activity_type, 
                :ip_address, 
                :user_agent, 
                :user_id, 
                :description
            )";
            
            $stmt = $this->pdo->prepare($sql);
            
            return $stmt->execute([
                'session_id' => $sessionId,
                'activity_type' => $activityType,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'user_id' => $userId,
                'description' => $description
            ]);
        } catch (\PDOException $e) {
            error_log("Session log error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Καταγράφει σύνδεση χρήστη
     * 
     * @param int $userId ID του χρήστη
     * @param string $userType Τύπος χρήστη (driver, company)
     * @return bool Επιτυχία ή αποτυχία
     */
    public function logLogin($userId, $userType)
    {
        return $this->log('login', "User type: {$userType}", $userId);
    }
    
    /**
     * Καταγράφει αποσύνδεση χρήστη
     * 
     * @param int $userId ID του χρήστη
     * @return bool Επιτυχία ή αποτυχία
     */
    public function logLogout($userId)
    {
        return $this->log('logout', '', $userId);
    }
    
    /**
     * Καταγράφει ύποπτη δραστηριότητα
     * 
     * @param string $description Περιγραφή της ύποπτης δραστηριότητας
     * @param int|null $userId ID του χρήστη (προαιρετικό)
     * @return bool Επιτυχία ή αποτυχία
     */
    public function logSuspiciousActivity($description, $userId = null)
    {
        return $this->log('suspicious', $description, $userId);
    }
    
    /**
     * Λαμβάνει ιστορικό δραστηριότητας συνεδριών για έναν χρήστη
     * 
     * @param int $userId ID του χρήστη
     * @param int $limit Μέγιστος αριθμός εγγραφών
     * @return array Λίστα με τις δραστηριότητες
     */
    public function getUserActivityHistory($userId, $limit = 50)
    {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE user_id = :user_id 
                    ORDER BY created_at DESC 
                    LIMIT :limit";
                    
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error fetching user activity history: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Λαμβάνει πρόσφατες ύποπτες δραστηριότητες
     * 
     * @param int $limit Μέγιστος αριθμός εγγραφών
     * @return array Λίστα με τις ύποπτες δραστηριότητες
     */
    public function getSuspiciousActivities($limit = 100)
    {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE activity_type = 'suspicious' 
                    ORDER BY created_at DESC 
                    LIMIT :limit";
                    
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error fetching suspicious activities: " . $e->getMessage());
            return [];
        }
    }
}
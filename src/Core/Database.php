<?php
namespace Drivejob\Core;

use PDO;
use PDOException;

/**
 * Singleton κλάση για τη διαχείριση της σύνδεσης με τη βάση δεδομένων
 */
class Database {
    /**
     * @var Database Η μοναδική περίσταση της κλάσης (Singleton pattern)
     */
    private static $instance = null;
    
    /**
     * @var PDO Η σύνδεση με τη βάση δεδομένων
     */
    private $connection;
    
    /**
     * Ιδιωτικός constructor για αποτροπή δημιουργίας πολλαπλών περιστάσεων
     */
    private function __construct() {
        try {
            // Φόρτωση των ρυθμίσεων της βάσης δεδομένων
            $config = include ROOT_DIR . '/config/db.php';
            
            // Δημιουργία σύνδεσης PDO
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
            $this->connection = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            die("Σφάλμα σύνδεσης με τη βάση δεδομένων: " . $e->getMessage());
        }
    }
    
    /**
     * Αποτροπή κλωνοποίησης (Singleton pattern)
     */
    private function __clone() {}
    
    /**
     * Επιστρέφει τη μοναδική περίσταση της κλάσης (Singleton pattern)
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Επιστρέφει τη σύνδεση PDO
     * 
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Εκτελεί ένα ερώτημα SQL
     * 
     * @param string $query Το ερώτημα SQL
     * @param array $params Παράμετροι για το ερώτημα
     * @return \PDOStatement
     */
    public function query($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            die("Σφάλμα εκτέλεσης ερωτήματος: " . $e->getMessage());
        }
    }
    
    /**
     * Επιστρέφει το ID της τελευταίας εισαγόμενης εγγραφής
     * 
     * @return int
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
}
<?php  // Βεβαιωθείτε ότι αυτή είναι η πρώτη γραμμή χωρίς κενά πριν από αυτήν
namespace Drivejob\Core;

class DatabaseSessionHandler implements \SessionHandlerInterface {
    private $pdo;
    private $options;

    public function __construct(\PDO $pdo, array $options = []) {
        $this->pdo = $pdo;
        $this->options = array_merge([
            'lifetime' => ini_get('session.gc_maxlifetime'),
            'table' => 'sessions'
        ], $options);
    }

    #[\ReturnTypeWillChange]
    public function open($savePath, $sessionName) {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function close() {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function read($id) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT payload FROM {$this->options['table']} WHERE id = :id"
            );
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                return $row['payload'];
            }
            
            return '';
        } catch (\PDOException $e) {
            error_log('Session read error: ' . $e->getMessage());
            return '';
        }
    }

    #[\ReturnTypeWillChange]
    public function write($id, $data) {
        try {
            // Λήψη πρόσθετων πληροφοριών
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $time = time();
            
            // Έλεγχος αν υπάρχει ήδη η συνεδρία
            $stmt = $this->pdo->prepare(
                "SELECT COUNT(*) FROM {$this->options['table']} WHERE id = :id"
            );
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                // Ενημέρωση υπάρχουσας συνεδρίας
                $stmt = $this->pdo->prepare(
                    "UPDATE {$this->options['table']} SET 
                     user_id = :user_id, 
                     ip_address = :ip_address, 
                     user_agent = :user_agent, 
                     payload = :payload, 
                     last_activity = :last_activity 
                     WHERE id = :id"
                );
            } else {
                // Εισαγωγή νέας συνεδρίας
                $stmt = $this->pdo->prepare(
                    "INSERT INTO {$this->options['table']} 
                     (id, user_id, ip_address, user_agent, payload, last_activity) 
                     VALUES (:id, :user_id, :ip_address, :user_agent, :payload, :last_activity)"
                );
            }
            
            // Εκτέλεση του ερωτήματος
            return $stmt->execute([
                ':id' => $id,
                ':user_id' => $userId,
                ':ip_address' => $ipAddress,
                ':user_agent' => $userAgent,
                ':payload' => $data,
                ':last_activity' => $time
            ]);
        } catch (\PDOException $e) {
            error_log('Session write error: ' . $e->getMessage());
            return false;
        }
    }

    #[\ReturnTypeWillChange]
    public function destroy($id) {
        try {
            $stmt = $this->pdo->prepare(
                "DELETE FROM {$this->options['table']} WHERE id = :id"
            );
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log('Session destroy error: ' . $e->getMessage());
            return false;
        }
    }

    #[\ReturnTypeWillChange]
    public function gc($maxlifetime) {
        try {
            $time = time() - $maxlifetime;
            $stmt = $this->pdo->prepare(
                "DELETE FROM {$this->options['table']} WHERE last_activity < :time"
            );
            $stmt->bindParam(':time', $time, \PDO::PARAM_INT);
            return $stmt->execute() ? $stmt->rowCount() : 0;
        } catch (\PDOException $e) {
            error_log('Session gc error: ' . $e->getMessage());
            return false;
        }
    }
}
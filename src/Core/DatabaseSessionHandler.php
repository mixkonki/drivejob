<?php

namespace Drivejob\Core;

class DatabaseSessionHandler implements \SessionHandlerInterface
{
    private $pdo;
    private $options;

    public function __construct(\PDO $pdo, array $options = [])
    {
        $this->pdo = $pdo;
        $this->options = array_merge([
            'lifetime' => ini_get('session.gc_maxlifetime'),
            'table' => 'sessions'
        ], $options);
    }

    public function open($savePath, $sessionName): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read($id): string
    {
        try {
            $stmt = $this->pdo->prepare("SELECT payload FROM {$this->options['table']} WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                return $row['payload'];
            }

            return '';
        } catch (\PDOException $e) {
            // Καταγραφή σφάλματος και επιστροφή κενού string
            error_log('Session read error: ' . $e->getMessage());
            return '';
        }
    }

    public function write($id, $data): bool
    {
        try {
            // Απλοποιημένη έκδοση χωρίς αναφορά στην κλάση Session
            $time = time();

            // Έλεγχος αν υπάρχει ήδη η συνεδρία
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->options['table']} WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->fetchColumn() > 0) {
                // Ενημέρωση υπάρχουσας συνεδρίας
                $stmt = $this->pdo->prepare("UPDATE {$this->options['table']} SET 
                     payload = :payload, 
                     last_activity = :last_activity 
                     WHERE id = :id");

                return $stmt->execute([
                    ':id' => $id,
                    ':payload' => $data,
                    ':last_activity' => $time
                ]);
            } else {
                // Εισαγωγή νέας συνεδρίας
                $stmt = $this->pdo->prepare("INSERT INTO {$this->options['table']} 
                     (id, payload, last_activity) 
                     VALUES (:id, :payload, :last_activity)");

                return $stmt->execute([
                    ':id' => $id,
                    ':payload' => $data,
                    ':last_activity' => $time
                ]);
            }
        } catch (\PDOException $e) {
            error_log('Session write error: ' . $e->getMessage());
            return false;
        }
    }

    public function destroy($id): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->options['table']} WHERE id = :id");
            $stmt->bindParam(':id', $id);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log('Session destroy error: ' . $e->getMessage());
            return false;
        }
    }

    public function gc($maxlifetime): int|false
    {
        try {
            $time = time() - $maxlifetime;
            $stmt = $this->pdo->prepare("DELETE FROM {$this->options['table']} WHERE last_activity < :time");
            $stmt->bindParam(':time', $time, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            error_log('Session gc error: ' . $e->getMessage());
            return false;
        }
    }
}

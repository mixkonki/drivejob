<?php
namespace Tests\Core;

use Drivejob\Core\DatabaseSessionHandler;
use PHPUnit\Framework\TestCase;
use Tests\Mocks\MockSession; // Εισαγωγή του mock session

class DatabaseSessionHandlerTest extends TestCase {
    private $pdo;
    private $handler;
    private $tableName = 'test_sessions';
    
    protected function setUp(): void {
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->exec("CREATE TABLE {$this->tableName} (
            id VARCHAR(128) PRIMARY KEY,
            user_id VARCHAR(128) NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            payload TEXT NOT NULL,
            last_activity INTEGER NOT NULL
        )");
        
        $this->handler = new DatabaseSessionHandler($this->pdo, [
            'table' => $this->tableName
        ]);
    }
    
    public function testReadAndWrite() {
        $sessionId = 'test_session_id';
        $sessionData = 'test_session_data';
        
        // Δοκιμή εγγραφής
        $result = $this->handler->write($sessionId, $sessionData);
        $this->assertTrue($result);
        
        // Έλεγχος άμεσα στη βάση δεδομένων
        $stmt = $this->pdo->prepare("SELECT payload FROM {$this->tableName} WHERE id = :id");
        $stmt->bindParam(':id', $sessionId);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals($sessionData, $row['payload']);
        
        // Δοκιμή ανάγνωσης
        $readData = $this->handler->read($sessionId);
        $this->assertEquals($sessionData, $readData);
    }
    
    public function testUpdateExistingSession() {
        $sessionId = 'test_session_id';
        $initialData = 'initial_data';
        $updatedData = 'updated_data';
        
        // Αρχική εγγραφή
        $this->handler->write($sessionId, $initialData);
        
        // Ενημέρωση δεδομένων
        $result = $this->handler->write($sessionId, $updatedData);
        $this->assertTrue($result);
        
        // Έλεγχος ότι τα δεδομένα ενημερώθηκαν
        $readData = $this->handler->read($sessionId);
        $this->assertEquals($updatedData, $readData);
    }
    
    public function testDestroy() {
        $sessionId = 'test_session_id';
        $sessionData = 'test_session_data';
        
        // Δημιουργία συνεδρίας
        $this->handler->write($sessionId, $sessionData);
        
        // Καταστροφή συνεδρίας
        $result = $this->handler->destroy($sessionId);
        $this->assertTrue($result);
        
        // Έλεγχος ότι η συνεδρία διαγράφηκε
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->tableName} WHERE id = :id");
        $stmt->bindParam(':id', $sessionId);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        $this->assertEquals(0, $count);
    }
    
    public function testGarbageCollection() {
        $currentTime = time();
        $maxlifetime = 3600; // 1 ώρα
        $oldTime = $currentTime - $maxlifetime - 100; // Παλιότερο από το όριο
        
        // Δημιουργία μιας παλιάς και μιας νέας συνεδρίας
        $oldSessionId = 'old_session';
        $newSessionId = 'new_session';
        
        // Χειροκίνητη εισαγωγή με συγκεκριμένο χρόνο
        $stmt = $this->pdo->prepare(
            "INSERT INTO {$this->tableName} 
             (id, user_id, ip_address, user_agent, payload, last_activity) 
             VALUES (:id, NULL, '127.0.0.1', 'test', 'old_data', :time)"
        );
        $stmt->execute([':id' => $oldSessionId, ':time' => $oldTime]);
        
        // Δημιουργία νέας συνεδρίας (θα χρησιμοποιήσει τον τρέχοντα χρόνο)
        $this->handler->write($newSessionId, 'new_data');
        
        // Εκτέλεση της συλλογής απορριμμάτων
        $deletedCount = $this->handler->gc($maxlifetime);
        
        // Αναμένουμε ότι καθαρίστηκε μία συνεδρία
        $this->assertEquals(1, $deletedCount);
        
        // Επιβεβαίωση ότι μόνο η παλιά συνεδρία αφαιρέθηκε
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->tableName} WHERE id = :id");
        
        $stmt->bindParam(':id', $oldSessionId);
        $stmt->execute();
        $oldCount = $stmt->fetchColumn();
        $this->assertEquals(0, $oldCount);
        
        $stmt->bindParam(':id', $newSessionId);
        $stmt->execute();
        $newCount = $stmt->fetchColumn();
        $this->assertEquals(1, $newCount);
    }
}
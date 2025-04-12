<?php
namespace Tests\Core;

use PHPUnit\Framework\TestCase;
use Drivejob\Core\DatabaseSessionHandler;

class DatabaseSessionHandlerTest extends TestCase {
    private $pdo;
    private $handler;
    private $tableName = 'test_sessions';
    
    protected function setUp(): void {
        // Βεβαιωθείτε ότι δεν έχουμε ενεργή συνεδρία
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        // Δημιουργία σύνδεσης με τη βάση δεδομένων
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        // Δημιουργία προσωρινού πίνακα
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS {$this->tableName} (
            id VARCHAR(128) NOT NULL PRIMARY KEY,
            user_id INT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            payload TEXT NOT NULL,
            last_activity INT NOT NULL
        )");
        
        // Δημιουργία του handler
        $this->handler = new DatabaseSessionHandler($this->pdo, [
            'table' => $this->tableName
        ]);
    }
    
    protected function tearDown(): void {
        // Διαγραφή του προσωρινού πίνακα
        $this->pdo->exec("DROP TABLE IF EXISTS {$this->tableName}");
        
        // Κλείσιμο της σύνδεσης
        $this->pdo = null;
    }
    
    public function testReadAndWrite() {
        $id = 'test_session_id';
        $data = 'test_session_data';
        
        // Βεβαιωθείτε ότι η συνεδρία δεν υπάρχει
        $this->assertEquals('', $this->handler->read($id));
        
        // Γράψτε τη συνεδρία
        $this->assertTrue($this->handler->write($id, $data));
        
        // Διαβάστε τη συνεδρία
        $this->assertEquals($data, $this->handler->read($id));
    }
    
    public function testUpdateExistingSession() {
        $id = 'test_session_id';
        $data1 = 'test_session_data_1';
        $data2 = 'test_session_data_2';
        
        // Γράψτε την αρχική συνεδρία
        $this->assertTrue($this->handler->write($id, $data1));
        
        // Ενημερώστε τη συνεδρία
        $this->assertTrue($this->handler->write($id, $data2));
        
        // Διαβάστε τη συνεδρία
        $this->assertEquals($data2, $this->handler->read($id));
    }
    
    public function testDestroy() {
        $id = 'test_session_id';
        $data = 'test_session_data';
        
        // Γράψτε τη συνεδρία
        $this->assertTrue($this->handler->write($id, $data));
        
        // Βεβαιωθείτε ότι η συνεδρία υπάρχει
        $this->assertEquals($data, $this->handler->read($id));
        
        // Καταστρέψτε τη συνεδρία
        $this->assertTrue($this->handler->destroy($id));
        
        // Βεβαιωθείτε ότι η συνεδρία διαγράφηκε
        $this->assertEquals('', $this->handler->read($id));
    }
    
    public function testGarbageCollection() {
        $id1 = 'test_session_id_1';
        $id2 = 'test_session_id_2';
        $data = 'test_session_data';
        
        // Γράψτε τις συνεδρίες
        $this->assertTrue($this->handler->write($id1, $data));
        $this->assertTrue($this->handler->write($id2, $data));
        
        // Ορίστε την τελευταία δραστηριότητα για το id1 στο παρελθόν
        $stmt = $this->pdo->prepare(
            "UPDATE {$this->tableName} SET last_activity = :time WHERE id = :id"
        );
        $stmt->execute([
            ':id' => $id1,
            ':time' => time() - 3600 // 1 ώρα πριν
        ]);
        
        // Εκτελέστε το garbage collection με χρόνο 30 λεπτών
        $this->handler->gc(1800);
        
        // Βεβαιωθείτε ότι το id1 διαγράφηκε και το id2 υπάρχει ακόμα
        $this->assertEquals('', $this->handler->read($id1));
        $this->assertEquals($data, $this->handler->read($id2));
    }
}
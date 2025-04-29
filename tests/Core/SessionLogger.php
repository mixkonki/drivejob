<?php
namespace Tests\Core;

use PHPUnit\Framework\TestCase;
use Drivejob\Core\SessionLogger;

class SessionLoggerTest extends TestCase
{
    private $pdo;
    private $logger;
    
    protected function setUp(): void
    {
        // Δημιουργία mock PDO αντικειμένου
        $this->pdo = $this->createMock(\PDO::class);
        
        // Δημιουργία του logger
        $this->logger = new SessionLogger($this->pdo);
        
        // Ορισμός των υπερκαθολικών μεταβλητών για τις δοκιμές
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test Browser';
    }
    
    /**
     * @test
     */
    public function it_can_log_general_activity()
    {
        // Δημιουργία mock PDOStatement
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        
        // Ρύθμιση του PDO mock για το prepare
        $this->pdo->method('prepare')->willReturn($stmt);
        
        // Εκτέλεση της μεθόδου log
        $result = $this->logger->log('test_activity', 'Test description', 1);
        
        // Επαλήθευση του αποτελέσματος
        $this->assertTrue($result);
    }
    
    /**
     * @test
     */
    public function it_can_log_login()
    {
        // Δημιουργία mock PDOStatement
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        
        // Ρύθμιση του PDO mock για το prepare
        $this->pdo->method('prepare')->willReturn($stmt);
        
        // Εκτέλεση της μεθόδου logLogin
        $result = $this->logger->logLogin(1, 'driver');
        
        // Επαλήθευση του αποτελέσματος
        $this->assertTrue($result);
    }
    
    /**
     * @test
     */
    public function it_can_log_logout()
    {
        // Δημιουργία mock PDOStatement
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        
        // Ρύθμιση του PDO mock για το prepare
        $this->pdo->method('prepare')->willReturn($stmt);
        
        // Εκτέλεση της μεθόδου logLogout
        $result = $this->logger->logLogout(1);
        
        // Επαλήθευση του αποτελέσματος
        $this->assertTrue($result);
    }
    
    /**
     * @test
     */
    public function it_can_log_suspicious_activity()
    {
        // Δημιουργία mock PDOStatement
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('execute')->willReturn(true);
        
        // Ρύθμιση του PDO mock για το prepare
        $this->pdo->method('prepare')->willReturn($stmt);
        
        // Εκτέλεση της μεθόδου logSuspiciousActivity
        $result = $this->logger->logSuspiciousActivity('Suspicious login attempt', 1);
        
        // Επαλήθευση του αποτελέσματος
        $this->assertTrue($result);
    }
    
    /**
     * @test
     */
    public function it_can_get_user_activity_history()
    {
        // Ψεύτικα δεδομένα ιστορικού
        $fakeHistory = [
            ['id' => 1, 'session_id' => 'test_session', 'activity_type' => 'login'],
            ['id' => 2, 'session_id' => 'test_session', 'activity_type' => 'logout']
        ];
        
        // Δημιουργία mock PDOStatement
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('bindValue')->willReturn(true);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($fakeHistory);
        
        // Ρύθμιση του PDO mock για το prepare
        $this->pdo->method('prepare')->willReturn($stmt);
        
        // Εκτέλεση της μεθόδου getUserActivityHistory
        $result = $this->logger->getUserActivityHistory(1, 10);
        
        // Επαλήθευση του αποτελέσματος
        $this->assertCount(2, $result);
        $this->assertEquals('login', $result[0]['activity_type']);
        $this->assertEquals('logout', $result[1]['activity_type']);
    }
    
    /**
     * @test
     */
    public function it_can_get_suspicious_activities()
    {
        // Ψεύτικα δεδομένα ύποπτων δραστηριοτήτων
        $fakeSuspiciousActivities = [
            ['id' => 1, 'activity_type' => 'suspicious', 'description' => 'Failed login'],
            ['id' => 2, 'activity_type' => 'suspicious', 'description' => 'IP change']
        ];
        
        // Δημιουργία mock PDOStatement
        $stmt = $this->createMock(\PDOStatement::class);
        $stmt->method('bindValue')->willReturn(true);
        $stmt->method('execute')->willReturn(true);
        $stmt->method('fetchAll')->willReturn($fakeSuspiciousActivities);
        
        // Ρύθμιση του PDO mock για το prepare
        $this->pdo->method('prepare')->willReturn($stmt);
        
        // Εκτέλεση της μεθόδου getSuspiciousActivities
        $result = $this->logger->getSuspiciousActivities(10);
        
        // Επαλήθευση του αποτελέσματος
        $this->assertCount(2, $result);
        $this->assertEquals('suspicious', $result[0]['activity_type']);
        $this->assertEquals('Failed login', $result[0]['description']);
    }
}
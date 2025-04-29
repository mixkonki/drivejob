<?php
namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;
use Drivejob\Controllers\AuthController;
use Drivejob\Core\Session;
use Drivejob\Models\DriversModel;
use Drivejob\Models\CompaniesModel;

class AuthControllerTest extends TestCase {
    private $controller;
    private $driversModel;
    private $companiesModel;
    
    protected function setUp(): void {
        // Δημιουργία mock των μοντέλων
        $this->driversModel = $this->createMock(DriversModel::class);
        $this->companiesModel = $this->createMock(CompaniesModel::class);
        
        // Εισαγωγή των mock στον controller μέσω reflection
        $this->controller = new AuthController(new \PDO('sqlite::memory:'));
        
        $reflection = new \ReflectionClass($this->controller);
        
        $driversProperty = $reflection->getProperty('driversModel');
        $driversProperty->setAccessible(true);
        $driversProperty->setValue($this->controller, $this->driversModel);
        
        $companiesProperty = $reflection->getProperty('companiesModel');
        $companiesProperty->setAccessible(true);
        $companiesProperty->setValue($this->controller, $this->companiesModel);
        
        // Καθαρισμός της συνεδρίας
        Session::start();
        Session::clear();
    }
    
    protected function tearDown(): void {
        // Καταστροφή της συνεδρίας μετά από κάθε δοκιμή
        Session::destroy();
    }
    
    public function testProcessLoginSuccessForDriver() {
        // Ρύθμιση των δεδομένων POST
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['email'] = 'driver@example.com';
        $_POST['password'] = 'password123';
        
        // Ρύθμιση της συμπεριφοράς του mock
        $driverData = [
            'id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'driver@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'is_verified' => 1
        ];
        
        $this->driversModel->expects($this->once())
                         ->method('getDriverByEmail')
                         ->with('driver@example.com')
                         ->willReturn($driverData);
        
        $this->driversModel->expects($this->once())
                          ->method('updateLastLogin')
                          ->with(1);
        
        // Εκτέλεση της δοκιμής μέσα σε ένα try-catch καθώς η μέθοδος θα προσπαθήσει να ανακατευθύνει
        try {
            $this->controller->processLogin();
        } catch (\Exception $e) {
            // Αγνοούμε την εξαίρεση για την ανακατεύθυνση
        }
        
        // Έλεγχος αν τα δεδομένα συνεδρίας είναι σωστά
        $this->assertEquals(1, Session::get('user_id'));
        $this->assertEquals('driver', Session::get('role'));
        $this->assertEquals('John Doe', Session::get('user_name'));
    }
    
    public function testProcessLoginSuccessForCompany() {
        // Ρύθμιση των δεδομένων POST
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['email'] = 'company@example.com';
        $_POST['password'] = 'password123';
        
        // Ρύθμιση της συμπεριφοράς των mock
        $this->driversModel->expects($this->once())
                         ->method('getDriverByEmail')
                         ->with('company@example.com')
                         ->willReturn(false);
        
        $companyData = [
            'id' => 1,
            'company_name' => 'Example Company',
            'email' => 'company@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'is_verified' => 1
        ];
        
        $this->companiesModel->expects($this->once())
                           ->method('getCompanyByEmail')
                           ->with('company@example.com')
                           ->willReturn($companyData);
        
        $this->companiesModel->expects($this->once())
                           ->method('updateLastLogin')
                           ->with(1);
        
        // Εκτέλεση της δοκιμής
        try {
            $this->controller->processLogin();
        } catch (\Exception $e) {
            // Αγνοούμε την εξαίρεση για την ανακατεύθυνση
        }
        
        // Έλεγχος αν τα δεδομένα συνεδρίας είναι σωστά
        $this->assertEquals(1, Session::get('user_id'));
        $this->assertEquals('company', Session::get('role'));
        $this->assertEquals('Example Company', Session::get('user_name'));
    }
    
    public function testProcessLoginFailedWithWrongPassword() {
        // Ρύθμιση των δεδομένων POST
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['email'] = 'driver@example.com';
        $_POST['password'] = 'wrong_password';
        
        // Ρύθμιση της συμπεριφοράς των mock
        $driverData = [
            'id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'driver@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'is_verified' => 1
        ];
        
        $this->driversModel->expects($this->once())
                         ->method('getDriverByEmail')
                         ->with('driver@example.com')
                         ->willReturn($driverData);
        
        // Δεν αναμένουμε κλήση του updateLastLogin
        $this->driversModel->expects($this->never())
                          ->method('updateLastLogin');
        
        // Εκτέλεση της δοκιμής
        try {
            $this->controller->processLogin();
        } catch (\Exception $e) {
            // Αγνοούμε την εξαίρεση για την ανακατεύθυνση
        }
        
        // Έλεγχος ότι οι συνεδρίες δεν έχουν οριστεί και ότι έχει οριστεί το μήνυμα σφάλματος
        $this->assertFalse(Session::has('user_id'));
        $this->assertFalse(Session::has('role'));
        $this->assertFalse(Session::has('user_name'));
        $this->assertTrue(Session::has('login_error'));
    }
}
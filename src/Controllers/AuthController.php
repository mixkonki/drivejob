<?php
namespace Drivejob\Controllers;

use Drivejob\Core\Session;
use Drivejob\Models\DriversModel;
use Drivejob\Models\CompaniesModel;

class AuthController {
    private $driversModel;
    private $companiesModel;
    
    public function __construct($pdo) {
        $this->driversModel = new DriversModel($pdo);
        $this->companiesModel = new CompaniesModel($pdo);
    }
    
    public function login() {
        // Έλεγχος αν ο χρήστης είναι ήδη συνδεδεμένος
        if (Session::has('user_id')) {
            $role = Session::get('role');
            if ($role === 'driver') {
                header('Location: ' . BASE_URL . 'drivers/driver_profile.php');
            } else {
                header('Location: ' . BASE_URL . 'companies/company_profile.php');
            }
            exit();
        }
        
        // Φόρτωση της σελίδας σύνδεσης
        include ROOT_DIR . '/src/Views/login.php';
    }
    
    public function processLogin() {
        // Έλεγχος αν το αίτημα είναι POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
        
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Αναζήτηση στον πίνακα drivers
        $driver = $this->driversModel->getDriverByEmail($email);
        
        if ($driver && $driver['is_verified'] && password_verify($password, $driver['password'])) {
            // Επιτυχής σύνδεση οδηγού
            Session::set('user_id', $driver['id']);
            Session::set('role', 'driver');
            Session::set('user_name', $driver['first_name'] . ' ' . $driver['last_name']);
            
            // Ενημέρωση τελευταίας σύνδεσης
            $this->driversModel->updateLastLogin($driver['id']);
            
            // Έλεγχος για ανακατεύθυνση μετά τη σύνδεση
            $redirectUrl = Session::has('redirect_after_login')
                ? Session::get('redirect_after_login')
                : BASE_URL . 'drivers/driver_profile.php';
            
            Session::remove('redirect_after_login');
            
            header('Location: ' . $redirectUrl);
            exit();
        }
        
        // Αναζήτηση στον πίνακα companies
        $company = $this->companiesModel->getCompanyByEmail($email);
        
        if ($company && $company['is_verified'] && password_verify($password, $company['password'])) {
            // Επιτυχής σύνδεση εταιρείας
            Session::set('user_id', $company['id']);
            Session::set('role', 'company');
            Session::set('user_name', $company['company_name']);
            
            // Ενημέρωση τελευταίας σύνδεσης
            $this->companiesModel->updateLastLogin($company['id']);
            
            // Έλεγχος για ανακατεύθυνση μετά τη σύνδεση
            $redirectUrl = Session::has('redirect_after_login')
                ? Session::get('redirect_after_login')
                : BASE_URL . 'companies/company_profile.php';
            
            Session::remove('redirect_after_login');
            
            header('Location: ' . $redirectUrl);
            exit();
        }
        
        // Αποτυχία σύνδεσης
        Session::set('login_error', 'Εσφαλμένο email ή συνθηματικό.');
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
    
    public function logout() {
        Session::destroy();
        header('Location: ' . BASE_URL);
        exit();
    }
}
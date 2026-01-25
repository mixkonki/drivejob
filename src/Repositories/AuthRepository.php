<?php

namespace Drivejob\Repositories;

use PDO;
use Drivejob\Core\Logger;
use Drivejob\Core\Session;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Repository για τη διαχείριση της αυθεντικοποίησης και εξουσιοδότησης
 */
class AuthRepository extends BaseRepository implements AuthRepositoryInterface
{
    /**
     * @var string Το όνομα του πίνακα
     */
    protected $table = 'users'; // Προεπιλεγμένος πίνακας, αλλά θα χρησιμοποιούμε διαφορετικούς πίνακες

    /**
     * @var DriversRepositoryInterface Το repository για τους οδηγούς
     */
    private $driversRepository;

    /**
     * @var CompaniesRepositoryInterface Το repository για τις εταιρείες
     */
    private $companiesRepository;

    /**
     * Constructor
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     * @param DriversRepositoryInterface|null $driversRepository Το repository για τους οδηγούς
     * @param CompaniesRepositoryInterface|null $companiesRepository Το repository για τις εταιρείες
     */
    public function __construct(
        PDO $pdo,
        DriversRepositoryInterface $driversRepository = null,
        CompaniesRepositoryInterface $companiesRepository = null
    ) {
        parent::__construct($pdo);

        $this->driversRepository = $driversRepository ?? new DriversRepository($pdo);
        $this->companiesRepository = $companiesRepository ?? new CompaniesRepository($pdo);
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate($email, $password, $role = null)
    {
        try {
            // Έλεγχος αν ο χρήστης είναι οδηγός
            if ($role === 'driver' || $role === null) {
                $driver = $this->authenticateDriver($email, $password);
                if ($driver) {
                    return [
                        'user_id' => $driver['id'],
                        'role' => 'driver',
                        'email' => $driver['email'],
                        'name' => $driver['first_name'] . ' ' . $driver['last_name'],
                        'is_verified' => $driver['is_verified'],
                        'is_active' => $driver['is_active']
                    ];
                }
            }

            // Έλεγχος αν ο χρήστης είναι εταιρεία
            if ($role === 'company' || $role === null) {
                $company = $this->authenticateCompany($email, $password);
                if ($company) {
                    return [
                        'user_id' => $company['id'],
                        'role' => 'company',
                        'email' => $company['email'],
                        'name' => $company['company_name'],
                        'is_verified' => $company['is_verified'],
                        'is_active' => $company['is_active']
                    ];
                }
            }

            // Έλεγχος αν ο χρήστης είναι διαχειριστής
            // Προσωρινά απενεργοποιημένο επειδή ο πίνακας 'admins' δεν υπάρχει
            /*
            if ($role === 'admin' || $role === null) {
                $admin = $this->authenticateAdmin($email, $password);
                if ($admin) {
                    return [
                        'user_id' => $admin['id'],
                        'role' => 'admin',
                        'email' => $admin['email'],
                        'name' => $admin['name'],
                        'is_verified' => 1,
                        'is_active' => 1
                    ];
                }
            }
            */

            return false;
        } catch (\Exception $e) {
            Logger::error('Error in authenticate: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Αυθεντικοποίηση οδηγού
     *
     * @param string $email Email του οδηγού
     * @param string $password Κωδικός πρόσβασης
     * @return array|false Τα στοιχεία του οδηγού ή false σε περίπτωση αποτυχίας
     */
    private function authenticateDriver($email, $password)
    {
        try {
            $query = "SELECT * FROM drivers WHERE email = :email";
            $params = ['email' => $email];
            $driver = $this->queryOne($query, $params);

            if ($driver && password_verify($password, $driver['password'])) {
                // Ενημέρωση της ημερομηνίας τελευταίας σύνδεσης
                $this->updateLastLogin('drivers', $driver['id']);
                return $driver;
            }

            return false;
        } catch (\PDOException $e) {
            Logger::error('Error in authenticateDriver: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Αυθεντικοποίηση εταιρείας
     *
     * @param string $email Email της εταιρείας
     * @param string $password Κωδικός πρόσβασης
     * @return array|false Τα στοιχεία της εταιρείας ή false σε περίπτωση αποτυχίας
     */
    private function authenticateCompany($email, $password)
    {
        try {
            $query = "SELECT * FROM companies WHERE email = :email";
            $params = ['email' => $email];
            $company = $this->queryOne($query, $params);

            if ($company && password_verify($password, $company['password'])) {
                // Ενημέρωση της ημερομηνίας τελευταίας σύνδεσης
                $this->updateLastLogin('companies', $company['id']);
                return $company;
            }

            return false;
        } catch (\PDOException $e) {
            Logger::error('Error in authenticateCompany: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Αυθεντικοποίηση διαχειριστή
     *
     * @param string $email Email του διαχειριστή
     * @param string $password Κωδικός πρόσβασης
     * @return array|false Τα στοιχεία του διαχειριστή ή false σε περίπτωση αποτυχίας
     */
    private function authenticateAdmin($email, $password)
    {
        try {
            $query = "SELECT * FROM admins WHERE email = :email";
            $params = ['email' => $email];
            $admin = $this->queryOne($query, $params);

            if ($admin && password_verify($password, $admin['password'])) {
                // Ενημέρωση της ημερομηνίας τελευταίας σύνδεσης
                $this->updateLastLogin('admins', $admin['id']);
                return $admin;
            }

            return false;
        } catch (\PDOException $e) {
            Logger::error('Error in authenticateAdmin: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateLastLogin($table, $userId)
    {
        try {
            $query = "UPDATE $table SET last_login = NOW() WHERE id = :id";
            $params = ['id' => $userId];
            return $this->execute($query, $params) > 0;
        } catch (\PDOException $e) {
            Logger::error('Error in updateLastLogin: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function emailExists($email)
    {
        try {
            // Έλεγχος στον πίνακα drivers
            $query = "SELECT COUNT(*) FROM drivers WHERE email = :email";
            $params = ['email' => $email];
            if ($this->queryScalar($query, $params) > 0) {
                return true;
            }

            // Έλεγχος στον πίνακα companies
            $query = "SELECT COUNT(*) FROM companies WHERE email = :email";
            if ($this->queryScalar($query, $params) > 0) {
                return true;
            }

            // Έλεγχος στον πίνακα admins
            // Προσωρινά απενεργοποιημένο επειδή ο πίνακας 'admins' δεν υπάρχει
            /*
            $query = "SELECT COUNT(*) FROM admins WHERE email = :email";
            if ($this->queryScalar($query, $params) > 0) {
                return true;
            }
            */

            return false;
        } catch (\PDOException $e) {
            Logger::error('Error in emailExists: ' . $e->getMessage());
            return true; // Επιστρέφουμε true σε περίπτωση σφάλματος για ασφάλεια
        }
    }

    /**
     * {@inheritdoc}
     */
    public function registerDriver($data)
    {
        try {
            // Έλεγχος αν υπάρχει ήδη χρήστης με το ίδιο email
            if ($this->emailExists($data['email'])) {
                return false;
            }

            // Κρυπτογράφηση του κωδικού πρόσβασης
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

            // Δημιουργία του κωδικού επαλήθευσης
            $data['verification_code'] = $this->generateVerificationCode();
            $data['verification_expires'] = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Ορισμός προεπιλεγμένων τιμών
            $data['is_verified'] = 0;
            $data['is_active'] = 1;
            $data['created_at'] = date('Y-m-d H:i:s');

            // Εισαγωγή του νέου οδηγού στη βάση δεδομένων
            $this->table = 'drivers';
            $driverId = $this->create($data);

            if ($driverId) {
                // Αποστολή email επαλήθευσης
                $this->sendVerificationEmail($data['email'], $data['verification_code'], 'driver');
                return $driverId;
            }

            return false;
        } catch (\PDOException $e) {
            Logger::error('Error in registerDriver: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function registerCompany($data)
    {
        try {
            // Έλεγχος αν υπάρχει ήδη χρήστης με το ίδιο email
            if ($this->emailExists($data['email'])) {
                return false;
            }

            // Κρυπτογράφηση του κωδικού πρόσβασης
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

            // Δημιουργία του κωδικού επαλήθευσης
            $data['verification_code'] = $this->generateVerificationCode();
            $data['verification_expires'] = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Ορισμός προεπιλεγμένων τιμών
            $data['is_verified'] = 0;
            $data['is_active'] = 1;
            $data['created_at'] = date('Y-m-d H:i:s');

            // Εισαγωγή της νέας εταιρείας στη βάση δεδομένων
            $this->table = 'companies';
            $companyId = $this->create($data);

            if ($companyId) {
                // Αποστολή email επαλήθευσης
                $this->sendVerificationEmail($data['email'], $data['verification_code'], 'company');
                return $companyId;
            }

            return false;
        } catch (\PDOException $e) {
            Logger::error('Error in registerCompany: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Δημιουργία κωδικού επαλήθευσης
     *
     * @return string Κωδικός επαλήθευσης
     */
    private function generateVerificationCode()
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Αποστολή email επαλήθευσης
     *
     * @param string $email Email του χρήστη
     * @param string $code Κωδικός επαλήθευσης
     * @param string $role Ρόλος του χρήστη (driver ή company)
     * @return bool Επιτυχία/αποτυχία
     */
    private function sendVerificationEmail($email, $code, $role)
    {
        // Σε πραγματικό περιβάλλον, εδώ θα υπήρχε κώδικας για την αποστολή email
        // Για τους σκοπούς του refactoring, απλά καταγράφουμε το γεγονός
        Logger::info("Verification email sent to $email with code $code for role $role");
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function verifyAccount($code)
    {
        try {
            // Έλεγχος στον πίνακα drivers
            $driver = $this->verifyDriver($code);
            if ($driver) {
                return [
                    'user_id' => $driver['id'],
                    'role' => 'driver',
                    'email' => $driver['email'],
                    'name' => $driver['first_name'] . ' ' . $driver['last_name']
                ];
            }

            // Έλεγχος στον πίνακα companies
            $company = $this->verifyCompany($code);
            if ($company) {
                return [
                    'user_id' => $company['id'],
                    'role' => 'company',
                    'email' => $company['email'],
                    'name' => $company['company_name']
                ];
            }

            return false;
        } catch (\Exception $e) {
            Logger::error('Error in verifyAccount: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Επαλήθευση λογαριασμού οδηγού
     *
     * @param string $code Κωδικός επαλήθευσης
     * @return array|false Τα στοιχεία του οδηγού ή false σε περίπτωση αποτυχίας
     */
    private function verifyDriver($code)
    {
        try {
            $query = "SELECT * FROM drivers WHERE verification_code = :code AND verification_expires > NOW()";
            $params = ['code' => $code];
            $driver = $this->queryOne($query, $params);

            if ($driver) {
                // Ενημέρωση του λογαριασμού
                $updateQuery = "UPDATE drivers SET is_verified = 1, verification_code = NULL, verification_expires = NULL WHERE id = :id";
                $updateParams = ['id' => $driver['id']];
                $this->execute($updateQuery, $updateParams);

                return $driver;
            }

            return false;
        } catch (\PDOException $e) {
            Logger::error('Error in verifyDriver: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Επαλήθευση λογαριασμού εταιρείας
     *
     * @param string $code Κωδικός επαλήθευσης
     * @return array|false Τα στοιχεία της εταιρείας ή false σε περίπτωση αποτυχίας
     */
    private function verifyCompany($code)
    {
        try {
            $query = "SELECT * FROM companies WHERE verification_code = :code AND verification_expires > NOW()";
            $params = ['code' => $code];
            $company = $this->queryOne($query, $params);

            if ($company) {
                // Ενημέρωση του λογαριασμού
                $updateQuery = "UPDATE companies SET is_verified = 1, verification_code = NULL, verification_expires = NULL WHERE id = :id";
                $updateParams = ['id' => $company['id']];
                $this->execute($updateQuery, $updateParams);

                return $company;
            }

            return false;
        } catch (\PDOException $e) {
            Logger::error('Error in verifyCompany: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sendPasswordResetEmail($email)
    {
        try {
            // Έλεγχος αν υπάρχει χρήστης με το συγκεκριμένο email
            if (!$this->emailExists($email)) {
                return false;
            }

            // Δημιουργία του κωδικού επαναφοράς
            $resetCode = $this->generateVerificationCode();
            $resetExpires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Ενημέρωση του λογαριασμού στη βάση δεδομένων
            $this->updateResetCode($email, $resetCode, $resetExpires);

            // Αποστολή email επαναφοράς
            $this->sendResetEmail($email, $resetCode);

            return true;
        } catch (\Exception $e) {
            Logger::error('Error in sendPasswordResetEmail: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημέρωση του κωδικού επαναφοράς
     *
     * @param string $email Email του χρήστη
     * @param string $resetCode Κωδικός επαναφοράς
     * @param string $resetExpires Ημερομηνία λήξης του κωδικού
     * @return bool Επιτυχία/αποτυχία
     */
    private function updateResetCode($email, $resetCode, $resetExpires)
    {
        try {
            // Ενημέρωση στον πίνακα drivers
            $query = "UPDATE drivers SET reset_code = :reset_code, reset_expires = :reset_expires WHERE email = :email";
            $params = ['reset_code' => $resetCode, 'reset_expires' => $resetExpires, 'email' => $email];
            $this->execute($query, $params);

            // Ενημέρωση στον πίνακα companies
            $query = "UPDATE companies SET reset_code = :reset_code, reset_expires = :reset_expires WHERE email = :email";
            $this->execute($query, $params);

            // Ενημέρωση στον πίνακα admins
            // Προσωρινά απενεργοποιημένο επειδή ο πίνακας 'admins' δεν υπάρχει
            /*
            $query = "UPDATE admins SET reset_code = :reset_code, reset_expires = :reset_expires WHERE email = :email";
            $this->execute($query, $params);
            */

            return true;
        } catch (\PDOException $e) {
            Logger::error('Error in updateResetCode: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Αποστολή email επαναφοράς κωδικού πρόσβασης
     *
     * @param string $email Email του χρήστη
     * @param string $resetCode Κωδικός επαναφοράς
     * @return bool Επιτυχία/αποτυχία
     */
    private function sendResetEmail($email, $resetCode)
    {
        // Σε πραγματικό περιβάλλον, εδώ θα υπήρχε κώδικας για την αποστολή email
        // Για τους σκοπούς του refactoring, απλά καταγράφουμε το γεγονός
        Logger::info("Password reset email sent to $email with code $resetCode");
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function resetPassword($resetCode, $newPassword)
    {
        try {
            // Έλεγχος αν ο κωδικός επαναφοράς είναι έγκυρος
            $user = $this->getUserByResetCode($resetCode);
            if (!$user) {
                return false;
            }

            // Κρυπτογράφηση του νέου κωδικού πρόσβασης
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Ενημέρωση του κωδικού πρόσβασης
            $this->updatePassword($user['table'], $user['id'], $hashedPassword);

            return true;
        } catch (\Exception $e) {
            Logger::error('Error in resetPassword: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Επιστρέφει τον χρήστη με βάση τον κωδικό επαναφοράς
     *
     * @param string $resetCode Κωδικός επαναφοράς
     * @return array|false Τα στοιχεία του χρήστη ή false σε περίπτωση αποτυχίας
     */
    private function getUserByResetCode($resetCode)
    {
        try {
            // Έλεγχος στον πίνακα drivers
            $query = "SELECT id FROM drivers WHERE reset_code = :reset_code AND reset_expires > NOW()";
            $params = ['reset_code' => $resetCode];
            $driver = $this->queryOne($query, $params);

            if ($driver) {
                return [
                    'id' => $driver['id'],
                    'table' => 'drivers'
                ];
            }

            // Έλεγχος στον πίνακα companies
            $query = "SELECT id FROM companies WHERE reset_code = :reset_code AND reset_expires > NOW()";
            $company = $this->queryOne($query, $params);

            if ($company) {
                return [
                    'id' => $company['id'],
                    'table' => 'companies'
                ];
            }

            // Έλεγχος στον πίνακα admins
            // Προσωρινά απενεργοποιημένο επειδή ο πίνακας 'admins' δεν υπάρχει
            /*
            $query = "SELECT id FROM admins WHERE reset_code = :reset_code AND reset_expires > NOW()";
            $admin = $this->queryOne($query, $params);

            if ($admin) {
                return [
                    'id' => $admin['id'],
                    'table' => 'admins'
                ];
            }
            */

            return false;
        } catch (\PDOException $e) {
            Logger::error('Error in getUserByResetCode: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημέρωση του κωδικού πρόσβασης
     *
     * @param string $table Όνομα του πίνακα
     * @param int $userId ID του χρήστη
     * @param string $hashedPassword Κρυπτογραφημένος κωδικός πρόσβασης
     * @return bool Επιτυχία/αποτυχία
     */
    private function updatePassword($table, $userId, $hashedPassword)
    {
        try {
            $query = "UPDATE $table SET password = :password, reset_code = NULL, reset_expires = NULL WHERE id = :id";
            $params = ['password' => $hashedPassword, 'id' => $userId];
            return $this->execute($query, $params) > 0;
        } catch (\PDOException $e) {
            Logger::error('Error in updatePassword: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function changePassword($role, $userId, $currentPassword, $newPassword)
    {
        try {
            // Έλεγχος του τρέχοντος κωδικού πρόσβασης
            if (!$this->verifyPassword($role, $userId, $currentPassword)) {
                return false;
            }

            // Κρυπτογράφηση του νέου κωδικού πρόσβασης
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Ενημέρωση του κωδικού πρόσβασης
            $table = $this->getTableByRole($role);
            return $this->updatePassword($table, $userId, $hashedPassword);
        } catch (\Exception $e) {
            Logger::error('Error in changePassword: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Επαλήθευση του τρέχοντος κωδικού πρόσβασης
     *
     * @param string $role Ρόλος του χρήστη (driver, company ή admin)
     * @param int $userId ID του χρήστη
     * @param string $password Κωδικός πρόσβασης
     * @return bool Επιτυχία/αποτυχία
     */
    private function verifyPassword($role, $userId, $password)
    {
        try {
            $table = $this->getTableByRole($role);
            $query = "SELECT password FROM $table WHERE id = :id";
            $params = ['id' => $userId];
            $user = $this->queryOne($query, $params);

            if ($user && password_verify($password, $user['password'])) {
                return true;
            }

            return false;
        } catch (\PDOException $e) {
            Logger::error('Error in verifyPassword: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Επιστρέφει το όνομα του πίνακα με βάση τον ρόλο
     *
     * @param string $role Ρόλος του χρήστη (driver, company ή admin)
     * @return string Όνομα του πίνακα
     */
    private function getTableByRole($role)
    {
        switch ($role) {
            case 'driver':
                return 'drivers';
            case 'company':
                return 'companies';
            case 'admin':
                return 'admins';
            default:
                throw new \InvalidArgumentException("Invalid role: $role");
        }
    }

    /**
     * Έλεγχος αν ο χρήστης είναι συνδεδεμένος
     *
     * @return bool Αν ο χρήστης είναι συνδεδεμένος
     */
    public function isLoggedIn()
    {
        return Session::has('user_id') && Session::has('role');
    }

    /**
     * Έλεγχος αν ο χρήστης έχει τον συγκεκριμένο ρόλο
     *
     * @param string $role Ρόλος του χρήστη (driver, company ή admin)
     * @return bool Αν ο χρήστης έχει τον συγκεκριμένο ρόλο
     */
    public function hasRole($role)
    {
        return $this->isLoggedIn() && Session::get('role') === $role;
    }

    /**
     * Αποσύνδεση χρήστη
     *
     * @return void
     */
    public function logout()
    {
        Session::destroy();
    }
}

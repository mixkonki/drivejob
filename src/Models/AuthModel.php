<?php

namespace Drivejob\Models;

use Drivejob\Core\Logger;
use Drivejob\Core\Session;
use Drivejob\Models\Driver\ProfileModel;
use Drivejob\Models\Company\CompaniesModel;

/**
 * Μοντέλο για τη διαχείριση της αυθεντικοποίησης και εξουσιοδότησης
 */
class AuthModel
{
    private $pdo;
    private $profileModel;
    private $companiesModel;

    /**
     * Κατασκευαστής του μοντέλου
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->profileModel = new ProfileModel($pdo);
        $this->companiesModel = new CompaniesModel($pdo);
    }

    /**
     * Αυθεντικοποίηση χρήστη
     *
     * @param string $email Email του χρήστη
     * @param string $password Κωδικός πρόσβασης
     * @param string $role Ρόλος του χρήστη (driver ή company)
     * @return array|false Τα στοιχεία του χρήστη ή false σε περίπτωση αποτυχίας
     */
    public function authenticate($email, $password, $role = null)
    {
        try {
            Logger::debug('AuthModel::authenticate called', [
                'email' => $email,
                'role' => $role,
                'session_id' => session_id()
            ]);

            // Έλεγχος αν ο χρήστης είναι οδηγός
            if ($role === 'driver' || $role === null) {
                Logger::debug('Attempting driver authentication');
                $driver = $this->authenticateDriver($email, $password);
                if ($driver) {
                    Logger::debug('Driver authentication successful', [
                        'driver_id' => $driver['id'],
                        'is_verified' => $driver['is_verified']
                    ]);
                    return [
                        'user_id' => $driver['id'],
                        'role' => 'driver',
                        'email' => $driver['email'],
                        'name' => $driver['first_name'] . ' ' . $driver['last_name'],
                        'is_verified' => $driver['is_verified'],
                        'is_active' => $driver['is_verified'] // Χρησιμοποιούμε το is_verified αντί για is_active
                    ];
                } else {
                    Logger::debug('Driver authentication failed');
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
                        'is_active' => $company['is_verified'] // Χρησιμοποιούμε το is_verified αντί για is_active
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
            Logger::debug('AuthModel::authenticateDriver called', [
                'email' => $email,
                'session_id' => session_id()
            ]);

            $query = "SELECT * FROM drivers WHERE email = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$email]);
            $driver = $stmt->fetch(\PDO::FETCH_ASSOC);

            Logger::debug('Driver query result', [
                'driver_found' => !empty($driver),
                'driver_data' => $driver ? [
                    'id' => $driver['id'],
                    'email' => $driver['email'],
                    'is_verified' => $driver['is_verified'] ?? null,
                    'is_active' => $driver['is_active'] ?? null
                ] : null
            ]);

            if ($driver) {
                $passwordVerified = password_verify($password, $driver['password']);
                Logger::debug('Password verification result', [
                    'password_verified' => $passwordVerified
                ]);

                if ($passwordVerified) {
                    // Ενημέρωση της ημερομηνίας τελευταίας σύνδεσης
                    $this->updateLastLogin('drivers', $driver['id']);
                    return $driver;
                }
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
            $query = "SELECT * FROM companies WHERE email = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$email]);
            $company = $stmt->fetch(\PDO::FETCH_ASSOC);

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
        // Προσωρινά απενεργοποιημένο επειδή ο πίνακας 'admins' δεν υπάρχει
        Logger::debug('authenticateAdmin method is disabled');
        return false;

        /*
        try {
            $query = "SELECT * FROM admins WHERE email = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$email]);
            $admin = $stmt->fetch(\PDO::FETCH_ASSOC);

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
        */
    }

    /**
     * Ενημέρωση της ημερομηνίας τελευταίας σύνδεσης
     *
     * @param string $table Όνομα του πίνακα
     * @param int $userId ID του χρήστη
     * @return bool Επιτυχία/αποτυχία
     */
    private function updateLastLogin($table, $userId)
    {
        try {
            $query = "UPDATE $table SET last_login = NOW() WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([$userId]);
        } catch (\PDOException $e) {
            Logger::error('Error in updateLastLogin: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Εγγραφή νέου οδηγού
     *
     * @param array $data Δεδομένα οδηγού
     * @return int|false ID του νέου οδηγού ή false σε περίπτωση αποτυχίας
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
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');

            $query = "INSERT INTO drivers (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $this->pdo->prepare($query);

            if ($stmt->execute(array_values($data))) {
                $driverId = $this->pdo->lastInsertId();

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
     * Εγγραφή νέας εταιρείας
     *
     * @param array $data Δεδομένα εταιρείας
     * @return int|false ID της νέας εταιρείας ή false σε περίπτωση αποτυχίας
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
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');

            $query = "INSERT INTO companies (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $this->pdo->prepare($query);

            if ($stmt->execute(array_values($data))) {
                $companyId = $this->pdo->lastInsertId();

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
     * Έλεγχος αν υπάρχει ήδη χρήστης με το ίδιο email
     *
     * @param string $email Email του χρήστη
     * @return bool Αν υπάρχει ήδη χρήστης με το ίδιο email
     */
    public function emailExists($email)
    {
        try {
            // Έλεγχος στον πίνακα drivers
            $query = "SELECT COUNT(*) FROM drivers WHERE email = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                return true;
            }

            // Έλεγχος στον πίνακα companies
            $query = "SELECT COUNT(*) FROM companies WHERE email = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                return true;
            }

            // Έλεγχος στον πίνακα admins
            // Προσωρινά απενεργοποιημένο επειδή ο πίνακας 'admins' δεν υπάρχει
            /*
            $query = "SELECT COUNT(*) FROM admins WHERE email = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
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
     * Επαλήθευση λογαριασμού
     *
     * @param string $code Κωδικός επαλήθευσης
     * @return array|false Τα στοιχεία του χρήστη ή false σε περίπτωση αποτυχίας
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
            $query = "SELECT * FROM drivers WHERE verification_code = ? AND verification_expires > NOW()";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$code]);
            $driver = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($driver) {
                // Ενημέρωση του λογαριασμού
                $updateQuery = "UPDATE drivers SET is_verified = 1, verification_code = NULL, verification_expires = NULL WHERE id = ?";
                $updateStmt = $this->pdo->prepare($updateQuery);
                $updateStmt->execute([$driver['id']]);

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
            $query = "SELECT * FROM companies WHERE verification_code = ? AND verification_expires > NOW()";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$code]);
            $company = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($company) {
                // Ενημέρωση του λογαριασμού
                $updateQuery = "UPDATE companies SET is_verified = 1, verification_code = NULL, verification_expires = NULL WHERE id = ?";
                $updateStmt = $this->pdo->prepare($updateQuery);
                $updateStmt->execute([$company['id']]);

                return $company;
            }

            return false;
        } catch (\PDOException $e) {
            Logger::error('Error in verifyCompany: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Αποστολή συνδέσμου επαναφοράς συνθηματικού
     *
     * @param string $email Email του χρήστη
     * @return bool Επιτυχία/αποτυχία
     */
    public function sendPasswordResetLink($email)
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
            Logger::error('Error in sendPasswordResetLink: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Έλεγχος εγκυρότητας του token επαναφοράς
     *
     * @param string $token Token επαναφοράς
     * @return bool Αν το token είναι έγκυρο
     */
    public function isValidResetToken($token)
    {
        try {
            // Έλεγχος στον πίνακα drivers
            $query = "SELECT COUNT(*) FROM drivers WHERE reset_code = ? AND reset_expires > NOW()";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$token]);
            if ($stmt->fetchColumn() > 0) {
                return true;
            }

            // Έλεγχος στον πίνακα companies
            $query = "SELECT COUNT(*) FROM companies WHERE reset_code = ? AND reset_expires > NOW()";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$token]);
            if ($stmt->fetchColumn() > 0) {
                return true;
            }

            // Έλεγχος στον πίνακα admins
            // Προσωρινά απενεργοποιημένο επειδή ο πίνακας 'admins' δεν υπάρχει
            /*
            $query = "SELECT COUNT(*) FROM admins WHERE reset_code = ? AND reset_expires > NOW()";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$token]);
            if ($stmt->fetchColumn() > 0) {
                return true;
            }
            */

            return false;
        } catch (\PDOException $e) {
            Logger::error('Error in isValidResetToken: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Αποστολή email επαναφοράς κωδικού πρόσβασης
     *
     * @param string $email Email του χρήστη
     * @return bool Επιτυχία/αποτυχία
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
            $query = "UPDATE drivers SET reset_code = ?, reset_expires = ? WHERE email = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$resetCode, $resetExpires, $email]);

            // Ενημέρωση στον πίνακα companies
            $query = "UPDATE companies SET reset_code = ?, reset_expires = ? WHERE email = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$resetCode, $resetExpires, $email]);

            // Ενημέρωση στον πίνακα admins
            // Προσωρινά απενεργοποιημένο επειδή ο πίνακας 'admins' δεν υπάρχει
            /*
            $query = "UPDATE admins SET reset_code = ?, reset_expires = ? WHERE email = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$resetCode, $resetExpires, $email]);
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
     * Επαναφορά κωδικού πρόσβασης
     *
     * @param string $resetCode Κωδικός επαναφοράς
     * @param string $newPassword Νέος κωδικός πρόσβασης
     * @return bool Επιτυχία/αποτυχία
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
            $query = "SELECT id FROM drivers WHERE reset_code = ? AND reset_expires > NOW()";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$resetCode]);
            $driver = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($driver) {
                return [
                    'id' => $driver['id'],
                    'table' => 'drivers'
                ];
            }

            // Έλεγχος στον πίνακα companies
            $query = "SELECT id FROM companies WHERE reset_code = ? AND reset_expires > NOW()";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$resetCode]);
            $company = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($company) {
                return [
                    'id' => $company['id'],
                    'table' => 'companies'
                ];
            }

            // Έλεγχος στον πίνακα admins
            // Προσωρινά απενεργοποιημένο επειδή ο πίνακας 'admins' δεν υπάρχει
            /*
            $query = "SELECT id FROM admins WHERE reset_code = ? AND reset_expires > NOW()";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$resetCode]);
            $admin = $stmt->fetch(\PDO::FETCH_ASSOC);

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
            $query = "UPDATE $table SET password = ?, reset_code = NULL, reset_expires = NULL WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute([$hashedPassword, $userId]);
        } catch (\PDOException $e) {
            Logger::error('Error in updatePassword: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Αλλαγή κωδικού πρόσβασης
     *
     * @param string $role Ρόλος του χρήστη (driver, company ή admin)
     * @param int $userId ID του χρήστη
     * @param string $currentPassword Τρέχων κωδικός πρόσβασης
     * @param string $newPassword Νέος κωδικός πρόσβασης
     * @return bool Επιτυχία/αποτυχία
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
            $query = "SELECT password FROM $table WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$userId]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

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

    /**
     * Επαναποστέλλει το email επαλήθευσης στον χρήστη
     *
     * @param int $userId Το ID του χρήστη
     * @return bool Επιτυχία ή αποτυχία
     */
    public function resendVerificationEmail($userId)
    {
        try {
            // Ανάκτηση των στοιχείων του χρήστη
            $role = Session::get('role');
            $table = $this->getTableByRole($role);

            $query = "SELECT * FROM $table WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$userId]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user) {
                Logger::error("User not found for resendVerificationEmail: $userId");
                return false;
            }

            // Δημιουργία νέου token επαλήθευσης
            $verificationCode = $this->generateVerificationCode();
            $verificationExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Αποθήκευση του νέου token στη βάση δεδομένων
            $updateQuery = "UPDATE $table SET verification_code = ?, verification_expires = ? WHERE id = ?";
            $updateStmt = $this->pdo->prepare($updateQuery);
            $updateStmt->execute([$verificationCode, $verificationExpires, $userId]);

            // Αποστολή του email επαλήθευσης
            $email = $user['email'];
            $name = $role === 'driver' ? $user['first_name'] . ' ' . $user['last_name'] : $user['company_name'];

            // Αποστολή του email επαλήθευσης
            return $this->sendVerificationEmail($email, $verificationCode, $role);
        } catch (\Exception $e) {
            Logger::error('Error in resendVerificationEmail: ' . $e->getMessage());
            return false;
        }
    }
}

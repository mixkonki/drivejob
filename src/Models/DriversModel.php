<?php
namespace Drivejob\Models;
use PDO;
use Drivejob\Core\Logger;

use PDOException;

class DriversModel {
    private $pdo;
    
    // Ορισμός σταθερών για τύπους αδειών
    const LICENSE_TYPE_DRIVING = 'driving_license';
    const LICENSE_TYPE_PEI = 'pei';
    const LICENSE_TYPE_ADR = 'adr_certificate';
    const LICENSE_TYPE_TACHOGRAPH = 'tachograph_card';
    const LICENSE_TYPE_OPERATOR = 'operator_license';
    const LICENSE_TYPE_SPECIAL = 'special_license';
    
    // Συνάρτηση κατασκευαστή
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Δημιουργεί έναν νέο λογαριασμό οδηγού
     * 
     * @param array $data Δεδομένα νέου οδηγού
     * @return int ID του νέου οδηγού
     */
    public function create($data) {
        $sql = "INSERT INTO drivers (email, password, last_name, first_name, phone, is_verified) 
                VALUES (:email, :password, :last_name, :first_name, :phone, :is_verified)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'email' => $data['email'],
            'password' => $data['password'],
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'phone' => $data['phone'],
            'is_verified' => $data['is_verified'] ?? 0
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Ενημερώνει τα βασικά στοιχεία ενός οδηγού
     * 
     * @param int $id ID του οδηγού
     * @param array $data Δεδομένα προς ενημέρωση
     * @return bool Επιτυχία/αποτυχία
     */
    public function update($id, $data) {
        $sql = "UPDATE drivers SET 
                email = :email, 
                last_name = :last_name, 
                first_name = :first_name, 
                phone = :phone 
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'email' => $data['email'],
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'phone' => $data['phone']
        ]);
    }
    
    /**
     * Επιστρέφει τα στοιχεία ενός οδηγού με βάση το ID
     * 
     * @param int $id ID του οδηγού
     * @return array|false Στοιχεία οδηγού ή false αν δεν βρέθηκε
     */
    public function getDriverById($id) {
        $sql = "SELECT * FROM drivers WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Επιστρέφει έναν οδηγό με βάση το email
     * 
     * @param string $email Email του οδηγού
     * @return array|false Στοιχεία οδηγού ή false αν δεν βρέθηκε
     */
    public function getDriverByEmail($email) {
        $sql = "SELECT * FROM drivers WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Διαγράφει έναν οδηγό
     * 
     * @param int $id ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function delete($id) {
        $sql = "DELETE FROM drivers WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Ενημερώνει την κατάσταση επαλήθευσης του οδηγού
     * 
     * @param string $email Email του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function verifyDriver($email) {
        $sql = "UPDATE drivers SET is_verified = 1 WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['email' => $email]);
    }
    
    /**
     * Ενημερώνει τον κωδικό πρόσβασης ενός οδηγού
     * 
     * @param int $id ID του οδηγού
     * @param string $password Νέος κωδικός πρόσβασης (κρυπτογραφημένος)
     * @return bool Επιτυχία/αποτυχία
     */
    public function updatePassword($id, $password) {
        $sql = "UPDATE drivers SET password = :password WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'password' => $password
        ]);
    }
    
    /**
     * Ενημερώνει το προφίλ ενός οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $data Δεδομένα προφίλ
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateProfile($driverId, $data) {
        try {
            $columns = [];
            $values = [];
            
            // Δημιουργία του μέρους SET του SQL ερωτήματος
            foreach ($data as $column => $value) {
                if ($value === null) {
                    $columns[] = "`$column` = NULL";
                } else {
                    $columns[] = "`$column` = ?";
                    $values[] = $value;
                }
            }
            
            // Προσθήκη του ID του οδηγού στο τέλος των παραμέτρων
            $values[] = $driverId;
            
            $sql = "UPDATE drivers SET " . implode(', ', $columns) . " WHERE id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($values);
            
            // Ενημέρωση των flags αδειών
            $this->updateDriverFlags($driverId);
            
            return $result;
        } catch (PDOException $e) {
            Logger::error('Error in updateProfile: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ενημερώνει τα flags αδειών (driving_license, adr_certificate, operator_license) βάσει των σχετικών εγγραφών
     * 
     * @param int $driverId ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    private function updateDriverFlags($driverId) {
        try {
            // Δημιουργία του πίνακα με τα flags και τα αντίστοιχα SQL ερωτήματα
            $flagQueries = [
                'driving_license' => "SELECT COUNT(*) FROM driver_licenses WHERE driver_id = ?",
                'adr_certificate' => "SELECT COUNT(*) FROM driver_adr_certificates WHERE driver_id = ?",
                'operator_license' => "SELECT COUNT(*) FROM driver_operator_licenses WHERE driver_id = ?"
            ];
            
            $flags = [];
            
            // Εκτέλεση των ερωτημάτων για κάθε flag
            foreach ($flagQueries as $flag => $query) {
                $stmt = $this->pdo->prepare($query);
                $stmt->execute([$driverId]);
                $flags[$flag] = ($stmt->fetchColumn() > 0) ? 1 : 0;
            }
            
            // Ενημέρωση των flags στον πίνακα drivers
            $updateFlags = $this->pdo->prepare("UPDATE drivers SET 
                driving_license = ?, 
                adr_certificate = ?, 
                operator_license = ? 
                WHERE id = ?");
                
            return $updateFlags->execute([
                $flags['driving_license'], 
                $flags['adr_certificate'], 
                $flags['operator_license'], 
                $driverId
            ]);
        } catch (PDOException $e) {
            Logger::error('Error in updateDriverFlags: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ενημερώνει την εικόνα προφίλ ενός οδηγού
     * 
     * @param int $id ID του οδηγού
     * @param string $imagePath Διαδρομή εικόνας
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateProfileImage($id, $imagePath) {
        $sql = "UPDATE drivers SET profile_image = :profile_image WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'profile_image' => $imagePath
        ]);
    }
    
    /**
     * Ενημερώνει το αρχείο βιογραφικού ενός οδηγού
     * 
     * @param int $id ID του οδηγού
     * @param string $filePath Διαδρομή αρχείου
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateResumeFile($id, $filePath) {
        $sql = "UPDATE drivers SET resume_file = :resume_file WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'resume_file' => $filePath
        ]);
    }
    
    /**
     * Ενημερώνει την τελευταία σύνδεση του οδηγού
     * 
     * @param int $id ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateLastLogin($id) {
        $sql = "UPDATE drivers SET last_login = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Ενημερώνει την αξιολόγηση ενός οδηγού
     * 
     * @param int $id ID του οδηγού
     * @param float $rating Νέα αξιολόγηση
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateRating($id, $rating) {
        $sql = "UPDATE drivers SET 
                rating = ((rating * rating_count) + :rating) / (rating_count + 1),
                rating_count = rating_count + 1
                WHERE id = :id";
                
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'rating' => $rating
        ]);
    }
    
    /**
     * Επιστρέφει όλους τους οδηγούς
     * 
     * @param int $limit Μέγιστος αριθμός αποτελεσμάτων
     * @param int $offset Μετατόπιση αποτελεσμάτων
     * @return array Λίστα οδηγών
     */
    public function getAllDrivers($limit = 100, $offset = 0) {
        $sql = "SELECT id, first_name, last_name, email, phone, city, country, 
                       experience_years, profile_image, rating
                FROM drivers 
                WHERE is_verified = 1 
                ORDER BY last_name, first_name 
                LIMIT :limit OFFSET :offset";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Μετράει το συνολικό αριθμό οδηγών
     * 
     * @return int Αριθμός οδηγών
     */
    public function countDrivers() {
        $sql = "SELECT COUNT(*) FROM drivers WHERE is_verified = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    /**
     * Αναζητά οδηγούς με βάση κριτήρια
     * 
     * @param array $params Παράμετροι αναζήτησης
     * @param int $page Αριθμός σελίδας
     * @param int $limit Αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Αποτελέσματα αναζήτησης και πληροφορίες σελιδοποίησης
     */
    public function searchDrivers($params, $page = 1, $limit = 10) {
        $conditions = ["is_verified = 1", "available_for_work = 1"];
        $parameters = [];
        
        // Φίλτρο με βάση την εμπειρία
        if (isset($params['min_experience']) && $params['min_experience'] > 0) {
            $conditions[] = "experience_years >= :min_experience";
            $parameters['min_experience'] = $params['min_experience'];
        }
        
        // Φίλτρο με βάση την προτιμώμενη τοποθεσία
        if (isset($params['location']) && $params['location']) {
            $conditions[] = "(preferred_location LIKE :location OR city LIKE :location OR country LIKE :location)";
            $parameters['location'] = '%' . $params['location'] . '%';
        }
        
        // Φίλτρο με βάση την άδεια οδήγησης
        if (isset($params['driving_license']) && $params['driving_license']) {
            $conditions[] = "driving_license = 1";
            // Χρειάζεται ειδικός έλεγχος για συγκεκριμένη κατηγορία
            if ($params['driving_license'] !== 'any') {
                $conditions[] = "EXISTS (SELECT 1 FROM driver_licenses dl WHERE dl.driver_id = drivers.id AND dl.license_type = :license_type)";
                $parameters['license_type'] = $params['driving_license'];
            }
        }
        
        // Φίλτρο για ADR πιστοποίηση
        if (isset($params['adr_certificate']) && $params['adr_certificate']) {
            $conditions[] = "adr_certificate = 1";
        }
        
        // Φίλτρο για άδεια χειριστή
        if (isset($params['operator_license']) && $params['operator_license']) {
            $conditions[] = "operator_license = 1";
        }
        
        // Φίλτρο για σεμινάρια
        if (isset($params['training_seminars']) && $params['training_seminars']) {
            $conditions[] = "training_seminars = 1";
        }
        
        // Αναζήτηση βάσει ονόματος ή επωνύμου
        if (isset($params['name']) && $params['name']) {
            $conditions[] = "(first_name LIKE :name OR last_name LIKE :name)";
            $parameters['name'] = '%' . $params['name'] . '%';
        }
        
        // Σύνθεση του SQL ερωτήματος
        $whereClause = implode(" AND ", $conditions);
        $offset = ($page - 1) * $limit;
        
        // Μέτρηση συνολικών αποτελεσμάτων
        $countSql = "SELECT COUNT(*) FROM drivers WHERE $whereClause";
        $countStmt = $this->pdo->prepare($countSql);
        
        foreach ($parameters as $key => $value) {
            if (is_int($value)) {
                $countStmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } else {
                $countStmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
            }
        }
        
        $countStmt->execute();
        $totalResults = $countStmt->fetchColumn();
        
        // Εκτέλεση του κύριου ερωτήματος
        $sql = "SELECT id, first_name, last_name, city, country, experience_years, 
                       driving_license, adr_certificate, operator_license, 
                       training_seminars, preferred_job_type, preferred_location, 
                       profile_image, rating 
                FROM drivers 
                WHERE $whereClause 
                ORDER BY last_login DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->pdo->prepare($sql);
        
        // Προσθήκη των παραμέτρων για το LIMIT και OFFSET
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        // Προσθήκη των υπόλοιπων παραμέτρων
        foreach ($parameters as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
            }
        }
        
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'results' => $results,
            'pagination' => [
                'total' => $totalResults,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($totalResults / $limit)
            ]
        ];
    }
    
    /**
     * Ελέγχει αν ένα email υπάρχει ήδη
     * 
     * @param string $email Email προς έλεγχο
     * @return bool Αν υπάρχει ήδη
     */
    public function emailExists($email) {
        $sql = "SELECT COUNT(*) FROM drivers WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Επιστρέφει τους πιο πρόσφατους διαθέσιμους οδηγούς
     * 
     * @param int $limit Μέγιστος αριθμός αποτελεσμάτων
     * @return array Λίστα οδηγών
     */
    public function getRecentAvailableDrivers($limit = 5) {
        $sql = "SELECT id, first_name, last_name, city, country, 
                       experience_years, profile_image, rating
                FROM drivers 
                WHERE is_verified = 1 AND available_for_work = 1 
                ORDER BY last_login DESC 
                LIMIT :limit";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Επιστρέφει τους κορυφαίους οδηγούς με βάση την αξιολόγηση
     * 
     * @param int $limit Μέγιστος αριθμός αποτελεσμάτων
     * @return array Λίστα οδηγών
     */
    public function getTopRatedDrivers($limit = 5) {
        $sql = "SELECT id, first_name, last_name, city, country, 
                       experience_years, profile_image, rating
                FROM drivers 
                WHERE is_verified = 1 AND rating > 0 
                ORDER BY rating DESC, rating_count DESC 
                LIMIT :limit";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // -------------------- ΆΔΕΙΕΣ ΟΔΗΓΗΣΗΣ --------------------
    
    /**
     * Διαγράφει όλες τις άδειες οδήγησης του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteDriverLicenses($driverId) {
        try {
            $sql = "DELETE FROM driver_licenses WHERE driver_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$driverId]);
            
            if ($result) {
                // Ενημέρωση του flag στον πίνακα drivers
                $updateFlag = $this->pdo->prepare("UPDATE drivers SET driving_license = 0 WHERE id = ?");
                $updateFlag->execute([$driverId]);
            }
            
            return $result;
        } catch (PDOException $e) {
            Logger::error('Error in deleteDriverLicenses: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημερώνει την εικόνα του διπλώματος του οδηγού (εμπρόσθια ή οπίσθια όψη)
     * 
     * @param int $driverId ID του οδηγού
     * @param string $imageType Τύπος εικόνας ('license_front_image' ή 'license_back_image')
     * @param string $imagePath Διαδρομή προς την εικόνα
     * @return bool Επιτυχία ή αποτυχία της ενημέρωσης
     */
    public function updateDriverLicenseImage($driverId, $imageType, $imagePath) {
        try {
            // Βεβαιωνόμαστε ότι το imageType είναι ασφαλές για SQL
            $validImageTypes = [
                'license_front_image', 
                'license_back_image', 
                'adr_front_image', 
                'adr_back_image', 
                'operator_front_image', 
                'operator_back_image', 
                'tachograph_front_image', 
                'tachograph_back_image'
            ];
            
            if (!in_array($imageType, $validImageTypes)) {
                Logger::error('Invalid image type: ' . $imageType);
                return false;
            }
            
            $sql = "UPDATE drivers SET $imageType = :imagePath WHERE id = :driverId";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':imagePath', $imagePath, PDO::PARAM_STR);
            $stmt->bindParam(':driverId', $driverId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            Logger::error('Σφάλμα κατά την ενημέρωση εικόνας διπλώματος: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Προσθήκη άδειας οδήγησης για τον οδηγό
     * 
     * @param int $driverId ID του οδηγού
     * @param string $licenseType Τύπος άδειας (A, B, C, D, κλπ.)
     * @param bool $hasPei Αν έχει ΠΕΙ
     * @param string $expiryDate Ημερομηνία λήξης της κατηγορίας
     * @param string $licenseNumber Αριθμός άδειας
     * @param string $peiExpiryC Ημερομηνία λήξης ΠΕΙ για κατηγορία C
     * @param string $peiExpiryD Ημερομηνία λήξης ΠΕΙ για κατηγορία D
     * @param string $licenseDocumentExpiry Ημερομηνία λήξης εντύπου
     * @return bool Επιτυχία ή αποτυχία της προσθήκης
     */
    public function addDriverLicense($driverId, $licenseType, $hasPei, $expiryDate, $licenseNumber, $peiExpiryC = null, $peiExpiryD = null, $licenseDocumentExpiry = null) {
        try {
            // Καθορισμός της ημερομηνίας λήξης ΠΕΙ ανάλογα με την κατηγορία
            $peiExpiryCValue = null;
            $peiExpiryDValue = null;
            
            if ($hasPei) {
                if (in_array($licenseType, ['C', 'CE', 'C1', 'C1E'])) {
                    $peiExpiryCValue = $peiExpiryC;
                } else if (in_array($licenseType, ['D', 'DE', 'D1', 'D1E'])) {
                    $peiExpiryDValue = $peiExpiryD;
                }
            }
            
            $sql = "INSERT INTO driver_licenses (
                    driver_id, license_type, has_pei, expiry_date, 
                    license_number, pei_expiry_c, pei_expiry_d, license_document_expiry
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $driverId, 
                $licenseType, 
                $hasPei ? 1 : 0, 
                $expiryDate, 
                $licenseNumber,
                $peiExpiryCValue, 
                $peiExpiryDValue,
                $licenseDocumentExpiry
            ]);
            
            if ($result) {
                // Ενημέρωση του flag στον πίνακα drivers
                $updateFlag = $this->pdo->prepare("UPDATE drivers SET driving_license = 1 WHERE id = ?");
                $updateFlag->execute([$driverId]);
            }
            
            return $result;
        } catch (PDOException $e) {
            Logger::error('Error in addDriverLicense: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Λαμβάνει όλες τις άδειες οδήγησης του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array Λίστα με άδειες οδήγησης
     */
    public function getDriverLicenses($driverId) {
        $sql = "SELECT * FROM driver_licenses WHERE driver_id = ? ORDER BY license_type";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$driverId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Λαμβάνει την ημερομηνία λήξης για συγκεκριμένη κατηγορία άδειας οδήγησης
     * 
     * @param int $driverId ID του οδηγού
     * @param string $licenseType Τύπος άδειας
     * @return string|null Ημερομηνία λήξης ή null αν δεν βρέθηκε
     */
    public function getDriverLicenseExpiryDate($driverId, $licenseType) {
        $sql = "SELECT expiry_date FROM driver_licenses WHERE driver_id = ? AND license_type = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$driverId, $licenseType]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['expiry_date'] : null;
    }

    /**
     * Ελέγχει αν ο οδηγός έχει ΠΕΙ για συγκεκριμένη κατηγορία
     * 
     * @param int $driverId ID του οδηγού
     * @param string $licenseType Τύπος άδειας
     * @return bool Αν έχει ΠΕΙ
     */
    public function hasDriverPEI($driverId, $licenseType) {
        $sql = "SELECT has_pei FROM driver_licenses WHERE driver_id = ? AND license_type = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$driverId, $licenseType]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['has_pei'] == 1;
    }

    /**
     * Λαμβάνει τις ημερομηνίες λήξης των ΠΕΙ του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array Ημερομηνίες λήξης ΠΕΙ για εμπορεύματα και επιβάτες
     */
    public function getDriverPEIExpiryDates($driverId) {
        $sql = "SELECT pei_expiry_c, pei_expiry_d FROM driver_licenses 
                WHERE driver_id = ? AND has_pei = 1 
                ORDER BY pei_expiry_c DESC, pei_expiry_d DESC 
                LIMIT 1";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$driverId]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: ['pei_expiry_c' => null, 'pei_expiry_d' => null];
    }

    /**
     * Ενημερώνει τον αριθμό άδειας οδήγησης του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param string $licenseNumber Αριθμός άδειας
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverLicenseNumber($driverId, $licenseNumber) {
        $sql = "UPDATE drivers SET license_number = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$licenseNumber, $driverId]);
    }
    
    // -------------------- ΠΙΣΤΟΠΟΙΗΤΙΚΑ ADR --------------------
    
    /**
     * Λαμβάνει το πιστοποιητικό ADR του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array|false Στοιχεία πιστοποιητικού ή false
     */
    public function getDriverADRCertificate($driverId) {
        $sql = "SELECT * FROM driver_adr_certificates WHERE driver_id = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$driverId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Διαγράφει το πιστοποιητικό ADR του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteDriverADRCertificate($driverId) {
        try {
            $sql = "DELETE FROM driver_adr_certificates WHERE driver_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$driverId]);
            
            if ($result) {
                // Ενημέρωση του flag στον πίνακα drivers
                $updateFlag = $this->pdo->prepare("UPDATE drivers SET adr_certificate = 0 WHERE id = ?");
                $updateFlag->execute([$driverId]);
            }
            
            return $result;
        } catch (PDOException $e) {
            Logger::error('Error in deleteDriverADRCertificate: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ενημερώνει το πιστοποιητικό ADR του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $adrData Δεδομένα πιστοποιητικού ADR
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverADRCertificate($driverId, $adrData) {
        try {
            // Έλεγχος αν υπάρχει ήδη εγγραφή
            $existingCert = $this->getDriverADRCertificate($driverId);
            
            if ($existingCert) {
                // Ενημέρωση υπάρχουσας εγγραφής
                $sql = "UPDATE driver_adr_certificates 
                        SET adr_type = ?, certificate_number = ?, expiry_date = ? 
                        WHERE driver_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute([
                    $adrData['adr_type'],
                    $adrData['certificate_number'],
                    $adrData['expiry_date'] ?: null,
                    $driverId
                ]);
            } else {
                // Δημιουργία νέας εγγραφής
                $sql = "INSERT INTO driver_adr_certificates 
                        (driver_id, adr_type, certificate_number, expiry_date) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute([
                    $driverId,
                    $adrData['adr_type'],
                    $adrData['certificate_number'],
                    $adrData['expiry_date'] ?: null
                ]);
            }
            
            if ($result) {
                // Ενημέρωση του flag στον πίνακα drivers
                $updateFlag = $this->pdo->prepare("UPDATE drivers SET adr_certificate = 1, adr_certificate_expiry = ? WHERE id = ?");
                $updateFlag->execute([$adrData['expiry_date'] ?: null, $driverId]);
            }
            
            return $result;
        } catch (PDOException $e) {
            Logger::error('Error in updateDriverADRCertificate: ' . $e->getMessage());
            return false;
        }
    }
    
    // -------------------- ΆΔΕΙΕΣ ΧΕΙΡΙΣΤΗ ΜΗΧΑΝΗΜΑΤΩΝ --------------------
    
    /**
     * Λαμβάνει τις υποειδικότητες της άδειας χειριστή μηχανημάτων
     * 
     * @param int $operatorLicenseId ID της άδειας χειριστή
     * @return array Λίστα υποειδικοτήτων
     */
    public function getDriverOperatorSubSpecialities($operatorLicenseId) {
        try {
            // Έλεγχος ύπαρξης πίνακα
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'driver_operator_sub_specialities'");
            if ($tableCheck->rowCount() == 0) {
                Logger::warning("Ο πίνακας driver_operator_sub_specialities δεν υπάρχει", "DriversModel");
                return [];
            }
            
            // Έλεγχος αν η στήλη group_type υπάρχει στον πίνακα
            $hasGroupTypeColumn = $this->checkColumnExists('driver_operator_sub_specialities', 'group_type');
            
            // Έλεγχος ύπαρξης πίνακα groups
            $hasGroupsTable = $this->checkTableExists('driver_operator_sub_speciality_groups');
            
            if ($hasGroupTypeColumn) {
                // Χρήση της στήλης group_type
                $sql = "SELECT id, sub_speciality, group_type FROM driver_operator_sub_specialities WHERE operator_license_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$operatorLicenseId]);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                return $result;
            } else if ($hasGroupsTable) {
                // Συνδυασμός με τον πίνακα ομάδων
                $sql = "SELECT dos.id, dos.sub_speciality, COALESCE(dosg.group_type, 'A') as group_type 
                        FROM driver_operator_sub_specialities dos
                        LEFT JOIN driver_operator_sub_speciality_groups dosg ON dos.id = dosg.sub_speciality_id
                        WHERE dos.operator_license_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$operatorLicenseId]);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                return $result;
            } else {
                // Χωρίς πληροφορίες ομάδας
                $sql = "SELECT id, sub_speciality, 'A' as group_type FROM driver_operator_sub_specialities WHERE operator_license_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$operatorLicenseId]);
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                return $result;
            }
        } catch (PDOException $e) {
            Logger::error("Σφάλμα λήψης υποειδικοτήτων: " . $e->getMessage(), "DriversModel");
            return [];
        }
    }

    /**
     * Προσθέτει μια υποειδικότητα στην άδεια χειριστή μηχανημάτων
     * 
     * @param int $operatorLicenseId ID της άδειας χειριστή
     * @param string $subSpeciality Κωδικός υποειδικότητας
     * @param string $groupType Τύπος ομάδας (A ή B)
     * @return bool Επιτυχία/αποτυχία
     */
    public function addDriverOperatorSubSpeciality($operatorLicenseId, $subSpeciality, $groupType = 'A') {
        try {
            // Έλεγχος εγκυρότητας του $groupType
            if ($groupType !== 'A' && $groupType !== 'B') {
                $groupType = 'A';
            }
            
            // Έλεγχος αν υπάρχει ήδη η υποειδικότητα (για αποφυγή διπλοεγγραφών)
            $checkSql = "SELECT COUNT(*) FROM driver_operator_sub_specialities 
                         WHERE operator_license_id = ? AND sub_speciality = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$operatorLicenseId, $subSpeciality]);
            $exists = $checkStmt->fetchColumn() > 0;
            
            if ($exists) {
                // Διαγραφή της υπάρχουσας εγγραφής
                $deleteSql = "DELETE FROM driver_operator_sub_specialities 
                             WHERE operator_license_id = ? AND sub_speciality = ?";
                $deleteStmt = $this->pdo->prepare($deleteSql);
                $deleteStmt->execute([$operatorLicenseId, $subSpeciality]);
            }
            
            // Έλεγχος αν η στήλη group_type υπάρχει στον πίνακα
            $hasGroupTypeColumn = $this->checkColumnExists('driver_operator_sub_specialities', 'group_type');
            
            // Αν δεν υπάρχει η στήλη group_type, προσπάθησε να την προσθέσεις
            if (!$hasGroupTypeColumn) {
                try {
                    $alterSql = "ALTER TABLE driver_operator_sub_specialities 
                                ADD COLUMN group_type CHAR(1) DEFAULT 'A' AFTER sub_speciality";
                    $this->pdo->exec($alterSql);
                    $hasGroupTypeColumn = true;
                } catch (PDOException $e) {
                    Logger::error("Σφάλμα προσθήκης στήλης group_type: " . $e->getMessage(), "DriversModel");
                }
            }
            
            // Εισαγωγή της υποειδικότητας
            if ($hasGroupTypeColumn) {
                $sql = "INSERT INTO driver_operator_sub_specialities 
                        (operator_license_id, sub_speciality, group_type) 
                        VALUES (?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute([$operatorLicenseId, $subSpeciality, $groupType]);
                
                return $result;
            } else {
                // Παλιός τρόπος με χωριστό πίνακα για τις ομάδες
                // Προσθήκη της υποειδικότητας
                $sql = "INSERT INTO driver_operator_sub_specialities 
                        (operator_license_id, sub_speciality) 
                        VALUES (?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute([$operatorLicenseId, $subSpeciality]);
                
                if ($result) {
                    $subSpecialityId = $this->pdo->lastInsertId();
                    
                    // Έλεγχος ύπαρξης πίνακα ομάδων
                    $hasGroupsTable = $this->checkTableExists('driver_operator_sub_speciality_groups');
                    
                    if (!$hasGroupsTable) {
                        // Δημιουργία του πίνακα ομάδων αν δεν υπάρχει
                        try {
                            $createTableSql = "
                                CREATE TABLE IF NOT EXISTS driver_operator_sub_speciality_groups (
                                    id INT NOT NULL AUTO_INCREMENT,
                                    sub_speciality_id INT NOT NULL,
                                    group_type CHAR(1) NOT NULL DEFAULT 'A',
                                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                                    PRIMARY KEY (id),
                                    KEY (sub_speciality_id),
                                    FOREIGN KEY (sub_speciality_id) REFERENCES driver_operator_sub_specialities(id) ON DELETE CASCADE
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                            ";
                            $this->pdo->exec($createTableSql);
                            $hasGroupsTable = true;
                        } catch (PDOException $e) {
                            Logger::error("Σφάλμα δημιουργίας πίνακα ομάδων: " . $e->getMessage(), "DriversModel");
                        }
                    }
                    
                    // Προσθήκη της ομάδας
                    if ($hasGroupsTable) {
                        try {
                            $groupSql = "INSERT INTO driver_operator_sub_speciality_groups 
                                         (sub_speciality_id, group_type) 
                                         VALUES (?, ?)";
                            $groupStmt = $this->pdo->prepare($groupSql);
                            $groupResult = $groupStmt->execute([$subSpecialityId, $groupType]);
                        } catch (PDOException $e) {
                            Logger::error("Σφάλμα προσθήκης στον πίνακα ομάδων: " . $e->getMessage(), "DriversModel");
                        }
                    }
                }
                
                return $result;
            }
        } catch (PDOException $e) {
            Logger::error("Γενικό σφάλμα προσθήκης υποειδικότητας: " . $e->getMessage(), "DriversModel");
            return false;
        }
    }

    /**
     * Διαγράφει τις υποειδικότητες της άδειας χειριστή μηχανημάτων
     * 
     * @param int $operatorLicenseId ID της άδειας χειριστή
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteDriverOperatorSubSpecialities($operatorLicenseId) {
        try {
            // Έλεγχος ύπαρξης πίνακα υποειδικοτήτων
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE 'driver_operator_sub_specialities'");
            if ($tableCheck->rowCount() == 0) {
                return true; // Θεωρούμε επιτυχία αφού δεν υπάρχει τίποτα για διαγραφή
            }
            
            // Έλεγχος ύπαρξης πίνακα ομάδων
            $hasGroupsTable = $this->checkTableExists('driver_operator_sub_speciality_groups');
            
            // Αν υπάρχει ο πίνακας ομάδων και δεν έχουμε foreign key constraint
            if ($hasGroupsTable) {
                try {
                    // Πρώτα βρίσκουμε τα IDs των υποειδικοτήτων
                    $findSql = "SELECT id FROM driver_operator_sub_specialities WHERE operator_license_id = ?";
                    $findStmt = $this->pdo->prepare($findSql);
                    $findStmt->execute([$operatorLicenseId]);
                    $subSpecialityIds = $findStmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (!empty($subSpecialityIds)) {
                        // Διαγραφή από τον πίνακα ομάδων
                        $idList = implode(',', array_fill(0, count($subSpecialityIds), '?'));
                        $groupDeleteSql = "DELETE FROM driver_operator_sub_speciality_groups WHERE sub_speciality_id IN ($idList)";
                        
                        $groupDeleteStmt = $this->pdo->prepare($groupDeleteSql);
                        $groupDeleteStmt->execute($subSpecialityIds);
                    }
                } catch (PDOException $e) {
                    Logger::error("Σφάλμα διαγραφής από τον πίνακα ομάδων: " . $e->getMessage(), "DriversModel");
                }
            }
            
            // Διαγραφή των υποειδικοτήτων
            $sql = "DELETE FROM driver_operator_sub_specialities WHERE operator_license_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$operatorLicenseId]);
            
            return $result;
        } catch (PDOException $e) {
            Logger::error("Σφάλμα διαγραφής υποειδικοτήτων: " . $e->getMessage(), "DriversModel");
            return false;
        }
    }

    /**
     * Ενημερώνει την άδεια χειριστή μηχανημάτων του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $operatorData Δεδομένα άδειας χειριστή
     * @return int|false ID της άδειας ή false σε αποτυχία
     */
    public function updateDriverOperatorLicense($driverId, $operatorData) {
        try {
            // Έλεγχος αν υπάρχει ήδη εγγραφή
            $existingLicense = $this->getDriverOperatorLicense($driverId);
            
            if ($existingLicense) {
                // Ενημέρωση υπάρχουσας εγγραφής
                $sql = "UPDATE driver_operator_licenses 
                        SET speciality = ?, license_number = ?, expiry_date = ? 
                        WHERE driver_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $success = $stmt->execute([
                    $operatorData['speciality'],
                    $operatorData['license_number'],
                    $operatorData['expiry_date'] ?: null,
                    $driverId
                ]);
                
                // Ενημέρωση του flag και της ημερομηνίας λήξης στον πίνακα drivers
                if ($success) {
                    $updateDriver = $this->pdo->prepare("UPDATE drivers SET 
                        operator_license = 1, 
                        operator_license_expiry = ? 
                        WHERE id = ?");
                    $updateDriver->execute([$operatorData['expiry_date'] ?: null, $driverId]);
                    
                    return $existingLicense['id'];
                } else {
                    return false;
                }
            } else {
                // Δημιουργία νέας εγγραφής
                $sql = "INSERT INTO driver_operator_licenses 
                        (driver_id, speciality, license_number, expiry_date) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $success = $stmt->execute([
                    $driverId,
                    $operatorData['speciality'],
                    $operatorData['license_number'],
                    $operatorData['expiry_date'] ?: null
                ]);
                
                if ($success) {
                    $licenseId = $this->pdo->lastInsertId();
                    
                    // Ενημέρωση του flag και της ημερομηνίας λήξης στον πίνακα drivers
                    $updateDriver = $this->pdo->prepare("UPDATE drivers SET 
                        operator_license = 1, 
                        operator_license_expiry = ? 
                        WHERE id = ?");
                    $updateDriver->execute([$operatorData['expiry_date'] ?: null, $driverId]);
                    
                    return $licenseId;
                } else {
                    return false;
                }
            }
        } catch (PDOException $e) {
            Logger::error('Error in updateDriverOperatorLicense: ' . $e->getMessage(), "DriversModel");
            return false;
        }
    }

    /**
     * Λαμβάνει την άδεια χειριστή μηχανημάτων του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array|false Στοιχεία άδειας χειριστή ή false
     */
    public function getDriverOperatorLicense($driverId) {
        try {
            $sql = "SELECT * FROM driver_operator_licenses WHERE driver_id = ? LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$driverId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Σφάλμα λήψης άδειας χειριστή: " . $e->getMessage(), "DriversModel");
            return false;
        }
    }
    
    /**
     * Διαγράφει την άδεια χειριστή μηχανημάτων του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteDriverOperatorLicense($driverId) {
        try {
            // Πρώτα βρίσκουμε την άδεια χειριστή για να πάρουμε το ID της
            $license = $this->getDriverOperatorLicense($driverId);
            
            if ($license) {
                // Διαγραφή των υποειδικοτήτων
                $this->deleteDriverOperatorSubSpecialities($license['id']);
                
                // Διαγραφή της άδειας χειριστή
                $sql = "DELETE FROM driver_operator_licenses WHERE driver_id = ?";
                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute([$driverId]);
                
                if ($result) {
                    // Ενημέρωση του flag στον πίνακα drivers
                    $updateFlag = $this->pdo->prepare("UPDATE drivers SET 
                        operator_license = 0, 
                        operator_license_expiry = NULL 
                        WHERE id = ?");
                    $updateFlag->execute([$driverId]);
                }
                
                return $result;
            }
            
            return true; // Δεν υπήρχε άδεια για διαγραφή
        } catch (PDOException $e) {
            Logger::error("Σφάλμα διαγραφής άδειας χειριστή: " . $e->getMessage(), "DriversModel");
            return false;
        }
    }
    
    // -------------------- ΚΑΡΤΑ ΤΑΧΟΓΡΑΦΟΥ --------------------
    
    /**
     * Λαμβάνει την κάρτα ταχογράφου του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array|false Στοιχεία κάρτας ταχογράφου ή false
     */
    public function getDriverTachographCard($driverId) {
        $sql = "SELECT * FROM driver_tachograph_cards WHERE driver_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$driverId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Ενημερώνει τα δεδομένα της κάρτας ταχογράφου ενός οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $tachographData Δεδομένα της κάρτας ταχογράφου
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverTachographCard($driverId, $tachographData) {
        try {
            // Έλεγχος αν υπάρχει ήδη εγγραφή
            $existingCard = $this->getDriverTachographCard($driverId);
            
            if ($existingCard) {
                // Ενημέρωση υπάρχουσας εγγραφής
                $sql = "UPDATE driver_tachograph_cards SET card_number = ?, expiry_date = ? WHERE driver_id = ?";
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([
                    $tachographData['card_number'],
                    $tachographData['expiry_date'] ?: null,
                    $driverId
                ]);
            } else {
                // Δημιουργία νέας εγγραφής
                $sql = "INSERT INTO driver_tachograph_cards (driver_id, card_number, expiry_date) VALUES (?, ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                return $stmt->execute([
                    $driverId,
                    $tachographData['card_number'],
                    $tachographData['expiry_date'] ?: null
                ]);
            }
        } catch (PDOException $e) {
            Logger::error('Error in updateDriverTachographCard: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Διαγράφει την κάρτα ταχογράφου του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteDriverTachographCard($driverId) {
        try {
            $sql = "DELETE FROM driver_tachograph_cards WHERE driver_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$driverId]);
        } catch (PDOException $e) {
            Logger::error('Error in deleteDriverTachographCard: ' . $e->getMessage());
            return false;
        }
    }
    
    // -------------------- ΕΙΔΙΚΈΣ ΆΔΕΙΕΣ --------------------
    
    /**
     * Ενημερώνει την εικόνα ενός εγγράφου του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param string $documentType Τύπος εγγράφου
     * @param string $imagePath Διαδρομή εικόνας
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverDocumentImage($driverId, $documentType, $imagePath) {
        try {
            // Βεβαιωνόμαστε ότι ο τύπος εγγράφου είναι ασφαλής για χρήση σε SQL
            $validDocTypes = [
                'license_front_image', 'license_back_image', 
                'adr_front_image', 'adr_back_image', 
                'operator_front_image', 'operator_back_image', 
                'tachograph_front_image', 'tachograph_back_image'
            ];
            
            if (!in_array($documentType, $validDocTypes)) {
                Logger::error('Invalid document type for image update: ' . $documentType);
                return false;
            }
            
            $sql = "UPDATE drivers SET $documentType = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$imagePath, $driverId]);
        } catch (PDOException $e) {
            Logger::error('Error in updateDriverDocumentImage: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Λαμβάνει τις ειδικές άδειες του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return array Λίστα ειδικών αδειών
     */
    public function getDriverSpecialLicenses($driverId) {
        try {
            $sql = "SELECT * FROM driver_special_licenses WHERE driver_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$driverId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('Error in getDriverSpecialLicenses: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Λαμβάνει μια συγκεκριμένη ειδική άδεια του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param string $licenseType Τύπος ειδικής άδειας
     * @return array|false Στοιχεία ειδικής άδειας ή false
     */
    public function getDriverSpecialLicenseByType($driverId, $licenseType) {
        try {
            $sql = "SELECT * FROM driver_special_licenses WHERE driver_id = ? AND license_type = ? LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$driverId, $licenseType]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error('Error in getDriverSpecialLicenseByType: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ενημερώνει τις ειδικές άδειες του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param array $specialLicenses Λίστα με ειδικές άδειες
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateDriverSpecialLicenses($driverId, $specialLicenses) {
        try {
            // Διαγραφή όλων των προηγούμενων εγγραφών
            $this->deleteDriverSpecialLicenses($driverId);
            
            // Αν δεν υπάρχουν νέες άδειες, επιστρέφουμε true
            if (empty($specialLicenses)) {
                return true;
            }
            
            // Εισαγωγή των νέων αδειών
            $sql = "INSERT INTO driver_special_licenses (driver_id, license_type, license_number, expiry_date, details) 
                    VALUES (?, ?, ?, ?, ?)";
            
            foreach ($specialLicenses as $license) {
                if (empty($license['license_type'])) {
                    continue; // Παραλείπουμε εγγραφές χωρίς τύπο άδειας
                }
                
                $stmt = $this->pdo->prepare($sql);
                $result = $stmt->execute([
                    $driverId,
                    $license['license_type'],
                    $license['license_number'] ?: null,
                    $license['expiry_date'] ?: null,
                    $license['details'] ?: null
                ]);
                
                if (!$result) {
                    Logger::error('Failed to insert special license: ' . print_r($license, true));
                    return false;
                }
            }
            
            return true;
        } catch (PDOException $e) {
            Logger::error('Error in updateDriverSpecialLicenses: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Διαγράφει όλες τις ειδικές άδειες του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteDriverSpecialLicenses($driverId) {
        try {
            $sql = "DELETE FROM driver_special_licenses WHERE driver_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$driverId]);
        } catch (PDOException $e) {
            Logger::error('Error in deleteDriverSpecialLicenses: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Διαγράφει μια συγκεκριμένη ειδική άδεια του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @param string $licenseType Τύπος ειδικής άδειας
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteDriverSpecialLicenseByType($driverId, $licenseType) {
        try {
            $sql = "DELETE FROM driver_special_licenses WHERE driver_id = ? AND license_type = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$driverId, $licenseType]);
        } catch (PDOException $e) {
            Logger::error('Error in deleteDriverSpecialLicenseByType: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Προσθέτει μια ειδική άδεια για τον οδηγό
     * 
     * @param int $driverId ID του οδηγού
     * @param string $licenseType Τύπος ειδικής άδειας
     * @param string $licenseNumber Αριθμός άδειας
     * @param string $expiryDate Ημερομηνία λήξης
     * @param string $details Λεπτομέρειες άδειας
     * @return bool Επιτυχία/αποτυχία
     */
    public function addDriverSpecialLicense($driverId, $licenseType, $licenseNumber, $expiryDate, $details = null) {
        try {
            $sql = "INSERT INTO driver_special_licenses (driver_id, license_type, license_number, expiry_date, details) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$driverId, $licenseType, $licenseNumber, $expiryDate, $details]);
        } catch (PDOException $e) {
            Logger::error('Error in addDriverSpecialLicense: ' . $e->getMessage());
            return false;
        }
    }
    
    // -------------------- ΕΙΔΟΠΟΙΉΣΕΙΣ ΛΗΞΗΣ ΑΔΕΙΩΝ --------------------
    
    /**
     * Λαμβάνει τους οδηγούς με άδειες που λήγουν σύντομα
     * 
     * @return array Λίστα οδηγών με άδειες που λήγουν
     */
    public function getDriversWithExpiringLicenses() {
        try {
            $expiryPeriods = [
                'driving_license' => [
                    'period' => '2 months',
                    'table' => 'driver_licenses',
                    'join' => 'dl.driver_id = d.id',
                    'expiry_field' => 'dl.expiry_date',
                    'type_field' => 'dl.license_type'
                ],
                'adr_certificate' => [
                    'period' => '1 year',
                    'table' => 'driver_adr_certificates',
                    'join' => 'dac.driver_id = d.id',
                    'expiry_field' => 'dac.expiry_date',
                    'type_field' => 'dac.adr_type'
                ],
                'operator_license' => [
                    'period' => '11 years',
                    'table' => 'driver_operator_licenses',
                    'join' => 'dol.driver_id = d.id',
                    'expiry_field' => 'dol.expiry_date',
                    'type_field' => 'dol.speciality'
                ]
            ];
            
            $results = [];
            
            foreach ($expiryPeriods as $licenseType => $config) {
                $targetDate = date('Y-m-d', strtotime('+' . $config['period']));
                
                $sql = "
                    SELECT d.id, d.first_name, d.last_name, d.email, 
                           '$licenseType' as type, 
                           {$config['expiry_field']} as expiry_date,
                           {$config['type_field']} as license_type
                    FROM drivers d 
                    JOIN {$config['table']} ON {$config['join']} 
                    WHERE {$config['expiry_field']} <= ? 
                      AND {$config['expiry_field']} >= CURRENT_DATE()
                ";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$targetDate]);
                $results[$licenseType] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            return $results;
        } catch (PDOException $e) {
            Logger::error('Error in getDriversWithExpiringLicenses: ' . $e->getMessage());
            return [
                'driving_licenses' => [],
                'adr_certificates' => [],
                'operator_licenses' => []
            ];
        }
    }
    
    // -------------------- ΒΟΗΘΗΤΙΚΈΣ ΣΥΝΑΡΤΉΣΕΙΣ --------------------
    
    /**
     * Ελέγχει αν η συγκεκριμένη στήλη υπάρχει στον πίνακα
     * 
     * @param string $table Όνομα πίνακα
     * @param string $column Όνομα στήλης
     * @return bool Αν υπάρχει η στήλη
     */
    private function checkColumnExists($table, $column) {
        try {
            $columnsResult = $this->pdo->query("SHOW COLUMNS FROM {$table} LIKE '{$column}'");
            return $columnsResult->rowCount() > 0;
        } catch (PDOException $e) {
            Logger::error("Σφάλμα ελέγχου στήλης {$column} στον πίνακα {$table}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ελέγχει αν ο συγκεκριμένος πίνακας υπάρχει
     * 
     * @param string $table Όνομα πίνακα
     * @return bool Αν υπάρχει ο πίνακας
     */
    private function checkTableExists($table) {
        try {
            $tableCheck = $this->pdo->query("SHOW TABLES LIKE '{$table}'");
            return $tableCheck->rowCount() > 0;
        } catch (PDOException $e) {
            Logger::error("Σφάλμα ελέγχου πίνακα {$table}: " . $e->getMessage());
            return false;
        }
    }
}
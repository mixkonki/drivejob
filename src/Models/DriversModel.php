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
 * @param int $driverId ID του οδηγού
 * @param string $resumeFile Διαδρομή του αρχείου βιογραφικού
 * @return bool Επιτυχία/αποτυχία
 */
public function updateResumeFile($driverId, $resumeFile) {
    try {
        $sql = "UPDATE drivers SET resume_file = :resume_file WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $driverId,
            'resume_file' => $resumeFile
        ]);
    } catch (\PDOException $e) {
        error_log('Error updating resume file: ' . $e->getMessage());
        return false;
    }
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
            $sql = "SELECT id, sub_speciality, group_type FROM driver_operator_sub_specialities WHERE operator_license_id = ? ORDER BY sub_speciality";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$operatorLicenseId]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            return $result;
        } else if ($hasGroupsTable) {
            // Συνδυασμός με τον πίνακα ομάδων
            $sql = "SELECT dos.id, dos.sub_speciality, COALESCE(dosg.group_type, 'A') as group_type 
                    FROM driver_operator_sub_specialities dos
                    LEFT JOIN driver_operator_sub_speciality_groups dosg ON dos.id = dosg.sub_speciality_id
                    WHERE dos.operator_license_id = ?
                    ORDER BY dos.sub_speciality";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$operatorLicenseId]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            return $result;
        } else {
            // Χωρίς πληροφορίες ομάδας
            $sql = "SELECT id, sub_speciality, 'A' as group_type FROM driver_operator_sub_specialities WHERE operator_license_id = ? ORDER BY sub_speciality";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$operatorLicenseId]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
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
 * Ανακτά τα στοιχεία της κάρτας ταχογράφου ενός οδηγού
 * 
 * @param int $driverId ID του οδηγού
 * @return array|null Τα στοιχεία της κάρτας ταχογράφου ή null αν δεν υπάρχει
 */
public function getDriverTachographCard($driverId) {
    try {
        $sql = "SELECT * FROM driver_tachograph_cards WHERE driver_id = :driver_id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        error_log('Error getting driver tachograph card: ' . $e->getMessage());
        return null;
    }
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
 * Ανακτά τις ειδικές άδειες ενός οδηγού
 * 
 * @param int $driverId ID του οδηγού
 * @return array Οι ειδικές άδειες του οδηγού
 */
public function getDriverSpecialLicenses($driverId) {
    try {
        $sql = "SELECT * FROM driver_special_licenses WHERE driver_id = :driver_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        error_log('Error getting driver special licenses: ' . $e->getMessage());
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
/**
 * Ανάκτηση των δεξιοτήτων ενός οδηγού
 * 
 * @param int $driverId Το ID του οδηγού
 * @return array Οι δεξιότητες του οδηγού
 */
public function getDriverSkills($driverId) {
    try {
        $sql = "SELECT * FROM driver_skills WHERE driver_id = :driver_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: [];
    } catch (PDOException $e) {
        error_log('Error getting driver skills: ' . $e->getMessage());
        return [];
    }
}


/**
 * Ενημερώνει τις δεξιότητες ενός οδηγού
 * 
 * @param int $driverId ID του οδηγού
 * @param array $skills Δεδομένα δεξιοτήτων
 * @return bool Επιτυχία/αποτυχία
 */
public function updateDriverSkills($driverId, $skills) {
    try {
        // Έλεγχος αν υπάρχει ήδη εγγραφή
        $existingSkills = $this->getDriverSkills($driverId);
        
        if (!empty($existingSkills)) {
            // Δημιουργία του SQL για την ενημέρωση
            $setParts = [];
            $params = [];
            
            foreach ($skills as $field => $value) {
                $setParts[] = "`$field` = ?";
                $params[] = $value;
            }
            
            // Προσθήκη του driver_id στο τέλος
            $params[] = $driverId;
            
            $sql = "UPDATE driver_skills SET " . implode(', ', $setParts) . " WHERE driver_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } else {
            // Δημιουργία νέας εγγραφής
            $fields = array_keys($skills);
            $fields[] = 'driver_id';
            
            $placeholders = array_fill(0, count($fields), '?');
            
            $values = array_values($skills);
            $values[] = $driverId;
            
            $sql = "INSERT INTO driver_skills (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($values);
        }
    } catch (PDOException $e) {
        Logger::error('Error in updateDriverSkills: ' . $e->getMessage());
        return false;
    }
}

/**
 * Επιστρέφει τα δεδομένα αυτοαξιολόγησης ενός οδηγού
 * 
 * @param int $driverId ID του οδηγού
 * @return array Στοιχεία αυτοαξιολόγησης οδηγού ή κενός πίνακας αν δεν βρέθηκαν
 */
public function getDriverAssessment($driverId) {
    try {
        $sql = "SELECT * FROM driver_assessment WHERE driver_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$driverId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Έλεγχος για δεδομένα τηλεματικής
            $telemetryData = $this->getDriverTelemetryData($driverId);
            if ($telemetryData) {
                $result['telemetry_data'] = $telemetryData;
            }
            
            return $result;
        }
        
        return [];
    } catch (PDOException $e) {
        Logger::error('Error in getDriverAssessment: ' . $e->getMessage());
        return [];
    }
}



/**
 * Ενημερώνει τα δεδομένα αυτοαξιολόγησης ενός οδηγού
 * 
 * @param int $driverId ID του οδηγού
 * @param array $assessmentData Δεδομένα αυτοαξιολόγησης
 * @return bool Επιτυχία/αποτυχία
 */
public function updateDriverAssessment($driverId, $assessmentData) {
    try {
        // Υπολογισμός των επιμέρους βαθμολογιών
        $scores = $this->calculateAssessmentScores($assessmentData);
        
        // Συγχώνευση των δεδομένων αυτοαξιολόγησης με τις βαθμολογίες
        $data = array_merge($assessmentData, $scores);
        
        // Έλεγχος αν υπάρχει ήδη εγγραφή
        $existingAssessment = $this->getDriverAssessment($driverId);
        
        if (!empty($existingAssessment)) {
            // Δημιουργία του SQL για την ενημέρωση
            $setParts = [];
            $params = [];
            
            foreach ($data as $field => $value) {
                $setParts[] = "`$field` = ?";
                $params[] = $value;
            }
            
            // Προσθήκη του driver_id στο τέλος
            $params[] = $driverId;
            
            $sql = "UPDATE driver_assessment SET " . implode(', ', $setParts) . ", updated_at = NOW() WHERE driver_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } else {
            // Δημιουργία νέας εγγραφής
            $fields = array_keys($data);
            $fields[] = 'driver_id';
            $fields[] = 'created_at';
            $fields[] = 'updated_at';
            
            $placeholders = array_fill(0, count($fields), '?');
            
            $values = array_values($data);
            $values[] = $driverId;
            $values[] = date('Y-m-d H:i:s');
            $values[] = date('Y-m-d H:i:s');
            
            $sql = "INSERT INTO driver_assessment (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($values);
        }
    } catch (PDOException $e) {
        Logger::error('Error in updateDriverAssessment: ' . $e->getMessage());
        return false;
    }
}

/**
 * Υπολογίζει τις βαθμολογίες αυτοαξιολόγησης
 * 
 * @param array $assessmentData Δεδομένα αυτοαξιολόγησης
 * @return array Υπολογισμένες βαθμολογίες
 */
private function calculateAssessmentScores($assessmentData) {
    // Βάρη για κάθε κατηγορία και τις ερωτήσεις της
    $weights = [
        // Οδηγικές Ικανότητες (40%)
        'driving_skills' => [
            'driving_experience' => 0.08,
            'annual_kilometers' => 0.08,
            'driving_conditions' => 0.08,
            'eco_driving_rating' => 0.08,
            'night_driving' => 0.08
        ],
        
        // Ασφάλεια & Συμμόρφωση (30%)
        'safety_compliance' => [
            'accidents' => 0.06,
            'traffic_violations' => 0.06,
            'tachograph_compliance' => 0.06,
            'safety_check' => 0.06,
            'load_securing' => 0.06
        ],
        
        // Επαγγελματισμός (15%)
        'professionalism' => [
            'punctuality' => 0.0375,
            'customer_interaction' => 0.0375,
            'appearance' => 0.0375,
            'documentation' => 0.0375
        ],
        
        // Τεχνικές Γνώσεις (15%)
        'technical_knowledge' => [
            'vehicle_maintenance' => 0.0375,
            'troubleshooting' => 0.0375,
            'navigation_skills' => 0.0375,
            'technical_knowledge' => 0.0375
        ]
    ];
    
    $scores = [];
    
    // Υπολογισμός βαθμολογίας για κάθε κατηγορία
    foreach ($weights as $category => $categoryWeights) {
        $categoryScore = 0;
        $totalWeight = 0;
        
        foreach ($categoryWeights as $field => $weight) {
            if (isset($assessmentData[$field])) {
                $value = intval($assessmentData[$field]);
                $categoryScore += $value * $weight * 20; // Μετατροπή σε κλίμακα 100
                $totalWeight += $weight;
            }
        }
        
        // Υπολογισμός τελικής βαθμολογίας κατηγορίας
        if ($totalWeight > 0) {
            $scores[$category] = round($categoryScore / $totalWeight);
        } else {
            $scores[$category] = 0;
        }
    }
    
    // Υπολογισμός συνολικής βαθμολογίας
    $totalScore = 0;
    $validScores = 0;
    
    foreach ($scores as $categoryScore) {
        if ($categoryScore > 0) {
            $totalScore += $categoryScore;
            $validScores++;
        }
    }
    
    if ($validScores > 0) {
        $scores['total_score'] = round($totalScore / $validScores);
    } else {
        $scores['total_score'] = 0;
    }
    
    return $scores;
}

/**
 * Επιστρέφει τις πιστοποιήσεις και τα σεμινάρια ενός οδηγού
 * 
 * @param int $driverId ID του οδηγού
 * @return array Λίστα πιστοποιήσεων και σεμιναρίων
 */
public function getDriverCertifications($driverId) {
    try {
        $sql = "SELECT * FROM driver_certifications WHERE driver_id = ? ORDER BY date DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$driverId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        Logger::error('Error in getDriverCertifications: ' . $e->getMessage());
        return [];
    }
}

/**
 * Διαγράφει όλες τις πιστοποιήσεις και τα σεμινάρια ενός οδηγού
 * 
 * @param int $driverId ID του οδηγού
 * @return bool Επιτυχία/αποτυχία
 */
public function deleteDriverCertifications($driverId) {
    try {
        $sql = "DELETE FROM driver_certifications WHERE driver_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$driverId]);
    } catch (PDOException $e) {
        Logger::error('Error in deleteDriverCertifications: ' . $e->getMessage());
        return false;
    }
}

/**
 * Προσθέτει πιστοποιήσεις και σεμινάρια για έναν οδηγό
 * 
 * @param int $driverId ID του οδηγού
 * @param array $certifications Λίστα πιστοποιήσεων
 * @return bool Επιτυχία/αποτυχία
 */
public function addDriverCertifications($driverId, $certifications) {
    try {
        // Διαγραφή προηγούμενων πιστοποιήσεων
        $this->deleteDriverCertifications($driverId);
        
        // Αν δεν υπάρχουν νέες πιστοποιήσεις, επιστρέφουμε επιτυχία
        if (empty($certifications)) {
            return true;
        }
        
        // Προσθήκη των νέων πιστοποιήσεων
        $sql = "INSERT INTO driver_certifications (
                driver_id, title, provider, date, expiry, description
            ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($certifications as $cert) {
            if (empty($cert['title'])) {
                continue; // Παραλείπουμε πιστοποιήσεις χωρίς τίτλο
            }
            
            $result = $stmt->execute([
                $driverId,
                $cert['title'],
                $cert['provider'] ?? null,
                $cert['date'] ?? null,
                $cert['expiry'] ?? null,
                $cert['description'] ?? null
            ]);
            
            if (!$result) {
                return false;
            }
        }
        
        return true;
    } catch (PDOException $e) {
        Logger::error('Error in addDriverCertifications: ' . $e->getMessage());
        return false;
    }
}

/**
 * Ενημερώνει μια πιστοποίηση οδηγού
 * 
 * @param int $certificationId ID της πιστοποίησης
 * @param array $data Δεδομένα της πιστοποίησης
 * @return bool Επιτυχία/αποτυχία
 */
public function updateDriverCertification($certificationId, $driverId, $data) {
    try {
        $sql = "UPDATE driver_certifications 
                SET title = ?, provider = ?, date = ?, expiry = ?, description = ? 
                WHERE id = ? AND driver_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['title'],
            $data['provider'] ?? null,
            $data['date'] ?? null,
            $data['expiry'] ?? null,
            $data['description'] ?? null,
            $certificationId,
            $driverId
        ]);
    } catch (PDOException $e) {
        Logger::error('Error in updateDriverCertification: ' . $e->getMessage());
        return false;
    }
}

/**
 * Διαγράφει μια πιστοποίηση οδηγού
 * 
 * @param int $certificationId ID της πιστοποίησης
 * @param int $driverId ID του οδηγού για επαλήθευση
 * @return bool Επιτυχία/αποτυχία
 */
public function deleteDriverCertification($certificationId, $driverId) {
    try {
        $sql = "DELETE FROM driver_certifications WHERE id = ? AND driver_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$certificationId, $driverId]);
    } catch (PDOException $e) {
        Logger::error('Error in deleteDriverCertification: ' . $e->getMessage());
        return false;
    }
}
/**
 * Λαμβάνει την εμπειρία οχημάτων ενός οδηγού
 * 
 * @param int $driverId ID του οδηγού
 * @return array Λίστα με την εμπειρία σε οχήματα
 */
public function getDriverVehicleExperience($driverId) {
    try {
        $sql = "SELECT * FROM driver_vehicle_experience WHERE driver_id = ? ORDER BY years DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$driverId]);
        
        $experience = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Προσθήκη των ονομάτων των τύπων οχημάτων για εμφάνιση
        foreach ($experience as &$exp) {
            $exp['vehicle_type_name'] = $this->getVehicleTypeName($exp['vehicle_category'], $exp['vehicle_type']);
        }
        
        return $experience;
    } catch (PDOException $e) {
        Logger::error('Error in getDriverVehicleExperience: ' . $e->getMessage());
        return [];
    }
}

/**
 * Επιστρέφει το όνομα του τύπου οχήματος με βάση την κατηγορία και τον κωδικό τύπου
 * 
 * @param string $category Κατηγορία οχήματος
 * @param string $type Κωδικός τύπου οχήματος
 * @return string Όνομα τύπου οχήματος
 */
private function getVehicleTypeName($category, $type) {
    $vehicleTypes = [
        'lcv' => [
            'panel_van' => 'Κλειστό Van',
            'pickup_truck' => 'Van με καρότσα (Pick-up)',
            'small_refrigerated' => 'Μικρό φορτηγό ψυγείο/κατάψυξης'
        ],
        'rigid_truck' => [
            'distribution_truck' => 'Φορτηγό Διανομών',
            'refrigerated_truck' => 'Φορτηγό Ψυγείο/Κατάψυξης',
            'platform_truck' => 'Φορτηγό Πλατφόρμα',
            'dump_truck' => 'Ανατρεπόμενο Φορτηγό',
            'tanker_truck' => 'Βυτιοφόρο (άκαμπτο)',
            'car_carrier' => 'Όχημα Μεταφοράς Οχημάτων',
            'silo_truck' => 'Φορτηγό με Σιλό',
            'crane_truck' => 'Φορτηγό με Γερανό',
            'livestock_truck' => 'Όχημα Μεταφοράς Ζώων'
        ],
        'articulated' => [
            'curtainsider' => 'Επικαθήμενο με Μουσαμά',
            'reefer' => 'Επικαθήμενο Ψυγείο/Κατάψυξη',
            'box_trailer' => 'Επικαθήμενο Κλειστού Τύπου',
            'flatbed' => 'Επικαθήμενο Πλατφόρμα',
            'tipper' => 'Επικαθήμενο Ανατρεπόμενο',
            'tanker' => 'Επικαθήμενο Βυτίο',
            'silo' => 'Επικαθήμενο Σιλό',
            'container' => 'Επικαθήμενο Μεταφοράς Εμπορευματοκιβωτίων',
            'car_transporter' => 'Επικαθήμενο Μεταφοράς Οχημάτων',
            'livestock' => 'Επικαθήμενο Μεταφοράς Ζώων',
            'low_loader' => 'Επικαθήμενο Χαμηλής Κλίνης',
            'drawbar' => 'Φορτηγό με Ρυμουλκούμενο (συρμός)'
        ],
        // Προσθέστε και τις υπόλοιπες κατηγορίες και τύπους
    ];
    
    // Επιστροφή του ονόματος αν υπάρχει, αλλιώς επιστρέφουμε τον κωδικό
    return isset($vehicleTypes[$category][$type]) ? $vehicleTypes[$category][$type] : $type;
}

/**
 * Διαγράφει όλη την εμπειρία οχημάτων ενός οδηγού
 * 
 * @param int $driverId ID του οδηγού
 * @return bool Επιτυχία/αποτυχία
 */
public function deleteDriverVehicleExperience($driverId) {
    try {
        $sql = "DELETE FROM driver_vehicle_experience WHERE driver_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$driverId]);
    } catch (PDOException $e) {
        Logger::error('Error in deleteDriverVehicleExperience: ' . $e->getMessage());
        return false;
    }
    
}


/**
 * Προσθέτει εμπειρία οχημάτων για έναν οδηγό
 * 
 * @param int $driverId ID του οδηγού
 * @param array $vehicleExperience Λίστα με την εμπειρία σε οχήματα
 * @return bool Επιτυχία/αποτυχία
 */
public function updateDriverVehicleExperience($driverId, $vehicleExperience) {
    try {
        // Διαγραφή προηγούμενης εμπειρίας
        $this->deleteDriverVehicleExperience($driverId);
        
        // Αν δεν υπάρχει νέα εμπειρία, επιστρέφουμε επιτυχία
        if (empty($vehicleExperience)) {
            return true;
        }
        
        // Προσθήκη της νέας εμπειρίας
        $sql = "INSERT INTO driver_vehicle_experience (
                driver_id, vehicle_category, vehicle_type, years, 
                start_date, end_date, description
            ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($vehicleExperience as $exp) {
            // Παραλείπουμε εγγραφές χωρίς επιλεγμένη κατηγορία οχήματος
            if (empty($exp['vehicle_category'])) {
                continue;
            }
            
            $result = $stmt->execute([
                $driverId,
                $exp['vehicle_category'],
                $exp['vehicle_type'] ?? '',
                intval($exp['years'] ?? 0),
                $exp['start_date'] ?? null,
                $exp['end_date'] ?? null,
                $exp['description'] ?? ''
            ]);
            
            if (!$result) {
                Logger::error('Failed to insert vehicle experience: ' . print_r($exp, true));
            }
        }
        
        return true;
    } catch (PDOException $e) {
        Logger::error('Error in updateDriverVehicleExperience: ' . $e->getMessage());
        return false;
    }
}
/**
 * Αποθηκεύει ένα νέο συμβάν για τον οδηγό
 * 
 * @param int $driverId ID του οδηγού
 * @param array $incidentData Δεδομένα συμβάντος
 * @return int|bool ID του συμβάντος ή false σε αποτυχία
 */
public function saveDriverIncident($driverId, $incidentData) {
    try {
        $sql = "INSERT INTO driver_incidents (
                    driver_id, incident_type, incident_date, 
                    description, severity
                ) VALUES (?, ?, ?, ?, ?)";
                
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([
            $driverId,
            $incidentData['incident_type'],
            $incidentData['incident_date'],
            $incidentData['description'],
            $incidentData['severity']
        ]);
        
        if ($result) {
            return $this->pdo->lastInsertId();
        }
        
        return false;
    } catch (PDOException $e) {
        Logger::error('Error in saveDriverIncident: ' . $e->getMessage());
        return false;
    }
}

/**
 * Λαμβάνει τα συμβάντα ενός οδηγού
 * 
 * @param int $driverId ID του οδηγού
 * @return array Λίστα συμβάντων
 */
public function getDriverIncidents($driverId) {
    try {
        $sql = "SELECT * FROM driver_incidents 
                WHERE driver_id = ? 
                ORDER BY incident_date DESC";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$driverId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        Logger::error('Error in getDriverIncidents: ' . $e->getMessage());
        return [];
    }
}

/**
 * Υπολογίζει και ενημερώνει τη συνολική βαθμολογία του οδηγού
 * 
 * @param int $driverId ID του οδηγού
 * @return bool Επιτυχία/αποτυχία
 */
public function updateDriverRating($driverId) {
    try {
        // Δημιουργία του αντικειμένου υπολογισμού βαθμολογίας
        $ratingService = new \Drivejob\Services\DriverRatingService($this->pdo);
        
        // Λήψη των βαθμολογιών
        $ratings = $ratingService->calculateTotalRating($driverId);
        
        // Έλεγχος αν υπάρχει ήδη εγγραφή
        $existingRating = $this->getDriverRating($driverId);
        
        if ($existingRating) {
            // Ενημέρωση υπάρχουσας βαθμολογίας
            $sql = "UPDATE driver_ratings 
                    SET skills_score = ?, safety_score = ?, 
                        professionalism_score = ?, technical_score = ?, 
                        total_score = ? 
                    WHERE driver_id = ?";
                    
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $ratings['skills_score'],
                $ratings['safety_score'],
                $ratings['professionalism_score'],
                $ratings['technical_score'],
                $ratings['total_score'],
                $driverId
            ]);
        } else {
            // Δημιουργία νέας βαθμολογίας
            $sql = "INSERT INTO driver_ratings (
                        driver_id, skills_score, safety_score, 
                        professionalism_score, technical_score, total_score
                    ) VALUES (?, ?, ?, ?, ?, ?)";
                    
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $driverId,
                $ratings['skills_score'],
                $ratings['safety_score'],
                $ratings['professionalism_score'],
                $ratings['technical_score'],
                $ratings['total_score']
            ]);
        }
    } catch (\Exception $e) {
        Logger::error('Error in updateDriverRating: ' . $e->getMessage());
        return false;
    }
}

/**
 * Ανάκτηση της μέσης βαθμολογίας ενός οδηγού
 * 
 * @param int $driverId Το ID του οδηγού
 * @return float Η μέση βαθμολογία του οδηγού
 */
public function getDriverRating($driverId) {
    try {
        // Πρώτα προσπαθούμε να πάρουμε την τιμή από τον πίνακα driver_ratings
        $sql = "SELECT total_score FROM driver_ratings WHERE driver_id = :driver_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && isset($result['total_score'])) {
            // Μετατροπή του score 0-100 σε βαθμολογία 0-5
            return min(5, round($result['total_score'] / 20, 1));
        }
        
        // Αν δεν υπάρχει, ελέγχουμε τον πίνακα driver_reviews
        $sql = "SELECT AVG(rating) as avg_rating FROM driver_reviews WHERE driver_id = :driver_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && isset($result['avg_rating'])) {
            return round($result['avg_rating'], 1);
        }
        
        // Αν δεν υπάρχει ούτε στις αξιολογήσεις, ελέγχουμε το πεδίο rating του πίνακα drivers
        $sql = "SELECT rating FROM drivers WHERE id = :driver_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && isset($result['rating'])) {
            return $result['rating'];
        }
        
        // Αλλιώς επιστρέφουμε 0
        return 0;
    } catch (PDOException $e) {
        error_log('Error getting driver rating: ' . $e->getMessage());
        return 0;
    }
}


/**
 * Λαμβάνει τα δεδομένα τηλεματικής του οδηγού
 * 
 * @param int $driverId ID του οδηγού
 * @return array|null Τελευταία δεδομένα τηλεματικής ή null
 */
public function getDriverTelemetryData($driverId) {
    try {
        $sql = "SELECT * FROM driver_telemetry 
                WHERE driver_id = ? 
                ORDER BY date_collected DESC 
                LIMIT 1";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$driverId]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        Logger::error('Error in getDriverTelemetryData: ' . $e->getMessage());
        return null;
    }
}

/**
 * Αποθηκεύει νέα δεδομένα τηλεματικής για τον οδηγό
 * 
 * @param int $driverId ID του οδηγού
 * @param array $telemetryData Δεδομένα τηλεματικής
 * @return bool Επιτυχία/αποτυχία
 */
public function saveDriverTelemetryData($driverId, $telemetryData) {
    try {
        $sql = "INSERT INTO driver_telemetry (
                    driver_id, avg_speed, max_speed, harsh_braking, 
                    harsh_acceleration, harsh_cornering, fuel_consumption, 
                    total_distance, score, date_collected
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $driverId,
            $telemetryData['avg_speed'],
            $telemetryData['max_speed'],
            $telemetryData['harsh_braking'],
            $telemetryData['harsh_acceleration'],
            $telemetryData['harsh_cornering'],
            $telemetryData['fuel_consumption'],
            $telemetryData['total_distance'],
            $telemetryData['score'],
            $telemetryData['date_collected']
        ]);
    } catch (PDOException $e) {
        Logger::error('Error in saveDriverTelemetryData: ' . $e->getMessage());
        return false;
    }
}
/**
 * Ανάκτηση των αξιολογήσεων ενός οδηγού
 * 
 * @param int $driverId Το ID του οδηγού
 * @return array Οι αξιολογήσεις του οδηγού
 */
public function getDriverReviews($driverId) {
    try {
        $sql = "SELECT r.*, c.company_name
                FROM driver_reviews r
                LEFT JOIN companies c ON r.company_id = c.id
                WHERE r.driver_id = :driver_id
                ORDER BY r.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting driver reviews: ' . $e->getMessage());
        return [];
    }
}
/**
 * Ανακτά τα πιστοποιητικά ADR ενός οδηγού
 * 
 * @param int $driverId ID του οδηγού
 * @return array Τα πιστοποιητικά ADR του οδηγού
 */
public function getDriverAdrCertificates($driverId) {
    try {
        $sql = "SELECT * FROM driver_adr_certificates WHERE driver_id = :driver_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        error_log('Error getting driver ADR certificates: ' . $e->getMessage());
        return [];
    }
}
/**
 * Ανακτά τις άδειες χειριστή μηχανημάτων έργου ενός οδηγού
 * 
 * @param int $driverId ID του οδηγού
 * @return array Οι άδειες χειριστή μηχανημάτων έργου του οδηγού
 */
public function getDriverOperatorLicenses($driverId) {
    try {
        $sql = "SELECT ol.*, GROUP_CONCAT(DISTINCT os.sub_speciality) AS sub_specialities
                FROM driver_operator_licenses ol
                LEFT JOIN driver_operator_sub_specialities os ON ol.id = os.operator_license_id
                WHERE ol.driver_id = :driver_id
                GROUP BY ol.id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        $operatorLicenses = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Ανάκτηση των υποειδικοτήτων για κάθε άδεια
        foreach ($operatorLicenses as &$license) {
            $license['sub_specialities'] = $this->getOperatorSubSpecialties($license['id']);
        }
        
        return $operatorLicenses;
    } catch (\PDOException $e) {
        error_log('Error getting driver operator licenses: ' . $e->getMessage());
        return [];
    }
}
/**
 * Ανακτά τις υποειδικότητες μιας άδειας χειριστή μηχανημάτων έργου
 * 
 * @param int $operatorLicenseId ID της άδειας χειριστή
 * @return array Οι υποειδικότητες της άδειας χειριστή
 */
private function getOperatorSubSpecialties($operatorLicenseId) {
    try {
        $sql = "SELECT * FROM driver_operator_sub_specialities 
                WHERE operator_license_id = :operator_license_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['operator_license_id' => $operatorLicenseId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        error_log('Error getting operator sub specialties: ' . $e->getMessage());
        return [];
    }
}

/**
 * Ανάκτηση των καρτών ταχογράφου ενός οδηγού
 * 
 * @param int $driverId Το ID του οδηγού
 * @return array Οι κάρτες ταχογράφου του οδηγού
 */
public function getDriverTachographCards($driverId) {
    try {
        $sql = "SELECT * FROM driver_tachograph_cards WHERE driver_id = :driver_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Error getting driver tachograph cards: ' . $e->getMessage());
        return [];
    }
}

/**
 * Ανάκτηση όλων των πληροφοριών ενός οδηγού συμπεριλαμβανομένων αδειών και πιστοποιήσεων
 * 
 * @param int $driverId Το ID του οδηγού
 * @return array|false Οι πληροφορίες του οδηγού ή false αν δεν βρέθηκε
 */
public function getDriverWithDetails($driverId) {
    try {
        // Ανάκτηση των βασικών πληροφοριών του οδηγού
        $driver = $this->getDriverById($driverId);
        
        if (!$driver) {
            return false;
        }
        
        // Προσθήκη των αδειών οδήγησης
        $driver['licenses'] = $this->getDriverLicenses($driverId);
        
        // Υπολογισμός των κατηγοριών αδειών
        $driver['license_categories'] = [];
        foreach ($driver['licenses'] as $license) {
            if (isset($license['category'])) {
                $driver['license_categories'][] = $license['category'];
            }
        }
        
        // Προσθήκη των πιστοποιητικών ADR
        $driver['adr_certificates'] = $this->getDriverAdrCertificates($driverId);
        
        // Προσθήκη των αδειών χειριστή
        $driver['operator_licenses'] = $this->getDriverOperatorLicenses($driverId);
        
        // Προσθήκη των καρτών ταχογράφου
        $driver['tachograph_cards'] = $this->getDriverTachographCards($driverId);
        
        // Προσθήκη των δεξιοτήτων
        $driver['skills'] = $this->getDriverSkills($driverId);
        
        // Προσθήκη της βαθμολογίας
        $driver['average_rating'] = $this->getDriverRating($driverId);
        
        // Επιστροφή του οδηγού με όλες τις λεπτομέρειες
        return $driver;
    } catch (PDOException $e) {
        error_log('Error getting driver with details: ' . $e->getMessage());
        return false;
    }
}
/**
 * Ανάκτηση όλων των αδειών και πιστοποιήσεων του οδηγού με λεπτομέρειες
 * 
 * @param int $driverId Το ID του οδηγού
 * @return array Συνδυασμένες πληροφορίες για τον οδηγό
 */
public function getDriverCertificationsDetails($driverId) {
    $result = [
        'success' => false,
        'driver' => null,
        'licenses' => [],
        'adr_certificates' => [],
        'operator_licenses' => [],
        'tachograph_cards' => [],
        'special_licenses' => []
    ];
    
    try {
        // Ανάκτηση βασικών πληροφοριών οδηγού
        $driver = $this->getDriverById($driverId);
        if (!$driver) {
            return $result;
        }
        
        $result['driver'] = $driver;
        $result['success'] = true;
        
        // 1. Άδειες οδήγησης
        $sql = "SELECT * FROM driver_licenses WHERE driver_id = :driver_id ORDER BY license_type";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        $result['licenses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 2. Πιστοποιητικά ADR
        $sql = "SELECT * FROM driver_adr_certificates WHERE driver_id = :driver_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        $result['adr_certificates'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 3. Άδειες χειριστή
        $sql = "SELECT ol.* FROM driver_operator_licenses ol WHERE ol.driver_id = :driver_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        $operatorLicenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Για κάθε άδεια χειριστή, ανάκτηση των υποκατηγοριών
        foreach ($operatorLicenses as &$license) {
            $sql = "SELECT oss.* FROM driver_operator_sub_specialities oss 
                    WHERE oss.operator_license_id = :license_id 
                    ORDER BY oss.group_type, oss.sub_speciality";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['license_id' => $license['id']]);
            $license['sub_specialities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        $result['operator_licenses'] = $operatorLicenses;
        
        // 4. Κάρτες ταχογράφου
        $sql = "SELECT * FROM driver_tachograph_cards WHERE driver_id = :driver_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        $result['tachograph_cards'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 5. Ειδικές άδειες
        $sql = "SELECT * FROM driver_special_licenses WHERE driver_id = :driver_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        $result['special_licenses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $result;
        
    } catch (PDOException $e) {
        error_log('Error getting driver certifications: ' . $e->getMessage());
        return $result;
    }
}

/**
 * Μορφοποίηση των αδειών οδήγησης σε αναγνώσιμη μορφή
 * 
 * @param array $licenses Οι άδειες οδήγησης
 * @return array Μορφοποιημένες άδειες
 */
public function formatDriverLicenses($licenses) {
    $categories = [];
    $licenseInfo = [];
    
    foreach ($licenses as $license) {
        if (!empty($license['license_type'])) {
            $categories[] = $license['license_type'];
            
            $licenseInfo[$license['license_type']] = [
                'has_pei' => $license['has_pei'] ?? 0,
                'expiry_date' => $license['expiry_date'] ?? null,
                'license_number' => $license['license_number'] ?? null,
                'pei_expiry_c' => $license['pei_expiry_c'] ?? null,
                'pei_expiry_d' => $license['pei_expiry_d'] ?? null
            ];
        }
    }
    
    return [
        'categories' => $categories,
        'details' => $licenseInfo
    ];
}

/**
 * Μορφοποίηση των πιστοποιητικών ADR σε αναγνώσιμη μορφή
 * 
 * @param array $adrCertificates Τα πιστοποιητικά ADR
 * @return array Μορφοποιημένα πιστοποιητικά
 */
public function formatAdrCertificates($adrCertificates) {
    $types = [];
    $detail = null;
    
    foreach ($adrCertificates as $cert) {
        $types[] = $cert['adr_type'];
        
        // Κρατάμε το πιο πρόσφατο πιστοποιητικό
        if ($detail === null || 
            (isset($cert['expiry_date']) && isset($detail['expiry_date']) && 
             strtotime($cert['expiry_date']) > strtotime($detail['expiry_date']))) {
            $detail = $cert;
        }
    }
    
    return [
        'has_adr' => !empty($types),
        'types' => $types,
        'types_text' => implode(', ', $types),
        'detail' => $detail
    ];
}

/**
 * Μορφοποίηση των αδειών χειριστή σε αναγνώσιμη μορφή
 * 
 * @param array $operatorLicenses Οι άδειες χειριστή
 * @return array Μορφοποιημένες άδειες
 */
public function formatOperatorLicenses($operatorLicenses) {
    $specialities = [];
    $subSpecialities = [];
    $groupedSubSpecialities = [];
    
    foreach ($operatorLicenses as $license) {
        if (!empty($license['speciality'])) {
            $specialities[] = $license['speciality'];
            
            if (!empty($license['sub_specialities'])) {
                foreach ($license['sub_specialities'] as $subSpec) {
                    $subSpecialities[] = $subSpec['sub_speciality'];
                    
                    $group = $subSpec['group_type'];
                    if (!isset($groupedSubSpecialities[$group])) {
                        $groupedSubSpecialities[$group] = [];
                    }
                    $groupedSubSpecialities[$group][] = $subSpec['sub_speciality'];
                }
            }
        }
    }
    
    // Μορφοποίηση των ομαδοποιημένων υποκατηγοριών
    $groupedText = [];
    foreach ($groupedSubSpecialities as $group => $items) {
        $groupedText[] = "Ομάδα {$group}: " . implode(', ', $items);
    }
    
    return [
        'has_operator_license' => !empty($specialities),
        'specialities' => $specialities,
        'specialities_text' => implode(', ', $specialities),
        'sub_specialities' => $subSpecialities,
        'sub_specialities_text' => implode(', ', $subSpecialities),
        'grouped_text' => implode(' | ', $groupedText),
        'details' => !empty($operatorLicenses) ? $operatorLicenses[0] : null
    ];
}

/**
 * Μορφοποίηση των καρτών ταχογράφου σε αναγνώσιμη μορφή
 * 
 * @param array $tachographCards Οι κάρτες ταχογράφου
 * @return array Μορφοποιημένες κάρτες
 */
public function formatTachographCards($tachographCards) {
    return [
        'has_tachograph' => !empty($tachographCards),
        'detail' => !empty($tachographCards) ? $tachographCards[0] : null
    ];
}

/**
 * Μορφοποίηση των ειδικών αδειών σε αναγνώσιμη μορφή
 * 
 * @param array $specialLicenses Οι ειδικές άδειες
 * @return array Μορφοποιημένες άδειες
 */
public function formatSpecialLicenses($specialLicenses) {
    $types = [];
    
    foreach ($specialLicenses as $license) {
        $types[] = $license['license_type'];
    }
    
    return [
        'has_special_licenses' => !empty($specialLicenses),
        'types' => $types,
        'details' => $specialLicenses
    ];
}

/**
 * Ανάκτηση ολοκληρωμένων πληροφοριών αδειών και πιστοποιήσεων για εμφάνιση
 * 
 * @param int $driverId Το ID του οδηγού
 * @return array Πλήρεις πληροφορίες πιστοποιήσεων
 */
public function getFormattedDriverCertifications($driverId) {
    $result = $this->getDriverCertificationsDetails($driverId);
    
    if (!$result['success']) {
        return [
            'success' => false,
            'message' => 'Αδυναμία ανάκτησης πληροφοριών οδηγού'
        ];
    }
    
    return [
        'success' => true,
        'driver' => $result['driver'],
        'licenses' => $this->formatDriverLicenses($result['licenses']),
        'adr' => $this->formatAdrCertificates($result['adr_certificates']),
        'operator' => $this->formatOperatorLicenses($result['operator_licenses']),
        'tachograph' => $this->formatTachographCards($result['tachograph_cards']),
        'special_licenses' => $this->formatSpecialLicenses($result['special_licenses'])
    ];
}
/**
 * Ανακτά τις βαθμολογίες ενός οδηγού (skills, safety, professionalism, technical)
 * 
 * @param int $driverId ID του οδηγού
 * @return array|null Οι βαθμολογίες του οδηγού ή null αν δεν υπάρχουν
 */
public function getDriverRatingDetails($driverId) {
    try {
        $sql = "SELECT * FROM driver_ratings WHERE driver_id = :driver_id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['driver_id' => $driverId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        error_log('Error getting driver rating details: ' . $e->getMessage());
        return null;
    }
}
}
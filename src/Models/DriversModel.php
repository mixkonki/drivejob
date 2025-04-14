<?php
namespace Drivejob\Models;
use PDO;
use PDOException;

class DriversModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Δημιουργεί έναν νέο λογαριασμό οδηγού
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
     * Ενημερώνει τα στοιχεία ενός οδηγού
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
     */
    public function getDriverById($id) {
        $sql = "SELECT * FROM drivers WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Επιστρέφει έναν οδηγό με βάση το email
     */
    public function getDriverByEmail($email) {
        $sql = "SELECT * FROM drivers WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Διαγράφει έναν οδηγό
     */
    public function delete($id) {
        $sql = "DELETE FROM drivers WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Ενημερώνει την κατάσταση επαλήθευσης του οδηγού
     */
    public function verifyDriver($email) {
        $sql = "UPDATE drivers SET is_verified = 1 WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['email' => $email]);
    }
    
    /**
     * Ενημερώνει τον κωδικό πρόσβασης ενός οδηγού
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
        
        return $stmt->execute($values);
    }
    
    /**
     * Ενημερώνει την εικόνα προφίλ ενός οδηγού
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
     */
    public function updateLastLogin($id) {
        $sql = "UPDATE drivers SET last_login = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Ενημερώνει την αξιολόγηση ενός οδηγού
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
     */
    public function countDrivers() {
        $sql = "SELECT COUNT(*) FROM drivers WHERE is_verified = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    /**
     * Αναζητά οδηγούς με βάση κριτήρια
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
            $conditions[] = "driving_license = :driving_license";
            $parameters['driving_license'] = $params['driving_license'];
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
        $countStmt->execute($parameters);
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
            $stmt->bindValue(':' . $key, $value);
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
     */
    public function emailExists($email) {
        $sql = "SELECT COUNT(*) FROM drivers WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Επιστρέφει τους πιο πρόσφατους διαθέσιμους οδηγούς
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
    
    /**
     * Διαγράφει όλες τις άδειες οδήγησης του οδηγού
     * 
     * @param int $driverId ID του οδηγού
     * @return bool Επιτυχία/αποτυχία
     */
    public function deleteDriverLicenses($driverId) {
        $sql = "DELETE FROM driver_licenses WHERE driver_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$driverId]);
    }

   /**
 * Ενημερώνει την εικόνα του διπλώματος του οδηγού (εμπρόσθια ή οπίσθια όψη)
 * 
 * @param int $driverId ID του οδηγού
 * @param string $imageType Τύπος εικόνας ('license_front' ή 'license_back')
 * @param string $imagePath Διαδρομή προς την εικόνα
 * @return bool Επιτυχία ή αποτυχία της ενημέρωσης
 */
public function updateDriverLicenseImage($driverId, $imageType, $imagePath) {
    try {
        $columnName = $imageType . '_image'; // Δημιουργία του ονόματος της στήλης (license_front_image ή license_back_image)
        
        $sql = "UPDATE drivers SET $columnName = :imagePath WHERE id = :driverId";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':imagePath', $imagePath, \PDO::PARAM_STR);
        $stmt->bindParam(':driverId', $driverId, \PDO::PARAM_INT);
        
        return $stmt->execute();
    } catch (\PDOException $e) {
        // Καταγραφή του σφάλματος
        error_log('Σφάλμα κατά την ενημέρωση εικόνας διπλώματος: ' . $e->getMessage());
        return false;
    }
}

/**
 * Προσθήκη άδειας οδήγησης για τον οδηγό με βελτιωμένο χειρισμό των ημερομηνιών λήξης
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
    return $stmt->execute([
        $driverId, 
        $licenseType, 
        $hasPei ? 1 : 0, 
        $expiryDate, 
        $licenseNumber,
        $peiExpiryCValue, 
        $peiExpiryDValue,
        $licenseDocumentExpiry
    ]);
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
    
    // Μέθοδοι για τα πιστοποιητικά ADR
    public function getDriverADRCertificate($driverId) {
        $sql = "SELECT * FROM driver_adr_certificates WHERE driver_id = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$driverId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function deleteDriverADRCertificates($driverId) {
        $sql = "DELETE FROM driver_adr_certificates WHERE driver_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$driverId]);
    }
    
    // Μέθοδοι για τις άδειες χειριστή μηχανημάτων
    public function getDriverOperatorLicense($driverId) {
        $sql = "SELECT * FROM driver_operator_licenses WHERE driver_id = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$driverId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getDriverOperatorSubSpecialities($operatorLicenseId) {
        $sql = "SELECT * FROM driver_operator_sub_specialities WHERE operator_license_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$operatorLicenseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function deleteDriverOperatorLicenses($driverId) {
        $sql = "DELETE FROM driver_operator_licenses WHERE driver_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$driverId]);
    }
    
    // Κάρτα Ψηφιακού Ταχογράφου
    public function getDriverTachographCard($driverId) {
        $sql = "SELECT * FROM driver_tachograph_cards WHERE driver_id = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$driverId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function deleteDriverTachographCard($driverId) {
        $sql = "DELETE FROM driver_tachograph_cards WHERE driver_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$driverId]);
    }
    
    public function addDriverTachographCard($driverId, $cardNumber, $expiryDate) {
        $sql = "INSERT INTO driver_tachograph_cards (driver_id, card_number, expiry_date) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$driverId, $cardNumber, $expiryDate]);
    }
    
    // Ειδικές Άδειες
    public function getDriverSpecialLicenses($driverId) {
        $sql = "SELECT * FROM driver_special_licenses WHERE driver_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$driverId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getDriverSpecialLicenseByType($driverId, $licenseType) {
        $sql = "SELECT * FROM driver_special_licenses WHERE driver_id = ? AND license_type = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$driverId, $licenseType]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function deleteDriverSpecialLicenses($driverId) {
        $sql = "DELETE FROM driver_special_licenses WHERE driver_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$driverId]);
    }
    
    public function deleteDriverSpecialLicenseByType($driverId, $licenseType) {
        $sql = "DELETE FROM driver_special_licenses WHERE driver_id = ? AND license_type = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$driverId, $licenseType]);
    }
    
    public function addDriverSpecialLicense($driverId, $licenseType, $licenseNumber, $expiryDate, $details = null) {
        $sql = "INSERT INTO driver_special_licenses (driver_id, license_type, license_number, expiry_date, details) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$driverId, $licenseType, $licenseNumber, $expiryDate, $details]);
    }
    
    // Τροποποιημένες μέθοδοι για το ADR
    public function addDriverADRCertificate($driverId, $adrType, $expiryDate, $certificateNumber) {
        $sql = "INSERT INTO driver_adr_certificates (driver_id, adr_type, expiry_date, certificate_number) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$driverId, $adrType, $expiryDate, $certificateNumber]);
    }
    
    // Τροποποιημένες μέθοδοι για χειριστές μηχανημάτων
    public function addDriverOperatorLicense($driverId, $speciality, $expiryDate, $licenseNumber) {
        $sql = "INSERT INTO driver_operator_licenses (driver_id, speciality, expiry_date, license_number) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$driverId, $speciality, $expiryDate, $licenseNumber]);
        return $this->pdo->lastInsertId();
    }
    
    public function addDriverOperatorSubSpeciality($operatorLicenseId, $subSpeciality) {
        $sql = "INSERT INTO driver_operator_sub_specialities (operator_license_id, sub_speciality) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$operatorLicenseId, $subSpeciality]);
        return $this->pdo->lastInsertId();
    }
    
    public function addDriverOperatorSubSpecialityGroup($subSpecialityId, $groupType) {
        $sql = "INSERT INTO driver_operator_sub_speciality_groups (sub_speciality_id, group_type) VALUES (?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$subSpecialityId, $groupType]);
    }
    
    // Μέθοδος για ειδοποιήσεις λήξης αδειών
    public function getDriversWithExpiringLicenses() {
        $twoMonthsFromNow = date('Y-m-d', strtotime('+2 months'));
        $oneYearFromNow = date('Y-m-d', strtotime('+1 year'));
        $eightYearsFromNow = date('Y-m-d', strtotime('+8 years'));
        
        // Οδηγοί με άδειες οδήγησης που λήγουν σε 2 μήνες
        $drivingLicensesSql = "
            SELECT d.id, d.first_name, d.last_name, d.email, 'driving_license' as type, dl.expiry_date 
            FROM drivers d 
            JOIN driver_licenses dl ON d.id = dl.driver_id 
            WHERE dl.expiry_date <= ? AND dl.expiry_date >= CURRENT_DATE()
        ";
        
        // Οδηγοί με ADR που λήγουν σε 1 χρόνο
        $adrSql = "
            SELECT d.id, d.first_name, d.last_name, d.email, 'adr_certificate' as type, dac.expiry_date 
            FROM drivers d 
            JOIN driver_adr_certificates dac ON d.id = dac.driver_id 
            WHERE dac.expiry_date <= ? AND dac.expiry_date >= CURRENT_DATE()
        ";
        
        // Οδηγοί με άδειες χειριστή που λήγουν σε 8 χρόνια
        $operatorSql = "
            SELECT d.id, d.first_name, d.last_name, d.email, 'operator_license' as type, dol.expiry_date 
            FROM drivers d 
            JOIN driver_operator_licenses dol ON d.id = dol.driver_id 
            WHERE dol.expiry_date <= ? AND dol.expiry_date >= CURRENT_DATE()
        ";
        
        $drivingLicensesStmt = $this->pdo->prepare($drivingLicensesSql);
        $adrStmt = $this->pdo->prepare($adrSql);
        $operatorStmt = $this->pdo->prepare($operatorSql);
        
        $drivingLicensesStmt->execute([$twoMonthsFromNow]);
        $adrStmt->execute([$oneYearFromNow]);
        $operatorStmt->execute([$eightYearsFromNow]);
        
        $drivingLicenses = $drivingLicensesStmt->fetchAll(PDO::FETCH_ASSOC);
        $adrCertificates = $adrStmt->fetchAll(PDO::FETCH_ASSOC);
        $operatorLicenses = $operatorStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'driving_licenses' => $drivingLicenses,
            'adr_certificates' => $adrCertificates,
            'operator_licenses' => $operatorLicenses
        ];
    }
}
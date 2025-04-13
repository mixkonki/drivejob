<?php

namespace Drivejob\Models;

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
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Επιστρέφει έναν οδηγό με βάση το email
     */
    public function getDriverByEmail($email) {
        $sql = "SELECT * FROM drivers WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
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
     */
    public function updateProfile($id, $data) {
        $sql = "UPDATE drivers SET
            first_name = :first_name,
            last_name = :last_name,
            phone = :phone,
            birth_date = :birth_date,
            address = :address,
            house_number = :house_number,
            city = :city,
            country = :country,
            postal_code = :postal_code,
            about_me = :about_me,
            experience_years = :experience_years,
            driving_license = :driving_license,
            driving_license_expiry = :driving_license_expiry,
            adr_certificate = :adr_certificate,
            adr_certificate_expiry = :adr_certificate_expiry,
            operator_license = :operator_license,
            operator_license_expiry = :operator_license_expiry,
            training_seminars = :training_seminars,
            training_details = :training_details,
            available_for_work = :available_for_work,
            preferred_job_type = :preferred_job_type,
            preferred_location = :preferred_location,
            social_linkedin = :social_linkedin
        WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'birth_date' => $data['birth_date'] ?: null,
            'address' => $data['address'] ?: null,
            'house_number' => $data['house_number'] ?: null,
            'city' => $data['city'] ?: null,
            'country' => $data['country'] ?: null,
            'postal_code' => $data['postal_code'] ?: null,
            'about_me' => $data['about_me'] ?: null,
            'experience_years' => $data['experience_years'] ?: null,
            'driving_license' => $data['driving_license'] ?: null,
            'driving_license_expiry' => $data['driving_license_expiry'] ?: null,
            'adr_certificate' => isset($data['adr_certificate']) ? 1 : 0,
            'adr_certificate_expiry' => $data['adr_certificate_expiry'] ?: null,
            'operator_license' => isset($data['operator_license']) ? 1 : 0,
            'operator_license_expiry' => $data['operator_license_expiry'] ?: null,
            'training_seminars' => isset($data['training_seminars']) ? 1 : 0,
            'training_details' => $data['training_details'] ?: null,
            'available_for_work' => isset($data['available_for_work']) ? 1 : 0,
            'preferred_job_type' => $data['preferred_job_type'] ?: null,
            'preferred_location' => $data['preferred_location'] ?: null,
            'social_linkedin' => $data['social_linkedin'] ?: null
        ]);
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
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        
        // Προσθήκη των υπόλοιπων παραμέτρων
        foreach ($parameters as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
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
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Μέθοδοι για τις άδειες οδήγησης
public function getDriverLicenses($driverId) {
    $sql = "SELECT * FROM driver_licenses WHERE driver_id = ?";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$driverId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function deleteDriverLicenses($driverId) {
    $sql = "DELETE FROM driver_licenses WHERE driver_id = ?";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([$driverId]);
}

public function addDriverLicense($driverId, $licenseType, $hasPei, $expiryDate) {
    $sql = "INSERT INTO driver_licenses (driver_id, license_type, has_pei, expiry_date) VALUES (?, ?, ?, ?)";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([$driverId, $licenseType, $hasPei ? 1 : 0, $expiryDate]);
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

public function addDriverADRCertificate($driverId, $adrType, $expiryDate) {
    $sql = "INSERT INTO driver_adr_certificates (driver_id, adr_type, expiry_date) VALUES (?, ?, ?)";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([$driverId, $adrType, $expiryDate]);
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

public function addDriverOperatorLicense($driverId, $speciality, $expiryDate) {
    $sql = "INSERT INTO driver_operator_licenses (driver_id, speciality, expiry_date) VALUES (?, ?, ?)";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$driverId, $speciality, $expiryDate]);
    return $this->pdo->lastInsertId();
}

public function addDriverOperatorSubSpeciality($operatorLicenseId, $subSpeciality, $groupType) {
    $sql = "INSERT INTO driver_operator_sub_specialities (operator_license_id, sub_speciality, group_type) VALUES (?, ?, ?)";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([$operatorLicenseId, $subSpeciality, $groupType]);
}
}
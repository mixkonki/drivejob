<?php

namespace Drivejob\Models;

class CompaniesModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo; // Αρχικοποιήστε την ιδιότητα
    }

    public function getCompanyById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM companies WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Δημιουργεί ένα νέο λογαριασμό εταιρείας
     */
    public function create($data) {
        $sql = "INSERT INTO companies (email, password, company_name, phone, is_verified) 
                VALUES (:email, :password, :company_name, :phone, :is_verified)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'email' => $data['email'],
            'password' => $data['password'],
            'company_name' => $data['company_name'],
            'phone' => $data['phone'],
            'is_verified' => $data['is_verified'] ?? 0
        ]);
        
        return $this->pdo->lastInsertId();
    }

    /**
     * Ενημερώνει τα στοιχεία μιας εταιρείας
     */
    public function update($id, $data) {
        $sql = "UPDATE companies SET 
                email = :email, 
                company_name = :company_name, 
                phone = :phone 
                WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'email' => $data['email'],
            'company_name' => $data['company_name'],
            'phone' => $data['phone']
        ]);
    }

    /**
     * Επιστρέφει μια εταιρεία με βάση το email
     */
    public function getCompanyByEmail($email) {
        $sql = "SELECT * FROM companies WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Διαγράφει μια εταιρεία
     */
    public function delete($id) {
        $sql = "DELETE FROM companies WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Ενημερώνει την κατάσταση επαλήθευσης της εταιρείας
     */
    public function verifyCompany($email) {
        $sql = "UPDATE companies SET is_verified = 1 WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['email' => $email]);
    }

    /**
     * Ενημερώνει τον κωδικό πρόσβασης μιας εταιρείας
     */
    public function updatePassword($id, $password) {
        $sql = "UPDATE companies SET password = :password WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'password' => $password
        ]);
    }

    /**
     * Ενημερώνει το προφίλ μιας εταιρείας
     */
    public function updateProfile($id, $data) {
        $sql = "UPDATE companies SET
            company_name = :company_name,
            phone = :phone,
            description = :description,
            website = :website,
            address = :address,
            city = :city,
            country = :country,
            postal_code = :postal_code,
            contact_person = :contact_person,
            position = :position,
            vat_number = :vat_number,
            company_size = :company_size,
            foundation_year = :foundation_year,
            industry = :industry,
            social_linkedin = :social_linkedin,
            social_facebook = :social_facebook,
            social_twitter = :social_twitter
        WHERE id = :id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'company_name' => $data['company_name'],
            'phone' => $data['phone'],
            'description' => $data['description'] ?: null,
            'website' => $data['website'] ?: null,
            'address' => $data['address'] ?: null,
            'city' => $data['city'] ?: null,
            'country' => $data['country'] ?: null,
            'postal_code' => $data['postal_code'] ?: null,
            'contact_person' => $data['contact_person'] ?: null,
            'position' => $data['position'] ?: null,
            'vat_number' => $data['vat_number'] ?: null,
            'company_size' => $data['company_size'] ?: null,
            'foundation_year' => $data['foundation_year'] ?: null,
            'industry' => $data['industry'] ?: null,
            'social_linkedin' => $data['social_linkedin'] ?: null,
            'social_facebook' => $data['social_facebook'] ?: null,
            'social_twitter' => $data['social_twitter'] ?: null
        ]);
    }

    /**
     * Ενημερώνει το λογότυπο μιας εταιρείας
     */
    public function updateCompanyLogo($id, $logoPath) {
        $sql = "UPDATE companies SET company_logo = :company_logo WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'company_logo' => $logoPath
        ]);
    }

    /**
     * Ενημερώνει την τελευταία σύνδεση της εταιρείας
     */
    public function updateLastLogin($id) {
        $sql = "UPDATE companies SET last_login = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Ενημερώνει την αξιολόγηση μιας εταιρείας
     */
    public function updateRating($id, $rating) {
        $sql = "UPDATE companies SET 
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
     * Ελέγχει αν ένα email υπάρχει ήδη
     */
    public function emailExists($email) {
        $sql = "SELECT COUNT(*) FROM companies WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Αναζητά εταιρείες με βάση κριτήρια
     */
    public function searchCompanies($params, $page = 1, $limit = 10) {
        $conditions = ["is_verified = 1"];
        $parameters = [];
        
        // Αναζήτηση βάσει ονόματος
        if (isset($params['company_name']) && $params['company_name']) {
            $conditions[] = "company_name LIKE :company_name";
            $parameters['company_name'] = '%' . $params['company_name'] . '%';
        }
        
        // Αναζήτηση βάσει τοποθεσίας
        if (isset($params['location']) && $params['location']) {
            $conditions[] = "(city LIKE :location OR country LIKE :location)";
            $parameters['location'] = '%' . $params['location'] . '%';
        }
        
        // Αναζήτηση βάσει κλάδου
        if (isset($params['industry']) && $params['industry']) {
            $conditions[] = "industry LIKE :industry";
            $parameters['industry'] = '%' . $params['industry'] . '%';
        }
        
        // Σύνθεση του SQL ερωτήματος
        $whereClause = implode(" AND ", $conditions);
        $offset = ($page - 1) * $limit;
        
        // Μέτρηση συνολικών αποτελεσμάτων
        $countSql = "SELECT COUNT(*) FROM companies WHERE $whereClause";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($parameters);
        $totalResults = $countStmt->fetchColumn();
        
        // Εκτέλεση του κύριου ερωτήματος
        $sql = "SELECT id, company_name, city, country, industry, 
                       company_size, website, company_logo, rating 
                FROM companies 
                WHERE $whereClause 
                ORDER BY company_name 
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
}

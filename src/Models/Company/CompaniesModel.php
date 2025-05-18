<?php

namespace Drivejob\Models\Company;

use Drivejob\Core\Logger;

/**
 * Μοντέλο για τη διαχείριση των εταιρειών
 */
class CompaniesModel
{
    private $pdo;

    /**
     * Κατασκευαστής του μοντέλου
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Επιστρέφει τα στοιχεία μιας εταιρείας με βάση το ID
     *
     * @param int $companyId ID της εταιρείας
     * @return array|false Τα στοιχεία της εταιρείας ή false αν δεν βρέθηκε
     */
    public function getCompanyById($companyId)
    {
        try {
            $query = "SELECT * FROM companies WHERE id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$companyId]);

            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            Logger::error('Error in getCompanyById: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Επιστρέφει τα στοιχεία μιας εταιρείας με βάση το email
     *
     * @param string $email Email της εταιρείας
     * @return array|false Τα στοιχεία της εταιρείας ή false αν δεν βρέθηκε
     */
    public function getCompanyByEmail($email)
    {
        try {
            $query = "SELECT * FROM companies WHERE email = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$email]);

            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            Logger::error('Error in getCompanyByEmail: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημερώνει το προφίλ μιας εταιρείας
     *
     * @param int $companyId ID της εταιρείας
     * @param array $data Δεδομένα προφίλ
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateProfile($companyId, $data)
    {
        try {
            // Δημιουργία του SQL ερωτήματος
            $fields = [];
            $values = [];

            foreach ($data as $field => $value) {
                $fields[] = "$field = ?";
                $values[] = $value;
            }

            $values[] = $companyId;

            $sql = "UPDATE companies SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute($values);
        } catch (\PDOException $e) {
            Logger::error('Error in updateProfile: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημερώνει το λογότυπο μιας εταιρείας
     *
     * @param int $companyId ID της εταιρείας
     * @param string $logoPath Διαδρομή του λογότυπου
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateCompanyLogo($companyId, $logoPath)
    {
        try {
            $sql = "UPDATE companies SET logo = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute([$logoPath, $companyId]);
        } catch (\PDOException $e) {
            Logger::error('Error in updateCompanyLogo: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Αναζητά εταιρείες με βάση διάφορα κριτήρια
     *
     * @param array $params Παράμετροι αναζήτησης
     * @param int $page Αριθμός σελίδας
     * @param int $limit Αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Αποτελέσματα αναζήτησης και πληροφορίες σελιδοποίησης
     */
    public function searchCompanies($params = [], $page = 1, $limit = 10)
    {
        try {
            $query = "SELECT * FROM companies WHERE is_active = 1 AND is_verified = 1";
            $queryParams = [];

            // Προσθήκη συνθηκών αναζήτησης
            if (!empty($params['company_name'])) {
                $query .= " AND company_name LIKE ?";
                $queryParams[] = '%' . $params['company_name'] . '%';
            }

            if (!empty($params['industry'])) {
                $query .= " AND industry = ?";
                $queryParams[] = $params['industry'];
            }

            if (!empty($params['location'])) {
                $query .= " AND (city LIKE ? OR country LIKE ?)";
                $queryParams[] = '%' . $params['location'] . '%';
                $queryParams[] = '%' . $params['location'] . '%';
            }

            // Αναζήτηση με βάση την τοποθεσία
            if (!empty($params['latitude']) && !empty($params['longitude']) && !empty($params['search_radius'])) {
                $lat = $params['latitude'];
                $lng = $params['longitude'];
                $radius = $params['search_radius'];

                // Υπολογισμός απόστασης με τον τύπο Haversine
                $query .= " AND (
                    6371 * acos(
                        cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + 
                        sin(radians(?)) * sin(radians(latitude))
                    ) <= ?
                )";

                $queryParams[] = $lat;
                $queryParams[] = $lng;
                $queryParams[] = $lat;
                $queryParams[] = $radius;
            }

            // Ταξινόμηση
            $query .= " ORDER BY company_name ASC";

            // Μέτρηση συνολικών αποτελεσμάτων
            $countQuery = "SELECT COUNT(*) FROM ($query) as count_table";
            $countStmt = $this->pdo->prepare($countQuery);
            $countStmt->execute($queryParams);
            $totalResults = $countStmt->fetchColumn();

            // Προσθήκη σελιδοποίησης
            $offset = ($page - 1) * $limit;
            $query .= " LIMIT ?, ?";
            $queryParams[] = $offset;
            $queryParams[] = $limit;

            // Εκτέλεση του ερωτήματος
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($queryParams);
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
        } catch (\PDOException $e) {
            Logger::error('Error in searchCompanies: ' . $e->getMessage());

            return [
                'results' => [],
                'pagination' => [
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => 0
                ]
            ];
        }
    }

    /**
     * Δημιουργεί μια νέα εταιρεία
     *
     * @param array $data Δεδομένα εταιρείας
     * @return int|false ID της νέας εταιρείας ή false σε περίπτωση αποτυχίας
     */
    public function create($data)
    {
        try {
            // Δημιουργία του SQL ερωτήματος
            $fields = array_keys($data);
            $placeholders = array_fill(0, count($fields), '?');

            $sql = "INSERT INTO companies (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            $stmt = $this->pdo->prepare($sql);

            if ($stmt->execute(array_values($data))) {
                return $this->pdo->lastInsertId();
            }

            return false;
        } catch (\PDOException $e) {
            Logger::error('Error in create: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημερώνει την κατάσταση επαλήθευσης μιας εταιρείας
     *
     * @param int $companyId ID της εταιρείας
     * @param bool $verified Κατάσταση επαλήθευσης
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateVerificationStatus($companyId, $verified)
    {
        try {
            $sql = "UPDATE companies SET is_verified = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute([$verified ? 1 : 0, $companyId]);
        } catch (\PDOException $e) {
            Logger::error('Error in updateVerificationStatus: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημερώνει την κατάσταση ενεργοποίησης μιας εταιρείας
     *
     * @param int $companyId ID της εταιρείας
     * @param bool $active Κατάσταση ενεργοποίησης
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateActiveStatus($companyId, $active)
    {
        try {
            $sql = "UPDATE companies SET is_active = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute([$active ? 1 : 0, $companyId]);
        } catch (\PDOException $e) {
            Logger::error('Error in updateActiveStatus: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ενημερώνει την ημερομηνία τελευταίας σύνδεσης μιας εταιρείας
     *
     * @param int $companyId ID της εταιρείας
     * @return bool Επιτυχία/αποτυχία
     */
    public function updateLastLogin($companyId)
    {
        try {
            $sql = "UPDATE companies SET last_login = NOW() WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);

            return $stmt->execute([$companyId]);
        } catch (\PDOException $e) {
            Logger::error('Error in updateLastLogin: ' . $e->getMessage());
            return false;
        }
    }
}

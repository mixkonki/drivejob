<?php

namespace Drivejob\Models;

use Drivejob\Core\Logger;

/**
 * Μοντέλο για τη διαχείριση των προφίλ οδηγών και εταιρειών
 */
class ProfileModel
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
     * Επιστρέφει τα στοιχεία ενός οδηγού με βάση το ID
     *
     * @param int $driverId ID του οδηγού
     * @return array|false Τα στοιχεία του οδηγού ή false αν δεν βρέθηκε
     */
    public function getDriverById($driverId)
    {
        try {
            $query = "SELECT d.*, 
                      GROUP_CONCAT(DISTINCT dl.license_type) as license_types,
                      GROUP_CONCAT(DISTINCT ds.skill_name) as skills
                      FROM drivers d
                      LEFT JOIN driver_licenses dl ON d.id = dl.driver_id
                      LEFT JOIN driver_skills ds ON d.id = ds.driver_id
                      WHERE d.id = ?
                      GROUP BY d.id";

            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$driverId]);

            $driver = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($driver) {
                // Μετατροπή των license_types και skills σε πίνακες
                if (!empty($driver['license_types'])) {
                    $driver['license_types'] = explode(',', $driver['license_types']);
                } else {
                    $driver['license_types'] = [];
                }

                if (!empty($driver['skills'])) {
                    $driver['skills'] = explode(',', $driver['skills']);
                } else {
                    $driver['skills'] = [];
                }

                // Λήψη των αξιολογήσεων του οδηγού
                $driver['assessments'] = $this->getDriverAssessments($driverId);
            }

            return $driver;
        } catch (\PDOException $e) {
            Logger::error('Error in getDriverById: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Αναζητά οδηγούς με βάση διάφορα κριτήρια
     *
     * @param array $params Παράμετροι αναζήτησης
     * @param int $page Αριθμός σελίδας
     * @param int $limit Αριθμός αποτελεσμάτων ανά σελίδα
     * @return array Αποτελέσματα αναζήτησης και πληροφορίες σελιδοποίησης
     */
    public function searchDrivers($params = [], $page = 1, $limit = 10)
    {
        try {
            $query = "SELECT d.*, 
                      GROUP_CONCAT(DISTINCT dl.license_type) as license_types,
                      GROUP_CONCAT(DISTINCT ds.skill_name) as skills
                      FROM drivers d
                      LEFT JOIN driver_licenses dl ON d.id = dl.driver_id
                      LEFT JOIN driver_skills ds ON d.id = ds.driver_id
                      WHERE d.is_active = 1 AND d.is_verified = 1";

            $queryParams = [];

            // Προσθήκη συνθηκών αναζήτησης
            if (!empty($params['job_type'])) {
                if (is_array($params['job_type'])) {
                    $placeholders = implode(',', array_fill(0, count($params['job_type']), '?'));
                    $query .= " AND d.preferred_job_type IN ($placeholders)";
                    $queryParams = array_merge($queryParams, $params['job_type']);
                } else {
                    $query .= " AND d.preferred_job_type = ?";
                    $queryParams[] = $params['job_type'];
                }
            }

            if (!empty($params['vehicle_type'])) {
                if (is_array($params['vehicle_type'])) {
                    $placeholders = implode(',', array_fill(0, count($params['vehicle_type']), '?'));
                    $query .= " AND d.preferred_vehicle_type IN ($placeholders)";
                    $queryParams = array_merge($queryParams, $params['vehicle_type']);
                } else {
                    $query .= " AND d.preferred_vehicle_type = ?";
                    $queryParams[] = $params['vehicle_type'];
                }
            }

            if (!empty($params['min_experience']) && is_numeric($params['min_experience'])) {
                $query .= " AND d.experience_years >= ?";
                $queryParams[] = $params['min_experience'];
            }

            if (!empty($params['adr_certificate'])) {
                $query .= " AND d.adr_certificate = 1";
            }

            if (!empty($params['operator_license'])) {
                $query .= " AND d.operator_license = 1";
            }

            // Αναζήτηση με βάση την τοποθεσία
            if (!empty($params['latitude']) && !empty($params['longitude']) && !empty($params['search_radius'])) {
                $lat = $params['latitude'];
                $lng = $params['longitude'];
                $radius = $params['search_radius'];

                // Υπολογισμός απόστασης με τον τύπο Haversine
                $query .= " AND (
                    6371 * acos(
                        cos(radians(?)) * cos(radians(d.latitude)) * cos(radians(d.longitude) - radians(?)) + 
                        sin(radians(?)) * sin(radians(d.latitude))
                    ) <= ?
                )";

                $queryParams[] = $lat;
                $queryParams[] = $lng;
                $queryParams[] = $lat;
                $queryParams[] = $radius;
            }

            // Ομαδοποίηση για να αποφύγουμε διπλότυπα
            $query .= " GROUP BY d.id";

            // Ταξινόμηση
            $query .= " ORDER BY d.last_login DESC";

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

            // Μετατροπή των license_types και skills σε πίνακες για κάθε οδηγό
            foreach ($results as &$driver) {
                if (!empty($driver['license_types'])) {
                    $driver['license_types'] = explode(',', $driver['license_types']);
                } else {
                    $driver['license_types'] = [];
                }

                if (!empty($driver['skills'])) {
                    $driver['skills'] = explode(',', $driver['skills']);
                } else {
                    $driver['skills'] = [];
                }
            }

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
            Logger::error('Error in searchDrivers: ' . $e->getMessage());

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
     * Επιστρέφει τις αξιολογήσεις ενός οδηγού
     *
     * @param int $driverId ID του οδηγού
     * @return array Οι αξιολογήσεις του οδηγού
     */
    private function getDriverAssessments($driverId)
    {
        try {
            $query = "SELECT * FROM driver_assessments WHERE driver_id = ?";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([$driverId]);

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            Logger::error('Error in getDriverAssessments: ' . $e->getMessage());
            return [];
        }
    }
}

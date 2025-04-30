<?php

namespace Drivejob\Models;

use PDO;
use PDOException;
use Drivejob\Core\Debug;

// Προσθέστε αυτή τη γραμμή
class JobListingModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Δημιουργεί μια νέα αγγελία με υποστήριξη για πολλαπλούς τύπους οχημάτων
     */
    public function create($data)
    {
        try {
// Μετατροπή του πίνακα vehicle_types σε κείμενο
            if (isset($data['vehicle_types']) && is_array($data['vehicle_types'])) {
                $data['vehicle_types'] = implode(',', $data['vehicle_types']);
            }

            // Ορισμός του SQL ερωτήματος
            $sql = "INSERT INTO job_listings (
                        title, 
                        company_id, 
                        driver_id, 
                        listing_type, 
                        job_type, 
                        required_license, 
                        description, 
                        salary_min, 
                        salary_max, 
                        salary_type, 
                        location, 
                        latitude, 
                        longitude, 
                        radius, 
                        remote_possible, 
                        experience_years, 
                        adr_certificate, 
                        operator_license, 
                        required_training, 
                        benefits, 
                        contact_email, 
                        contact_phone, 
                        is_active, 
                        created_at, 
                        expires_at,
                        preferred_schedule,
                        max_days_away
                    ) VALUES (
                        :title, 
                        :company_id, 
                        :driver_id, 
                        :listing_type, 
                        :job_type, 
                        :required_license, 
                        :description, 
                        :salary_min, 
                        :salary_max, 
                        :salary_type, 
                        :location, 
                        :latitude, 
                        :longitude, 
                        :radius, 
                        :remote_possible, 
                        :experience_years, 
                        :adr_certificate, 
                        :operator_license, 
                        :required_training, 
                        :benefits, 
                        :contact_email, 
                        :contact_phone, 
                        :is_active, 
                        NOW(), 
                        :expires_at,
                        :preferred_schedule,
                        :max_days_away
                    )";
// Προετοιμασία της δήλωσης
            $stmt = $this->pdo->prepare($sql);
// Διαμόρφωση των παραμέτρων
            $params = [
                'title' => $data['title'],
                'company_id' => isset($data['company_id']) ? $data['company_id'] : null,
                'driver_id' => isset($data['driver_id']) ? $data['driver_id'] : null,
                'listing_type' => $data['listing_type'],
                'job_type' => $data['job_type'],
                'required_license' => $data['required_license'],
                'description' => $data['description'],
                'salary_min' => !empty($data['salary_min']) ? $data['salary_min'] : null,
                'salary_max' => !empty($data['salary_max']) ? $data['salary_max'] : null,
                'salary_type' => !empty($data['salary_type']) ? $data['salary_type'] : null,
                'location' => $data['location'],
                'latitude' => !empty($data['latitude']) ? $data['latitude'] : null,
                'longitude' => !empty($data['longitude']) ? $data['longitude'] : null,
                'radius' => !empty($data['radius']) ? $data['radius'] : null,
                'remote_possible' => isset($data['remote_possible']) ? 1 : 0,
                'experience_years' => !empty($data['experience_years']) ? $data['experience_years'] : null,
                'adr_certificate' => isset($data['show_adr']) ? 1 : 0,
                'operator_license' => isset($data['show_operator_license']) ? 1 : 0,
                'required_training' => isset($data['required_training']) ? $data['required_training'] : '',
                'benefits' => isset($data['benefits']) ? $data['benefits'] : '',
                'contact_email' => isset($data['contact_email']) ? $data['contact_email'] : null,
                'contact_phone' => isset($data['contact_phone']) ? $data['contact_phone'] : null,
                'is_active' => 1,
                'expires_at' => isset($data['expires_at']) ? $data['expires_at'] : date('Y-m-d', strtotime('+30 days')),
                'preferred_schedule' => isset($data['preferred_schedule']) ? $data['preferred_schedule'] : null,
                'max_days_away' => isset($data['max_days_away']) ? $data['max_days_away'] : null
            ];
// Εκτέλεση του ερωτήματος
            $stmt->execute($params);
// Επιστροφή του ID της νέας εγγραφής
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
        // Καταγραφή του σφάλματος για αποσφαλμάτωση
            error_log('Error creating job listing: ' . $e->getMessage() . "\nData: " . print_r($data, true));
            throw new Exception('Υπήρξε ένα σφάλμα κατά τη δημιουργία της αγγελίας: ' . $e->getMessage());
        }
    }

    /**
     * Ενημερώνει μια αγγελία με υποστήριξη για πολλαπλούς τύπους οχημάτων
     */
/**
 * Ενημερώνει μια αγγελία
 *
 * @param int $id ID της αγγελίας
 * @param array $data Δεδομένα προς ενημέρωση
 * @return bool Επιτυχία/αποτυχία
 */
    public function update($id, $data)
    {
        \Drivejob\Core\Debug::log("JobListingModel::update - Έναρξη με ID: $id", $data);
        try {
        // Αφαίρεση vehicle_types αν υπάρχει
            if (isset($data['vehicle_types'])) {
                \Drivejob\Core\Debug::log("Αφαίρεση του πεδίου vehicle_types από τα δεδομένα");
                unset($data['vehicle_types']);
            }

            // Δημιουργία του SQL ερωτήματος
            $sql = "UPDATE job_listings SET ";
            $updateParts = [];
            $params = [];
        // Δυναμική δημιουργία των μερών του SQL ερωτήματος βάσει των διαθέσιμων δεδομένων
            foreach ($data as $key => $value) {
                if ($key !== 'id') {
                // Εξαιρούμε το id από τα πεδία που θα ενημερωθούν
                    $updateParts[] = "$key = :$key";
                    $params[$key] = $value;
                    \Drivejob\Core\Debug::log("Προσθήκη πεδίου για ενημέρωση: $key = " . (is_array($value) ? json_encode($value) : $value));
                }
            }

            // Προσθήκη του updated_at
            $updateParts[] = "updated_at = CURRENT_TIMESTAMP";
        // Ολοκλήρωση του SQL ερωτήματος
            $sql .= implode(", ", $updateParts);
            $sql .= " WHERE id = :id";
            $params['id'] = $id;
            \Drivejob\Core\Debug::log("SQL ερώτημα: $sql", $params);
        // Προετοιμασία και εκτέλεση του ερωτήματος
            $stmt = $this->pdo->prepare($sql);
            try {
                $result = $stmt->execute($params);
                \Drivejob\Core\Debug::log("Επιτυχής εκτέλεση του SQL ερωτήματος, αποτέλεσμα: " . ($result ? 'true' : 'false'));
                return true;
            } catch (\PDOException $e) {
                \Drivejob\Core\Debug::log("Σφάλμα PDO κατά την εκτέλεση: " . $e->getMessage(), [
                'SQL' => $sql,
                'Params' => $params,
                'ErrorInfo' => $stmt->errorInfo()
                ]);
                throw $e;
            }
        } catch (\Exception $e) {
            \Drivejob\Core\Debug::log("Γενικό σφάλμα: " . $e->getMessage(), $e->getTraceAsString());
            throw $e;
        }
    }
/**
 * Ανακτά μια αγγελία από τη βάση δεδομένων με βάση το ID
 *
 * @param int $id Το ID της αγγελίας
 * @return array|false Τα δεδομένα της αγγελίας ή false αν δεν βρέθηκε
 */
/**
 * Ανακτά μια αγγελία από τη βάση δεδομένων με βάση το ID
 *
 * @param int $id Το ID της αγγελίας
 * @return array|false Τα δεδομένα της αγγελίας ή false αν δεν βρέθηκε
 */
    public function getById($id)
    {
        try {
            $sql = "SELECT * FROM job_listings WHERE id = :id LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
// Προσθήκη της ανάστροφης καθέτου
        } catch (\PDOException $e) {
        // Προσθήκη της ανάστροφης καθέτου
            error_log('Error getting job listing: ' . $e->getMessage());
            return false;
        }
    }

    public function saveVehicleTypes($jobListingId, $vehicleTypes)
    {
        $sql = "INSERT INTO job_listing_vehicle_types (job_listing_id, vehicle_type) VALUES (:job_listing_id, :vehicle_type)";
        $stmt = $this->pdo->prepare($sql);
        foreach ($vehicleTypes as $vehicleType) {
            $stmt->execute([
            'job_listing_id' => $jobListingId,
            'vehicle_type' => $vehicleType
            ]);
        }
    }

    public function deleteVehicleTypes($jobListingId)
    {
        $sql = "DELETE FROM job_listing_vehicle_types WHERE job_listing_id = :job_listing_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['job_listing_id' => $jobListingId]);
    }
    /**
 * Επιστρέφει τους τύπους οχημάτων για μια αγγελία
 * Τροποποίηση της μεθόδου ώστε να είναι public και όχι private
 *
 * @param int $jobListingId Το ID της αγγελίας
 * @return array Οι τύποι οχημάτων
 */
    public function getVehicleTypes($jobListingId)
    {
        try {
            $sql = "SELECT vehicle_type FROM job_listing_vehicle_types WHERE job_listing_id = :job_listing_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['job_listing_id' => $jobListingId]);
            return $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log('Error getting vehicle types: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Επιστρέφει τις ενεργές αγγελίες με βάση κριτήρια,
     * συμπεριλαμβανομένης της υποστήριξης για πολλαπλούς τύπους οχημάτων
     */
    public function getActiveListings($params = [], $page = 1, $limit = 10)
    {
        $conditions = ["jl.is_active = 1"];
        $parameters = [];
        $joins = [];
// Filter by listing type
        if (isset($params['listing_type'])) {
            $conditions[] = "jl.listing_type = :listing_type";
            $parameters['listing_type'] = $params['listing_type'];
        }

        // Filter by job type
        if (isset($params['job_type'])) {
            $conditions[] = "jl.job_type = :job_type";
            $parameters['job_type'] = $params['job_type'];
        }

        // Filter by vehicle type (πολλαπλές επιλογές)
        if (isset($params['vehicle_types']) && is_array($params['vehicle_types']) && !empty($params['vehicle_types'])) {
            $vehicleTypeCondition = [];
            foreach ($params['vehicle_types'] as $index => $vehicleType) {
                $paramName = "vehicle_type_" . $index;
                $vehicleTypeCondition[] = "jlvt.vehicle_type = :" . $paramName;
                $parameters[$paramName] = $vehicleType;
            }

            if (!empty($vehicleTypeCondition)) {
                $joins[] = "LEFT JOIN job_listing_vehicle_types jlvt ON jl.id = jlvt.job_listing_id";
                $conditions[] = "(" . implode(" OR ", $vehicleTypeCondition) . ")";
            }
        }

        // Filter by location (search within radius)
        if (isset($params['latitude']) && isset($params['longitude']) && isset($params['search_radius'])) {
// Haversine formula for finding distance in km
            $conditions[] = "(
                6371 * acos(
                    cos(radians(:latitude)) * 
                    cos(radians(jl.latitude)) * 
                    cos(radians(jl.longitude) - radians(:longitude)) + 
                    sin(radians(:latitude)) * 
                    sin(radians(jl.latitude))
                )
            ) <= :search_radius";
            $parameters['latitude'] = $params['latitude'];
            $parameters['longitude'] = $params['longitude'];
            $parameters['search_radius'] = $params['search_radius'];
        }

        // Filter by preferred schedule
        if (isset($params['preferred_schedule'])) {
            $conditions[] = "jl.preferred_schedule = :preferred_schedule";
            $parameters['preferred_schedule'] = $params['preferred_schedule'];
        }

        // Filter by max days away
        if (isset($params['max_days_away'])) {
            $conditions[] = "jl.max_days_away <= :max_days_away";
            $parameters['max_days_away'] = $params['max_days_away'];
        }

        // Υπόλοιπα φίλτρα από την υπάρχουσα μέθοδο...
        // ...

        // Build the FROM and JOIN clause
        $fromClause = "FROM job_listings jl";
        if (!empty($joins)) {
            $fromClause .= " " . implode(" ", $joins);
        }

        // Build the WHERE clause
        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
// Group by για να αποφύγουμε διπλότυπα αποτελέσματα λόγω των joins
        $groupByClause = "GROUP BY jl.id";
// Calculate offset for pagination
        $offset = ($page - 1) * $limit;
// Count total results for pagination
        $countSql = "SELECT COUNT(DISTINCT jl.id) " . $fromClause . " " . $whereClause;
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($parameters);
        $totalResults = $countStmt->fetchColumn();
// Execute the main query with pagination
        $sql = "SELECT jl.* " . $fromClause . " " . $whereClause . " " . $groupByClause . " ORDER BY jl.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
// Bind pagination parameters
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
// Bind the rest of the parameters
        foreach ($parameters as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }

        $stmt->execute();
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
// Προσθήκη των τύπων οχημάτων σε κάθε αγγελία
        foreach ($results as &$listing) {
            $listing['vehicle_types'] = $this->getVehicleTypes($listing['id']);
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
    }

    /**
     * Επιστρέφει τις αγγελίες μιας εταιρείας
     */
    public function getCompanyListings($companyId, $active = true, $page = 1, $limit = 10)
    {
        $conditions = ["company_id = :company_id"];
        if ($active !== null) {
            $conditions[] = "is_active = " . ($active ? "1" : "0");
        }

        $whereClause = implode(" AND ", $conditions);
        $offset = ($page - 1) * $limit;
        $countSql = "SELECT COUNT(*) FROM job_listings WHERE $whereClause";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute(['company_id' => $companyId]);
        $totalResults = $countStmt->fetchColumn();
        $sql = "SELECT * FROM job_listings WHERE $whereClause ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':company_id', $companyId);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return [
            'results' => $stmt->fetchAll(\PDO::FETCH_ASSOC),
            'pagination' => [
                'total' => $totalResults,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($totalResults / $limit)
            ]
        ];
    }

    /**
     * Επιστρέφει τις αγγελίες ενός οδηγού
     */
    public function getDriverListings($driverId, $active = true, $page = 1, $limit = 10)
    {
        $conditions = ["driver_id = :driver_id"];
        if ($active !== null) {
            $conditions[] = "is_active = " . ($active ? "1" : "0");
        }

        $whereClause = implode(" AND ", $conditions);
        $offset = ($page - 1) * $limit;
        $countSql = "SELECT COUNT(*) FROM job_listings WHERE $whereClause";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute(['driver_id' => $driverId]);
        $totalResults = $countStmt->fetchColumn();
        $sql = "SELECT * FROM job_listings WHERE $whereClause ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':driver_id', $driverId);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return [
            'results' => $stmt->fetchAll(\PDO::FETCH_ASSOC),
            'pagination' => [
                'total' => $totalResults,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($totalResults / $limit)
            ]
        ];
    }

    /**
     * Προσθέτει ένα tag σε μια αγγελία
     */
    public function addTag($jobListingId, $tagId)
    {
        $sql = "INSERT INTO job_listing_tags (job_listing_id, job_tag_id) VALUES (:job_listing_id, :job_tag_id)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'job_listing_id' => $jobListingId,
            'job_tag_id' => $tagId
        ]);
    }

    /**
     * Αφαιρεί ένα tag από μια αγγελία
     */
    public function removeTag($jobListingId, $tagId)
    {
        $sql = "DELETE FROM job_listing_tags WHERE job_listing_id = :job_listing_id AND job_tag_id = :job_tag_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'job_listing_id' => $jobListingId,
            'job_tag_id' => $tagId
        ]);
    }

    /**
     * Επιστρέφει τα tags μιας αγγελίας
     */
    public function getTagsByJobId($jobListingId)
    {
        $sql = "SELECT jt.* FROM job_tags jt 
                JOIN job_listing_tags jlt ON jt.id = jlt.job_tag_id 
                WHERE jlt.job_listing_id = :job_listing_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['job_listing_id' => $jobListingId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    /**
 * Επιστρέφει στατιστικά στοιχεία για τις αγγελίες
 * (αριθμός ενεργών, συνολικών, προβολών, αιτήσεων κλπ.)
 */
    public function getStatistics($period = 'all')
    {
        $stats = [
        'active_listings' => 0,
        'total_listings' => 0,
        'total_views' => 0,
        'total_applications' => 0,
        'job_offer_listings' => 0,
        'job_search_listings' => 0
        ];
// Προσθήκη συνθήκης περιόδου (π.χ. τελευταίος μήνας, εβδομάδα κλπ.)
        $periodCondition = '';
        if ($period == 'month') {
            $periodCondition = " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        } elseif ($period == 'week') {
            $periodCondition = " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        } elseif ($period == 'day') {
            $periodCondition = " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
        }

        // Λήψη στατιστικών από τη βάση δεδομένων
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                SUM(views_count) as views,
                SUM(applications_count) as applications,
                SUM(CASE WHEN listing_type = 'job_offer' THEN 1 ELSE 0 END) as job_offers,
                SUM(CASE WHEN listing_type = 'job_search' THEN 1 ELSE 0 END) as job_searches
            FROM job_listings
            WHERE 1=1 $periodCondition";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($result) {
            $stats['total_listings'] = $result['total'];
            $stats['active_listings'] = $result['active'];
            $stats['total_views'] = $result['views'];
            $stats['total_applications'] = $result['applications'];
            $stats['job_offer_listings'] = $result['job_offers'];
            $stats['job_search_listings'] = $result['job_searches'];
        }

        return $stats;
    }
/**
 * Αυξάνει τον μετρητή προβολών μιας αγγελίας
 *
 * @param int $id Το ID της αγγελίας
 * @return bool Επιτυχία/αποτυχία
 */
    public function incrementViewsCount($id)
    {
        try {
            $sql = "UPDATE job_listings SET views_count = views_count + 1 WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            return true;
        } catch (PDOException $e) {
            error_log('Error incrementing views count: ' . $e->getMessage());
            return false;
        }
    }
/**
 * Αυξάνει τον μετρητή αιτήσεων μιας αγγελίας
 */
    public function incrementApplicationsCount($id)
    {
        $sql = "UPDATE job_listings SET applications_count = applications_count + 1 WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
/**
 * Αποθήκευση των επιλεγμένων τύπων οχημάτων με βελτιωμένο χειρισμό σφαλμάτων
 *
 * @param int $jobListingId ID της αγγελίας
 * @param array|string $vehicleTypes Πίνακας με τους τύπους οχημάτων ή string με διαχωριστικό κόμμα
 * @return bool Επιτυχία/αποτυχία
 */
    public function updateVehicleTypes($jobListingId, $vehicleTypes)
    {
        \Drivejob\Core\Debug::log("JobListingModel::updateVehicleTypes - Έναρξη με ID: $jobListingId", $vehicleTypes);
        try {
        // Διαγραφή των υπαρχόντων τύπων οχημάτων
            \Drivejob\Core\Debug::log("Διαγραφή των υπαρχόντων τύπων οχημάτων");
            $sql = "DELETE FROM job_listing_vehicle_types WHERE job_listing_id = :job_listing_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['job_listing_id' => $jobListingId]);
        // Αν δεν έχουμε νέους τύπους, επιστρέφουμε επιτυχία
            if (empty($vehicleTypes)) {
                \Drivejob\Core\Debug::log("Δεν υπάρχουν τύποι οχημάτων προς προσθήκη");
                return true;
            }

            // Έλεγχος εάν η μεταβλητή είναι πίνακας
            if (!is_array($vehicleTypes)) {
                \Drivejob\Core\Debug::log("vehicleTypes δεν είναι πίνακας: " . gettype($vehicleTypes));
                $vehicleTypes = explode(',', $vehicleTypes);
// Μετατροπή σε πίνακα αν είναι string
            }

            // Προσθήκη νέων εγγραφών
            $sql = "INSERT INTO job_listing_vehicle_types (job_listing_id, vehicle_type) VALUES (:job_listing_id, :vehicle_type)";
            $stmt = $this->pdo->prepare($sql);
            foreach ($vehicleTypes as $type) {
        // Παράλειψη κενών στοιχείων
                if (empty(trim($type))) {
                    continue;
                }

                \Drivejob\Core\Debug::log("Προσθήκη τύπου οχήματος: $type");
                try {
                    $stmt->bindValue(':job_listing_id', $jobListingId, \PDO::PARAM_INT);
                    $stmt->bindValue(':vehicle_type', $type, \PDO::PARAM_STR);
                    $success = $stmt->execute();
                    \Drivejob\Core\Debug::log("Αποτέλεσμα προσθήκης: " . ($success ? "Επιτυχία" : "Αποτυχία"));
                    // Αν αποτύχει μια εγγραφή, συνεχίζουμε με τις υπόλοιπες
                    if (!$success) {
                        \Drivejob\Core\Debug::log("Αποτυχία προσθήκης τύπου οχήματος: " . $type);
                    }
                } catch (\PDOException $e) {
                    \Drivejob\Core\Debug::log("Σφάλμα PDO κατά την προσθήκη τύπου οχήματος: " . $e->getMessage());
                    // Συνεχίζουμε με τους υπόλοιπους τύπους
                    continue;
                }
            }

            \Drivejob\Core\Debug::log("Επιτυχής ενημέρωση τύπων οχημάτων");
            return true;
        } catch (\Exception $e) {
            \Drivejob\Core\Debug::log("Γενικό σφάλμα: " . $e->getMessage(), $e->getTraceAsString());
        // Επιστρέφουμε false αντί να πετάμε εξαίρεση για πιο ομαλό χειρισμό
            return false;
        }
    }
/**
 * Ανακτά μια αγγελία από τη βάση δεδομένων με βάση το ID
 * με πλήρη πληροφορίες οχημάτων
 *
 * @param int $id Το ID της αγγελίας
 * @return array|false Τα δεδομένα της αγγελίας ή false αν δεν βρέθηκε
 */
    public function getByIdWithDetails($id)
    {
        try {
// Ανάκτηση βασικών πληροφοριών αγγελίας
            $listing = $this->getById($id);
            if (!$listing) {
                return false;
            }

            // Προσθήκη των τύπων οχημάτων
            $listing['vehicle_types'] = $this->getVehicleTypes($id);
// Αν είναι αγγελία οδηγού, πρόσθεσε επιπλέον πληροφορίες
            if ($listing['listing_type'] === 'job_search' && !empty($listing['driver_id'])) {
// Θα συμπληρωθεί με επιπλέον πληροφορίες οδηγού αν χρειάζεται
            }

            return $listing;
        } catch (\PDOException $e) {
            error_log('Error getting job listing with details: ' . $e->getMessage());
            return false;
        }
    }
/**
 * Διαγράφει μια αγγελία από τη βάση δεδομένων
 *
 * @param int $id Το ID της αγγελίας προς διαγραφή
 * @return bool Επιτυχία/αποτυχία
 */
    public function delete($id)
    {
        try {
            $sql = "DELETE FROM job_listings WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log('Error deleting job listing: ' . $e->getMessage());
            throw $e;
        }
    }
    public function addVehicleType($jobListingId, $vehicleType)
    {
        $sql = "INSERT INTO job_listing_vehicle_types (job_listing_id, vehicle_type) VALUES (:job_listing_id, :vehicle_type)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
        'job_listing_id' => $jobListingId,
        'vehicle_type' => $vehicleType
        ]);
    }
}

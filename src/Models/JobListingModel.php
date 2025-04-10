<?php

namespace Drivejob\Models;

class JobListingModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Δημιουργεί μια νέα αγγελία
     */
    public function create($data) {
        $sql = "INSERT INTO job_listings (
            title, company_id, driver_id, listing_type, job_type, 
            vehicle_type, required_license, description, salary_min, 
            salary_max, salary_type, location, latitude, longitude, 
            radius, remote_possible, experience_years, adr_certificate, 
            operator_license, required_training, benefits, contact_email, 
            contact_phone, expires_at
        ) VALUES (
            :title, :company_id, :driver_id, :listing_type, :job_type,
            :vehicle_type, :required_license, :description, :salary_min,
            :salary_max, :salary_type, :location, :latitude, :longitude,
            :radius, :remote_possible, :experience_years, :adr_certificate,
            :operator_license, :required_training, :benefits, :contact_email,
            :contact_phone, :expires_at
        )";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $this->pdo->lastInsertId();
    }

    /**
     * Ενημερώνει μια αγγελία
     */
    public function update($id, $data) {
        $sql = "UPDATE job_listings SET 
            title = :title,
            job_type = :job_type,
            vehicle_type = :vehicle_type,
            required_license = :required_license,
            description = :description,
            salary_min = :salary_min,
            salary_max = :salary_max,
            salary_type = :salary_type,
            location = :location,
            latitude = :latitude,
            longitude = :longitude,
            radius = :radius,
            remote_possible = :remote_possible,
            experience_years = :experience_years,
            adr_certificate = :adr_certificate,
            operator_license = :operator_license,
            required_training = :required_training,
            benefits = :benefits,
            contact_email = :contact_email,
            contact_phone = :contact_phone,
            is_active = :is_active,
            expires_at = :expires_at,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = :id";

        $data['id'] = $id;
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * Επιστρέφει μια αγγελία με βάση το ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM job_listings WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Διαγράφει μια αγγελία
     */
    public function delete($id) {
        $sql = "DELETE FROM job_listings WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Επιστρέφει τις ενεργές αγγελίες με βάση κριτήρια
     */
    public function getActiveListings($params = [], $page = 1, $limit = 10) {
        $conditions = ["is_active = 1"];
        $parameters = [];
        
        // Filter by listing type
        if (isset($params['listing_type'])) {
            $conditions[] = "listing_type = :listing_type";
            $parameters['listing_type'] = $params['listing_type'];
        }
        
        // Filter by job type
        if (isset($params['job_type'])) {
            $conditions[] = "job_type = :job_type";
            $parameters['job_type'] = $params['job_type'];
        }
        
        // Filter by vehicle type
        if (isset($params['vehicle_type'])) {
            $conditions[] = "vehicle_type = :vehicle_type";
            $parameters['vehicle_type'] = $params['vehicle_type'];
        }
        
        // Filter by location (search within radius)
        if (isset($params['latitude']) && isset($params['longitude']) && isset($params['search_radius'])) {
            // Haversine formula for finding distance in km
            $conditions[] = "(
                6371 * acos(
                    cos(radians(:latitude)) * 
                    cos(radians(latitude)) * 
                    cos(radians(longitude) - radians(:longitude)) + 
                    sin(radians(:latitude)) * 
                    sin(radians(latitude))
                )
            ) <= :search_radius";
            $parameters['latitude'] = $params['latitude'];
            $parameters['longitude'] = $params['longitude'];
            $parameters['search_radius'] = $params['search_radius'];
        }
        
        // Filter by minimum experience years
        if (isset($params['min_experience'])) {
            $conditions[] = "experience_years >= :min_experience";
            $parameters['min_experience'] = $params['min_experience'];
        }
        
        // Filter by special requirements
        if (isset($params['adr_certificate']) && $params['adr_certificate']) {
            $conditions[] = "adr_certificate = 1";
        }
        
        if (isset($params['operator_license']) && $params['operator_license']) {
            $conditions[] = "operator_license = 1";
        }

        // Build the WHERE clause
        $whereClause = !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
        
        // Calculate offset for pagination
        $offset = ($page - 1) * $limit;
        
        // Count total results for pagination
        $countSql = "SELECT COUNT(*) FROM job_listings $whereClause";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($parameters);
        $totalResults = $countStmt->fetchColumn();
        
        // Execute the main query with pagination
        $sql = "SELECT * FROM job_listings $whereClause ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
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
    public function getCompanyListings($companyId, $active = true, $page = 1, $limit = 10) {
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
    public function getDriverListings($driverId, $active = true, $page = 1, $limit = 10) {
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
    public function addTag($jobListingId, $tagId) {
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
    public function removeTag($jobListingId, $tagId) {
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
    public function getTagsByJobId($jobListingId) {
        $sql = "SELECT jt.* FROM job_tags jt 
                JOIN job_listing_tags jlt ON jt.id = jlt.job_tag_id 
                WHERE jlt.job_listing_id = :job_listing_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['job_listing_id' => $jobListingId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
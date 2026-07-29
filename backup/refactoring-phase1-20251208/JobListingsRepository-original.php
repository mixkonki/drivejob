<?php

namespace Drivejob\Repositories;

use PDO;

class JobListingsRepository
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get job listing by ID
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT j.*, c.company_name, c.city as company_city, c.email as company_email
            FROM job_listings j
            JOIN companies c ON j.company_id = c.id
            WHERE j.id = ?
        ");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Get all active job listings
     */
    public function getActiveListings(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("
            SELECT j.*, c.company_name, c.city as company_city
            FROM job_listings j
            JOIN companies c ON j.company_id = c.id
            WHERE j.status = 'active'
            ORDER BY j.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get job listings by company
     */
    public function getByCompanyId(int $companyId, string $status = null): array
    {
        $sql = "
            SELECT j.*
            FROM job_listings j
            WHERE j.company_id = ?
        ";

        $params = [$companyId];

        if ($status) {
            $sql .= " AND j.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY j.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Search job listings
     */
    public function search(array $criteria): array
    {
        $sql = "
            SELECT j.*, c.company_name, c.city as company_city
            FROM job_listings j
            JOIN companies c ON j.company_id = c.id
            WHERE j.status = 'active'
        ";

        $params = [];

        // Location filter
        if (!empty($criteria['location'])) {
            $sql .= " AND (j.location LIKE ? OR c.city LIKE ?)";
            $locationParam = '%' . $criteria['location'] . '%';
            $params[] = $locationParam;
            $params[] = $locationParam;
        }

        // License type filter
        if (!empty($criteria['license_type'])) {
            $sql .= " AND j.required_license = ?";
            $params[] = $criteria['license_type'];
        }

        // Vehicle type filter
        if (!empty($criteria['vehicle_type'])) {
            $sql .= " AND j.vehicle_type = ?";
            $params[] = $criteria['vehicle_type'];
        }

        // Employment type filter
        if (!empty($criteria['employment_type'])) {
            $sql .= " AND j.employment_type = ?";
            $params[] = $criteria['employment_type'];
        }

        // Salary range filter
        if (!empty($criteria['min_salary'])) {
            $sql .= " AND j.salary_max >= ?";
            $params[] = $criteria['min_salary'];
        }

        if (!empty($criteria['max_salary'])) {
            $sql .= " AND j.salary_min <= ?";
            $params[] = $criteria['max_salary'];
        }

        // Experience filter
        if (isset($criteria['max_experience'])) {
            $sql .= " AND j.min_experience <= ?";
            $params[] = $criteria['max_experience'];
        }

        $sql .= " ORDER BY j.created_at DESC";

        // Limit
        if (!empty($criteria['limit'])) {
            $sql .= " LIMIT " . intval($criteria['limit']);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create new job listing
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO job_listings (
                company_id, title, description, requirements, benefits,
                location, required_license, vehicle_type, employment_type,
                salary_min, salary_max, min_experience, route_type,
                cargo_type, is_urgent, status, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            )
        ");

        $stmt->execute([
            $data['company_id'],
            $data['title'],
            $data['description'],
            $data['requirements'] ?? '',
            $data['benefits'] ?? '',
            $data['location'],
            $data['required_license'],
            $data['vehicle_type'] ?? null,
            $data['employment_type'] ?? 'full_time',
            $data['salary_min'] ?? null,
            $data['salary_max'] ?? null,
            $data['min_experience'] ?? 0,
            $data['route_type'] ?? 'local',
            $data['cargo_type'] ?? null,
            $data['is_urgent'] ?? 0,
            $data['status'] ?? 'active'
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Update job listing
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            if (in_array($key, [
                'title',
                'description',
                'requirements',
                'benefits',
                'location',
                'required_license',
                'vehicle_type',
                'employment_type',
                'salary_min',
                'salary_max',
                'min_experience',
                'route_type',
                'cargo_type',
                'is_urgent',
                'status'
            ])) {
                $fields[] = "$key = ?";
                $params[] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;

        $stmt = $this->pdo->prepare("
            UPDATE job_listings 
            SET " . implode(', ', $fields) . ", updated_at = NOW()
            WHERE id = ?
        ");

        return $stmt->execute($params);
    }

    /**
     * Delete job listing
     */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM job_listings WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Update job status
     */
    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE job_listings 
            SET status = ?, updated_at = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([$status, $id]);
    }

    /**
     * Get job statistics
     */
    public function getStatistics(int $companyId = null): array
    {
        $sql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
                SUM(CASE WHEN is_urgent = 1 THEN 1 ELSE 0 END) as urgent
            FROM job_listings
        ";

        $params = [];
        if ($companyId) {
            $sql .= " WHERE company_id = ?";
            $params[] = $companyId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get popular job locations
     */
    public function getPopularLocations(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare("
            SELECT location, COUNT(*) as count
            FROM job_listings
            WHERE status = 'active'
            GROUP BY location
            ORDER BY count DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get required licenses distribution
     */
    public function getLicenseDistribution(): array
    {
        $stmt = $this->pdo->query("
            SELECT required_license, COUNT(*) as count
            FROM job_listings
            WHERE status = 'active'
            GROUP BY required_license
            ORDER BY count DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

<?php

namespace Drivejob\Services\AI;

use Drivejob\Core\Database;

class FeatureExtractor
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Extract features for a driver
     */
    public function extractDriverFeatures(int $driverId): array
    {
        $features = [];

        // Get driver basic info
        $stmt = $this->pdo->prepare("
            SELECT d.*
            FROM drivers d 
            WHERE d.id = ?
        ");
        $stmt->execute([$driverId]);
        $driver = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$driver) {
            return [];
        }

        // Basic features
        $features['years_experience'] = $driver['years_experience'] ?? 0;
        $features['age'] = $driver['age'] ?? 0;
        $features['available_immediately'] = $driver['is_available'] == 1;
        $features['preferred_schedule'] = $driver['preferred_schedule'] ?? 'any';

        // Get licenses
        $stmt = $this->pdo->prepare("
            SELECT license_type 
            FROM driver_licenses 
            WHERE driver_id = ? AND is_active = 1
        ");
        $stmt->execute([$driverId]);
        $features['licenses'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        // Get certifications
        $stmt = $this->pdo->prepare("
            SELECT certification_type 
            FROM driver_certifications 
            WHERE driver_id = ? AND status = 'active'
        ");
        $stmt->execute([$driverId]);
        $features['certifications'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        // Get vehicle experience
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT vehicle_type 
            FROM driver_vehicle_experience 
            WHERE driver_id = ?
        ");
        $stmt->execute([$driverId]);
        $features['vehicle_types'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        // Get location (simplified - in production would use proper geocoding)
        $features['location'] = [
            'lat' => $driver['latitude'] ?? 37.9838,  // Default Athens
            'lng' => $driver['longitude'] ?? 23.7275,
            'city' => $driver['city'] ?? '',
            'region' => $driver['region'] ?? ''
        ];

        // Get skills and ratings - simplified for now
        $features['avg_rating'] = 0;
        $features['review_count'] = 0;

        // Check if company_reviews table exists and has the right structure
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as review_count
                FROM company_reviews
                WHERE driver_id = ?
            ");
            $stmt->execute([$driverId]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            $features['review_count'] = intval($result['review_count'] ?? 0);
        } catch (\Exception $e) {
            // Table might not exist or have different structure
        }

        // Get availability preferences
        $features['max_distance'] = $driver['max_distance'] ?? 100;
        $features['min_salary'] = $driver['min_salary'] ?? 0;

        return $features;
    }

    /**
     * Extract features for a job listing
     */
    public function extractJobFeatures(int $jobId): array
    {
        $features = [];

        // Get job basic info
        $stmt = $this->pdo->prepare("
            SELECT j.*, c.company_name, c.city as company_city
            FROM job_listings j
            JOIN companies c ON j.company_id = c.id
            WHERE j.id = ?
        ");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$job) {
            return [];
        }

        // Basic features
        $features['required_license'] = $job['required_license'] ?? '';
        $features['min_experience'] = intval($job['min_experience'] ?? 0);
        $features['vehicle_type'] = $job['vehicle_type'] ?? '';
        $features['schedule_type'] = $job['employment_type'] ?? 'full_time';
        $features['urgent'] = $job['is_urgent'] == 1;

        // Parse required certifications from description or dedicated field
        $features['required_certifications'] = $this->extractCertifications($job['requirements'] ?? '');

        // Location features
        $features['location'] = [
            'lat' => $job['latitude'] ?? 37.9838,
            'lng' => $job['longitude'] ?? 23.7275,
            'city' => $job['location'] ?? $job['company_city'] ?? '',
            'region' => $job['region'] ?? ''
        ];

        // Job characteristics
        $features['salary_range'] = [
            'min' => floatval($job['salary_min'] ?? 0),
            'max' => floatval($job['salary_max'] ?? 0)
        ];

        $features['benefits'] = $this->extractBenefits($job['benefits'] ?? '');
        $features['route_type'] = $job['route_type'] ?? 'local'; // local, national, international
        $features['cargo_type'] = $job['cargo_type'] ?? 'general';

        // Company preferences
        $features['company_size'] = $this->getCompanySize($job['company_id']);
        $features['company_rating'] = $this->getCompanyRating($job['company_id']);

        return $features;
    }

    /**
     * Extract certifications from text
     */
    private function extractCertifications(string $text): array
    {
        $certifications = [];
        $certPatterns = [
            'ADR' => '/\bADR\b/i',
            'PEI' => '/\bΠΕΙ\b|\bPEI\b/i',
            'ΚΤΕΟ' => '/\bΚΤΕΟ\b/i',
            'Ταχογράφος' => '/\bταχογράφ/i',
            'Γερανός' => '/\bγεραν/i',
            'Ψυγείο' => '/\bψυγεί/i'
        ];

        foreach ($certPatterns as $cert => $pattern) {
            if (preg_match($pattern, $text)) {
                $certifications[] = $cert;
            }
        }

        return $certifications;
    }

    /**
     * Extract benefits from text
     */
    private function extractBenefits(string $text): array
    {
        $benefits = [];
        $benefitPatterns = [
            'insurance' => '/ασφάλ/i',
            'accommodation' => '/διαμονή|στέγαση/i',
            'meals' => '/γεύματα|φαγητό/i',
            'bonus' => '/μπόνους|bonus/i',
            'training' => '/εκπαίδευση|training/i'
        ];

        foreach ($benefitPatterns as $benefit => $pattern) {
            if (preg_match($pattern, $text)) {
                $benefits[] = $benefit;
            }
        }

        return $benefits;
    }

    /**
     * Get company size category
     */
    private function getCompanySize(int $companyId): string
    {
        $stmt = $this->pdo->prepare("
            SELECT fleet_size 
            FROM companies 
            WHERE id = ?
        ");
        $stmt->execute([$companyId]);
        $size = $stmt->fetchColumn();

        if ($size <= 5) return 'small';
        if ($size <= 20) return 'medium';
        return 'large';
    }

    /**
     * Get company average rating
     */
    private function getCompanyRating(int $companyId): float
    {
        // Simplified for now - return default rating
        // In production, this would calculate from actual reviews
        return 0.0;
    }
}

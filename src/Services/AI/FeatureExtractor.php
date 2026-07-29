<?php

namespace Drivejob\Services\AI;

use PDO;
use Drivejob\Core\Database;
use Drivejob\Core\Logger;

/**
 * Feature Extractor για AI Matching
 * Εξάγει χαρακτηριστικά από drivers και job listings για AI processing
 */
class FeatureExtractor
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance()->getConnection();
    }

    /**
     * Εξάγει χαρακτηριστικά οδηγού για AI matching
     */
    public function extractDriverFeatures(int $driverId): array
    {
        try {
            // Βασικά στοιχεία οδηγού
            $stmt = $this->pdo->prepare("
                SELECT * FROM drivers WHERE id = ?
            ");
            $stmt->execute([$driverId]);
            $driver = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$driver) {
                return [];
            }

            // Άδειες οδήγησης - έλεγχος αν υπάρχει ο πίνακας
            $licenses = [];
            try {
                $stmt = $this->pdo->prepare("
                    SELECT license_type, issue_date, expiry_date
                    FROM driver_licenses
                    WHERE driver_id = ?
                ");
                $stmt->execute([$driverId]);
                $licenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Exception $e) {
                // Fallback: χρήση των βασικών πεδίων από τον πίνακα drivers
                if ($driver['driving_license'] ?? false) {
                    $licenses = [['license_type' => 'B']]; // Default license
                }
            }

            // Πιστοποιήσεις - έλεγχος αν υπάρχει ο πίνακας
            $certifications = [];
            try {
                $stmt = $this->pdo->prepare("
                    SELECT certification_type, certification_name, issue_date, expiry_date
                    FROM driver_certifications
                    WHERE driver_id = ?
                ");
                $stmt->execute([$driverId]);
                $certifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Exception $e) {
                // Fallback: χρήση των βασικών πεδίων
                if ($driver['adr_certificate'] ?? false) {
                    $certifications[] = ['certification_type' => 'ADR'];
                }
                if ($driver['operator_license'] ?? false) {
                    $certifications[] = ['certification_type' => 'Operator'];
                }
            }

            // Εμπειρία με οχήματα - έλεγχος αν υπάρχει ο πίνακας
            $vehicleExperience = [];
            try {
                $stmt = $this->pdo->prepare("
                    SELECT vehicle_category, vehicle_type, years
                    FROM driver_vehicle_experience
                    WHERE driver_id = ?
                ");
                $stmt->execute([$driverId]);
                $vehicleExperience = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Exception $e) {
                // Fallback: χρήση του preferred_vehicle_type
                if (!empty($driver['preferred_vehicle_type'])) {
                    $vehicleExperience = [['vehicle_type' => $driver['preferred_vehicle_type']]];
                }
            }

            // Δημιουργία feature vector με safe array access
            $features = [
                'driver_id' => $driverId,
                'years_experience' => intval($driver['experience_years'] ?? $driver['years_experience'] ?? 0),
                'available_immediately' => boolval($driver['available_for_work'] ?? false),
                'preferred_schedule' => $this->determinePreferredSchedule($driver),
                'city' => $driver['city'] ?? '',
                'country' => $driver['country'] ?? '',
                'location' => [
                    'city' => $driver['city'] ?? '',
                    'country' => $driver['country'] ?? '',
                    'lat' => $this->getLocationCoordinates($driver['city'] ?? '')['lat'] ?? 0,
                    'lng' => $this->getLocationCoordinates($driver['city'] ?? '')['lng'] ?? 0
                ],
                'licenses' => array_column($licenses, 'license_type'),
                'certifications' => array_column($certifications, 'certification_type'),
                'vehicle_types' => array_column($vehicleExperience, 'vehicle_type'),
                'skills' => $this->extractDriverSkills($driver),
                'rating' => floatval($driver['rating'] ?? 0),
                'completed_jobs' => intval($driver['completed_jobs'] ?? 0)
            ];

            return $features;
        } catch (\Exception $e) {
            Logger::error('Error extracting driver features: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Εξάγει χαρακτηριστικά job listing για AI matching
     */
    public function extractJobFeatures(int $jobId): array
    {
        try {
            // Βασικά στοιχεία αγγελίας
            $stmt = $this->pdo->prepare("
                SELECT j.*, c.company_name, c.city as company_city, c.country as company_country
                FROM job_listings j
                JOIN companies c ON j.company_id = c.id
                WHERE j.id = ?
            ");
            $stmt->execute([$jobId]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$job) {
                return [];
            }

            // Δημιουργία feature vector
            $features = [
                'job_id' => $jobId,
                'company_id' => $job['company_id'],
                'vehicle_type' => $job['vehicle_type'] ?? '',
                'required_license' => $job['license_required'] ?? '',
                'required_certifications' => $this->parseRequiredCertifications($job),
                'min_experience' => $job['experience_years'] ?? 0,
                'schedule_type' => $job['job_type'] ?? 'full_time',
                'urgent' => $job['is_urgent'] ?? false,
                'location' => [
                    'city' => $job['location'] ?? $job['company_city'] ?? '',
                    'country' => $job['company_country'] ?? '',
                    'lat' => $this->getLocationCoordinates($job['location'] ?? $job['company_city'] ?? '')['lat'] ?? 0,
                    'lng' => $this->getLocationCoordinates($job['location'] ?? $job['company_city'] ?? '')['lng'] ?? 0
                ],
                'salary_range' => [
                    'min' => $job['salary_min'] ?? 0,
                    'max' => $job['salary_max'] ?? 0
                ],
                'required_skills' => $this->parseRequiredSkills($job),
                'company_rating' => $this->getCompanyRating($job['company_id']),
                'job_category' => $this->determineJobCategory($job)
            ];

            return $features;
        } catch (\Exception $e) {
            Logger::error('Error extracting job features: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Καθορίζει το προτιμώμενο ωράριο του οδηγού
     */
    private function determinePreferredSchedule(array $driver): string
    {
        // Λογική για καθορισμό προτιμώμενου ωραρίου
        if (isset($driver['preferred_schedule'])) {
            return $driver['preferred_schedule'];
        }

        // Default logic based on availability
        if ($driver['available_for_work'] ?? false) {
            return 'any';
        }

        return 'full_time';
    }

    /**
     * Εξάγει δεξιότητες οδηγού
     */
    private function extractDriverSkills(array $driver): array
    {
        $skills = [];

        // Βασικές δεξιότητες από τα πεδία του οδηγού
        if ($driver['pei_certificate'] ?? false) {
            $skills[] = 'PEI';
        }
        if ($driver['adr_certificate'] ?? false) {
            $skills[] = 'ADR';
        }
        if ($driver['tachograph_card'] ?? false) {
            $skills[] = 'Tachograph';
        }
        if ($driver['operator_license'] ?? false) {
            $skills[] = 'Operator License';
        }

        // Πρόσθετες δεξιότητες από εμπειρία
        $experience = $driver['years_experience'] ?? 0;
        if ($experience >= 5) {
            $skills[] = 'Experienced Driver';
        }
        if ($experience >= 10) {
            $skills[] = 'Senior Driver';
        }

        return $skills;
    }

    /**
     * Αναλύει τις απαιτούμενες πιστοποιήσεις από την αγγελία
     */
    private function parseRequiredCertifications(array $job): array
    {
        $certifications = [];

        if ($job['pei_required'] ?? false) {
            $certifications[] = 'PEI';
        }
        if ($job['adr_required'] ?? false) {
            $certifications[] = 'ADR';
        }
        if ($job['tachograph_required'] ?? false) {
            $certifications[] = 'Tachograph';
        }
        if ($job['operator_license_required'] ?? false) {
            $certifications[] = 'Operator License';
        }

        return $certifications;
    }

    /**
     * Αναλύει τις απαιτούμενες δεξιότητες από την αγγελία
     */
    private function parseRequiredSkills(array $job): array
    {
        $skills = [];

        // Από το requirements field
        if (!empty($job['requirements'])) {
            $requirements = strtolower($job['requirements']);

            if (strpos($requirements, 'εμπειρία') !== false || strpos($requirements, 'experience') !== false) {
                $skills[] = 'Experience Required';
            }
            if (strpos($requirements, 'αγγλικά') !== false || strpos($requirements, 'english') !== false) {
                $skills[] = 'English';
            }
            if (strpos($requirements, 'γερμανικά') !== false || strpos($requirements, 'german') !== false) {
                $skills[] = 'German';
            }
        }

        return $skills;
    }

    /**
     * Λαμβάνει συντεταγμένες τοποθεσίας (mock implementation)
     */
    private function getLocationCoordinates(string $location): array
    {
        // Mock implementation - σε πραγματική εφαρμογή θα χρησιμοποιούσαμε geocoding API
        $coordinates = [
            'Αθήνα' => ['lat' => 37.9838, 'lng' => 23.7275],
            'Θεσσαλονίκη' => ['lat' => 40.6401, 'lng' => 22.9444],
            'Πάτρα' => ['lat' => 38.2466, 'lng' => 21.7346],
            'Ηράκλειο' => ['lat' => 35.3387, 'lng' => 25.1442],
            'Athens' => ['lat' => 37.9838, 'lng' => 23.7275],
            'Thessaloniki' => ['lat' => 40.6401, 'lng' => 22.9444]
        ];

        return $coordinates[$location] ?? ['lat' => 0, 'lng' => 0];
    }

    /**
     * Λαμβάνει rating εταιρείας
     */
    private function getCompanyRating(int $companyId): float
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT AVG(rating) as avg_rating
                FROM company_reviews
                WHERE company_id = ?
            ");
            $stmt->execute([$companyId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['avg_rating'] ?? 0.0;
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /**
     * Καθορίζει την κατηγορία εργασίας
     */
    private function determineJobCategory(array $job): string
    {
        $vehicleType = $job['vehicle_type'] ?? '';

        switch ($vehicleType) {
            case 'truck':
                return 'Long Distance Transport';
            case 'van':
                return 'Local Delivery';
            case 'bus':
                return 'Passenger Transport';
            case 'car':
                return 'Personal Transport';
            default:
                return 'General Transport';
        }
    }
}

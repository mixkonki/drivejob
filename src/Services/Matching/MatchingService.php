<?php

namespace Drivejob\Services\Matching;

use Drivejob\Core\Database;
use Drivejob\Core\Exceptions\DatabaseException;
use Drivejob\Repositories\JobListingRepository;
use Drivejob\Repositories\DriversRepository;
use Drivejob\Repositories\CompaniesRepository;
use Drivejob\Repositories\DriverLicenseRepository;
use Drivejob\Repositories\DriverSkillsRepository;
use Drivejob\Repositories\DriverRatingRepository;

/**
 * Υπηρεσία ταιριάσματος αγγελιών
 */
class MatchingService implements MatchingServiceInterface
{
    /**
     * @var Database Η σύνδεση με τη βάση δεδομένων
     */
    private $db;

    /**
     * @var JobListingRepository Το repository για τις αγγελίες
     */
    private $jobListingRepository;

    /**
     * @var DriversRepository Το repository για τους οδηγούς
     */
    private $driversRepository;

    /**
     * @var CompaniesRepository Το repository για τις εταιρείες
     */
    private $companiesRepository;

    /**
     * @var DriverLicenseRepository Το repository για τις άδειες οδήγησης
     */
    private $driverLicenseRepository;

    /**
     * @var DriverSkillsRepository Το repository για τις δεξιότητες οδηγών
     */
    private $driverSkillsRepository;

    /**
     * @var DriverRatingRepository Το repository για τις αξιολογήσεις οδηγών
     */
    private $driverRatingRepository;

    /**
     * @var array Οι προκαθορισμένοι συντελεστές βαρύτητας
     */
    private $defaultWeights = [
        'location' => 0.8,
        'job_type' => 0.7,
        'vehicle_type' => 0.9,
        'license' => 1.0,
        'experience' => 0.6,
        'skills' => 0.5,
        'schedule' => 0.4,
        'rating' => 0.3
    ];

    /**
     * Constructor
     *
     * @param Database|null $db Η σύνδεση με τη βάση δεδομένων
     * @param JobListingRepository|null $jobListingRepository Το repository για τις αγγελίες
     * @param DriversRepository|null $driversRepository Το repository για τους οδηγούς
     * @param CompaniesRepository|null $companiesRepository Το repository για τις εταιρείες
     * @param DriverLicenseRepository|null $driverLicenseRepository Το repository για τις άδειες οδήγησης
     * @param DriverSkillsRepository|null $driverSkillsRepository Το repository για τις δεξιότητες οδηγών
     * @param DriverRatingRepository|null $driverRatingRepository Το repository για τις αξιολογήσεις οδηγών
     */
    public function __construct(
        Database $db = null,
        JobListingRepository $jobListingRepository = null,
        DriversRepository $driversRepository = null,
        CompaniesRepository $companiesRepository = null,
        DriverLicenseRepository $driverLicenseRepository = null,
        DriverSkillsRepository $driverSkillsRepository = null,
        DriverRatingRepository $driverRatingRepository = null
    ) {
        $this->db = $db ?? new Database();
        $this->jobListingRepository = $jobListingRepository ?? new JobListingRepository($this->db);
        $this->driversRepository = $driversRepository ?? new DriversRepository($this->db);
        $this->companiesRepository = $companiesRepository ?? new CompaniesRepository($this->db);
        $this->driverLicenseRepository = $driverLicenseRepository ?? new DriverLicenseRepository($this->db);
        $this->driverSkillsRepository = $driverSkillsRepository ?? new DriverSkillsRepository($this->db);
        $this->driverRatingRepository = $driverRatingRepository ?? new DriverRatingRepository($this->db);
    }

    /**
     * {@inheritdoc}
     */
    public function findMatchesForDriver(int $driverId, array $criteria = [], int $page = 1, int $limit = 10): array
    {
        try {
            // Λήψη των στοιχείων του οδηγού
            $driver = $this->driversRepository->find($driverId);
            if (!$driver) {
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

            // Λήψη των αδειών οδήγησης του οδηγού
            $driverLicenses = $this->driverLicenseRepository->findByDriver($driverId);
            $licenseTypes = [];
            $hasPEI = false;
            foreach ($driverLicenses as $license) {
                $licenseTypes[] = $license['license_type'];
                if (isset($license['has_pei']) && $license['has_pei']) {
                    $hasPEI = true;
                }
            }

            // Λήψη των δεξιοτήτων του οδηγού
            $driverSkills = $this->driverSkillsRepository->findByDriver($driverId);
            $skills = [];
            foreach ($driverSkills as $skill) {
                $skills[] = $skill['skill_name'];
            }

            // Λήψη της αξιολόγησης του οδηγού
            $driverRating = $this->driverRatingRepository->getAverageRating($driverId);

            // Λήψη των προτιμήσεων ταιριάσματος του οδηγού
            $preferences = $this->getMatchPreferences($driverId, 'driver');

            // Δημιουργία του ερωτήματος για τις αγγελίες
            $query = "SELECT j.*, c.company_name, 0 as match_score
                      FROM job_listings j
                      LEFT JOIN companies c ON j.company_id = c.id
                      WHERE j.is_active = 1 AND (j.expires_at IS NULL OR j.expires_at > NOW())
                      AND j.listing_type = 'job_offer'";

            $params = [];
            $conditions = [];

            // Προσθήκη των κριτηρίων αναζήτησης
            if (isset($criteria['location']) && $criteria['location']) {
                $conditions[] = "j.location LIKE :location";
                $params['location'] = '%' . $criteria['location'] . '%';
            } else if (isset($driver['city']) && $driver['city']) {
                $conditions[] = "j.location LIKE :location";
                $params['location'] = '%' . $driver['city'] . '%';
            }

            if (isset($criteria['job_type']) && $criteria['job_type']) {
                $conditions[] = "j.job_type = :job_type";
                $params['job_type'] = $criteria['job_type'];
            }

            if (isset($criteria['vehicle_type']) && $criteria['vehicle_type']) {
                $conditions[] = "j.vehicle_type = :vehicle_type";
                $params['vehicle_type'] = $criteria['vehicle_type'];
            }

            // Προσθήκη των συνθηκών στο ερώτημα
            if (!empty($conditions)) {
                $query .= " AND " . implode(" AND ", $conditions);
            }

            // Εκτέλεση του ερωτήματος
            $results = $this->db->query($query, $params)->fetchAll(\PDO::FETCH_ASSOC);

            // Υπολογισμός του σκορ ταιριάσματος για κάθε αγγελία
            foreach ($results as &$result) {
                $result['match_score'] = $this->calculateMatchScore($result, $driver, $driverLicenses, $driverSkills, $driverRating, $preferences);
            }

            // Ταξινόμηση των αποτελεσμάτων με βάση το σκορ ταιριάσματος
            usort($results, function ($a, $b) {
                return $b['match_score'] <=> $a['match_score'];
            });

            // Υπολογισμός της σελιδοποίησης
            $totalResults = count($results);
            $offset = ($page - 1) * $limit;
            $results = array_slice($results, $offset, $limit);

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
            throw new DatabaseException("Σφάλμα κατά την αναζήτηση ταιριασμάτων: " . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findMatchesForJobListing(int $jobListingId, array $criteria = [], int $page = 1, int $limit = 10): array
    {
        try {
            // Λήψη των στοιχείων της αγγελίας
            $jobListing = $this->jobListingRepository->find($jobListingId);
            if (!$jobListing) {
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

            // Λήψη των στοιχείων της εταιρείας
            $company = $this->companiesRepository->find($jobListing['company_id']);

            // Λήψη των προτιμήσεων ταιριάσματος της εταιρείας
            $preferences = $this->getMatchPreferences($jobListing['company_id'], 'company');

            // Δημιουργία του ερωτήματος για τους οδηγούς
            $query = "SELECT d.*, 0 as match_score
                      FROM drivers d
                      WHERE d.is_active = 1 AND d.is_available = 1";

            $params = [];
            $conditions = [];

            // Προσθήκη των κριτηρίων αναζήτησης
            if (isset($criteria['location']) && $criteria['location']) {
                $conditions[] = "d.city LIKE :location";
                $params['location'] = '%' . $criteria['location'] . '%';
            } else if (isset($jobListing['location']) && $jobListing['location']) {
                $conditions[] = "d.city LIKE :location";
                $params['location'] = '%' . $jobListing['location'] . '%';
            }

            // Προσθήκη των συνθηκών στο ερώτημα
            if (!empty($conditions)) {
                $query .= " AND " . implode(" AND ", $conditions);
            }

            // Εκτέλεση του ερωτήματος
            $results = $this->db->query($query, $params)->fetchAll(\PDO::FETCH_ASSOC);

            // Υπολογισμός του σκορ ταιριάσματος για κάθε οδηγό
            foreach ($results as &$result) {
                // Λήψη των αδειών οδήγησης του οδηγού
                $driverLicenses = $this->driverLicenseRepository->findByDriver($result['id']);

                // Λήψη των δεξιοτήτων του οδηγού
                $driverSkills = $this->driverSkillsRepository->findByDriver($result['id']);

                // Λήψη της αξιολόγησης του οδηγού
                $driverRating = $this->driverRatingRepository->getAverageRating($result['id']);

                $result['match_score'] = $this->calculateMatchScoreForDriver($jobListing, $result, $driverLicenses, $driverSkills, $driverRating, $preferences);
            }

            // Ταξινόμηση των αποτελεσμάτων με βάση το σκορ ταιριάσματος
            usort($results, function ($a, $b) {
                return $b['match_score'] <=> $a['match_score'];
            });

            // Υπολογισμός της σελιδοποίησης
            $totalResults = count($results);
            $offset = ($page - 1) * $limit;
            $results = array_slice($results, $offset, $limit);

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
            throw new DatabaseException("Σφάλμα κατά την αναζήτηση ταιριασμάτων: " . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findMatchesForCompany(int $companyId, array $criteria = [], int $page = 1, int $limit = 10): array
    {
        try {
            // Λήψη των στοιχείων της εταιρείας
            $company = $this->companiesRepository->find($companyId);
            if (!$company) {
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

            // Λήψη των αγγελιών της εταιρείας
            $jobListings = $this->jobListingRepository->getCompanyListings($companyId, true)['results'];
            if (empty($jobListings)) {
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

            // Λήψη των προτιμήσεων ταιριάσματος της εταιρείας
            $preferences = $this->getMatchPreferences($companyId, 'company');

            // Δημιουργία του ερωτήματος για τους οδηγούς
            $query = "SELECT d.*, 0 as match_score
                      FROM drivers d
                      WHERE d.is_active = 1 AND d.is_available = 1";

            $params = [];
            $conditions = [];

            // Προσθήκη των κριτηρίων αναζήτησης
            if (isset($criteria['location']) && $criteria['location']) {
                $conditions[] = "d.city LIKE :location";
                $params['location'] = '%' . $criteria['location'] . '%';
            } else if (isset($company['city']) && $company['city']) {
                $conditions[] = "d.city LIKE :location";
                $params['location'] = '%' . $company['city'] . '%';
            }

            // Προσθήκη των συνθηκών στο ερώτημα
            if (!empty($conditions)) {
                $query .= " AND " . implode(" AND ", $conditions);
            }

            // Εκτέλεση του ερωτήματος
            $results = $this->db->query($query, $params)->fetchAll(\PDO::FETCH_ASSOC);

            // Υπολογισμός του σκορ ταιριάσματος για κάθε οδηγό
            foreach ($results as &$result) {
                // Λήψη των αδειών οδήγησης του οδηγού
                $driverLicenses = $this->driverLicenseRepository->findByDriver($result['id']);

                // Λήψη των δεξιοτήτων του οδηγού
                $driverSkills = $this->driverSkillsRepository->findByDriver($result['id']);

                // Λήψη της αξιολόγησης του οδηγού
                $driverRating = $this->driverRatingRepository->getAverageRating($result['id']);

                // Υπολογισμός του μέγιστου σκορ ταιριάσματος για όλες τις αγγελίες της εταιρείας
                $maxScore = 0;
                $bestJobListing = null;
                foreach ($jobListings as $jobListing) {
                    $score = $this->calculateMatchScoreForDriver($jobListing, $result, $driverLicenses, $driverSkills, $driverRating, $preferences);
                    if ($score > $maxScore) {
                        $maxScore = $score;
                        $bestJobListing = $jobListing;
                    }
                }

                $result['match_score'] = $maxScore;
                $result['best_job_listing'] = $bestJobListing;
            }

            // Ταξινόμηση των αποτελεσμάτων με βάση το σκορ ταιριάσματος
            usort($results, function ($a, $b) {
                return $b['match_score'] <=> $a['match_score'];
            });

            // Υπολογισμός της σελιδοποίησης
            $totalResults = count($results);
            $offset = ($page - 1) * $limit;
            $results = array_slice($results, $offset, $limit);

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
            throw new DatabaseException("Σφάλμα κατά την αναζήτηση ταιριασμάτων: " . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function saveMatchPreferences(int $userId, string $userType, array $preferences): bool
    {
        try {
            // Έλεγχος αν υπάρχουν ήδη προτιμήσεις για τον χρήστη
            $query = "SELECT id FROM match_preferences WHERE user_id = :user_id AND user_type = :user_type";
            $params = [
                'user_id' => $userId,
                'user_type' => $userType
            ];
            $result = $this->db->query($query, $params)->fetch(\PDO::FETCH_ASSOC);

            if ($result) {
                // Ενημέρωση των υπαρχόντων προτιμήσεων
                $query = "UPDATE match_preferences SET
                          location_weight = :location_weight,
                          job_type_weight = :job_type_weight,
                          vehicle_type_weight = :vehicle_type_weight,
                          license_weight = :license_weight,
                          experience_weight = :experience_weight,
                          skills_weight = :skills_weight,
                          schedule_weight = :schedule_weight,
                          rating_weight = :rating_weight,
                          updated_at = NOW()
                          WHERE user_id = :user_id AND user_type = :user_type";
            } else {
                // Δημιουργία νέων προτιμήσεων
                $query = "INSERT INTO match_preferences (
                          user_id, user_type, location_weight, job_type_weight, vehicle_type_weight,
                          license_weight, experience_weight, skills_weight, schedule_weight, rating_weight,
                          created_at, updated_at
                          ) VALUES (
                          :user_id, :user_type, :location_weight, :job_type_weight, :vehicle_type_weight,
                          :license_weight, :experience_weight, :skills_weight, :schedule_weight, :rating_weight,
                          NOW(), NOW()
                          )";
            }

            $params = [
                'user_id' => $userId,
                'user_type' => $userType,
                'location_weight' => $preferences['location_weight'] ?? $this->defaultWeights['location'],
                'job_type_weight' => $preferences['job_type_weight'] ?? $this->defaultWeights['job_type'],
                'vehicle_type_weight' => $preferences['vehicle_type_weight'] ?? $this->defaultWeights['vehicle_type'],
                'license_weight' => $preferences['license_weight'] ?? $this->defaultWeights['license'],
                'experience_weight' => $preferences['experience_weight'] ?? $this->defaultWeights['experience'],
                'skills_weight' => $preferences['skills_weight'] ?? $this->defaultWeights['skills'],
                'schedule_weight' => $preferences['schedule_weight'] ?? $this->defaultWeights['schedule'],
                'rating_weight' => $preferences['rating_weight'] ?? $this->defaultWeights['rating']
            ];

            $stmt = $this->db->query($query, $params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την αποθήκευση των προτιμήσεων ταιριάσματος: " . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMatchPreferences(int $userId, string $userType): array
    {
        try {
            $query = "SELECT * FROM match_preferences WHERE user_id = :user_id AND user_type = :user_type";
            $params = [
                'user_id' => $userId,
                'user_type' => $userType
            ];
            $result = $this->db->query($query, $params)->fetch(\PDO::FETCH_ASSOC);

            if ($result) {
                return [
                    'location_weight' => (float)$result['location_weight'],
                    'job_type_weight' => (float)$result['job_type_weight'],
                    'vehicle_type_weight' => (float)$result['vehicle_type_weight'],
                    'license_weight' => (float)$result['license_weight'],
                    'experience_weight' => (float)$result['experience_weight'],
                    'skills_weight' => (float)$result['skills_weight'],
                    'schedule_weight' => (float)$result['schedule_weight'],
                    'rating_weight' => (float)$result['rating_weight']
                ];
            } else {
                return $this->defaultWeights;
            }
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την ανάκτηση των προτιμήσεων ταιριάσματος: " . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function logMatchAction(int $driverId, int $jobListingId, float $matchScore, string $driverAction = 'no_action', string $companyAction = 'no_action'): bool
    {
        try {
            // Έλεγχος αν υπάρχει ήδη καταγραφή για το συγκεκριμένο ταίριασμα
            $query = "SELECT id FROM match_history WHERE driver_id = :driver_id AND job_listing_id = :job_listing_id";
            $params = [
                'driver_id' => $driverId,
                'job_listing_id' => $jobListingId
            ];
            $result = $this->db->query($query, $params)->fetch(\PDO::FETCH_ASSOC);

            if ($result) {
                // Ενημέρωση της υπάρχουσας καταγραφής
                $query = "UPDATE match_history SET
                          match_score = :match_score,
                          driver_action = :driver_action,
                          company_action = :company_action,
                          updated_at = NOW()
                          WHERE driver_id = :driver_id AND job_listing_id = :job_listing_id";
            } else {
                // Δημιουργία νέας καταγραφής
                $query = "INSERT INTO match_history (
                          driver_id, job_listing_id, match_score, driver_action, company_action, created_at, updated_at
                          ) VALUES (
                          :driver_id, :job_listing_id, :match_score, :driver_action, :company_action, NOW(), NOW()
                          )";
            }

            $params = [
                'driver_id' => $driverId,
                'job_listing_id' => $jobListingId,
                'match_score' => $matchScore,
                'driver_action' => $driverAction,
                'company_action' => $companyAction
            ];

            $stmt = $this->db->query($query, $params);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            throw new DatabaseException("Σφάλμα κατά την καταγραφή της ενέργειας ταιριάσματος: " . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverMatchHistory(int $driverId, int $page = 1, int $limit = 10): array
    {
        try {
            $query = "SELECT mh.*, j.title, j.description, j.location, j.job_type, j.company_id, c.company_name
                      FROM match_history mh
                      LEFT JOIN job_listings j ON mh.job_listing_id = j.id
                      LEFT JOIN companies c ON j.company_id = c.id
                      WHERE mh.driver_id = :driver_id
                      ORDER BY mh.updated_at DESC";
            $params = ['driver_id' => $driverId];

            // Μέτρηση συνολικών αποτελεσμάτων
            $countQuery = "SELECT COUNT(*) as total FROM ({$query}) as count_table";
            $countResult = $this->db->query($countQuery, $params)->fetch(\PDO::FETCH_ASSOC);
            $totalResults = $countResult ? (int)$countResult['total'] : 0;

            // Προσθήκη του LIMIT και OFFSET
            $offset = ($page - 1) * $limit;
            $query .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            // Εκτέλεση του ερωτήματος
            $results = $this->db->query($query, $params)->fetchAll(\PDO::FETCH_ASSOC);

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
            throw new DatabaseException("Σφάλμα κατά την ανάκτηση του ιστορικού ταιριάσματος: " . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getJobListingMatchHistory(int $jobListingId, int $page = 1, int $limit = 10): array
    {
        try {
            $query = "SELECT mh.*, d.first_name, d.last_name, d.email, d.phone, d.city
                      FROM match_history mh
                      LEFT JOIN drivers d ON mh.driver_id = d.id
                      WHERE mh.job_listing_id = :job_listing_id
                      ORDER BY mh.match_score DESC, mh.updated_at DESC";
            $params = ['job_listing_id' => $jobListingId];

            // Μέτρηση συνολικών αποτελεσμάτων
            $countQuery = "SELECT COUNT(*) as total FROM ({$query}) as count_table";
            $countResult = $this->db->query($countQuery, $params)->fetch(\PDO::FETCH_ASSOC);
            $totalResults = $countResult ? (int)$countResult['total'] : 0;

            // Προσθήκη του LIMIT και OFFSET
            $offset = ($page - 1) * $limit;
            $query .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

            // Εκτέλεση του ερωτήματος
            $results = $this->db->query($query, $params)->fetchAll(\PDO::FETCH_ASSOC);

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
            throw new DatabaseException("Σφάλμα κατά την ανάκτηση του ιστορικού ταιριάσματος: " . $e->getMessage());
        }
    }

    /**
     * Υπολογίζει το σκορ ταιριάσματος μεταξύ μιας αγγελίας και ενός οδηγού
     *
     * @param array $jobListing Τα στοιχεία της αγγελίας
     * @param array $driver Τα στοιχεία του οδηγού
     * @param array $driverLicenses Οι άδειες οδήγησης του οδηγού
     * @param array $driverSkills Οι δεξιότητες του οδηγού
     * @param float $driverRating Η αξιολόγηση του οδηγού
     * @param array $weights Οι συντελεστές βαρύτητας
     * @return float Το σκορ ταιριάσματος
     */
    private function calculateMatchScore(array $jobListing, array $driver, array $driverLicenses, array $driverSkills, float $driverRating, array $weights): float
    {
        $score = 0;
        $maxScore = 0;

        // Ταίριασμα τοποθεσίας
        if (isset($jobListing['location']) && isset($driver['city'])) {
            $maxScore += $weights['location'];
            if (stripos($jobListing['location'], $driver['city']) !== false || stripos($driver['city'], $jobListing['location']) !== false) {
                $score += $weights['location'];
            }
        }

        // Ταίριασμα τύπου εργασίας
        if (isset($jobListing['job_type']) && isset($driver['preferred_job_type'])) {
            $maxScore += $weights['job_type'];
            if ($jobListing['job_type'] === $driver['preferred_job_type']) {
                $score += $weights['job_type'];
            }
        }

        // Ταίριασμα τύπου οχήματος
        if (isset($jobListing['vehicle_type']) && isset($driver['preferred_vehicle_type'])) {
            $maxScore += $weights['vehicle_type'];
            if ($jobListing['vehicle_type'] === $driver['preferred_vehicle_type']) {
                $score += $weights['vehicle_type'];
            }
        }

        // Ταίριασμα αδειών οδήγησης
        if (isset($jobListing['license_required']) && $jobListing['license_required']) {
            $maxScore += $weights['license'];
            $requiredLicenses = explode(',', $jobListing['license_types']);
            $driverLicenseTypes = [];
            foreach ($driverLicenses as $license) {
                $driverLicenseTypes[] = $license['license_type'];
            }
            $hasAllLicenses = true;
            foreach ($requiredLicenses as $license) {
                if (!in_array($license, $driverLicenseTypes)) {
                    $hasAllLicenses = false;
                    break;
                }
            }
            if ($hasAllLicenses) {
                $score += $weights['license'];
            }
        }

        // Ταίριασμα ΠΕΙ
        if (isset($jobListing['pei_required']) && $jobListing['pei_required']) {
            $maxScore += $weights['license'];
            $hasPEI = false;
            foreach ($driverLicenses as $license) {
                if (isset($license['has_pei']) && $license['has_pei']) {
                    $hasPEI = true;
                    break;
                }
            }
            if ($hasPEI) {
                $score += $weights['license'];
            }
        }

        // Ταίριασμα ADR
        if (isset($jobListing['adr_required']) && $jobListing['adr_required']) {
            $maxScore += $weights['license'];
            $hasADR = false;
            foreach ($driverLicenses as $license) {
                if (isset($license['has_adr']) && $license['has_adr']) {
                    $hasADR = true;
                    break;
                }
            }
            if ($hasADR) {
                $score += $weights['license'];
            }
        }

        // Ταίριασμα ταχογράφου
        if (isset($jobListing['tachograph_required']) && $jobListing['tachograph_required']) {
            $maxScore += $weights['license'];
            $hasTachograph = false;
            foreach ($driverLicenses as $license) {
                if (isset($license['has_tachograph']) && $license['has_tachograph']) {
                    $hasTachograph = true;
                    break;
                }
            }
            if ($hasTachograph) {
                $score += $weights['license'];
            }
        }

        // Ταίριασμα εμπειρίας
        if (isset($jobListing['experience_years']) && isset($driver['experience_years'])) {
            $maxScore += $weights['experience'];
            if ($driver['experience_years'] >= $jobListing['experience_years']) {
                $score += $weights['experience'];
            } else {
                // Μερικό ταίριασμα εμπειρίας
                $score += $weights['experience'] * ($driver['experience_years'] / $jobListing['experience_years']);
            }
        }

        // Ταίριασμα δεξιοτήτων
        if (isset($jobListing['required_skills']) && !empty($driverSkills)) {
            $maxScore += $weights['skills'];
            $requiredSkills = explode(',', $jobListing['required_skills']);
            $driverSkillNames = [];
            foreach ($driverSkills as $skill) {
                $driverSkillNames[] = $skill['skill_name'];
            }
            $matchedSkills = 0;
            foreach ($requiredSkills as $skill) {
                if (in_array($skill, $driverSkillNames)) {
                    $matchedSkills++;
                }
            }
            if (count($requiredSkills) > 0) {
                $score += $weights['skills'] * ($matchedSkills / count($requiredSkills));
            }
        }

        // Ταίριασμα ωραρίου
        if (isset($jobListing['schedule']) && isset($driver['preferred_schedule'])) {
            $maxScore += $weights['schedule'];
            if ($jobListing['schedule'] === $driver['preferred_schedule']) {
                $score += $weights['schedule'];
            }
        }

        // Ταίριασμα αξιολόγησης
        if (isset($jobListing['min_rating']) && $driverRating > 0) {
            $maxScore += $weights['rating'];
            if ($driverRating >= $jobListing['min_rating']) {
                $score += $weights['rating'];
            } else {
                // Μερικό ταίριασμα αξιολόγησης
                $score += $weights['rating'] * ($driverRating / $jobListing['min_rating']);
            }
        }

        // Υπολογισμός του τελικού σκορ (ποσοστό)
        return $maxScore > 0 ? ($score / $maxScore) * 100 : 0;
    }

    /**
     * Υπολογίζει το σκορ ταιριάσματος μεταξύ ενός οδηγού και μιας αγγελίας
     *
     * @param array $jobListing Τα στοιχεία της αγγελίας
     * @param array $driver Τα στοιχεία του οδηγού
     * @param array $driverLicenses Οι άδειες οδήγησης του οδηγού
     * @param array $driverSkills Οι δεξιότητες του οδηγού
     * @param float $driverRating Η αξιολόγηση του οδηγού
     * @param array $weights Οι συντελεστές βαρύτητας
     * @return float Το σκορ ταιριάσματος
     */
    private function calculateMatchScoreForDriver(array $jobListing, array $driver, array $driverLicenses, array $driverSkills, float $driverRating, array $weights): float
    {
        // Χρησιμοποιούμε την ίδια μέθοδο με το calculateMatchScore, αλλά με διαφορετικό όνομα για σαφήνεια
        return $this->calculateMatchScore($jobListing, $driver, $driverLicenses, $driverSkills, $driverRating, $weights);
    }
}

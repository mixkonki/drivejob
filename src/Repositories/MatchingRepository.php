<?php

namespace Drivejob\Repositories;

use Drivejob\Helpers\VehicleTypes;

use PDO;
use Drivejob\Core\Exceptions\DatabaseException;

/**
 * Repository για το ταίριασμα αγγελιών
 */
class MatchingRepository extends BaseRepository implements MatchingRepositoryInterface
{
    /**
     * @var string Το όνομα του πίνακα
     */
    protected $table = 'job_matches';

    /**
     * @var array Τα πεδία που μπορούν να ενημερωθούν
     */
    protected $fillable = [
        'driver_id',
        'company_listing_id',
        'match_score',
        'is_viewed_by_driver',
        'is_viewed_by_company',
        'created_at',
        'updated_at'
    ];

    /**
     * @var array Τα πεδία που δεν μπορούν να ενημερωθούν
     */
    protected $guarded = [
        'id',
        'created_at'
    ];

    /**
     * @var DriversRepositoryInterface Το repository για τους οδηγούς
     */
    private $driversRepository;

    /**
     * @var JobListingRepositoryInterface Το repository για τις αγγελίες
     */
    private $jobListingRepository;

    /**
     * Constructor
     *
     * @param PDO $pdo Η σύνδεση με τη βάση δεδομένων
     * @param DriversRepositoryInterface|null $driversRepository Το repository για τους οδηγούς
     * @param JobListingRepositoryInterface|null $jobListingRepository Το repository για τις αγγελίες
     */
    public function __construct(
        PDO $pdo,
        ?DriversRepositoryInterface $driversRepository = null,
        ?JobListingRepositoryInterface $jobListingRepository = null
    ) {
        parent::__construct($pdo);

        $this->driversRepository = $driversRepository ?? new DriversRepository($pdo);
        $this->jobListingRepository = $jobListingRepository ?? new JobListingRepository($pdo);
    }

    /**
     * {@inheritdoc}
     */
    public function findMatchingJobsForDriver($driverId, array $criteria = [], $page = 1, $limit = 10)
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

            // Δημιουργία του βασικού ερωτήματος
            $query = "SELECT jl.*, c.company_name, c.logo, c.city, c.country,
                      COALESCE(jm.match_score, 0) as match_score
                      FROM job_listings jl
                      JOIN companies c ON jl.company_id = c.id
                      LEFT JOIN {$this->table} jm ON jl.id = jm.company_listing_id AND jm.driver_id = :driver_id
                      WHERE jl.is_active = 1 AND jl.is_approved = 1 AND jl.company_id IS NOT NULL
                      AND jl.driver_id IS NULL AND jl.expires_at > NOW()";
            $params = ['driver_id' => $driverId];
            $conditions = [];

            // Προσθήκη των κριτηρίων
            if (isset($criteria['location']) && $criteria['location']) {
                $conditions[] = "(jl.location LIKE :location OR c.city LIKE :location OR c.country LIKE :location)";
                $params['location'] = '%' . $criteria['location'] . '%';
            }

            if (isset($criteria['job_type']) && $criteria['job_type']) {
                $conditions[] = "jl.job_type = :job_type";
                $params['job_type'] = $criteria['job_type'];
            }

            if (isset($criteria['vehicle_type']) && $criteria['vehicle_type']) {
                $conditions[] = "jl.vehicle_type = :vehicle_type";
                $params['vehicle_type'] = $criteria['vehicle_type'];
            }

            if (isset($criteria['min_salary']) && $criteria['min_salary']) {
                $conditions[] = "jl.salary_min >= :min_salary";
                $params['min_salary'] = $criteria['min_salary'];
            }

            // Προσθήκη των συνθηκών στο ερώτημα
            if (!empty($conditions)) {
                $query .= " AND " . implode(" AND ", $conditions);
            }

            // Προσθήκη της ταξινόμησης
            if (isset($criteria['sort_by']) && $criteria['sort_by']) {
                $sortField = $criteria['sort_by'];
                $sortDirection = isset($criteria['sort_direction']) && strtoupper($criteria['sort_direction']) === 'DESC' ? 'DESC' : 'ASC';

                // Έλεγχος για έγκυρο πεδίο ταξινόμησης
                $validSortFields = ['match_score', 'created_at', 'salary_min', 'salary_max'];
                if (in_array($sortField, $validSortFields)) {
                    $query .= " ORDER BY $sortField $sortDirection";
                } else {
                    $query .= " ORDER BY match_score DESC, jl.created_at DESC";
                }
            } else {
                $query .= " ORDER BY match_score DESC, jl.created_at DESC";
            }

            // Μέτρηση συνολικών αποτελεσμάτων
            $countQuery = "SELECT COUNT(*) FROM ({$query}) as count_table";
            $totalResults = $this->queryScalar($countQuery, $params);

            // Προσθήκη του LIMIT και OFFSET
            $offset = ($page - 1) * $limit;
            /*
             * LIMIT/OFFSET ΔΕΝ γίνονται named params: το PDO τα δένει ως
             * κείμενο ('5' OFFSET '0') και η MariaDB απορρίπτει το ερώτημα
             * — το ταίριασμα σκοτωνόταν σε ΚΑΘΕ φόρτωση προφίλ (φαινόταν
             * μόνο στα logs). Ασφαλές ως άμεση παρεμβολή: μόνο ακέραιοι.
             */
            $query .= ' LIMIT ' . max(1, (int) $limit) . ' OFFSET ' . max(0, (int) $offset);

            // Εκτέλεση του ερωτήματος
            $results = $this->query($query, $params);

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
            throw DatabaseException::fromPDOException($e, $query ?? null, $params ?? []);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findMatchingDriversForCompany($companyId, array $criteria = [], $page = 1, $limit = 10)
    {
        try {
            // Δημιουργία του βασικού ερωτήματος
            $query = "SELECT jl.*, d.first_name, d.last_name, d.profile_image, d.city, d.country,
                      COALESCE(jm.match_score, 0) as match_score
                      FROM job_listings jl
                      JOIN drivers d ON jl.driver_id = d.id
                      LEFT JOIN {$this->table} jm ON jl.id = jm.driver_listing_id AND jm.company_id = :company_id
                      WHERE jl.is_active = 1 AND jl.is_approved = 1 AND jl.driver_id IS NOT NULL
                      AND jl.company_id IS NULL AND jl.expires_at > NOW()";
            $params = ['company_id' => $companyId];
            $conditions = [];

            // Προσθήκη των κριτηρίων
            if (isset($criteria['location']) && $criteria['location']) {
                $conditions[] = "(jl.location LIKE :location OR d.city LIKE :location OR d.country LIKE :location)";
                $params['location'] = '%' . $criteria['location'] . '%';
            }

            if (isset($criteria['job_type']) && $criteria['job_type']) {
                $conditions[] = "jl.job_type = :job_type";
                $params['job_type'] = $criteria['job_type'];
            }

            if (isset($criteria['vehicle_type']) && $criteria['vehicle_type']) {
                $conditions[] = "jl.vehicle_type = :vehicle_type";
                $params['vehicle_type'] = $criteria['vehicle_type'];
            }

            if (isset($criteria['experience_years']) && $criteria['experience_years']) {
                $conditions[] = "d.experience_years >= :experience_years";
                $params['experience_years'] = $criteria['experience_years'];
            }

            // Προσθήκη των συνθηκών στο ερώτημα
            if (!empty($conditions)) {
                $query .= " AND " . implode(" AND ", $conditions);
            }

            // Προσθήκη της ταξινόμησης
            if (isset($criteria['sort_by']) && $criteria['sort_by']) {
                $sortField = $criteria['sort_by'];
                $sortDirection = isset($criteria['sort_direction']) && strtoupper($criteria['sort_direction']) === 'DESC' ? 'DESC' : 'ASC';

                // Έλεγχος για έγκυρο πεδίο ταξινόμησης
                $validSortFields = ['match_score', 'created_at', 'experience_years'];
                if (in_array($sortField, $validSortFields)) {
                    $query .= " ORDER BY $sortField $sortDirection";
                } else {
                    $query .= " ORDER BY match_score DESC, jl.created_at DESC";
                }
            } else {
                $query .= " ORDER BY match_score DESC, jl.created_at DESC";
            }

            // Μέτρηση συνολικών αποτελεσμάτων
            $countQuery = "SELECT COUNT(*) FROM ({$query}) as count_table";
            $totalResults = $this->queryScalar($countQuery, $params);

            // Προσθήκη του LIMIT και OFFSET
            $offset = ($page - 1) * $limit;
            /*
             * LIMIT/OFFSET ΔΕΝ γίνονται named params: το PDO τα δένει ως
             * κείμενο ('5' OFFSET '0') και η MariaDB απορρίπτει το ερώτημα
             * — το ταίριασμα σκοτωνόταν σε ΚΑΘΕ φόρτωση προφίλ (φαινόταν
             * μόνο στα logs). Ασφαλές ως άμεση παρεμβολή: μόνο ακέραιοι.
             */
            $query .= ' LIMIT ' . max(1, (int) $limit) . ' OFFSET ' . max(0, (int) $offset);

            // Εκτέλεση του ερωτήματος
            $results = $this->query($query, $params);

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
            throw DatabaseException::fromPDOException($e, $query ?? null, $params ?? []);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findMatchingJobsForDriverListing($driverListingId, array $criteria = [], $page = 1, $limit = 10)
    {
        try {
            // Λήψη της αγγελίας του οδηγού
            $driverListing = $this->jobListingRepository->find($driverListingId);
            if (!$driverListing || empty($driverListing['driver_id'])) {
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

            // Δημιουργία του βασικού ερωτήματος
            $query = "SELECT jl.*, c.company_name, c.logo, c.city, c.country,
                      COALESCE(jm.match_score, 0) as match_score
                      FROM job_listings jl
                      JOIN companies c ON jl.company_id = c.id
                      LEFT JOIN {$this->table} jm ON jl.id = jm.company_listing_id AND jm.driver_listing_id = :driver_listing_id
                      WHERE jl.is_active = 1 AND jl.is_approved = 1 AND jl.company_id IS NOT NULL
                      AND jl.driver_id IS NULL AND jl.expires_at > NOW()";
            $params = ['driver_listing_id' => $driverListingId];
            $conditions = [];

            // Προσθήκη των κριτηρίων από την αγγελία του οδηγού
            if (!empty($driverListing['location'])) {
                $conditions[] = "(jl.location LIKE :location OR c.city LIKE :location OR c.country LIKE :location)";
                $params['location'] = '%' . $driverListing['location'] . '%';
            }

            if (!empty($driverListing['job_type'])) {
                $conditions[] = "jl.job_type = :job_type";
                $params['job_type'] = $driverListing['job_type'];
            }

            if (!empty($driverListing['vehicle_type'])) {
                $conditions[] = "jl.vehicle_type = :vehicle_type";
                $params['vehicle_type'] = $driverListing['vehicle_type'];
            }

            // Προσθήκη των επιπλέον κριτηρίων
            if (isset($criteria['min_salary']) && $criteria['min_salary']) {
                $conditions[] = "jl.salary_min >= :min_salary";
                $params['min_salary'] = $criteria['min_salary'];
            }

            // Προσθήκη των συνθηκών στο ερώτημα
            if (!empty($conditions)) {
                $query .= " AND " . implode(" AND ", $conditions);
            }

            // Προσθήκη της ταξινόμησης
            if (isset($criteria['sort_by']) && $criteria['sort_by']) {
                $sortField = $criteria['sort_by'];
                $sortDirection = isset($criteria['sort_direction']) && strtoupper($criteria['sort_direction']) === 'DESC' ? 'DESC' : 'ASC';

                // Έλεγχος για έγκυρο πεδίο ταξινόμησης
                $validSortFields = ['match_score', 'created_at', 'salary_min', 'salary_max'];
                if (in_array($sortField, $validSortFields)) {
                    $query .= " ORDER BY $sortField $sortDirection";
                } else {
                    $query .= " ORDER BY match_score DESC, jl.created_at DESC";
                }
            } else {
                $query .= " ORDER BY match_score DESC, jl.created_at DESC";
            }

            // Μέτρηση συνολικών αποτελεσμάτων
            $countQuery = "SELECT COUNT(*) FROM ({$query}) as count_table";
            $totalResults = $this->queryScalar($countQuery, $params);

            // Προσθήκη του LIMIT και OFFSET
            $offset = ($page - 1) * $limit;
            /*
             * LIMIT/OFFSET ΔΕΝ γίνονται named params: το PDO τα δένει ως
             * κείμενο ('5' OFFSET '0') και η MariaDB απορρίπτει το ερώτημα
             * — το ταίριασμα σκοτωνόταν σε ΚΑΘΕ φόρτωση προφίλ (φαινόταν
             * μόνο στα logs). Ασφαλές ως άμεση παρεμβολή: μόνο ακέραιοι.
             */
            $query .= ' LIMIT ' . max(1, (int) $limit) . ' OFFSET ' . max(0, (int) $offset);

            // Εκτέλεση του ερωτήματος
            $results = $this->query($query, $params);

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
            throw DatabaseException::fromPDOException($e, $query ?? null, $params ?? []);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findMatchingDriversForCompanyListing($companyListingId, array $criteria = [], $page = 1, $limit = 10)
    {
        try {
            // Λήψη της αγγελίας της εταιρείας
            $companyListing = $this->jobListingRepository->find($companyListingId);
            if (!$companyListing || empty($companyListing['company_id'])) {
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

            // Δημιουργία του βασικού ερωτήματος
            $query = "SELECT jl.*, d.first_name, d.last_name, d.profile_image, d.city, d.country,
                      COALESCE(jm.match_score, 0) as match_score
                      FROM job_listings jl
                      JOIN drivers d ON jl.driver_id = d.id
                      LEFT JOIN {$this->table} jm ON jl.id = jm.driver_listing_id AND jm.company_listing_id = :company_listing_id
                      WHERE jl.is_active = 1 AND jl.is_approved = 1 AND jl.driver_id IS NOT NULL
                      AND jl.company_id IS NULL AND jl.expires_at > NOW()";
            $params = ['company_listing_id' => $companyListingId];
            $conditions = [];

            // Προσθήκη των κριτηρίων από την αγγελία της εταιρείας
            if (!empty($companyListing['location'])) {
                $conditions[] = "(jl.location LIKE :location OR d.city LIKE :location OR d.country LIKE :location)";
                $params['location'] = '%' . $companyListing['location'] . '%';
            }

            if (!empty($companyListing['job_type'])) {
                $conditions[] = "jl.job_type = :job_type";
                $params['job_type'] = $companyListing['job_type'];
            }

            if (!empty($companyListing['vehicle_type'])) {
                $conditions[] = "jl.vehicle_type = :vehicle_type";
                $params['vehicle_type'] = $companyListing['vehicle_type'];
            }

            if (!empty($companyListing['experience_years'])) {
                $conditions[] = "d.experience_years >= :experience_years";
                $params['experience_years'] = $companyListing['experience_years'];
            }

            // Προσθήκη των επιπλέον κριτηρίων
            if (isset($criteria['experience_years']) && $criteria['experience_years']) {
                $conditions[] = "d.experience_years >= :criteria_experience_years";
                $params['criteria_experience_years'] = $criteria['experience_years'];
            }

            // Προσθήκη των συνθηκών στο ερώτημα
            if (!empty($conditions)) {
                $query .= " AND " . implode(" AND ", $conditions);
            }

            // Προσθήκη της ταξινόμησης
            if (isset($criteria['sort_by']) && $criteria['sort_by']) {
                $sortField = $criteria['sort_by'];
                $sortDirection = isset($criteria['sort_direction']) && strtoupper($criteria['sort_direction']) === 'DESC' ? 'DESC' : 'ASC';

                // Έλεγχος για έγκυρο πεδίο ταξινόμησης
                $validSortFields = ['match_score', 'created_at', 'experience_years'];
                if (in_array($sortField, $validSortFields)) {
                    $query .= " ORDER BY $sortField $sortDirection";
                } else {
                    $query .= " ORDER BY match_score DESC, jl.created_at DESC";
                }
            } else {
                $query .= " ORDER BY match_score DESC, jl.created_at DESC";
            }

            // Μέτρηση συνολικών αποτελεσμάτων
            $countQuery = "SELECT COUNT(*) FROM ({$query}) as count_table";
            $totalResults = $this->queryScalar($countQuery, $params);

            // Προσθήκη του LIMIT και OFFSET
            $offset = ($page - 1) * $limit;
            /*
             * LIMIT/OFFSET ΔΕΝ γίνονται named params: το PDO τα δένει ως
             * κείμενο ('5' OFFSET '0') και η MariaDB απορρίπτει το ερώτημα
             * — το ταίριασμα σκοτωνόταν σε ΚΑΘΕ φόρτωση προφίλ (φαινόταν
             * μόνο στα logs). Ασφαλές ως άμεση παρεμβολή: μόνο ακέραιοι.
             */
            $query .= ' LIMIT ' . max(1, (int) $limit) . ' OFFSET ' . max(0, (int) $offset);

            // Εκτέλεση του ερωτήματος
            $results = $this->query($query, $params);

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
            throw DatabaseException::fromPDOException($e, $query ?? null, $params ?? []);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function calculateMatchScore($driverId, $companyListingId)
    {
        try {
            // Λήψη των στοιχείων του οδηγού
            $driver = $this->driversRepository->find($driverId);
            if (!$driver) {
                return 0;
            }

            // Λήψη της αγγελίας της εταιρείας
            $companyListing = $this->jobListingRepository->find($companyListingId);
            if (!$companyListing || empty($companyListing['company_id'])) {
                return 0;
            }

            // Υπολογισμός του σκορ ταιριάσματος
            $score = 0;
            $totalWeight = 0;

            // Ταίριασμα τοποθεσίας (βάρος: 20%)
            if (!empty($companyListing['location']) && !empty($driver['city'])) {
                $locationMatch = $this->calculateLocationMatch($companyListing['location'], $driver['city'], $driver['country']);
                $score += $locationMatch * 20;
                $totalWeight += 20;
            }

            // Ταίριασμα τύπου εργασίας (βάρος: 15%)
            if (!empty($companyListing['job_type'])) {
                // Λήψη των προτιμήσεων του οδηγού για τον τύπο εργασίας
                $driverJobTypes = $this->getDriverJobTypes($driverId);
                $jobTypeMatch = in_array($companyListing['job_type'], $driverJobTypes) ? 100 : 0;
                $score += $jobTypeMatch * 15;
                $totalWeight += 15;
            }

            // Ταίριασμα τύπου οχήματος (βάρος: 15%)
            if (!empty($companyListing['vehicle_type'])) {
                // Λήψη των αδειών οδήγησης του οδηγού
                $driverLicenses = $this->getDriverLicenses($driverId);
                $vehicleTypeMatch = $this->calculateVehicleTypeMatch($companyListing['vehicle_type'], $driverLicenses);
                $score += $vehicleTypeMatch * 15;
                $totalWeight += 15;
            }

            // Ταίριασμα απαιτούμενων αδειών (βάρος: 20%)
            if (!empty($companyListing['required_licenses'])) {
                $requiredLicenses = explode(',', $companyListing['required_licenses']);
                $driverLicenses = $this->getDriverLicenses($driverId);
                $licensesMatch = $this->calculateLicensesMatch($requiredLicenses, $driverLicenses);
                $score += $licensesMatch * 20;
                $totalWeight += 20;
            }

            // Ταίριασμα ετών εμπειρίας (βάρος: 10%)
            if (!empty($companyListing['experience_years']) && !empty($driver['experience_years'])) {
                $experienceMatch = $driver['experience_years'] >= $companyListing['experience_years'] ? 100 : 0;
                $score += $experienceMatch * 10;
                $totalWeight += 10;
            }

            // Ταίριασμα απαιτούμενων δεξιοτήτων (βάρος: 20%)
            if (!empty($companyListing['required_skills'])) {
                $requiredSkills = explode(',', $companyListing['required_skills']);
                $driverSkills = $this->getDriverSkills($driverId);
                $skillsMatch = $this->calculateSkillsMatch($requiredSkills, $driverSkills);
                $score += $skillsMatch * 20;
                $totalWeight += 20;
            }

            // Υπολογισμός του τελικού σκορ
            return $totalWeight > 0 ? round($score / $totalWeight) : 0;
        } catch (\Exception $e) {
            // Σε περίπτωση σφάλματος, επιστρέφουμε 0
            return 0;
        }
    }

    /**
     * Υπολογίζει το ταίριασμα τοποθεσίας
     * 
     * @param string $listingLocation Η τοποθεσία της αγγελίας
     * @param string $driverCity Η πόλη του οδηγού
     * @param string $driverCountry Η χώρα του οδηγού
     * @return float Το ποσοστό ταιριάσματος (0-100)
     */
    private function calculateLocationMatch($listingLocation, $driverCity, $driverCountry)
    {
        // Απλή υλοποίηση με βάση το όνομα της πόλης ή της χώρας
        // Σε μια πραγματική εφαρμογή θα χρησιμοποιούσαμε γεωγραφικές συντεταγμένες
        if (stripos($listingLocation, $driverCity) !== false) {
            return 100;
        } elseif (stripos($listingLocation, $driverCountry) !== false) {
            return 70;
        } else {
            return 0;
        }
    }

    /**
     * Λαμβάνει τους προτιμώμενους τύπους εργασίας του οδηγού
     * 
     * @param int $driverId Το ID του οδηγού
     * @return array Οι προτιμώμενοι τύποι εργασίας
     */
    private function getDriverJobTypes($driverId)
    {
        try {
            $query = "SELECT job_type FROM driver_job_preferences WHERE driver_id = :driver_id";
            $results = $this->query($query, ['driver_id' => $driverId]);
            return array_column($results, 'job_type');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Λαμβάνει τις άδειες οδήγησης του οδηγού
     * 
     * @param int $driverId Το ID του οδηγού
     * @return array Οι άδειες οδήγησης
     */
    private function getDriverLicenses($driverId)
    {
        try {
            $query = "SELECT license_type FROM driver_licenses WHERE driver_id = :driver_id";
            $results = $this->query($query, ['driver_id' => $driverId]);
            return array_column($results, 'license_type');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Υπολογίζει το ταίριασμα τύπου οχήματος
     * 
     * @param string $requiredVehicleType Ο απαιτούμενος τύπος οχήματος
     * @param array $driverLicenses Οι άδειες οδήγησης του οδηγού
     * @return float Το ποσοστό ταιριάσματος (0-100)
     */
    private function calculateVehicleTypeMatch($requiredVehicleType, array $driverLicenses)
    {
        // Αντιστοίχιση τύπων οχημάτων με άδειες οδήγησης
        // Η αντιστοίχιση οχήματος–διπλώματος ζει στο VehicleTypes.

        // Έλεγχος αν ο οδηγός έχει την κατάλληλη άδεια
        if (VehicleTypes::licensesFor($requiredVehicleType) !== []) {
            $requiredLicenses = VehicleTypes::licensesFor($requiredVehicleType);
            foreach ($requiredLicenses as $license) {
                if (in_array($license, $driverLicenses)) {
                    return 100;
                }
            }
        }

        return 0;
    }

    /**
     * Υπολογίζει το ταίριασμα αδειών οδήγησης
     * 
     * @param array $requiredLicenses Οι απαιτούμενες άδειες οδήγησης
     * @param array $driverLicenses Οι άδειες οδήγησης του οδηγού
     * @return float Το ποσοστό ταιριάσματος (0-100)
     */
    private function calculateLicensesMatch(array $requiredLicenses, array $driverLicenses)
    {
        if (empty($requiredLicenses)) {
            return 100;
        }

        $matchCount = 0;
        foreach ($requiredLicenses as $license) {
            if (in_array($license, $driverLicenses)) {
                $matchCount++;
            }
        }

        return $matchCount > 0 ? ($matchCount / count($requiredLicenses)) * 100 : 0;
    }

    /**
     * Λαμβάνει τις δεξιότητες του οδηγού
     * 
     * @param int $driverId Το ID του οδηγού
     * @return array Οι δεξιότητες του οδηγού
     */
    private function getDriverSkills($driverId)
    {
        try {
            $query = "SELECT skill_name FROM driver_skills WHERE driver_id = :driver_id";
            $results = $this->query($query, ['driver_id' => $driverId]);
            return array_column($results, 'skill_name');
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Υπολογίζει το ταίριασμα δεξιοτήτων
     * 
     * @param array $requiredSkills Οι απαιτούμενες δεξιότητες
     * @param array $driverSkills Οι δεξιότητες του οδηγού
     * @return float Το ποσοστό ταιριάσματος (0-100)
     */
    private function calculateSkillsMatch(array $requiredSkills, array $driverSkills)
    {
        if (empty($requiredSkills)) {
            return 100;
        }

        $matchCount = 0;
        foreach ($requiredSkills as $skill) {
            if (in_array($skill, $driverSkills)) {
                $matchCount++;
            }
        }

        return $matchCount > 0 ? ($matchCount / count($requiredSkills)) * 100 : 0;
    }

    /**
     * {@inheritdoc}
     */
    public function saveMatch($driverId, $companyListingId, $score)
    {
        try {
            // Έλεγχος αν υπάρχει ήδη ταίριασμα
            $existingMatch = $this->findOne([
                'driver_id' => $driverId,
                'company_listing_id' => $companyListingId
            ]);

            if ($existingMatch) {
                // Ενημέρωση του υπάρχοντος ταιριάσματος
                $this->update($existingMatch['id'], [
                    'match_score' => $score,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                return $existingMatch['id'];
            } else {
                // Δημιουργία νέου ταιριάσματος
                $data = [
                    'driver_id' => $driverId,
                    'company_listing_id' => $companyListingId,
                    'match_score' => $score,
                    'is_viewed_by_driver' => 0,
                    'is_viewed_by_company' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                return $this->create($data);
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findDriverMatches($driverId, $page = 1, $limit = 10)
    {
        try {
            $query = "SELECT jm.*, jl.title, jl.description, jl.location, jl.job_type, jl.vehicle_type,
                      jl.salary_min, jl.salary_max, jl.created_at as listing_created_at,
                      c.company_name, c.city, c.country
                      FROM {$this->table} jm
                      JOIN job_listings jl ON jm.company_listing_id = jl.id
                      JOIN companies c ON jl.company_id = c.id
                      WHERE jm.driver_id = :driver_id
                      ORDER BY jm.match_score DESC, jm.created_at DESC";
            $params = ['driver_id' => $driverId];

            // Μέτρηση συνολικών αποτελεσμάτων
            $countQuery = "SELECT COUNT(*) FROM {$this->table} WHERE driver_id = :driver_id";
            $totalResults = $this->queryScalar($countQuery, $params);

            // Προσθήκη του LIMIT και OFFSET
            $offset = ($page - 1) * $limit;
            /*
             * LIMIT/OFFSET ΔΕΝ γίνονται named params: το PDO τα δένει ως
             * κείμενο ('5' OFFSET '0') και η MariaDB απορρίπτει το ερώτημα
             * — το ταίριασμα σκοτωνόταν σε ΚΑΘΕ φόρτωση προφίλ (φαινόταν
             * μόνο στα logs). Ασφαλές ως άμεση παρεμβολή: μόνο ακέραιοι.
             */
            $query .= ' LIMIT ' . max(1, (int) $limit) . ' OFFSET ' . max(0, (int) $offset);

            // Εκτέλεση του ερωτήματος
            $results = $this->query($query, $params);

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
            throw DatabaseException::fromPDOException($e, $query ?? null, $params ?? []);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findCompanyMatches($companyId, $page = 1, $limit = 10)
    {
        try {
            $query = "SELECT jm.*, jl.title, jl.description, jl.location, jl.job_type, jl.vehicle_type,
                      jl.created_at as listing_created_at,
                      d.first_name, d.last_name, d.profile_image, d.city, d.country, d.experience_years
                      FROM {$this->table} jm
                      JOIN job_listings jl ON jm.company_listing_id = jl.id
                      JOIN drivers d ON jm.driver_id = d.id
                      WHERE jl.company_id = :company_id
                      ORDER BY jm.match_score DESC, jm.created_at DESC";
            $params = ['company_id' => $companyId];

            // Μέτρηση συνολικών αποτελεσμάτων
            $countQuery = "SELECT COUNT(*) FROM {$this->table} jm
                          JOIN job_listings jl ON jm.company_listing_id = jl.id
                          WHERE jl.company_id = :company_id";
            $totalResults = $this->queryScalar($countQuery, $params);

            // Προσθήκη του LIMIT και OFFSET
            $offset = ($page - 1) * $limit;
            /*
             * LIMIT/OFFSET ΔΕΝ γίνονται named params: το PDO τα δένει ως
             * κείμενο ('5' OFFSET '0') και η MariaDB απορρίπτει το ερώτημα
             * — το ταίριασμα σκοτωνόταν σε ΚΑΘΕ φόρτωση προφίλ (φαινόταν
             * μόνο στα logs). Ασφαλές ως άμεση παρεμβολή: μόνο ακέραιοι.
             */
            $query .= ' LIMIT ' . max(1, (int) $limit) . ' OFFSET ' . max(0, (int) $offset);

            // Εκτέλεση του ερωτήματος
            $results = $this->query($query, $params);

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
            throw DatabaseException::fromPDOException($e, $query ?? null, $params ?? []);
        }
    }
}

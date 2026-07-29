<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Drivejob\Services\Matching\MatchingService;
use Drivejob\Core\Database;
use Drivejob\Repositories\JobListingRepository;
use Drivejob\Repositories\DriversRepository;
use Drivejob\Repositories\CompaniesRepository;
use Drivejob\Repositories\DriverLicenseRepository;
use Drivejob\Repositories\DriverSkillsRepository;
use Drivejob\Repositories\DriverRatingRepository;

/**
 * Δοκιμές για το MatchingService
 * 
 * Αυτή η κλάση περιέχει δοκιμές για τις μεθόδους του MatchingService
 */
class MatchingServiceTest extends TestCase
{
    /**
     * @var MatchingService Η υπηρεσία ταιριάσματος
     */
    private $matchingService;

    /**
     * @var Database Mock του Database
     */
    private $dbMock;

    /**
     * @var JobListingRepository Mock του JobListingRepository
     */
    private $jobListingRepoMock;

    /**
     * @var DriversRepository Mock του DriversRepository
     */
    private $driversRepoMock;

    /**
     * @var CompaniesRepository Mock του CompaniesRepository
     */
    private $companiesRepoMock;

    /**
     * @var DriverLicenseRepository Mock του DriverLicenseRepository
     */
    private $driverLicenseRepoMock;

    /**
     * @var DriverSkillsRepository Mock του DriverSkillsRepository
     */
    private $driverSkillsRepoMock;

    /**
     * @var DriverRatingRepository Mock του DriverRatingRepository
     */
    private $driverRatingRepoMock;

    /**
     * Ρύθμιση πριν από κάθε δοκιμή
     */
    protected function setUp(): void
    {
        // Δημιουργία mock για το Database και τα repositories
        $this->dbMock = $this->createMock(Database::class);
        $this->jobListingRepoMock = $this->createMock(JobListingRepository::class);
        $this->driversRepoMock = $this->createMock(DriversRepository::class);
        $this->companiesRepoMock = $this->createMock(CompaniesRepository::class);
        $this->driverLicenseRepoMock = $this->createMock(DriverLicenseRepository::class);
        $this->driverSkillsRepoMock = $this->createMock(DriverSkillsRepository::class);
        $this->driverRatingRepoMock = $this->createMock(DriverRatingRepository::class);

        // Δημιουργία του MatchingService με τα mock
        $this->matchingService = new MatchingService(
            $this->dbMock,
            $this->jobListingRepoMock,
            $this->driversRepoMock,
            $this->companiesRepoMock,
            $this->driverLicenseRepoMock,
            $this->driverSkillsRepoMock,
            $this->driverRatingRepoMock
        );
    }

    /**
     * Δοκιμή για τη μέθοδο findMatchesForDriver
     */
    public function testFindMatchesForDriver()
    {
        // Δεδομένα δοκιμής
        $driverId = 1;
        $criteria = [
            'location' => 'Αθήνα',
            'job_type' => 'Μεταφορές',
            'vehicle_type' => 'Φορτηγό'
        ];
        $page = 1;
        $limit = 10;

        // Ρύθμιση των mock
        $this->driversRepoMock->method('find')
            ->with($driverId)
            ->willReturn([
                'id' => 1,
                'first_name' => 'Γιώργος',
                'last_name' => 'Παπαδόπουλος',
                'city' => 'Αθήνα',
                'available' => 1
            ]);

        $this->driverLicenseRepoMock->method('findByDriver')
            ->with($driverId)
            ->willReturn([
                ['license_type' => 'B', 'has_pei' => false],
                ['license_type' => 'C', 'has_pei' => true]
            ]);

        $this->driverSkillsRepoMock->method('findByDriver')
            ->with($driverId)
            ->willReturn([
                ['skill_name' => 'Μεταφορές'],
                ['skill_name' => 'Διανομές']
            ]);

        $this->driverRatingRepoMock->method('getAverageRating')
            ->with($driverId)
            ->willReturn(4.5);

        // Ρύθμιση του mock για το Database
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('fetchAll')
            ->willReturn([
                [
                    'id' => 1,
                    'title' => 'Οδηγός Φορτηγού',
                    'location' => 'Αθήνα',
                    'job_type' => 'Μεταφορές',
                    'vehicle_type' => 'Φορτηγό',
                    'company_id' => 1,
                    'company_name' => 'Μεταφορική ΑΕ',
                    'is_active' => 1,
                    'expires_at' => '2025-12-31',
                    'listing_type' => 'job_offer'
                ]
            ]);

        $this->dbMock->method('query')
            ->willReturn($stmtMock);

        // Κλήση της μεθόδου που δοκιμάζουμε
        $result = $this->matchingService->findMatchesForDriver($driverId, $criteria, $page, $limit);

        // Επαλήθευση του αποτελέσματος
        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('pagination', $result);
    }

    /**
     * Δοκιμή για τη μέθοδο findMatchesForJobListing
     */
    public function testFindMatchesForJobListing()
    {
        // Δεδομένα δοκιμής
        $jobListingId = 1;
        $criteria = [
            'location' => 'Αθήνα'
        ];
        $page = 1;
        $limit = 10;

        // Ρύθμιση των mock
        $this->jobListingRepoMock->method('find')
            ->with($jobListingId)
            ->willReturn([
                'id' => 1,
                'title' => 'Οδηγός Φορτηγού',
                'location' => 'Αθήνα',
                'job_type' => 'Μεταφορές',
                'vehicle_type' => 'Φορτηγό',
                'company_id' => 1
            ]);

        $this->companiesRepoMock->method('find')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'company_name' => 'Μεταφορική ΑΕ',
                'city' => 'Αθήνα'
            ]);

        // Ρύθμιση του mock για το Database
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('fetchAll')
            ->willReturn([
                [
                    'id' => 1,
                    'first_name' => 'Γιώργος',
                    'last_name' => 'Παπαδόπουλος',
                    'city' => 'Αθήνα',
                    'is_active' => 1,
                    'is_available' => 1
                ]
            ]);

        $this->dbMock->method('query')
            ->willReturn($stmtMock);

        // Ρύθμιση των mock για τα repositories που χρησιμοποιούνται στον υπολογισμό του σκορ
        $this->driverLicenseRepoMock->method('findByDriver')
            ->with(1)
            ->willReturn([
                ['license_type' => 'B', 'has_pei' => false],
                ['license_type' => 'C', 'has_pei' => true]
            ]);

        $this->driverSkillsRepoMock->method('findByDriver')
            ->with(1)
            ->willReturn([
                ['skill_name' => 'Μεταφορές'],
                ['skill_name' => 'Διανομές']
            ]);

        $this->driverRatingRepoMock->method('getAverageRating')
            ->with(1)
            ->willReturn(4.5);

        // Κλήση της μεθόδου που δοκιμάζουμε
        $result = $this->matchingService->findMatchesForJobListing($jobListingId, $criteria, $page, $limit);

        // Επαλήθευση του αποτελέσματος
        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('pagination', $result);
    }

    /**
     * Δοκιμή για τη μέθοδο findMatchesForCompany
     */
    public function testFindMatchesForCompany()
    {
        // Δεδομένα δοκιμής
        $companyId = 1;
        $criteria = [
            'location' => 'Αθήνα'
        ];
        $page = 1;
        $limit = 10;

        // Ρύθμιση των mock
        $this->companiesRepoMock->method('find')
            ->with($companyId)
            ->willReturn([
                'id' => 1,
                'company_name' => 'Μεταφορική ΑΕ',
                'city' => 'Αθήνα'
            ]);

        $this->jobListingRepoMock->method('getCompanyListings')
            ->with($companyId, true)
            ->willReturn([
                'results' => [
                    [
                        'id' => 1,
                        'title' => 'Οδηγός Φορτηγού',
                        'location' => 'Αθήνα',
                        'job_type' => 'Μεταφορές',
                        'vehicle_type' => 'Φορτηγό',
                        'company_id' => 1
                    ]
                ]
            ]);

        // Ρύθμιση του mock για το Database
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('fetchAll')
            ->willReturn([
                [
                    'id' => 1,
                    'first_name' => 'Γιώργος',
                    'last_name' => 'Παπαδόπουλος',
                    'city' => 'Αθήνα',
                    'is_active' => 1,
                    'is_available' => 1
                ]
            ]);

        $this->dbMock->method('query')
            ->willReturn($stmtMock);

        // Ρύθμιση των mock για τα repositories που χρησιμοποιούνται στον υπολογισμό του σκορ
        $this->driverLicenseRepoMock->method('findByDriver')
            ->with(1)
            ->willReturn([
                ['license_type' => 'B', 'has_pei' => false],
                ['license_type' => 'C', 'has_pei' => true]
            ]);

        $this->driverSkillsRepoMock->method('findByDriver')
            ->with(1)
            ->willReturn([
                ['skill_name' => 'Μεταφορές'],
                ['skill_name' => 'Διανομές']
            ]);

        $this->driverRatingRepoMock->method('getAverageRating')
            ->with(1)
            ->willReturn(4.5);

        // Κλήση της μεθόδου που δοκιμάζουμε
        $result = $this->matchingService->findMatchesForCompany($companyId, $criteria, $page, $limit);

        // Επαλήθευση του αποτελέσματος
        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('pagination', $result);
    }

    /**
     * Δοκιμή για τη μέθοδο getMatchPreferences
     */
    public function testGetMatchPreferences()
    {
        // Δεδομένα δοκιμής
        $userId = 1;
        $userType = 'driver';

        // Ρύθμιση του mock για το Database
        $stmtMock = $this->createMock(\PDOStatement::class);
        $stmtMock->method('fetch')
            ->willReturn([
                'id' => 1,
                'user_id' => 1,
                'user_type' => 'driver',
                'location_weight' => 1.0,
                'job_type_weight' => 1.0,
                'vehicle_type_weight' => 1.0,
                'license_weight' => 1.0,
                'experience_weight' => 1.0,
                'skills_weight' => 1.0,
                'schedule_weight' => 1.0,
                'rating_weight' => 1.0
            ]);

        $this->dbMock->method('query')
            ->willReturn($stmtMock);

        // Κλήση της μεθόδου που δοκιμάζουμε
        $preferences = $this->matchingService->getMatchPreferences($userId, $userType);

        // Επαλήθευση του αποτελέσματος
        $this->assertIsArray($preferences);
        $this->assertArrayHasKey('location_weight', $preferences);
        $this->assertArrayHasKey('job_type_weight', $preferences);
        $this->assertArrayHasKey('vehicle_type_weight', $preferences);
        $this->assertArrayHasKey('license_weight', $preferences);
        $this->assertArrayHasKey('experience_weight', $preferences);
        $this->assertArrayHasKey('skills_weight', $preferences);
        $this->assertArrayHasKey('schedule_weight', $preferences);
        $this->assertArrayHasKey('rating_weight', $preferences);
    }

    /**
     * Δοκιμή για τη μέθοδο saveMatchPreferences
     */
    public function testSaveMatchPreferences()
    {
        // Δεδομένα δοκιμής
        $userId = 1;
        $userType = 'driver';
        $preferences = [
            'location_weight' => 1.0,
            'job_type_weight' => 1.0,
            'vehicle_type_weight' => 1.0,
            'license_weight' => 1.0,
            'experience_weight' => 1.0,
            'skills_weight' => 1.0,
            'schedule_weight' => 1.0,
            'rating_weight' => 1.0
        ];

        // Ρύθμιση του mock για το Database για τον έλεγχο αν υπάρχουν ήδη προτιμήσεις
        $stmtMock1 = $this->createMock(\PDOStatement::class);
        $stmtMock1->method('fetch')
            ->willReturn(['id' => 1]);

        // Ρύθμιση του mock για το Database για την ενημέρωση των προτιμήσεων
        $stmtMock2 = $this->createMock(\PDOStatement::class);
        $stmtMock2->method('rowCount')
            ->willReturn(1);

        $this->dbMock->method('query')
            ->willReturnOnConsecutiveCalls($stmtMock1, $stmtMock2);

        // Κλήση της μεθόδου που δοκιμάζουμε
        $result = $this->matchingService->saveMatchPreferences($userId, $userType, $preferences);

        // Επαλήθευση του αποτελέσματος
        $this->assertTrue($result);
    }

    /**
     * Δοκιμή για τη μέθοδο logMatchAction
     */
    public function testLogMatchAction()
    {
        // Δεδομένα δοκιμής
        $driverId = 1;
        $jobListingId = 1;
        $matchScore = 85.5;
        $driverAction = 'viewed';
        $companyAction = 'no_action';

        // Ρύθμιση του mock για το Database για τον έλεγχο αν υπάρχει ήδη καταγραφή
        $stmtMock1 = $this->createMock(\PDOStatement::class);
        $stmtMock1->method('fetch')
            ->willReturn(['id' => 1]);

        // Ρύθμιση του mock για το Database για την ενημέρωση της καταγραφής
        $stmtMock2 = $this->createMock(\PDOStatement::class);
        $stmtMock2->method('rowCount')
            ->willReturn(1);

        $this->dbMock->method('query')
            ->willReturnOnConsecutiveCalls($stmtMock1, $stmtMock2);

        // Κλήση της μεθόδου που δοκιμάζουμε
        $result = $this->matchingService->logMatchAction(
            $driverId,
            $jobListingId,
            $matchScore,
            $driverAction,
            $companyAction
        );

        // Επαλήθευση του αποτελέσματος
        $this->assertTrue($result);
    }

    /**
     * Δοκιμή για τη μέθοδο getDriverMatchHistory
     */
    public function testGetDriverMatchHistory()
    {
        // Δεδομένα δοκιμής
        $driverId = 1;
        $page = 1;
        $limit = 10;

        // Ρύθμιση του mock για το Database
        $stmtMock1 = $this->createMock(\PDOStatement::class);
        $stmtMock1->method('fetch')
            ->willReturn(['total' => 1]);

        $stmtMock2 = $this->createMock(\PDOStatement::class);
        $stmtMock2->method('fetchAll')
            ->willReturn([
                [
                    'id' => 1,
                    'driver_id' => 1,
                    'job_listing_id' => 1,
                    'match_score' => 85.5,
                    'driver_action' => 'viewed',
                    'company_action' => 'no_action',
                    'title' => 'Οδηγός Φορτηγού',
                    'description' => 'Περιγραφή θέσης',
                    'location' => 'Αθήνα',
                    'job_type' => 'Μεταφορές',
                    'company_id' => 1,
                    'company_name' => 'Μεταφορική ΑΕ'
                ]
            ]);

        $this->dbMock->method('query')
            ->willReturnOnConsecutiveCalls($stmtMock1, $stmtMock2);

        // Κλήση της μεθόδου που δοκιμάζουμε
        $result = $this->matchingService->getDriverMatchHistory($driverId, $page, $limit);

        // Επαλήθευση του αποτελέσματος
        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(1, $result['results']);
    }

    /**
     * Δοκιμή για τη μέθοδο getJobListingMatchHistory
     */
    public function testGetJobListingMatchHistory()
    {
        // Δεδομένα δοκιμής
        $jobListingId = 1;
        $page = 1;
        $limit = 10;

        // Ρύθμιση του mock για το Database
        $stmtMock1 = $this->createMock(\PDOStatement::class);
        $stmtMock1->method('fetch')
            ->willReturn(['total' => 1]);

        $stmtMock2 = $this->createMock(\PDOStatement::class);
        $stmtMock2->method('fetchAll')
            ->willReturn([
                [
                    'id' => 1,
                    'driver_id' => 1,
                    'job_listing_id' => 1,
                    'match_score' => 85.5,
                    'driver_action' => 'viewed',
                    'company_action' => 'no_action',
                    'first_name' => 'Γιώργος',
                    'last_name' => 'Παπαδόπουλος',
                    'email' => 'example@example.com',
                    'phone' => '1234567890',
                    'city' => 'Αθήνα'
                ]
            ]);

        $this->dbMock->method('query')
            ->willReturnOnConsecutiveCalls($stmtMock1, $stmtMock2);

        // Κλήση της μεθόδου που δοκιμάζουμε
        $result = $this->matchingService->getJobListingMatchHistory($jobListingId, $page, $limit);

        // Επαλήθευση του αποτελέσματος
        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(1, $result['results']);
    }
}

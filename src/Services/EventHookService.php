<?php

namespace Drivejob\Services;

use PDO;
use Drivejob\Core\Logger;
use Drivejob\Core\Database;
use Drivejob\Services\RealTimeMatchingService;

/**
 * Event Hook Service
 * Διαχειρίζεται event-driven updates για το matching system
 */
class EventHookService
{
    private PDO $pdo;
    private RealTimeMatchingService $realTimeService;

    public function __construct(
        PDO $pdo = null,
        RealTimeMatchingService $realTimeService = null
    ) {
        $this->pdo = $pdo ?? Database::getInstance()->getConnection();
        $this->realTimeService = $realTimeService ?? new RealTimeMatchingService($this->pdo);
    }

    /**
     * Hook που καλείται όταν ενημερώνεται το προφίλ οδηγού
     */
    public function onDriverProfileUpdate(int $driverId, array $oldData = [], array $newData = []): void
    {
        try {
            Logger::info("Driver profile update hook triggered for driver: {$driverId}");

            // Έλεγχος αν έχουν αλλάξει σημαντικά στοιχεία που επηρεάζουν το matching
            $significantFields = [
                'city',
                'country',
                'available_for_work',
                'experience_years',
                'license_types',
                'skills',
                'certifications',
                'vehicle_experience'
            ];

            $hasSignificantChanges = false;
            foreach ($significantFields as $field) {
                if (isset($oldData[$field]) && isset($newData[$field])) {
                    if ($oldData[$field] !== $newData[$field]) {
                        $hasSignificantChanges = true;
                        break;
                    }
                }
            }

            // Αν υπάρχουν σημαντικές αλλαγές, ενημέρωσε τα matches
            if ($hasSignificantChanges || empty($oldData)) {
                $result = $this->realTimeService->updateDriverMatches($driverId);

                Logger::info("Driver matches updated after profile change", [
                    'driver_id' => $driverId,
                    'updated_matches' => $result['updated_matches'] ?? 0,
                    'high_quality_matches' => $result['high_quality_matches'] ?? 0
                ]);
            }
        } catch (\Exception $e) {
            Logger::error("Error in driver profile update hook: " . $e->getMessage());
        }
    }

    /**
     * Hook που καλείται όταν δημιουργείται νέα αγγελία εργασίας
     */
    public function onJobListingCreated(int $jobId, array $jobData = []): void
    {
        try {
            Logger::info("New job listing hook triggered for job: {$jobId}");

            // Process new job listing για matching
            $result = $this->realTimeService->processNewJobListing($jobId);

            Logger::info("New job listing processed", [
                'job_id' => $jobId,
                'job_title' => $result['job_title'] ?? 'Unknown',
                'matched_drivers' => $result['matched_drivers'] ?? 0,
                'notifications_sent' => $result['notifications_sent'] ?? 0
            ]);
        } catch (\Exception $e) {
            Logger::error("Error in job listing creation hook: " . $e->getMessage());
        }
    }

    /**
     * Hook που καλείται όταν ενημερώνεται αγγελία εργασίας
     */
    public function onJobListingUpdate(int $jobId, array $oldData = [], array $newData = []): void
    {
        try {
            Logger::info("Job listing update hook triggered for job: {$jobId}");

            // Έλεγχος αν έχουν αλλάξει σημαντικά στοιχεία
            $significantFields = [
                'title',
                'location',
                'vehicle_type',
                'required_licenses',
                'salary_min',
                'salary_max',
                'job_type',
                'schedule',
                'required_skills',
                'is_active'
            ];

            $hasSignificantChanges = false;
            foreach ($significantFields as $field) {
                if (isset($oldData[$field]) && isset($newData[$field])) {
                    if ($oldData[$field] !== $newData[$field]) {
                        $hasSignificantChanges = true;
                        break;
                    }
                }
            }

            // Αν υπάρχουν σημαντικές αλλαγές, επανυπολόγισε τα matches
            if ($hasSignificantChanges) {
                $this->recalculateJobMatches($jobId);
            }
        } catch (\Exception $e) {
            Logger::error("Error in job listing update hook: " . $e->getMessage());
        }
    }

    /**
     * Hook που καλείται όταν αλλάζει η διαθεσιμότητα οδηγού
     */
    public function onDriverAvailabilityChange(int $driverId, bool $oldAvailability, bool $newAvailability): void
    {
        try {
            Logger::info("Driver availability change hook triggered", [
                'driver_id' => $driverId,
                'old_availability' => $oldAvailability,
                'new_availability' => $newAvailability
            ]);

            // Αν ο οδηγός έγινε διαθέσιμος, ενημέρωσε τα matches
            if (!$oldAvailability && $newAvailability) {
                $result = $this->realTimeService->updateDriverMatches($driverId);

                Logger::info("Driver matches updated after becoming available", [
                    'driver_id' => $driverId,
                    'updated_matches' => $result['updated_matches'] ?? 0
                ]);
            }
        } catch (\Exception $e) {
            Logger::error("Error in driver availability change hook: " . $e->getMessage());
        }
    }

    /**
     * Hook που καλείται όταν εγγράφεται νέος οδηγός
     */
    public function onDriverRegistration(int $driverId, array $driverData = []): void
    {
        try {
            Logger::info("New driver registration hook triggered for driver: {$driverId}");

            // Καλωσόρισμα email με initial matches
            $this->sendWelcomeEmailWithMatches($driverId);

            // Αρχικός υπολογισμός matches
            $result = $this->realTimeService->updateDriverMatches($driverId);

            Logger::info("New driver matches calculated", [
                'driver_id' => $driverId,
                'initial_matches' => $result['updated_matches'] ?? 0
            ]);
        } catch (\Exception $e) {
            Logger::error("Error in driver registration hook: " . $e->getMessage());
        }
    }

    /**
     * Hook που καλείται όταν εγγράφεται νέα εταιρία
     */
    public function onCompanyRegistration(int $companyId, array $companyData = []): void
    {
        try {
            Logger::info("New company registration hook triggered for company: {$companyId}");

            // Καλωσόρισμα email με πληροφορίες για το matching system
            $this->sendCompanyWelcomeEmail($companyId);
        } catch (\Exception $e) {
            Logger::error("Error in company registration hook: " . $e->getMessage());
        }
    }

    /**
     * Επανυπολογισμός matches για συγκεκριμένη αγγελία
     */
    private function recalculateJobMatches(int $jobId): void
    {
        try {
            // Λήψη όλων των ενεργών οδηγών
            $stmt = $this->pdo->prepare("
                SELECT id FROM drivers 
                WHERE is_active = 1 AND available_for_work = 1
            ");
            $stmt->execute();
            $drivers = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $updatedCount = 0;
            foreach ($drivers as $driverId) {
                // Επανυπολογισμός score για κάθε οδηγό
                $matchingService = new \Drivejob\Services\EnhancedMatchingService($this->pdo);
                $score = $matchingService->calculateMatchScore($driverId, $jobId);
                if ($score > 0) {
                    $updatedCount++;
                }
            }

            Logger::info("Recalculated matches for job {$jobId}: {$updatedCount} drivers updated");
        } catch (\Exception $e) {
            Logger::error("Error recalculating job matches: " . $e->getMessage());
        }
    }

    /**
     * Αποστολή καλωσορίσματος με matches σε νέο οδηγό
     */
    private function sendWelcomeEmailWithMatches(int $driverId): void
    {
        try {
            // Λήψη στοιχείων οδηγού
            $stmt = $this->pdo->prepare("
                SELECT d.*, u.email
                FROM drivers d
                JOIN users u ON d.user_id = u.id
                WHERE d.id = ?
            ");
            $stmt->execute([$driverId]);
            $driver = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$driver || !$driver['email']) {
                return;
            }

            // Λήψη top matches
            $matchingService = new \Drivejob\Services\EnhancedMatchingService($this->pdo);
            $matches = $matchingService->getTopMatchesForDriver($driverId, 5);

            if (!empty($matches)) {
                // Δημιουργία welcome email με matches
                $subject = "🎉 Καλώς ήρθατε στο DriveJob - Βρήκαμε εργασίες για εσάς!";
                $emailContent = $this->generateWelcomeEmailTemplate($driver, $matches);

                // Αποστολή email (θα χρησιμοποιήσουμε το notification service)
                $emailService = new EmailService(
                    'smtp.thessdrive.gr',
                    587,
                    'info@thessdrive.gr',
                    'inf1q2w!Q@W',
                    'info@thessdrive.gr',
                    'DriveJob Team'
                );

                $emailService->send($driver['email'], $subject, $emailContent);
                Logger::info("Welcome email sent to new driver: {$driverId}");
            }
        } catch (\Exception $e) {
            Logger::error("Error sending welcome email: " . $e->getMessage());
        }
    }

    /**
     * Αποστολή καλωσορίσματος σε νέα εταιρία
     */
    private function sendCompanyWelcomeEmail(int $companyId): void
    {
        try {
            // Λήψη στοιχείων εταιρίας
            $stmt = $this->pdo->prepare("
                SELECT * FROM companies WHERE id = ?
            ");
            $stmt->execute([$companyId]);
            $company = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$company || !$company['email']) {
                return;
            }

            $subject = "🎉 Καλώς ήρθατε στο DriveJob - Βρείτε τους καλύτερους οδηγούς!";
            $emailContent = $this->generateCompanyWelcomeEmailTemplate($company);

            // Αποστολή email
            $emailService = new EmailService(
                'smtp.thessdrive.gr',
                587,
                'info@thessdrive.gr',
                'inf1q2w!Q@W',
                'info@thessdrive.gr',
                'DriveJob Team'
            );

            $emailService->send($company['email'], $subject, $emailContent);
            Logger::info("Welcome email sent to new company: {$companyId}");
        } catch (\Exception $e) {
            Logger::error("Error sending company welcome email: " . $e->getMessage());
        }
    }

    /**
     * Template για welcome email οδηγού
     */
    private function generateWelcomeEmailTemplate(array $driver, array $matches): string
    {
        $driverName = $driver['first_name'];
        $matchesCount = count($matches);

        $matchesHtml = '';
        foreach (array_slice($matches, 0, 3) as $match) {
            $matchesHtml .= "
                <div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px;'>
                    <h4 style='margin: 0 0 10px 0; color: #333;'>{$match['title']}</h4>
                    <p style='margin: 5px 0; color: #666;'><strong>Εταιρία:</strong> {$match['company_name']}</p>
                    <p style='margin: 5px 0; color: #666;'><strong>Τοποθεσία:</strong> {$match['location']}</p>
                    <a href='https://drivejob.gr/job-listings/show/{$match['id']}' style='color: #667eea; text-decoration: none; font-weight: bold;'>Δείτε λεπτομέρειες →</a>
                </div>
            ";
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Καλώς ήρθατε στο DriveJob</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; }
                .content { padding: 30px 20px; }
                .cta-button { display: inline-block; background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; margin: 20px 0; font-weight: bold; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>🎉 Καλώς ήρθατε στο DriveJob!</h1>
                <p>Η πλατφόρμα που συνδέει οδηγούς με εταιρίες</p>
            </div>
            <div class='content'>
                <p>Γεια σας {$driverName},</p>
                
                <p>Καλώς ήρθατε στο DriveJob! Είμαστε ενθουσιασμένοι που εγγραφήκατε στην πλατφόρμα μας.</p>
                
                <p>Το AI σύστημά μας ήδη ανέλυσε το προφίλ σας και βρήκε <strong>{$matchesCount} εργασίες</strong> που μπορεί να σας ενδιαφέρουν:</p>
                
                {$matchesHtml}
                
                " . ($matchesCount > 3 ? "<p><em>Και " . ($matchesCount - 3) . " ακόμα εργασίες σας περιμένουν!</em></p>" : "") . "
                
                <div style='text-align: center;'>
                    <a href='https://drivejob.gr/drivers/profile' class='cta-button'>Δείτε το Προφίλ σας</a>
                </div>
                
                <h3>Τι μπορείτε να κάνετε στο DriveJob:</h3>
                <ul>
                    <li>🔍 Βρείτε εργασίες που ταιριάζουν στο προφίλ σας</li>
                    <li>📧 Λάβετε ειδοποιήσεις για νέες αγγελίες</li>
                    <li>💼 Διαχειριστείτε τις αιτήσεις σας</li>
                    <li>⭐ Δημιουργήστε το επαγγελματικό σας προφίλ</li>
                </ul>
            </div>
            <div class='footer'>
                <p>Αν έχετε οποιαδήποτε ερώτηση, μη διστάσετε να επικοινωνήσετε μαζί μας.</p>
                <p>&copy; " . date('Y') . " DriveJob. Όλα τα δικαιώματα διατηρούνται.</p>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Template για welcome email εταιρίας
     */
    private function generateCompanyWelcomeEmailTemplate(array $company): string
    {
        $companyName = $company['company_name'];

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Καλώς ήρθατε στο DriveJob</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; }
                .content { padding: 30px 20px; }
                .cta-button { display: inline-block; background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; margin: 20px 0; font-weight: bold; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>🎉 Καλώς ήρθατε στο DriveJob!</h1>
                <p>Βρείτε τους καλύτερους οδηγούς για την εταιρία σας</p>
            </div>
            <div class='content'>
                <p>Γεια σας {$companyName},</p>
                
                <p>Καλώς ήρθατε στο DriveJob! Είμαστε ενθουσιασμένοι που εγγραφήκατε στην πλατφόρμα μας.</p>
                
                <h3>Τι μπορείτε να κάνετε στο DriveJob:</h3>
                <ul>
                    <li>📝 Δημοσιεύστε αγγελίες εργασίας</li>
                    <li>🤖 Χρησιμοποιήστε το AI matching για να βρείτε τους καλύτερους οδηγούς</li>
                    <li>📊 Παρακολουθήστε τις αιτήσεις και τα ταιριάσματα</li>
                    <li>💬 Επικοινωνήστε απευθείας με τους υποψηφίους</li>
                    <li>⭐ Αξιολογήστε και λάβετε αξιολογήσεις</li>
                </ul>
                
                <div style='text-align: center;'>
                    <a href='https://drivejob.gr/companies/profile' class='cta-button'>Δημιουργήστε την πρώτη σας αγγελία</a>
                </div>
                
                <h3>🤖 AI Matching System</h3>
                <p>Το προηγμένο AI σύστημά μας αναλύει αυτόματα τις αγγελίες σας και προτείνει τους καταλληλότερους οδηγούς βάσει:</p>
                <ul>
                    <li>Εμπειρίας και δεξιοτήτων</li>
                    <li>Γεωγραφικής θέσης</li>
                    <li>Διαθεσιμότητας</li>
                    <li>Τύπου οχήματος και αδειών</li>
                </ul>
            </div>
            <div class='footer'>
                <p>Αν έχετε οποιαδήποτε ερώτηση, μη διστάσετε να επικοινωνήσετε μαζί μας.</p>
                <p>&copy; " . date('Y') . " DriveJob. Όλα τα δικαιώματα διατηρούνται.</p>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Batch processing για παλιά δεδομένα
     */
    public function batchProcessExistingData(): array
    {
        try {
            Logger::info("Starting batch processing of existing data");

            $result = $this->realTimeService->batchUpdateAllMatches();

            Logger::info("Batch processing completed", $result);

            return $result;
        } catch (\Exception $e) {
            Logger::error("Error in batch processing: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Getter για το RealTimeMatchingService
     */
    public function getRealTimeService(): RealTimeMatchingService
    {
        return $this->realTimeService;
    }
}

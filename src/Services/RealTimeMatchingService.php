<?php

namespace Drivejob\Services;

use PDO;
use Drivejob\Core\Logger;
use Drivejob\Core\Database;
use Drivejob\Services\EnhancedMatchingService;
use Drivejob\Services\NotificationServices;
use Drivejob\Services\EmailService;
use Drivejob\Services\SmsService;
use Drivejob\Helpers\JsonHelper;

/**
 * Real-Time Matching Service
 * Διαχειρίζεται real-time updates για matching scores και ειδοποιήσεις
 */
class RealTimeMatchingService
{
    private PDO $pdo;
    private EnhancedMatchingService $matchingService;
    private NotificationServices $notificationService;
    private EmailService $emailService;
    private SmsService $smsService;
    private array $config;

    public function __construct(
        ?PDO $pdo = null,
        ?EnhancedMatchingService $matchingService = null,
        ?NotificationServices $notificationService = null
    ) {
        $this->pdo = $pdo ?? Database::getInstance()->getConnection();
        $this->matchingService = $matchingService ?? new EnhancedMatchingService($this->pdo);

        // Load notification config
        $this->config = require ROOT_DIR . '/config/notifications.php';

        // Initialize notification services
        $this->emailService = new EmailService(
            $this->config['smtp_host'],
            $this->config['smtp_port'],
            $this->config['smtp_username'],
            $this->config['smtp_password'],
            $this->config['sender_email'],
            $this->config['sender_name'],
            $this->config['debug_mode'] ?? false
        );
        $this->smsService = new SmsService(
            $this->config['sms_api_key'],
            $this->config['sms_api_url'],
            $this->config['sms_sender'] ?? 'DriveJob',
            $this->config['debug_mode'] ?? false
        );
        $this->notificationService = $notificationService ?? new NotificationServices(
            $this->pdo,
            $this->emailService,
            $this->smsService,
            $this->config
        );
    }

    /**
     * Ενημερώνει όλα τα matching scores όταν αλλάζει το προφίλ οδηγού
     */
    public function updateDriverMatches(int $driverId): array
    {
        try {
            Logger::info("Starting real-time driver matches update for driver: {$driverId}");

            // Λήψη όλων των ενεργών αγγελιών
            $stmt = $this->pdo->prepare("
                SELECT j.*, c.company_name, c.city as company_city
                FROM job_listings j
                JOIN companies c ON j.company_id = c.id
                WHERE j.is_active = 1
                ORDER BY j.created_at DESC
            ");
            $stmt->execute();
            $jobListings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $updatedMatches = [];
            $highQualityMatches = [];

            foreach ($jobListings as $job) {
                // Υπολογισμός νέου score
                $newScore = $this->matchingService->calculateMatchScore($driverId, $job);

                if ($newScore > 0) {
                    $updatedMatches[] = [
                        'job_id' => $job['id'],
                        'job_title' => $job['title'],
                        'company_name' => $job['company_name'],
                        'score' => $newScore
                    ];

                    // Αν το score είναι υψηλό (>70%), το θεωρούμε high-quality match
                    if ($newScore >= 70) {
                        $highQualityMatches[] = [
                            'job_id' => $job['id'],
                            'job_title' => $job['title'],
                            'company_name' => $job['company_name'],
                            'score' => $newScore,
                            'job_data' => $job
                        ];
                    }
                }
            }

            // Αποστολή ειδοποιήσεων για high-quality matches
            if (!empty($highQualityMatches)) {
                $this->sendMatchNotifications($driverId, $highQualityMatches, 'profile_update');
            }

            Logger::info("Updated {count} matches for driver {driverId}, {highCount} high-quality", [
                'count' => count($updatedMatches),
                'driverId' => $driverId,
                'highCount' => count($highQualityMatches)
            ]);

            return [
                'success' => true,
                'updated_matches' => count($updatedMatches),
                'high_quality_matches' => count($highQualityMatches),
                'matches' => $updatedMatches
            ];
        } catch (\Exception $e) {
            Logger::error("Error updating driver matches: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Ενημερώνει όλα τα matching scores όταν δημιουργείται νέα αγγελία
     */
    public function processNewJobListing(int $jobId): array
    {
        try {
            Logger::info("Processing new job listing: {$jobId}");

            // Λήψη στοιχείων αγγελίας
            $stmt = $this->pdo->prepare("
                SELECT j.*, c.company_name, c.city as company_city, c.email as company_email
                FROM job_listings j
                JOIN companies c ON j.company_id = c.id
                WHERE j.id = ?
            ");
            $stmt->execute([$jobId]);
            $jobListing = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$jobListing) {
                throw new \Exception("Job listing not found: {$jobId}");
            }

            // Λήψη όλων των ενεργών οδηγών
            $stmt = $this->pdo->prepare("
                SELECT d.*, u.email
                FROM drivers d
                JOIN users u ON d.user_id = u.id
                WHERE d.is_verified = 1 AND d.available_for_work = 1
                ORDER BY d.last_login DESC
            ");
            $stmt->execute();
            $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $matchedDrivers = [];
            $notificationsSent = 0;

            foreach ($drivers as $driver) {
                // Υπολογισμός matching score
                $score = $this->matchingService->calculateMatchScore($driver, $jobListing);

                if ($score > 0) {
                    $matchedDrivers[] = [
                        'driver_id' => $driver['id'],
                        'driver_name' => $driver['first_name'] . ' ' . $driver['last_name'],
                        'score' => $score
                    ];

                    // Αποστολή ειδοποίησης για high-quality matches (>60%)
                    if ($score >= 60) {
                        $sent = $this->sendNewJobNotification($driver, $jobListing, $score);
                        if ($sent) {
                            $notificationsSent++;
                        }
                    }
                }
            }

            // Ταξινόμηση κατά score
            usort($matchedDrivers, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            Logger::info("Processed new job listing {jobId}: {matches} matches, {notifications} notifications", [
                'jobId' => $jobId,
                'matches' => count($matchedDrivers),
                'notifications' => $notificationsSent
            ]);

            return [
                'success' => true,
                'job_title' => $jobListing['title'],
                'company_name' => $jobListing['company_name'],
                'matched_drivers' => count($matchedDrivers),
                'notifications_sent' => $notificationsSent,
                'top_matches' => array_slice($matchedDrivers, 0, 10)
            ];
        } catch (\Exception $e) {
            Logger::error("Error processing new job listing: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Αποστολή ειδοποίησης για νέα αγγελία σε οδηγό
     */
    private function sendNewJobNotification(array $driver, array $jobListing, float $score): bool
    {
        try {
            $driverName = $driver['first_name'] . ' ' . $driver['last_name'];
            $email = $driver['email'];
            $phone = $driver['phone'] ?? null;

            if (!$email) {
                Logger::warning("No email for driver {$driver['id']}, skipping notification");
                return false;
            }

            // Δημιουργία email
            $subject = "🎯 Νέα Αγγελία Εργασίας - {$score}% Ταίριασμα!";
            $emailContent = $this->generateNewJobEmailTemplate($driver, $jobListing, $score);

            // Αποστολή email
            $emailSent = $this->emailService->send($email, $subject, $emailContent);

            // Αποστολή SMS για πολύ υψηλά scores (>80%)
            $smsSent = false;
            if ($phone && $score >= 80) {
                $smsMessage = "DriveJob: Νέα αγγελία {$score}% ταίριασμα! '{$jobListing['title']}' από {$jobListing['company_name']}. Δείτε: drivejob.gr";
                $smsSent = $this->smsService->sendSms($phone, $smsMessage);
            }

            // Καταγραφή ειδοποίησης
            if ($emailSent || $smsSent) {
                $this->notificationService->recordNotification(
                    'new_job_match',
                    $driver['id'],
                    'driver',
                    [
                        'job_id' => $jobListing['id'],
                        'job_title' => $jobListing['title'],
                        'company_name' => $jobListing['company_name'],
                        'match_score' => $score
                    ],
                    $smsSent ? ($emailSent ? 'both' : 'sms') : 'email'
                );
            }

            Logger::info("Sent new job notification to driver {$driver['id']}: email={$emailSent}, sms={$smsSent}");
            return $emailSent || $smsSent;
        } catch (\Exception $e) {
            Logger::error("Error sending new job notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Αποστολή ειδοποιήσεων για ενημερωμένα matches
     */
    private function sendMatchNotifications(int $driverId, array $matches, string $reason): bool
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
                return false;
            }

            $subject = "🔄 Ενημερωμένα Ταιριάσματα Εργασίας";
            $emailContent = $this->generateMatchUpdateEmailTemplate($driver, $matches, $reason);

            $sent = $this->emailService->send($driver['email'], $subject, $emailContent);

            if ($sent) {
                $this->notificationService->recordNotification(
                    'match_update',
                    $driverId,
                    'driver',
                    [
                        'reason' => $reason,
                        'matches_count' => count($matches),
                        'top_score' => max(array_column($matches, 'score'))
                    ],
                    'email'
                );
            }

            return $sent;
        } catch (\Exception $e) {
            Logger::error("Error sending match notifications: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Template για email νέας αγγελίας
     */
    private function generateNewJobEmailTemplate(array $driver, array $jobListing, float $score): string
    {
        $driverName = $driver['first_name'];
        $jobTitle = htmlspecialchars($jobListing['title']);
        $companyName = htmlspecialchars($jobListing['company_name']);
        $location = htmlspecialchars($jobListing['location'] ?? $jobListing['company_city'] ?? '');
        $scoreColor = $score >= 80 ? '#27ae60' : ($score >= 60 ? '#f39c12' : '#e74c3c');
        $jobUrl = "https://drivejob.gr/job-listings/show/{$jobListing['id']}";

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Νέα Αγγελία Εργασίας - DriveJob</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; }
                .content { padding: 30px 20px; }
                .match-score { background: {$scoreColor}; color: white; padding: 15px; border-radius: 10px; text-align: center; margin: 20px 0; }
                .job-details { background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0; }
                .cta-button { display: inline-block; background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; margin: 20px 0; font-weight: bold; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>🎯 Νέα Αγγελία Εργασίας!</h1>
                <p>Βρήκαμε μια εργασία που ταιριάζει στο προφίλ σας</p>
            </div>
            <div class='content'>
                <p>Γεια σας {$driverName},</p>
                
                <div class='match-score'>
                    <h2>{$score}% Ταίριασμα!</h2>
                    <p>Αυτή η αγγελία ταιριάζει εξαιρετικά με το προφίλ σας</p>
                </div>
                
                <div class='job-details'>
                    <h3>{$jobTitle}</h3>
                    <p><strong>Εταιρία:</strong> {$companyName}</p>
                    <p><strong>Τοποθεσία:</strong> {$location}</p>
                    <p><strong>Δημοσιεύθηκε:</strong> " . date('d/m/Y H:i', strtotime($jobListing['created_at'])) . "</p>
                </div>
                
                <p>Το AI σύστημά μας ανέλυσε το προφίλ σας και βρήκε ότι αυτή η θέση εργασίας ταιριάζει εξαιρετικά με τις δεξιότητές σας, την εμπειρία σας και τις προτιμήσεις σας.</p>
                
                <div style='text-align: center;'>
                    <a href='{$jobUrl}' class='cta-button'>Δείτε την Αγγελία</a>
                </div>
                
                <p><small>💡 <strong>Συμβουλή:</strong> Οι αγγελίες με υψηλό ταίριασμα συνήθως γεμίζουν γρήγορα. Μην χάσετε την ευκαιρία!</small></p>
            </div>
            <div class='footer'>
                <p>Λάβατε αυτό το email επειδή έχετε ενεργοποιημένες τις ειδοποιήσεις για νέες αγγελίες.</p>
                <p><a href='https://drivejob.gr/drivers/profile'>Διαχείριση Ειδοποιήσεων</a></p>
                <p>&copy; " . date('Y') . " DriveJob. Όλα τα δικαιώματα διατηρούνται.</p>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Template για email ενημερωμένων matches
     */
    private function generateMatchUpdateEmailTemplate(array $driver, array $matches, string $reason): string
    {
        $driverName = $driver['first_name'];
        $matchesCount = count($matches);
        $reasonText = $reason === 'profile_update' ? 'ενημερώσατε το προφίλ σας' : 'προστέθηκαν νέες αγγελίες';

        $matchesHtml = '';
        foreach (array_slice($matches, 0, 5) as $match) {
            $scoreColor = $match['score'] >= 80 ? '#27ae60' : ($match['score'] >= 60 ? '#f39c12' : '#e74c3c');
            $matchesHtml .= "
                <div style='border-left: 4px solid {$scoreColor}; padding: 15px; margin: 10px 0; background: #f8f9fa;'>
                    <h4 style='margin: 0 0 10px 0;'>{$match['job_title']}</h4>
                    <p style='margin: 5px 0;'><strong>Εταιρία:</strong> {$match['company_name']}</p>
                    <p style='margin: 5px 0;'><strong>Ταίριασμα:</strong> <span style='color: {$scoreColor}; font-weight: bold;'>{$match['score']}%</span></p>
                    <a href='https://drivejob.gr/job-listings/show/{$match['job_id']}' style='color: #667eea; text-decoration: none;'>Δείτε λεπτομέρειες →</a>
                </div>
            ";
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Ενημερωμένα Ταιριάσματα - DriveJob</title>
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
                <h1>🔄 Ενημερωμένα Ταιριάσματα</h1>
                <p>Βρήκαμε {$matchesCount} νέα ταιριάσματα για εσάς</p>
            </div>
            <div class='content'>
                <p>Γεια σας {$driverName},</p>
                
                <p>Επειδή {$reasonText}, το AI σύστημά μας ανακάλυψε νέα ταιριάσματα εργασίας που μπορεί να σας ενδιαφέρουν:</p>
                
                {$matchesHtml}
                
                " . ($matchesCount > 5 ? "<p><em>Και " . ($matchesCount - 5) . " ακόμα ταιριάσματα...</em></p>" : "") . "
                
                <div style='text-align: center;'>
                    <a href='https://drivejob.gr/drivers/profile#job-matches' class='cta-button'>Δείτε Όλα τα Ταιριάσματα</a>
                </div>
            </div>
            <div class='footer'>
                <p>Λάβατε αυτό το email επειδή έχετε ενεργοποιημένες τις ειδοποιήσεις ταιριασμάτων.</p>
                <p><a href='https://drivejob.gr/drivers/profile'>Διαχείριση Ειδοποιήσεων</a></p>
                <p>&copy; " . date('Y') . " DriveJob. Όλα τα δικαιώματα διατηρούνται.</p>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Batch update όλων των matching scores
     */
    public function batchUpdateAllMatches(): array
    {
        try {
            Logger::info("Starting batch update of all matching scores");

            // Λήψη όλων των ενεργών οδηγών
            $stmt = $this->pdo->prepare("
                SELECT id FROM drivers WHERE is_active = 1 AND available_for_work = 1
            ");
            $stmt->execute();
            $drivers = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Λήψη όλων των ενεργών αγγελιών
            $stmt = $this->pdo->prepare("
                SELECT id FROM job_listings WHERE is_active = 1
            ");
            $stmt->execute();
            $jobs = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $totalUpdates = 0;
            $batchSize = 50;

            foreach (array_chunk($drivers, $batchSize) as $driverBatch) {
                foreach ($driverBatch as $driverId) {
                    foreach ($jobs as $jobId) {
                        $score = $this->matchingService->calculateMatchScore($driverId, $jobId);
                        if ($score > 0) {
                            $totalUpdates++;
                        }
                    }
                }

                // Small delay to prevent overwhelming the system
                usleep(100000); // 0.1 second
            }

            Logger::info("Batch update completed: {$totalUpdates} scores updated");

            return [
                'success' => true,
                'drivers_processed' => count($drivers),
                'jobs_processed' => count($jobs),
                'total_updates' => $totalUpdates
            ];
        } catch (\Exception $e) {
            Logger::error("Error in batch update: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Καθαρισμός παλιών matching scores
     */
    public function cleanupOldScores(int $daysOld = 30): int
    {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM matching_scores 
                WHERE updated_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$daysOld, $daysOld]);

            $deletedRows = $stmt->rowCount();
            Logger::info("Cleaned up {$deletedRows} old matching scores");

            return $deletedRows;
        } catch (\Exception $e) {
            Logger::error("Error cleaning up old scores: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Στατιστικά matching system
     */
    public function getMatchingStats(): array
    {
        try {
            $stats = [];

            // Total matches
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM matching_scores WHERE overall_score > 0");
            $stats['total_matches'] = $stmt->fetchColumn();

            // High quality matches (>70%)
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM matching_scores WHERE overall_score >= 70");
            $stats['high_quality_matches'] = $stmt->fetchColumn();

            // Average score
            $stmt = $this->pdo->query("SELECT AVG(overall_score) FROM matching_scores WHERE overall_score > 0");
            $stats['average_score'] = round($stmt->fetchColumn(), 2);

            // Recent updates (last 24 hours)
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM matching_scores WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $stats['recent_updates'] = $stmt->fetchColumn();

            // Score distribution
            $stmt = $this->pdo->query("
                SELECT 
                    CASE 
                        WHEN overall_score >= 90 THEN '90-100%'
                        WHEN overall_score >= 80 THEN '80-89%'
                        WHEN overall_score >= 70 THEN '70-79%'
                        WHEN overall_score >= 60 THEN '60-69%'
                        WHEN overall_score >= 50 THEN '50-59%'
                        WHEN overall_score >= 40 THEN '40-49%'
                        WHEN overall_score >= 30 THEN '30-39%'
                        ELSE '0-29%'
                    END as score_range,
                    COUNT(*) as count
                FROM matching_scores 
                WHERE overall_score > 0
                GROUP BY score_range
                ORDER BY MIN(overall_score) DESC
            ");
            $stats['score_distribution'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            return $stats;
        } catch (\Exception $e) {
            Logger::error("Error getting matching stats: " . $e->getMessage());
            return [];
        }
    }
}

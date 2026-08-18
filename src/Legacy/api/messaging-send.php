<?php
require_once dirname(__DIR__, 3) . '/src/bootstrap.php';

// Load email configuration
require_once dirname(__DIR__, 3) . '/config/email.php';

use Drivejob\Core\Session;
use Drivejob\Core\JsonResponse;
use Drivejob\Services\MessagingService;
use Drivejob\Services\EmailService;
use Drivejob\Core\Database;

// Start session
Session::start();

// Check if user is logged in and is a company
if (!Session::has('user_id') || Session::get('user_role') !== 'company') {
    JsonResponse::error('Unauthorized access', 401);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
$driverId = $input['driver_id'] ?? null;
$jobId = $input['job_id'] ?? null;
$subject = $input['subject'] ?? '';
$message = $input['message'] ?? '';
$sendEmail = $input['send_email'] ?? true; // Default to true

if (!$driverId || !$jobId || !$subject || !$message) {
    JsonResponse::error('Missing required fields');
}

try {
    $pdo = Database::getInstance()->getConnection();
    $companyId = Session::get('user_id');

    // Get company details
    $stmt = $pdo->prepare("SELECT company_name, email FROM companies WHERE id = ?");
    $stmt->execute([$companyId]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        JsonResponse::error('Company not found');
    }

    // Get driver details
    $stmt = $pdo->prepare("SELECT first_name, last_name, email FROM drivers WHERE id = ?");
    $stmt->execute([$driverId]);
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$driver) {
        JsonResponse::error('Driver not found');
    }

    // Get job details
    $stmt = $pdo->prepare("SELECT title FROM job_listings WHERE id = ? AND company_id = ?");
    $stmt->execute([$jobId, $companyId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        JsonResponse::error('Job listing not found or unauthorized');
    }

    // Create messaging service instance
    $messagingService = new MessagingService();

    // Start conversation or send message
    $conversationId = null;

    // Check if conversation already exists
    $stmt = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE company_id = ? AND driver_id = ? AND job_id = ?
        AND status = 'active'
    ");
    $stmt->execute([$companyId, $driverId, $jobId]);
    $existingConversation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingConversation) {
        // Send message to existing conversation
        $conversationId = $existingConversation['id'];
        $messagingService->sendMessage($conversationId, 'company', $companyId, $message);
    } else {
        // Start new conversation
        $conversationId = $messagingService->startConversation($companyId, $driverId, $jobId, $subject, $message);
    }

    // Send email notification if requested
    $emailSent = false;
    if ($sendEmail && !empty($driver['email'])) {
        try {
            // Initialize EmailService with config
            $emailService = new EmailService(
                SMTP_HOST,
                SMTP_PORT,
                SMTP_USERNAME,
                SMTP_PASSWORD,
                SMTP_FROM_EMAIL,
                SMTP_FROM_NAME,
                EMAIL_DEBUG
            );

            // Create email content
            $driverName = $driver['first_name'] . ' ' . $driver['last_name'];
            $emailSubject = "Νέο μήνυμα από {$company['company_name']} - DriveJob";

            $emailBody = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
                    .content { background-color: #f8f9fa; padding: 20px; margin-top: 20px; }
                    .message-box { background-color: white; padding: 15px; border-radius: 5px; margin-top: 15px; }
                    .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
                    .button { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>DriveJob</h1>
                        <p>Νέο μήνυμα από εταιρεία</p>
                    </div>
                    <div class='content'>
                        <p>Γεια σας {$driverName},</p>
                        <p>Έχετε λάβει νέο μήνυμα από την εταιρεία <strong>{$company['company_name']}</strong> σχετικά με τη θέση <strong>{$job['title']}</strong>.</p>
                        
                        <div class='message-box'>
                            <h3>Θέμα: {$subject}</h3>
                            <p>" . nl2br(htmlspecialchars($message)) . "</p>
                        </div>
                        
                        <p>Για να απαντήσετε στο μήνυμα, συνδεθείτε στον λογαριασμό σας στο DriveJob.</p>
                        
                        <center>
                            <a href='" . BASE_URL . "login.php' class='button'>Σύνδεση στο DriveJob</a>
                        </center>
                    </div>
                    <div class='footer'>
                        <p>Αυτό το email στάλθηκε από την πλατφόρμα DriveJob.<br>
                        Αν δεν περιμένατε αυτό το μήνυμα, παρακαλούμε αγνοήστε το.</p>
                    </div>
                </div>
            </body>
            </html>
            ";

            // Send email
            $emailSent = $emailService->send($driver['email'], $emailSubject, $emailBody);

            if (!$emailSent) {
                error_log("Failed to send email notification to driver {$driverId}");
            }
        } catch (Exception $e) {
            // Log email error but don't fail the whole operation
            error_log("Email notification error: " . $e->getMessage());
        }
    }

    // Return success response
    JsonResponse::success([
        'conversation_id' => $conversationId,
        'message' => 'Το μήνυμα στάλθηκε επιτυχώς',
        'email_sent' => $emailSent
    ]);
} catch (Exception $e) {
    error_log("Messaging API Error: " . $e->getMessage());
    JsonResponse::error('Σφάλμα κατά την αποστολή του μηνύματος: ' . $e->getMessage());
}

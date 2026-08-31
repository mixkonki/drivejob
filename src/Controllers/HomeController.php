<?php

namespace Drivejob\Controllers;

use Drivejob\Core\Logger;
use Drivejob\Core\Session;
use Drivejob\Core\CSRF;
use Drivejob\Services\EmailService;

class HomeController extends BaseController
{
    public function renderHomePage()
    {
        $configFile = dirname(__DIR__, 2) . '/config/config.php';

        if (file_exists($configFile)) {
            require_once $configFile;
        } else {
            echo "Το αρχείο ρυθμίσεων (config.php) δεν βρέθηκε. Επικοινωνήστε με τον διαχειριστή.";
            return;
        }

        /*
         * Ζωντανά νούμερα για την αρχική (01/09/2026): πραγματικές μετρήσεις
         * αντί για διαφημιστικές υποσχέσεις. Αν η βάση δεν απαντά, η αρχική
         * ΔΕΝ πέφτει — απλώς δεν δείχνει αριθμούς.
         */
        $homeStats = null;
        try {
            $pdo = $this->pdo ?: \Drivejob\Core\Database::getInstance()->getConnection();
            $homeStats = $pdo->query(
                "SELECT
                    (SELECT COUNT(*) FROM job_listings
                      WHERE is_active = 1 AND listing_type = 'job_offer'
                        AND (expires_at IS NULL OR expires_at > NOW())) AS listings,
                    (SELECT COUNT(*) FROM companies WHERE is_active = 1) AS companies,
                    (SELECT COUNT(*) FROM drivers  WHERE is_active = 1) AS drivers"
            )->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            Logger::warning('Αρχική: αδυναμία φόρτωσης στατιστικών — ' . $e->getMessage());
        }

        $viewFile = dirname(__DIR__, 2) . '/public/index.view.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo "Η αρχική σελίδα δεν βρέθηκε. Επικοινωνήστε με τον διαχειριστή.";
        }
    }

    /**
     * Σελίδα «Σχετικά με Εμάς» — route: GET /about
     */
    public function about()
    {
        $this->view('info/about');
    }

    /**
     * Σελίδα Επικοινωνίας — route: GET /contact
     */
    public function contact()
    {
        $this->view('info/contact');
    }

    /**
     * Όροι Χρήσης — route: GET /terms
     */
    public function terms()
    {
        $this->view('info/terms');
    }

    /**
     * Πολιτική Απορρήτου — route: GET /privacy
     */
    public function privacy()
    {
        $this->view('info/privacy');
    }

    /**
     * Συχνές Ερωτήσεις — route: GET /faq
     */
    public function faq()
    {
        $this->view('info/faq');
    }

    /**
     * Υποβολή φόρμας επικοινωνίας — route: POST /contact
     */
    public function submitContactForm()
    {
        // CSRF προστασία
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            $this->redirect(BASE_URL . 'contact');
            return;
        }

        $name = trim($this->sanitize($_POST['name'] ?? ''));
        $email = $this->sanitizeEmail($_POST['email'] ?? '');
        $message = trim($this->sanitize($_POST['message'] ?? ''));

        if ($name === '' || !$email || $message === '') {
            Session::set('error_message', 'Παρακαλώ συμπληρώστε σωστά όλα τα πεδία.');
            Session::set('old_input', $_POST);
            $this->redirect(BASE_URL . 'contact');
            return;
        }

        try {
            if (defined('SMTP_HOST') && SMTP_HOST !== '') {
                $emailService = new EmailService(
                    SMTP_HOST,
                    SMTP_PORT,
                    SMTP_USERNAME,
                    SMTP_PASSWORD,
                    SMTP_FROM_EMAIL,
                    SMTP_FROM_NAME,
                    defined('EMAIL_DEBUG') ? EMAIL_DEBUG : false
                );

                $body = '<h2>Νέο μήνυμα από τη φόρμα επικοινωνίας DriveJob</h2>'
                    . '<p><strong>Όνομα:</strong> ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</p>'
                    . '<p><strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</p>'
                    . '<p><strong>Μήνυμα:</strong></p>'
                    . '<p>' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</p>';

                $emailService->send(
                    SMTP_FROM_EMAIL,
                    'DriveJob — Νέο μήνυμα επικοινωνίας από ' . $name,
                    $body
                );
            } else {
                Logger::warning('Φόρμα επικοινωνίας: SMTP μη ρυθμισμένο, το μήνυμα καταγράφεται μόνο.', [
                    'name' => $name,
                    'email' => $email,
                    'message' => $message,
                ]);
            }

            Logger::info('Υποβολή φόρμας επικοινωνίας', ['name' => $name, 'email' => $email]);
            Session::set('success_message', 'Το μήνυμά σας εστάλη. Θα επικοινωνήσουμε μαζί σας σύντομα.');
        } catch (\Exception $e) {
            Logger::error('Αποτυχία αποστολής φόρμας επικοινωνίας: ' . $e->getMessage());
            Session::set('error_message', 'Δεν ήταν δυνατή η αποστολή του μηνύματος. Δοκιμάστε ξανά αργότερα.');
        }

        $this->redirect(BASE_URL . 'contact');
    }
}

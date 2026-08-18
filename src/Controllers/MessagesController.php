<?php

namespace Drivejob\Controllers;

use Drivejob\Core\Database;
use Drivejob\Core\Session;
use Drivejob\Services\MessagingService;
use PDO;

/**
 * Μηνύματα & συνομιλίες οδηγών/εταιρειών (Πακέτο 5.5).
 *
 * Μεταφέρθηκε από τις αυτόνομες σελίδες του src/Legacy/ σε κανονικό MVC:
 * τα queries εδώ, το HTML στα src/Views/messages/. Η αποστολή απάντησης
 * γίνεται πλέον και μέσω POST route (πριν η φόρμα POSTαρε σε GET-only
 * route και χανόταν).
 */
class MessagesController extends BaseController
{
    public function __construct()
    {
        parent::__construct(); // container + $this->pdo
        if (!$this->pdo) {
            $this->pdo = Database::getInstance()->getConnection();
        }
    }

    // ---- Οδηγοί ----------------------------------------------------------

    /** Λίστα συνομιλιών οδηγού. */
    public function driverMessages()
    {
        $driverId = $this->requireRole('driver');

        $stmt = $this->pdo->prepare('SELECT first_name, last_name FROM drivers WHERE id = ?');
        $stmt->execute([$driverId]);
        $driver = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare("
            SELECT c.id, c.subject, c.status, c.created_at, c.updated_at,
                   comp.company_name, comp.company_logo,
                   j.title AS job_title,
                   (SELECT COUNT(*) FROM messages m
                    WHERE m.conversation_id = c.id AND m.is_read = 0 AND m.sender_type = 'company') AS unread_count,
                   (SELECT message FROM messages m2
                    WHERE m2.conversation_id = c.id ORDER BY m2.created_at DESC LIMIT 1) AS last_message
            FROM conversations c
            JOIN companies comp ON c.company_id = comp.id
            JOIN job_listings j ON c.job_id = j.id
            WHERE c.driver_id = ?
            ORDER BY c.updated_at DESC
        ");
        $stmt->execute([$driverId]);
        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'Τα Μηνύματά μου';
        include ROOT_DIR . '/src/Views/messages/driver-inbox.php';
        exit;
    }

    /** Συνομιλία οδηγού (GET προβολή + POST απάντηση). */
    public function driverConversation()
    {
        $driverId = $this->requireRole('driver');
        $conversationId = $_GET['id'] ?? null;
        if (!$conversationId) {
            $this->goTo('drivers/messages');
        }

        $stmt = $this->pdo->prepare("
            SELECT c.*, comp.company_name, comp.company_logo, j.title AS job_title
            FROM conversations c
            JOIN companies comp ON c.company_id = comp.id
            JOIN job_listings j ON c.job_id = j.id
            WHERE c.id = ? AND c.driver_id = ?
        ");
        $stmt->execute([$conversationId, $driverId]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$conversation) {
            $this->goTo('drivers/messages');
        }

        // Απάντηση (PRG: redirect μετά το POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
            (new MessagingService())->sendMessage($conversationId, 'driver', $driverId, $_POST['message']);
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }

        // Σήμανση εισερχόμενων ως αναγνωσμένων
        $this->pdo->prepare("
            UPDATE messages SET is_read = 1
            WHERE conversation_id = ? AND sender_type = 'company' AND is_read = 0
        ")->execute([$conversationId]);

        $stmt = $this->pdo->prepare('SELECT * FROM messages WHERE conversation_id = ? ORDER BY created_at ASC');
        $stmt->execute([$conversationId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = $conversation['subject'];
        include ROOT_DIR . '/src/Views/messages/driver-conversation.php';
        exit;
    }

    // ---- Εταιρείες -------------------------------------------------------

    /** Λίστα συνομιλιών εταιρείας. */
    public function companyMessages()
    {
        $companyId = $this->requireRole('company');

        $stmt = $this->pdo->prepare('SELECT company_name FROM companies WHERE id = ?');
        $stmt->execute([$companyId]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare("
            SELECT c.id, c.subject, c.status, c.created_at, c.updated_at,
                   d.first_name, d.last_name, d.profile_image,
                   j.title AS job_title,
                   (SELECT COUNT(*) FROM messages m
                    WHERE m.conversation_id = c.id AND m.is_read = 0 AND m.sender_type = 'driver') AS unread_count,
                   (SELECT message FROM messages m2
                    WHERE m2.conversation_id = c.id ORDER BY m2.created_at DESC LIMIT 1) AS last_message
            FROM conversations c
            JOIN drivers d ON c.driver_id = d.id
            JOIN job_listings j ON c.job_id = j.id
            WHERE c.company_id = ?
            ORDER BY c.updated_at DESC
        ");
        $stmt->execute([$companyId]);
        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = 'Μηνύματα';
        include ROOT_DIR . '/src/Views/messages/company-inbox.php';
        exit;
    }

    /** Συνομιλία εταιρείας (GET προβολή + POST απάντηση). */
    public function companyConversation()
    {
        $companyId = $this->requireRole('company');
        $conversationId = $_GET['id'] ?? null;
        if (!$conversationId) {
            $this->goTo('companies/messages');
        }

        $stmt = $this->pdo->prepare("
            SELECT c.*, d.first_name, d.last_name, d.profile_image,
                   d.email AS driver_email, d.phone AS driver_phone,
                   j.title AS job_title
            FROM conversations c
            JOIN drivers d ON c.driver_id = d.id
            JOIN job_listings j ON c.job_id = j.id
            WHERE c.id = ? AND c.company_id = ?
        ");
        $stmt->execute([$conversationId, $companyId]);
        $conversation = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$conversation) {
            $this->goTo('companies/messages');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
            (new MessagingService())->sendMessage($conversationId, 'company', $companyId, $_POST['message']);
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }

        $this->pdo->prepare("
            UPDATE messages SET is_read = 1
            WHERE conversation_id = ? AND sender_type = 'driver' AND is_read = 0
        ")->execute([$conversationId]);

        $stmt = $this->pdo->prepare('SELECT * FROM messages WHERE conversation_id = ? ORDER BY created_at ASC');
        $stmt->execute([$conversationId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pageTitle = $conversation['subject'];
        include ROOT_DIR . '/src/Views/messages/company-conversation.php';
        exit;
    }

    // ---- helpers ---------------------------------------------------------

    /** Απαιτεί συνδεδεμένο χρήστη με τον συγκεκριμένο ρόλο· επιστρέφει το user_id. */
    private function requireRole(string $role): int
    {
        if (!Session::has('user_id') || Session::get('user_role') !== $role) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }
        return (int) Session::get('user_id');
    }

    private function goTo(string $path): void
    {
        header('Location: ' . BASE_URL . $path);
        exit;
    }
}

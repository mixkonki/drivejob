<?php

namespace Drivejob\Controllers;

use Drivejob\Core\Session;
use Drivejob\Core\CSRF;
use Drivejob\Core\Logger;
use Drivejob\Helpers\JsonHelper;
use Drivejob\Repositories\NotificationRepository;

/**
 * Το καμπανάκι — η σελίδα και τα endpoints των ειδοποιήσεων.
 *
 * Ο πίνακας notifications υπήρχε, το repository υπήρχε, και δεν υπήρχε
 * ΚΑΝΕΝΑΣ τρόπος να δει ο χρήστης τις ειδοποιήσεις του: ούτε σελίδα, ούτε
 * route, ούτε ένδειξη στο μενού. Ο Notifier γράφει πλέον εγγραφές — κάπου
 * πρέπει και να διαβάζονται.
 *
 * Τρία endpoints, τίποτα περιττό:
 *   GET  /notifications               — η σελίδα
 *   GET  /notifications/unread-count  — JSON για το σήμα στο μενού
 *   POST /notifications/read-all      — όλα ως αναγνωσμένα
 */
class NotificationController extends BaseController
{
    private NotificationRepository $notifications;

    public function __construct()
    {
        parent::__construct();
        $this->notifications = new NotificationRepository($this->pdo);
    }

    /**
     * Ο ρόλος όπως τον ξέρει ο πίνακας notifications (driver/company).
     *
     * @return array{0:int,1:string}|null null = μη συνδεδεμένος
     */
    private function viewer(): ?array
    {
        if (!Session::has('user_id') || !Session::has('user_role')) {
            return null;
        }

        $role = (string) Session::get('user_role');

        if (!in_array($role, ['driver', 'company'], true)) {
            return null;
        }

        return [(int) Session::get('user_id'), $role];
    }

    /** GET /notifications — η σελίδα του χρήστη. */
    public function index()
    {
        $viewer = $this->viewer();

        if ($viewer === null) {
            Session::set('error_message', 'Συνδέσου για να δεις τις ειδοποιήσεις σου.');
            header('Location: ' . BASE_URL . 'auth/login');
            exit();
        }

        [$userId, $userType] = $viewer;

        try {
            $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
            $result = $this->notifications->findByUser($userId, $userType, false, $page, 20);

            $notifications = $result['results'] ?? [];
            $pagination = $result['pagination'] ?? [];
            $unread = $this->notifications->countUnread($userId, $userType);

            include ROOT_DIR . '/src/Views/notifications/index.php';
        } catch (\Throwable $e) {
            Logger::error('Σφάλμα στη σελίδα ειδοποιήσεων', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ]);
            Session::set('error_message', 'Οι ειδοποιήσεις δεν φορτώθηκαν. Δοκίμασε ξανά.');
            header('Location: ' . BASE_URL);
            exit();
        }
    }

    /**
     * GET /notifications/unread-count — για το σήμα στο μενού.
     *
     * Καλείται από κάθε σελίδα, από κάθε συνδεδεμένο χρήστη — γι' αυτό
     * απαντά ΜΟΝΟ έναν αριθμό και τίποτα άλλο. Χωρίς σύνδεση: 0, όχι
     * σφάλμα — το μενού δεν είναι μέρος που αξίζει 401.
     */
    public function unreadCount()
    {
        $viewer = $this->viewer();

        if ($viewer === null) {
            JsonHelper::response(['count' => 0]);
        }

        [$userId, $userType] = $viewer;

        try {
            JsonHelper::response(['count' => $this->notifications->countUnread($userId, $userType)]);
        } catch (\Throwable $e) {
            // Το σήμα δεν αξίζει 500 — μηδέν και συνεχίζουμε.
            JsonHelper::response(['count' => 0]);
        }
    }

    /**
     * POST /notifications/read-all — όλα ως αναγνωσμένα.
     *
     * Μία ενέργεια αντί για κλικ σε καθεμία: όποιος άνοιξε τη σελίδα τις
     * είδε ήδη όλες.
     */
    public function readAll()
    {
        $viewer = $this->viewer();

        if ($viewer === null) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit();
        }

        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'notifications');
            exit();
        }

        [$userId, $userType] = $viewer;

        try {
            $this->notifications->markAllAsRead($userId, $userType);
        } catch (\Throwable $e) {
            Logger::error('Αποτυχία σήμανσης ειδοποιήσεων', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ]);
        }

        header('Location: ' . BASE_URL . 'notifications');
        exit();
    }
}

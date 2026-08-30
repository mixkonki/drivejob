<?php

namespace Drivejob\Controllers\Admin;

use Drivejob\Controllers\BaseController;
use Drivejob\Core\AuthMiddleware;
use Drivejob\Core\CSRF;
use Drivejob\Core\Logger;
use Drivejob\Core\Session;
use Drivejob\Repositories\AdminRepository;
use Drivejob\Services\AdminActivityLogger;

/**
 * Το admin panel — με πραγματικά δεδομένα πλέον.
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  ΤΙ ΗΤΑΝ ΠΡΙΝ
 * ══════════════════════════════════════════════════════════════════════════
 *
 * Επτά μέθοδοι, οι έξι ήταν redirect στο dashboard — και το dashboard
 * φόρτωνε μια σελίδα που ζητούσε μετρήσεις από legacy APIs. Ο διαχειριστής
 * έβλεπε ένα μενού με «Χρήστες», «Αγγελίες», «Στατιστικά» και κάθε κλικ τον
 * γύριζε εκεί απ' όπου ξεκίνησε.
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  ΚΑΝΟΝΕΣ
 * ══════════════════════════════════════════════════════════════════════════
 *
 * Κάθε POST ελέγχει CSRF. Κάθε καταστροφική ή διοικητική ενέργεια
 * (ενεργοποίηση/απενεργοποίηση) καταγράφεται στο admin_activity_logs —
 * ποιος, τι, πότε, σε ποιον. Διαγραφή χρηστών ΔΕΝ υπάρχει από εδώ: η
 * διαγραφή λογαριασμού είναι διαδικασία GDPR με δικά της βήματα, όχι
 * κουμπί σε πίνακα.
 */
class AdminController extends BaseController
{
    private AdminRepository $admin;

    public function __construct()
    {
        parent::__construct();
        // Όλες οι admin σελίδες απαιτούν ρόλο admin
        AuthMiddleware::hasRole('admin');
        $this->admin = new AdminRepository($this->pdo);
    }

    /** GET /admin/dashboard — οι αριθμοί της πλατφόρμας. */
    public function dashboard()
    {
        try {
            $stats = $this->admin->stats();
            $recentUsers = $this->admin->recentRegistrations(8);
            $recentActivity = $this->admin->recentActivity(8);
        } catch (\Throwable $e) {
            Logger::error('Σφάλμα στο admin dashboard', ['message' => $e->getMessage()]);
            $stats = [];
            $recentUsers = [];
            $recentActivity = [];
        }

        include ROOT_DIR . '/src/Views/admin/dashboard.php';
    }

    /** GET /admin/users[/{type}] — διαχείριση χρηστών. */
    public function users($type = null)
    {
        $type = in_array($type ?? ($_GET['type'] ?? 'all'), ['driver', 'company'], true)
            ? ($type ?? $_GET['type'])
            : 'all';
        $status = in_array($_GET['status'] ?? 'all', ['active', 'inactive', 'verified', 'unverified'], true)
            ? $_GET['status']
            : 'all';
        $search = trim((string) ($_GET['search'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));

        try {
            $users = $this->admin->users($type, $status, $search, $page, 20);
        } catch (\Throwable $e) {
            Logger::error('Σφάλμα στη λίστα χρηστών admin', ['message' => $e->getMessage()]);
            $users = ['data' => [], 'pagination' => []];
            Session::set('error_message', 'Η λίστα δεν φορτώθηκε. Δοκίμασε ξανά.');
        }

        include ROOT_DIR . '/src/Views/admin/users.php';
    }

    /** GET /admin/user-details/{userId}/{userType} */
    public function userDetails($userId, $userType)
    {
        $userType = $userType === 'company' ? 'company' : 'driver';
        $details = null;

        try {
            $details = $this->admin->userDetails((int) $userId, $userType);
        } catch (\Throwable $e) {
            Logger::error('Σφάλμα στα στοιχεία χρήστη admin', ['message' => $e->getMessage()]);
        }

        if ($details === null) {
            Session::set('error_message', 'Ο χρήστης δεν βρέθηκε.');
            $this->redirect(BASE_URL . 'admin/users');
            return;
        }

        $user = $details['user'];
        $activity = $details['activity'];

        include ROOT_DIR . '/src/Views/admin/user-details.php';
    }

    /** POST /admin/toggle-user-status/{userId}/{userType} */
    public function toggleUserStatus($userId, $userType)
    {
        $this->requireCsrf('admin/users');
        $userType = $userType === 'company' ? 'company' : 'driver';

        try {
            $newState = $this->admin->toggleUserStatus((int) $userId, $userType);

            if ($newState === null) {
                Session::set('error_message', 'Ο χρήστης δεν βρέθηκε.');
            } else {
                /*
                 * Η καταγραφή ΔΕΝ είναι διακοσμητική: όταν ένας λογαριασμός
                 * βρεθεί απενεργοποιημένος, το «ποιος και πότε» είναι η
                 * διαφορά μεταξύ απάντησης και εικασίας.
                 */
                (new AdminActivityLogger($this->pdo))->log(
                    (int) Session::get('user_id'),
                    $newState ? 'user_activated' : 'user_deactivated',
                    $userType,
                    (int) $userId
                );

                Session::set('success_message', $newState
                    ? 'Ο λογαριασμός ενεργοποιήθηκε.'
                    : 'Ο λογαριασμός απενεργοποιήθηκε — δεν μπορεί πλέον να συνδεθεί.');
            }
        } catch (\Throwable $e) {
            Logger::error('Σφάλμα σε toggle χρήστη', ['message' => $e->getMessage()]);
            Session::set('error_message', 'Η ενέργεια δεν ολοκληρώθηκε.');
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?? BASE_URL . 'admin/users');
    }

    /** GET /admin/job-listings — διαχείριση αγγελιών. */
    public function jobListings()
    {
        $status = in_array($_GET['status'] ?? 'all', ['active', 'inactive'], true) ? $_GET['status'] : 'all';
        $search = trim((string) ($_GET['search'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));

        try {
            $listings = $this->admin->listings($status, $search, $page, 20);
        } catch (\Throwable $e) {
            Logger::error('Σφάλμα στη λίστα αγγελιών admin', ['message' => $e->getMessage()]);
            $listings = ['data' => [], 'pagination' => []];
        }

        include ROOT_DIR . '/src/Views/admin/job-listings.php';
    }

    /** POST /admin/toggle-listing/{id} */
    public function toggleListing($id)
    {
        $this->requireCsrf('admin/job-listings');

        try {
            $newState = $this->admin->toggleListing((int) $id);

            if ($newState === null) {
                Session::set('error_message', 'Η αγγελία δεν βρέθηκε.');
            } else {
                (new AdminActivityLogger($this->pdo))->log(
                    (int) Session::get('user_id'),
                    $newState ? 'listing_activated' : 'listing_deactivated',
                    'job_listing',
                    (int) $id
                );

                Session::set('success_message', $newState
                    ? 'Η αγγελία ενεργοποιήθηκε.'
                    : 'Η αγγελία απενεργοποιήθηκε — δεν εμφανίζεται πλέον στη λίστα.');
            }
        } catch (\Throwable $e) {
            Logger::error('Σφάλμα σε toggle αγγελίας', ['message' => $e->getMessage()]);
            Session::set('error_message', 'Η ενέργεια δεν ολοκληρώθηκε.');
        }

        $this->redirect($_SERVER['HTTP_REFERER'] ?? BASE_URL . 'admin/job-listings');
    }

    /** GET /admin/analytics — εγγραφές και δραστηριότητα ανά μήνα. */
    public function analytics()
    {
        try {
            $monthly = $this->admin->monthly();
            $stats = $this->admin->stats();
        } catch (\Throwable $e) {
            Logger::error('Σφάλμα στα στατιστικά admin', ['message' => $e->getMessage()]);
            $monthly = [];
            $stats = [];
        }

        include ROOT_DIR . '/src/Views/admin/analytics.php';
    }

    /** GET /admin/activity-logs — ποιος διαχειριστής έκανε τι. */
    public function activityLogs()
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = 30;
        $offset = ($page - 1) * $limit;

        try {
            $total = (int) $this->pdo->query('SELECT COUNT(*) FROM admin_activity_logs')->fetchColumn();
            $logs = $this->pdo->query(
                "SELECT l.*, u.email AS admin_email
                 FROM admin_activity_logs l
                 LEFT JOIN users u ON u.id = l.admin_id
                 ORDER BY l.created_at DESC
                 LIMIT $limit OFFSET $offset"
            )->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            Logger::error('Σφάλμα στα activity logs', ['message' => $e->getMessage()]);
            $total = 0;
            $logs = [];
        }

        $pagination = [
            'total' => $total,
            'page' => $page,
            'pages' => (int) ceil($total / $limit),
        ];

        include ROOT_DIR . '/src/Views/admin/activity-logs.php';
    }

    /**
     * GET /admin/settings — παραπέμπει στο monitoring μέχρι να αποκτήσει
     * πραγματικό περιεχόμενο. Το παλιό view ήταν αυτόνομη σελίδα Bootstrap
     * εκτός του admin layout, με κουμπιά που δεν έκαναν τίποτα.
     */
    public function settings()
    {
        Session::set('error_message', 'Οι ρυθμίσεις δεν είναι διαθέσιμες ακόμη.');
        $this->redirect(BASE_URL . 'admin/dashboard');
    }

    /*
     * ══════════════════════════════════════════════════════════════════
     *  ΚΑΤΑΛΟΓΟΙ ΤΙΜΩΝ (30/08/2026)
     * ══════════════════════════════════════════════════════════════════
     *
     * Οι λίστες που βλέπουν οι οδηγοί (ειδικές άδειες κ.λπ.) ήταν
     * σκληροκωδικοποιημένες: κάθε νέα κατηγορία της νομοθεσίας απαιτούσε
     * αλλαγή κώδικα και deploy. Τώρα τις συντηρεί ο διαχειριστής.
     *
     * Κανόνας: ΚΑΜΙΑ διαγραφή τιμής που χρησιμοποιείται — μόνο
     * απενεργοποίηση, ώστε τα προφίλ των οδηγών να μη μείνουν με ορφανούς
     * κωδικούς (ο έλεγχος γίνεται στο LookupModel).
     */

    /** GET /admin/lookups[/{domain}] — διαχείριση καταλόγων. */
    public function lookups($domain = null)
    {
        $domain = $domain ?? ($_GET['domain'] ?? 'special_license');
        if (!isset(\Drivejob\Models\LookupModel::DOMAINS[$domain])) {
            $domain = 'special_license';
        }

        $lookupModel = new \Drivejob\Models\LookupModel($this->pdo);
        $domains = \Drivejob\Models\LookupModel::DOMAINS;
        $values = $lookupModel->all($domain, false); // και ανενεργές

        // Πόσοι οδηγοί χρησιμοποιούν κάθε τιμή — ώστε ο διαχειριστής να
        // βλέπει τι θα επηρεάσει πριν αποσύρει μια κατηγορία.
        foreach ($values as &$value) {
            $value['usage_count'] = $lookupModel->usageCount($domain, $value['code']);
        }
        unset($value);

        include ROOT_DIR . '/src/Views/admin/lookups.php';
    }

    /** POST /admin/lookups/{domain}/save — προσθήκη ή ενημέρωση τιμής. */
    public function saveLookup($domain)
    {
        $this->requireCsrf('admin/lookups/' . $domain);

        $lookupModel = new \Drivejob\Models\LookupModel($this->pdo);
        $id = (int) ($_POST['id'] ?? 0);
        $label = (string) ($_POST['label'] ?? '');
        $shortLabel = (string) ($_POST['short_label'] ?? '');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);

        $result = $id > 0
            ? $lookupModel->update($id, $label, $shortLabel, $sortOrder)
            : $lookupModel->create($domain, (string) ($_POST['code'] ?? ''), $label, $shortLabel, $sortOrder);

        if ($result['ok']) {
            Session::set('success_message', $id > 0 ? 'Η τιμή ενημερώθηκε.' : 'Η τιμή προστέθηκε.');
        } else {
            Session::set('error_message', $result['error'] ?? 'Δεν ήταν δυνατή η αποθήκευση.');
        }

        $this->redirect(BASE_URL . 'admin/lookups/' . $domain);
    }

    /** POST /admin/lookups/{domain}/toggle/{id} — απόσυρση/επαναφορά. */
    public function toggleLookup($domain, $id)
    {
        $this->requireCsrf('admin/lookups/' . $domain);

        $lookupModel = new \Drivejob\Models\LookupModel($this->pdo);
        $current = $lookupModel->find((int) $id);
        $result = $lookupModel->setActive((int) $id, !($current && (int) $current['is_active'] === 1));

        if ($result['ok']) {
            Session::set('success_message', 'Η κατάσταση της τιμής άλλαξε.');
        } else {
            Session::set('error_message', $result['error'] ?? 'Δεν ήταν δυνατή η αλλαγή.');
        }

        $this->redirect(BASE_URL . 'admin/lookups/' . $domain);
    }

    /** POST /admin/lookups/{domain}/delete/{id} — μόνο αν δεν χρησιμοποιείται. */
    public function deleteLookup($domain, $id)
    {
        $this->requireCsrf('admin/lookups/' . $domain);

        $lookupModel = new \Drivejob\Models\LookupModel($this->pdo);
        $result = $lookupModel->delete((int) $id);

        if ($result['ok']) {
            Session::set('success_message', 'Η τιμή διαγράφηκε.');
        } else {
            Session::set('error_message', $result['error'] ?? 'Δεν ήταν δυνατή η διαγραφή.');
        }

        $this->redirect(BASE_URL . 'admin/lookups/' . $domain);
    }

    /** Έλεγχος CSRF για κάθε POST του admin — μία φορά, εδώ. */
    private function requireCsrf(string $backTo): void
    {
        if (!isset($_POST['csrf_token']) || !CSRF::validateToken($_POST['csrf_token'])) {
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            $this->redirect(BASE_URL . $backTo);
            exit();
        }
    }
}

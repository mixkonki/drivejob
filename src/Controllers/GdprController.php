<?php

namespace Drivejob\Controllers;

use Drivejob\Core\Database;
use Drivejob\Core\Logger;
use Drivejob\Core\Session;
use PDO;

/**
 * Δικαιώματα υποκειμένων δεδομένων — GDPR (Πακέτο 7).
 *
 *   GET  /gdpr/export          → εξαγωγή όλων των δεδομένων του χρήστη σε JSON
 *                                (άρθρα 15 πρόσβαση & 20 φορητότητα)
 *   GET  /gdpr/delete          → σελίδα επιβεβαίωσης διαγραφής λογαριασμού
 *   POST /gdpr/delete          → οριστική διαγραφή (άρθρο 17) με επιβεβαίωση κωδικού
 *
 * Διαγραφή οδηγού: σβήνονται τα αρχεία του από το storage/uploads και η εγγραφή
 * του από τον πίνακα drivers — όλα τα συνδεδεμένα (άδειες, αιτήσεις, συνομιλίες,
 * σκορ, αξιολογήσεις) διαγράφονται από τα FK ON DELETE CASCADE της βάσης.
 */
class GdprController extends BaseController
{
    /** Στήλες αρχείων οδηγού (ό,τι και στο FileController). */
    private const DRIVER_FILE_COLUMNS = [
        'profile_image', 'resume_file',
        'license_front_image', 'license_back_image',
        'tachograph_front_image', 'tachograph_back_image',
        'adr_front_image', 'adr_back_image',
        'operator_front_image', 'operator_back_image',
    ];

    public function __construct()
    {
        parent::__construct();
        if (!$this->pdo) {
            $this->pdo = Database::getInstance()->getConnection();
        }
    }

    // ---- Εξαγωγή (άρθρα 15 & 20) -----------------------------------------

    public function export()
    {
        [$role, $userId] = $this->requireUser();

        $data = [
            '_info' => [
                'platform' => 'DriveJob',
                'export_date' => date('c'),
                'subject_type' => $role,
                'notice' => 'Πλήρης εξαγωγή των προσωπικών σας δεδομένων (άρθρα 15 & 20 GDPR).',
            ],
        ];

        if ($role === 'driver') {
            $data['profile'] = $this->fetchOne('SELECT * FROM drivers WHERE id = ?', [$userId], ['password', 'reset_token', 'verification_token']);
            $data['licenses'] = $this->fetchAll('SELECT * FROM driver_licenses WHERE driver_id = ?', [$userId]);
            $data['adr_certificates'] = $this->fetchAll('SELECT * FROM driver_adr_certificates WHERE driver_id = ?', [$userId]);
            $data['tachograph_cards'] = $this->fetchAll('SELECT * FROM driver_tachograph_cards WHERE driver_id = ?', [$userId]);
            $data['operator_licenses'] = $this->fetchAll('SELECT * FROM driver_operator_licenses WHERE driver_id = ?', [$userId]);
            $data['special_licenses'] = $this->fetchAll('SELECT * FROM driver_special_licenses WHERE driver_id = ?', [$userId]);
            $data['certifications'] = $this->fetchAll('SELECT * FROM driver_certifications WHERE driver_id = ?', [$userId]);
            $data['skills'] = $this->fetchAll('SELECT * FROM driver_skills WHERE driver_id = ?', [$userId]);
            $data['vehicle_experience'] = $this->fetchAll('SELECT * FROM driver_vehicle_experience WHERE driver_id = ?', [$userId]);
            $data['languages'] = $this->fetchAll('SELECT * FROM driver_languages WHERE driver_id = ?', [$userId]);
            $data['job_applications'] = $this->fetchAll(
                'SELECT ja.id, ja.job_listing_id, jl.title AS job_title, ja.status, ja.message, ja.created_at
                 FROM job_applications ja LEFT JOIN job_listings jl ON jl.id = ja.job_listing_id
                 WHERE ja.driver_id = ?', [$userId]
            );
            $data['conversations'] = $this->fetchAll(
                'SELECT c.id, c.subject, c.status, c.created_at, comp.company_name
                 FROM conversations c LEFT JOIN companies comp ON comp.id = c.company_id
                 WHERE c.driver_id = ?', [$userId]
            );
            $data['messages_sent'] = $this->fetchAll(
                "SELECT m.conversation_id, m.message, m.created_at
                 FROM messages m JOIN conversations c ON c.id = m.conversation_id
                 WHERE c.driver_id = ? AND m.sender_type = 'driver'", [$userId]
            );
            $data['ratings_received'] = $this->fetchAll('SELECT * FROM driver_ratings WHERE driver_id = ?', [$userId]);
        } else {
            $data['profile'] = $this->fetchOne('SELECT * FROM companies WHERE id = ?', [$userId], ['password', 'reset_token', 'verification_token']);
            $data['job_listings'] = $this->fetchAll('SELECT * FROM job_listings WHERE company_id = ?', [$userId]);
            $data['conversations'] = $this->fetchAll(
                'SELECT c.id, c.subject, c.status, c.created_at FROM conversations c WHERE c.company_id = ?', [$userId]
            );
            $data['messages_sent'] = $this->fetchAll(
                "SELECT m.conversation_id, m.message, m.created_at
                 FROM messages m JOIN conversations c ON c.id = m.conversation_id
                 WHERE c.company_id = ? AND m.sender_type = 'company'", [$userId]
            );
        }

        $filename = 'drivejob-data-' . $role . '-' . $userId . '-' . date('Ymd') . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // ---- Διαγραφή (άρθρο 17) ---------------------------------------------

    /** Σελίδα επιβεβαίωσης. */
    public function deleteConfirm()
    {
        $this->requireUser();
        include ROOT_DIR . '/src/Views/gdpr/delete-confirm.php';
        exit;
    }

    /** Οριστική διαγραφή με επιβεβαίωση κωδικού. */
    public function delete()
    {
        [$role, $userId] = $this->requireUser();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'gdpr/delete');
            exit;
        }

        if (!\Drivejob\Core\CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            Session::set('error_message', 'Άκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.');
            header('Location: ' . BASE_URL . 'gdpr/delete');
            exit;
        }

        $table = $role === 'driver' ? 'drivers' : 'companies';
        $stmt = $this->pdo->prepare("SELECT * FROM {$table} WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($_POST['password'] ?? '', $user['password'])) {
            Session::set('error_message', 'Λάθος κωδικός πρόσβασης — η διαγραφή δεν έγινε.');
            header('Location: ' . BASE_URL . 'gdpr/delete');
            exit;
        }

        try {
            // 1. Διαγραφή αρχείων από το storage (μόνο για οδηγούς)
            if ($role === 'driver') {
                $this->deleteDriverFiles($user);
            } else {
                $this->deleteFileIfSafe($user['company_logo'] ?? null);
            }

            // 2. Διαγραφή εγγραφής — τα CASCADE καθαρίζουν όλα τα συνδεδεμένα
            $this->pdo->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$userId]);

            Logger::info("GDPR: διαγράφηκε λογαριασμός {$role} #{$userId} κατόπιν αιτήματος του υποκειμένου");

            // 3. Τερματισμός συνεδρίας
            Session::destroy();
            session_start();
            Session::set('success_message', 'Ο λογαριασμός και τα δεδομένα σας διαγράφηκαν οριστικά.');
            header('Location: ' . BASE_URL);
            exit;
        } catch (\Throwable $e) {
            Logger::error('GDPR delete failed: ' . $e->getMessage());
            Session::set('error_message', 'Σφάλμα κατά τη διαγραφή. Επικοινωνήστε στο info@drivejob.gr.');
            header('Location: ' . BASE_URL . 'gdpr/delete');
            exit;
        }
    }

    // ---- helpers ---------------------------------------------------------

    /** Απαιτεί συνδεδεμένο οδηγό ή εταιρεία· επιστρέφει [role, id]. */
    private function requireUser(): array
    {
        $role = Session::get('user_role');
        if (!Session::has('user_id') || !in_array($role, ['driver', 'company'], true)) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit;
        }
        return [$role, (int) Session::get('user_id')];
    }

    private function fetchOne(string $sql, array $params, array $stripKeys = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($row) {
            foreach ($stripKeys as $k) {
                unset($row[$k]);
            }
        }
        return $row;
    }

    private function fetchAll(string $sql, array $params): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function deleteDriverFiles(array $driver): void
    {
        foreach (self::DRIVER_FILE_COLUMNS as $col) {
            $this->deleteFileIfSafe($driver[$col] ?? null);
        }
        // Αρχεία πιστοποιήσεων (ξεχωριστός πίνακας)
        $stmt = $this->pdo->prepare('SELECT certificate_file FROM driver_certifications WHERE driver_id = ? AND certificate_file IS NOT NULL');
        $stmt->execute([$driver['id']]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $path) {
            $this->deleteFileIfSafe($path);
        }
    }

    /** Διαγράφει αρχείο ΜΟΝΟ αν βρίσκεται μέσα στο storage/uploads (anti-traversal). */
    private function deleteFileIfSafe(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }
        $base = realpath(ROOT_DIR . '/storage');
        // Οι διαδρομές στη βάση είναι της μορφής "uploads/<φάκελος>/<αρχείο>"
        $real = realpath(ROOT_DIR . '/storage/' . ltrim($relativePath, '/'));
        if ($base && $real && strpos($real, $base) === 0 && is_file($real)) {
            @unlink($real);
        }
    }
}

<?php

namespace Drivejob\Repositories;

use PDO;

/**
 * Τα ερωτήματα του admin panel — μετρήσεις και διαχείριση.
 *
 * ══════════════════════════════════════════════════════════════════════════
 *  ΓΙΑΤΙ ΥΠΑΡΧΕΙ
 * ══════════════════════════════════════════════════════════════════════════
 *
 * Ο AdminController ήταν 7 placeholders που ανακατεύθυναν όλα στο dashboard —
 * και το dashboard φόρτωνε μια σκοτεινή σελίδα που ζητούσε δεδομένα από
 * legacy APIs. Ο Κώστας μπήκε ως διαχειριστής και ρώτησε: «πού θα δω έναν
 * πίνακα με πληροφορίες, διαχείριση χρηστών, αγγελιών, μετρήσεις;» —
 * πουθενά. Αυτή η κλάση είναι η απάντηση.
 *
 * Όλα τα ερωτήματα είναι COUNT/SELECT σε ευρετηριασμένους πίνακες — καμία
 * επεξεργασία, κανένα JOIN που να βαραίνει. Το admin panel το βλέπει ένας
 * άνθρωπος λίγες φορές τη μέρα· η απλότητα αξίζει περισσότερο από την
 * εξυπνάδα.
 */
class AdminRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ═══════════════════════════════════════════════ Μετρήσεις

    /**
     * Οι αριθμοί της πλατφόρμας — μία ματιά, όλη η εικόνα.
     */
    public function stats(): array
    {
        $one = fn(string $sql): int => (int) $this->pdo->query($sql)->fetchColumn();

        return [
            // Χρήστες
            'drivers_total'      => $one('SELECT COUNT(*) FROM drivers'),
            'drivers_active'     => $one('SELECT COUNT(*) FROM drivers WHERE is_active = 1'),
            'drivers_available'  => $one('SELECT COUNT(*) FROM drivers WHERE is_active = 1 AND available_for_work = 1'),
            'drivers_new_7d'     => $one('SELECT COUNT(*) FROM drivers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'),
            'companies_total'    => $one('SELECT COUNT(*) FROM companies'),
            'companies_active'   => $one('SELECT COUNT(*) FROM companies WHERE is_active = 1'),
            'companies_new_7d'   => $one('SELECT COUNT(*) FROM companies WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'),

            // Αγγελίες
            'listings_active'    => $one("SELECT COUNT(*) FROM job_listings WHERE is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())"),
            'listings_offers'    => $one("SELECT COUNT(*) FROM job_listings WHERE is_active = 1 AND listing_type = 'job_offer'"),
            'listings_searches'  => $one("SELECT COUNT(*) FROM job_listings WHERE is_active = 1 AND listing_type = 'job_search'"),
            'listings_new_7d'    => $one('SELECT COUNT(*) FROM job_listings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'),

            // Η κίνηση — αυτό που μετράει πραγματικά σε ένα marketplace
            'applications_total'   => $one('SELECT COUNT(*) FROM job_applications'),
            'applications_pending' => $one("SELECT COUNT(*) FROM job_applications WHERE status IN ('pending','viewed')"),
            'applications_hired'   => $one("SELECT COUNT(*) FROM job_applications WHERE status = 'hired'"),
            'applications_new_7d'  => $one('SELECT COUNT(*) FROM job_applications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)'),
            'offers_total'         => $one('SELECT COUNT(*) FROM job_offers'),
            'offers_pending'       => $one("SELECT COUNT(*) FROM job_offers WHERE status IN ('pending','viewed')"),
            'offers_accepted'      => $one("SELECT COUNT(*) FROM job_offers WHERE status = 'accepted'"),
        ];
    }

    /**
     * Οι τελευταίες εγγραφές — τι συνέβη πρόσφατα, ποιος μπήκε.
     */
    public function recentRegistrations(int $limit = 8): array
    {
        $sql = "
            (SELECT d.id, 'driver' AS type, TRIM(CONCAT(COALESCE(d.first_name,''),' ',COALESCE(d.last_name,''))) AS name,
                    d.email, d.is_active, d.is_verified, d.created_at
             FROM drivers d)
            UNION ALL
            (SELECT c.id, 'company' AS type, c.company_name AS name,
                    c.email, c.is_active, c.is_verified, c.created_at
             FROM companies c)
            ORDER BY created_at DESC
            LIMIT " . (int) $limit;

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Η πρόσφατη δραστηριότητα: αιτήσεις και προσφορές μαζί, χρονολογικά. */
    public function recentActivity(int $limit = 8): array
    {
        $sql = "
            (SELECT ja.id, 'application' AS kind, ja.status, ja.created_at,
                    jl.title AS subject
             FROM job_applications ja
             LEFT JOIN job_listings jl ON jl.id = ja.job_listing_id)
            UNION ALL
            (SELECT o.id, 'offer' AS kind, o.status, o.created_at,
                    o.title AS subject
             FROM job_offers o)
            ORDER BY created_at DESC
            LIMIT " . (int) $limit;

        return $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // ═══════════════════════════════════════════════ Χρήστες

    /**
     * Ενιαία λίστα οδηγών και εταιριών, με φίλτρα και αναζήτηση.
     *
     * UNION αντί για δύο ερωτήματα: η σελίδα δείχνει «όλους τους χρήστες»
     * ταξινομημένους κατά εγγραφή — δύο χωριστές λίστες δεν συγχωνεύονται
     * σωστά σελιδοποιημένες.
     */
    public function users(string $type = 'all', string $status = 'all', string $search = '', int $page = 1, int $limit = 20): array
    {
        $parts = [];
        $params = [];

        $searchSql = '';
        if ($search !== '') {
            $searchSql = ' AND (email LIKE :search OR name LIKE :search OR phone LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $statusSql = match ($status) {
            'active'     => ' AND is_active = 1',
            'inactive'   => ' AND is_active = 0',
            'verified'   => ' AND is_verified = 1',
            'unverified' => ' AND is_verified = 0',
            default      => '',
        };

        if ($type === 'all' || $type === 'driver') {
            $parts[] = "SELECT * FROM (
                SELECT d.id, 'driver' AS type,
                       TRIM(CONCAT(COALESCE(d.first_name,''),' ',COALESCE(d.last_name,''))) AS name,
                       d.email, d.phone, d.profile_image, NULL AS logo,
                       d.is_active, d.is_verified, d.created_at
                FROM drivers d) AS drv WHERE 1=1 $searchSql $statusSql";
        }

        if ($type === 'all' || $type === 'company') {
            $parts[] = "SELECT * FROM (
                SELECT c.id, 'company' AS type, c.company_name AS name,
                       c.email, c.phone, NULL AS profile_image, c.company_logo AS logo,
                       c.is_active, c.is_verified, c.created_at
                FROM companies c) AS cmp WHERE 1=1 $searchSql $statusSql";
        }

        $union = '(' . implode(') UNION ALL (', $parts) . ')';

        $total = (int) $this->prepared("SELECT COUNT(*) FROM ($union) AS u", $params)->fetchColumn();

        $offset = ($page - 1) * $limit;
        $rows = $this->prepared(
            "SELECT * FROM ($union) AS u ORDER BY created_at DESC LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $rows,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => (int) ceil($total / $limit),
            ],
        ];
    }

    /** Ένας χρήστης αναλυτικά — μαζί με τη δραστηριότητά του. */
    public function userDetails(int $userId, string $userType): ?array
    {
        if ($userType === 'driver') {
            $st = $this->pdo->prepare('SELECT * FROM drivers WHERE id = ?');
            $st->execute([$userId]);
            $user = $st->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return null;
            }

            $n = fn(string $sql): int => (int) $this->prepared($sql, ['id' => $userId])->fetchColumn();

            return [
                'user' => $user,
                'activity' => [
                    'listings'     => $n('SELECT COUNT(*) FROM job_listings WHERE driver_id = :id'),
                    'applications' => $n('SELECT COUNT(*) FROM job_applications WHERE driver_id = :id'),
                    'hired'        => $n("SELECT COUNT(*) FROM job_applications WHERE driver_id = :id AND status = 'hired'"),
                    'offers'       => $n('SELECT COUNT(*) FROM job_offers WHERE driver_id = :id'),
                ],
            ];
        }

        $st = $this->pdo->prepare('SELECT * FROM companies WHERE id = ?');
        $st->execute([$userId]);
        $user = $st->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }

        $n = fn(string $sql): int => (int) $this->prepared($sql, ['id' => $userId])->fetchColumn();

        return [
            'user' => $user,
            'activity' => [
                'listings'     => $n('SELECT COUNT(*) FROM job_listings WHERE company_id = :id'),
                'applications' => $n('SELECT COUNT(*) FROM job_applications ja JOIN job_listings jl ON jl.id = ja.job_listing_id WHERE jl.company_id = :id'),
                'hired'        => $n("SELECT COUNT(*) FROM job_applications ja JOIN job_listings jl ON jl.id = ja.job_listing_id WHERE jl.company_id = :id AND ja.status = 'hired'"),
                'offers'       => $n('SELECT COUNT(*) FROM job_offers WHERE company_id = :id'),
            ],
        ];
    }

    /**
     * Εναλλαγή ενεργού/ανενεργού. Επιστρέφει τη νέα κατάσταση ή null.
     */
    public function toggleUserStatus(int $userId, string $userType): ?bool
    {
        $table = $userType === 'company' ? 'companies' : 'drivers';

        $st = $this->pdo->prepare("SELECT is_active FROM {$table} WHERE id = ?");
        $st->execute([$userId]);
        $current = $st->fetchColumn();

        if ($current === false) {
            return null;
        }

        $new = ((int) $current) ? 0 : 1;
        $up = $this->pdo->prepare("UPDATE {$table} SET is_active = ? WHERE id = ?");
        $up->execute([$new, $userId]);

        return (bool) $new;
    }

    // ═══════════════════════════════════════════════ Αγγελίες

    public function listings(string $status = 'all', string $search = '', int $page = 1, int $limit = 20): array
    {
        $where = '1=1';
        $params = [];

        if ($status === 'active') {
            $where .= ' AND jl.is_active = 1';
        } elseif ($status === 'inactive') {
            $where .= ' AND jl.is_active = 0';
        }

        if ($search !== '') {
            $where .= ' AND (jl.title LIKE :search OR jl.location LIKE :search OR c.company_name LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $base = "FROM job_listings jl
                 LEFT JOIN companies c ON c.id = jl.company_id
                 WHERE $where";

        $total = (int) $this->prepared("SELECT COUNT(*) $base", $params)->fetchColumn();

        $offset = ($page - 1) * $limit;
        $rows = $this->prepared(
            "SELECT jl.id, jl.title, jl.listing_type, jl.job_type, jl.location,
                    jl.is_active, jl.created_at, jl.views_count, jl.applications,
                    jl.driver_id, c.company_name
             $base
             ORDER BY jl.created_at DESC
             LIMIT " . (int) $limit . ' OFFSET ' . (int) $offset,
            $params
        )->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $rows,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => (int) ceil($total / $limit),
            ],
        ];
    }

    public function toggleListing(int $id): ?bool
    {
        $st = $this->pdo->prepare('SELECT is_active FROM job_listings WHERE id = ?');
        $st->execute([$id]);
        $current = $st->fetchColumn();

        if ($current === false) {
            return null;
        }

        $new = ((int) $current) ? 0 : 1;
        $up = $this->pdo->prepare('UPDATE job_listings SET is_active = ? WHERE id = ?');
        $up->execute([$new, $id]);

        return (bool) $new;
    }

    // ═══════════════════════════════════════════════ Στατιστικά ανά μήνα

    /**
     * Εγγραφές και δραστηριότητα ανά μήνα, τελευταίοι 6 μήνες.
     */
    public function monthly(): array
    {
        $series = [];

        foreach ([
            'drivers'      => 'SELECT DATE_FORMAT(created_at, "%Y-%m") m, COUNT(*) n FROM drivers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY m',
            'companies'    => 'SELECT DATE_FORMAT(created_at, "%Y-%m") m, COUNT(*) n FROM companies WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY m',
            'listings'     => 'SELECT DATE_FORMAT(created_at, "%Y-%m") m, COUNT(*) n FROM job_listings WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY m',
            'applications' => 'SELECT DATE_FORMAT(created_at, "%Y-%m") m, COUNT(*) n FROM job_applications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY m',
        ] as $key => $sql) {
            $series[$key] = $this->pdo->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
        }

        return $series;
    }

    private function prepared(string $sql, array $params): \PDOStatement
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);

        return $st;
    }
}

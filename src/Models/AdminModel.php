<?php

namespace Drivejob\Models;

use PDO;
use PDOException;

/**
 * AdminModel - Μοντέλο για τη διαχείριση των administrators
 * 
 * Παρέχει όλες τις λειτουργίες βάσης δεδομένων για το admin system
 */
class AdminModel extends BaseModel
{
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo, 'admins');
    }

    /**
     * Αυθεντικοποίηση admin χρήστη
     */
    public function authenticate($email, $password)
    {
        try {
            $sql = "SELECT * FROM admins WHERE email = :email AND is_active = 1";
            $admin = $this->queryOne($sql, ['email' => $email]);

            if ($admin && password_verify($password, $admin['password'])) {
                // Έλεγχος για account lockout
                if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
                    return false;
                }

                // Reset login attempts και ενημέρωση last_login
                $this->update([
                    'last_login' => date('Y-m-d H:i:s'),
                    'login_attempts' => 0,
                    'locked_until' => null
                ], ['id' => $admin['id']]);

                return $admin;
            } else {
                // Αύξηση login attempts
                if ($admin) {
                    $attempts = $admin['login_attempts'] + 1;
                    $updateData = ['login_attempts' => $attempts];

                    // Lock account μετά από 5 αποτυχημένες προσπάθειες
                    if ($attempts >= 5) {
                        $updateData['locked_until'] = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                    }

                    $this->update($updateData, ['id' => $admin['id']]);
                }

                return false;
            }
        } catch (PDOException $e) {
            error_log("Admin authentication error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Λήψη χρηστών (οδηγοί και εταιρείες)
     */
    public function getUsers($type = 'all', $page = 1, $limit = 20, $search = '', $status = 'all')
    {
        $offset = ($page - 1) * $limit;
        $params = [];
        $whereConditions = [];

        // Κατασκευή query βάσει τύπου
        if ($type === 'drivers') {
            $baseQuery = "FROM drivers d";
            $selectFields = "d.id, d.email, d.first_name, d.last_name, d.phone, d.is_verified, d.created_at, 'driver' as user_type";
        } elseif ($type === 'companies') {
            $baseQuery = "FROM companies c";
            $selectFields = "c.id, c.email, c.company_name as first_name, '' as last_name, c.phone, c.is_verified, c.created_at, 'company' as user_type";
        } else {
            // Union για όλους τους χρήστες
            $driversQuery = "SELECT d.id, d.email, d.first_name, d.last_name, d.phone, d.is_verified, d.created_at, 'driver' as user_type FROM drivers d";
            $companiesQuery = "SELECT c.id, c.email, c.company_name as first_name, '' as last_name, c.phone, c.is_verified, c.created_at, 'company' as user_type FROM companies c";

            if ($search) {
                $searchCondition = " WHERE (email LIKE :search OR first_name LIKE :search OR last_name LIKE :search)";
                $driversQuery .= str_replace('first_name', 'd.first_name', $searchCondition);
                $driversQuery = str_replace('last_name', 'd.last_name', $driversQuery);
                $companiesQuery .= str_replace('first_name', 'c.company_name', $searchCondition);
                $params['search'] = "%{$search}%";
            }

            if ($status !== 'all') {
                $statusCondition = $search ? " AND " : " WHERE ";
                $statusCondition .= "is_verified = :status";
                $driversQuery .= $statusCondition;
                $companiesQuery .= $statusCondition;
                $params['status'] = ($status === 'verified') ? 1 : 0;
            }

            $sql = "({$driversQuery}) UNION ({$companiesQuery}) ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            $params['limit'] = $limit;
            $params['offset'] = $offset;

            return $this->query($sql, $params);
        }

        // Search conditions
        if ($search) {
            if ($type === 'drivers') {
                $whereConditions[] = "(d.email LIKE :search OR d.first_name LIKE :search OR d.last_name LIKE :search)";
            } else {
                $whereConditions[] = "(c.email LIKE :search OR c.company_name LIKE :search)";
            }
            $params['search'] = "%{$search}%";
        }

        // Status filter
        if ($status !== 'all') {
            $prefix = ($type === 'drivers') ? 'd' : 'c';
            $whereConditions[] = "{$prefix}.is_verified = :status";
            $params['status'] = ($status === 'verified') ? 1 : 0;
        }

        $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $sql = "SELECT {$selectFields} {$baseQuery} {$whereClause} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->query($sql, $params);
    }

    /**
     * Toggle κατάστασης χρήστη
     */
    public function toggleUserStatus($userId, $userType)
    {
        try {
            $table = ($userType === 'driver') ? 'drivers' : 'companies';

            // Λήψη τρέχουσας κατάστασης
            $sql = "SELECT is_verified FROM {$table} WHERE id = :id";
            $current = $this->queryOne($sql, ['id' => $userId]);

            if (!$current) {
                return false;
            }

            $newStatus = $current['is_verified'] ? 0 : 1;

            $sql = "UPDATE {$table} SET is_verified = :status WHERE id = :id";
            return $this->execute($sql, ['status' => $newStatus, 'id' => $userId]);
        } catch (PDOException $e) {
            error_log("Toggle user status error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Λήψη αγγελιών εργασίας
     */
    public function getJobListings($page = 1, $limit = 20, $search = '', $status = 'all')
    {
        $offset = ($page - 1) * $limit;
        $params = [];
        $whereConditions = [];

        if ($search) {
            $whereConditions[] = "(title LIKE :search OR description LIKE :search OR location LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        if ($status !== 'all') {
            $whereConditions[] = "status = :status";
            $params['status'] = $status;
        }

        $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $sql = "SELECT jl.*, 
                CASE 
                    WHEN jl.user_type = 'driver' THEN CONCAT(d.first_name, ' ', d.last_name)
                    WHEN jl.user_type = 'company' THEN c.company_name
                    ELSE 'Unknown'
                END as creator_name
                FROM job_listings jl
                LEFT JOIN drivers d ON jl.user_id = d.id AND jl.user_type = 'driver'
                LEFT JOIN companies c ON jl.user_id = c.id AND jl.user_type = 'company'
                {$whereClause}
                ORDER BY jl.created_at DESC 
                LIMIT :limit OFFSET :offset";

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->query($sql, $params);
    }

    /**
     * Στατιστικά για το dashboard
     */
    public function getTotalDrivers()
    {
        return $this->queryOne("SELECT COUNT(*) as count FROM drivers")['count'];
    }

    public function getTotalCompanies()
    {
        return $this->queryOne("SELECT COUNT(*) as count FROM companies")['count'];
    }

    public function getTotalJobListings()
    {
        return $this->queryOne("SELECT COUNT(*) as count FROM job_listings")['count'];
    }

    public function getActiveMatches()
    {
        return $this->queryOne("SELECT COUNT(*) as count FROM match_history WHERE status = 'active'")['count'] ?? 0;
    }

    public function getNewRegistrationsToday()
    {
        $sql = "SELECT 
                (SELECT COUNT(*) FROM drivers WHERE DATE(created_at) = CURDATE()) +
                (SELECT COUNT(*) FROM companies WHERE DATE(created_at) = CURDATE()) as count";
        return $this->queryOne($sql)['count'];
    }

    public function getNewJobListingsToday()
    {
        return $this->queryOne("SELECT COUNT(*) as count FROM job_listings WHERE DATE(created_at) = CURDATE()")['count'];
    }

    public function getRecentActivity($limit = 10)
    {
        $sql = "SELECT 
                'driver_registration' as type,
                CONCAT(first_name, ' ', last_name) as description,
                created_at
                FROM drivers 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                
                UNION ALL
                
                SELECT 
                'company_registration' as type,
                company_name as description,
                created_at
                FROM companies 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                
                UNION ALL
                
                SELECT 
                'job_listing' as type,
                title as description,
                created_at
                FROM job_listings 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                
                ORDER BY created_at DESC 
                LIMIT :limit";

        return $this->query($sql, ['limit' => $limit]);
    }

    /**
     * Analytics δεδομένα
     */
    public function getAnalytics($period = '30days')
    {
        $days = ($period === '7days') ? 7 : (($period === '90days') ? 90 : 30);

        $sql = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as registrations
                FROM (
                    SELECT created_at FROM drivers WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                    UNION ALL
                    SELECT created_at FROM companies WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                ) as all_registrations
                GROUP BY DATE(created_at)
                ORDER BY date DESC";

        return $this->query($sql, ['days' => $days]);
    }

    /**
     * Ρυθμίσεις συστήματος
     */
    public function getSystemSettings()
    {
        // Προσωρινά επιστρέφουμε default settings
        // Στο μέλλον θα δημιουργήσουμε πίνακα settings
        return [
            'site_name' => 'DriveJob',
            'site_description' => 'Πλατφόρμα εύρεσης εργασίας για οδηγούς',
            'admin_email' => 'admin@drivejob.gr',
            'maintenance_mode' => false,
            'registration_enabled' => true,
            'email_verification_required' => true
        ];
    }

    public function updateSystemSettings($settings)
    {
        // Προσωρινά επιστρέφουμε true
        // Στο μέλλον θα υλοποιήσουμε πίνακα settings
        return true;
    }

    /**
     * Activity logs
     */
    public function getActivityLogs($page = 1, $limit = 50, $adminId = null, $action = '', $dateFrom = '', $dateTo = '')
    {
        $offset = ($page - 1) * $limit;
        $params = [];
        $whereConditions = [];

        if ($adminId) {
            $whereConditions[] = "al.admin_id = :admin_id";
            $params['admin_id'] = $adminId;
        }

        if ($action) {
            $whereConditions[] = "al.action LIKE :action";
            $params['action'] = "%{$action}%";
        }

        if ($dateFrom) {
            $whereConditions[] = "DATE(al.created_at) >= :date_from";
            $params['date_from'] = $dateFrom;
        }

        if ($dateTo) {
            $whereConditions[] = "DATE(al.created_at) <= :date_to";
            $params['date_to'] = $dateTo;
        }

        $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        $sql = "SELECT al.*, 
                CONCAT(a.first_name, ' ', a.last_name) as admin_name
                FROM admin_activity_logs al
                JOIN admins a ON al.admin_id = a.id
                {$whereClause}
                ORDER BY al.created_at DESC 
                LIMIT :limit OFFSET :offset";

        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->query($sql, $params);
    }
}

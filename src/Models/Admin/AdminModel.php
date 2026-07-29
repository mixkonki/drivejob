<?php

namespace Drivejob\Models\Admin;

use PDO;
use PDOException;
use Drivejob\Models\BaseModel;
use Drivejob\Core\Logger;

/**
 * AdminModel - Μοντέλο για τη διαχείριση των administrators
 * 
 * Παρέχει όλες τις λειτουργίες βάσης δεδομένων για το admin system
 */
class AdminModel extends BaseModel
{
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo, 'users');
    }

    /**
     * Αυθεντικοποίηση admin χρήστη
     */
    public function authenticate($email, $password)
    {
        try {
            $sql = "SELECT * FROM users WHERE username = :email AND role = 'admin'";
            $admin = $this->queryOne($sql, ['email' => $email]);

            if ($admin && password_verify($password, $admin['password'])) {
                // Έλεγχος για account lockout
                if (isset($admin['locked_until']) && $admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
                    return false;
                }

                // Reset login attempts και ενημέρωση last_login
                if (isset($admin['login_attempts'])) {
                    $this->update([
                        'last_login' => date('Y-m-d H:i:s'),
                        'login_attempts' => 0,
                        'locked_until' => null
                    ], ['id' => $admin['id']]);
                }

                return $admin;
            } else {
                // Αύξηση login attempts
                if ($admin && isset($admin['login_attempts'])) {
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
            Logger::error("Admin authentication error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Λήψη χρηστών (οδηγοί και εταιρείες)
     */
    public function getUsers($type = 'all', $page = 1, $limit = 20, $search = '', $status = 'all')
    {
        try {
            $offset = ($page - 1) * $limit;
            $params = [];

            // Βασικά queries για drivers και companies
            $driversQuery = "SELECT 
                d.id, 
                d.email, 
                CONCAT(d.first_name, ' ', d.last_name) as name,
                d.first_name,
                d.last_name,
                d.phone, 
                d.is_verified, 
                d.is_active,
                d.created_at, 
                d.profile_image,
                'driver' as type 
                FROM drivers d";

            $companiesQuery = "SELECT 
                c.id, 
                c.email, 
                c.company_name as name,
                c.company_name as first_name,
                '' as last_name,
                c.phone, 
                c.is_verified,
                c.is_active, 
                c.created_at,
                c.company_logo as profile_image,
                'company' as type 
                FROM companies c";

            // Προσθήκη search conditions
            $searchConditions = [];
            if ($search) {
                $searchConditions[] = "(email LIKE :search OR first_name LIKE :search OR last_name LIKE :search OR phone LIKE :search)";
                $params['search'] = "%{$search}%";
            }

            // Προσθήκη status conditions
            if ($status === 'active') {
                $searchConditions[] = "is_verified = 1";
            } elseif ($status === 'inactive') {
                $searchConditions[] = "is_verified = 0";
            } elseif ($status === 'verified') {
                $searchConditions[] = "is_verified = 1";
            } elseif ($status === 'unverified') {
                $searchConditions[] = "is_verified = 0";
            }

            // Προσθήκη WHERE clause αν υπάρχουν conditions
            if (!empty($searchConditions)) {
                $whereClause = " WHERE " . implode(' AND ', $searchConditions);
                $driversQuery .= str_replace(
                    ['first_name', 'last_name', 'email', 'phone', 'is_verified'],
                    ['d.first_name', 'd.last_name', 'd.email', 'd.phone', 'd.is_verified'],
                    $whereClause
                );
                $companiesQuery .= str_replace(
                    ['first_name', 'email', 'phone', 'is_verified'],
                    ['c.company_name', 'c.email', 'c.phone', 'c.is_verified'],
                    $whereClause
                );
            }

            // Δημιουργία του τελικού query βάσει τύπου
            if ($type === 'driver') {
                $countQuery = str_replace('SELECT', 'SELECT COUNT(*) as total FROM (SELECT', $driversQuery) . ') as t';
                $dataQuery = $driversQuery . " ORDER BY d.created_at DESC LIMIT :limit OFFSET :offset";
            } elseif ($type === 'company') {
                $countQuery = str_replace('SELECT', 'SELECT COUNT(*) as total FROM (SELECT', $companiesQuery) . ') as t';
                $dataQuery = $companiesQuery . " ORDER BY c.created_at DESC LIMIT :limit OFFSET :offset";
            } else {
                // Union για όλους τους χρήστες
                $unionQuery = "({$driversQuery}) UNION ALL ({$companiesQuery})";
                $countQuery = "SELECT COUNT(*) as total FROM ({$unionQuery}) as t";
                $dataQuery = $unionQuery . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
            }

            // Εκτέλεση count query
            $stmt = $this->pdo->prepare($countQuery);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Εκτέλεση data query
            $params['limit'] = $limit;
            $params['offset'] = $offset;

            $stmt = $this->pdo->prepare($dataQuery);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'data' => $data,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ];
        } catch (PDOException $e) {
            Logger::error("Get users error: " . $e->getMessage());
            return [
                'data' => [],
                'pagination' => [
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => 0
                ]
            ];
        }
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
            Logger::error("Toggle user status error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Λήψη αγγελιών εργασίας
     */
    public function getJobListings($page = 1, $limit = 20, $search = '', $status = 'all')
    {
        try {
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

            // Count query
            $countSql = "SELECT COUNT(*) as total FROM job_listings jl {$whereClause}";
            $stmt = $this->pdo->prepare($countSql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Data query
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

            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'data' => $data,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ];
        } catch (PDOException $e) {
            Logger::error("Get job listings error: " . $e->getMessage());
            return [
                'data' => [],
                'pagination' => [
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => 0
                ]
            ];
        }
    }

    /**
     * Στατιστικά για το dashboard
     */
    public function getTotalDrivers()
    {
        try {
            $result = $this->queryOne("SELECT COUNT(*) as count FROM drivers");
            return $result ? $result['count'] : 0;
        } catch (PDOException $e) {
            Logger::error("Get total drivers error: " . $e->getMessage());
            return 0;
        }
    }

    public function getTotalCompanies()
    {
        try {
            $result = $this->queryOne("SELECT COUNT(*) as count FROM companies");
            return $result ? $result['count'] : 0;
        } catch (PDOException $e) {
            Logger::error("Get total companies error: " . $e->getMessage());
            return 0;
        }
    }

    public function getTotalJobListings()
    {
        try {
            $result = $this->queryOne("SELECT COUNT(*) as count FROM job_listings");
            return $result ? $result['count'] : 0;
        } catch (PDOException $e) {
            Logger::error("Get total job listings error: " . $e->getMessage());
            return 0;
        }
    }

    public function getActiveMatches()
    {
        try {
            $result = $this->queryOne("SELECT COUNT(*) as count FROM match_history WHERE status = 'active'");
            return $result ? $result['count'] : 0;
        } catch (PDOException $e) {
            Logger::error("Get active matches error: " . $e->getMessage());
            return 0;
        }
    }

    public function getNewRegistrationsToday()
    {
        try {
            $sql = "SELECT 
                    (SELECT COUNT(*) FROM drivers WHERE DATE(created_at) = CURDATE()) +
                    (SELECT COUNT(*) FROM companies WHERE DATE(created_at) = CURDATE()) as count";
            $result = $this->queryOne($sql);
            return $result ? $result['count'] : 0;
        } catch (PDOException $e) {
            Logger::error("Get new registrations today error: " . $e->getMessage());
            return 0;
        }
    }

    public function getNewJobListingsToday()
    {
        try {
            $result = $this->queryOne("SELECT COUNT(*) as count FROM job_listings WHERE DATE(created_at) = CURDATE()");
            return $result ? $result['count'] : 0;
        } catch (PDOException $e) {
            Logger::error("Get new job listings today error: " . $e->getMessage());
            return 0;
        }
    }

    public function getRecentActivity($limit = 10)
    {
        try {
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

            return $this->query($sql, ['limit' => $limit]) ?: [];
        } catch (PDOException $e) {
            Logger::error("Get recent activity error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Analytics δεδομένα
     */
    public function getAnalytics($period = '30days')
    {
        try {
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

            return $this->query($sql, ['days' => $days]) ?: [];
        } catch (PDOException $e) {
            Logger::error("Get analytics error: " . $e->getMessage());
            return [];
        }
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
        try {
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

            // Count query
            $countSql = "SELECT COUNT(*) as total FROM admin_activity_logs al {$whereClause}";
            $stmt = $this->pdo->prepare($countSql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Data query
            $sql = "SELECT al.*, 
                    u.username as admin_name
                    FROM admin_activity_logs al
                    LEFT JOIN users u ON al.admin_id = u.id
                    {$whereClause}
                    ORDER BY al.created_at DESC 
                    LIMIT :limit OFFSET :offset";

            $params['limit'] = $limit;
            $params['offset'] = $offset;

            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'data' => $data,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ];
        } catch (PDOException $e) {
            Logger::error("Get activity logs error: " . $e->getMessage());
            return [
                'data' => [],
                'pagination' => [
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => 0
                ]
            ];
        }
    }
}

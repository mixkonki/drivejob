<?php

namespace Drivejob\Models\Admin;

use PDO;
use PDOException;
use Drivejob\Models\BaseModel;
use Drivejob\Core\Logger;

/**
 * SystemMonitoringModel - Μοντέλο για την παρακολούθηση του συστήματος
 * 
 * Παρέχει λειτουργίες για την παρακολούθηση της απόδοσης, των σφαλμάτων
 * και των στατιστικών χρήσης του συστήματος DriveJob
 */
class SystemMonitoringModel extends BaseModel
{
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo, 'system_logs');
    }

    /**
     * Λήψη σφαλμάτων συστήματος
     */
    public function getErrors($period = '7days', $page = 1, $limit = 50, $type = 'all', $search = '')
    {
        try {
            $offset = ($page - 1) * $limit;
            $params = [];
            $whereConditions = [];

            // Προσθήκη περιόδου
            $days = $this->getPeriodDays($period);
            $whereConditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
            $params['days'] = $days;

            // Προσθήκη τύπου
            if ($type !== 'all') {
                $whereConditions[] = "type = :type";
                $params['type'] = $type;
            }

            // Προσθήκη αναζήτησης
            if ($search) {
                $whereConditions[] = "(message LIKE :search OR context LIKE :search)";
                $params['search'] = "%{$search}%";
            }

            $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

            // Count query
            $countSql = "SELECT COUNT(*) as total FROM error_logs {$whereClause}";
            $stmt = $this->pdo->prepare($countSql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Data query
            $sql = "SELECT * FROM error_logs {$whereClause} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
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
            Logger::error("Get errors error: " . $e->getMessage());
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
     * Λήψη δεδομένων απόδοσης
     */
    public function getPerformanceData($period = '7days')
    {
        try {
            $days = $this->getPeriodDays($period);
            $sql = "SELECT 
                    DATE(created_at) as date,
                    AVG(response_time) as avg_response_time,
                    MAX(response_time) as max_response_time,
                    COUNT(*) as requests
                    FROM performance_logs
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('days', $days, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Get performance data error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Λήψη δεδομένων χρήσης
     */
    public function getUsageData($period = '30days')
    {
        try {
            $days = $this->getPeriodDays($period);
            $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(DISTINCT user_id) as active_users,
                    COUNT(*) as page_views,
                    SUM(CASE WHEN is_mobile = 1 THEN 1 ELSE 0 END) as mobile_views,
                    SUM(CASE WHEN is_mobile = 0 THEN 1 ELSE 0 END) as desktop_views
                    FROM usage_logs
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('days', $days, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Logger::error("Get usage data error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Λήψη αρχείων καταγραφής
     */
    public function getLogs($type = 'all', $page = 1, $limit = 100, $search = '', $dateFrom = '', $dateTo = '')
    {
        try {
            $offset = ($page - 1) * $limit;
            $params = [];
            $whereConditions = [];

            // Προσθήκη τύπου
            if ($type !== 'all') {
                $whereConditions[] = "type = :type";
                $params['type'] = $type;
            }

            // Προσθήκη αναζήτησης
            if ($search) {
                $whereConditions[] = "(message LIKE :search OR context LIKE :search)";
                $params['search'] = "%{$search}%";
            }

            // Προσθήκη ημερομηνίας από
            if ($dateFrom) {
                $whereConditions[] = "created_at >= :date_from";
                $params['date_from'] = $dateFrom . ' 00:00:00';
            }

            // Προσθήκη ημερομηνίας έως
            if ($dateTo) {
                $whereConditions[] = "created_at <= :date_to";
                $params['date_to'] = $dateTo . ' 23:59:59';
            }

            $whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

            // Count query
            $countSql = "SELECT COUNT(*) as total FROM system_logs {$whereClause}";
            $stmt = $this->pdo->prepare($countSql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Data query
            $sql = "SELECT * FROM system_logs {$whereClause} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
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
            Logger::error("Get logs error: " . $e->getMessage());
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
     * Εκκαθάριση αρχείων καταγραφής
     */
    public function clearLogs($type = 'all', $olderThan = 30)
    {
        try {
            $params = ['days' => $olderThan];
            $whereType = '';

            if ($type !== 'all') {
                $whereType = "AND type = :type";
                $params['type'] = $type;
            }

            $sql = "DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY) {$whereType}";
            $stmt = $this->pdo->prepare($sql);

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            return $stmt->execute();
        } catch (PDOException $e) {
            Logger::error("Clear logs error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Δημιουργία αντιγράφου ασφαλείας βάσης δεδομένων
     */
    public function createDatabaseBackup()
    {
        try {
            // Λήψη ρυθμίσεων βάσης δεδομένων
            $dbConfig = require ROOT_DIR . '/config/database.php';

            // Δημιουργία ονόματος αρχείου
            $backupDir = ROOT_DIR . '/backups';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $filename = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';

            // Εκτέλεση εντολής mysqldump
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s %s > %s',
                escapeshellarg($dbConfig->username),
                escapeshellarg($dbConfig->password),
                escapeshellarg($dbConfig->host),
                escapeshellarg($dbConfig->database),
                escapeshellarg($filename)
            );

            exec($command, $output, $returnVar);

            if ($returnVar === 0) {
                // Καταγραφή του backup
                $this->logBackup($filename);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Logger::error("Create database backup error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Καταγραφή αντιγράφου ασφαλείας
     */
    private function logBackup($filename)
    {
        try {
            $sql = "INSERT INTO database_backups (filename, size, created_at) VALUES (:filename, :size, NOW())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('filename', basename($filename));
            $stmt->bindValue('size', filesize($filename));
            return $stmt->execute();
        } catch (PDOException $e) {
            Logger::error("Log backup error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Λήψη στατιστικών βάσης δεδομένων
     */
    public function getDatabaseStats()
    {
        try {
            // Μέγεθος βάσης δεδομένων
            $sql = "SELECT 
                    table_schema as 'database',
                    SUM(data_length + index_length) as 'size'
                    FROM information_schema.TABLES 
                    WHERE table_schema = DATABASE()
                    GROUP BY table_schema";

            $dbSize = $this->queryOne($sql);

            // Αριθμός πινάκων
            $sql = "SELECT COUNT(*) as count FROM information_schema.TABLES WHERE table_schema = DATABASE()";
            $tablesCount = $this->queryOne($sql);

            // Αριθμός εγγραφών ανά πίνακα
            $sql = "SELECT 
                    table_name, 
                    table_rows
                    FROM information_schema.TABLES 
                    WHERE table_schema = DATABASE()
                    ORDER BY table_rows DESC";

            $tableRows = $this->query($sql);

            return [
                'size' => $dbSize['size'] ?? 0,
                'tables_count' => $tablesCount['count'] ?? 0,
                'table_rows' => $tableRows
            ];
        } catch (PDOException $e) {
            Logger::error("Get database stats error: " . $e->getMessage());
            return [
                'size' => 0,
                'tables_count' => 0,
                'table_rows' => []
            ];
        }
    }

    /**
     * Λήψη στατιστικών σφαλμάτων
     */
    public function getErrorStats()
    {
        try {
            // Σφάλματα ανά ημέρα (τελευταίες 7 ημέρες)
            $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as count
                    FROM error_logs
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date DESC";

            $errorsPerDay = $this->query($sql);

            // Σφάλματα ανά τύπο
            $sql = "SELECT 
                    type,
                    COUNT(*) as count
                    FROM error_logs
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY type
                    ORDER BY count DESC";

            $errorsPerType = $this->query($sql);

            // Συνολικά σφάλματα
            $sql = "SELECT COUNT(*) as count FROM error_logs";
            $totalErrors = $this->queryOne($sql);

            return [
                'total' => $totalErrors['count'] ?? 0,
                'per_day' => $errorsPerDay,
                'per_type' => $errorsPerType
            ];
        } catch (PDOException $e) {
            Logger::error("Get error stats error: " . $e->getMessage());
            return [
                'total' => 0,
                'per_day' => [],
                'per_type' => []
            ];
        }
    }

    /**
     * Λήψη στατιστικών απόδοσης
     */
    public function getPerformanceStats()
    {
        try {
            // Μέσος χρόνος απόκρισης (τελευταίες 7 ημέρες)
            $sql = "SELECT 
                    AVG(response_time) as avg_response_time
                    FROM performance_logs
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";

            $avgResponseTime = $this->queryOne($sql);

            // Μέγιστος χρόνος απόκρισης (τελευταίες 7 ημέρες)
            $sql = "SELECT 
                    MAX(response_time) as max_response_time
                    FROM performance_logs
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";

            $maxResponseTime = $this->queryOne($sql);

            // Αιτήματα ανά ημέρα (τελευταίες 7 ημέρες)
            $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as count
                    FROM performance_logs
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date DESC";

            $requestsPerDay = $this->query($sql);

            return [
                'avg_response_time' => $avgResponseTime['avg_response_time'] ?? 0,
                'max_response_time' => $maxResponseTime['max_response_time'] ?? 0,
                'requests_per_day' => $requestsPerDay
            ];
        } catch (PDOException $e) {
            Logger::error("Get performance stats error: " . $e->getMessage());
            return [
                'avg_response_time' => 0,
                'max_response_time' => 0,
                'requests_per_day' => []
            ];
        }
    }

    /**
     * Λήψη στατιστικών χρήσης
     */
    public function getUsageStats()
    {
        try {
            // Ενεργοί χρήστες ανά ημέρα (τελευταίες 30 ημέρες)
            $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(DISTINCT user_id) as active_users
                    FROM usage_logs
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date DESC";

            $activeUsersPerDay = $this->query($sql);

            // Προβολές σελίδων ανά ημέρα (τελευταίες 30 ημέρες)
            $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as page_views
                    FROM usage_logs
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    GROUP BY DATE(created_at)
                    ORDER BY date DESC";

            $pageViewsPerDay = $this->query($sql);

            // Κατανομή συσκευών (τελευταίες 30 ημέρες)
            $sql = "SELECT 
                    SUM(CASE WHEN is_mobile = 1 THEN 1 ELSE 0 END) as mobile,
                    SUM(CASE WHEN is_mobile = 0 THEN 1 ELSE 0 END) as desktop
                    FROM usage_logs
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

            $deviceDistribution = $this->queryOne($sql);

            return [
                'active_users_per_day' => $activeUsersPerDay,
                'page_views_per_day' => $pageViewsPerDay,
                'device_distribution' => [
                    'mobile' => $deviceDistribution['mobile'] ?? 0,
                    'desktop' => $deviceDistribution['desktop'] ?? 0
                ]
            ];
        } catch (PDOException $e) {
            Logger::error("Get usage stats error: " . $e->getMessage());
            return [
                'active_users_per_day' => [],
                'page_views_per_day' => [],
                'device_distribution' => [
                    'mobile' => 0,
                    'desktop' => 0
                ]
            ];
        }
    }

    /**
     * Λήψη στατιστικών αντιγράφων ασφαλείας
     */
    public function getBackupStats()
    {
        try {
            // Τελευταίο αντίγραφο ασφαλείας
            $sql = "SELECT * FROM database_backups ORDER BY created_at DESC LIMIT 1";
            $lastBackup = $this->queryOne($sql);

            // Συνολικά αντίγραφα ασφαλείας
            $sql = "SELECT COUNT(*) as count FROM database_backups";
            $totalBackups = $this->queryOne($sql);

            // Αντίγραφα ασφαλείας ανά μήνα
            $sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as count
                    FROM database_backups
                    GROUP BY month
                    ORDER BY month DESC";

            $backupsPerMonth = $this->query($sql);

            return [
                'last_backup' => $lastBackup,
                'total_backups' => $totalBackups['count'] ?? 0,
                'backups_per_month' => $backupsPerMonth
            ];
        } catch (PDOException $e) {
            Logger::error("Get backup stats error: " . $e->getMessage());
            return [
                'last_backup' => null,
                'total_backups' => 0,
                'backups_per_month' => []
            ];
        }
    }

    /**
     * Μετατροπή περιόδου σε ημέρες
     */
    private function getPeriodDays($period)
    {
        switch ($period) {
            case '1day':
                return 1;
            case '7days':
                return 7;
            case '30days':
                return 30;
            case '90days':
                return 90;
            case '1year':
                return 365;
            default:
                return 7;
        }
    }
}

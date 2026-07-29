<?php

namespace Drivejob\Models\Admin;

/**
 * DummySystemMonitoringModel - Προσωρινό μοντέλο για το σύστημα παρακολούθησης
 * 
 * Παρέχει δοκιμαστικά δεδομένα για το σύστημα παρακολούθησης
 * μέχρι να επιλυθούν τα προβλήματα με το κανονικό μοντέλο
 */
class DummySystemMonitoringModel implements SystemMonitoringModelInterface
{
    /**
     * Λήψη τρέχουσας κατάστασης συστήματος
     */
    public function getSystemStatus()
    {
        return [
            'status' => 'healthy',
            'database' => [
                'connected' => true,
                'response_time' => 45,
                'status' => 'good'
            ],
            'disk_space' => [
                'total' => '100 GB',
                'used' => '45 GB',
                'free' => '55 GB',
                'percentage' => 45,
                'status' => 'good'
            ],
            'memory' => [
                'current' => '128 MB',
                'peak' => '256 MB',
                'limit' => '512 MB',
                'percentage' => 25,
                'status' => 'good'
            ],
            'cpu' => [
                'usage' => 35,
                'status' => 'good'
            ],
            'logs' => [
                'files' => [],
                'total_size' => '15 MB',
                'status' => 'good'
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Λήψη μετρικών απόδοσης
     */
    public function getPerformanceMetrics($period = '24h')
    {
        return [
            'response_time' => [
                'average' => 250,
                'unit' => 'ms'
            ],
            'requests' => [
                'total' => 5000,
                'per_minute' => 83.33
            ],
            'error_rate' => 0.5,
            'active_users' => 25,
            'database' => [
                'size' => '125 MB',
                'tables' => 44,
                'connections' => 10
            ],
            'period' => $period
        ];
    }

    /**
     * Λήψη πρόσφατων σφαλμάτων
     */
    public function getRecentErrors($limit = 50)
    {
        $errors = [];
        for ($i = 0; $i < min($limit, 10); $i++) {
            $errors[] = [
                'id' => $i + 1,
                'error_type' => ['warning', 'error', 'info'][rand(0, 2)],
                'error_message' => 'Δοκιμαστικό σφάλμα #' . ($i + 1),
                'stack_trace' => 'Stack trace here...',
                'user_id' => rand(1, 100),
                'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 24) . ' hours'))
            ];
        }
        return $errors;
    }

    /**
     * Λήψη στατιστικών χρήσης
     */
    public function getUsageStatistics($period = '7d')
    {
        return [
            'registrations' => [
                ['date' => date('Y-m-d'), 'count' => 5, 'type' => 'driver'],
                ['date' => date('Y-m-d'), 'count' => 2, 'type' => 'company']
            ],
            'logins' => [
                ['date' => date('Y-m-d'), 'count' => 45]
            ],
            'popular_pages' => [
                ['page_url' => '/drivers/profile', 'views' => 150, 'avg_load_time' => 250],
                ['page_url' => '/companies/profile', 'views' => 120, 'avg_load_time' => 280],
                ['page_url' => '/job-listings', 'views' => 100, 'avg_load_time' => 300]
            ],
            'user_types' => [
                ['type' => 'drivers', 'total' => 11, 'active' => 8],
                ['type' => 'companies', 'total' => 1, 'active' => 1]
            ],
            'period' => $period
        ];
    }

    /**
     * Λήψη δεδομένων για γραφήματα
     */
    public function getChartData($chartType, $period = '7d')
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $data[] = [
                'date' => date('Y-m-d', strtotime("-{$i} days")),
                'value' => rand(50, 200)
            ];
        }
        return $data;
    }

    /**
     * Καταγραφή μετρικής απόδοσης
     */
    public function logPerformanceMetric($type, $value, $metadata = [])
    {
        // Προσομοίωση επιτυχίας
        return true;
    }

    /**
     * Καταγραφή σφάλματος συστήματος
     */
    public function logSystemError($errorType, $message, $stackTrace = '', $userId = null)
    {
        // Προσομοίωση επιτυχίας
        return true;
    }

    /**
     * Επιστρέφει δοκιμαστικά στατιστικά συστήματος
     * 
     * @return array
     */
    public function getSystemStats()
    {
        return [
            'server' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Apache',
                'memory_usage' => memory_get_usage(true),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size')
            ],
            'database' => [
                'size' => 1024 * 1024 * 1.44, // 1.44 MB
                'tables_count' => 44,
                'table_rows' => [
                    ['table_name' => 'users', 'table_rows' => 120],
                    ['table_name' => 'drivers', 'table_rows' => 85],
                    ['table_name' => 'companies', 'table_rows' => 35],
                    ['table_name' => 'job_listings', 'table_rows' => 75],
                    ['table_name' => 'match_history', 'table_rows' => 42]
                ]
            ],
            'errors' => [
                'total' => 24,
                'per_day' => [
                    ['date' => date('Y-m-d', strtotime('-6 days')), 'count' => 5],
                    ['date' => date('Y-m-d', strtotime('-5 days')), 'count' => 3],
                    ['date' => date('Y-m-d', strtotime('-4 days')), 'count' => 7],
                    ['date' => date('Y-m-d', strtotime('-3 days')), 'count' => 2],
                    ['date' => date('Y-m-d', strtotime('-2 days')), 'count' => 4],
                    ['date' => date('Y-m-d', strtotime('-1 days')), 'count' => 1],
                    ['date' => date('Y-m-d'), 'count' => 2]
                ],
                'per_type' => [
                    ['type' => 'error', 'count' => 12],
                    ['type' => 'warning', 'count' => 8],
                    ['type' => 'notice', 'count' => 3],
                    ['type' => 'deprecated', 'count' => 1]
                ]
            ],
            'performance' => [
                'avg_response_time' => 0.245, // seconds
                'max_response_time' => 1.2, // seconds
                'requests_per_day' => [
                    ['date' => date('Y-m-d', strtotime('-6 days')), 'count' => 1250, 'avg_response_time' => 0.22],
                    ['date' => date('Y-m-d', strtotime('-5 days')), 'count' => 1420, 'avg_response_time' => 0.24],
                    ['date' => date('Y-m-d', strtotime('-4 days')), 'count' => 1380, 'avg_response_time' => 0.23],
                    ['date' => date('Y-m-d', strtotime('-3 days')), 'count' => 1510, 'avg_response_time' => 0.25],
                    ['date' => date('Y-m-d', strtotime('-2 days')), 'count' => 1620, 'avg_response_time' => 0.26],
                    ['date' => date('Y-m-d', strtotime('-1 days')), 'count' => 1480, 'avg_response_time' => 0.24],
                    ['date' => date('Y-m-d'), 'count' => 980, 'avg_response_time' => 0.23]
                ]
            ],
            'usage' => [
                'active_users_per_day' => [
                    ['date' => date('Y-m-d', strtotime('-6 days')), 'active_users' => 85],
                    ['date' => date('Y-m-d', strtotime('-5 days')), 'active_users' => 92],
                    ['date' => date('Y-m-d', strtotime('-4 days')), 'active_users' => 88],
                    ['date' => date('Y-m-d', strtotime('-3 days')), 'active_users' => 95],
                    ['date' => date('Y-m-d', strtotime('-2 days')), 'active_users' => 105],
                    ['date' => date('Y-m-d', strtotime('-1 days')), 'active_users' => 98],
                    ['date' => date('Y-m-d'), 'active_users' => 78]
                ],
                'page_views_per_day' => [
                    ['date' => date('Y-m-d', strtotime('-6 days')), 'page_views' => 3250],
                    ['date' => date('Y-m-d', strtotime('-5 days')), 'page_views' => 3420],
                    ['date' => date('Y-m-d', strtotime('-4 days')), 'page_views' => 3380],
                    ['date' => date('Y-m-d', strtotime('-3 days')), 'page_views' => 3510],
                    ['date' => date('Y-m-d', strtotime('-2 days')), 'page_views' => 3620],
                    ['date' => date('Y-m-d', strtotime('-1 days')), 'page_views' => 3480],
                    ['date' => date('Y-m-d'), 'page_views' => 2980]
                ],
                'device_distribution' => [
                    'mobile' => 2850,
                    'desktop' => 4150
                ]
            ],
            'backups' => [
                'last_backup' => [
                    'filename' => 'backup_' . date('Y-m-d_H-i-s', strtotime('-2 days')) . '.sql',
                    'size' => 1024 * 1024 * 8.5, // 8.5 MB
                    'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
                ],
                'total_backups' => 12,
                'backups_per_month' => [
                    ['month' => date('Y-m', strtotime('-2 months')), 'count' => 4],
                    ['month' => date('Y-m', strtotime('-1 months')), 'count' => 5],
                    ['month' => date('Y-m'), 'count' => 3]
                ]
            ]
        ];
    }

    /**
     * Προσομοίωση δημιουργίας αντιγράφου ασφαλείας
     * 
     * @return bool
     */
    public function createDatabaseBackup()
    {
        // Προσομοίωση επιτυχίας
        return true;
    }

    /**
     * Προσομοίωση λήψης σφαλμάτων
     * 
     * @param string $period
     * @param int $page
     * @param int $limit
     * @param string $type
     * @param string $search
     * @return array
     */
    public function getErrors($period = '7days', $page = 1, $limit = 50, $type = 'all', $search = '')
    {
        // Δημιουργία δοκιμαστικών δεδομένων
        $data = [];
        for ($i = 0; $i < 10; $i++) {
            $data[] = [
                'id' => $i + 1,
                'type' => ['error', 'warning', 'notice'][rand(0, 2)],
                'message' => 'Δοκιμαστικό σφάλμα #' . ($i + 1),
                'file' => 'src/Controllers/SomeController.php',
                'line' => rand(10, 200),
                'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 7) . ' days'))
            ];
        }

        return [
            'data' => $data,
            'pagination' => [
                'total' => 24,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil(24 / $limit)
            ]
        ];
    }

    /**
     * Προσομοίωση λήψης δεδομένων απόδοσης
     * 
     * @param string $period
     * @return array
     */
    public function getPerformanceData($period = '7days')
    {
        $data = [];
        for ($i = 0; $i < 7; $i++) {
            $data[] = [
                'date' => date('Y-m-d', strtotime('-' . (6 - $i) . ' days')),
                'avg_response_time' => rand(200, 300) / 1000,
                'max_response_time' => rand(800, 1500) / 1000,
                'requests' => rand(800, 1800)
            ];
        }

        return $data;
    }

    /**
     * Προσομοίωση λήψης δεδομένων χρήσης
     * 
     * @param string $period
     * @return array
     */
    public function getUsageData($period = '30days')
    {
        $data = [];
        for ($i = 0; $i < 7; $i++) {
            $data[] = [
                'date' => date('Y-m-d', strtotime('-' . (6 - $i) . ' days')),
                'active_users' => rand(70, 120),
                'page_views' => rand(2500, 4000),
                'mobile_views' => rand(1000, 1500),
                'desktop_views' => rand(1500, 2500)
            ];
        }

        return $data;
    }

    /**
     * Προσομοίωση λήψης αρχείων καταγραφής
     * 
     * @param string $type
     * @param int $page
     * @param int $limit
     * @param string $search
     * @param string $dateFrom
     * @param string $dateTo
     * @return array
     */
    public function getLogs($type = 'all', $page = 1, $limit = 100, $search = '', $dateFrom = '', $dateTo = '')
    {
        $data = [];
        for ($i = 0; $i < 20; $i++) {
            $data[] = [
                'id' => $i + 1,
                'type' => ['info', 'warning', 'error', 'debug'][rand(0, 3)],
                'message' => 'Δοκιμαστικό μήνυμα καταγραφής #' . ($i + 1),
                'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'))
            ];
        }

        return [
            'data' => $data,
            'pagination' => [
                'total' => 120,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil(120 / $limit)
            ]
        ];
    }
}

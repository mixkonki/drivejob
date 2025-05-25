<?php

namespace Drivejob\Helpers;

/**
 * MonitoringHelper - Βοηθητικές συναρτήσεις για το σύστημα παρακολούθησης
 */
class MonitoringHelper
{
    /**
     * Μορφοποίηση bytes σε ανθρώπινα αναγνώσιμη μορφή
     */
    public static function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Δημιουργία δοκιμαστικών δεδομένων για το dashboard
     */
    public static function getDefaultStats()
    {
        return [
            'server' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
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
}

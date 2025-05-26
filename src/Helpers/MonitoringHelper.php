<?php

namespace Drivejob\Helpers;

/**
 * Helper class για το monitoring system
 */
class MonitoringHelper
{
    /**
     * Επιστροφή προεπιλεγμένων στατιστικών για το dashboard
     */
    public static function getDefaultStats(): array
    {
        return [
            'system_status' => [
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
            ],
            'performance' => [
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
                'period' => '24h'
            ],
            'errors' => [
                [
                    'id' => 1,
                    'error_type' => 'warning',
                    'error_message' => 'Δοκιμαστικό σφάλμα #1',
                    'stack_trace' => 'Stack trace here...',
                    'user_id' => null,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
                ],
                [
                    'id' => 2,
                    'error_type' => 'info',
                    'error_message' => 'Δοκιμαστικό σφάλμα #2',
                    'stack_trace' => 'Stack trace here...',
                    'user_id' => null,
                    'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours'))
                ]
            ],
            'usage' => [
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
                'period' => '7d'
            ]
        ];
    }

    /**
     * Format bytes σε human-readable format
     */
    public static function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Format uptime σε human-readable format
     */
    public static function formatUptime($seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . ' ' . ($days == 1 ? 'day' : 'days');
        }
        if ($hours > 0) {
            $parts[] = $hours . ' ' . ($hours == 1 ? 'hour' : 'hours');
        }
        if ($minutes > 0) {
            $parts[] = $minutes . ' ' . ($minutes == 1 ? 'minute' : 'minutes');
        }

        return implode(', ', $parts) ?: '0 minutes';
    }

    /**
     * Υπολογισμός status βάσει τιμών
     */
    public static function calculateStatus($value, $warningThreshold = 70, $criticalThreshold = 90): string
    {
        if ($value >= $criticalThreshold) {
            return 'critical';
        } elseif ($value >= $warningThreshold) {
            return 'warning';
        } else {
            return 'good';
        }
    }

    /**
     * Δημιουργία χρωματικού κώδικα για status
     */
    public static function getStatusColor($status): string
    {
        switch ($status) {
            case 'critical':
            case 'error':
                return '#dc3545'; // Red
            case 'warning':
                return '#ffc107'; // Yellow
            case 'good':
            case 'healthy':
                return '#28a745'; // Green
            default:
                return '#6c757d'; // Gray
        }
    }

    /**
     * Δημιουργία CSS class για status
     */
    public static function getStatusClass($status): string
    {
        switch ($status) {
            case 'critical':
            case 'error':
                return 'status-critical';
            case 'warning':
                return 'status-warning';
            case 'good':
            case 'healthy':
                return 'status-good';
            default:
                return 'status-unknown';
        }
    }

    /**
     * Format χρονικής διάρκειας
     */
    public static function formatDuration($seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $seconds = $seconds % 60;
            return $minutes . 'm ' . $seconds . 's';
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $hours . 'h ' . $minutes . 'm';
        }
    }

    /**
     * Δημιουργία percentage bar HTML
     */
    public static function createProgressBar($percentage, $label = '', $showPercentage = true): string
    {
        $status = self::calculateStatus($percentage);
        $color = self::getStatusColor($status);

        $html = '<div class="progress-container">';
        if ($label) {
            $html .= '<div class="progress-label">' . htmlspecialchars($label) . '</div>';
        }
        $html .= '<div class="progress">';
        $html .= '<div class="progress-bar" style="width: ' . $percentage . '%; background-color: ' . $color . ';">';
        if ($showPercentage) {
            $html .= '<span class="progress-text">' . $percentage . '%</span>';
        }
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Δημιουργία alert message HTML
     */
    public static function createAlert($message, $type = 'info', $dismissible = true): string
    {
        $classes = 'alert alert-' . $type;
        if ($dismissible) {
            $classes .= ' alert-dismissible';
        }

        $html = '<div class="' . $classes . '" role="alert">';
        $html .= htmlspecialchars($message);
        if ($dismissible) {
            $html .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
            $html .= '<span aria-hidden="true">&times;</span>';
            $html .= '</button>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Έλεγχος αν μια υπηρεσία είναι διαθέσιμη
     */
    public static function checkServiceAvailability($host, $port, $timeout = 5): bool
    {
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        if ($fp) {
            fclose($fp);
            return true;
        }
        return false;
    }

    /**
     * Δημιουργία chart data για JavaScript
     */
    public static function prepareChartData($data, $type = 'line'): array
    {
        $labels = [];
        $values = [];

        foreach ($data as $item) {
            if (isset($item['date'])) {
                $labels[] = date('d/m', strtotime($item['date']));
            } elseif (isset($item['datetime'])) {
                $labels[] = date('H:i', strtotime($item['datetime']));
            }

            if (isset($item['value'])) {
                $values[] = $item['value'];
            } elseif (isset($item['count'])) {
                $values[] = $item['count'];
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Data',
                    'data' => $values,
                    'borderColor' => '#007bff',
                    'backgroundColor' => 'rgba(0, 123, 255, 0.1)',
                    'tension' => 0.1
                ]
            ]
        ];
    }
}
